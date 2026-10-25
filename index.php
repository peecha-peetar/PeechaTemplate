<?php
/* قالب پشتیبان برای صفحاتی که موتور قالب پوشش نمی‌دهد: آرشیو، جستجو، پیش‌نمایش المنتور */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
if ( is_singular() ) {
    while ( have_posts() ) {
        the_post();
        $el = false;
        if ( class_exists( '\Elementor\Plugin' ) ) { $d = \Elementor\Plugin::$instance->documents->get( get_the_ID() ); $el = $d ? $d->is_built_with_elementor() : false; }
        if ( $el ) { the_content(); continue; }
        ?>
<main class="wrap page-main"><article class="page-card"><h1 class="page-title"><?php the_title(); ?></h1><div class="page-content"><?php the_content(); ?></div></article></main>
        <?php
    }
} else {
    $title = is_search() ? 'نتایج جستجو: ' . get_search_query() : ( is_home() ? 'مقالات' : wp_strip_all_tags( get_the_archive_title() ) );
    ?>
<main class="wrap page-main">
<h1 class="page-title"><?php echo esc_html( $title ); ?></h1>
<?php if ( have_posts() ) : ?>
<div class="blog-grid"><?php while ( have_posts() ) { the_post(); echo sahel_post_card( get_post() ); } ?></div>
<div class="shop-pagination"><?php the_posts_pagination( array( 'prev_text' => '→', 'next_text' => '←' ) ); ?></div>
<?php else : ?>
<div class="empty-state"><p>مطلبی یافت نشد 😕</p><a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت به خانه</a></div>
<?php endif; ?>
</main>
    <?php
}
get_footer();
