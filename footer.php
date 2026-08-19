<footer id="contact">
  <div class="wrap">
    <div class="foot-grid">
      <div class="foot-brand">
        <img src="<?php echo esc_url( barones_logo_url() ); ?>" alt="بارونس">
        <p>بارونس؛ فروشگاه آنلاین لوازم ورزشی، بدنسازی، کتانی و پیلاتس برای آقایان و خانم‌ها. قدرت، سرعت، سبک.</p>
        <div class="socials">
          <a href="#" aria-label="اینستاگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
          <a href="#" aria-label="تلگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3L2 10.5l6 2.5M22 3l-3 18-8-8M22 3L8 13"/></svg></a>
          <a href="#" aria-label="واتساپ"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5z"/></svg></a>
        </div>
      </div>
      <div>
        <h5>دسترسی سریع</h5>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a>
        <a href="<?php echo esc_url( barones_shop_url() ); ?>">فروشگاه</a>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>#offer">پیشنهاد ویژه</a>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>#about">درباره بارونس</a>
      </div>
      <div>
        <h5>دسته‌بندی‌ها</h5>
        <?php $barones_fcats = barones_product_cats( 4 ); if ( $barones_fcats ) : foreach ( $barones_fcats as $barones_f ) : ?>
          <a href="<?php echo esc_url( get_term_link( $barones_f ) ); ?>"><?php echo esc_html( $barones_f->name ); ?></a>
        <?php endforeach; else : ?>
          <a href="<?php echo esc_url( barones_shop_url() ); ?>">کتانی</a>
          <a href="<?php echo esc_url( barones_shop_url() ); ?>">بدنسازی</a>
          <a href="<?php echo esc_url( barones_shop_url() ); ?>">پیلاتس</a>
        <?php endif; ?>
      </div>
      <div>
        <h5>تماس با بارونس</h5>
        <p>تهران، بلوار ورزش، مرکز خرید بارونس، پلاک ۲۶</p>
        <p>تلفن: <?php echo barones_fa( '021-910000' ); ?></p>
        <p>ایمیل: info@barones.ir</p>
        <p>هر روز، ۹ صبح تا ۹ شب</p>
      </div>
    </div>
    <div class="foot-bottom">
      <span>© <?php echo barones_fa( number_format( date( 'Y' ) - 621, 0, '', '' ) ); ?> تمامی حقوق برای <b>بارونس</b> محفوظ است.</span>
      <span>طراحی‌شده با ⚡ برای قهرمان‌ها</span>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
