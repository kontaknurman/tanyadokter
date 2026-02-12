<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Performance {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );
        add_action( 'wp_head', [ __CLASS__, 'preconnect' ], 0 );
        add_filter( 'wp_lazy_loading_enabled', [ __CLASS__, 'lazy_load' ], 10, 2 );

        // Remove emoji scripts on HW pages for cleaner output.
        if ( self::is_hw_request() ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
        }
    }

    /**
     * Enqueue minimal CSS; inline it for zero render-blocking.
     */
    public static function assets(): void {
        if ( ! is_singular( HW_POST_TYPES ) && ! is_post_type_archive( HW_POST_TYPES ) ) {
            return;
        }

        $css_file = HW_PATH . 'assets/css/health-wiki.css';

        if ( file_exists( $css_file ) ) {
            $css = file_get_contents( $css_file );
            if ( $css ) {
                // Inline CSS — eliminates render-blocking request.
                add_action( 'wp_head', static function () use ( $css ): void {
                    echo '<style id="hw-inline-css">' . $css . '</style>' . "\n";
                }, 5 );
            }
        }
    }

    /**
     * DNS prefetch and preconnect hints.
     */
    public static function preconnect(): void {
        if ( ! is_singular( HW_POST_TYPES ) ) {
            return;
        }
        echo '<link rel="dns-prefetch" href="//schema.org">' . "\n";
    }

    /**
     * Ensure lazy loading for images.
     */
    public static function lazy_load( bool $default, string $tag_name ): bool {
        if ( 'img' === $tag_name && is_singular( HW_POST_TYPES ) ) {
            return true;
        }
        return $default;
    }

    /**
     * Rough check if this might be a HW page (runs before query is set up).
     */
    private static function is_hw_request(): bool {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $slugs = [ '/penyakit/', '/obat/', '/organ/', '/gizi-makanan/', '/pengobatan/' ];
        foreach ( $slugs as $slug ) {
            if ( str_contains( $uri, $slug ) ) {
                return true;
            }
        }
        return false;
    }
}
