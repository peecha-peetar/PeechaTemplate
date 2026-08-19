<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="toast"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg><span id="toastMsg"></span></div>

<header id="header">
  <div class="wrap header-inner">
    <a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
      <img src="<?php echo esc_url( barones_logo_url() ); ?>" alt="لوگو بارونس">
      <span class="brand-name"><?php bloginfo( 'name' ); ?><small>BARONES SPORT</small></span>
    </a>

    <nav id="nav">
      <?php if ( has_nav_menu( 'primary' ) ) : ?>
        <?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => false ) ); ?>
      <?php else : ?>
        <ul>
          <li><a class="active" href="<?php echo esc_url( home_url( '/' ) ); ?>">خانه</a></li>
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#categories">دسته‌بندی‌ها</a></li>
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#shop">فروشگاه</a></li>
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#offer">پیشنهاد ویژه</a></li>
          <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>#about">درباره بارونس</a></li>
        </ul>
      <?php endif; ?>
    </nav>

    <div class="header-actions">
      <div class="search-box">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex;width:100%">
          <input type="search" name="s" placeholder="جستجوی محصول…">
          <input type="hidden" name="post_type" value="product">
        </form>
      </div>

      <button class="icon-btn" id="cartBtn" aria-label="سبد خرید">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.6"/><circle cx="19" cy="21" r="1.6"/><path d="M2 3h3l2.7 12.4A2 2 0 0 0 9.7 17h9.7a2 2 0 0 0 2-1.6L23 7H6"/></svg>
        <span id="cartCount"><?php echo barones_cart_count(); ?></span>
      </button>

      <a class="btn btn-primary login-btn" href="<?php echo esc_url( barones_account_url() ); ?>">
        <?php echo is_user_logged_in() ? 'حساب کاربری' : 'ورود | ثبت‌نام'; ?>
      </a>

      <button class="icon-btn" id="menuBtn" aria-label="منو">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>
</header>

<div id="overlay"></div>
<aside id="cartDrawer" aria-label="سبد خرید بارونس">
  <div class="cart-head">
    <h3>سبد خرید بارونس</h3>
    <button id="closeCart" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  </div>
  <div class="drawer-body widget_shopping_cart_content">
    <?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } else { echo '<p class="woocommerce-mini-cart__empty-message">ووکامرس فعال نیست</p>'; } ?>
  </div>
  <div class="cart-foot">
    <a class="btn btn-primary" href="<?php echo esc_url( barones_checkout_url() ); ?>">ادامه و پرداخت امن</a>
    <a class="cart-foot-link" href="<?php echo esc_url( barones_cart_url() ); ?>">مشاهده سبد خرید</a>
  </div>
</aside>
