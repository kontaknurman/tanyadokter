<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto Internal Linking — automatically links keywords in content
 * to matching Health Wiki posts and regular posts.
 *
 * SEO rules:
 * - Only first occurrence of each keyword is linked
 * - Max 10 auto-links per page
 * - No self-linking (current post excluded)
 * - No linking inside <a>, <h1>-<h6>, <script>, <style> tags
 * - Internal links: no nofollow, proper anchor text
 * - Min keyword length: 3 characters
 * - Longest keyword matched first (prevents partial matches)
 */
final class Health_Wiki_Autolink {

    private const CACHE_KEY  = 'hw_autolink_keywords';
    private const CACHE_TTL  = HOUR_IN_SECONDS;
    private const MAX_LINKS  = 10;
    private const MIN_LENGTH = 3;

    /** Tags where linking is skipped. */
    private const SKIP_TAGS = [ 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'script', 'style', 'code', 'pre', 'button', 'input', 'textarea' ];

    public static function init(): void {
        add_filter( 'the_content', [ __CLASS__, 'process' ], 30 );
        add_action( 'save_post', [ __CLASS__, 'clear_cache' ] );
        add_action( 'delete_post', [ __CLASS__, 'clear_cache' ] );
    }

    /**
     * Replace keyword occurrences in content with internal links.
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
     * Clear keyword cache when posts change.
     */
    public static function clear_cache(): void {
        delete_transient( self::CACHE_KEY );
    }

    /* ── Keyword Replacement Engine ───────────────────────── */

    private static function replace_keywords( string $content, array $keywords ): string {
        // Split content into HTML tags and text nodes.
        $parts = preg_split( '/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE );

        if ( ! $parts ) {
            return $content;
        }

        $linked     = [];
        $link_count = 0;
        $skip_depth = []; // track nesting of skip tags

        foreach ( self::SKIP_TAGS as $tag ) {
            $skip_depth[ $tag ] = 0;
        }

        foreach ( $parts as &$part ) {
            // Detect opening / closing of skip tags.
            if ( isset( $part[0] ) && $part[0] === '<' ) {
                foreach ( self::SKIP_TAGS as $tag ) {
                    if ( preg_match( '/^<' . $tag . '[\s>]/i', $part ) ) {
                        $skip_depth[ $tag ]++;
                    } elseif ( preg_match( '/^<\/' . $tag . '>/i', $part ) ) {
                        $skip_depth[ $tag ] = max( 0, $skip_depth[ $tag ] - 1 );
                    }
                }
                continue; // Don't process HTML tags themselves.
            }

            // Check if inside any skip tag.
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

            // Replace keywords in this text node.
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

    /* ── Keyword Cache ────────────────────────────────────── */

    private static function get_keywords( int $exclude_id ): array {
        $all = get_transient( self::CACHE_KEY );

        if ( $all === false ) {
            $all = self::build_keyword_list();
            set_transient( self::CACHE_KEY, $all, self::CACHE_TTL );
        }

        // Exclude current post.
        $result = [];
        foreach ( $all as $title => $data ) {
            if ( (int) $data['id'] !== $exclude_id ) {
                $result[ $title ] = $data['url'];
            }
        }

        return $result;
    }

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

        // Sort by length DESC — longest keywords first to prevent partial matches.
        uksort( $keywords, static fn( string $a, string $b ): int => mb_strlen( $b ) - mb_strlen( $a ) );

        return $keywords;
    }
}
