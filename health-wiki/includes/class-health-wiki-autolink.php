<?php
/**
 * Auto Internal Linking — otomatis mengubah keyword dalam konten
 * menjadi link ke post Health Wiki dan post reguler.
 *
 * Aturan SEO:
 * - Hanya link kejadian pertama per keyword
 * - Maksimal 10 auto-link per halaman
 * - Tidak link ke diri sendiri (post saat ini dikecualikan)
 * - Tidak link di dalam tag: <a>, <h1>-<h6>, <script>, <style>, <code>, <pre>
 * - Link internal: tanpa nofollow, anchor text sesuai
 * - Panjang keyword minimum: 3 karakter
 * - Keyword terpanjang dicocokkan duluan (mencegah partial match)
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Autolink {

    private const CACHE_KEY  = 'hw_autolink_keywords';
    private const CACHE_TTL  = HOUR_IN_SECONDS;
    private const MAX_LINKS  = 10;
    private const MIN_LENGTH = 3;

    /** Tag-tag yang di-skip (tidak boleh ada link di dalamnya). */
    private const SKIP_TAGS = [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style', 'code', 'pre', 'button', 'input', 'textarea' ];

    public static function init(): void {
        add_filter( 'the_content', [ __CLASS__, 'process' ], 30 );
        add_action( 'save_post', [ __CLASS__, 'clear_cache' ] );
        add_action( 'delete_post', [ __CLASS__, 'clear_cache' ] );
    }

    /**
     * Ganti keyword di konten dengan link internal.
     */
    public static function process( string $content ): string {
        if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $current_id = get_the_ID();
        if ( ! $current_id ) {
            return $content;
        }

        $keywords = self::get_keywords( $current_id );
        if ( ! $keywords ) {
            return $content;
        }

        return self::replace_keywords( $content, $keywords );
    }

    /**
     * Hapus cache keyword saat post berubah.
     */
    public static function clear_cache(): void {
        delete_transient( self::CACHE_KEY );
    }

    /* ── Mesin Pengganti Keyword ─────────────────────────── */

    /** Proses penggantian keyword di teks (melewati tag HTML). */
    private static function replace_keywords( string $content, array $keywords ): string {
        /* Pecah konten menjadi tag HTML dan teks. */
        $parts = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

        if ( ! $parts ) {
            return $content;
        }

        $linked     = [];
        $link_count = 0;
        $skip_depth = []; /* Lacak kedalaman tag yang di-skip */

        foreach ( self::SKIP_TAGS as $tag ) {
            $skip_depth[ $tag ] = 0;
        }

        foreach ( $parts as &$part ) {
            /* Deteksi pembukaan / penutupan tag yang di-skip. */
            if ( isset( $part[0] ) && $part[0] === '<' ) {
                foreach ( self::SKIP_TAGS as $tag ) {
                    if ( preg_match( '/^<' . $tag . '[\s>]/i', $part ) ) {
                        $skip_depth[ $tag ]++;
                    } elseif ( preg_match( '/^<\/' . $tag . '>/i', $part ) ) {
                        $skip_depth[ $tag ] = max( 0, $skip_depth[ $tag ] - 1 );
                    }
                }
                continue; /* Jangan proses tag HTML itu sendiri. */
            }

            /* Cek apakah di dalam tag yang di-skip. */
            $inside_skip = false;
            foreach ( $skip_depth as $depth ) {
                if ( $depth > 0 ) {
                    $inside_skip = true;
                    break;
                }
            }
            if ( $inside_skip ) {
                continue;
            }

            /* Ganti keyword di node teks ini. */
            foreach ( $keywords as $keyword => $url ) {
                if ( $link_count >= self::MAX_LINKS ) {
                    break 2;
                }
                if ( isset( $linked[ $keyword ] ) ) {
                    continue;
                }

                $pattern = '/\b(' . preg_quote( $keyword, '/' ) . ')\b/iu';

                if ( preg_match( $pattern, $part ) ) {
                    $link = '<a href="' . esc_url( $url ) . '" class="hw-autolink">\1</a>';
                    $part = preg_replace( $pattern, $link, $part, 1 );
                    $linked[ $keyword ] = true;
                    $link_count++;
                }
            }
        }

        return implode( '', $parts );
    }

    /* ── Cache Keyword ───────────────────────────────────── */

    /** Ambil daftar keyword (kecuali post saat ini). */
    private static function get_keywords( int $exclude_id ): array {
        $all = get_transient( self::CACHE_KEY );

        if ( $all === false ) {
            $all = self::build_keyword_list();
            set_transient( self::CACHE_KEY, $all, self::CACHE_TTL );
        }

        /* Kecualikan post saat ini. */
        $result = [];
        foreach ( $all as $title => $data ) {
            if ( (int) $data['id'] !== $exclude_id ) {
                $result[ $title ] = $data['url'];
            }
        }

        return $result;
    }

    /** Bangun daftar keyword dari semua post type. */
    private static function build_keyword_list(): array {
        $post_types = array_merge( HW_POST_TYPES, [ 'post' ] );

        $posts = get_posts( [
            'post_type'      => $post_types,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ] );

        $keywords = [];
        foreach ( $posts as $p ) {
            $title = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
            if ( mb_strlen( $title ) < self::MIN_LENGTH ) {
                continue;
            }
            $keywords[ $title ] = [
                'url' => get_permalink( $p ),
                'id'  => $p->ID,
            ];
        }

        /* Urutkan dari terpanjang — keyword panjang dicocokkan duluan. */
        uksort( $keywords, static fn( string $a, string $b ): int => mb_strlen( $b ) - mb_strlen( $a ) );

        return $keywords;
    }
}
