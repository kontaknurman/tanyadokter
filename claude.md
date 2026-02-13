# Health Wiki — Panduan Pengembangan

## Stack

- WordPress 6.0+, PHP 8.0+, ACF Pro
- Path plugin: `health-wiki/`

## Aturan Kode

- **HTML5 semantik**: Gunakan `<article>`, `<section>`, `<nav>`, `<aside>`, `<figure>`. Hindari div tanpa semantic meaning.
- **Escape semua output**: `esc_html()` untuk teks, `esc_url()` untuk URL, `esc_attr()` untuk atribut, `wp_kses_post()` untuk konten WYSIWYG. Tidak ada exception.
- **Tidak ada inline JS di halaman frontend**. Jika butuh JS, gunakan `wp_enqueue_script` dengan `defer`.
- **CSS < 5KB total**. Di-inline via `<style>` di `wp_head`. Tidak ada external stylesheet di frontend.
- **CSS inline disanitasi** dengan `wp_strip_all_tags()` sebelum output.

## Schema.org (JSON-LD)

- Setiap CPT harus punya JSON-LD yang valid. Validasi di https://validator.schema.org/
- MedicalCondition, Drug, AnatomicalStructure, MedicalTherapy, Article, BreadcrumbList
- Output di `wp_head` dengan `wp_json_encode()` + flag `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`
- Sertakan `inLanguage: "id-ID"` di semua schema

## SEO Checklist

- [ ] Meta description ≤ 160 karakter dari field ringkasan
- [ ] Open Graph: og:title, og:description, og:image, og:url, og:type, og:locale
- [ ] Twitter Card: summary_large_image
- [ ] Canonical URL di setiap halaman
- [ ] Judul dokumen format: "{Nama} — {Konteks} | {Situs}"
- [ ] Robots: index, follow, max-snippet:-1, max-image-preview:large
- [ ] Breadcrumb dengan BreadcrumbList schema
- [ ] Heading hierarchy: satu H1 (dari tema), H2 per section
- [ ] Internal linking antar CPT via relationship fields (wajib diisi untuk setiap konten)
- [ ] URL slug pendek dan deskriptif (sudah diatur di CPT rewrite)

## PageSpeed 100 Checklist

- [ ] Zero render-blocking resources (CSS inline, tanpa external JS)
- [ ] Lazy loading semua gambar (`loading="lazy"`)
- [ ] Tidak ada unused CSS/JS
- [ ] Gambar: gunakan WebP, ukuran optimal, width/height attributes
- [ ] Minimal DOM size — hindari nested div berlebihan
- [ ] Emoji scripts dihapus di halaman wiki
- [ ] Font: gunakan system font stack, tidak ada web font tambahan
- [ ] Preconnect/DNS-prefetch untuk external resources

## Keamanan

- Tidak ada `$_GET`/`$_POST` tanpa sanitasi
- Tidak ada `eval()`, `extract()`, `$wpdb->prepare()` tanpa placeholder
- Semua link eksternal: `rel="noopener noreferrer nofollow"`
- Capability check untuk admin operations
- Tidak ada file upload handler kustom
- CSS inline disanitasi via `wp_strip_all_tags()`

## Auto Internal Linking

- Class: `Health_Wiki_Autolink`, hook `the_content` prioritas 30 (setelah template prioritas 20)
- Berlaku di semua `is_singular()` (termasuk post reguler)
- Cache keyword di transient `hw_autolink_keywords` (1 jam), clear on `save_post` / `delete_post`
- Maks 10 link per halaman, min 3 karakter keyword
- Skip tags: `<a>`, `<h1>`-`<h6>`, `<script>`, `<style>`, `<code>`, `<pre>`, `<button>`
- Keyword terpanjang dicocokkan duluan, case-insensitive, word boundary

## Kartu Post Type

- Class: `Health_Wiki_Cards`, shortcode `[hw_post_type_cards]`
- PHP: `echo hw_post_type_cards( $args )`
- Args: `columns` (2-6), `show_post` (bool), `post_types` (array)
- Thumbnail dari gambar unggulan post terbaru, fallback ke ikon SVG
- CSS grid responsif (5 kolom desktop, 2 kolom mobile)

## Archive A-Z

- Setiap CPT punya halaman archive di URL slug-nya (`/penyakit/`, `/obat/`, dll)
- Template: `templates/archive-health-wiki.php` — gunakan `get_header()` / `get_footer()`
- Data dari `Health_Wiki_Archive::get_data()` — return array dengan grouped posts
- Filter huruf via `?huruf=X` query param (disanitasi)
- Kotak pencarian redirect ke WordPress native search dengan filter `post_type`
- Schema: `CollectionPage` + `BreadcrumbList`
- Tidak ada JS — semua server-side rendered

## Relasi Antar CPT

Field pattern: `hw_{cpt}_rel_{target}` (ACF Relationship, return format: object)

| Source | Target Fields |
|--------|--------------|
| Penyakit | `_rel_obat`, `_rel_organ`, `_rel_pengobatan`, `_rel_gizi` |
| Obat | `_rel_penyakit`, `_rel_pengobatan` |
| Organ | `_rel_penyakit`, `_rel_gizi` |
| Gizi | `_rel_penyakit`, `_rel_organ` |
| Pengobatan | `_rel_penyakit`, `_rel_obat` |

- Selalu isi relasi di kedua arah (misal: Penyakit→Obat DAN Obat→Penyakit)
- Template render otomatis di section "Artikel Terkait"
- Schema menambahkan `relatedLink` untuk SEO
- Maks 10 relasi per field

## Konvensi

- Prefix class: `Health_Wiki_`
- Prefix field: `hw_`
- Prefix CPT: `hw_`
- Prioritas hook: init=5, acf/init=10, the_content=20 (template) / 30 (autolink), wp_head=0-5
- PHP strict types: `declare(strict_types=1)` di setiap file
- Satu class per file, nama file `class-health-wiki-*.php`
- Semua komentar kode dalam Bahasa Indonesia
- Siap produksi: PHP 8.0+, keamanan di-escape, zero render-blocking
