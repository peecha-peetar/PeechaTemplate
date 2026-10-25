<?php get_header(); ?>
<main class="wrap woo-wrap">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); the_content(); endwhile;
  else : ?>
    <p style="color:#5c6b80;font-weight:700">محتوایی یافت نشد؛ از منو به فروشگاه بارونس بروید.</p>
  <?php endif; ?>
</main>
<?php get_footer(); ?>
