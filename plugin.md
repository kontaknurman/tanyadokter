# Health Wiki — Plugin WordPress

Wiki Kesehatan Indonesia. Referensi: Alodokter, Halodoc.

## Custom Post Types

| CPT | Slug | Deskripsi |
|-----|------|-----------|
| `hw_penyakit` | `/penyakit/` | Database penyakit: gejala, penyebab, diagnosis, pengobatan, pencegahan |
| `hw_obat` | `/obat/` | Database obat: manfaat, dosis, efek samping, interaksi, kontraindikasi |
| `hw_organ` | `/organ/` | Organ tubuh: fungsi, struktur, gangguan, pemeliharaan |
| `hw_gizi` | `/gizi-makanan/` | Kandungan gizi makanan: tabel nutrisi, manfaat, sumber |
| `hw_pengobatan` | `/pengobatan/` | Metode pengobatan: prosedur, risiko, persiapan, pemulihan |

Setiap CPT memiliki taxonomy kategori masing-masing.

## Relasi Antar CPT

Semua post type saling terhubung via ACF Relationship fields:

| CPT | Terhubung ke |
|-----|-------------|
| Penyakit | Obat Terkait, Organ Terkait, Pengobatan Terkait, Nutrisi Terkait |
| Obat | Penyakit yang Ditangani, Pengobatan Terkait |
| Organ | Penyakit pada Organ, Nutrisi untuk Organ |
| Gizi | Mencegah Penyakit, Baik untuk Organ |
| Pengobatan | Penyakit yang Ditangani, Obat yang Digunakan |

Relasi bersifat bi-directional secara manual (pilih di kedua sisi). Field prefix: `hw_{cpt}_rel_{target}`. Template menampilkan "Artikel Terkait" dengan link antar CPT. Schema menambahkan `relatedLink` untuk SEO internal linking.

## Dependensi

- **ACF Pro** — Semua field data terdaftar via `acf_add_local_field_group()` di `class-health-wiki-acf.php`
- **PHP 8.0+** — Menggunakan match expression, named arguments, typed properties
- **WordPress 6.0+**

## Arsitektur

```
health-wiki/
├── health-wiki.php              # Entry point, constants, loader
├── includes/
│   ├── class-health-wiki-cpt.php       # Register 5 CPT + 5 taxonomy
│   ├── class-health-wiki-acf.php       # ACF field groups (programmatic)
│   ├── class-health-wiki-template.php  # Render konten via the_content filter
│   ├── class-health-wiki-schema.php    # JSON-LD structured data
│   ├── class-health-wiki-seo.php       # Meta tags, OG, Twitter, canonical
│   ├── class-health-wiki-performance.php # Inline CSS, lazy load, cleanup
│   ├── class-health-wiki-archive.php    # Archive A-Z listing, SEO, schema
│   ├── class-health-wiki-autolink.php   # Auto internal linking
│   └── class-health-wiki-cards.php      # Post type cards widget + shortcode
├── templates/
│   └── archive-health-wiki.php          # Archive template (A-Z)
└── assets/css/health-wiki.css           # Styling (di-inline saat render)
```

## Halaman Archive A-Z

Setiap CPT memiliki halaman archive dengan:
- Judul: "Daftar Penyakit", "Daftar Obat", dll
- Search box (redirect ke WordPress search dengan `post_type` filter)
- Navigasi A-Z (letter links, huruf tanpa konten di-disable, tombol "Semua" untuk reset)
- Daftar post dikelompokkan per huruf, diurutkan A-Z
- Total counter

URL: `/penyakit/`, `/obat/`, `/organ/`, `/gizi-makanan/`, `/pengobatan/`
Filter huruf: `/penyakit/?huruf=A`

Template menggunakan `get_header()` / `get_footer()` dari tema aktif.
Schema: `CollectionPage` + `BreadcrumbList`.

File:
- `includes/class-health-wiki-archive.php` — logic, SEO, schema
- `templates/archive-health-wiki.php` — HTML template

## Auto Internal Linking

Otomatis mengubah keyword di konten menjadi internal link jika cocok dengan judul post dari semua HW post types + regular post.

Aturan SEO:
- Hanya link occurrence pertama per keyword
- Max 10 auto-link per halaman
- Tidak link ke diri sendiri (current post)
- Skip di dalam tag: `<a>`, `<h1>`-`<h6>`, `<script>`, `<style>`, `<code>`, `<pre>`
- Keyword min 3 karakter, longest match first
- Internal link tanpa `nofollow`
- Cache 1 jam via transient, auto-clear saat post disimpan

File: `includes/class-health-wiki-autolink.php`

## Post Type Cards

Widget visual menampilkan semua CPT dalam grid card (thumbnail/icon, label, jumlah post).

Panggil di PHP:
```php
echo hw_post_type_cards();
echo hw_post_type_cards( [ 'columns' => 3, 'show_post' => true ] );
```

Shortcode:
```
[hw_post_type_cards]
[hw_post_type_cards columns="3" show_post="1"]
```

Parameter:
- `columns` (2-6): jumlah kolom grid, default 5
- `show_post` (bool): tampilkan juga post type 'post'
- `post_types` (array, PHP only): override CPT list

File: `includes/class-health-wiki-cards.php`

## Template (Single)

Plugin menggunakan filter `the_content` (prioritas 20) sehingga kompatibel dengan **single.php tema apapun**. Tidak perlu template khusus.

Konten yang di-render:
1. Breadcrumb navigasi
2. Overview card (ringkasan + meta data)
3. Konten editor WordPress (deskripsi utama)
4. Table of Contents (otomatis dari section yang terisi)
5. Section-section terstruktur dari ACF fields
6. Artikel Terkait (link antar CPT dari relationship fields)
7. Daftar referensi

## Schema.org

- Penyakit → `MedicalCondition` + `MedicalWebPage`
- Obat → `Drug` + `MedicalWebPage`
- Organ → `AnatomicalStructure` + `WebPage`
- Gizi → `Article` + `NutritionInformation`
- Pengobatan → `MedicalTherapy`/`SurgicalProcedure` + `MedicalWebPage`
- Semua halaman: `BreadcrumbList`

## SEO

- Meta description dari field ringkasan (maks 160 karakter)
- Open Graph + Twitter Card tags
- Document title otomatis: "{Nama} — {Konteks} | {Site Name}"
- Canonical URL
- Robots: index, follow, max-snippet:-1

## Keamanan

- Semua output di-escape: `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`
- Tidak ada direct SQL query
- Tidak ada user input tanpa sanitasi
- Referensi link: `rel="noopener noreferrer nofollow"`

## Performa

- CSS di-inline (zero render-blocking)
- Lazy loading gambar
- Tidak ada JavaScript
- Emoji scripts dihapus di halaman wiki
- Total CSS < 3KB
