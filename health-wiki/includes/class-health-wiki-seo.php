<?php
/**
 * SEO — Meta tags, Open Graph, Twitter Card, dan judul dokumen.
 *
 * Mengelola:
 * - Meta description dari field ringkasan (maks 160 karakter)
 * - Open Graph tags (og:title, og:description, og:image, dll)
 * - Twitter Card (summary_large_image)
 * - Canonical URL
 * - Robots directive
 * - Judul dokumen otomatis
 *
 * @package HealthWiki
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_SEO {

    public static function init(): void {
        add_action( 'wp_head', [ __CLASS__, 'meta_tags' ], 2 );
        add_filter( 'document_title_parts', [ __CLASS__, 'title' ] );
    }

    /**
     * Cetak meta description, Open Graph, dan Twitter Card tags.
     */
    public static function meta_tags(): void {
        if ( ! is_singular( HW_POST_TYPES ) ) {
            return;
        }

        $post_id   = get_the_ID();
        $post_type = get_post_type( $post_id );

        if ( ! $post_id || ! $post_type ) {
            return;
        }

        $title       = get_the_title( $post_id );
        $description = self::get_description( $post_id, $post_type );
        $url         = get_permalink( $post_id );
        $image       = get_the_post_thumbnail_url( $post_id, 'large' );
        $site_name   = get_bloginfo( 'name' );
        $modified    = get_the_modified_date( 'c', $post_id );
        $published   = get_the_date( 'c', $post_id );

        $type_label = match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'Penyakit',
            HW_CPT_OBAT       => 'Obat',
            HW_CPT_ORGAN      => 'Organ Tubuh',
            HW_CPT_GIZI       => 'Kandungan Gizi',
            HW_CPT_PENGOBATAN => 'Pengobatan',
            default           => '',
        };

        $meta_title = $title . ' — ' . $type_label . ' | ' . $site_name;

        /* Meta description */
        if ( $description ) {
            printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
        }

        /* Canonical URL */
        printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );

        /* Open Graph */
        echo '<meta property="og:type" content="article">' . "\n";
        printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $meta_title ) );
        printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
        printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( $site_name ) );
        echo '<meta property="og:locale" content="id_ID">' . "\n";

        if ( $description ) {
            printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
        }
        if ( $image ) {
            printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
        }
        if ( $published ) {
            printf( '<meta property="article:published_time" content="%s">' . "\n", esc_attr( $published ) );
        }
        if ( $modified ) {
            printf( '<meta property="article:modified_time" content="%s">' . "\n", esc_attr( $modified ) );
        }

        /* Twitter Card */
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $meta_title ) );
        if ( $description ) {
            printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
        }
        if ( $image ) {
            printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
        }

        /* Robots */
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
    }

    /**
     * Perkaya judul dokumen untuk halaman Health Wiki.
     * Format: "{Nama} — {Konteks SEO} | {Nama Situs}"
     */
    public static function title( array $parts ): array {
        if ( ! is_singular( HW_POST_TYPES ) ) {
            return $parts;
        }

        $post_type = get_post_type();
        $suffix    = match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'Gejala, Penyebab & Pengobatan',
            HW_CPT_OBAT       => 'Manfaat, Dosis & Efek Samping',
            HW_CPT_ORGAN      => 'Fungsi & Struktur',
            HW_CPT_GIZI       => 'Kandungan Gizi & Manfaat',
            HW_CPT_PENGOBATAN => 'Prosedur & Risiko',
            default           => '',
        };

        if ( $suffix ) {
            $parts['title'] = $parts['title'] . ' — ' . $suffix;
        }

        return $parts;
    }

    /**
     * Ambil deskripsi meta dari field ringkasan ACF atau excerpt.
     * Dibatasi maksimal 160 karakter untuk SEO optimal.
     */
    private static function get_description( int $id, string $post_type ): string {
        $field = match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'hw_penyakit_ringkasan',
            HW_CPT_OBAT       => 'hw_obat_ringkasan',
            HW_CPT_ORGAN      => 'hw_organ_ringkasan',
            HW_CPT_GIZI       => 'hw_gizi_ringkasan',
            HW_CPT_PENGOBATAN => 'hw_pengobatan_ringkasan',
            default           => '',
        };

        $desc = $field ? (string) get_field( $field, $id ) : '';

        if ( ! $desc ) {
            $desc = get_the_excerpt( $id );
        }

        /* Batasi ~160 karakter untuk meta description */
        if ( mb_strlen( $desc ) > 160 ) {
            $desc = mb_substr( $desc, 0, 157 ) . '...';
        }

        return $desc;
    }
}
