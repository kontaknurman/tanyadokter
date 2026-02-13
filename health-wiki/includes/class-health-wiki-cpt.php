<?php
/**
 * Registrasi Custom Post Type dan Taxonomy.
 *
 * 5 CPT: Penyakit, Obat, Organ Tubuh, Kandungan Gizi, Pengobatan.
 * 5 Taxonomy: Kategori per CPT.
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_CPT {

    /**
     * Daftarkan semua CPT dan taxonomy.
     */
    public static function register(): void {
        self::register_post_types();
        self::register_taxonomies();
    }

    /**
     * Daftarkan 5 Custom Post Type.
     */
    private static function register_post_types(): void {
        $types = [
            HW_CPT_PENYAKIT   => [ 'Penyakit', 'penyakit', 'dashicons-heart' ],
            HW_CPT_OBAT       => [ 'Obat', 'obat', 'dashicons-plus-alt' ],
            HW_CPT_ORGAN      => [ 'Organ Tubuh', 'organ', 'dashicons-admin-users' ],
            HW_CPT_GIZI       => [ 'Kandungan Gizi', 'gizi-makanan', 'dashicons-carrot' ],
            HW_CPT_PENGOBATAN => [ 'Pengobatan', 'pengobatan', 'dashicons-clipboard' ],
        ];

        foreach ( $types as $key => [ $label, $slug, $icon ] ) {
            register_post_type( $key, [
                'labels'       => self::labels( $label ),
                'public'       => true,
                'has_archive'  => true,
                'rewrite'      => [ 'slug' => $slug, 'with_front' => false ],
                'supports'     => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
                'menu_icon'    => $icon,
                'show_in_rest' => true,
            ] );
        }
    }

    /**
     * Daftarkan taxonomy kategori untuk setiap CPT.
     */
    private static function register_taxonomies(): void {
        $taxonomies = [
            'hw_kategori_penyakit'   => [ HW_CPT_PENYAKIT, 'Kategori Penyakit', 'kategori-penyakit' ],
            'hw_kategori_obat'       => [ HW_CPT_OBAT, 'Kategori Obat', 'kategori-obat' ],
            'hw_sistem_organ'        => [ HW_CPT_ORGAN, 'Sistem Organ', 'sistem-organ' ],
            'hw_kategori_gizi'       => [ HW_CPT_GIZI, 'Kategori Gizi', 'kategori-gizi' ],
            'hw_jenis_pengobatan'    => [ HW_CPT_PENGOBATAN, 'Jenis Pengobatan', 'jenis-pengobatan' ],
        ];

        foreach ( $taxonomies as $tax => [ $cpt, $label, $slug ] ) {
            register_taxonomy( $tax, $cpt, [
                'labels'            => self::tax_labels( $label ),
                'public'            => true,
                'hierarchical'      => true,
                'rewrite'           => [ 'slug' => $slug, 'with_front' => false ],
                'show_in_rest'      => true,
                'show_admin_column' => true,
            ] );
        }
    }

    /**
     * Label untuk CPT dalam Bahasa Indonesia.
     */
    private static function labels( string $name ): array {
        return [
            'name'               => $name,
            'singular_name'      => $name,
            'add_new'            => 'Tambah Baru',
            'add_new_item'       => 'Tambah ' . $name,
            'edit_item'          => 'Edit ' . $name,
            'new_item'           => $name . ' Baru',
            'view_item'          => 'Lihat ' . $name,
            'search_items'       => 'Cari ' . $name,
            'not_found'          => $name . ' tidak ditemukan',
            'not_found_in_trash' => $name . ' tidak ditemukan di Sampah',
            'all_items'          => 'Semua ' . $name,
            'archives'           => 'Arsip ' . $name,
        ];
    }

    /**
     * Label untuk taxonomy dalam Bahasa Indonesia.
     */
    private static function tax_labels( string $name ): array {
        return [
            'name'          => $name,
            'singular_name' => $name,
            'search_items'  => 'Cari ' . $name,
            'all_items'     => 'Semua ' . $name,
            'edit_item'     => 'Edit ' . $name,
            'update_item'   => 'Perbarui ' . $name,
            'add_new_item'  => 'Tambah ' . $name,
            'new_item_name' => $name . ' Baru',
        ];
    }
}
