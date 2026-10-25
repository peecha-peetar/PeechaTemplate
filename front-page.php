<?php get_header(); ?>

<?php $sahel_hero = sahel_slide_data( 1 ); ?>
<div class="hero" id="top">
  <div class="wrap hero-inner">
    <div class="hero-text">
      <span class="pill"><i></i> <?php echo esc_html( $sahel_hero['pill'] ? $sahel_hero['pill'] : 'کالکشن جدید رسید' ); ?></span>
      <h1><?php echo esc_html( sahel_brand() ); ?> <span class="grad-text">همراه شماست</span></h1>
      <p>فروشگاه آنلاین <?php echo esc_html( sahel_brand() ); ?>؛ محصولات اورجینال، ارسال سریع و پشتیبانی واقعی، همیشه یک قدم جلوتر.</p>
      <div class="hero-cta">
        <a href="<?php echo esc_url( sahel_shop_url() ); ?>" class="btn btn-primary">مشاهده فروشگاه
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></a>
        <a href="#offer" class="btn btn-ghost">🔥 پیشنهاد شگفت‌انگیز</a>
      </div>
    </div>
    <div class="hero-visual">
      <div class="glow"></div>
      <?php if ( $sahel_hero['img'] ) : ?>
        <img class="main" src="<?php echo esc_url( $sahel_hero['img'] ); ?>" alt="<?php echo esc_attr( sahel_brand() ); ?>">
      <?php endif; ?>
    </div>
  </div>
</div>

<section id="categories">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><h2>دسته‌بندی‌های <span><?php echo esc_html( sahel_brand() ); ?></span></h2><p>محصولاتی که دنبالشون بودی، اینجاست</p></div>
      <a class="sec-link" href="<?php echo esc_url( sahel_shop_url() ); ?>">مشاهده همه محصولات
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></a>
    </div>
    <div class="cat-grid">
      <?php
      $sahel_cats = array_slice( sahel_product_cats(), 0, 4 );
      if ( $sahel_cats ) :
        foreach ( $sahel_cats as $sahel_cat ) :
          $sahel_thumb_id = get_term_meta( $sahel_cat->term_id, 'thumbnail_id', true );
          $sahel_img = $sahel_thumb_id ? wp_get_attachment_image_url( $sahel_thumb_id, 'medium_large' ) : '';
          ?>
          <a href="<?php echo esc_url( get_term_link( $sahel_cat ) ); ?>" class="cat-card reveal">
            <?php if ( $sahel_img ) : ?><img loading="lazy" src="<?php echo esc_url( $sahel_img ); ?>" alt="<?php echo esc_attr( $sahel_cat->name ); ?>"><?php endif; ?>
            <div class="ovl"></div>
            <span class="go"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></span>
            <div class="txt"><h3><?php echo esc_html( $sahel_cat->name ); ?></h3><p><?php echo sahel_fa( $sahel_cat->count ); ?> محصول</p></div>
          </a>
        <?php endforeach;
      else : ?>
        <p style="color:#5c6b80;font-weight:700">هنوز دسته‌بندی محصولی ثبت نشده است.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section id="shop" style="padding-top:20px">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><h2>فروشگاه <span><?php echo esc_html( sahel_brand() ); ?></span></h2><p>جدیدترین محصولات با گارانتی اصالت کالا</p></div>
    </div>
    <div class="tabs reveal">
      <a href="<?php echo esc_url( sahel_shop_url() ); ?>" class="tab active">همه</a>
      <?php foreach ( sahel_product_cats() as $sahel_t ) : ?>
        <a href="<?php echo esc_url( get_term_link( $sahel_t ) ); ?>" class="tab"><?php echo esc_html( $sahel_t->name ); ?></a>
      <?php endforeach; ?>
    </div>
    <div class="reveal">
      <?php if ( class_exists( 'WooCommerce' ) ) : ?>
        <?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); ?>
      <?php else : ?>
        <p style="color:#5c6b80;font-weight:700">افزونه WooCommerce فعال نیست؛ پس از فعال‌کردن، محصولات اینجا نمایش داده می‌شوند.</p>
      <?php endif; ?>
    </div>
    <div style="text-align:center;margin-top:44px">
      <a href="<?php echo esc_url( sahel_shop_url() ); ?>" class="btn btn-ghost">مشاهده تمام محصولات</a>
    </div>
  </div>
</section>

<?php $sahel_offer = sahel_top_sale(); if ( $sahel_offer ) : $sahel_bo = $sahel_offer['p']; ?>
<section id="offer" style="padding-top:20px">
  <div class="wrap">
    <div class="offer reveal">
      <div class="offer-inner">
        <div>
          <h3><i></i> پیشنهاد شگفت‌انگیز هفته</h3>
          <h2><?php echo esc_html( $sahel_bo->get_name() ); ?></h2>
          <p class="offer-desc">فقط تا پایان این شمارش معکوس، این محصول ویژه را با <?php echo sahel_fa( $sahel_offer['bp'] ); ?>٪ تخفیف دریافت کن.</p>
          <div class="count">
            <div class="cell"><b id="cdD">۰۲</b><span>روز</span></div>
            <div class="cell"><b id="cdH">۱۴</b><span>ساعت</span></div>
            <div class="cell"><b id="cdM">۴۵</b><span>دقیقه</span></div>
            <div class="cell"><b id="cdS">۳۰</b><span>ثانیه</span></div>
          </div>
          <button type="button" data-product_id="<?php echo esc_attr( $sahel_bo->get_id() ); ?>" class="btn btn-light add_to_cart_button ajax_add_to_cart">افزودن به سبد با <?php echo sahel_fa( $sahel_offer['bp'] ); ?>٪ تخفیف</button>
        </div>
        <div class="offer-visual">
          <span class="offer-off"><?php echo sahel_fa( $sahel_offer['bp'] ); ?>٪</span>
          <?php echo $sahel_bo->get_image( 'large' ); ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section style="padding-top:10px">
  <div class="wrap features">
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 3h13v13H1zM14 8h4l4 4v4h-8z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/></svg></div><h4>ارسال سریع</h4><p>تحویل سریع در سراسر کشور</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></div><h4>ضمانت اصالت</h4><p>تمامی کالاها اورجینال و دارای گارانتی</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg></div><h4>۷ روز بازگشت</h4><p>بدون قید و شرط تا ۷ روز مهلت بازگشت کالا</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div><h4>پرداخت امن</h4><p>درگاه بانکی معتبر و پرداخت در محل</p></div>
  </div>
</section>

<section id="about">
  <div class="wrap about-grid">
    <div class="about-logo reveal">
      <img src="<?php echo esc_url( sahel_logo_url() ); ?>" alt="<?php echo esc_attr( sahel_brand() ); ?>">
    </div>
    <div class="about-txt reveal">
      <h2>داستان <span class="grad-text"><?php echo esc_html( sahel_brand() ); ?></span></h2>
      <p><?php echo esc_html( sahel_brand() ); ?> با یک باور ساده متولد شد: هر مشتری لیاقت تجربه‌ی خرید آسان و محصولاتی باکیفیت را دارد. این متن را از پیشخوان وردپرس → صفحات → درباره ما ویرایش کنید.</p>
      <ul class="checks">
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> پشتیبانی واقعی هر روز هفته</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> مشاوره تخصصی رایگان انتخاب محصول</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> باشگاه مشتریان با تخفیف‌های اختصاصی</li>
      </ul>
    </div>
  </div>
</section>

<section style="padding-top:10px">
  <div class="wrap">
    <div class="news reveal">
      <h2>عضو <span class="grad-text">باشگاه مشتریان</span> شو 🏆</h2>
      <p>اولین نفری باش که از تخفیف‌های اختصاصی و محصولات جدید باخبر می‌شه</p>
      <form id="newsForm">
        <input type="tel" placeholder="شماره موبایل خود را وارد کنید" required>
        <button class="btn btn-primary" type="submit">عضویت</button>
      </form>
    </div>
  </div>
</section>

<?php get_footer(); ?>
