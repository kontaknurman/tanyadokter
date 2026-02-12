<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Archive {

    public static function init(): void {
        add_filter( 'template_include', [ __CLASS__, 'load_template' ] );
        add_filter( 'document_title_parts', [ __CLASS__, 'title' ] );
        add_action( 'wp_head', [ __CLASS__, 'meta_tags' ], 2 );
        add_action( 'wp_head', [ __CLASS__, 'schema' ], 1 );
    }

    /* ── Template ─────────────────────────────────────────── */

    public static function load_template( string $template ): string {
        if ( ! is_post_type_archive( HW_POST_TYPES ) ) {
            return $template;
        }
        $custom = HW_PATH . 'templates/archive-health-wiki.php';
        return file_exists( $custom ) ? $custom : $template;
    }

    /* ── Data for template ────────────────────────────────── */

    public static function get_data(): array {
        $obj       = get_queried_object();
        $post_type = $obj->name ?? '';

        $all = get_posts( [
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ] );

        $grouped = [];
        foreach ( $all as $p ) {
            $letter = mb_strtoupper( mb_substr( $p->post_title, 0, 1 ) );
            if ( ! preg_match( '/[A-Z]/', $letter ) ) {
                $letter = '#';
            }
            $grouped[ $letter ][] = $p;
        }
        ksort( $grouped );

        $raw     = isset( $_GET['huruf'] ) ? sanitize_text_field( wp_unslash( $_GET['huruf'] ) ) : '';
        $current = strtoupper( $raw );

        if ( $current && isset( $grouped[ $current ] ) ) {
            $display = [ $current => $grouped[ $current ] ];
        } else {
            $current = '';
            $display = $grouped;
        }

        return [
            'post_type'   => $post_type,
            'title'       => self::archive_title( $post_type ),
            'all_letters' => array_keys( $grouped ),
            'current'     => $current,
            'grouped'     => $display,
            'total'       => count( $all ),
            'placeholder' => self::search_placeholder( $post_type ),
            'archive_url' => get_post_type_archive_link( $post_type ) ?: '',
        ];
    }

    /* ── Document Title ───────────────────────────────────── */

    public static function title( array $parts ): array {
        if ( ! is_post_type_archive( HW_POST_TYPES ) ) {
            return $parts;
        }
        $obj = get_queried_object();
        $parts['title'] = self::archive_title( $obj->name ?? '' );
        return $parts;
    }

    /* ── Meta Tags ────────────────────────────────────────── */

    public static function meta_tags(): void {
        if ( ! is_post_type_archive( HW_POST_TYPES ) ) {
            return;
        }

        $obj   = get_queried_object();
        $type  = $obj->name ?? '';
        $title = self::archive_title( $type );
        $url   = get_post_type_archive_link( $type );
        $site  = get_bloginfo( 'name' );
        $desc  = $title . ' dari A sampai Z — informasi kesehatan lengkap di ' . $site;

        printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
        echo '<meta property="og:type" content="website">' . "\n";
        printf( '<meta property="og:title" content="%s | %s">' . "\n", esc_attr( $title ), esc_attr( $site ) );
        printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
        printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
        printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( $site ) );
        echo '<meta property="og:locale" content="id_ID">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
    }

    /* ── Schema ───────────────────────────────────────────── */

    public static function schema(): void {
        if ( ! is_post_type_archive( HW_POST_TYPES ) ) {
            return;
        }

        $obj   = get_queried_object();
        $type  = $obj->name ?? '';
        $title = self::archive_title( $type );
        $url   = get_post_type_archive_link( $type );

        $schemas = [
            [
                '@context'    => 'https://schema.org',
                '@type'       => 'CollectionPage',
                'name'        => $title,
                'url'         => $url,
                'description' => $title . ' dari A sampai Z',
                'isPartOf'    => [
                    '@type' => 'WebSite',
                    'name'  => get_bloginfo( 'name' ),
                    'url'   => home_url( '/' ),
                ],
            ],
            [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => [
                    [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => home_url( '/' ) ],
                    [ '@type' => 'ListItem', 'position' => 2, 'name' => $title ],
                ],
            ],
        ];

        foreach ( $schemas as $s ) {
            echo '<script type="application/ld+json">';
            echo wp_json_encode( $s, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
            echo '</script>' . "\n";
        }
    }

    /* ── Helpers ──────────────────────────────────────────── */

    private static function archive_title( string $post_type ): string {
        return match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'Daftar Penyakit',
            HW_CPT_OBAT       => 'Daftar Obat',
            HW_CPT_ORGAN      => 'Daftar Organ Tubuh',
            HW_CPT_GIZI       => 'Daftar Kandungan Gizi Makanan',
            HW_CPT_PENGOBATAN => 'Daftar Pengobatan',
            default           => 'Daftar',
        };
    }

    private static function search_placeholder( string $post_type ): string {
        return match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'Pencarian Penyakit',
            HW_CPT_OBAT       => 'Pencarian Obat',
            HW_CPT_ORGAN      => 'Pencarian Organ Tubuh',
            HW_CPT_GIZI       => 'Pencarian Kandungan Gizi',
            HW_CPT_PENGOBATAN => 'Pencarian Pengobatan',
            default           => 'Pencarian',
        };
    }
}
