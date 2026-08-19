<?php
global $product;
if ( empty( $product ) || ! $product->is_visible() ) { return; }
$sd = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 14 );
?>
<li <?php wc_product_class( '', $product ); ?>>
<article class="prod-card">
<a class="prod-media" href="<?php the_permalink(); ?>">
<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?>
<?php if ( $product->is_on_sale() ) : $br = (float) $product->get_regular_price(); $bs = $product->get_sale_price(); $bp = $br > 0 && $bs !== '' ? round( ( ( $br - $bs ) / $br ) * 100 ) : 0; ?>
<span class="badge hot"><?php echo sahel_fa( $bp ); ?>٪ تخفیف</span>
<?php else :
    $bd = $product->get_date_created();
    $is_new = $bd && $bd->getTimestamp() > time() - 15 * DAY_IN_SECONDS;
?>
<span class="badge <?php echo $is_new ? 'new' : 'best'; ?>"><?php echo $is_new ? 'جدید' : esc_html( sahel_brand_short() ); ?></span>
<?php endif; ?>
</a>
<div class="prod-body">
<?php echo wc_get_product_category_list( $product->get_id(), '، ', '<span class="prod-cat">', '</span>' ); ?>
<h3 class="prod-name"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
<?php if ( $product->is_type( 'variable' ) ) : ?>
<div class="prod-vars">
<?php foreach ( $product->get_variation_attributes() as $var_name => $var_opts ) : ?>
<span><?php echo esc_html( wc_attribute_label( $var_name ) . ': ' . sahel_fa( count( $var_opts ) ) . ' تنوع' ); ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ( $sd ) : ?><p class="prod-desc"><?php echo esc_html( $sd ); ?></p><?php endif; ?>
<div class="prod-price"><?php echo $product->get_price_html(); ?></div>
<?php woocommerce_template_loop_add_to_cart(); ?>
</div>
</article>
</li>
