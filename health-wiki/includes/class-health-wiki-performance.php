<?php
/**
 * Optimasi performa — CSS inline, lazy loading, dan pembersihan.
 *
 * Strategi:
 * - CSS di-inline langsung (tanpa render-blocking request)
 * - DNS prefetch untuk sumber daya eksternal
 * - Lazy loading dipaksa untuk semua gambar
 * - Emoji scripts dihapus di halaman wiki
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Performance {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
        add_action( 'wp_head', [ __CLASS__, 'preconnect' ], 0 );
        add_filter( 'wp_lazy_loading_enabled', [ __CLASS__, 'lazy_load' ], 10, 2 );

        /* Hapus emoji scripts di halaman HW untuk output lebih bersih. */
        if ( self::is_hw_request() ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
        }
    }

    /**
     * Muat CSS minimal; inline langsung untuk zero render-blocking.
     */
    public static function assets(): void {
        if ( ! is_singular( HW_POST_TYPES ) && ! is_post_type_archive( HW_POST_TYPES ) ) {
            return;
        }

        $css_file = HW_PATH . 'assets/css/health-wiki.css';

        if ( file_exists( $css_file ) ) {
            $css = file_get_contents( $css_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- file lokal
            if ( $css ) {
                /* Inline CSS — menghilangkan render-blocking request. */
                add_action( 'wp_head', static function () use ( $css ): void {
                    echo '<style id="hw-inline-css">' . wp_strip_all_tags( $css ) . '</style>' . "\n";
                }, 5 );
            }
        }
    }

    /**
     * DNS prefetch dan preconnect hints.
     */
    public static function preconnect(): void {
        if ( ! is_singular( HW_POST_TYPES ) ) {
            return;
        }
        echo '<link rel="dns-prefetch" href="//schema.org">' . "\n";
    }

    /**
     * Paksa lazy loading untuk gambar di halaman wiki.
     */
    public static function lazy_load( bool $default, string $tag_name ): bool {
        if ( 'img' === $tag_name && is_singular( HW_POST_TYPES ) ) {
            return true;
        }
        return $default;
    }

    /**
     * Deteksi kasar apakah request ini untuk halaman HW (berjalan sebelum query tersedia).
     */
    private static function is_hw_request(): bool {
        $uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
        $slugs = [ '/penyakit/', '/obat/', '/organ/', '/gizi-makanan/', '/pengobatan/' ];
        foreach ( $slugs as $slug ) {
            if ( str_contains( $uri, $slug ) ) {
                return true;
            }
        }
        return false;
    }
}
