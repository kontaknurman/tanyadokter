<?php
/**
 * Kartu Post Type — grid visual menampilkan setiap CPT
 * dengan thumbnail, label, dan jumlah post.
 *
 * Penggunaan di PHP:
 *   echo hw_post_type_cards();
 *   echo hw_post_type_cards( [ 'columns' => 3, 'show_post' => true ] );
 *
 * Penggunaan shortcode:
 *   [hw_post_type_cards]
 *   [hw_post_type_cards columns="3" show_post="1"]
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Cards {

    private const DEFAULTS = [
        'post_types' => [],      /* Kosong = semua HW post types. */
        'columns'    => 5,       /* Kolom grid (2-6). */
        'show_post'  => false,   /* Tampilkan juga post type 'post'. */
    ];

    /** Ikon SVG per CPT. */
    private const CPT_ICONS = [
        HW_CPT_PENYAKIT   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        HW_CPT_OBAT       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 00-3.7-3.7 48.678 48.678 0 00-7.324 0 4.006 4.006 0 00-3.7 3.7c-.017.22-.032.441-.046.662M19.5 12l3-3m-3 3l-3-3m-12 3c0 1.232.046 2.453.138 3.662a4.006 4.006 0 003.7 3.7 48.656 48.656 0 007.324 0 4.006 4.006 0 003.7-3.7c.017-.22.032-.441.046-.662M4.5 12l3 3m-3-3l-3 3"/></svg>',
        HW_CPT_ORGAN      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>',
        HW_CPT_GIZI       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 16.5m18-4.5l-1.5.75a3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0 3.354 3.354 0 00-3 0 3.354 3.354 0 01-3 0L3 12"/></svg>',
        HW_CPT_PENGOBATAN => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/></svg>',
    ];

    /** Ikon SVG untuk post reguler. */
    private const POST_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>';

    public static function init(): void {
        add_shortcode( 'hw_post_type_cards', [ __CLASS__, 'shortcode' ] );
    }

    /**
     * Handler shortcode.
     */
    public static function shortcode( $atts = [] ): string {
        $atts = shortcode_atts( [
            'columns'   => (string) self::DEFAULTS['columns'],
            'show_post' => '0',
        ], $atts, 'hw_post_type_cards' );

        return self::render( [
            'columns'   => (int) $atts['columns'],
            'show_post' => (bool) $atts['show_post'],
        ] );
    }

    /**
     * Render kartu post type.
     *
     * @param array{post_types?: string[], columns?: int, show_post?: bool} $args
     */
    public static function render( array $args = [] ): string {
        $args = array_merge( self::DEFAULTS, $args );

        $types = $args['post_types'] ?: HW_POST_TYPES;

        if ( $args['show_post'] ) {
            $types[] = 'post';
        }

        $cols = max( 2, min( 6, (int) $args['columns'] ) );
        $html = '<div class="hw-cards" style="--hw-cards-cols:' . $cols . '">';

        foreach ( $types as $pt ) {
            $obj = get_post_type_object( $pt );
            if ( ! $obj ) {
                continue;
            }

            $count     = wp_count_posts( $pt );
            $published = (int) ( $count->publish ?? 0 );
            $label     = $obj->labels->name ?? $pt;
            $link      = get_post_type_archive_link( $pt ) ?: '#';
            $thumb     = self::get_thumbnail( $pt );
            $icon      = self::get_icon( $pt );

            $html .= '<a href="' . esc_url( $link ) . '" class="hw-cards__item">';

            if ( $thumb ) {
                $html .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( $label ) . '" class="hw-cards__img" width="80" height="80" loading="lazy">';
            } else {
                $html .= '<span class="hw-cards__icon">' . $icon . '</span>';
            }

            $html .= '<span class="hw-cards__label">' . esc_html( $label ) . '</span>';
            $html .= '<span class="hw-cards__count">' . esc_html( (string) $published ) . '</span>';
            $html .= '</a>';
        }

        $html .= '</div>';
        return $html;
    }

    /* ── Fungsi Pembantu ─────────────────────────────────── */

    /** Ambil gambar unggulan dari post terbaru di CPT ini. */
    private static function get_thumbnail( string $post_type ): string {
        $latest = get_posts( [
            'post_type'      => $post_type,
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ] );

        if ( $latest ) {
            $url = get_the_post_thumbnail_url( $latest[0], 'thumbnail' );
            if ( $url ) {
                return $url;
            }
        }

        return '';
    }

    /** Ambil ikon SVG untuk post type. */
    private static function get_icon( string $post_type ): string {
        return self::CPT_ICONS[ $post_type ] ?? self::POST_ICON;
    }
}

/* ── Fungsi global pembantu ─────────────────────────────── */

if ( ! function_exists( 'hw_post_type_cards' ) ) {
    /**
     * Render kartu post type Health Wiki.
     *
     * Penggunaan:
     *   echo hw_post_type_cards();
     *   echo hw_post_type_cards( [ 'columns' => 3, 'show_post' => true ] );
     *
     * @param array $args Opsional. columns (2-6), show_post (bool), post_types (array).
     */
    function hw_post_type_cards( array $args = [] ): string {
        return Health_Wiki_Cards::render( $args );
    }
}
