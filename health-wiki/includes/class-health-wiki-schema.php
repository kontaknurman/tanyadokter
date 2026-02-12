<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Schema {

    public static function init(): void {
        add_action( 'wp_head', [ __CLASS__, 'output' ], 1 );
    }

    public static function output(): void {
        if ( ! is_singular( HW_POST_TYPES ) ) {
            return;
        }

        $post_id   = get_the_ID();
        $post_type = get_post_type( $post_id );

        if ( ! $post_id || ! $post_type ) {
            return;
        }

        $schemas = [];

        // WebPage
        $schemas[] = self::webpage( $post_id, $post_type );

        // BreadcrumbList
        $schemas[] = self::breadcrumb_schema( $post_type );

        // Type-specific schema
        $specific = match ( $post_type ) {
            HW_CPT_PENYAKIT   => self::medical_condition( $post_id ),
            HW_CPT_OBAT       => self::drug( $post_id ),
            HW_CPT_ORGAN      => self::anatomical_structure( $post_id ),
            HW_CPT_GIZI       => self::nutrition_article( $post_id ),
            HW_CPT_PENGOBATAN => self::medical_therapy( $post_id ),
            default           => null,
        };

        if ( $specific ) {
            $schemas[] = $specific;
        }

        foreach ( $schemas as $schema ) {
            echo '<script type="application/ld+json">';
            echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
            echo '</script>' . "\n";
        }
    }

    /* ── WebPage ──────────────────────────────────────────── */

    private static function webpage( int $id, string $post_type ): array {
        $page_type = match ( $post_type ) {
            HW_CPT_PENYAKIT, HW_CPT_OBAT, HW_CPT_PENGOBATAN => 'MedicalWebPage',
            default => 'WebPage',
        };

        return [
            '@context'      => 'https://schema.org',
            '@type'         => $page_type,
            'name'          => get_the_title( $id ),
            'url'           => get_permalink( $id ),
            'datePublished' => get_the_date( 'c', $id ),
            'dateModified'  => get_the_modified_date( 'c', $id ),
            'publisher'     => [
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'url'   => home_url( '/' ),
            ],
        ];
    }

    /* ── BreadcrumbList ───────────────────────────────────── */

    private static function breadcrumb_schema( string $post_type ): array {
        $labels = [
            HW_CPT_PENYAKIT   => [ 'Penyakit', 'penyakit' ],
            HW_CPT_OBAT       => [ 'Obat', 'obat' ],
            HW_CPT_ORGAN      => [ 'Organ Tubuh', 'organ' ],
            HW_CPT_GIZI       => [ 'Kandungan Gizi', 'gizi-makanan' ],
            HW_CPT_PENGOBATAN => [ 'Pengobatan', 'pengobatan' ],
        ];

        [ $label, $slug ] = $labels[ $post_type ] ?? [ '', '' ];

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'name'     => 'Beranda',
                    'item'     => home_url( '/' ),
                ],
                [
                    '@type'    => 'ListItem',
                    'position' => 2,
                    'name'     => $label,
                    'item'     => home_url( '/' . $slug . '/' ),
                ],
                [
                    '@type'    => 'ListItem',
                    'position' => 3,
                    'name'     => get_the_title(),
                ],
            ],
        ];
    }

    /* ── MedicalCondition (Penyakit) ──────────────────────── */

    private static function medical_condition( int $id ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'MedicalCondition',
            'name'        => get_the_title( $id ),
            'url'         => get_permalink( $id ),
            'description' => (string) get_field( 'hw_penyakit_ringkasan', $id ),
        ];

        $alias = (string) get_field( 'hw_penyakit_alias', $id );
        if ( $alias ) {
            $schema['alternateName'] = $alias;
        }

        $gejala = get_field( 'hw_penyakit_gejala', $id );
        if ( $gejala && is_array( $gejala ) ) {
            $schema['signOrSymptom'] = array_map(
                fn( $r ) => [ '@type' => 'MedicalSignOrSymptom', 'name' => $r['gejala'] ?? '' ],
                $gejala
            );
        }

        $penyebab = get_field( 'hw_penyakit_penyebab', $id );
        if ( $penyebab && is_array( $penyebab ) ) {
            $schema['cause'] = array_map(
                fn( $r ) => [ '@type' => 'MedicalCause', 'name' => $r['penyebab'] ?? '' ],
                $penyebab
            );
        }

        $risiko = get_field( 'hw_penyakit_faktor_risiko', $id );
        if ( $risiko && is_array( $risiko ) ) {
            $schema['riskFactor'] = array_map(
                fn( $r ) => [ '@type' => 'MedicalRiskFactor', 'name' => $r['faktor'] ?? '' ],
                $risiko
            );
        }

        return $schema;
    }

    /* ── Drug (Obat) ──────────────────────────────────────── */

    private static function drug( int $id ): array {
        $schema = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Drug',
            'name'          => get_the_title( $id ),
            'url'           => get_permalink( $id ),
            'description'   => (string) get_field( 'hw_obat_ringkasan', $id ),
        ];

        $generik = (string) get_field( 'hw_obat_nama_generik', $id );
        if ( $generik ) {
            $schema['nonProprietaryName'] = $generik;
        }

        $golongan = (string) get_field( 'hw_obat_golongan', $id );
        if ( $golongan ) {
            $schema['drugClass'] = [ '@type' => 'DrugClass', 'name' => $golongan ];
        }

        $kategori = (string) get_field( 'hw_obat_kategori', $id );
        if ( $kategori ) {
            $schema['prescriptionStatus'] = $kategori === 'resep' ? 'PrescriptionOnly' : 'OTC';
        }

        $peringatan = (string) get_field( 'hw_obat_peringatan', $id );
        if ( $peringatan ) {
            $schema['warning'] = wp_strip_all_tags( $peringatan );
        }

        return $schema;
    }

    /* ── AnatomicalStructure (Organ) ──────────────────────── */

    private static function anatomical_structure( int $id ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'AnatomicalStructure',
            'name'        => get_the_title( $id ),
            'url'         => get_permalink( $id ),
            'description' => (string) get_field( 'hw_organ_ringkasan', $id ),
        ];

        $latin = (string) get_field( 'hw_organ_nama_latin', $id );
        if ( $latin ) {
            $schema['alternateName'] = $latin;
        }

        $sistem = (string) get_field( 'hw_organ_sistem', $id );
        if ( $sistem ) {
            $schema['partOfSystem'] = [ '@type' => 'AnatomicalSystem', 'name' => $sistem ];
        }

        $fungsi = get_field( 'hw_organ_fungsi', $id );
        if ( $fungsi && is_array( $fungsi ) ) {
            $schema['bodyFunction'] = implode( ', ', array_column( $fungsi, 'fungsi' ) );
        }

        $gangguan = get_field( 'hw_organ_gangguan', $id );
        if ( $gangguan && is_array( $gangguan ) ) {
            $schema['relatedCondition'] = array_map(
                fn( $r ) => [ '@type' => 'MedicalCondition', 'name' => $r['gangguan'] ?? '' ],
                $gangguan
            );
        }

        return $schema;
    }

    /* ── Article + NutritionInformation (Gizi) ────────────── */

    private static function nutrition_article( int $id ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Article',
            'name'        => get_the_title( $id ),
            'url'         => get_permalink( $id ),
            'description' => (string) get_field( 'hw_gizi_ringkasan', $id ),
            'about'       => [ '@type' => 'NutritionInformation' ],
        ];

        $kalori = get_field( 'hw_gizi_kalori', $id );
        if ( $kalori ) {
            $schema['about']['calories'] = $kalori . ' kkal';
        }

        return $schema;
    }

    /* ── MedicalTherapy (Pengobatan) ──────────────────────── */

    private static function medical_therapy( int $id ): array {
        $jenis = (string) get_field( 'hw_pengobatan_jenis', $id );

        $type = match ( $jenis ) {
            'operasi' => 'SurgicalProcedure',
            'terapi', 'rehabilitasi' => 'PhysicalTherapy',
            default => 'MedicalTherapy',
        };

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => $type,
            'name'        => get_the_title( $id ),
            'url'         => get_permalink( $id ),
            'description' => (string) get_field( 'hw_pengobatan_ringkasan', $id ),
        ];

        $indikasi = get_field( 'hw_pengobatan_indikasi', $id );
        if ( $indikasi && is_array( $indikasi ) ) {
            $schema['indication'] = array_map(
                fn( $r ) => [ '@type' => 'MedicalIndication', 'name' => $r['indikasi'] ?? '' ],
                $indikasi
            );
        }

        $risiko = get_field( 'hw_pengobatan_risiko', $id );
        if ( $risiko && is_array( $risiko ) ) {
            $schema['risks'] = implode( ', ', array_column( $risiko, 'risiko' ) );
        }

        return $schema;
    }
}
