<?php get_header(); ?>

<div class="hero" id="top">
  <div class="wrap hero-inner">
    <div class="hero-text">
      <span class="pill"><i></i> کالکشنن ۲۰۲۶ بارونس رسید</span>
      <h1>تجهیزات ورزشی <span class="grad-text">نسل آینده</span><br>برای قهرمان‌های امروز</h1>
      <p>بارونس؛ مرجع تخصصی فروش آنلاین لوازم بدنسازی، کتانی و پیلاتس برای آقایان و خانم‌ها. با ضمانت اصالت کالا، ارسال اکسپرس و پشتیبانی واقعی، یک قدم جلوتر تمرین کن.</p>
      <div class="hero-cta">
        <a href="<?php echo esc_url( barones_shop_url() ); ?>" class="btn btn-primary">مشاهده فروشگاه
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></a>
        <a href="#offer" class="btn btn-ghost">🔥 پیشنهاد شگفت‌انگیز</a>
      </div>
      <div class="hero-stats">
        <div class="stat"><b class="grad-text">+۱۲٬۰۰۰</b><span>مشتری خوشحال</span></div>
        <div class="stat"><b class="grad-text">+۳۵</b><span>محصول اورجینال</span></div>
        <div class="stat"><b class="grad-text">۹۸٪</b><span>رضایت خرید</span></div>
      </div>
    </div>
    <div class="hero-visual">
      <div class="glow"></div>
      <img class="main" src="<?php echo esc_url( barones_hero_url() ); ?>" alt="کتانی بارونس">
      <div class="float-chip chip-a"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z"/></svg> ضمانت اصالت کالا</div>
      <div class="float-chip chip-b"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h7l-1 8 11-13h-7z"/></svg> ارسال اکسپرس ۲۴ ساعته</div>
    </div>
  </div>
</div>

<div class="marquee">
  <div class="marquee-track">
    <span><b>بارونس</b> ✦ قدرت ✦ سرعت ✦ سبک ✦ <em>ضمانت اصالت</em> ✦ بدنسازی  کتانی ✦ پیلاتس ✦ <b>ارسال اکسپرس</b> ✦ آقایان  خانم‌ها ✦</span>
    <span><b>بارونس</b> ✦ قدرت ✦ سرعت ✦ سبک ✦ <em>ضمانت اصالت</em> ✦ بدنسازی ✦ کتانی ✦ پیلاتس ✦ <b>ارسال اکسپرس</b> ✦ آقایان ✦ خانم‌ها ✦</span>
  </div>
</div>

<section id="categories">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><h2>دسته‌بندی‌های <span>بارونس</span></h2><p>هر آنچه برای یک زندگی ورزشی حرفه‌ای نیاز داری</p></div>
      <a class="sec-link" href="<?php echo esc_url( barones_shop_url() ); ?>">مشاهده همه محصولات
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></a>
    </div>
    <div class="cat-grid">
      <?php
      $barones_fallbacks = array(
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1ad607918-34c7-4b2b-9dec-82de88040faf.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1ba0a8b27-83c9-40c3-ac7d-676fc12d37b9.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/14ff4c4fa-e59c-44de-b2fa-0b0816f1593d.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/12138fea5-4382-49f1-b2ce-bd192dc93ca8.png',
      );
      $barones_defaults = array(
        array( 'کتانی', 'رانینگ و تمرینی آقایان و خانم‌ها' ),
        array( 'بدنسازی', 'دمبل، کتل‌بل و تجهیزات قدرت' ),
        array( 'پیلاتس', 'مت، کش و ابزارهای اصلاحی' ),
        array( 'لوازم جانبی', 'شیکر، دستکش و اکسسوری' ),
      );
      $barones_cats = array_slice( barones_product_cats(), 0, 4 );
      if ( $barones_cats ) : $barones_i = 0;
        foreach ( $barones_cats as $barones_cat ) :
          $barones_thumb_id = get_term_meta( $barones_cat->term_id, 'thumbnail_id', true );
          $barones_img = $barones_thumb_id ? wp_get_attachment_image_url( $barones_thumb_id, 'medium_large' ) : $barones_fallbacks[ $barones_i % 4 ];
          ?>
          <a href="<?php echo esc_url( get_term_link( $barones_cat ) ); ?>" class="cat-card reveal">
            <img loading="lazy" src="<?php echo esc_url( $barones_img ); ?>" alt="<?php echo esc_attr( $barones_cat->name ); ?>">
            <div class="ovl"></div>
            <span class="go"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></span>
            <div class="txt"><h3><?php echo esc_html( $barones_cat->name ); ?></h3><p><?php echo barones_fa( $barones_cat->count ); ?> محصول</p></div>
          </a>
          <?php $barones_i++;
        endforeach;
      else :
        foreach ( $barones_defaults as $barones_k => $barones_d ) : ?>
          <a href="<?php echo esc_url( barones_shop_url() ); ?>" class="cat-card reveal">
            <img loading="lazy" src="<?php echo esc_url( $barones_fallbacks[ $barones_k ] ); ?>" alt="<?php echo esc_attr( $barones_d[0] ); ?>">
            <div class="ovl"></div>
            <span class="go"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg></span>
            <div class="txt"><h3><?php echo esc_html( $barones_d[0] ); ?></h3><p><?php echo esc_html( $barones_d[1] ); ?></p></div>
          </a>
        <?php endforeach;
      endif; ?>
    </div>
  </div>
</section>

<section id="shop" style="padding-top:20px">
  <div class="wrap">
    <div class="sec-head reveal">
      <div><h2>فروشگاه <span>بارونس</span></h2><p>جدیدترین محصولات اورجینال با گارانتی تعویض ۷ روزه</p></div>
    </div>
    <div class="tabs reveal">
      <a href="<?php echo esc_url( barones_shop_url() ); ?>" class="tab active">همه</a>
      <?php foreach ( barones_product_cats() as $barones_t ) : ?>
        <a href="<?php echo esc_url( get_term_link( $barones_t ) ); ?>" class="tab"><?php echo esc_html( $barones_t->name ); ?></a>
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
      <a href="<?php echo esc_url( barones_shop_url() ); ?>" class="btn btn-ghost">مشاهده تمام محصولات بارونس</a>
    </div>
  </div>
</section>

<?php $barones_offer = barones_top_sale_product(); if ( $barones_offer ) : $barones_bo = $barones_offer['product']; ?>
<section id="offer" style="padding-top:20px">
  <div class="wrap">
    <div class="offer reveal">
      <div class="offer-inner">
        <div>
          <h3><i></i> پیشنهاد شگفت‌انگیز هفته بارونس</h3>
          <h2><?php echo esc_html( $barones_bo->get_name() ); ?></h2>
          <p class="offer-desc">فقط تا پایان این شمارش معکوس، این محصول ویژه بارونس را با <?php echo barones_fa( $barones_offer['pct'] ); ?>٪ تخفیف و ارسال رایگان دریافت کن.</p>
          <div class="count">
            <div class="cell"><b id="cdD">۰۲</b><span>روز</span></div>
            <div class="cell"><b id="cdH">۱۴</b><span>ساعت</span></div>
            <div class="cell"><b id="cdM">۴۵</b><span>دقیقه</span></div>
            <div class="cell"><b id="cdS">۳۰</b><span>ثانیه</span></div>
          </div>
          <button type="button" data-product_id="<?php echo esc_attr( $barones_bo->get_id() ); ?>" class="btn btn-light add_to_cart_button ajax_add_to_cart">افزودن به سبد با <?php echo barones_fa( $barones_offer['pct'] ); ?>٪ تخفیف</button>
        </div>
        <div class="offer-visual">
          <span class="offer-off"><?php echo barones_fa( $barones_offer['pct'] ); ?>٪</span>
          <?php echo $barones_bo->get_image( 'large' ); ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<section style="padding-top:10px">
  <div class="wrap features">
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 3h13v13H1zM14 8h4l4 4v4h-8z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/></svg></div><h4>ارسال اکسپرس</h4><p>تحویل ۲۴ ساعته در تهران و ۴۸ ساعته سراسر کشور</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/></svg></div><h4>ضمانت اصالت</h4><p>تمامی کالاها اورجینال با هولوگرام رسمی بارونس</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg></div><h4>۷ روز بازگشت</h4><p>بدون قید و شرط تا ۷ روز مهلت بازگشت کالا</p></div>
    <div class="feat reveal"><div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div><h4>پرداخت امن</h4><p>درگاه بانکی معتبر و پرداخت در محل</p></div>
  </div>
</section>

<section id="about">
  <div class="wrap about-grid">
    <div class="about-logo reveal">
      <img src="<?php echo esc_url( barones_logo_url() ); ?>" alt="لوگو بارونس">
    </div>
    <div class="about-txt reveal">
      <h2>داستان <span class="grad-text">بارونس</span>؛ قدرت، سرعت، سبک</h2>
      <p>بارونس با یک باور ساده متولد شد: هر کسی لیاقت تجهیزات ورزشی حرفه‌ای و اورجینال را دارد. ما از بدنسازی تا پیلاتس، از کتانی‌های رانینگ تا اکسسوری، گلچینی از بهترین‌ها را برای آقایان و خانم‌ها فراهم کرده‌ایم تا مسیر قهرمانی‌ات هموارتر شود.</p>
      <ul class="checks">
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> پشتیبانی واقعی ۷ روز هفته، ۹ صبح تا ۹ شب</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> مشاوره تخصصی رایگان انتخاب سایز و محصول</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg> باشگاه مشتریان با تخفیف‌های اختصاصی هر هفته</li>
      </ul>
    </div>
  </div>
</section>

<section style="padding-top:10px">
  <div class="wrap">
    <div class="sec-head reveal"><div><h2>نظر <span>مشتریان</span> بارونس</h2><p>تجربه واقعی قهرمان‌های بارونس</p></div></div>
    <div class="testi-grid">
      <div class="testi reveal">
        <div class="stars"><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg></div>
        <p>«کتانی رانینگ بارونس رو خریدم، کیفیتش از برندهای خارجی که قبلاً می‌پوشیدم بهتره. ارسالش هم واقعاً ۲۴ ساعته بود!»</p>
        <div class="who"><span class="av">م</span><div><b>محمد رضایی</b><span>تهران — کتانی مردانه</span></div></div>
      </div>
      <div class="testi reveal">
        <div class="stars"><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg></div>
        <p>«ست پیلاتس بارونس برای تمرین‌های خانگیم عالیه. مشاوره سایز و راهنماییشون فوق‌العاده حرفه‌ای بود.»</p>
        <div class="who"><span class="av">س</span><div><b>سارا محمدی</b><span>اصفهان — ست پیلاتس</span></div></div>
      </div>
      <div class="testi reveal">
        <div class="stars"><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg><svg viewBox="0 0 24 24"><path d="M12 2l3 7 7 .8-5.3 4.9L18.2 22 12 18.3 5.8 22l1.5-7.3L2 9.8 9 9z"/></svg></div>
        <p>«دمبل‌ها و کتل‌بل‌ها کیفیت باشگاهی دارن ولی با قیمت منصفانه. بسته‌بندی و پیگیری سفارشم بی‌نقص بود.»</p>
        <div class="who"><span class="av">ع</span><div><b>علی کریمی</b><span>شیراز — تجهیزات بدنسازی</span></div></div>
      </div>
    </div>
  </div>
</section>

<section style="padding-top:10px">
  <div class="wrap">
    <div class="news reveal">
      <h2>عضو <span class="grad-text">باشگاه بارونس</span> شو 🏆</h2>
      <p>اولین نفری باش که از تخفیف‌های اختصاصی و کالکشنن‌های جدید باخبر می‌شه</p>
      <form id="newsForm">
        <input type="tel" placeholder="شماره موبایل خود را وارد کنید" required>
        <button class="btn btn-primary" type="submit">عضویت</button>
      </form>
    </div>
  </div>
</section>

<?php get_footer(); ?>
