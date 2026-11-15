<?php
global $product;
if ( empty( $product ) || ! $product->is_visible() ) { return; }
$sd = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 14 );
?>
<li <?php wc_product_class( '', $product ); ?>>
<article class="prod-card">
<div class="prod-shot">
<a class="prod-media" href="<?php the_permalink(); ?>">
<?php
$sahel_img = array( 'loading' => 'lazy', 'decoding' => 'async' );
if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && empty( $GLOBALS['sahel_card_lcp'] ) ) {
    $GLOBALS['sahel_card_lcp'] = 1;
    $sahel_img = array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' );
}
echo $product->get_image( 'woocommerce_thumbnail', $sahel_img );
?>
<?php if ( function_exists( 'sahel_card_alt_img' ) ) { echo sahel_card_alt_img( $product ); } ?>
<?php if ( $product->is_on_sale() ) : $br = (float) $product->get_regular_price(); $bs = $product->get_sale_price(); $bp = $br > 0 && $bs !== '' ? round( ( ( $br - $bs ) / $br ) * 100 ) : 0; ?>
<span class="badge hot"><?php echo sahel_fa( $bp ); ?>٪ تخفیف</span>
<?php else :
    $bd = $product->get_date_created();
    $is_new = $bd && $bd->getTimestamp() > time() - 15 * DAY_IN_SECONDS;
?>
<span class="badge <?php echo $is_new ? 'new' : 'best'; ?>"><?php echo $is_new ? 'جدید' : esc_html( sahel_brand_short() ); ?></span>
<?php endif; ?>
<?php if ( $product->is_type( 'variable' ) ) : ?>
<span class="badge variant">دارای گزینه</span>
<?php endif; ?>
</a>
<?php $sahel_vid = function_exists( 'sahel_product_video_data' ) ? sahel_product_video_data( $product ) : null; ?>
<?php if ( $sahel_vid ) : ?>
<button type="button" class="prod-vid" data-type="<?php echo esc_attr( $sahel_vid['type'] ); ?>" data-src="<?php echo esc_url( $sahel_vid['src'] ); ?>" aria-label="پخش ویدئو"><span></span></button>
<?php endif; ?>
</div>
<div class="prod-body">
<?php echo wc_get_product_category_list( $product->get_id(), '، ', '<span class="prod-cat">', '</span>' ); ?>
<h3 class="prod-name"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
<?php $sahel_cv = function_exists( 'sahel_card_vars_html' ) ? sahel_card_vars_html( $product ) : ''; ?>
<?php if ( $sahel_cv ) : echo $sahel_cv; elseif ( $product->is_type( 'variable' ) ) : ?>
<div class="prod-vars">
<?php foreach ( $product->get_variation_attributes() as $var_name => $var_opts ) : ?>
<span><?php echo esc_html( wc_attribute_label( $var_name ) . ': ' . sahel_fa( count( $var_opts ) ) . ' تنوع' ); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ( $sd ) : ?><p class="prod-desc"><?php echo esc_html( $sd ); ?></p><?php endif; ?>
<div class="prod-footer">
<div class="prod-price"><?php echo $product->get_price_html(); ?></div>
<div class="prod-notch">
<span class="prod-notch-curve prod-notch-curve-1"></span>
<span class="prod-notch-curve prod-notch-curve-2"></span>
<?php woocommerce_template_loop_add_to_cart(); ?>
</div>
</div>
</div>
<?php $sahel_parts = function_exists( 'sahel_snapp_parts' ) ? sahel_snapp_parts( $product ) : 0; ?>
<?php if ( $sahel_parts ) : ?>
<p class="prod-snapp">پرداخت با اسنپ‌پی · <?php echo esc_html( function_exists( 'sahel_fa' ) ? sahel_fa( (string) $sahel_parts ) : (string) $sahel_parts ); ?> قسط</p>
<?php endif; ?>
</article>
</li>
