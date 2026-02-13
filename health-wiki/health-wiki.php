<?php
/**
 * Plugin Name: Health Wiki
 * Plugin URI:  https://github.com/kontaknurman/tanyadokter
 * Description: Wiki Kesehatan Indonesia — Penyakit, Obat, Organ Tubuh, Kandungan Gizi Makanan, dan Pengobatan.
 * Version:     1.0.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author:      TanyaDokter
 * Author URI:  https://github.com/kontaknurman
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: health-wiki
 * Domain Path: /languages
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* Cek versi PHP minimum */
if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
    add_action( 'admin_notices', static function (): void {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__( 'Health Wiki membutuhkan PHP 8.0 atau lebih baru.', 'health-wiki' )
        );
    } );
    return;
}

/* Cek ACF Pro aktif */
add_action( 'admin_init', static function (): void {
    if ( ! class_exists( 'ACF' ) ) {
        add_action( 'admin_notices', static function (): void {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                esc_html__( 'Health Wiki membutuhkan plugin Advanced Custom Fields PRO untuk berfungsi optimal.', 'health-wiki' )
            );
        } );
    }
} );

/* Konstanta plugin */
define( 'HW_VERSION', '1.0.0' );
define( 'HW_PLUGIN_FILE', __FILE__ );
define( 'HW_PATH', plugin_dir_path( __FILE__ ) );
define( 'HW_URL', plugin_dir_url( __FILE__ ) );

/* Konstanta Custom Post Type */
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

/* Muat semua class */
require_once HW_PATH . 'includes/class-health-wiki-cpt.php';
require_once HW_PATH . 'includes/class-health-wiki-acf.php';
require_once HW_PATH . 'includes/class-health-wiki-template.php';
require_once HW_PATH . 'includes/class-health-wiki-schema.php';
require_once HW_PATH . 'includes/class-health-wiki-seo.php';
require_once HW_PATH . 'includes/class-health-wiki-performance.php';
require_once HW_PATH . 'includes/class-health-wiki-archive.php';
require_once HW_PATH . 'includes/class-health-wiki-autolink.php';
require_once HW_PATH . 'includes/class-health-wiki-cards.php';

/* Daftarkan CPT dan taxonomy */
add_action( 'init', [ Health_Wiki_CPT::class, 'register' ], 5 );

/* Daftarkan field ACF Pro */
add_action( 'acf/init', [ Health_Wiki_ACF::class, 'register' ] );

/* Inisialisasi semua modul */
Health_Wiki_Template::init();
Health_Wiki_Schema::init();
Health_Wiki_SEO::init();
Health_Wiki_Performance::init();
Health_Wiki_Archive::init();
Health_Wiki_Autolink::init();
Health_Wiki_Cards::init();

/* Flush rewrite rules saat aktivasi */
register_activation_hook( __FILE__, static function (): void {
    Health_Wiki_CPT::register();
    flush_rewrite_rules();
} );

/* Flush rewrite rules saat deaktivasi */
register_deactivation_hook( __FILE__, static function (): void {
    flush_rewrite_rules();
} );
