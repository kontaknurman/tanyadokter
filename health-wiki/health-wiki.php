<?php
/**
 * Plugin Name: Health Wiki
 * Plugin URI:  https://github.com/kontaknurman/tanyadokter
 * Description: Wiki Kesehatan Indonesia — Penyakit, Obat, Organ Tubuh, Kandungan Gizi Makanan, dan Pengobatan.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      TanyaDokter
 * License:     GPL v2 or later
 * Text Domain: health-wiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
    add_action( 'admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'Health Wiki membutuhkan PHP 8.0 atau lebih baru.', 'health-wiki' )
        );
    } );
    return;
}

define( 'HW_VERSION', '1.0.0' );
define( 'HW_PLUGIN_FILE', __FILE__ );
define( 'HW_PATH', plugin_dir_path( __FILE__ ) );
define( 'HW_URL', plugin_dir_url( __FILE__ ) );

define( 'HW_CPT_PENYAKIT', 'hw_penyakit' );
define( 'HW_CPT_OBAT', 'hw_obat' );
define( 'HW_CPT_ORGAN', 'hw_organ' );
define( 'HW_CPT_GIZI', 'hw_gizi' );
define( 'HW_CPT_PENGOBATAN', 'hw_pengobatan' );

define( 'HW_POST_TYPES', [
    HW_CPT_PENYAKIT,
    HW_CPT_OBAT,
    HW_CPT_ORGAN,
    HW_CPT_GIZI,
    HW_CPT_PENGOBATAN,
] );

require_once HW_PATH . 'includes/class-health-wiki-cpt.php';
require_once HW_PATH . 'includes/class-health-wiki-acf.php';
require_once HW_PATH . 'includes/class-health-wiki-template.php';
require_once HW_PATH . 'includes/class-health-wiki-schema.php';
require_once HW_PATH . 'includes/class-health-wiki-seo.php';
require_once HW_PATH . 'includes/class-health-wiki-performance.php';
require_once HW_PATH . 'includes/class-health-wiki-archive.php';

add_action( 'init', [ Health_Wiki_CPT::class, 'register' ], 5 );
add_action( 'acf/init', [ Health_Wiki_ACF::class, 'register' ] );

Health_Wiki_Template::init();
Health_Wiki_Schema::init();
Health_Wiki_SEO::init();
Health_Wiki_Performance::init();
Health_Wiki_Archive::init();

register_activation_hook( __FILE__, static function (): void {
    Health_Wiki_CPT::register();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, static function (): void {
    flush_rewrite_rules();
} );
