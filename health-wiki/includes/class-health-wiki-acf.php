<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_ACF {

    public static function register(): void {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }
        self::register_penyakit();
        self::register_obat();
        self::register_organ();
        self::register_gizi();
        self::register_pengobatan();
    }

    /* ── Penyakit ─────────────────────────────────────────── */
    private static function register_penyakit(): void {
        acf_add_local_field_group( [
            'key'      => 'group_hw_penyakit',
            'title'    => 'Data Penyakit',
            'fields'   => [
                self::text( 'hw_penyakit_alias', 'Nama Lain', 'Contoh: Kencing Manis, DM' ),
                self::text( 'hw_penyakit_spesialis', 'Dokter Spesialis' ),
                self::textarea( 'hw_penyakit_ringkasan', 'Ringkasan Singkat' ),
                self::repeater( 'hw_penyakit_gejala', 'Gejala', [
                    self::text( 'gejala', 'Gejala' ),
                ] ),
                self::repeater( 'hw_penyakit_penyebab', 'Penyebab', [
                    self::text( 'penyebab', 'Penyebab' ),
                ] ),
                self::repeater( 'hw_penyakit_faktor_risiko', 'Faktor Risiko', [
                    self::text( 'faktor', 'Faktor' ),
                ] ),
                self::wysiwyg( 'hw_penyakit_diagnosis', 'Diagnosis' ),
                self::wysiwyg( 'hw_penyakit_pengobatan', 'Pengobatan' ),
                self::wysiwyg( 'hw_penyakit_pencegahan', 'Pencegahan' ),
                self::repeater( 'hw_penyakit_komplikasi', 'Komplikasi', [
                    self::text( 'komplikasi', 'Komplikasi' ),
                ] ),
                self::wysiwyg( 'hw_penyakit_kapan_ke_dokter', 'Kapan Harus ke Dokter' ),
                self::repeater( 'hw_penyakit_referensi', 'Referensi', [
                    self::text( 'judul', 'Judul' ),
                    self::field( 'url', 'url', 'URL' ),
                ] ),
                self::relationship( 'hw_penyakit_rel_obat', 'Obat Terkait', [ HW_CPT_OBAT ] ),
                self::relationship( 'hw_penyakit_rel_organ', 'Organ Terkait', [ HW_CPT_ORGAN ] ),
                self::relationship( 'hw_penyakit_rel_pengobatan', 'Pengobatan Terkait', [ HW_CPT_PENGOBATAN ] ),
                self::relationship( 'hw_penyakit_rel_gizi', 'Nutrisi Terkait', [ HW_CPT_GIZI ] ),
            ],
            'location' => self::location( HW_CPT_PENYAKIT ),
        ] );
    }

    /* ── Obat ─────────────────────────────────────────────── */
    private static function register_obat(): void {
        acf_add_local_field_group( [
            'key'      => 'group_hw_obat',
            'title'    => 'Data Obat',
            'fields'   => [
                self::text( 'hw_obat_nama_generik', 'Nama Generik' ),
                self::text( 'hw_obat_golongan', 'Golongan Obat' ),
                self::select( 'hw_obat_kategori', 'Kategori', [
                    'resep'           => 'Obat Resep',
                    'bebas'           => 'Obat Bebas',
                    'bebas_terbatas'  => 'Obat Bebas Terbatas',
                ] ),
                self::textarea( 'hw_obat_ringkasan', 'Ringkasan' ),
                self::wysiwyg( 'hw_obat_manfaat', 'Manfaat' ),
                self::wysiwyg( 'hw_obat_dosis', 'Dosis & Aturan Pakai' ),
                self::repeater( 'hw_obat_efek_samping', 'Efek Samping', [
                    self::text( 'efek', 'Efek Samping' ),
                ] ),
                self::repeater( 'hw_obat_interaksi', 'Interaksi Obat', [
                    self::text( 'interaksi', 'Interaksi' ),
                ] ),
                self::wysiwyg( 'hw_obat_kontraindikasi', 'Kontraindikasi' ),
                self::wysiwyg( 'hw_obat_peringatan', 'Peringatan & Perhatian' ),
                self::text( 'hw_obat_penyimpanan', 'Cara Penyimpanan' ),
                self::repeater( 'hw_obat_referensi', 'Referensi', [
                    self::text( 'judul', 'Judul' ),
                    self::field( 'url', 'url', 'URL' ),
                ] ),
                self::relationship( 'hw_obat_rel_penyakit', 'Penyakit yang Ditangani', [ HW_CPT_PENYAKIT ] ),
                self::relationship( 'hw_obat_rel_pengobatan', 'Pengobatan Terkait', [ HW_CPT_PENGOBATAN ] ),
            ],
            'location' => self::location( HW_CPT_OBAT ),
        ] );
    }

    /* ── Organ ────────────────────────────────────────────── */
    private static function register_organ(): void {
        acf_add_local_field_group( [
            'key'      => 'group_hw_organ',
            'title'    => 'Data Organ Tubuh',
            'fields'   => [
                self::text( 'hw_organ_nama_latin', 'Nama Latin' ),
                self::text( 'hw_organ_sistem', 'Sistem Tubuh' ),
                self::textarea( 'hw_organ_ringkasan', 'Ringkasan' ),
                self::repeater( 'hw_organ_fungsi', 'Fungsi', [
                    self::text( 'fungsi', 'Fungsi' ),
                ] ),
                self::wysiwyg( 'hw_organ_struktur', 'Struktur & Anatomi' ),
                self::repeater( 'hw_organ_gangguan', 'Gangguan / Penyakit Terkait', [
                    self::text( 'gangguan', 'Gangguan' ),
                ] ),
                self::wysiwyg( 'hw_organ_pemeliharaan', 'Tips Menjaga Kesehatan' ),
                self::repeater( 'hw_organ_referensi', 'Referensi', [
                    self::text( 'judul', 'Judul' ),
                    self::field( 'url', 'url', 'URL' ),
                ] ),
                self::relationship( 'hw_organ_rel_penyakit', 'Penyakit pada Organ Ini', [ HW_CPT_PENYAKIT ] ),
                self::relationship( 'hw_organ_rel_gizi', 'Nutrisi untuk Organ', [ HW_CPT_GIZI ] ),
            ],
            'location' => self::location( HW_CPT_ORGAN ),
        ] );
    }

    /* ── Kandungan Gizi ───────────────────────────────────── */
    private static function register_gizi(): void {
        acf_add_local_field_group( [
            'key'      => 'group_hw_gizi',
            'title'    => 'Data Kandungan Gizi',
            'fields'   => [
                self::select( 'hw_gizi_kategori', 'Kategori Makanan', [
                    'buah'      => 'Buah-buahan',
                    'sayur'     => 'Sayuran',
                    'protein'   => 'Sumber Protein',
                    'karbohidrat' => 'Karbohidrat',
                    'lemak'     => 'Sumber Lemak',
                    'minuman'   => 'Minuman',
                    'lainnya'   => 'Lainnya',
                ] ),
                self::textarea( 'hw_gizi_ringkasan', 'Ringkasan' ),
                self::field( 'hw_gizi_kalori', 'number', 'Kalori per 100g', [ 'append' => 'kkal' ] ),
                self::repeater( 'hw_gizi_kandungan', 'Tabel Kandungan Gizi', [
                    self::text( 'nutrisi', 'Nutrisi' ),
                    self::text( 'jumlah', 'Jumlah' ),
                    self::text( 'satuan', 'Satuan' ),
                    self::field( 'akb', 'number', '% AKG', [ 'append' => '%' ] ),
                ] ),
                self::repeater( 'hw_gizi_manfaat', 'Manfaat', [
                    self::text( 'manfaat', 'Manfaat' ),
                ] ),
                self::repeater( 'hw_gizi_sumber', 'Sumber Makanan', [
                    self::text( 'sumber', 'Sumber' ),
                ] ),
                self::wysiwyg( 'hw_gizi_kelebihan', 'Efek Kelebihan Konsumsi' ),
                self::wysiwyg( 'hw_gizi_kekurangan', 'Efek Kekurangan' ),
                self::repeater( 'hw_gizi_referensi', 'Referensi', [
                    self::text( 'judul', 'Judul' ),
                    self::field( 'url', 'url', 'URL' ),
                ] ),
                self::relationship( 'hw_gizi_rel_penyakit', 'Mencegah Penyakit', [ HW_CPT_PENYAKIT ] ),
                self::relationship( 'hw_gizi_rel_organ', 'Baik untuk Organ', [ HW_CPT_ORGAN ] ),
            ],
            'location' => self::location( HW_CPT_GIZI ),
        ] );
    }

    /* ── Pengobatan ───────────────────────────────────────── */
    private static function register_pengobatan(): void {
        acf_add_local_field_group( [
            'key'      => 'group_hw_pengobatan',
            'title'    => 'Data Pengobatan',
            'fields'   => [
                self::select( 'hw_pengobatan_jenis', 'Jenis Pengobatan', [
                    'medis'       => 'Medis',
                    'alami'       => 'Alami / Herbal',
                    'terapi'      => 'Terapi',
                    'operasi'     => 'Operasi / Bedah',
                    'rehabilitasi' => 'Rehabilitasi',
                ] ),
                self::textarea( 'hw_pengobatan_ringkasan', 'Ringkasan' ),
                self::repeater( 'hw_pengobatan_indikasi', 'Indikasi / Kondisi yang Ditangani', [
                    self::text( 'indikasi', 'Indikasi' ),
                ] ),
                self::wysiwyg( 'hw_pengobatan_prosedur', 'Prosedur' ),
                self::repeater( 'hw_pengobatan_risiko', 'Risiko & Efek Samping', [
                    self::text( 'risiko', 'Risiko' ),
                ] ),
                self::wysiwyg( 'hw_pengobatan_persiapan', 'Persiapan Sebelum Tindakan' ),
                self::wysiwyg( 'hw_pengobatan_pemulihan', 'Pemulihan Setelah Tindakan' ),
                self::repeater( 'hw_pengobatan_referensi', 'Referensi', [
                    self::text( 'judul', 'Judul' ),
                    self::field( 'url', 'url', 'URL' ),
                ] ),
                self::relationship( 'hw_pengobatan_rel_penyakit', 'Penyakit yang Ditangani', [ HW_CPT_PENYAKIT ] ),
                self::relationship( 'hw_pengobatan_rel_obat', 'Obat yang Digunakan', [ HW_CPT_OBAT ] ),
            ],
            'location' => self::location( HW_CPT_PENGOBATAN ),
        ] );
    }

    /* ── Helpers ──────────────────────────────────────────── */

    private static function field( string $name, string $type, string $label, array $extra = [] ): array {
        return array_merge( [
            'key'   => 'field_' . $name,
            'label' => $label,
            'name'  => $name,
            'type'  => $type,
        ], $extra );
    }

    private static function text( string $name, string $label, string $placeholder = '' ): array {
        return [
            'key'         => 'field_' . $name,
            'label'       => $label,
            'name'        => $name,
            'type'        => 'text',
            'placeholder' => $placeholder,
        ];
    }

    private static function textarea( string $name, string $label ): array {
        return [
            'key'   => 'field_' . $name,
            'label' => $label,
            'name'  => $name,
            'type'  => 'textarea',
            'rows'  => 3,
        ];
    }

    private static function wysiwyg( string $name, string $label ): array {
        return [
            'key'       => 'field_' . $name,
            'label'     => $label,
            'name'      => $name,
            'type'      => 'wysiwyg',
            'tabs'      => 'all',
            'toolbar'   => 'full',
            'media_upload' => 1,
        ];
    }

    private static function select( string $name, string $label, array $choices ): array {
        return [
            'key'     => 'field_' . $name,
            'label'   => $label,
            'name'    => $name,
            'type'    => 'select',
            'choices' => $choices,
        ];
    }

    private static function relationship( string $name, string $label, array $post_types ): array {
        return [
            'key'           => 'field_' . $name,
            'label'         => $label,
            'name'          => $name,
            'type'          => 'relationship',
            'post_type'     => $post_types,
            'filters'       => [ 'search', 'post_type' ],
            'return_format' => 'object',
            'min'           => 0,
            'max'           => 10,
        ];
    }

    private static function repeater( string $name, string $label, array $sub_fields ): array {
        return [
            'key'        => 'field_' . $name,
            'label'      => $label,
            'name'       => $name,
            'type'       => 'repeater',
            'layout'     => 'table',
            'min'        => 0,
            'sub_fields' => $sub_fields,
        ];
    }

    private static function location( string $post_type ): array {
        return [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => $post_type,
                ],
            ],
        ];
    }
}
