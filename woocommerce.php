<?php get_header(); ?>
<main class="wrap woo-wrap">
  <div class="sec-head"><div><h2><?php if ( function_exists( 'woocommerce_page_title' ) ) { woocommerce_page_title(); } else { echo 'فروشگاه'; } ?></h2><p>فروشگاه آنلاین بارونس</p></div></div>
  <div class="tabs">
    <a class="tab <?php echo is_shop() ? 'active' : ''; ?>" href="<?php echo esc_url( barones_shop_url() ); ?>">همه</a>
    <?php foreach ( barones_product_cats() as $barones_c ) : ?>
      <a class="tab <?php echo is_product_cat( $barones_c->slug ) ? 'active' : ''; ?>" href="<?php echo esc_url( get_term_link( $barones_c ) ); ?>"><?php echo esc_html( $barones_c->name ); ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ( function_exists( 'woocommerce_content' ) ) { woocommerce_content(); } else { echo '<p style="color:#5c6b80;font-weight:700">ووکامرس فعال نیست.</p>'; } ?>
</main>
<?php get_footer(); ?>