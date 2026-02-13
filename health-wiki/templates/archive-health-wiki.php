<?php
/**
 * Template Archive — Daftar alfabet A-Z per CPT.
 *
 * Dimuat via Health_Wiki_Archive::load_template().
 * Menggunakan header.php dan footer.php dari tema aktif.
 *
 * @package HealthWiki
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$data    = Health_Wiki_Archive::get_data();
$letters = range( 'A', 'Z' );

get_header();
?>

<main id="hw-archive" class="hw-archive" role="main">
<div class="hw-archive__container">

    <nav class="hw-breadcrumb" aria-label="Breadcrumb">
        <ol>
            <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Beranda</a></li>
            <li aria-current="page"><?php echo esc_html( $data['title'] ); ?></li>
        </ol>
    </nav>

    <h1 class="hw-archive__title"><?php echo esc_html( $data['title'] ); ?></h1>

    <form class="hw-archive__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <input type="hidden" name="post_type" value="<?php echo esc_attr( $data['post_type'] ); ?>">
        <label for="hw-search" class="screen-reader-text"><?php echo esc_attr( $data['placeholder'] ); ?></label>
        <input
            type="search"
            id="hw-search"
            name="s"
            placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>"
            class="hw-archive__search-input"
        >
        <button type="submit" class="hw-archive__search-btn" aria-label="Cari">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        </button>
    </form>

    <nav class="hw-archive__az" aria-label="Navigasi abjad">
        <h2 class="hw-archive__az-title">Cari berdasarkan abjad</h2>
        <div class="hw-archive__az-grid">
            <?php foreach ( $letters as $l ) :
                $has   = in_array( $l, $data['all_letters'], true );
                $active = $data['current'] === $l;
                $cls   = 'hw-archive__letter';
                if ( $active ) $cls .= ' hw-archive__letter--active';
                if ( ! $has )  $cls .= ' hw-archive__letter--empty';
            ?>
                <?php if ( $has ) : ?>
                    <a href="<?php echo esc_url( $data['archive_url'] . '?huruf=' . $l ); ?>" class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $l ); ?></a>
                <?php else : ?>
                    <span class="<?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $l ); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ( $data['current'] ) : ?>
                <a href="<?php echo esc_url( $data['archive_url'] ); ?>" class="hw-archive__letter hw-archive__letter--reset">Semua</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="hw-archive__list">
        <?php if ( ! empty( $data['grouped'] ) ) : ?>
            <?php foreach ( $data['grouped'] as $letter => $posts ) : ?>
                <section class="hw-archive__group" id="huruf-<?php echo esc_attr( (string) $letter ); ?>">
                    <h3 class="hw-archive__group-letter"><?php echo esc_html( (string) $letter ); ?></h3>
                    <ul class="hw-archive__group-list">
                        <?php foreach ( $posts as $p ) : ?>
                            <li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="hw-archive__empty">Belum ada konten.</p>
        <?php endif; ?>
    </div>

    <?php if ( $data['total'] > 0 ) : ?>
        <p class="hw-archive__count">Total: <?php echo esc_html( (string) $data['total'] ); ?> artikel</p>
    <?php endif; ?>

</div>
</main>

<?php get_footer(); ?>
