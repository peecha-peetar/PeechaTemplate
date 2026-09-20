<footer id="contact">
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <img src="<?php echo esc_url( sahel_logo_url() ); ?>" alt="<?php echo esc_attr( sahel_brand() ); ?>">
        <p><?php echo esc_html( get_theme_mod( 'sahel_footer_about', sahel_brand() . '؛ فروشگاه آنلاین.' ) ); ?></p>
        <div class="socials">
          <a href="<?php echo esc_url( get_theme_mod( 'sahel_instagram', '#' ) ); ?>" aria-label="اینستاگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
          <a href="<?php echo esc_url( get_theme_mod( 'sahel_telegram', '#' ) ); ?>" aria-label="تلگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3L2 10.5l6 2.5M22 3l-3 18-8-8M22 3L8 13"/></svg></a>
          <a href="<?php echo esc_url( get_theme_mod( 'sahel_whatsapp', '#' ) ); ?>" aria-label="واتساپ"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5z"/></svg></a>
        </div>
      </div>
      <div>
        <h5>دسترسی سریع</h5>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
        <a href="<?php echo esc_url( sahel_shop_url() ); ?>">فروشگاه</a>
        <a href="<?php echo esc_url( sahel_page_url( 'about-us' ) ); ?>">درباره ما</a>
        <a href="<?php echo esc_url( sahel_page_url( 'contact-us' ) ); ?>">تماس با ما</a>
      </div>
      <div>
        <h5>دسته‌بندی‌ها</h5>
        <?php $sahel_fcats = sahel_product_cats( 4 ); if ( $sahel_fcats ) : foreach ( $sahel_fcats as $sahel_f ) : ?>
          <a href="<?php echo esc_url( get_term_link( $sahel_f ) ); ?>"><?php echo esc_html( $sahel_f->name ); ?></a>
        <?php endforeach; else : ?>
          <a href="<?php echo esc_url( sahel_shop_url() ); ?>">مشاهده فروشگاه</a>
        <?php endif; ?>
      </div>
      <div>
        <h5>تماس با <?php echo esc_html( sahel_brand() ); ?></h5>
        <p><?php echo esc_html( get_theme_mod( 'sahel_address', 'آدرس فروشگاه' ) ); ?></p>
        <p>تلفن: <?php echo esc_html( get_theme_mod( 'sahel_phone', sahel_fa( '021-220000' ) ) ); ?></p>
        <p>ایمیل: <?php echo esc_html( get_theme_mod( 'sahel_email', 'info@example.com' ) ); ?></p>
        <p><?php echo esc_html( get_theme_mod( 'sahel_hours', 'هر روز ۱۰-۲۱' ) ); ?></p>
      </div>
    </div>
    <div class="foot-bottom">
      <span><?php echo esc_html( get_theme_mod( 'sahel_copyright', '© تمامی حقوق برای ' . sahel_brand() . ' محفوظ است.' ) ); ?></span>
      <span>🎨 <a class="peecha-link" href="<?php echo esc_url( get_theme_mod( 'sahel_peecha_url', 'https://www.peecha.ir' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_theme_mod( 'sahel_peecha_text', 'طراحی شده توسط پیچا' ) ); ?></a></span>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
