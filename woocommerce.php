<?php get_header(); ?>
<main class="wrap woo-wrap">
  <div class="sec-head"><div><h2><?php if ( function_exists( 'woocommerce_page_title' ) ) { woocommerce_page_title(); } else { echo 'فروشگاه'; } ?></h2><p>فروشگاه آنلاین <?php echo esc_html( sahel_brand() ); ?></p></div></div>
  <div class="tabs">
    <a class="tab <?php echo is_shop() ? 'active' : ''; ?>" href="<?php echo esc_url( sahel_shop_url() ); ?>">همه</a>
    <?php foreach ( sahel_product_cats() as $sahel_c ) : ?>
      <a class="tab <?php echo is_product_cat( $sahel_c->slug ) ? 'active' : ''; ?>" href="<?php echo esc_url( get_term_link( $sahel_c ) ); ?>"><?php echo esc_html( $sahel_c->name ); ?></a>
    <?php endforeach; ?>
  </div>
  <?php if ( function_exists( 'woocommerce_content' ) ) { woocommerce_content(); } else { echo '<p style="color:#5c6b80;font-weight:700">ووکامرس فعال نیست.</p>'; } ?>
</main>
<?php get_footer(); ?>
