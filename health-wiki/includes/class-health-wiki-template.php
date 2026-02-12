<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Health_Wiki_Template {

    public static function init(): void {
        add_filter( 'the_content', [ __CLASS__, 'render' ], 20 );
    }

    /**
     * Inject structured Health Wiki content on single CPT pages.
     * Uses the theme's single.php — only the_content is modified.
     */
    public static function render( string $content ): string {
        if ( ! is_singular( HW_POST_TYPES ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }

        $post_id   = get_the_ID();
        $post_type = get_post_type( $post_id );

        if ( ! $post_id || ! $post_type ) {
            return $content;
        }

        $html = '<div class="hw-article" itemscope>';

        $html .= self::breadcrumb( $post_type );
        $html .= self::overview( $post_id, $post_type );

        if ( $content ) {
            $html .= '<div class="hw-body">' . $content . '</div>';
        }

        $sections = match ( $post_type ) {
            HW_CPT_PENYAKIT   => self::sections_penyakit( $post_id ),
            HW_CPT_OBAT       => self::sections_obat( $post_id ),
            HW_CPT_ORGAN      => self::sections_organ( $post_id ),
            HW_CPT_GIZI       => self::sections_gizi( $post_id ),
            HW_CPT_PENGOBATAN => self::sections_pengobatan( $post_id ),
            default           => [],
        };

        if ( $sections ) {
            $html .= self::toc( $sections );
            foreach ( $sections as $s ) {
                $html .= $s['html'];
            }
        }

        $html .= self::related_posts( $post_id, $post_type );
        $html .= self::references( $post_id, $post_type );
        $html .= '</div><!-- .hw-article -->';

        return $html;
    }

    /* ── Breadcrumb ───────────────────────────────────────── */

    private static function breadcrumb( string $post_type ): string {
        $labels = [
            HW_CPT_PENYAKIT   => [ 'Penyakit', 'penyakit' ],
            HW_CPT_OBAT       => [ 'Obat', 'obat' ],
            HW_CPT_ORGAN      => [ 'Organ Tubuh', 'organ' ],
            HW_CPT_GIZI       => [ 'Kandungan Gizi', 'gizi-makanan' ],
            HW_CPT_PENGOBATAN => [ 'Pengobatan', 'pengobatan' ],
        ];

        [ $label, $slug ] = $labels[ $post_type ] ?? [ '', '' ];

        return sprintf(
            '<nav class="hw-breadcrumb" aria-label="Breadcrumb"><ol>' .
            '<li><a href="%s">Beranda</a></li>' .
            '<li><a href="%s">%s</a></li>' .
            '<li aria-current="page">%s</li>' .
            '</ol></nav>',
            esc_url( home_url( '/' ) ),
            esc_url( home_url( '/' . $slug . '/' ) ),
            esc_html( $label ),
            esc_html( get_the_title() )
        );
    }

    /* ── Overview Cards ───────────────────────────────────── */

    private static function overview( int $post_id, string $post_type ): string {
        $rows = match ( $post_type ) {
            HW_CPT_PENYAKIT => array_filter( [
                self::ov_row( 'Nama Lain', (string) get_field( 'hw_penyakit_alias', $post_id ) ),
                self::ov_row( 'Spesialis', (string) get_field( 'hw_penyakit_spesialis', $post_id ) ),
            ] ),
            HW_CPT_OBAT => array_filter( [
                self::ov_row( 'Nama Generik', (string) get_field( 'hw_obat_nama_generik', $post_id ) ),
                self::ov_row( 'Golongan', (string) get_field( 'hw_obat_golongan', $post_id ) ),
                self::ov_row( 'Kategori', self::obat_kategori_label( (string) get_field( 'hw_obat_kategori', $post_id ) ) ),
            ] ),
            HW_CPT_ORGAN => array_filter( [
                self::ov_row( 'Nama Latin', (string) get_field( 'hw_organ_nama_latin', $post_id ) ),
                self::ov_row( 'Sistem Tubuh', (string) get_field( 'hw_organ_sistem', $post_id ) ),
            ] ),
            HW_CPT_GIZI => array_filter( [
                self::ov_row( 'Kategori', self::gizi_kategori_label( (string) get_field( 'hw_gizi_kategori', $post_id ) ) ),
                self::ov_row( 'Kalori', (string) get_field( 'hw_gizi_kalori', $post_id ) ? get_field( 'hw_gizi_kalori', $post_id ) . ' kkal/100g' : '' ),
            ] ),
            HW_CPT_PENGOBATAN => array_filter( [
                self::ov_row( 'Jenis', self::pengobatan_jenis_label( (string) get_field( 'hw_pengobatan_jenis', $post_id ) ) ),
            ] ),
            default => [],
        };

        $ringkasan_field = match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'hw_penyakit_ringkasan',
            HW_CPT_OBAT       => 'hw_obat_ringkasan',
            HW_CPT_ORGAN      => 'hw_organ_ringkasan',
            HW_CPT_GIZI       => 'hw_gizi_ringkasan',
            HW_CPT_PENGOBATAN => 'hw_pengobatan_ringkasan',
            default           => '',
        };

        $ringkasan = $ringkasan_field ? (string) get_field( $ringkasan_field, $post_id ) : '';

        if ( ! $rows && ! $ringkasan ) {
            return '';
        }

        $html = '<aside class="hw-overview"><h2>Ringkasan</h2>';

        if ( $ringkasan ) {
            $html .= '<p class="hw-overview__desc">' . esc_html( $ringkasan ) . '</p>';
        }

        if ( $rows ) {
            $html .= '<dl class="hw-overview__meta">';
            foreach ( $rows as [ $dt, $dd ] ) {
                $html .= '<dt>' . esc_html( $dt ) . '</dt><dd>' . esc_html( $dd ) . '</dd>';
            }
            $html .= '</dl>';
        }

        $html .= '</aside>';
        return $html;
    }

    private static function ov_row( string $label, string $value ): ?array {
        return $value ? [ $label, $value ] : null;
    }

    /* ── Sections per CPT ─────────────────────────────────── */

    private static function sections_penyakit( int $id ): array {
        return array_filter( [
            self::repeater_section( $id, 'hw_penyakit_gejala', 'Gejala', 'gejala', 'gejala' ),
            self::repeater_section( $id, 'hw_penyakit_penyebab', 'Penyebab', 'penyebab', 'penyebab' ),
            self::repeater_section( $id, 'hw_penyakit_faktor_risiko', 'Faktor Risiko', 'faktor-risiko', 'faktor' ),
            self::wysiwyg_section( $id, 'hw_penyakit_diagnosis', 'Diagnosis', 'diagnosis' ),
            self::wysiwyg_section( $id, 'hw_penyakit_pengobatan', 'Pengobatan', 'pengobatan' ),
            self::wysiwyg_section( $id, 'hw_penyakit_pencegahan', 'Pencegahan', 'pencegahan' ),
            self::repeater_section( $id, 'hw_penyakit_komplikasi', 'Komplikasi', 'komplikasi', 'komplikasi' ),
            self::wysiwyg_section( $id, 'hw_penyakit_kapan_ke_dokter', 'Kapan Harus ke Dokter', 'kapan-ke-dokter' ),
        ] );
    }

    private static function sections_obat( int $id ): array {
        return array_filter( [
            self::wysiwyg_section( $id, 'hw_obat_manfaat', 'Manfaat', 'manfaat' ),
            self::wysiwyg_section( $id, 'hw_obat_dosis', 'Dosis & Aturan Pakai', 'dosis' ),
            self::repeater_section( $id, 'hw_obat_efek_samping', 'Efek Samping', 'efek-samping', 'efek' ),
            self::repeater_section( $id, 'hw_obat_interaksi', 'Interaksi Obat', 'interaksi', 'interaksi' ),
            self::wysiwyg_section( $id, 'hw_obat_kontraindikasi', 'Kontraindikasi', 'kontraindikasi' ),
            self::wysiwyg_section( $id, 'hw_obat_peringatan', 'Peringatan & Perhatian', 'peringatan' ),
            self::text_section( $id, 'hw_obat_penyimpanan', 'Cara Penyimpanan', 'penyimpanan' ),
        ] );
    }

    private static function sections_organ( int $id ): array {
        return array_filter( [
            self::repeater_section( $id, 'hw_organ_fungsi', 'Fungsi', 'fungsi', 'fungsi' ),
            self::wysiwyg_section( $id, 'hw_organ_struktur', 'Struktur & Anatomi', 'struktur' ),
            self::repeater_section( $id, 'hw_organ_gangguan', 'Gangguan / Penyakit Terkait', 'gangguan', 'gangguan' ),
            self::wysiwyg_section( $id, 'hw_organ_pemeliharaan', 'Tips Menjaga Kesehatan', 'pemeliharaan' ),
        ] );
    }

    private static function sections_gizi( int $id ): array {
        $sections = [];

        // Nutrition table
        $kandungan = get_field( 'hw_gizi_kandungan', $id );
        if ( $kandungan && is_array( $kandungan ) ) {
            $table = '<section class="hw-section" id="kandungan-gizi">';
            $table .= '<h2>Kandungan Gizi</h2>';
            $table .= '<div class="hw-table-wrap"><table class="hw-nutrition-table">';
            $table .= '<thead><tr><th>Nutrisi</th><th>Jumlah</th><th>Satuan</th><th>% AKG</th></tr></thead><tbody>';
            foreach ( $kandungan as $row ) {
                $table .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                    esc_html( $row['nutrisi'] ?? '' ),
                    esc_html( $row['jumlah'] ?? '' ),
                    esc_html( $row['satuan'] ?? '' ),
                    $row['akb'] ? esc_html( $row['akb'] ) . '%' : '-'
                );
            }
            $table .= '</tbody></table></div></section>';
            $sections[] = [ 'title' => 'Kandungan Gizi', 'id' => 'kandungan-gizi', 'html' => $table ];
        }

        return array_merge( $sections, array_filter( [
            self::repeater_section( $id, 'hw_gizi_manfaat', 'Manfaat', 'manfaat', 'manfaat' ),
            self::repeater_section( $id, 'hw_gizi_sumber', 'Sumber Makanan', 'sumber-makanan', 'sumber' ),
            self::wysiwyg_section( $id, 'hw_gizi_kelebihan', 'Efek Kelebihan Konsumsi', 'efek-kelebihan' ),
            self::wysiwyg_section( $id, 'hw_gizi_kekurangan', 'Efek Kekurangan', 'efek-kekurangan' ),
        ] ) );
    }

    private static function sections_pengobatan( int $id ): array {
        return array_filter( [
            self::repeater_section( $id, 'hw_pengobatan_indikasi', 'Indikasi', 'indikasi', 'indikasi' ),
            self::wysiwyg_section( $id, 'hw_pengobatan_prosedur', 'Prosedur', 'prosedur' ),
            self::repeater_section( $id, 'hw_pengobatan_risiko', 'Risiko & Efek Samping', 'risiko', 'risiko' ),
            self::wysiwyg_section( $id, 'hw_pengobatan_persiapan', 'Persiapan', 'persiapan' ),
            self::wysiwyg_section( $id, 'hw_pengobatan_pemulihan', 'Pemulihan', 'pemulihan' ),
        ] );
    }

    /* ── Section Builders ─────────────────────────────────── */

    private static function repeater_section( int $id, string $field, string $title, string $slug, string $sub ): ?array {
        $rows = get_field( $field, $id );
        if ( ! $rows || ! is_array( $rows ) ) {
            return null;
        }

        $html = '<section class="hw-section" id="' . esc_attr( $slug ) . '">';
        $html .= '<h2>' . esc_html( $title ) . '</h2><ul>';
        foreach ( $rows as $row ) {
            $val = $row[ $sub ] ?? '';
            if ( $val ) {
                $html .= '<li>' . esc_html( $val ) . '</li>';
            }
        }
        $html .= '</ul></section>';

        return [ 'title' => $title, 'id' => $slug, 'html' => $html ];
    }

    private static function wysiwyg_section( int $id, string $field, string $title, string $slug ): ?array {
        $value = get_field( $field, $id );
        if ( ! $value ) {
            return null;
        }

        $html = '<section class="hw-section" id="' . esc_attr( $slug ) . '">';
        $html .= '<h2>' . esc_html( $title ) . '</h2>';
        $html .= '<div class="hw-section__content">' . wp_kses_post( $value ) . '</div>';
        $html .= '</section>';

        return [ 'title' => $title, 'id' => $slug, 'html' => $html ];
    }

    private static function text_section( int $id, string $field, string $title, string $slug ): ?array {
        $value = (string) get_field( $field, $id );
        if ( ! $value ) {
            return null;
        }

        $html = '<section class="hw-section" id="' . esc_attr( $slug ) . '">';
        $html .= '<h2>' . esc_html( $title ) . '</h2>';
        $html .= '<p>' . esc_html( $value ) . '</p>';
        $html .= '</section>';

        return [ 'title' => $title, 'id' => $slug, 'html' => $html ];
    }

    /* ── Table of Contents ────────────────────────────────── */

    private static function toc( array $sections ): string {
        if ( count( $sections ) < 2 ) {
            return '';
        }

        $html = '<nav class="hw-toc" aria-label="Daftar Isi"><h2>Daftar Isi</h2><ol>';
        foreach ( $sections as $s ) {
            $html .= '<li><a href="#' . esc_attr( $s['id'] ) . '">' . esc_html( $s['title'] ) . '</a></li>';
        }
        $html .= '</ol></nav>';
        return $html;
    }

    /* ── References ───────────────────────────────────────── */

    private static function references( int $id, string $post_type ): string {
        $field = match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'hw_penyakit_referensi',
            HW_CPT_OBAT       => 'hw_obat_referensi',
            HW_CPT_ORGAN      => 'hw_organ_referensi',
            HW_CPT_GIZI       => 'hw_gizi_referensi',
            HW_CPT_PENGOBATAN => 'hw_pengobatan_referensi',
            default           => '',
        };

        if ( ! $field ) {
            return '';
        }

        $refs = get_field( $field, $id );
        if ( ! $refs || ! is_array( $refs ) ) {
            return '';
        }

        $html = '<section class="hw-references" id="referensi"><h2>Referensi</h2><ol>';
        foreach ( $refs as $ref ) {
            $judul = $ref['judul'] ?? '';
            $url   = $ref['url'] ?? '';
            if ( $judul && $url ) {
                $html .= '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer nofollow">' . esc_html( $judul ) . '</a></li>';
            } elseif ( $judul ) {
                $html .= '<li>' . esc_html( $judul ) . '</li>';
            }
        }
        $html .= '</ol></section>';
        return $html;
    }

    /* ── Related Posts ────────────────────────────────────── */

    private static function related_posts( int $id, string $post_type ): string {
        $fields = match ( $post_type ) {
            HW_CPT_PENYAKIT   => [
                'hw_penyakit_rel_obat'       => 'Obat Terkait',
                'hw_penyakit_rel_organ'      => 'Organ Terkait',
                'hw_penyakit_rel_pengobatan' => 'Pengobatan Terkait',
                'hw_penyakit_rel_gizi'       => 'Nutrisi Terkait',
            ],
            HW_CPT_OBAT       => [
                'hw_obat_rel_penyakit'   => 'Penyakit yang Ditangani',
                'hw_obat_rel_pengobatan' => 'Pengobatan Terkait',
            ],
            HW_CPT_ORGAN      => [
                'hw_organ_rel_penyakit' => 'Penyakit pada Organ Ini',
                'hw_organ_rel_gizi'     => 'Nutrisi untuk Organ',
            ],
            HW_CPT_GIZI       => [
                'hw_gizi_rel_penyakit' => 'Mencegah Penyakit',
                'hw_gizi_rel_organ'    => 'Baik untuk Organ',
            ],
            HW_CPT_PENGOBATAN => [
                'hw_pengobatan_rel_penyakit' => 'Penyakit yang Ditangani',
                'hw_pengobatan_rel_obat'     => 'Obat yang Digunakan',
            ],
            default => [],
        };

        $groups = [];
        foreach ( $fields as $field => $label ) {
            $posts = get_field( $field, $id );
            if ( ! $posts || ! is_array( $posts ) ) {
                continue;
            }
            $items = '';
            foreach ( $posts as $p ) {
                $cpt_label = self::cpt_label( (string) get_post_type( $p ) );
                $items .= sprintf(
                    '<li><a href="%s">%s</a><span class="hw-related__badge">%s</span></li>',
                    esc_url( get_permalink( $p ) ),
                    esc_html( get_the_title( $p ) ),
                    esc_html( $cpt_label )
                );
            }
            if ( $items ) {
                $groups[] = '<div class="hw-related__group"><h3>' . esc_html( $label ) . '</h3><ul>' . $items . '</ul></div>';
            }
        }

        if ( ! $groups ) {
            return '';
        }

        return '<section class="hw-section hw-related" id="artikel-terkait"><h2>Artikel Terkait</h2>'
            . implode( '', $groups )
            . '</section>';
    }

    private static function cpt_label( string $post_type ): string {
        return match ( $post_type ) {
            HW_CPT_PENYAKIT   => 'Penyakit',
            HW_CPT_OBAT       => 'Obat',
            HW_CPT_ORGAN      => 'Organ',
            HW_CPT_GIZI       => 'Gizi',
            HW_CPT_PENGOBATAN => 'Pengobatan',
            default           => '',
        };
    }

    /* ── Label Helpers ────────────────────────────────────── */

    private static function obat_kategori_label( string $key ): string {
        return match ( $key ) {
            'resep'          => 'Obat Resep',
            'bebas'          => 'Obat Bebas',
            'bebas_terbatas' => 'Obat Bebas Terbatas',
            default          => '',
        };
    }

    private static function gizi_kategori_label( string $key ): string {
        return match ( $key ) {
            'buah'        => 'Buah-buahan',
            'sayur'       => 'Sayuran',
            'protein'     => 'Sumber Protein',
            'karbohidrat' => 'Karbohidrat',
            'lemak'       => 'Sumber Lemak',
            'minuman'     => 'Minuman',
            'lainnya'     => 'Lainnya',
            default       => '',
        };
    }

    private static function pengobatan_jenis_label( string $key ): string {
        return match ( $key ) {
            'medis'        => 'Medis',
            'alami'        => 'Alami / Herbal',
            'terapi'       => 'Terapi',
            'operasi'      => 'Operasi / Bedah',
            'rehabilitasi' => 'Rehabilitasi',
            default        => '',
        };
    }
}
