<?php
/** Peecha v4.5 — Apple-style + Multiple Headers + Fixed Issues — فارسی */
if ( ! defined( 'ABSPATH' ) ) exit;

/* قفل دامنه */
// define( 'PEECHA_DOMAINS', array( 'example.com', 'www.example.com' ) );
if ( defined( 'PEECHA_DOMAINS' ) && is_array( PEECHA_DOMAINS ) && ! empty( PEECHA_DOMAINS ) && ! is_admin() ) {
    $peecha_host = preg_replace( '/^www\./', '', isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' );
    $peecha_ok = false;
    foreach ( PEECHA_DOMAINS as $peecha_d ) { if ( preg_replace( '/^www\./', '', $peecha_d ) === $peecha_host ) { $peecha_ok = true; break; } }
    if ( ! $peecha_ok ) { add_filter( 'template_include', function() { wp_die( '⛔ قالب Peecha برای این دامنه مجوز ندارد.', 'مجوز قالب Peecha', array( 'response' => 403 ) ); }, 99999 ); }
}

if ( ! function_exists( 'sahel_fa' ) ) { function sahel_fa( $s ) { return str_replace( array('0','1','2','3','4','5','6','7','8','9'), array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'), (string) $s ); } }
if ( ! function_exists( 'sahel_fa_safe' ) ) {
    function sahel_fa_safe( $s ) {
        $s = (string) $s;
        return preg_replace_callback( '/&[a-zA-Z][a-zA-Z0-9]*;|&#\d+;|&#x[0-9a-fA-F]+;|\d/', function ( $m ) {
            if ( $m[0][0] === '&' ) { return $m[0]; }
            $fa = array( '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹' );
            return $fa[ (int) $m[0] ];
        }, $s );
    }
}
if ( ! function_exists( 'sahel_num' ) ) { function sahel_num( $n ) { return sahel_fa( number_format( (float) $n ) ); } }
if ( ! function_exists( 'sahel_rgb_str' ) ) {
    function sahel_rgb_str( $hex ) {
        $hex = ltrim( (string) $hex, '#' );
        if ( strlen( $hex ) === 3 ) { $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2]; }
        if ( strlen( $hex ) !== 6 ) { return '250,247,242'; }
        return hexdec( substr( $hex, 0, 2 ) ) . ',' . hexdec( substr( $hex, 2, 2 ) ) . ',' . hexdec( substr( $hex, 4, 2 ) );
    }
}

function sahel_kses_map( $html ) {
    $allowed = wp_kses_allowed_html( 'post' );
    $allowed['iframe'] = array(
        'src' => true, 'width' => true, 'height' => true, 'style' => true,
        'allow' => true, 'allowfullscreen' => true, 'loading' => true,
        'referrerpolicy' => true, 'frameborder' => true, 'title' => true,
    );
    return wp_kses( (string) $html, $allowed );
}
function sahel_brand()       { return get_theme_mod( 'sahel_brand', 'فروشگاه شما' ); }
function sahel_brand_short() { return get_theme_mod( 'sahel_brand_short', 'فروشگاه' ); }
function sahel_brand_en()    { return get_theme_mod( 'sahel_brand_en', 'YOUR STORE' ); }
function sahel_logo_url() {
    if ( function_exists( 'has_custom_logo' ) && has_custom_logo() ) {
        $u = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
        if ( $u ) { return $u; }
    }
    return 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1c1baba47-699f-44b5-ab28-18c79972310a.png';
}
function sahel_shop_url() { return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop' ); }
function sahel_cart_url() { return function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart' ); }
function sahel_checkout_url() { return function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout' ); }
function sahel_account_url() { $i = get_option( 'woocommerce_myaccount_page_id' ); return $i ? get_permalink( $i ) : wp_login_url(); }
function sahel_cart_count() { if ( function_exists( 'WC' ) && WC()->cart ) { return sahel_num( WC()->cart->get_cart_contents_count() ); } return sahel_fa( 0 ); }

function sahel_product_cats( $n = 0 ) {
    if ( ! taxonomy_exists( 'product_cat' ) ) return array();
    $a = array( 'hide_empty' => false, 'parent' => 0 ); if ( $n ) $a['number'] = $n;
    $t = get_terms( 'product_cat', $a ); return is_wp_error( $t ) ? array() : (array) $t;
}
function sahel_product_cats_tree( $parent = 0 ) {
    if ( ! taxonomy_exists( 'product_cat' ) ) return array();
    $terms = get_terms( 'product_cat', array( 'hide_empty' => false, 'parent' => $parent ) );
    if ( is_wp_error( $terms ) || empty( $terms ) ) return array();
    $tree = array();
    foreach ( $terms as $t ) { $tree[] = array( 'term' => $t, 'children' => sahel_product_cats_tree( $t->term_id ) ); }
    return $tree;
}
function sahel_product_cats_flat() {
    $flat = array();
    $walk = function( $nodes ) use ( &$flat, &$walk ) {
        foreach ( $nodes as $n ) { $flat[] = $n['term']; $walk( $n['children'] ); }
    };
    $walk( sahel_product_cats_tree() );
    return $flat;
}
function sahel_cat_img( $c, $fb ) {
    $tid = get_term_meta( $c->term_id, 'thumbnail_id', true );
    return $tid ? wp_get_attachment_image_url( $tid, 'medium' ) : $fb;
}
/* برای مگامنوی دسته‌بندی‌ها: هر سطحی که زیرشاخه داره فقط یه تیتر ساده‌ست،
   فقط عمیق‌ترین سطح (بدون زیرشاخه) به‌صورت کارت تصویری نمایش داده می‌شه. */
function sahel_render_cat_dd_node( $node, $fbimg ) {
    $c = $node['term'];
    if ( ! empty( $node['children'] ) ) {
        $html = '<div class="dd-group dd-group-parent"><a class="dd-title" href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a><div class="dd-cards">';
        foreach ( $node['children'] as $child ) {
            $html .= sahel_render_cat_dd_node( $child, $fbimg );
        }
        $html .= '</div></div>';
        return $html;
    }
    return '<a class="dd-card" href="' . esc_url( get_term_link( $c ) ) . '"><img src="' . esc_url( sahel_cat_img( $c, $fbimg ) ) . '" alt=""><span><b>' . esc_html( $c->name ) . '</b><small>' . sahel_num( $c->count ) . '</small></span></a>';
}
function sahel_page_url( $slug ) { $p = get_page_by_path( $slug ); return $p ? get_permalink( $p ) : home_url( '/' ); }
function sahel_footer_link_url( $type, $val ) {
    if ( $type === 'home' ) { return home_url( '/' ); }
    if ( $type === 'shop' ) { return sahel_shop_url(); }
    if ( $type === 'url' ) { return $val; }
    if ( $type === 'page' ) { return sahel_page_url( $val ); }
    return '';
}
function sahel_top_sale() {
    if ( ! function_exists( 'wc_get_products' ) ) return null;
    $cached = get_transient( 'sahel_top_sale_id' );
    if ( false !== $cached ) {
        if ( ! $cached ) { return null; }
        $p = wc_get_product( $cached['id'] );
        return $p ? array( 'p' => $p, 'bp' => $cached['bp'] ) : null;
    }
    $ids = wc_get_products( array( 'limit' => 200, 'status' => 'publish', 'return' => 'ids', 'on_sale' => true, 'orderby' => 'date', 'order' => 'DESC' ) );
    $best = null; $bp = 0;
    foreach ( (array) $ids as $id ) { $p = wc_get_product( $id ); if ( ! $p ) continue;
        $r = (float) $p->get_regular_price(); $s = $p->get_sale_price();
        if ( $r > 0 && $s !== '' ) { $pc = round( ( ( $r - $s ) / $r ) * 100 ); if ( $pc > $bp ) { $bp = $pc; $best = $p; } } }
    set_transient( 'sahel_top_sale_id', $best ? array( 'id' => $best->get_id(), 'bp' => $bp ) : 0, 15 * MINUTE_IN_SECONDS );
    return $best ? array( 'p' => $best, 'bp' => $bp ) : null;
}
add_action( 'save_post_product', function() { delete_transient( 'sahel_top_sale_id' ); } );
add_action( 'woocommerce_update_product', function() { delete_transient( 'sahel_top_sale_id' ); } );
function sahel_arrows() {
    return '<div class="strip-arrows"><button class="s-arrow prev" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button><button class="s-arrow next" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button></div>';
}
function sahel_slide_data( $i ) {
    $d = array(
        1 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1762ba743-5206-44f2-8090-f738bd071c0d.png', 'pill' => 'کالکشن جدید', 't1' => 'عنوان اسلاید ۱؛', 't2' => 'قابل ویرایش', 'desc' => 'این متن نمونه است؛ از تنظیمات نمایش ← اسلاید ۱ آن را با توضیحات خودتان جایگزین کنید.', 'btn' => 'مشاهده محصولات', 'url' => '' ),
        2 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1316896db-bd87-4b94-afa0-087689252253.png', 'pill' => 'پیشنهاد ویژه', 't1' => 'عنوان اسلاید ۲؛', 't2' => 'قابل ویرایش', 'desc' => 'این متن نمونه است؛ از تنظیمات نمایش ← اسلاید ۲ آن را با توضیحات خودتان جایگزین کنید.', 'btn' => 'مشاهده محصولات', 'url' => '' ),
        3 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1cab5e6b5-d545-4e84-a529-fbfb06285707.png', 'pill' => 'محصولات منتخب', 't1' => 'عنوان اسلاید ۳؛', 't2' => 'قابل ویرایش', 'desc' => 'این متن نمونه است؛ از تنظیمات نمایش ← اسلاید ۳ آن را با توضیحات خودتان جایگزین کنید.', 'btn' => 'مشاهده محصولات', 'url' => '' ),
    );
    $b = isset( $d[ $i ] ) ? $d[ $i ] : array( 'img' => '', 'pill' => '', 't1' => '', 't2' => '', 'desc' => '', 'btn' => '', 'url' => '' );
    return array(
        'bgtype' => get_theme_mod( 'sahel_slide' . $i . '_bgtype', 'image' ),
        'img' => get_theme_mod( 'sahel_slide' . $i . '_image', $b['img'] ),
        'bgcolor1' => get_theme_mod( 'sahel_slide' . $i . '_bgcolor1', '#f3ede4' ),
        'bgcolor2' => get_theme_mod( 'sahel_slide' . $i . '_bgcolor2', '#d9c8b4' ),
        'pill' => get_theme_mod( 'sahel_slide' . $i . '_pill', $b['pill'] ),
        't1' => get_theme_mod( 'sahel_slide' . $i . '_t1', $b['t1'] ),
        't2' => get_theme_mod( 'sahel_slide' . $i . '_t2', $b['t2'] ),
        'desc' => get_theme_mod( 'sahel_slide' . $i . '_desc', $b['desc'] ),
        'btn' => get_theme_mod( 'sahel_slide' . $i . '_btn', $b['btn'] ),
        'url' => get_theme_mod( 'sahel_slide' . $i . '_url', $b['url'] ),
        'align' => get_theme_mod( 'sahel_slide' . $i . '_align', 'right' ),
        'ovl_color' => get_theme_mod( 'sahel_slide' . $i . '_ovl_color', '#faf7f2' ),
        'ovl_opacity' => get_theme_mod( 'sahel_slide' . $i . '_ovl_opacity', 95 ),
    );
}
function sahel_font_map() { return array( 'vazirmatn' => 'Vazirmatn', 'tajawal' => 'Tajawal', 'cairo' => 'Cairo', 'readex' => 'Readex Pro', 'almarai' => 'Almarai' ); }
function sahel_font_family() {
    $f = get_theme_mod( 'sahel_font', 'vazirmatn' );
    $map = sahel_font_map();
    return isset( $map[ $f ] ) ? $map[ $f ] : 'Vazirmatn';
}
function sahel_heading_font_family() {
    $f = get_theme_mod( 'sahel_heading_font', '' );
    if ( ! $f ) { return sahel_font_family(); }
    $map = sahel_font_map();
    return isset( $map[ $f ] ) ? $map[ $f ] : 'Vazirmatn';
}
function sahel_header_font_family() {
    $f = get_theme_mod( 'sahel_header_font', '' );
    if ( ! $f ) { return sahel_font_family(); }
    $map = sahel_font_map();
    return isset( $map[ $f ] ) ? $map[ $f ] : 'Vazirmatn';
}

add_action( 'after_setup_theme', function() {
    add_theme_support( 'woocommerce' ); add_theme_support( 'wc-product-gallery-zoom' ); add_theme_support( 'wc-product-gallery-lightbox' ); add_theme_support( 'wc-product-gallery-slider' );
    add_theme_support( 'post-thumbnails' ); add_theme_support( 'title-tag' ); add_theme_support( 'custom-logo', array( 'height' => 64, 'width' => 140 ) );
    register_nav_menu( 'primary', 'منوی اصلی' );
} );

/* بعضی هاست‌ها یک کش سرور (Object Cache) دارن که با انتشار تنظیمات پاک نمی‌شه؛
   این باعث می‌شه سایت زنده مقادیر قدیمی تنظیمات (مثلاً آیتم‌های منو) رو نشون بده
   در حالی که پیش‌نمایش Customizer مقدار جدید رو می‌بینه. برای رفع این حالت،
   کش رو صریحاً موقع انتشار پاک می‌کنیم. */
function sahel_purge_all_caches() {
    if ( function_exists( 'wp_cache_flush' ) ) { wp_cache_flush(); }
    // LiteSpeed Cache - افزونه (اگه نصب باشه)
    do_action( 'litespeed_purge_all' );
    // LiteSpeed Cache - ماژول سطح سرور (بدون نیاز به افزونه؛ هاست‌هایی که وب‌سرورشون
    // LiteSpeed هست، کش صفحه رو با همین هدر پاک می‌کنن حتی اگه هیچ افزونه‌ای فعال نباشه)
    if ( ! headers_sent() ) {
        header( 'X-LiteSpeed-Purge: *' );
    }
    // WP Rocket
    if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
    // W3 Total Cache
    if ( function_exists( 'w3tc_flush_all' ) ) { w3tc_flush_all(); }
    // WP Super Cache
    if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); }
    // WP Fastest Cache
    if ( function_exists( 'wpfc_clear_all_cache' ) ) { wpfc_clear_all_cache(); }
    // SG Optimizer (سایت‌گراند)
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) { sg_cachepress_purge_cache(); }
    // Autoptimize / عمومی
    do_action( 'autoptimize_action_cachepurged' );
}
add_action( 'customize_save_after', function() {
    sahel_purge_all_caches();
    delete_transient( 'sahel_top_sale_id' );
} );
add_action( 'switch_theme', function() {
    sahel_purge_all_caches();
} );

/* صفحه‌ی تشخیصی موقت: مقدار واقعیِ همین‌الانِ تنظیمات رو مستقیم از پایگاه‌داده نشون می‌ده.
   چون این صفحه داخل پیش‌خوانه، هیچ‌وقت توسط کش سرور/صفحه ذخیره نمی‌شه؛ پس اگه بعد از
   زدن «انتشار» مقدار جدید اینجا اومد ولی توی سایت اصلی نیومد، یعنی مشکل از کشه نه ذخیره‌سازی. */
add_action( 'admin_menu', function() {
    add_management_page( 'بررسی زنده‌ی تنظیمات قالب', 'بررسی تنظیمات قالب', 'manage_options', 'sahel-live-check', 'sahel_live_check_page' );
} );
function sahel_live_check_page() {
    echo '<div class="wrap"><h1>بررسی زنده‌ی تنظیمات (بدون کش)</h1>';
    echo '<p>این صفحه همیشه مستقیم از پایگاه‌داده خونده می‌شه و هیچ‌وقت کش نمی‌شه. یه تغییر بده، «انتشار» بزن، بعد همین صفحه رو (F5) تازه‌سازی کن و ببین مقدار جدید اینجا اومده یا نه.</p>';
    echo '<p><b>زمان همین لحظه‌ی سرور:</b> ' . esc_html( current_time( 'Y-m-d H:i:s' ) ) . '</p>';
    echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>تنظیم</th><th>مقدار فعلی در پایگاه‌داده</th></tr></thead><tbody>';
    echo '<tr><td>نام برند</td><td>' . esc_html( get_theme_mod( 'sahel_brand', '(خالی)' ) ) . '</td></tr>';
    for ( $i = 1; $i <= 12; $i++ ) {
        $label = get_theme_mod( 'sahel_m' . $i . '_label', '' );
        if ( '' === $label ) { continue; }
        $type = get_theme_mod( 'sahel_m' . $i . '_type', '' );
        $val  = get_theme_mod( 'sahel_m' . $i . '_val', '' );
        echo '<tr><td>آیتم منوی قالب #' . (int) $i . '</td><td>عنوان: ' . esc_html( $label ) . '<br>نوع: <b>' . esc_html( $type ?: '(خالی!)' ) . '</b><br>مقدار: ' . esc_html( $val ?: '(خالی)' ) . '</td></tr>';
    }
    $locations = get_nav_menu_locations();
    $primary_name = '(هیچ منویی به موقعیت اصلی نسبت داده نشده)';
    if ( ! empty( $locations['primary'] ) ) {
        $menu_obj = wp_get_nav_menu_object( $locations['primary'] );
        if ( $menu_obj ) { $primary_name = $menu_obj->name; }
    }
    echo '<tr><td>منوی وردپرسی (موقعیت اصلی)</td><td>' . esc_html( $primary_name ) . '</td></tr>';
    echo '</tbody></table></div>';
}

add_action( 'wp_enqueue_scripts', function() {
    $map = array( 'vazirmatn' => 'vazirmatn', 'tajawal' => 'tajawal', 'cairo' => 'cairo', 'readex' => 'readex-pro', 'almarai' => 'almarai' );
    $f = get_theme_mod( 'sahel_font', 'vazirmatn' );
    $slug = isset( $map[ $f ] ) ? $map[ $f ] : 'vazirmatn';
    wp_enqueue_style( 'sahel-font', 'https://cdn.jsdelivr.net/fontsource/css/' . $slug . '@latest/index.css', array(), '1.0' );
    wp_enqueue_style( 'sahel-font-700', 'https://cdn.jsdelivr.net/fontsource/css/' . $slug . '@latest/700.css', array(), '1.0' );
    $hf = get_theme_mod( 'sahel_heading_font', '' );
    if ( $hf && $hf !== $f && isset( $map[ $hf ] ) ) {
        wp_enqueue_style( 'sahel-font-head', 'https://cdn.jsdelivr.net/fontsource/css/' . $map[ $hf ] . '@latest/700.css', array(), '1.0' );
    }
    if ( function_exists( 'WC' ) ) { wp_enqueue_script( 'wc-add-to-cart' ); wp_enqueue_script( 'wc-cart-fragments' ); }
    /* sahel_engine() renders most page types itself with its own inline script (see sahel_shell());
       this file backs the classic template fallback (admin preview / Elementor preview / search
       results) so it must not load on engine-rendered pages, or click handlers would double-fire. */
    if ( is_admin() || isset( $_GET['elementor-preview'] ) || is_search() ) {
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'sahel-scripts', get_stylesheet_directory_uri() . '/assets/js/scripts.js', array( 'jquery' ), '1.0', true );
    }
}, 5 );
add_action( 'wp_enqueue_scripts', function() {
    if ( function_exists( 'is_product' ) && is_product() ) {
        wp_enqueue_script( 'wc-add-to-cart-variation' );
        wp_enqueue_script( 'wc-single-product' );
    }
}, 20 );

add_filter( 'pre_option_show_on_front', function( $v ) { return is_admin() ? $v : 'posts'; } );
add_filter( 'woocommerce_price_decimals', function() { return 0; }, 99 );
add_filter( 'pre_option_woocommerce_price_thousand_sep', function() { return ','; } );
add_filter( 'woocommerce_add_to_cart_fragments', function( $f ) { ob_start(); ?><span id="cartCount"><?php echo sahel_cart_count(); ?></span><?php $f['#cartCount'] = ob_get_clean(); return $f; } );
add_filter( 'woocommerce_get_price_html', 'sahel_fa_safe' );
add_filter( 'woocommerce_cart_item_price', 'sahel_fa_safe' );
add_filter( 'woocommerce_cart_item_subtotal', 'sahel_fa_safe' );
add_action( 'elementor/theme/register_locations', function( $m ) { $m->register_all_core_location(); } );
add_filter( 'loop_shop_per_page', function() { return (int) get_theme_mod( 'sahel_shop_per_page', 15 ); }, 99 );
add_filter( 'woocommerce_product_query', function( $q ) {
    if ( ! is_admin() && isset( $_GET['mstock'] ) && $_GET['mstock'] === 'in' ) { $q->set( 'stock_status', array( 'instock' ) ); }
} );

/* جستجوی زنده */
add_action( 'wp_ajax_sahel_live_search', 'sahel_live_search' );
add_action( 'wp_ajax_nopriv_sahel_live_search', 'sahel_live_search' );
function sahel_live_search() {
    $q = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
    $out = array();
    if ( $q && function_exists( 'wc_get_products' ) ) {
        $ids = wc_get_products( array( 's' => $q, 'limit' => 6, 'status' => 'publish', 'return' => 'ids' ) );
        foreach ( (array) $ids as $id ) {
            $p = wc_get_product( $id ); if ( ! $p ) { continue; }
            $img = wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_gallery_thumbnail' );
            $out[] = array(
                'link' => $p->get_permalink(),
                'img' => $img ? $img : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
                'title' => $p->get_name(),
                'price' => sahel_fa_safe( wp_strip_all_tags( $p->get_price_html() ) ),
            );
        }
    }
    wp_send_json( $out );
}

/* باشگاه مشتریان */
add_action( 'wp_ajax_sahel_newsletter', 'sahel_newsletter' );
add_action( 'wp_ajax_nopriv_sahel_newsletter', 'sahel_newsletter' );
function sahel_newsletter() {
    $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
    $phone = preg_replace( '/[^0-9]/', '', strtr( $phone, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' ) ) );
    if ( strlen( $phone ) < 10 || strlen( $phone ) > 15 ) { wp_send_json( array( 'ok' => 0, 'msg' => 'شماره موبایل معتبر نیست' ) ); }
    $m = get_option( 'sahel_club_members', array() );
    if ( ! in_array( $phone, $m, true ) ) {
        $m[] = $phone; update_option( 'sahel_club_members', $m );
        wp_send_json( array( 'ok' => 1, 'msg' => 'به باشگاه مشتریان ' . sahel_brand() . ' خوش آمدی 🤎' ) );
    }
    wp_send_json( array( 'ok' => 1, 'msg' => 'شما قبلاً در باشگاه مشتریان عضو شده‌اید ✔' ) );
}
add_action( 'admin_menu', function() {
    add_menu_page( 'باشگاه مشتریان', 'باشگاه مشتریان', 'manage_options', 'sahel-club', 'sahel_club_page', 'dashicons-groups', 56 );
} );
function sahel_club_page() {
    $m = get_option( 'sahel_club_members', array() );
    echo '<div class="wrap"><h1>باشگاه مشتریان ' . esc_html( sahel_brand() ) . '</h1>';
    echo '<p>تعداد اعضا: <b>' . count( $m ) . '</b></p>';
    if ( $m ) {
        echo '<table class="widefat" style="max-width:520px"><thead><tr><th>#</th><th>شماره موبایل</th></tr></thead><tbody>';
        foreach ( $m as $i => $p ) { echo '<tr><td>' . ( $i + 1 ) . '</td><td>' . esc_html( $p ) . '</td></tr>'; }
        echo '</tbody></table>';
    } else { echo '<p>هنوز عضوی ثبت نشده است.</p>'; }
    echo '</div>';
}

/* صفحات خودکار */
add_action( 'init', function() {
    if ( get_option( 'sahel_pages_v1' ) ) { return; }
    $about = '<h2>درباره ' . sahel_brand() . '</h2><p>' . sahel_brand() . ' با یک باور ساده متولد شد: هر مشتری لیاقت تجربه‌ی خرید آسان و محصولاتی باکیفیت را دارد. این متن را از پیشخوان وردپرس → صفحات ویرایش کنید.</p>';
    $contact = '<h2>راه‌های ارتباط با ' . sahel_brand() . '</h2><p>📍 ' . get_theme_mod( 'sahel_address', 'آدرس فروشگاه خود را از تنظیمات نمایش وارد کنید' ) . '</p>';
    $pages = array(
        array( 'about-us', 'درباره ما', $about ),
        array( 'contact-us', 'تماس با ما', $contact ),
        array( 'blog', 'مقالات', '' ),
    );
    foreach ( $pages as $pg ) {
        if ( ! get_page_by_path( $pg[0] ) ) {
            wp_insert_post( array( 'post_title' => $pg[1], 'post_name' => $pg[0], 'post_content' => $pg[2], 'post_status' => 'publish', 'post_type' => 'page' ) );
        }
    }
    update_option( 'sahel_pages_v1', 1 );
}, 8 );

/* تعریف بخش‌های صفحه اصلی */
function sahel_home_sec_defs() {
    $b = sahel_brand();
    return array(
        'stats'    => array( 'نوار آمار و اعداد', '', '', 1 ),
        'marquee'  => array( 'نوار متحرک', '', '', 2 ),
        'cats'     => array( 'دسته‌بندی‌ها', 'دسته‌بندی‌های ' . $b, 'هر آنچه نیاز داری، یک‌جا', 3 ),
        'offer'    => array( 'پیشنهاد ویژه', 'پیشنهاد ویژه هفته', '', 4 ),
        'new'      => array( 'جدیدترین محصولات', 'جدیدترین محصولات', 'تازه‌های کالکشن', 5 ),
        'sale'     => array( 'محصولات تخفیف‌دار', 'لباس‌های تخفیف‌دار', 'فرصت‌های ویژه با تخفیف واقعی', 6 ),
        'best'     => array( 'پرفروش‌ترین‌ها', 'پرفروش‌ترین‌ها', 'انتخاب مشتری‌های ما', 7 ),
        'brands'   => array( 'برندها', 'برندهای محبوب', 'افتخار همکاری با برترین برندها', 8 ),
        'features' => array( 'مزایا و اعتمادسازی', '', '', 9 ),
        'blog'     => array( 'مقالات', 'مقالات', 'راهنمای انتخاب و ست‌کردن', 10 ),
        'about'    => array( 'درباره ما', 'درباره ما', '', 11 ),
        'news'     => array( 'باشگاه مشتریان (خبرنامه)', '', '', 12 ),
        'campaign' => array( 'بنر کمپین', 'کالکشن جدید رسید! ✨', 'تازه‌ترین طراحی‌ها با پارچه‌های خنک', 13 ),
        'look'     => array( 'گالری استایل', 'گالری استایل', 'ایده‌هایی برای ست‌کردن', 14 ),
        'testi'    => array( 'نظرات مشتریان', 'نظر مشتری‌های ما', 'تجربه واقعی خرید از ' . $b, 15 ),
        'faq'      => array( 'سؤالات متداول', 'سؤالات متداول', 'پاسخ سریع به سؤال‌های شما', 16 ),
        'insta'    => array( 'دعوت اینستاگرام', '', '', 17 ),
    );
}
function sahel_sec( $key ) {
    $defs = sahel_home_sec_defs();
    $d = isset( $defs[ $key ] ) ? $defs[ $key ] : array( '', '', '', 99 );
    return array(
        'on'         => (int) get_theme_mod( 'sahel_sec_' . $key . '_on', 1 ),
        'title'      => get_theme_mod( 'sahel_sec_' . $key . '_title', $d[1] ),
        'sub'        => get_theme_mod( 'sahel_sec_' . $key . '_sub', $d[2] ),
        'full'       => (int) get_theme_mod( 'sahel_sec_' . $key . '_full', 0 ),
        'order'      => (int) get_theme_mod( 'sahel_sec_' . $key . '_order', isset( $d[3] ) ? $d[3] : 99 ),
        'align'      => get_theme_mod( 'sahel_sec_' . $key . '_align', 'right' ),
        'bg_img'     => get_theme_mod( 'sahel_sec_' . $key . '_bg_img', '' ),
        'bg_color'   => get_theme_mod( 'sahel_sec_' . $key . '_bg_color', '' ),
        'bg_grad'    => get_theme_mod( 'sahel_sec_' . $key . '_bg_grad', '' ),
        'grad_c1'    => get_theme_mod( 'sahel_sec_' . $key . '_grad_c1', '' ),
        'grad_c2'    => get_theme_mod( 'sahel_sec_' . $key . '_grad_c2', '' ),
        'bg_opacity' => (int) get_theme_mod( 'sahel_sec_' . $key . '_bg_opacity', 100 ),
        'btn_color'  => get_theme_mod( 'sahel_sec_' . $key . '_btn_color', '' ),
        'btn_text'   => get_theme_mod( 'sahel_sec_' . $key . '_btn_text', '' ),
        'title_size' => (int) get_theme_mod( 'sahel_sec_' . $key . '_title_size', 0 ),
        'sub_size'   => (int) get_theme_mod( 'sahel_sec_' . $key . '_sub_size', 0 ),
        'padding_x'  => (int) get_theme_mod( 'sahel_sec_' . $key . '_padding_x', 0 ),
        'title_color' => get_theme_mod( 'sahel_sec_' . $key . '_title_color', '' ),
        'text_color'  => get_theme_mod( 'sahel_sec_' . $key . '_text_color', '' ),
        'hover_color' => get_theme_mod( 'sahel_sec_' . $key . '_hover_color', '' ),
        'height'      => (int) get_theme_mod( 'sahel_sec_' . $key . '_height', 0 ),
        'height_m'    => (int) get_theme_mod( 'sahel_sec_' . $key . '_height_m', 0 ),
        'padding_x_m' => (int) get_theme_mod( 'sahel_sec_' . $key . '_padding_x_m', 0 ),
        'hide_mobile'  => (int) get_theme_mod( 'sahel_sec_' . $key . '_hide_mobile', 0 ),
        'hide_tablet'  => (int) get_theme_mod( 'sahel_sec_' . $key . '_hide_tablet', 0 ),
        'hide_desktop' => (int) get_theme_mod( 'sahel_sec_' . $key . '_hide_desktop', 0 ),
    );
}
function sahel_sec_head_html( $title, $sub, $extra = '' ) {
    return '<div class="sec-head rv"><div><h2><span>' . esc_html( $title ) . '</span></h2>' . ( $sub ? '<p>' . esc_html( $sub ) . '</p>' : '' ) . '</div>' . $extra . '</div>';
}
function sahel_sec_bg_attr( $sec ) {
    $style = array();
    $overlay = '';
    if ( $sec['bg_img'] ) {
        $opacity = $sec['bg_opacity'] / 100;
        $overlay = '<div class="sec-bg-img" style="opacity:' . $opacity . ';background-image:url(' . esc_url( $sec['bg_img'] ) . ')"></div>';
    }
    if ( $sec['bg_color'] ) {
        if ( $sec['bg_opacity'] < 100 ) {
            $style[] = 'background-color:rgba(' . sahel_rgb_str( $sec['bg_color'] ) . ',' . ( $sec['bg_opacity'] / 100 ) . ')';
        } else {
            $style[] = 'background-color:' . esc_attr( $sec['bg_color'] );
        }
    }
    if ( ! empty( $sec['grad_c1'] ) && ! empty( $sec['grad_c2'] ) ) {
        $style[] = 'background-image:linear-gradient(135deg,' . esc_attr( $sec['grad_c1'] ) . ',' . esc_attr( $sec['grad_c2'] ) . ')';
    } elseif ( $sec['bg_grad'] ) {
        $style[] = 'background-image:' . $sec['bg_grad'];
    }
    if ( ! empty( $sec['title_size'] ) ) { $style[] = '--sec-title-fs:' . (int) $sec['title_size'] . 'px'; }
    if ( ! empty( $sec['sub_size'] ) ) { $style[] = '--sec-sub-fs:' . (int) $sec['sub_size'] . 'px'; }
    if ( ! empty( $sec['padding_x'] ) ) { $style[] = '--sec-px:' . (int) $sec['padding_x'] . 'px'; }
    if ( ! empty( $sec['title_color'] ) ) { $style[] = '--sec-title-color:' . esc_attr( $sec['title_color'] ); }
    if ( ! empty( $sec['text_color'] ) ) { $style[] = '--sec-text-color:' . esc_attr( $sec['text_color'] ); }
    if ( ! empty( $sec['hover_color'] ) ) { $style[] = '--sec-hover-color:' . esc_attr( $sec['hover_color'] ); }
    if ( ! empty( $sec['height'] ) ) { $style[] = '--sec-min-h:' . (int) $sec['height'] . 'px'; }
    if ( ! empty( $sec['height_m'] ) ) { $style[] = '--sec-min-h-m:' . (int) $sec['height_m'] . 'px'; }
    if ( ! empty( $sec['padding_x_m'] ) ) { $style[] = '--sec-px-m:' . (int) $sec['padding_x_m'] . 'px'; }
    $class = 'sec-al-' . ( $sec['align'] ? $sec['align'] : 'right' );
    if ( ! empty( $sec['hide_mobile'] ) ) { $class .= ' hide-m'; }
    if ( ! empty( $sec['hide_tablet'] ) ) { $class .= ' hide-t'; }
    if ( ! empty( $sec['hide_desktop'] ) ) { $class .= ' hide-d'; }
    return array( 'class' => $class, 'style' => !empty($style) ? ' style="' . implode(';', $style) . '"' : '', 'overlay' => $overlay );
}

/* پنل سفارشی‌سازی - مرتب‌شده */
add_action( 'customize_register', function( $w ) {
    /* ===== هویت برند ===== */
    $w->add_section( 'sahel_brand', array( 'title' => '۱. هویت برند', 'priority' => 29 ) );
    $w->add_setting( 'sahel_brand', array( 'default' => 'فروشگاه شما', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_brand', array( 'label' => 'نام برند', 'section' => 'sahel_brand' ) );
    $w->add_setting( 'sahel_brand_short', array( 'default' => 'فروشگاه', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_brand_short', array( 'label' => 'نام کوتاه برند', 'section' => 'sahel_brand' ) );
    $w->add_setting( 'sahel_brand_en', array( 'default' => 'YOUR STORE', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_brand_en', array( 'label' => 'نام انگلیسی برند', 'section' => 'sahel_brand' ) );

    /* ===== هدر و منو ===== */
    $w->add_section( 'sahel_header', array( 'title' => '۲. هدر و منو', 'priority' => 29.5 ) );
    
    /* v4.5: مدل هدر */
    $w->add_setting( 'sahel_header_style', array( 'default' => 'classic', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_header_style', array(
        'label' => '🎨 مدل هدر',
        'section' => 'sahel_header',
        'type' => 'select',
        'choices' => array(
            'classic' => 'کلاسیک (لوگو چپ، منو وسط، دکمه‌ها راست)',
            'modern' => 'مدرن (لوگو وسط، منو پایین)',
            'minimal' => 'مینیمال (لوگو چپ، منو راست)',
            'glass' => 'شیشه‌ای (مات و شفاف)',
            'compact' => 'فشرده (کوچک و جمع‌وجور)',
            'bold' => '🟧 پررنگ (نوار گرادیانی برند)',
            'underline' => '➖ زیرخط متحرک (مینیمال ادیتوریال)',
            'border' => '⬛ قاب‌دار (خط پررنگ پایین هدر)',
            'sidebar' => '☰ منوی کشویی (حتی در دسکتاپ)',
            'boxed' => '🔲 شناور جعبه‌ای (فاصله از لبه‌ها)'
        )
    ) );
    $w->add_setting( 'sahel_topbar_on', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_topbar_on', array( 'label' => '✔ نمایش نوار اطلاعیه بالای هدر', 'section' => 'sahel_header', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_topbar_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_topbar_text', array( 'label' => 'متن نوار اطلاعیه (مثلاً ارسال رایگان بالای ...)', 'section' => 'sahel_header' ) );
    $w->add_setting( 'sahel_topbar_bg', array( 'default' => '#1c1c1c', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_topbar_bg', array( 'label' => 'رنگ پس‌زمینه نوار اطلاعیه', 'section' => 'sahel_header' ) ) );
    $w->add_setting( 'sahel_topbar_color', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_topbar_color', array( 'label' => 'رنگ متن نوار اطلاعیه', 'section' => 'sahel_header' ) ) );
    $w->add_setting( 'sahel_topbar_hide_mobile', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_topbar_hide_mobile', array( 'label' => '🚫 پنهان در موبایل', 'section' => 'sahel_header', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_topbar_hide_tablet', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_topbar_hide_tablet', array( 'label' => '🚫 پنهان در تبلت', 'section' => 'sahel_header', 'type' => 'checkbox' ) );

    $fonts = array( 'vazirmatn' => 'وزیرمتن', 'tajawal' => 'تجوال', 'cairo' => 'قاهره', 'readex' => 'ریدکس پرو', 'almarai' => 'المرای' );
    $w->add_setting( 'sahel_header_font', array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_header_font', array( 'label' => 'فونت هدر', 'section' => 'sahel_header', 'type' => 'select', 'choices' => array( '' => 'پیش‌فرض' ) + $fonts ) );
    
    /* v4.5: رنگ فونت هدر */
    $w->add_setting( 'sahel_header_text_color', array( 'default' => '#33261c', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_header_text_color', array( 'label' => '🎨 رنگ متن منو', 'section' => 'sahel_header' ) ) );
    
    $w->add_setting( 'sahel_header_btn_color', array( 'default' => '#b08968', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_header_btn_color', array( 'label' => '🎨 رنگ پس‌زمینه دکمه‌ها', 'section' => 'sahel_header' ) ) );
    
    $w->add_setting( 'sahel_header_btn_text', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_header_btn_text', array( 'label' => '🎨 رنگ متن دکمه‌ها', 'section' => 'sahel_header' ) ) );

    $w->add_setting( 'sahel_header_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_header_image', array( 'label' => '🖼 تصویر پس‌زمینه هدر', 'section' => 'sahel_header' ) ) );
    $w->add_setting( 'sahel_header_image_opacity', array( 'default' => 30, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_header_image_opacity', array( 'label' => 'شدت تصویر پس‌زمینه هدر (٪)', 'section' => 'sahel_header', 'type' => 'number', 'input_attrs' => array( 'min' => 5, 'max' => 100 ) ) );
    $w->add_setting( 'sahel_headerbg', array( 'default' => '#faf7f2', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_headerbg', array( 'label' => '🎨 پس‌زمینه هدر', 'section' => 'sahel_header' ) ) );
    $w->add_setting( 'sahel_header_opacity', array( 'default' => 82, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_header_opacity', array( 'label' => 'شفافیت هدر (٪)', 'section' => 'sahel_header', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 100 ) ) );

    /* ===== آیتم‌های منو ===== */
    $w->add_section( 'sahel_menu', array( 'title' => '۳. آیتم‌های منو', 'description' => 'حداکثر ۱۲ آیتم. آیتم خالی = نمایش داده نمی‌شود', 'priority' => 29.6 ) );
    $menu_defaults = array(
        1 => array( 'صفحه اصلی', 'home', '' ),
        2 => array( 'فروشگاه', 'cats', '' ),
        3 => array( 'مقالات', 'page', 'blog' ),
        4 => array( 'درباره ما', 'page', 'about-us' ),
        5 => array( 'تماس با ما', 'page', 'contact-us' ),
    );
    $menu_types = array( '' => '— نوع آیتم —', 'home' => 'صفحه اصلی', 'url' => 'لینک دلخواه', 'page' => 'صفحه وردپرس', 'cats' => 'دسته‌بندی‌های محصول', 'shop' => 'صفحه فروشگاه' );
    for ( $i = 1; $i <= 12; $i++ ) {
        $def = isset( $menu_defaults[ $i ] ) ? $menu_defaults[ $i ] : array( '', 'page', '' );
        $w->add_setting( 'sahel_m' . $i . '_label', array( 'default' => $def[0], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_m' . $i . '_label', array( 'label' => '📝 عنوان آیتم ' . sahel_fa( $i ), 'section' => 'sahel_menu' ) );
        $w->add_setting( 'sahel_m' . $i . '_type', array( 'default' => $def[1], 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_m' . $i . '_type', array( 'label' => 'نوع آیتم ' . sahel_fa( $i ), 'section' => 'sahel_menu', 'type' => 'select', 'choices' => $menu_types ) );
        $w->add_setting( 'sahel_m' . $i . '_val', array( 'default' => $def[2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_m' . $i . '_val', array( 'label' => 'مقدار آیتم ' . sahel_fa( $i ), 'section' => 'sahel_menu' ) );
    }
    /* ===== اسلایدر ===== */
    $w->add_section( 'sahel_slider', array( 'title' => '۴. اسلایدر', 'priority' => 30 ) );
    $w->add_setting( 'sahel_slide_count', array( 'default' => 3, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_count', array( 'label' => 'تعداد اسلایدها', 'section' => 'sahel_slider', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 10 ) ) );
    $w->add_setting( 'sahel_slide_height', array( 'default' => 620, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_height', array( 'label' => 'ارتفاع اسلاید دسکتاپ', 'section' => 'sahel_slider', 'type' => 'number' ) );
    $w->add_setting( 'sahel_slide_height_m', array( 'default' => 480, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_height_m', array( 'label' => 'ارتفاع اسلاید موبایل', 'section' => 'sahel_slider', 'type' => 'number' ) );
    $w->add_setting( 'sahel_slide_height_t', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_height_t', array( 'label' => 'ارتفاع اسلاید تبلت — ۰ یعنی مثل موبایل', 'section' => 'sahel_slider', 'type' => 'number' ) );
    $w->add_setting( 'sahel_slider_hide_mobile', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_hide_mobile', array( 'label' => '🚫 پنهان در موبایل', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_slider_hide_tablet', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_hide_tablet', array( 'label' => '🚫 پنهان در تبلت', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_slider_hide_desktop', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_hide_desktop', array( 'label' => '🚫 پنهان در دسکتاپ', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_slider_full', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_full', array( 'label' => '↔ اسلایدر تمام‌عرض', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_slider_width', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_width', array( 'label' => 'عرض دستی اسلایدر (px) — خالی/۰ یعنی خودکار', 'section' => 'sahel_slider', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 3000 ) ) );
    $w->add_setting( 'sahel_slider_on', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_on', array( 'label' => 'نمایش اسلایدر', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_slider_style', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_slider_style', array( 'label' => '🎨 قالب اسلایدر/هیرو', 'section' => 'sahel_slider', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک (تصویر تمام‌عرض + متن روی آن)',
        '2' => 'دوستونه (تصویر یک‌طرف، متن طرف دیگر)',
        '3' => 'متن‌محور وسط‌چین (بدون تصویر، مناسب پس‌زمینه رنگی)',
        '4' => 'بنر جمع‌وجور (ارتفاع کم، شبیه کارت تبلیغاتی)',
    ) ) );

    $slide_max = min( 10, max( 1, (int) get_theme_mod( 'sahel_slide_count', 3 ) ) + 1 );
    for ( $i = 1; $i <= $slide_max; $i++ ) {
        $w->add_section( 'sahel_slide' . $i, array( 'title' => 'اسلاید ' . sahel_fa( $i ), 'priority' => 30 ) );
        $w->add_setting( 'sahel_slide' . $i . '_bgtype', array( 'default' => 'image', 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_slide' . $i . '_bgtype', array( 'label' => 'نوع پس‌زمینه', 'section' => 'sahel_slide' . $i, 'type' => 'select', 'choices' => array( 'image' => 'تصویر', 'color' => 'رنگ ساده', 'gradient' => 'گرادیانت' ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_slide' . $i . '_image', array( 'label' => 'تصویر اسلاید (وقتی نوع پس‌زمینه = تصویر)', 'section' => 'sahel_slide' . $i ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_bgcolor1', array( 'default' => '#f3ede4', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_slide' . $i . '_bgcolor1', array( 'label' => 'رنگ پس‌زمینه (یا رنگ اول گرادیانت)', 'section' => 'sahel_slide' . $i ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_bgcolor2', array( 'default' => '#d9c8b4', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_slide' . $i . '_bgcolor2', array( 'label' => 'رنگ دوم گرادیانت', 'section' => 'sahel_slide' . $i ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_align', array( 'default' => 'right', 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_slide' . $i . '_align', array( 'label' => 'محل متن', 'section' => 'sahel_slide' . $i, 'type' => 'select', 'choices' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_ovl_color', array( 'default' => '#faf7f2', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_slide' . $i . '_ovl_color', array( 'label' => 'رنگ روکش', 'section' => 'sahel_slide' . $i ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_ovl_opacity', array( 'default' => 95, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_slide' . $i . '_ovl_opacity', array( 'label' => 'شدت روکش (۰-۱۰۰)', 'section' => 'sahel_slide' . $i, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 100 ) ) );
        $w->add_setting( 'sahel_slide' . $i . '_pill', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_slide' . $i . '_pill', array( 'label' => 'برچسب', 'section' => 'sahel_slide' . $i ) );
        $w->add_setting( 'sahel_slide' . $i . '_t1', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_slide' . $i . '_t1', array( 'label' => 'خط اول تیتر', 'section' => 'sahel_slide' . $i ) );
        $w->add_setting( 'sahel_slide' . $i . '_t2', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_slide' . $i . '_t2', array( 'label' => 'خط دوم تیتر', 'section' => 'sahel_slide' . $i ) );
        $w->add_setting( 'sahel_slide' . $i . '_desc', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_slide' . $i . '_desc', array( 'label' => 'توضیح', 'section' => 'sahel_slide' . $i ) );
        $w->add_setting( 'sahel_slide' . $i . '_btn', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_slide' . $i . '_btn', array( 'label' => 'متن دکمه', 'section' => 'sahel_slide' . $i ) );
        $w->add_setting( 'sahel_slide' . $i . '_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( 'sahel_slide' . $i . '_url', array( 'label' => 'لینک دکمه', 'section' => 'sahel_slide' . $i ) );
    }

    /* ===== استایل‌ساز ===== */
    $w->add_section( 'sahel_styles', array( 'title' => '۵. استایل‌ساز', 'priority' => 31 ) );
    $w->add_setting( 'sahel_btn_style', array( 'default' => 'pill', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_btn_style', array( 'label' => 'طراحی دکمه‌ها', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array( 'pill' => 'پیل', 'round' => 'گوشه‌گرد', 'square' => 'مربع', 'outline' => 'دورخط', 'soft' => 'نرم', 'glass' => 'شیشه‌ای', 'neon' => 'نئون' ) ) );
    $w->add_setting( 'sahel_catcard', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_catcard', array( 'label' => 'کارت دسته‌بندی', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک', '2' => 'متن روی تصویر', '3' => 'افقی', '4' => 'دایره‌ای', '5' => 'شیشه‌ای', '6' => 'مینیمال',
        '7' => '✨ گلس‌مورفیسم', '8' => '💫 حلقه‌ای گرادیانی', '9' => '🎯 سه‌بعدی Tilt', '10' => '📐 بنری عریض',
        '11' => '🎨 مسطح رنگی', '12' => '➖ خط‌دور مینیمال', '13' => '🔢 نشان شمارش شناور' ) ) );
    $w->add_setting( 'sahel_prodcard', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_prodcard', array( 'label' => 'کارت محصول', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک', '2' => 'مینیمال', '3' => 'خط رنگی', '4' => 'شیشه‌ای تیره', '5' => 'شیشه‌ای روشن', '6' => 'نئون', '7' => 'مجله‌ای',
        '8' => '✨ شیشه‌ای مدرن', '9' => '🌙 نئون درخشان', '10' => '⚡ درخشش عبوری', '11' => '🎯 سه‌بعدی Tilt',
        '12' => '🛒 سبد شناور گوشه‌ای', '13' => '🎨 مسطح مدرن', '14' => '🏷 برچسب گوشه‌بریده',
        '15' => '🔵 مینیمال با بج رنگ‌بندی و دکمه دایره‌ای' ) ) );
    $w->add_setting( 'sahel_postcard', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_postcard', array( 'label' => 'کارت مقاله', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک', '2' => 'مینیمال بدون قاب', '3' => 'متن روی تصویر', '4' => 'افقی',
        '5' => '⭕ مینیمال دایره‌ای (لیست ادیتوریال)', '6' => '🎨 گرادیانت مدرن' ) ) );
    $w->add_setting( 'sahel_dd_style', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_dd_style', array( 'label' => 'دراپ‌داون دسته‌بندی در هدر', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کارتی جمع‌وجور (پیش‌فرض)', '2' => 'ساده فهرستی (تک‌ستون)', '5' => 'ساده فهرستی (سه‌ستون)', '3' => 'شیشه‌ای شناور', '4' => 'گرید ریز دایره‌ای' ) ) );
    $w->add_setting( 'sahel_cat_gap', array( 'default' => 14, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_cat_gap', array( 'label' => 'فاصله بین کارت‌های دسته‌بندی (px)', 'section' => 'sahel_styles', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 60 ) ) );
    $w->add_setting( 'sahel_prod_gap', array( 'default' => 14, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_prod_gap', array( 'label' => 'فاصله بین کارت‌های محصول (px)', 'section' => 'sahel_styles', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 60 ) ) );
    $w->add_setting( 'sahel_post_gap', array( 'default' => 18, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_post_gap', array( 'label' => 'فاصله بین کارت‌های مقاله (px)', 'section' => 'sahel_styles', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 60 ) ) );

    /* ===== موبایل ===== */
    $w->add_section( 'sahel_mobile', array( 'title' => '۶. موبایل', 'priority' => 32 ) );
    $w->add_setting( 'sahel_m_h1', array( 'default' => 26, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_m_h1', array( 'label' => 'اندازه تیتر', 'section' => 'sahel_mobile', 'type' => 'number' ) );
    $w->add_setting( 'sahel_m_p', array( 'default' => 13, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_m_p', array( 'label' => 'اندازه توضیح', 'section' => 'sahel_mobile', 'type' => 'number' ) );
    $w->add_setting( 'sahel_m_hide_desc', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_m_hide_desc', array( 'label' => 'پنهان‌کردن توضیح', 'section' => 'sahel_mobile', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_m_overlay', array( 'default' => 92, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_m_overlay', array( 'label' => 'شدت روکش (٪)', 'section' => 'sahel_mobile', 'type' => 'number' ) );

    /* ===== آمار ===== */
    $w->add_setting( 'sahel_stat1', array( 'default' => '8000', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat1', array( 'label' => 'عدد ۱', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat1_label', array( 'default' => 'مشتری خوشحال', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat1_label', array( 'label' => 'متن ۱', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat2', array( 'default' => '450', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat2', array( 'label' => 'عدد ۲', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat2_label', array( 'default' => 'محصول متنوع', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat2_label', array( 'label' => 'متن ۲', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat3', array( 'default' => '98', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat3', array( 'label' => 'عدد ۳ (٪)', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat3_label', array( 'default' => 'رضایت خرید', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat3_label', array( 'label' => 'متن ۳', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat4_text', array( 'default' => 'سراسر کشور', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat4_text', array( 'label' => 'متن ۴', 'section' => 'sahel_secgrp_stats' ) );
    $w->add_setting( 'sahel_stat4_label', array( 'default' => 'ارسال سریع', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat4_label', array( 'label' => 'متن ۴', 'section' => 'sahel_secgrp_stats' ) );

    /* ===== رنگ‌ها و فونت ===== */
    $w->add_section( 'sahel_colors', array( 'title' => '۷. رنگ‌ها و فونت', 'priority' => 34 ) );
    $cols = array(
        'sahel_c1' => array( 'رنگ اصلی', '#b08968' ),
        'sahel_c2' => array( 'رنگ ثانویه', '#d4a373' ),
        'sahel_hot' => array( 'رنگ تأکیدی', '#c65a3f' ),
        'sahel_bg' => array( 'پس‌زمینه سایت', '#faf7f2' ),
        'sahel_ink' => array( 'رنگ متن', '#33261c' ),
        'sahel_muted' => array( 'رنگ کم‌رنگ', '#8b7a6b' ),
        'sahel_footerbg' => array( 'پس‌زمینه فوتر', '#241a12' ),
        'sahel_price_color' => array( 'رنگ قیمت', '#c65a3f' ),
        'sahel_btn_text' => array( 'رنگ متن دکمه‌ها', '#ffffff' ),
        'sahel_carticon_color' => array( 'رنگ آیکن سبد', '#b08968' ),
        'sahel_carticon_hover' => array( 'رنگ آیکن سبد هاور', '#ffffff' ),
        'sahel_card_bg' => array( 'پس‌زمینه کارت‌ها', '#ffffff' ),
        'sahel_footer_text' => array( 'رنگ متن فوتر', '#e7cfb8' ),
    );
    foreach ( $cols as $k => $c ) {
        $w->add_setting( $k, array( 'default' => $c[1], 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, $k, array( 'label' => $c[0], 'section' => 'sahel_colors' ) ) );
    }
    $w->add_setting( 'sahel_font', array( 'default' => 'vazirmatn', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_font', array( 'label' => 'فونت بدنه', 'section' => 'sahel_colors', 'type' => 'select', 'choices' => $fonts ) );
    $w->add_setting( 'sahel_heading_font', array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_heading_font', array( 'label' => 'فونت تیترها', 'section' => 'sahel_colors', 'type' => 'select', 'choices' => array( '' => 'پیش‌فرض' ) + $fonts ) );
    $w->add_setting( 'sahel_body_size', array( 'default' => 16, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_body_size', array( 'label' => 'اندازه فونت', 'section' => 'sahel_colors', 'type' => 'number' ) );

    /* ===== فروشگاه ===== */
    $w->add_section( 'sahel_shop', array( 'title' => '۸. فروشگاه', 'priority' => 35 ) );
    $w->add_setting( 'sahel_shop_banner', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_shop_banner', array( 'label' => 'تصویر بنر', 'section' => 'sahel_shop' ) ) );
    $w->add_setting( 'sahel_shop_title', array( 'default' => 'فروشگاه ' . sahel_brand(), 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_shop_title', array( 'label' => 'تیتر بنر', 'section' => 'sahel_shop' ) );
    $w->add_setting( 'sahel_shop_sub', array( 'default' => 'استایل تو، امضای تو', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_shop_sub', array( 'label' => 'زیرتیتر', 'section' => 'sahel_shop' ) );
    $w->add_setting( 'sahel_shop_filters', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_shop_filters', array( 'label' => 'نمایش فیلترها', 'section' => 'sahel_shop', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_shop_stock', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_shop_stock', array( 'label' => 'فیلتر موجودی', 'section' => 'sahel_shop', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_shop_per_page', array( 'default' => 15, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_shop_per_page', array( 'label' => 'تعداد در صفحه', 'section' => 'sahel_shop', 'type' => 'number' ) );

    /* ===== محصول ===== */
    $w->add_section( 'sahel_product', array( 'title' => '۹. محصول', 'priority' => 36 ) );
    $w->add_setting( 'sahel_product_trust', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_trust', array( 'label' => 'نوار اعتماد', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_tabs', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_tabs', array( 'label' => 'تب‌ها', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_related', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_related', array( 'label' => 'محصولات مرتبط', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_fullwidth', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_fullwidth', array( 'label' => 'تمام‌عرض', 'section' => 'sahel_product', 'type' => 'checkbox' ) );

    /* ===== صفحات ===== */
    $w->add_section( 'sahel_pages', array( 'title' => '۱۰. صفحات', 'priority' => 37 ) );
    $w->add_setting( 'sahel_about_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_about_image', array( 'label' => 'تصویر درباره ما', 'section' => 'sahel_pages' ) ) );
    $w->add_setting( 'sahel_contact_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_contact_image', array( 'label' => 'تصویر تماس با ما', 'section' => 'sahel_pages' ) ) );
    $w->add_setting( 'sahel_contact_map', array( 'default' => '', 'sanitize_callback' => 'sahel_kses_map' ) );
    $w->add_control( 'sahel_contact_map', array( 'label' => 'کد نقشه', 'section' => 'sahel_pages', 'type' => 'textarea' ) );
    $w->add_setting( 'sahel_404_text', array( 'default' => 'صفحه پیدا نشد!', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_404_text', array( 'label' => 'متن ۴۰۴', 'section' => 'sahel_pages' ) );

    /* ===== فوتر ===== */
    $w->add_section( 'sahel_footer', array( 'title' => '۱۱. فوتر', 'priority' => 38 ) );
    $w->add_setting( 'sahel_copyright', array( 'default' => '© تمامی حقوق برای ' . sahel_brand() . ' محفوظ است.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_copyright', array( 'label' => 'کپی‌رایت', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_footer_about', array( 'default' => sahel_brand() . '؛ فروشگاه آنلاین.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_footer_about', array( 'label' => 'متن درباره', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_address', array( 'default' => 'تهران', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_address', array( 'label' => 'آدرس', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_phone', array( 'default' => sahel_fa( '021-220000' ), 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_phone', array( 'label' => 'تلفن', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_email', array( 'default' => 'info@example.com', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_email', array( 'label' => 'ایمیل', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_hours', array( 'default' => 'هر روز ۱۰-۲۱', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_hours', array( 'label' => 'ساعت کاری', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_instagram', array( 'default' => '#', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_instagram', array( 'label' => 'اینستاگرام', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_telegram', array( 'default' => '#', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_telegram', array( 'label' => 'تلگرام', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_whatsapp', array( 'default' => '#', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_whatsapp', array( 'label' => 'واتساپ', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_peecha_url', array( 'default' => 'https://www.peecha.ir', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_peecha_url', array( 'label' => 'لینک پیچا', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_peecha_text', array( 'default' => 'طراحی شده توسط پیچا', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_peecha_text', array( 'label' => 'متن پیچا', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_footer_style', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_footer_style', array( 'label' => '🎨 مدل فوتر', 'section' => 'sahel_footer', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک (چهار ستونه)', '2' => 'مینیمال (وسط‌چین تک‌ردیف)', '3' => 'تیره و پررنگ', '4' => 'خبرنامه‌محور (تمرکز روی عضویت)',
        '5' => '➖ فوق‌مینیمال (تک‌خط)', '6' => '🅰 برند بزرگ گرافیکی' ) ) );
    $w->add_setting( 'sahel_footer_hide_mobile', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_footer_hide_mobile', array( 'label' => '🚫 پنهان در موبایل', 'section' => 'sahel_footer', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_footer_hide_tablet', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_footer_hide_tablet', array( 'label' => '🚫 پنهان در تبلت', 'section' => 'sahel_footer', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_footer_links_title', array( 'default' => 'دسترسی سریع', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_footer_links_title', array( 'label' => 'عنوان ستون دسترسی سریع', 'section' => 'sahel_footer' ) );
    $footer_link_defaults = array(
        1 => array( 'خانه', 'home', '' ),
        2 => array( 'فروشگاه', 'shop', '' ),
        3 => array( 'مقالات', 'page', 'blog' ),
        4 => array( '', '', '' ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $fld = $footer_link_defaults[ $i ];
        $w->add_setting( 'sahel_footer_link' . $i . '_label', array( 'default' => $fld[0], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_footer_link' . $i . '_label', array( 'label' => 'عنوان لینک ' . sahel_fa( $i ), 'section' => 'sahel_footer' ) );
        $w->add_setting( 'sahel_footer_link' . $i . '_type', array( 'default' => $fld[1], 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_footer_link' . $i . '_type', array( 'label' => 'نوع لینک ' . sahel_fa( $i ), 'section' => 'sahel_footer', 'type' => 'select', 'choices' => array( '' => '— غیرفعال —', 'home' => 'صفحه اصلی', 'shop' => 'فروشگاه', 'url' => 'آدرس دلخواه', 'page' => 'صفحه وردپرس' ) ) );
        $w->add_setting( 'sahel_footer_link' . $i . '_val', array( 'default' => $fld[2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_footer_link' . $i . '_val', array( 'label' => 'مقدار لینک ' . sahel_fa( $i ) . ' (اسلاگ صفحه یا آدرس کامل)', 'section' => 'sahel_footer' ) );
    }

    /* ===== مارکی ===== */
    $w->add_setting( 'sahel_marquee_text', array( 'default' => sahel_brand() . ' ✦ ظرافت ✦ آرامش ✦ ضمانت ✦ پیراهن ✦ بافت ✦ ارسال ', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_marquee_text', array( 'label' => 'متن (با ✦ جدا کنید)', 'section' => 'sahel_secgrp_marquee' ) );
    $w->add_setting( 'sahel_marquee_color', array( 'default' => '#8b7a6b', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_marquee_color', array( 'label' => 'رنگ متن', 'section' => 'sahel_secgrp_marquee' ) ) );
    $w->add_setting( 'sahel_marquee_size', array( 'default' => 15, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_marquee_size', array( 'label' => 'اندازه فونت', 'section' => 'sahel_secgrp_marquee', 'type' => 'number' ) );
    /* v4.5: افکت مارکی */
    $w->add_setting( 'sahel_marquee_effect', array( 'default' => 'slide', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_marquee_effect', array(
        'label' => '🎬 افکت مارکی',
        'section' => 'sahel_secgrp_marquee',
        'type' => 'select',
        'choices' => array(
            'slide' => 'اسلاید ساده (چپ به راست)',
            'fade' => 'محو شدن',
            'bounce' => 'پرشی',
            'scale' => 'کوچک و بزرگ شدن',
            'wave' => 'موجی'
        )
    ) );
    $w->add_setting( 'sahel_marquee_speed', array( 'default' => 26, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_marquee_speed', array( 'label' => 'سرعت (ثانیه — کمتر = سریع‌تر)', 'section' => 'sahel_secgrp_marquee', 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 60 ) ) );
    $w->add_setting( 'sahel_marquee_dir', array( 'default' => 'rtl', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_marquee_dir', array( 'label' => '↔ مسیر حرکت', 'section' => 'sahel_secgrp_marquee', 'type' => 'select', 'choices' => array( 'rtl' => 'راست به چپ', 'ltr' => 'چپ به راست' ) ) );

    /* ===== بخش‌های صفحه اصلی ===== */
    $w->add_panel( 'sahel_home_panel', array( 'title' => '۱۲. بخش‌های صفحه اصلی', 'description' => 'هر بخش صفحه اصلی، تنظیمات (فعال‌سازی، تیتر، پس‌زمینه، ...) و محتوای اختصاصی خودش را در یک قسمت جداگانه دارد.', 'priority' => 40 ) );
    foreach ( sahel_home_sec_defs() as $key => $def ) {
        $w->add_section( 'sahel_secgrp_' . $key, array( 'title' => $def[0], 'panel' => 'sahel_home_panel' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_on', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_on', array( 'label' => '✔ نمایش این بخش', 'section' => 'sahel_secgrp_' . $key, 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_order', array( 'default' => $def[3], 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_order', array( 'label' => '🔃 ترتیب نمایش', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 20 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_title', array( 'default' => $def[1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_sec_' . $key . '_title', array( 'label' => 'تیتر', 'section' => 'sahel_secgrp_' . $key ) );
        $w->add_setting( 'sahel_sec_' . $key . '_sub', array( 'default' => $def[2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_sec_' . $key . '_sub', array( 'label' => 'زیرتیتر', 'section' => 'sahel_secgrp_' . $key ) );
        $w->add_setting( 'sahel_sec_' . $key . '_full', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_full', array( 'label' => '↔ تمام‌عرض', 'section' => 'sahel_secgrp_' . $key, 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_align', array( 'default' => 'right', 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_sec_' . $key . '_align', array( 'label' => '⚖ تراز', 'section' => 'sahel_secgrp_' . $key, 'type' => 'select', 'choices' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_img', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_sec_' . $key . '_bg_img', array( 'label' => '🖼 تصویر پس‌زمینه', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_bg_color', array( 'label' => '🎨 رنگ پس‌زمینه', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_grad_c1', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_grad_c1', array( 'label' => '🌈 رنگ اول گرادیان', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_grad_c2', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_grad_c2', array( 'label' => '🌈 رنگ دوم گرادیان (هر دو رنگ باید انتخاب شوند)', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_opacity', array( 'default' => 100, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_bg_opacity', array( 'label' => '💫 شفافیت پس‌زمینه', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 100 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_btn_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_btn_color', array( 'label' => '🎯 رنگ دکمه', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_btn_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_btn_text', array( 'label' => '🎯 رنگ متن دکمه', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_title_size', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_title_size', array( 'label' => '🔠 اندازه فونت تیتر (px) — ۰ یعنی پیش‌فرض', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 60 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_sub_size', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_sub_size', array( 'label' => '🔠 اندازه فونت زیرتیتر (px) — ۰ یعنی پیش‌فرض', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 40 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_padding_x', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_padding_x', array( 'label' => '↔ فاصله جانبی (px) — ۰ یعنی پیش‌فرض', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 120 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_title_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_title_color', array( 'label' => '🎨 رنگ تیتر', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_text_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_text_color', array( 'label' => '🎨 رنگ متن بخش', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_hover_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_hover_color', array( 'label' => '🎨 رنگ هاور (لینک/دکمه)', 'section' => 'sahel_secgrp_' . $key ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_height', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_height', array( 'label' => '↕ حداقل ارتفاع بخش در دسکتاپ (px) — ۰ یعنی خودکار', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 1200 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_height_m', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_height_m', array( 'label' => '↕ حداقل ارتفاع بخش در موبایل (px) — ۰ یعنی خودکار', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 1200 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_padding_x_m', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_padding_x_m', array( 'label' => '↔ فاصله جانبی در موبایل (px) — ۰ یعنی پیش‌فرض', 'section' => 'sahel_secgrp_' . $key, 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 80 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_hide_mobile', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_hide_mobile', array( 'label' => '🚫 پنهان در موبایل', 'section' => 'sahel_secgrp_' . $key, 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_hide_tablet', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_hide_tablet', array( 'label' => '🚫 پنهان در تبلت', 'section' => 'sahel_secgrp_' . $key, 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_hide_desktop', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_hide_desktop', array( 'label' => '🚫 پنهان در دسکتاپ', 'section' => 'sahel_secgrp_' . $key, 'type' => 'checkbox' ) );
    }
    $w->add_setting( 'sahel_about_home_text', array( 'default' => sahel_brand() . ' با یک باور ساده متولد شد.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $w->add_control( 'sahel_about_home_text', array( 'label' => 'متن درباره در صفحه اصلی', 'section' => 'sahel_secgrp_about', 'type' => 'textarea' ) );

    /* ===== برندها ===== */
    $w->add_setting( 'sahel_brands_mode', array( 'default' => 'marquee', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_brands_mode', array( 'label' => 'حالت نمایش', 'section' => 'sahel_secgrp_brands', 'type' => 'select', 'choices' => array( 'marquee' => 'مارکی', 'float' => 'شناور', 'static' => 'ایستا' ) ) );
    $w->add_setting( 'sahel_brands_speed', array( 'default' => 30, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_brands_speed', array( 'label' => 'سرعت (ثانیه)', 'section' => 'sahel_secgrp_brands', 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 90 ) ) );
    for ( $i = 1; $i <= 8; $i++ ) {
        $w->add_setting( 'sahel_brand_logo' . $i, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_brand_logo' . $i, array( 'label' => 'لوگو ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_brands' ) ) );
        $w->add_setting( 'sahel_brand_name' . $i, array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_brand_name' . $i, array( 'label' => 'نام ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_brands' ) );
    }

    /* ===== نوار پایین موبایل ===== */
    $w->add_section( 'sahel_bottombar', array( 'title' => '۱۳. نوار پایین موبایل', 'priority' => 42 ) );
    $w->add_setting( 'sahel_bb_on', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_bb_on', array( 'label' => '✔ نمایش نوار پایین در موبایل', 'section' => 'sahel_bottombar', 'type' => 'checkbox' ) );
    $bb_choices = array( '' => '— غیرفعال —', 'home' => 'خانه', 'shop' => 'فروشگاه', 'cart' => 'سبد', 'contact' => 'تماس', 'search' => 'جستجو', 'account' => 'حساب', 'blog' => 'مقالات', 'about' => 'درباره' );
    $bb_defs = array( 'shop', 'cart', 'contact', 'search' );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_bb' . $i, array( 'default' => $bb_defs[ $i - 1 ], 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_bb' . $i, array( 'label' => 'آیتم ' . sahel_fa( $i ), 'section' => 'sahel_bottombar', 'type' => 'select', 'choices' => $bb_choices ) );
    }

    /* ===== دکمه شناور ===== */
    $w->add_section( 'sahel_fab', array( 'title' => '۱۴. دکمه شناور تماس', 'priority' => 43 ) );
    $w->add_setting( 'sahel_fab_on', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_fab_on', array( 'label' => 'نمایش دکمه', 'section' => 'sahel_fab', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_fab_pos', array( 'default' => 'bl', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_fab_pos', array( 'label' => 'موقعیت', 'section' => 'sahel_fab', 'type' => 'select', 'choices' => array( 'bl' => 'پایین چپ', 'br' => 'پایین راست', 'ml' => 'وسط چپ', 'mr' => 'وسط راست' ) ) );
    $w->add_setting( 'sahel_fab_bg', array( 'default' => '#b08968', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_fab_bg', array( 'label' => 'رنگ دکمه', 'section' => 'sahel_fab' ) ) );
    $w->add_setting( 'sahel_fab_icon', array( 'default' => '#ffffff', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_fab_icon', array( 'label' => 'رنگ آیکن', 'section' => 'sahel_fab' ) ) );
    $w->add_setting( 'sahel_fab_tel', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_fab_tel', array( 'label' => 'شماره تماس', 'section' => 'sahel_fab' ) );
    $w->add_setting( 'sahel_fab_whatsapp', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_fab_whatsapp', array( 'label' => 'واتساپ', 'section' => 'sahel_fab' ) );
    $w->add_setting( 'sahel_fab_telegram', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_fab_telegram', array( 'label' => 'تلگرام', 'section' => 'sahel_fab' ) );
    $w->add_setting( 'sahel_fab_ita', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_fab_ita', array( 'label' => 'ایتا', 'section' => 'sahel_fab' ) );
    $w->add_setting( 'sahel_fab_bale', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_fab_bale', array( 'label' => 'بله', 'section' => 'sahel_fab' ) );
    $w->add_setting( 'sahel_fab_instagram', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_fab_instagram', array( 'label' => 'اینستاگرام', 'section' => 'sahel_fab' ) );

    /* ===== تبلیغ شناور گوشه سایت ===== */
    $w->add_section( 'sahel_promo', array( 'title' => '۱۵. تبلیغ شناور گوشه سایت', 'priority' => 44 ) );
    $w->add_setting( 'sahel_promo_on', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_promo_on', array( 'label' => '✔ نمایش تبلیغ شناور', 'section' => 'sahel_promo', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_promo_img', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_promo_img', array( 'label' => 'تصویر تبلیغ', 'section' => 'sahel_promo' ) ) );
    $w->add_setting( 'sahel_promo_title', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_promo_title', array( 'label' => 'متن/عنوان تبلیغ', 'section' => 'sahel_promo' ) );
    $w->add_setting( 'sahel_promo_link', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_promo_link', array( 'label' => 'لینک مقصد', 'section' => 'sahel_promo' ) );
    $w->add_setting( 'sahel_promo_pos', array( 'default' => 'br', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_promo_pos', array( 'label' => '📍 موقعیت', 'section' => 'sahel_promo', 'type' => 'select', 'choices' => array(
        'bl' => 'پایین چپ', 'br' => 'پایین راست', 'tl' => 'بالا چپ', 'tr' => 'بالا راست' ) ) );
    $w->add_setting( 'sahel_promo_style', array( 'default' => 'card', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_promo_style', array( 'label' => '🎨 طراحی', 'section' => 'sahel_promo', 'type' => 'select', 'choices' => array(
        'card' => 'کارتی با سایه', 'circle' => 'دایره‌ای کوچک', 'ribbon' => 'روبان مورب گوشه', 'overlay' => 'تمام‌عکس با گرادیانت' ) ) );
    $w->add_setting( 'sahel_promo_size', array( 'default' => 130, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_promo_size', array( 'label' => '📐 اندازه تصویر (px)', 'section' => 'sahel_promo', 'type' => 'number', 'input_attrs' => array( 'min' => 50, 'max' => 320 ) ) );
    $w->add_setting( 'sahel_promo_font_size', array( 'default' => 13, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_promo_font_size', array( 'label' => '🔠 اندازه فونت متن (px)', 'section' => 'sahel_promo', 'type' => 'number', 'input_attrs' => array( 'min' => 9, 'max' => 28 ) ) );
    $w->add_setting( 'sahel_promo_closable', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_promo_closable', array( 'label' => '✖ دکمه بستن داشته باشد (و تا پایان جلسه مرورگر دوباره نمایش داده نشود)', 'section' => 'sahel_promo', 'type' => 'checkbox' ) );

    /* ===== محتوای بخش‌های جدید ===== */
    $w->add_setting( 'sahel_campaign_btn', array( 'default' => 'مشاهده کالکشن', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_campaign_btn', array( 'label' => 'دکمه بنر کمپین', 'section' => 'sahel_secgrp_campaign' ) );
    $w->add_setting( 'sahel_campaign_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_campaign_url', array( 'label' => 'لینک بنر کمپین', 'section' => 'sahel_secgrp_campaign' ) );
    $look_defs = array(
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1762ba743-5206-44f2-8090-f738bd071c0d.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1316896db-bd87-4b94-afa0-087689252253.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1cab5e6b5-d545-4e84-a529-fbfb06285707.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png',
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_look' . $i, array( 'default' => $look_defs[ $i - 1 ], 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_look' . $i, array( 'label' => 'تصویر گالری ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_look' ) ) );
    }
    $testi_defs = array(
        array( 'کیفیت عالی بود.', 'نگار احمدی', 'تهران' ),
        array( 'ارسال سریع.', 'سارا محمدی', 'اصفهان' ),
        array( 'راضی هستم.', 'مریم رضایی', 'شیراز' ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $w->add_setting( 'sahel_testi' . $i . '_txt', array( 'default' => $testi_defs[ $i - 1 ][0], 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_txt', array( 'label' => 'نظر ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_testi', 'type' => 'textarea' ) );
        $w->add_setting( 'sahel_testi' . $i . '_name', array( 'default' => $testi_defs[ $i - 1 ][1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_name', array( 'label' => 'نام ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_testi' ) );
        $w->add_setting( 'sahel_testi' . $i . '_role', array( 'default' => $testi_defs[ $i - 1 ][2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_role', array( 'label' => 'شهر ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_testi' ) );
    }
    $faq_defs = array(
        array( 'شرایط ارسال؟', '۲۴-۴۸ ساعت' ),
        array( 'بازگشت کالا؟', '۷ روز' ),
        array( 'ضمانت اصالت؟', 'بله' ),
        array( 'سایزبندی؟', 'راهنمای سایز دارد' ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_faq' . $i . '_q', array( 'default' => $faq_defs[ $i - 1 ][0], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_faq' . $i . '_q', array( 'label' => 'سؤال ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_faq' ) );
        $w->add_setting( 'sahel_faq' . $i . '_a', array( 'default' => $faq_defs[ $i - 1 ][1], 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $w->add_control( 'sahel_faq' . $i . '_a', array( 'label' => 'پاسخ ' . sahel_fa( $i ), 'section' => 'sahel_secgrp_faq', 'type' => 'textarea' ) );
    }
    $w->add_setting( 'sahel_insta_text', array( 'default' => 'ما را دنبال کنید.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_insta_text', array( 'label' => 'متن اینستاگرام', 'section' => 'sahel_secgrp_insta' ) );
} );

/* v4.5: ساخت منو */
function sahel_render_menu_items() {
    $locations = get_nav_menu_locations();
    if ( ! empty( $locations['primary'] ) ) {
        ob_start();
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => '',
            'fallback_cb'    => false,
            'walker'         => new Sahel_Nav_Walker(),
        ) );
        $menu_html = ob_get_clean();
        if ( $menu_html ) {
            return $menu_html;
        }
    }

    $html = '';
    for ( $i = 1; $i <= 12; $i++ ) {
        $label = trim( (string) get_theme_mod( 'sahel_m' . $i . '_label', '' ) );
        $type  = get_theme_mod( 'sahel_m' . $i . '_type', '' );
        $val   = trim( (string) get_theme_mod( 'sahel_m' . $i . '_val', '' ) );
        if ( ! $label ) { continue; }
        if ( $type === 'cats' ) {
            $html .= '<div class="dd"><a href="' . esc_url( sahel_shop_url() ) . '" class="dd-toggle">' . esc_html( $label ) . ' <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg></a>';
            $html .= '<div class="dd-menu"><div class="dd-panel"><div class="dd-grid">';
            $fbimg = 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png';
            foreach ( sahel_product_cats_tree() as $node ) {
                $html .= sahel_render_cat_dd_node( $node, $fbimg );
            }
            $html .= '<a class="dd-all" href="' . esc_url( sahel_shop_url() ) . '">همه محصولات ←</a></div></div></div></div>';
        } else {
            $url = '';
            if ( $type === 'home' ) { $url = home_url( '/' ); }
            elseif ( $type === 'shop' ) { $url = sahel_shop_url(); }
            elseif ( $type === 'url' ) { $url = $val; }
            elseif ( $type === 'page' ) { $url = sahel_page_url( $val ); }
            else { $url = $val ? $val : '#'; }
            $html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
    }
    return $html;
}

/* اگه ادمین یه منو رو به موقعیت "primary" (نمایش ← فهرست‌ها) نسبت داده باشه،
   sahel_render_menu_items() به‌جاش از همون منوی استاندارد وردپرس استفاده می‌کنه؛
   این Walker همون ساختار dd/dd-menu/dd-panel رو برای آیتم‌های دارای زیرمجموعه می‌سازه. */
class Sahel_Nav_Walker extends Walker_Nav_Menu {
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        if ( 0 === $depth ) {
            $output .= '<div class="dd-menu"><div class="dd-panel"><div class="dd-grid">';
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        if ( 0 === $depth ) {
            $output .= '</div></div></div>';
        }
    }

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes      = empty( $item->classes ) ? array() : (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes, true );

        if ( $has_children && 0 === $depth ) {
            $output .= '<div class="dd"><a href="' . esc_url( $item->url ) . '" class="dd-toggle">' . esc_html( $item->title ) . ' <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg></a>';
        } else {
            $output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
        }
    }

    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $classes      = empty( $item->classes ) ? array() : (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes, true );

        if ( $has_children && 0 === $depth ) {
            $output .= '</div>';
        }
    }
}

/* CSS - اصلاح‌شده با Apple-style و رفع مشکلات */
function sahel_css() {
    $c1 = get_theme_mod( 'sahel_c1', '#b08968' );
    $c2 = get_theme_mod( 'sahel_c2', '#d4a373' );
    $hot = get_theme_mod( 'sahel_hot', '#c65a3f' );
    $bg = get_theme_mod( 'sahel_bg', '#faf7f2' );
    $ink = get_theme_mod( 'sahel_ink', '#33261c' );
    $muted = get_theme_mod( 'sahel_muted', '#8b7a6b' );
    $hbg = get_theme_mod( 'sahel_headerbg', '#faf7f2' );
    $fbg = get_theme_mod( 'sahel_footerbg', '#241a12' );
    $mh1 = (int) get_theme_mod( 'sahel_m_h1', 26 );
    $mp = (int) get_theme_mod( 'sahel_m_p', 13 );
    $mov = (int) get_theme_mod( 'sahel_m_overlay', 92 );
    $font = sahel_font_family();
    $pricec = get_theme_mod( 'sahel_price_color', '#c65a3f' );
    $btnt = get_theme_mod( 'sahel_btn_text', '#ffffff' );
    $carticon = get_theme_mod( 'sahel_carticon_color', '#b08968' );
    $carticonh = get_theme_mod( 'sahel_carticon_hover', '#ffffff' );
    $cardbg = get_theme_mod( 'sahel_card_bg', '#ffffff' );
    $ftext = get_theme_mod( 'sahel_footer_text', '#e7cfb8' );
    $hfont = sahel_heading_font_family();
    $hfont2 = sahel_header_font_family();
    $fsize = (int) get_theme_mod( 'sahel_body_size', 16 );
    $mqc = get_theme_mod( 'sahel_marquee_color', '#8b7a6b' );
    $mqs = (int) get_theme_mod( 'sahel_marquee_size', 15 );
    $sh = (int) get_theme_mod( 'sahel_slide_height', 620 );
    $shm = (int) get_theme_mod( 'sahel_slide_height_m', 480 );
    $sht = (int) get_theme_mod( 'sahel_slide_height_t', 0 );
    if ( ! $sht ) { $sht = $shm; }
    $slider_full = (int) get_theme_mod( 'sahel_slider_full', 0 );
    $br_speed = (int) get_theme_mod( 'sahel_brands_speed', 30 );
    $header_text = get_theme_mod( 'sahel_header_text_color', '#33261c' );
    $header_btn_bg = get_theme_mod( 'sahel_header_btn_color', '#b08968' );
    $header_btn_text = get_theme_mod( 'sahel_header_btn_text', '#ffffff' );
    $mq_effect = get_theme_mod( 'sahel_marquee_effect', 'slide' );
    $mq_speed = (int) get_theme_mod( 'sahel_marquee_speed', 26 );
    $header_style = get_theme_mod( 'sahel_header_style', 'classic' );
    $cat_gap = (int) get_theme_mod( 'sahel_cat_gap', 14 );
    $prod_gap = (int) get_theme_mod( 'sahel_prod_gap', 14 );
    $post_gap = (int) get_theme_mod( 'sahel_post_gap', 18 );
    
    $dyn = ':root{--c1:' . $c1 . ';--c2:' . $c2 . ';--hot:' . $hot . ';--bg:' . $bg . ';--ink:' . $ink . ';--muted:' . $muted . ';--headerbg:' . $hbg . ';--footerbg:' . $fbg . ';--m-h1:' . $mh1 . 'px;--m-p:' . $mp . 'px;--m-ov:' . $mov . '%;--font:\'' . $font . '\',system-ui,Tahoma,sans-serif;--font-head:\'' . $hfont . '\',system-ui,Tahoma,sans-serif;--font-header:\'' . $hfont2 . '\',system-ui,Tahoma,sans-serif;--fs:' . $fsize . 'px;--pricec:' . $pricec . ';--btnt:' . $btnt . ';--carticon:' . $carticon . ';--carticonh:' . $carticonh . ';--cardbg:' . $cardbg . ';--ftext:' . $ftext . ';--mqc:' . $mqc . ';--mqs:' . $mqs . 'px;--sh:' . $sh . 'px;--shm:' . $shm . 'px;--sht:' . $sht . 'px;--br-speed:' . $br_speed . 's;--slider-full:' . $slider_full . ';--caramel:var(--c1);--sand:var(--c2);--cream:color-mix(in srgb,var(--c2) 45%,#fff);--grad:linear-gradient(100deg,var(--c2),var(--c1) 55%,color-mix(in srgb,var(--c1) 75%,#000));--grad2:linear-gradient(100deg,var(--cream),var(--c2));--line:color-mix(in srgb,var(--ink) 10%,transparent);--line2:color-mix(in srgb,var(--c1) 55%,transparent);--shadow:0 10px 30px color-mix(in srgb,var(--ink) 9%,transparent);--shadow-lg:0 30px 70px color-mix(in srgb,var(--c1) 22%,transparent);--r:24px;--header-text:' . $header_text . ';--header-btn-bg:' . $header_btn_bg . ';--header-btn-text:' . $header_btn_text . ';--mq-speed:' . $mq_speed . 's;--header-style:' . $header_style . ';--cat-gap:' . $cat_gap . 'px;--prod-gap:' . $prod_gap . 'px;--post-gap:' . $post_gap . 'px}';
    
    $static = <<<'SHCSS'
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--font);background:var(--bg);color:var(--ink);overflow-x:hidden}
::selection{background:var(--sand);color:#fff}
::-webkit-scrollbar{width:9px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--grad);border-radius:99px}
img{max-width:100%;display:block}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}
input{font-family:inherit}
.wrap{max-width:1260px;margin:0 auto;padding:0 24px;position:relative;z-index:1}
.grad-text{background:var(--grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.hot-text{background:var(--grad2);-webkit-background-clip:text;background-clip:text;color:transparent}
.rv{opacity:0;transform:translateY(30px);transition:.9s cubic-bezier(.2,.7,.2,1)}
.rv.in{opacity:1;transform:none}

/* Apple-style animations */
@keyframes appleFloat{
  0%,100%{transform:translateY(0) rotate(0)}
  50%{transform:translateY(-12px) rotate(-2deg)}
}
@keyframes appleSpring{
  0%{transform:scale(1)}
  50%{transform:scale(1.05)}
  100%{transform:scale(1)}
}
@keyframes appleBlurIn{
  from{opacity:0;filter:blur(10px);transform:translateY(20px)}
  to{opacity:1;filter:blur(0);transform:translateY(0)}
}

body.btnst-round .btn{border-radius:14px}
body.btnst-square .btn{border-radius:6px}
body.btnst-outline .btn-primary{background:transparent;color:var(--caramel);border:2px solid var(--caramel)}
body.btnst-outline .btn-primary:hover{background:var(--caramel);color:var(--btnt)}
body.btnst-soft .btn-primary{background:color-mix(in srgb,var(--c2) 25%,#fff);color:color-mix(in srgb,var(--c1) 80%,#000)}

/* v4.5: Logo fix - no border */
.brand-logo{position:relative;display:inline-flex}
.brand-logo img{height:56px;width:auto;object-fit:contain;border:none;background:transparent;filter:drop-shadow(0 6px 16px color-mix(in srgb,var(--c1) 35%,transparent));transition:transform .5s cubic-bezier(.2,.7,.2,1)}
.brand:hover .brand-logo img{transform:scale(1.08) rotate(-4deg)}

/* v4.5: Header styles */
#header{position:sticky;top:0;z-index:50;backdrop-filter:blur(20px) saturate(1.4);-webkit-backdrop-filter:blur(20px) saturate(1.4);background:color-mix(in srgb,var(--headerbg) 82%,transparent);border-bottom:1px solid var(--line);font-family:var(--font-header)}
#header::before{content:"";position:absolute;inset:0;background-image:var(--header-img,none);background-size:cover;background-position:center;opacity:var(--header-img-op,0);z-index:-1;pointer-events:none}
#header::after{content:"";position:absolute;inset-inline:0;bottom:-1px;height:1px;background:linear-gradient(90deg,transparent,var(--sand),var(--caramel),transparent);opacity:.7;z-index:2}

.header-inner{display:flex;align-items:center;gap:18px;padding:12px 0;flex-wrap:wrap;position:relative}
.brand{display:flex;align-items:center;gap:12px;font-family:var(--font-header)}
.brand-name{font-weight:900;font-size:1.25rem;font-family:var(--font-header);color:var(--header-text)}
.brand-name small{display:block;font-size:.6rem;color:var(--muted);letter-spacing:3px}

/* v4.5: Desktop menu always visible */
.bnav{display:flex;gap:2px;flex-wrap:wrap;align-items:center;font-family:var(--font-header);margin-inline-start:auto}
.bnav>a{padding:9px 16px;border-radius:99px;font-size:.88rem;font-weight:700;color:var(--header-text);transition:.25s cubic-bezier(.4,0,.2,1)}
.bnav>a:hover{color:var(--caramel);background:color-mix(in srgb,var(--c2) 12%,transparent);transform:translateY(-2px)}

.dd-toggle{display:flex;align-items:center;gap:6px;padding:9px 16px;border-radius:99px;font-size:.88rem;font-weight:700;color:var(--header-text);font-family:var(--font-header)}
.dd-toggle:hover{color:var(--caramel);background:color-mix(in srgb,var(--c2) 12%,transparent)}
.dd-toggle svg{width:13px;height:13px;stroke:currentColor;transition:transform .3s cubic-bezier(.4,0,.2,1)}
.dd:hover .dd-toggle svg{transform:rotate(180deg)}
.dd-menu{position:absolute;top:100%;right:0;left:auto;padding-top:12px;display:none;z-index:70}
.dd:hover .dd-menu,.dd.open .dd-menu{display:block;animation:appleBlurIn .4s cubic-bezier(.4,0,.2,1)}
.dd-panel{width:min(620px,90vw);max-height:min(66vh,520px);overflow-y:auto;overscroll-behavior:contain;direction:ltr;background:color-mix(in srgb,var(--bg) 85%,transparent);backdrop-filter:blur(24px) saturate(1.4);-webkit-backdrop-filter:blur(24px) saturate(1.4);border:1px solid rgba(255,255,255,.8);border-radius:24px;box-shadow:var(--shadow-lg);padding:14px}
.dd-grid{direction:rtl;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;align-content:start}
.dd-card{display:flex;align-items:center;gap:9px;padding:7px 9px;border-radius:15px;border:1px solid transparent;transition:.3s cubic-bezier(.4,0,.2,1);background:rgba(255,255,255,.6)}
.dd-card:hover{background:#fff;border-color:var(--line2);transform:translateY(-3px) scale(1.02);box-shadow:var(--shadow)}
.dd-card img{width:38px;height:38px;border-radius:11px;object-fit:cover;border:1px solid var(--line);flex-shrink:0}
.dd-card span{flex:1;display:flex;flex-direction:column;align-items:flex-end;text-align:right;gap:1px;min-width:0}
.dd-card b{font-size:.8rem}
.dd-card small{color:var(--muted);font-size:.66rem}
.dd-all{grid-column:1/-1;text-align:center;color:var(--caramel);font-weight:800;font-size:.8rem;padding:12px;border-top:1px solid var(--line);display:block}

/* v4.5: Submenu - levels as text, only last level as cards */
.dd-group-parent{grid-column:1/-1}
.dd-title{display:block;font-weight:800;font-size:.82rem;color:var(--muted);padding:6px 12px 8px}
.dd-title:hover{color:var(--caramel)}
.dd-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px}
.sh-mnav .dd-title{color:var(--ink)}
.sh-mnav .dd-cards{grid-template-columns:repeat(2,1fr)}

/* v4.7: استایل‌های دراپ‌داون دسته‌بندی — کارتی جمع‌وجور (پیش‌فرض)، ساده فهرستی، ساده چندستونه، شیشه‌ای شناور، گرید ریز دایره‌ای */
body.ddst-2 .dd-panel{width:min(300px,88vw)}
body.ddst-2 .dd-grid,body.ddst-2 .dd-cards{grid-template-columns:1fr;gap:0}
body.ddst-2 .dd-card{border-radius:10px;padding:9px 10px}
body.ddst-2 .dd-card img{display:none}
body.ddst-2 .dd-card span{align-items:flex-end;text-align:right}
body.ddst-2 .dd-card small{display:none}
body.ddst-5 .dd-panel{width:min(560px,90vw)}
body.ddst-5 .dd-grid,body.ddst-5 .dd-cards{grid-template-columns:repeat(3,1fr);gap:2px 10px}
body.ddst-5 .dd-card{border-radius:10px;padding:8px 10px}
body.ddst-5 .dd-card img{display:none}
body.ddst-5 .dd-card span{align-items:flex-end;text-align:right}
body.ddst-5 .dd-card small{display:none}
body.ddst-3 .dd-panel{background:color-mix(in srgb,var(--bg) 55%,transparent);border-color:rgba(255,255,255,.5);box-shadow:0 24px 60px color-mix(in srgb,var(--c1) 22%,transparent)}
body.ddst-3 .dd-card{background:color-mix(in srgb,#fff 35%,transparent);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-radius:99px;padding:7px 14px 7px 7px}
body.ddst-3 .dd-card img{border-radius:99px}
body.ddst-3 .dd-card:hover{background:#fff;transform:translateY(-2px) scale(1.03)}
body.ddst-4 .dd-panel{width:min(480px,90vw)}
body.ddst-4 .dd-grid,body.ddst-4 .dd-cards{grid-template-columns:repeat(auto-fit,minmax(84px,1fr));gap:10px 6px}
body.ddst-4 .dd-card{flex-direction:column;text-align:center;padding:4px;gap:6px;border-radius:0;border:none;background:transparent}
body.ddst-4 .dd-card:hover{background:transparent;box-shadow:none;transform:translateY(-3px)}
body.ddst-4 .dd-card:hover img{box-shadow:var(--shadow)}
body.ddst-4 .dd-card img{width:52px;height:52px;border-radius:50%}
body.ddst-4 .dd-card span{align-items:center;width:100%;text-align:center}
body.ddst-4 .dd-card small{display:none}

.sh-search{position:relative}
.sh-search form{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.9);border:1px solid var(--line);border-radius:99px;padding:9px 16px;width:230px;box-shadow:var(--shadow);transition:.3s cubic-bezier(.4,0,.2,1)}
.sh-search form:focus-within{border-color:var(--line2);box-shadow:0 0 0 4px color-mix(in srgb,var(--c2) 16%,transparent);transform:scale(1.02)}
.sh-search input[type=search]{border:none;outline:none;background:transparent;width:100%;font-family:inherit;font-size:.85rem;color:var(--ink)}
.sh-search input[type=search]::placeholder{color:var(--muted)}
.sh-search svg{width:16px;height:16px;stroke:var(--muted);flex-shrink:0}
.sh-live{position:absolute;top:calc(100% + 10px);inset-inline-start:0;width:400px;max-width:92vw;background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow-lg);padding:10px;display:none;z-index:90;max-height:430px;overflow-y:auto}
.sh-live.show{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;animation:appleBlurIn .3s cubic-bezier(.4,0,.2,1)}
.lv-card{border:1px solid var(--line);border-radius:14px;overflow:hidden;background:#fff;display:block;transition:.25s cubic-bezier(.4,0,.2,1)}
.lv-card:hover{border-color:var(--line2);box-shadow:var(--shadow);transform:translateY(-4px) scale(1.02)}
.lv-card img{width:100%;aspect-ratio:1/0.9;object-fit:cover}
.lv-card .lv-t{padding:7px 9px 2px;font-size:.68rem;font-weight:700;line-height:1.7;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;color:var(--ink)}
.lv-card .lv-p{padding:2px 9px 9px;color:var(--pricec);font-size:.68rem;font-weight:800}
.lv-card .lv-p del{color:var(--muted);font-weight:500;margin-inline-end:6px}
.lv-empty{grid-column:1/-1;text-align:center;color:var(--muted);font-size:.75rem;font-weight:700;padding:16px}
.so-box .sh-live{position:static;width:100%;margin-top:12px;max-height:320px}
@media(max-width:920px){.sh-search{display:none}}

.hact{display:flex;align-items:center;gap:12px;margin-inline-start:auto;font-family:var(--font-header)}
.icon-btn{position:relative;width:46px;height:46px;display:grid;place-items:center;border:1px solid var(--line);border-radius:16px;background:rgba(255,255,255,.85);box-shadow:var(--shadow);transition:.25s cubic-bezier(.4,0,.2,1)}
.icon-btn:hover{border-color:var(--line2);transform:translateY(-3px) scale(1.05)}
.icon-btn svg{width:20px;height:20px;stroke:var(--ink)}
#cartBtn svg{stroke:var(--carticon)}
#cartBtn:hover svg{stroke:var(--carticonh)}
#cartCount{position:absolute;top:-8px;left:-8px;min-width:22px;height:22px;padding:0 5px;display:grid;place-items:center;background:var(--grad);color:#fff;font-size:.7rem;font-weight:800;border-radius:99px;box-shadow:0 6px 16px color-mix(in srgb,var(--c1) 40%,transparent);animation:appleSpring .4s cubic-bezier(.4,0,.2,1)}

/* v4.9: تول‌تیپ عمومی — روی هر عنصری با data-tip کار می‌کنه.
   موقعیت با data-tip-pos (top پیش‌فرض/bottom/right/left) و استایل با data-tip-style (dark پیش‌فرض/light/brand) قابل تغییره. */
[data-tip]{position:relative}
[data-tip]::after,[data-tip]::before{position:absolute;opacity:0;visibility:hidden;pointer-events:none;transition:.22s cubic-bezier(.4,0,.2,1);z-index:80}
[data-tip]::after{content:attr(data-tip);bottom:calc(100% + 10px);left:50%;transform:translateX(-50%) translateY(4px);background:var(--ink);color:#fff;font-size:.7rem;font-weight:700;padding:6px 13px;border-radius:8px;white-space:nowrap;box-shadow:var(--shadow-lg)}
[data-tip]::before{content:"";bottom:calc(100% + 4px);left:50%;transform:translateX(-50%) translateY(4px);border:6px solid transparent;border-top-color:var(--ink)}
[data-tip]:hover::after,[data-tip]:hover::before,[data-tip]:focus-visible::after,[data-tip]:focus-visible::before{opacity:1;visibility:visible;transform:translateX(-50%) translateY(0)}
[data-tip][data-tip-pos="bottom"]::after{bottom:auto;top:calc(100% + 10px);transform:translateX(-50%) translateY(-4px)}
[data-tip][data-tip-pos="bottom"]::before{bottom:auto;top:calc(100% + 4px);transform:translateX(-50%) translateY(-4px);border-top-color:transparent;border-bottom-color:var(--ink)}
[data-tip][data-tip-pos="bottom"]:hover::after,[data-tip][data-tip-pos="bottom"]:hover::before{transform:translateX(-50%) translateY(0)}
[data-tip][data-tip-pos="right"]::after{bottom:auto;top:50%;left:calc(100% + 10px);transform:translateY(-50%) translateX(-4px)}
[data-tip][data-tip-pos="right"]::before{bottom:auto;top:50%;left:calc(100% + 4px);transform:translateY(-50%) translateX(-4px);border-top-color:transparent;border-inline-end-color:var(--ink)}
[data-tip][data-tip-pos="right"]:hover::after,[data-tip][data-tip-pos="right"]:hover::before{transform:translateY(-50%) translateX(0)}
[data-tip][data-tip-pos="left"]::after{bottom:auto;top:50%;left:auto;right:calc(100% + 10px);transform:translateY(-50%) translateX(4px)}
[data-tip][data-tip-pos="left"]::before{bottom:auto;top:50%;left:auto;right:calc(100% + 4px);transform:translateY(-50%) translateX(4px);border-top-color:transparent;border-inline-start-color:var(--ink)}
[data-tip][data-tip-pos="left"]:hover::after,[data-tip][data-tip-pos="left"]:hover::before{transform:translateY(-50%) translateX(0)}
[data-tip][data-tip-style="light"]::after{background:#fff;color:var(--ink);border:1px solid var(--line);box-shadow:var(--shadow)}
[data-tip][data-tip-style="light"]::before{border-top-color:#fff}
[data-tip][data-tip-style="light"][data-tip-pos="bottom"]::before{border-bottom-color:#fff}
[data-tip][data-tip-style="brand"]::after{background:var(--grad);color:#fff;font-weight:800}
[data-tip][data-tip-style="brand"]::before{border-top-color:var(--c1)}
[data-tip][data-tip-style="brand"][data-tip-pos="bottom"]::before{border-bottom-color:var(--c1)}

.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 28px;border-radius:99px;font-weight:800;font-size:.92rem;transition:.3s cubic-bezier(.4,0,.2,1);white-space:nowrap}
.btn-primary{background:var(--grad);color:var(--btnt);box-shadow:0 12px 32px color-mix(in srgb,var(--c1) 35%,transparent)}
.btn-primary:hover{transform:translateY(-4px) scale(1.03);box-shadow:0 18px 44px color-mix(in srgb,var(--c1) 45%,transparent)}
.btn-ghost{border:1px solid var(--line);color:var(--ink);background:rgba(255,255,255,.85);backdrop-filter:blur(8px)}
.btn-ghost:hover{border-color:var(--line2);color:var(--caramel);transform:translateY(-3px)}
.btn-light{background:#fff;color:color-mix(in srgb,var(--c1) 80%,#000)}

.slider{position:relative;min-height:var(--sh,620px);overflow:hidden}
.slider:not(.is-full){max-width:1260px;margin-inline:auto;border-radius:24px}
.slider.is-full .wrap{max-width:none;padding-inline:36px}
.slider[style*="--slider-w"]{max-width:var(--slider-w)!important}
.slide{position:absolute;inset:0;display:flex;align-items:center;opacity:0;visibility:hidden;transition:opacity 1.5s ease,visibility 1.5s}
.slide.active{opacity:1;visibility:visible}
.sb-wrap{position:absolute;inset:0;overflow:hidden}
.slide.active .sb-wrap{animation:kenburns 11s ease forwards}
@keyframes kenburns{from{transform:scale(1.16)}to{transform:scale(1.04)}}
.slide-bg{width:100%;height:100%;object-fit:cover;object-position:center top;transform:scale(1.06);transition:transform 1.1s cubic-bezier(.2,.7,.2,1);filter:saturate(1.05)}
.slide-bg-color{width:100%;height:100%}
.slide-ovl{position:absolute;inset:0}
.slide .wrap{position:relative;z-index:2;padding-top:70px;padding-bottom:70px;width:100%}
.slide-txt{max-width:640px}
.slide-txt.al-center{margin-inline:auto;text-align:center}
.slide-txt.al-center .hero-cta{justify-content:center}
.slide-txt.al-center .pill{margin-inline:auto}
.slide-txt.al-left{margin-inline-start:auto;text-align:left}
.slide-txt.al-left .hero-cta{justify-content:flex-end}
.slide.active .slide-txt>*{animation:rise .9s cubic-bezier(.2,.7,.2,1) both}
.slide.active .slide-txt>*:nth-child(2){animation-delay:.12s}
.slide.active .slide-txt>*:nth-child(3){animation-delay:.22s}
.slide.active .slide-txt>*:nth-child(4){animation-delay:.32s}
@keyframes rise{from{opacity:0;transform:translateY(34px)}to{opacity:1;transform:none}}
.slide-txt .pill{display:inline-flex;gap:8px;align-items:center;padding:9px 18px;border-radius:99px;border:1px solid var(--line2);background:color-mix(in srgb,var(--cream) 35%,transparent);backdrop-filter:blur(8px);color:color-mix(in srgb,var(--c1) 80%,#000);font-size:.78rem;font-weight:800}
.slide-txt .pill i{width:7px;height:7px;border-radius:99px;background:var(--caramel);animation:blink 1.6s infinite}
@keyframes blink{50%{opacity:.25}}
.slide-txt h1{font-size:clamp(2.2rem,5vw,3.9rem);font-weight:900;line-height:1.35;margin:20px 0 16px;letter-spacing:-.5px}
.slide-txt p{color:var(--muted);line-height:2.1;margin-bottom:28px;font-size:1rem}
.hero-cta{display:flex;gap:14px;flex-wrap:wrap}
body.sldst-2 .sb-wrap{left:0;right:auto;width:56%}
body.sldst-2 .slide-txt{background:color-mix(in srgb,var(--bg) 92%,transparent);backdrop-filter:blur(6px);padding:30px;border-radius:22px}
body.sldst-3 .slide{background:var(--bg)}
body.sldst-3 .slide-bg{display:none}
body.sldst-3 .slide-ovl{display:none}
body.sldst-3 .slide-txt{max-width:820px;margin-inline:auto;text-align:center}
body.sldst-3 .slide-txt .hero-cta{justify-content:center}
body.sldst-3 .slide-txt .pill{margin-inline:auto}
body.sldst-3 .slide-txt h1{font-size:clamp(2.6rem,6vw,4.6rem)}
body.sldst-4 .slider{min-height:260px}
body.sldst-4 .slider:not(.is-full){border-radius:20px}
body.sldst-4 .slide .wrap{padding-top:20px;padding-bottom:20px}
body.sldst-4 .slide-txt h1{font-size:clamp(1.5rem,3vw,2.2rem);margin:10px 0 10px}
body.sldst-4 .slide-txt p{display:none}
@media(max-width:920px){
body.sldst-2 .sb-wrap{position:absolute;left:0;right:0;width:100%;opacity:.35}
body.sldst-2 .slide-txt{background:transparent;backdrop-filter:none;padding:0}
}
.s-nav{position:absolute;top:50%;transform:translateY(-50%);width:50px;height:50px;border-radius:99px;background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border:1px solid var(--line);box-shadow:var(--shadow);display:grid;place-items:center;z-index:6;transition:.25s cubic-bezier(.4,0,.2,1)}
.s-nav:hover{background:var(--grad);border-color:transparent;transform:translateY(-50%) scale(1.1)}
.s-nav:hover svg{stroke:#fff}
.s-nav svg{width:18px;height:18px;stroke:var(--ink)}
.s-next{left:24px}
.s-prev{right:24px}
.s-dots{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);display:flex;gap:8px;z-index:6}
.s-dots button{width:26px;height:5px;border-radius:99px;background:color-mix(in srgb,var(--ink) 18%,transparent);transition:.35s cubic-bezier(.4,0,.2,1)}
.s-dots button.active{width:44px;background:var(--grad)}
.s-progress{position:absolute;bottom:0;right:0;height:3px;background:var(--grad);z-index:6;width:0}
@media(max-width:920px){
.slider{min-height:var(--shm,480px)}
.slide-txt h1{font-size:var(--m-h1)}
.slide-txt p{font-size:var(--m-p)}
body.hide-m-desc .slide-txt p{display:none}
.slide .wrap{padding-top:46px;padding-bottom:56px}
.s-nav{display:none}
}
@media(min-width:601px) and (max-width:920px){
.slider{min-height:var(--sht,480px)}
}

.statband{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;padding:26px 0;background:transparent;border-bottom:1px solid var(--line)}
.stat{background:rgba(255,255,255,.8);backdrop-filter:blur(10px);border:1px solid var(--line);border-radius:20px;padding:18px 14px;text-align:center;transition:.3s cubic-bezier(.4,0,.2,1)}
.stat:hover{transform:translateY(-6px) scale(1.03);box-shadow:var(--shadow-lg);border-color:var(--line2)}
.stat b{display:block;font-size:1.5rem;font-weight:900}
.stat span{color:var(--muted);font-size:.76rem;font-weight:700}

/* v4.5: Marquee with multiple effects - LTR (left to right) */
.marquee{direction:ltr;border-block:1px solid var(--line);background:rgba(255,255,255,.7);overflow:hidden;padding:14px 0}
.marquee-track{display:flex;width:max-content;animation:marqueeSlide var(--mq-speed,26s) linear infinite}
.marquee.dir-ltr .marquee-track{animation-direction:reverse}
.marquee-track>span{display:inline-block}
.marquee.effect-fade .marquee-track>span{animation:marqueeFadeLoop 2.2s ease-in-out infinite}
.marquee.effect-bounce .marquee-track>span{animation:marqueeBounceLoop 1.3s ease-in-out infinite}
.marquee.effect-scale .marquee-track>span{animation:marqueeScaleLoop 1.7s ease-in-out infinite}
.marquee.effect-wave .marquee-track>span{animation:marqueeWaveLoop 2s ease-in-out infinite}

@keyframes marqueeSlide{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes marqueeFadeLoop{0%,100%{opacity:1}50%{opacity:.45}}
@keyframes marqueeBounceLoop{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
@keyframes marqueeScaleLoop{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}
@keyframes marqueeWaveLoop{0%,100%{transform:rotate(0)}25%{transform:rotate(3deg)}75%{transform:rotate(-3deg)}}

.marquee span{display:flex;gap:26px;padding-inline:13px;font-weight:800;color:var(--mqc);white-space:nowrap;font-size:var(--mqs,.9rem)}
.marquee b{background:var(--grad);-webkit-background-clip:text;color:transparent}
.marquee em{font-style:normal;color:var(--hot)}

/* v4.5: Section backgrounds on sections, not containers */
section{padding:70px 0;position:relative}
section.sec-full{max-width:none}
section.sec-full>.wrap{max-width:none;padding-inline:28px}
section.sec-al-center .wrap{text-align:center}
section.sec-al-center .sec-head{justify-content:center;text-align:center}
section.sec-al-center .sec-head>div{flex:1}
section.sec-al-left .wrap{text-align:left}
section.sec-al-left .sec-head{flex-direction:row-reverse}
section[style*="--sec-title-fs"] .sec-head h2{font-size:var(--sec-title-fs)}
section[style*="--sec-sub-fs"] .sec-head p{font-size:var(--sec-sub-fs)}
section[style*="--sec-px"] > .wrap{padding-inline:var(--sec-px)}
section[style*="--sec-min-h"]{min-height:var(--sec-min-h);display:flex;flex-direction:column;justify-content:center}
section[style*="--sec-title-color"] .sec-head h2{color:var(--sec-title-color)}
section[style*="--sec-text-color"]{color:var(--sec-text-color)}
section[style*="--sec-text-color"] .sec-head p{color:var(--sec-text-color)}
section[style*="--sec-hover-color"] a:hover,section[style*="--sec-hover-color"] .sec-link:hover{color:var(--sec-hover-color)}
section[style*="--sec-hover-color"] .btn-primary:hover{background:var(--sec-hover-color);border-color:var(--sec-hover-color)}
.sec-bg-img{position:absolute;inset:0;background-size:cover;background-position:center;background-repeat:no-repeat;z-index:0;pointer-events:none}
section[style*="--sec-min-h-m"]{display:flex;flex-direction:column;justify-content:center}
@media(max-width:600px){
section[style*="--sec-min-h-m"]{min-height:var(--sec-min-h-m)}
section[style*="--sec-px-m"] > .wrap{padding-inline:var(--sec-px-m)}
}
/* v4.8: نمایش/عدم‌نمایش جداگانه بر اساس دستگاه (موبایل ≤۶۰۰، تبلت ۶۰۱-۹۲۰، دسکتاپ ≥۹۲۱) */
@media(max-width:600px){ .hide-m{display:none!important} }
@media(min-width:601px) and (max-width:920px){ .hide-t{display:none!important} }
@media(min-width:921px){ .hide-d{display:none!important} }
section>.wrap{position:relative;z-index:1}

.sec-head{margin-bottom:26px;display:flex;align-items:end;justify-content:space-between;gap:16px;flex-wrap:wrap}
.sec-head h2{font-size:clamp(1.3rem,2.6vw,1.8rem);font-weight:900;letter-spacing:-.3px}
.sec-head h2 span{background:var(--grad);-webkit-background-clip:text;color:transparent}
.sec-head p{color:var(--muted);margin-top:6px;font-size:.8rem}
.sec-link{padding:10px 20px;border-radius:99px;border:1px solid var(--line);background:#fff;color:var(--caramel);font-weight:800;font-size:.78rem;transition:.25s cubic-bezier(.4,0,.2,1)}
.sec-link:hover{background:var(--grad);color:var(--btnt);border-color:transparent;transform:translateX(-4px)}

.strip-arrows{display:flex;gap:8px}
.s-arrow{width:40px;height:40px;border-radius:99px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;transition:.25s cubic-bezier(.4,0,.2,1);box-shadow:var(--shadow)}
.s-arrow:hover{background:var(--grad);border-color:transparent;transform:scale(1.1)}
.s-arrow:hover svg{stroke:#fff}
.s-arrow svg{width:16px;height:16px;stroke:var(--ink)}
.strip{margin-inline:calc(50% - 50vw)}
.strip ul.products{display:flex!important;flex-wrap:nowrap!important;grid-template-columns:none!important;gap:var(--prod-gap,14px);overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding:16px max(20px,calc((100vw - 1216px)/2)) 18px;mask-image:linear-gradient(90deg,transparent,#000 2%,#000 98%,transparent);-webkit-mask-image:linear-gradient(90deg,transparent,#000 2%,#000 98%,transparent)}
section.sec-al-center .strip ul.products{justify-content:center}
section.sec-al-left .strip ul.products{justify-content:flex-end}
.strip ul.products::-webkit-scrollbar{display:none}
.strip ul.products li.product{flex:0 0 250px!important;width:250px!important;scroll-snap-align:start}
.strip .prod-card{height:100%}

.sale-sec .strip-arrows .s-arrow{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2)}
.sale-sec .strip-arrows .s-arrow svg{stroke:var(--cream)}
.sale-sec .strip-arrows .s-arrow:hover{background:var(--grad2)}
.sale-sec .strip-arrows .s-arrow:hover svg{stroke:color-mix(in srgb,var(--c1) 60%,#000)}

.cat-grid{display:flex;gap:var(--cat-gap,14px);overflow-x:auto;padding:16px 4px 18px;scroll-snap-type:x mandatory;scrollbar-width:none;mask-image:linear-gradient(90deg,transparent,#000 2%,#000 98%,transparent);-webkit-mask-image:linear-gradient(90deg,transparent,#000 2%,#000 98%,transparent)}
section.sec-al-center .cat-grid{justify-content:center}
section.sec-al-left .cat-grid{justify-content:flex-end}
.cat-grid::-webkit-scrollbar{display:none}
.cat-card{flex:0 0 168px;width:168px;height:auto;padding:10px 10px 14px;scroll-snap-align:start;border-radius:22px;border:1px solid var(--line);background:var(--cardbg);box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1)}
.cat-grid .ovl{display:none}
.cat-card img{width:100%;height:auto;aspect-ratio:1/1;object-fit:cover;border-radius:16px;border:1px solid var(--line);background:linear-gradient(135deg,color-mix(in srgb,var(--cream) 55%,#fff),var(--bg));transition:.7s cubic-bezier(.4,0,.2,1)}
.cat-card .txt{position:static;inset-inline:auto;padding:12px 4px 0;text-align:center}
.cat-card h3{font-weight:800;font-size:.86rem;color:var(--ink)}
.cat-card p{display:inline-block;margin-top:6px;background:color-mix(in srgb,var(--c2) 15%,transparent);color:var(--caramel);border-radius:99px;padding:3px 12px;font-size:.62rem;font-weight:800}
.cat-card:hover{transform:translateY(-8px) scale(1.03);box-shadow:var(--shadow-lg);border-color:var(--line2)}
.cat-card:hover img{transform:scale(1.08)}

/* v4.5: Circular/Square cards fully floating */
body.catst-4 .cat-card{background:transparent;border:none;box-shadow:none;padding:0}
body.catst-4 .cat-card img{border-radius:50%;box-shadow:var(--shadow)}
body.catst-4 .cat-card:hover{transform:translateY(-10px) scale(1.05)}
body.catst-4 .cat-card:hover img{box-shadow:var(--shadow-lg);transform:scale(1.1) rotate(5deg)}
body.catst-4 .cat-card .txt{padding:12px 0 0}

ul.products{display:grid !important;grid-template-columns:repeat(var(--pcols,5),minmax(0,1fr))!important;gap:var(--prod-gap,14px);list-style:none;padding:0;margin:0 !important}
ul.products li.product{margin:0 !important;width:auto !important;float:none !important;min-width:0;display:flex}
.prod-card{flex:1;width:100%;background:var(--cardbg);border:1px solid var(--line);border-radius:22px;display:flex;flex-direction:column;box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1)}
.prod-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:var(--shadow-lg);border-color:var(--line2)}
.prod-media{margin:8px;border-radius:16px;aspect-ratio:1/0.92;background:color-mix(in srgb,var(--cream) 40%,#fff);display:block;position:relative;overflow:hidden}
.prod-media img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.4,0,.2,1)}
.prod-card:hover .prod-media img{transform:scale(1.05)}
.badge{position:absolute;top:8px;inset-inline-start:8px;padding:4px 10px;border-radius:99px;font-size:.62rem;font-weight:800;z-index:2;backdrop-filter:blur(6px)}
.badge.hot{background:color-mix(in srgb,var(--cream) 70%,#fff);color:var(--hot);border:1px solid color-mix(in srgb,var(--hot) 35%,transparent)}
.badge.new,.badge.best{background:color-mix(in srgb,var(--cream) 50%,#fff);color:color-mix(in srgb,var(--c1) 80%,#000);border:1px solid color-mix(in srgb,var(--c1) 40%,transparent)}
.badge.variant{display:none}
.prod-body{padding:8px 12px 12px;display:flex;flex-wrap:wrap;gap:4px;flex:1;position:relative;z-index:2}
.prod-cat{width:100%;color:var(--caramel);font-size:.6rem;font-weight:800}
.prod-name{width:100%;font-weight:800;font-size:.78rem;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.5em}
.prod-name a:hover{color:var(--caramel)}
.prod-desc{width:100%;color:var(--muted);font-size:.64rem;line-height:1.9;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;min-height:2.4em}
.prod-vars{width:100%;display:flex;flex-wrap:wrap;gap:4px;margin-top:2px}
.prod-vars span{font-size:.6rem;font-weight:800;color:var(--caramel);background:color-mix(in srgb,var(--c2) 14%,transparent);border-radius:99px;padding:2px 9px}
.prod-price{flex:1;align-self:flex-end;margin:0}
.prod-price .amount{font-weight:900;font-size:.82rem;color:var(--pricec)}
.prod-price ins{text-decoration:none;color:var(--pricec);font-weight:900}
.prod-price del{color:var(--muted);font-size:.62rem;margin-inline-end:6px}
.prod-body a.button{margin:0!important;width:42px!important;height:42px!important;border-radius:14px!important;border:1px solid color-mix(in srgb,var(--c1) 35%,transparent)!important;background:color-mix(in srgb,var(--cream) 40%,#fff) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23b08968'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/20px no-repeat!important;color:transparent!important;font-size:0!important;line-height:0!important;text-indent:-9999px;overflow:hidden;padding:0!important;flex-shrink:0;align-self:flex-end;transition:.25s cubic-bezier(.4,0,.2,1);display:inline-block}
.prod-body a.button:hover{background:var(--grad) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/20px no-repeat!important;transform:translateY(-3px) scale(1.1);border-color:transparent!important}

.col-switch{display:flex;gap:6px;align-items:center}
.col-switch span{font-size:.7rem;color:var(--muted);font-weight:700;margin-inline-end:2px}
.col-switch button{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--muted);font-weight:800;font-size:.8rem;transition:.25s cubic-bezier(.4,0,.2,1)}
.col-switch button.active{background:var(--grad);color:#fff;border-color:transparent}

.page-banner{position:relative;border-radius:28px;overflow:hidden;margin-bottom:22px;min-height:190px;box-shadow:var(--shadow)}
.page-banner img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.pb-ovl{position:absolute;inset:0;background:linear-gradient(to top,rgba(20,14,10,.78),transparent 62%)}
.pb-txt{position:absolute;bottom:0;inset-inline:0;padding:22px;color:#fff}
.pb-txt h1{font-weight:900;font-size:1.6rem}
.pb-txt p{color:rgba(255,255,255,.82);font-size:.85rem;margin-top:4px}

.stock-toggle{padding:9px 18px;border-radius:99px;border:1px solid var(--line);background:#fff;color:var(--muted);font-weight:800;font-size:.78rem;transition:.25s cubic-bezier(.4,0,.2,1)}
.stock-toggle.on{background:var(--grad);color:#fff;border-color:transparent}

.sale-sec{position:relative;overflow:hidden;background:var(--footerbg);border-radius:0;margin:26px 0;padding:24px 0;box-shadow:0 30px 70px color-mix(in srgb,var(--footerbg) 45%,transparent)}
.sale-sec::before{content:"";position:absolute;inset:0;background:radial-gradient(700px 300px at 85% 0%,color-mix(in srgb,var(--c2) 25%,transparent),transparent 60%),radial-gradient(600px 280px at 10% 100%,color-mix(in srgb,var(--cream) 18%,transparent),transparent 60%)}
.sale-sec::after{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:46px 46px;mask-image:radial-gradient(80% 80% at 50% 40%,#000,transparent)}
.sale-sec .wrap{position:relative;z-index:2}
.sale-sec .sec-head{margin-bottom:14px}
.sale-sec .sec-head h2{color:#fff;font-size:1.15rem}
.sale-sec .sec-head h2 span{background:var(--grad2);-webkit-background-clip:text;color:transparent}
.sale-sec .sec-head p{color:color-mix(in srgb,var(--cream) 60%,transparent);font-size:.72rem}
.sale-sec ul.products{display:flex!important;flex-wrap:nowrap!important;grid-template-columns:none!important;gap:var(--prod-gap,14px);overflow-x:auto;padding:16px max(20px,calc((100vw - 1216px)/2)) 8px;scroll-snap-type:x mandatory;scrollbar-width:none}
.sale-sec ul.products::-webkit-scrollbar{display:none}
.sale-sec ul.products li.product{flex:0 0 210px!important;width:210px!important;scroll-snap-align:start}
.sale-sec .prod-card{background:rgba(255,255,255,.06);backdrop-filter:blur(16px);border-color:rgba(255,255,255,.12);box-shadow:0 14px 34px rgba(0,0,0,.3)}
.sale-sec .prod-card:hover{border-color:color-mix(in srgb,var(--c2) 60%,transparent);box-shadow:0 18px 44px color-mix(in srgb,var(--c2) 25%,transparent)}
.sale-sec .prod-media{margin:6px;background:rgba(255,255,255,.05)}
.sale-sec .prod-body{padding:6px 10px 10px}
.sale-sec .prod-cat,.sale-sec .prod-desc{display:none}
.sale-sec .prod-name{font-size:.7rem}
.sale-sec .prod-name a{color:#fff}
.sale-sec .prod-price .amount{font-size:.74rem;color:#fff}
.sale-sec .prod-price del{font-size:.58rem;color:color-mix(in srgb,var(--cream) 45%,transparent)}
.sale-sec .prod-body a.button{width:36px!important;height:36px!important;border-radius:12px!important;background:color-mix(in srgb,var(--c2) 20%,transparent) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23e7cfb8'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/18px no-repeat!important;border-color:color-mix(in srgb,var(--c2) 45%,transparent)}
.sale-sec .prod-body a.button:hover{background:var(--grad2) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%234a2f1d'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/18px no-repeat!important}

.shop-main{padding:24px 24px 70px}
.crumbs{margin-bottom:14px}
.woocommerce-breadcrumb{color:var(--muted);font-size:.76rem}
.woocommerce-breadcrumb a{color:var(--caramel)}
.catbar{display:flex;gap:10px;overflow-x:auto;padding-bottom:12px;margin-bottom:16px;flex-wrap:nowrap;scrollbar-width:none}
.catbar::-webkit-scrollbar{display:none}
.catbar a{flex-shrink:0;padding:9px 22px;border-radius:99px;border:1px solid var(--line);background:#fff;color:var(--muted);font-weight:800;font-size:.82rem;transition:.25s cubic-bezier(.4,0,.2,1)}
.catbar a:hover{color:var(--caramel);border-color:var(--line2);transform:translateY(-2px)}
.catbar a.active{background:var(--grad);color:#fff;border-color:transparent;box-shadow:0 10px 24px color-mix(in srgb,var(--c1) 35%,transparent)}
.catbar a.catbar-sub{background:color-mix(in srgb,var(--c2) 10%,#fff);border-style:dashed}
.shop-toolbar{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:22px;flex-wrap:wrap;background:rgba(255,255,255,.8);border:1px solid var(--line);border-radius:16px;padding:10px 16px}
.woocommerce-result-count{color:var(--muted);font-size:.8rem;margin:0}
.woocommerce-ordering{margin:0}
.woocommerce-ordering select{padding:9px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;font-family:inherit;font-size:.82rem}
.shop-pagination{margin-top:36px;display:flex;justify-content:center}
.woocommerce-pagination ul,.shop-pagination .nav-links{display:flex;gap:8px;list-style:none;border:none;padding:0}
.woocommerce-pagination ul li a,.woocommerce-pagination ul li span,.nav-links a,.nav-links span{display:grid;place-items:center;min-width:42px;height:42px;padding:0 12px;border-radius:14px;border:1px solid var(--line);background:#fff;font-weight:800;transition:.25s cubic-bezier(.4,0,.2,1)}
.woocommerce-pagination ul li a:hover,.nav-links a:hover{transform:translateY(-2px);border-color:var(--line2)}
.woocommerce-pagination ul li .current,.nav-links .current{background:var(--grad);color:#fff;border-color:transparent}
.empty-state{text-align:center;background:#fff;border:1px dashed var(--line);border-radius:24px;padding:60px 20px;color:var(--muted);font-weight:700;display:grid;gap:16px;justify-items:center}
.page-hero{display:flex;align-items:center;gap:46px;padding:26px 6px 54px;flex-wrap:wrap}
.page-hero-img{width:340px;flex-shrink:0;border-radius:28px;overflow:hidden;box-shadow:var(--shadow-lg)}
.page-hero-img img{width:100%;height:300px;object-fit:cover}
.logo3d{position:relative;width:300px;height:300px;perspective:1000px;flex-shrink:0}
.logo3d-glow{position:absolute;inset:8%;border-radius:50%;background:radial-gradient(circle,color-mix(in srgb,var(--c2) 40%,transparent),color-mix(in srgb,var(--cream) 20%,transparent) 55%,transparent 72%);filter:blur(22px);animation:glowPulse 4s ease-in-out infinite}
.logo3d-ring{position:absolute;inset:4%;border-radius:50%;border:1.5px dashed color-mix(in srgb,var(--c1) 50%,transparent);animation:spin3d 16s linear infinite}
.logo3d-ring::before{content:"";position:absolute;top:-5px;left:50%;width:9px;height:9px;border-radius:50%;background:var(--caramel);box-shadow:0 0 14px color-mix(in srgb,var(--c1) 90%,transparent)}
.logo3d-float{position:relative;z-index:2;width:100%;height:100%;animation:logoFloat 6s ease-in-out infinite;transform-style:preserve-3d}
.logo3d-float img.main{width:100%;height:100%;object-fit:contain;transition:transform .25s ease-out;filter:drop-shadow(0 26px 34px color-mix(in srgb,var(--ink) 25%,transparent)) drop-shadow(0 8px 18px color-mix(in srgb,var(--c1) 35%,transparent))}
.logo3d .refl{position:absolute;z-index:1;left:0;right:0;bottom:-40%;width:100%;height:100%;object-fit:contain;transform:scaleY(-1);opacity:.15;filter:blur(3px);-webkit-mask-image:linear-gradient(to top,rgba(0,0,0,.7),transparent 55%);mask-image:linear-gradient(to top,rgba(0,0,0,.7),transparent 55%);pointer-events:none}
.page-hero-txt h1{font-size:clamp(1.6rem,3.4vw,2.4rem);font-weight:900}
.page-hero-txt p{color:var(--muted);margin-top:10px;font-size:.95rem;line-height:2}
@keyframes logoFloat{50%{transform:translateY(-14px)}}
@keyframes glowPulse{50%{opacity:.55;transform:scale(1.07)}}
@keyframes spin3d{to{transform:rotate(360deg)}}
.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:20px}
.contact-grid img{border-radius:20px;object-fit:cover;width:100%;height:280px;box-shadow:var(--shadow)}
.map-box iframe{width:100%;height:280px;border:0;border-radius:20px;box-shadow:var(--shadow)}
@media(max-width:700px){.logo3d{width:230px;height:230px;margin:0 auto}.page-hero{justify-content:center;text-align:center;gap:26px}.page-hero-img{width:100%}.contact-grid{grid-template-columns:1fr}}

.pd-main{padding:24px 24px 70px}
body.pd-full .pd-main{max-width:none;padding-inline:30px}
.pd-wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:44px;background:#fff;border:1px solid var(--line);border-radius:32px;padding:36px;box-shadow:var(--shadow)}
.pd-gallery .flexslider{height:auto!important;border:none!important;background:transparent!important;box-shadow:none!important;margin:0}
.pd-gallery .woocommerce-product-gallery__image,.pd-gallery .flex-viewport{aspect-ratio:1/1;border-radius:24px;overflow:hidden;max-height:none!important}
.pd-gallery .flex-viewport img,.pd-gallery .woocommerce-product-gallery__image img{width:100%;height:100%;object-fit:cover;border-radius:24px;border:1px solid var(--line)}
.pd-gallery .flex-control-thumbs{display:flex;gap:10px;margin-top:14px;list-style:none;padding:0}
.pd-gallery .flex-control-thumbs li{width:74px}
.pd-gallery .flex-control-thumbs img{border-radius:12px;border:2px solid transparent;opacity:1;object-fit:cover;transition:.25s cubic-bezier(.4,0,.2,1)}
.pd-gallery .flex-control-thumbs img:hover{border-color:var(--c2);transform:scale(1.05)}
.pd-gallery .flex-control-thumbs .flex-active{border-color:var(--caramel)}
@media(max-width:920px){.pd-gallery .woocommerce-product-gallery__image,.pd-gallery .flex-viewport{aspect-ratio:4/3}}
.pd-cats{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.pd-cats a{display:inline-block;padding:6px 16px;border-radius:99px;background:color-mix(in srgb,var(--c2) 15%,transparent);color:var(--caramel);font-size:.72rem;font-weight:800;transition:.25s cubic-bezier(.4,0,.2,1)}
.pd-cats a:hover{background:var(--c2);color:#fff;transform:translateY(-2px)}
.pd-summary .product_title{font-weight:900;font-size:1.7rem;line-height:1.5}
.pd-summary .woocommerce-product-rating{display:flex;align-items:center;gap:8px;margin:10px 0}
.pd-summary .star-rating{color:var(--c2)}
.pd-pricebox{display:inline-flex;align-items:center;gap:10px;padding:12px 22px;border-radius:18px;background:color-mix(in srgb,var(--hot) 8%,#fff);border:1px solid color-mix(in srgb,var(--hot) 25%,transparent);margin:8px 0 14px}
.pd-pricebox .price{font-size:1.3rem;font-weight:900;color:var(--pricec)}
.pd-pricebox .price del{color:var(--muted);font-size:.9rem}
.pd-pricebox .price ins{text-decoration:none}
.pd-excerpt p{color:var(--muted);line-height:2.1}
.pd-summary form.cart{display:flex;gap:12px;align-items:center;margin-top:20px;flex-wrap:wrap}
.pd-summary .qty input{width:76px;height:52px;border:1px solid var(--line);border-radius:16px;text-align:center;font-family:inherit;font-weight:800;background:#fff}
.pd-summary .single_add_to_cart_button{background:var(--grad)!important;color:#fff!important;border:none!important;border-radius:99px!important;padding:15px 36px;font-weight:900;font-size:1rem;box-shadow:0 16px 40px color-mix(in srgb,var(--c1) 40%,transparent);cursor:pointer;transition:.3s cubic-bezier(.4,0,.2,1)}
.pd-summary .single_add_to_cart_button:hover{transform:translateY(-4px) scale(1.03)}
.pd-trust{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
.pd-trust span{padding:9px 16px;border-radius:99px;background:color-mix(in srgb,var(--ink) 5%,#fff);font-size:.72rem;font-weight:800;color:var(--muted)}
.pd-meta{margin-top:18px;display:grid;gap:6px;color:var(--muted);font-size:.78rem}
.pd-meta a{color:var(--caramel);font-weight:700}
.pd-tabs{margin-top:30px;background:#fff;border:1px solid var(--line);border-radius:28px;padding:30px;box-shadow:var(--shadow)}
.pd-tabs .wc-tabs{display:flex;gap:10px;list-style:none;border:none;margin:0 0 22px;padding:0;flex-wrap:wrap}
.pd-tabs .wc-tabs li a{display:block;padding:11px 26px;border-radius:99px;border:1px solid var(--line);font-weight:800;color:var(--muted);transition:.25s cubic-bezier(.4,0,.2,1)}
.pd-tabs .wc-tabs li a:hover{border-color:var(--line2);color:var(--caramel)}
.pd-tabs .wc-tabs li.active a{background:var(--grad);color:#fff;border-color:transparent}
.pd-tabs .woocommerce-Tabs-panel{color:var(--muted);line-height:2.2}
.pd-tabs h2{font-size:1rem;font-weight:900;margin:0 0 12px;color:var(--ink)}
.pd-related{margin-top:54px}
.pd-related-title{font-weight:900;font-size:1.25rem;margin-bottom:22px}
.pd-related-title span{background:var(--grad);-webkit-background-clip:text;color:transparent}
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--post-gap,18px)}
.post-card{background:var(--cardbg);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1);overflow:hidden}
.post-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:var(--shadow-lg)}
.post-card img{width:100%;aspect-ratio:16/9;object-fit:cover}
.post-card .pb{padding:18px;display:grid;gap:8px}
.post-card h3{font-weight:800;font-size:.92rem;line-height:1.8}
.post-card p{color:var(--muted);font-size:.78rem;line-height:1.9}
.post-card .more{color:var(--caramel);font-weight:800;font-size:.76rem}
.post-card .pdate{color:var(--muted);font-size:.66rem}
.about-home{display:grid;grid-template-columns:400px 1fr;gap:30px;align-items:center}
.about-home .ah-img{border-radius:24px;overflow:hidden;box-shadow:var(--shadow-lg)}
.about-home .ah-img img{width:100%;height:340px;object-fit:cover}
.about-home h2{font-weight:900;font-size:1.4rem;margin-bottom:12px}
.about-home p{color:var(--muted);line-height:2.1;font-size:.9rem}
@media(max-width:800px){.about-home{grid-template-columns:1fr}}
.offer{border-radius:28px;position:relative;overflow:hidden;background:var(--footerbg);box-shadow:0 34px 80px color-mix(in srgb,var(--footerbg) 45%,transparent)}
.offer::before{content:"";position:absolute;inset:0;background:radial-gradient(640px 320px at 85% 10%,color-mix(in srgb,var(--c2) 35%,transparent),transparent 60%),radial-gradient(520px 300px at 10% 90%,color-mix(in srgb,var(--hot) 25%,transparent),transparent 60%)}
.offer-inner{display:grid;grid-template-columns:1.1fr .9fr;gap:24px;align-items:center;padding:26px;position:relative;z-index:2}
.offer h3{color:var(--cream);font-weight:800;font-size:.76rem}
.offer h2{color:#fff;font-size:1.15rem;font-weight:900;margin:8px 0}
.offer .od{color:color-mix(in srgb,var(--cream) 85%,transparent);line-height:1.9;margin-bottom:14px;font-size:.78rem}
.count{display:flex;gap:8px;margin-bottom:14px}
.count .cell{min-width:52px;padding:8px 4px;text-align:center;border-radius:12px;background:rgba(255,255,255,.08);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15)}
.count .cell b{display:block;font-size:1.05rem;font-weight:900;color:#fff}
.count .cell span{font-size:.55rem;color:color-mix(in srgb,var(--cream) 80%,transparent)}
.offer-visual{position:relative}
.offer-visual img{border-radius:18px;max-height:210px;aspect-ratio:16/10;object-fit:cover;width:100%;border:1px solid rgba(255,255,255,.15)}
.offer-off{position:absolute;top:-12px;inset-inline-start:-10px;width:52px;height:52px;border-radius:99px;background:var(--grad2);color:color-mix(in srgb,var(--c1) 60%,#000);display:grid;place-items:center;font-weight:900;font-size:.9rem;box-shadow:0 14px 34px rgba(0,0,0,.4)}
.offer .btn-light{padding:10px 22px;font-size:.82rem}
.features{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
.feat{padding:26px 18px;border-radius:var(--r);border:1px solid var(--line);background:var(--cardbg);backdrop-filter:blur(10px);text-align:center;box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1)}
.feat:hover{transform:translateY(-8px) scale(1.03);box-shadow:var(--shadow-lg);border-color:var(--line2)}
.feat h4{font-weight:900;font-size:.95rem;margin-bottom:8px}
.feat p{color:var(--muted);font-size:.76rem;line-height:1.9}
.news{border-radius:32px;border:1px solid var(--line2);background:linear-gradient(135deg,color-mix(in srgb,var(--cream) 40%,#fff),rgba(255,255,255,.9) 50%,color-mix(in srgb,var(--cream) 30%,#fff));backdrop-filter:blur(10px);padding:44px;text-align:center;position:relative;overflow:hidden}
section:not(.sec-al-center) .campaign,section:not(.sec-al-center) .news{text-align:inherit}
section:not(.sec-al-center) .news form{margin-inline:0}
section.sec-al-left .news form{margin-inline-start:auto;margin-inline-end:0}
.news::before{content:"";position:absolute;inset:0;background:radial-gradient(500px 240px at 80% 0%,color-mix(in srgb,var(--c2) 20%,transparent),transparent 60%)}
.news h2{font-weight:900;font-size:1.35rem;margin-bottom:10px;position:relative}
.news p{color:var(--muted);margin-bottom:22px;font-size:.84rem;position:relative}
.news form{display:flex;gap:12px;max-width:520px;margin:0 auto;position:relative}
.news input{flex:1;padding:14px 20px;border-radius:99px;border:1px solid var(--line);background:#fff;outline:none}

/* v4.5: Brands section */
.brands-strip{overflow:hidden;position:relative;padding:20px 0}
.brands-track{display:flex;gap:46px;width:max-content;animation:brandslide var(--br-speed,30s) linear infinite}
.brands-strip.is-float .brands-track{animation:none;flex-wrap:wrap;justify-content:flex-start;gap:36px}
.brands-strip.is-static .brands-track{animation:none;flex-wrap:wrap;justify-content:flex-start;gap:36px}
section.sec-al-center .brands-strip.is-float .brands-track,section.sec-al-center .brands-strip.is-static .brands-track{justify-content:center}
section.sec-al-left .brands-strip.is-float .brands-track,section.sec-al-left .brands-strip.is-static .brands-track{justify-content:flex-end}
.brands-strip.is-float .brand-partner{animation:appleFloat 4s ease-in-out infinite}
.brands-strip.is-float .brand-partner:nth-child(2){animation-delay:.5s}
.brands-strip.is-float .brand-partner:nth-child(3){animation-delay:1s}
.brands-strip.is-float .brand-partner:nth-child(4){animation-delay:1.5s}
.brands-strip.is-float .brand-partner:nth-child(5){animation-delay:2s}
.brands-strip.is-float .brand-partner:nth-child(6){animation-delay:2.5s}
.brands-strip.is-float .brand-partner:nth-child(7){animation-delay:3s}
.brands-strip.is-float .brand-partner:nth-child(8){animation-delay:3.5s}
@keyframes brandslide{to{transform:translateX(-50%)}}
.brand-partner{flex-shrink:0;height:56px;width:auto;display:flex;align-items:center;justify-content:center;padding:10px 22px;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1);opacity:.8}
.brand-partner img{max-height:40px;width:auto;object-fit:contain;filter:grayscale(40%);transition:.35s cubic-bezier(.4,0,.2,1)}
.brand-partner:hover{opacity:1;border-color:var(--line2);transform:translateY(-6px) scale(1.08);box-shadow:var(--shadow-lg)}
.brand-partner:hover img{filter:grayscale(0%)}

.bn-bottombar{display:none}
.sh-searchover{position:fixed;inset:0;z-index:120;background:color-mix(in srgb,var(--bg) 97%,transparent);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);display:none;align-items:flex-start;justify-content:center;padding:90px 22px}
.sh-searchover.open{display:flex}
.so-box{width:100%;max-width:560px;position:relative}
.so-box form{display:flex;gap:10px}
.so-box input[type=search]{flex:1;padding:14px 18px;border-radius:99px;border:1px solid var(--line);background:#fff;font-family:inherit;font-size:.95rem;outline:none;box-shadow:var(--shadow)}
.so-box input[type=search]:focus{border-color:var(--line2)}
.so-box .btn{flex-shrink:0}
.so-close{position:absolute;top:-58px;inset-inline-start:0;width:42px;height:42px;border-radius:14px;background:#fff;border:1px solid var(--line);display:grid;place-items:center;box-shadow:var(--shadow)}
.so-close svg{width:18px;height:18px;stroke:var(--ink)}
.so-hint{margin-top:14px;color:var(--muted);font-size:.75rem;font-weight:700;text-align:center}
.page-card{background:#fff;border:1px solid var(--line);border-radius:28px;padding:40px;box-shadow:var(--shadow)}
.page-title{font-weight:900;font-size:1.5rem;margin-bottom:20px}
.page-content{color:var(--muted);line-height:2.2}
.page-content h2,.page-content h3{color:var(--ink);margin:16px 0 8px;font-weight:800}
.page-content img{border-radius:16px}
footer{background:var(--footerbg);color:var(--ftext);padding:52px 0 0;position:relative;overflow:hidden}
footer::before{content:"";position:absolute;inset:0;background:radial-gradient(700px 300px at 90% 0%,color-mix(in srgb,var(--c2) 14%,transparent),transparent 60%),radial-gradient(600px 300px at 10% 100%,color-mix(in srgb,var(--cream) 10%,transparent),transparent 60%)}
.foot-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr 1.2fr;gap:36px;padding-bottom:40px;position:relative}
.foot-grid h5{color:#fff;font-weight:900;margin-bottom:14px;font-size:.95rem}
.foot-grid a,.foot-grid p{display:block;color:var(--ftext);font-size:.82rem;line-height:2.3}
.foot-grid a:hover{color:var(--sand)}
.foot-brand img{height:60px;margin-bottom:12px;object-fit:contain}
.foot-social{display:flex;gap:10px;margin-top:14px}
.foot-social a{width:40px;height:40px;border-radius:12px;border:1px solid rgba(255,255,255,.15);display:grid;place-items:center;transition:.25s cubic-bezier(.4,0,.2,1)}
.foot-social a:hover{border-color:var(--sand);background:color-mix(in srgb,var(--c2) 12%,transparent);transform:translateY(-3px) scale(1.1)}
.foot-social svg{width:18px;height:18px;stroke:#fff}
.foot-bottom{border-top:1px solid rgba(255,255,255,.08);padding:18px 0;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;font-size:.74rem;position:relative}
.foot-bottom b{color:var(--sand)}
.peecha-link{color:var(--sand);font-weight:800;text-decoration:none;padding:3px 10px;border-radius:99px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);transition:.25s cubic-bezier(.4,0,.2,1)}
.peecha-link:hover{background:rgba(255,255,255,.12);color:#fff;transform:translateY(-2px)}
footer.fstyle-2 .foot-grid{grid-template-columns:1fr;text-align:center;justify-items:center;gap:26px}
footer.fstyle-2 .foot-social{justify-content:center}
footer.fstyle-2 .foot-brand img{margin-inline:auto}
footer.fstyle-3{background:#0a0a0a;border-top:4px solid var(--c1)}
footer.fstyle-3::before{display:none}
footer.fstyle-3 .foot-grid h5{color:var(--sand)}
footer.fstyle-3 .foot-social a{border-color:rgba(255,255,255,.1)}
footer.fstyle-5 .foot-grid{grid-template-columns:1fr;justify-items:center;text-align:center;padding-bottom:20px}
footer.fstyle-5 .foot-grid>div:not(.foot-brand){display:none}
footer.fstyle-5 .foot-brand img{margin-inline:auto}
footer.fstyle-5 .foot-brand p{display:none}
footer.fstyle-5 .foot-social{justify-content:center}
footer.fstyle-6{position:relative;padding-top:60px;overflow:hidden}
footer.fstyle-6::after{content:attr(data-brand);position:absolute;bottom:-.18em;left:50%;transform:translateX(-50%);font-size:min(13vw,160px);font-weight:900;color:color-mix(in srgb,var(--c1) 10%,transparent);white-space:nowrap;pointer-events:none;line-height:1;z-index:0}
footer.fstyle-6 .wrap{position:relative;z-index:1}
.foot-news{position:relative;border-bottom:1px solid rgba(255,255,255,.08);padding:30px 0;text-align:center}
.foot-news h4{font-size:1.15rem;font-weight:900;color:#fff;margin-bottom:14px}
.foot-news form{display:flex;gap:8px;max-width:420px;margin:0 auto;flex-wrap:wrap;justify-content:center}
.foot-news input{flex:1;min-width:200px;padding:12px 18px;border-radius:99px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.08);color:#fff;font-family:inherit;font-size:.85rem}
.foot-news input::placeholder{color:rgba(255,255,255,.6)}
.foot-news .btn-primary{white-space:nowrap}
#overlay{position:fixed;inset:0;background:color-mix(in srgb,var(--footerbg) 45%,transparent);backdrop-filter:blur(4px);z-index:90;opacity:0;pointer-events:none;transition:.3s}
#overlay.show{opacity:1;pointer-events:auto}
#cartDrawer{position:fixed;top:0;bottom:0;left:0;width:min(380px,92vw);background:#fff;z-index:95;transform:translateX(-105%);transition:.45s cubic-bezier(.2,.8,.2,1);display:flex;flex-direction:column}
#cartDrawer.show{transform:translateX(0)}
.cart-head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid var(--line)}
.cart-head h3{font-weight:900;font-size:.95rem}
#closeCart{width:34px;height:34px;border-radius:10px;border:1px solid var(--line);display:grid;place-items:center}
#closeCart svg{width:15px;height:15px;stroke:var(--ink)}
.drawer-body{flex:1;overflow-y:auto;padding:12px}
.drawer-body ul.cart_list{list-style:none;display:flex;flex-direction:column;gap:8px;margin:0}
.drawer-body ul.cart_list li{display:flex;gap:10px;align-items:center;border:1px solid var(--line);border-radius:14px;padding:8px}
.drawer-body ul.cart_list li img{width:46px;height:46px;object-fit:cover;border-radius:10px;flex-shrink:0}
.drawer-body ul.cart_list li>a:not(.remove){font-size:.72rem;font-weight:700;line-height:1.5;flex:1}
.drawer-body ul.cart_list li .quantity{display:block;color:var(--caramel);font-weight:800;font-size:.64rem}
.drawer-body ul.cart_list li .remove{margin-inline-start:0;color:var(--hot);font-weight:900;font-size:.8rem;flex-shrink:0}
.drawer-body .woocommerce-mini-cart__empty-message{text-align:center;color:var(--muted);padding:30px 10px;font-weight:700;font-size:.8rem}
.drawer-body .woocommerce-mini-cart__total{display:flex;justify-content:space-between;font-weight:800;font-size:.85rem;padding-top:10px;border-top:1px dashed var(--line);margin-top:10px}
.cart-foot{border-top:1px solid var(--line);padding:12px;display:grid;gap:8px}
.cart-foot .btn{width:100%;padding:11px}
.cart-foot-link{text-align:center;color:var(--muted);font-size:.74rem;font-weight:700}
#toast{position:fixed;bottom:24px;left:50%;transform:translate(-50%,90px);z-index:120000;background:var(--footerbg);color:#fff;padding:13px 26px;border-radius:99px;font-weight:700;font-size:.84rem;opacity:0;transition:.4s;pointer-events:none;box-shadow:0 20px 50px color-mix(in srgb,var(--footerbg) 50%,transparent)}
#toast.show{opacity:1;transform:translate(-50%,0)}
table.shop_table{width:100%;border:1px solid var(--line);border-radius:16px;border-collapse:separate;background:#fff;overflow:hidden}
table.shop_table th{background:color-mix(in srgb,var(--cream) 40%,#fff);padding:12px 14px;font-size:.82rem}
table.shop_table td{padding:12px 14px;border-top:1px solid var(--line)}
table.shop_table img{width:58px;height:58px;object-fit:cover;border-radius:12px}
.woocommerce a.remove{color:var(--hot)!important;font-weight:800}
.woocommerce .button.alt,#place_order{background:var(--grad);color:#fff;border:none;border-radius:99px;padding:13px 30px;font-weight:800;cursor:pointer}
.woocommerce .button,.woocommerce input.button{background:color-mix(in srgb,var(--c2) 12%,#fff);color:var(--caramel);border:1px solid color-mix(in srgb,var(--c1) 35%,transparent);border-radius:99px;padding:10px 22px;font-weight:700}
.woocommerce form .form-row label{font-size:.82rem;font-weight:700;display:block;margin-bottom:6px}
.woocommerce form .form-row input.input-text,.woocommerce form .form-row textarea,.woocommerce form .form-row select{width:100%;padding:12px;border:1px solid var(--line);border-radius:14px;font-family:inherit;background:#fff}
.woocommerce #payment{background:#fff;border:1px solid var(--line);border-radius:18px;padding:18px}
.woocommerce #payment ul.payment_methods{list-style:none;padding:0;display:grid;gap:10px}
.woocommerce-message{background:color-mix(in srgb,var(--cream) 55%,#fff);border:1px solid color-mix(in srgb,var(--c1) 40%,transparent);color:color-mix(in srgb,var(--c1) 80%,#000);border-radius:14px;padding:12px 18px;font-weight:700;margin:0 0 16px}
.woocommerce-error{background:color-mix(in srgb,var(--hot) 10%,#fff);border:1px solid color-mix(in srgb,var(--hot) 35%,transparent);color:var(--hot);border-radius:14px;padding:12px 18px;font-weight:700;margin:0 0 16px;list-style:none}
.woocommerce-info{background:color-mix(in srgb,var(--cream) 40%,#fff);border:1px solid color-mix(in srgb,var(--c1) 40%,transparent);color:color-mix(in srgb,var(--c1) 80%,#000);border-radius:14px;padding:12px 18px;font-weight:700;margin:0 0 16px}
.woocommerce-MyAccount-navigation ul{list-style:none;display:flex;gap:10px;flex-wrap:wrap;margin-bottom:22px}
.woocommerce-MyAccount-navigation ul li a{display:block;padding:10px 20px;border-radius:99px;border:1px solid var(--line);background:#fff;font-weight:700;color:var(--muted)}
.woocommerce-MyAccount-navigation ul li.is-active a{background:var(--grad);color:#fff}
.col2-set{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.sh-burger{display:none}
.sh-mnav{position:fixed;inset:0;z-index:110;background:color-mix(in srgb,var(--bg) 97%,transparent);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);transform:translateY(-102%);transition:transform .5s cubic-bezier(.2,.8,.2,1);overflow-y:auto;padding:24px 22px 46px}
body.sh-menu-open{overflow:hidden}
body.sh-menu-open .sh-mnav{transform:translateY(0)}
.sh-mnav-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.sh-mnav-top img{height:50px;width:auto;object-fit:contain}
.sh-close{width:42px;height:42px;border-radius:14px;border:1px solid var(--line);background:#fff;display:grid;place-items:center;box-shadow:0 10px 30px color-mix(in srgb,var(--ink) 8%,transparent)}
.sh-close svg{width:18px;height:18px;stroke:var(--ink)}
.sh-mnav nav{display:flex;flex-direction:column;gap:8px}
.sh-mnav nav>a{padding:14px 16px;border-radius:16px;background:#fff;border:1px solid var(--line);font-weight:800;font-size:1rem;color:var(--ink);transition:.25s cubic-bezier(.4,0,.2,1)}
.sh-mnav nav>a:hover{border-color:var(--line2);transform:translateX(4px)}
.sh-mnav .dd{position:static}
.sh-mnav .dd-toggle{width:100%;justify-content:space-between;background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px;font-size:1rem;font-weight:800;color:var(--ink)}
.sh-mnav .dd-menu{position:static;display:none;padding:10px 0 0;width:100%}
.sh-mnav .dd.open .dd-menu{display:block}
.sh-mnav .dd-panel{width:100%;max-height:none;overflow:visible;background:transparent;backdrop-filter:none;-webkit-backdrop-filter:none;border:none;box-shadow:none;padding:0}
.sh-mnav .dd-grid{grid-template-columns:repeat(2,1fr);gap:10px}
.sh-mnav .dd-card{background:#fff;border:1px solid var(--line)}
.sh-mnav .dd-all{grid-column:1/-1;border-top:none;background:#fff;border:1px solid var(--line);border-radius:16px;margin-top:2px}
.sh-mnav .sh-login{margin-top:16px}
.sh-mnav .sh-login .btn{width:100%}
@media(max-width:920px){
.bnav{display:none!important}
.sh-burger{display:grid}
.hact .btn-primary{display:none}
.header-inner{flex-wrap:nowrap}
.brand-name{font-size:1.05rem}
.brand-logo img{height:46px}
.strip ul.products{padding-inline:14px}
.strip ul.products li.product{flex:0 0 210px!important;width:210px!important}
.offer-inner{grid-template-columns:1fr;padding:20px}
.pd-wrap{grid-template-columns:1fr;padding:22px}
.col2-set{grid-template-columns:1fr}
.features{grid-template-columns:repeat(2,1fr)}
.foot-grid{grid-template-columns:repeat(2,1fr)}
.blog-grid{grid-template-columns:repeat(2,1fr)}
.statband{grid-template-columns:repeat(2,1fr)}
ul.products:not(.strip ul.products){--pcols:3}
.bn-bottombar{position:fixed;bottom:0;left:0;right:0;z-index:100;display:flex;gap:6px;background:color-mix(in srgb,var(--bg) 94%,transparent);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-top:1px solid var(--line);padding:8px 10px calc(8px + env(safe-area-inset-bottom));box-shadow:0 -10px 30px color-mix(in srgb,var(--ink) 8%,transparent)}
body:not(.bbar-off){padding-bottom:76px}
.bb-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;padding:8px 4px;border-radius:14px;color:var(--muted);font-size:.62rem;font-weight:800;transition:.25s cubic-bezier(.4,0,.2,1);border:none;background:none;cursor:pointer;font-family:inherit}
.bb-item svg{width:20px;height:20px;stroke:currentColor}
.bb-item.active,.bb-item:hover{color:var(--caramel);background:color-mix(in srgb,var(--c2) 12%,transparent);transform:translateY(-2px)}
#toast{bottom:92px}
}
@media(max-width:600px){
ul.products:not(.strip ul.products){--pcols:2}
.strip ul.products li.product{flex:0 0 180px!important;width:180px!important}
.cat-card{flex:0 0 140px;width:140px}
.cat-card h3{font-size:.78rem}
.prod-name{font-size:.72rem}
.prod-price .amount{font-size:.74rem}
.features{grid-template-columns:1fr}
.foot-grid{grid-template-columns:1fr}
.news{padding:30px 18px}
.news form{flex-direction:column}
.blog-grid{grid-template-columns:1fr}
.sale-sec ul.products li.product{flex:0 0 170px!important;width:170px!important}
.statband{grid-template-columns:repeat(2,1fr);gap:10px}
.so-box form{flex-direction:column}
.so-box .btn{width:100%}
}
SHCSS;
    $cart_icon_path = 'M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z';
    $cart_icon_fill = '%23' . ltrim( $carticon, '#' );
    $cart_icon_fill_h = '%23' . ltrim( $carticonh, '#' );
    $cart_icon_css = '.prod-body a.button{background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'' . $cart_icon_fill . '\'%3E%3Cpath d=\'' . $cart_icon_path . '\'/%3E%3C/svg%3E")!important}';
    $cart_icon_css .= '.prod-body a.button:hover{background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'' . $cart_icon_fill_h . '\'%3E%3Cpath d=\'' . $cart_icon_path . '\'/%3E%3C/svg%3E")!important}';
    return $dyn . $static . $cart_icon_css;
}

/* Rest of the functions remain the same as v4.4 */
function sahel_bottombar_active() {
    if ( ! get_theme_mod( 'sahel_bb_on', 1 ) ) { return false; }
    $default4 = array( 'shop', 'cart', 'contact', 'search' );
    $valid = array( 'home', 'shop', 'cart', 'contact', 'search', 'account', 'blog', 'about' );
    for ( $i = 1; $i <= 4; $i++ ) {
        $k = get_theme_mod( 'sahel_bb' . $i, $default4[ $i - 1 ] );
        if ( $k && in_array( $k, $valid, true ) ) { return true; }
    }
    return false;
}
function sahel_bottombar_render() {
    if ( ! sahel_bottombar_active() ) { return; }
    $defs = array(
        'home' => array( 'خانه', home_url( '/' ), is_front_page(), '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></svg>' ),
        'shop' => array( 'فروشگاه', sahel_shop_url(), ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product' ) && is_product() ), '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1-5h16l1 5"/><path d="M4 9v11h16V9"/><path d="M9 20v-6h6v6"/></svg>' ),
        'cart' => array( 'سبد خرید', '', false, '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.6"/><circle cx="19" cy="21" r="1.6"/><path d="M2 3h3l2.7 12.4A2 2 0 0 0 9.7 17h9.7a2 2 0 0 0 2-1.6L23 7H6"/></svg>' ),
        'contact' => array( 'تماس', sahel_page_url( 'contact-us' ), is_page( 'contact-us' ), '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L4.6 7.2a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>' ),
        'search' => array( 'جستجو', '', false, '<svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>' ),
        'account' => array( 'حساب', sahel_account_url(), false, '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>' ),
        'blog' => array( 'مقالات', sahel_page_url( 'blog' ), is_page( 'blog' ), '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h12a2 2 0 0 1 2 2v14H6a2 2 0 0 1-2-2z"/><path d="M8 8h6M8 12h6M8 16h4"/></svg>' ),
        'about' => array( 'درباره ما', sahel_page_url( 'about-us' ), is_page( 'about-us' ), '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01M12 11v5"/></svg>' ),
    );
    $default4 = array( 'shop', 'cart', 'contact', 'search' );
    $items = '';
    for ( $i = 1; $i <= 4; $i++ ) {
        $k = get_theme_mod( 'sahel_bb' . $i, $default4[ $i - 1 ] );
        if ( ! $k || ! isset( $defs[ $k ] ) ) { continue; }
        $d = $defs[ $k ];
        if ( $k === 'search' ) {
            $items .= '<button class="bb-item bbSearch" aria-label="' . esc_attr( $d[0] ) . '">' . $d[3] . esc_html( $d[0] ) . '</button>';
        } elseif ( $k === 'cart' ) {
            $items .= '<button class="bb-item bbCart" aria-label="' . esc_attr( $d[0] ) . '">' . $d[3] . esc_html( $d[0] ) . '</button>';
        } else {
            $items .= '<a class="bb-item' . ( $d[2] ? ' active' : '' ) . '" href="' . esc_url( $d[1] ) . '">' . $d[3] . esc_html( $d[0] ) . '</a>';
        }
    }
    if ( ! $items ) { return; }
    echo '<nav class="bn-bottombar">' . $items . '</nav>';
}

function sahel_fab_icons( $k ) {
    $m = array(
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L4.6 7.2a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2z"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5z"/><path d="M9 10h.01M12.5 10h.01M16 10h.01"/></svg>',
        'telegram' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3L2 10.5l6 2.5M22 3l-3 18-8-8M22 3L8 13"/></svg>',
        'ita' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M12 7v6M9 10h6"/></svg>',
        'bale' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M8 9h8M8 13h5"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>',
    );
    return isset( $m[ $k ] ) ? $m[ $k ] : $m['ita'];
}
function sahel_fab_render() {
    if ( ! get_theme_mod( 'sahel_fab_on', 1 ) ) { return; }
    $pos = get_theme_mod( 'sahel_fab_pos', 'bl' );
    $bg = get_theme_mod( 'sahel_fab_bg', '#b08968' );
    $icon = get_theme_mod( 'sahel_fab_icon', '#ffffff' );
    $items = array();
    $tel = trim( (string) get_theme_mod( 'sahel_fab_tel', '' ) );
    if ( ! $tel ) { $tel = (string) get_theme_mod( 'sahel_phone', '' ); }
    $tel_en = str_replace( array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'), array('0','1','2','3','4','5','6','7','8','9'), $tel );
    $tel_en = preg_replace( '/[^\d+]/', '', $tel_en );
    if ( $tel_en ) { $items[] = array( 'تماس تلفنی', 'tel:' . $tel_en, 'phone' ); }
    $net = array(
        'sahel_fab_whatsapp' => array( 'واتساپ', 'whatsapp' ),
        'sahel_fab_telegram' => array( 'تلگرام', 'telegram' ),
        'sahel_fab_ita' => array( 'ایتا', 'ita' ),
        'sahel_fab_bale' => array( 'بله', 'bale' ),
        'sahel_fab_instagram' => array( 'اینستاگرام', 'instagram' ),
    );
    foreach ( $net as $key => $meta ) {
        $u = get_theme_mod( $key, '' );
        if ( $u ) { $items[] = array( $meta[0], $u, $meta[1] ); }
    }
    if ( empty( $items ) ) { return; }
    echo '<div class="fab-wrap pos-' . esc_attr( $pos ) . '" style="--fab-bg:' . esc_attr( $bg ) . ';--fab-icon:' . esc_attr( $icon ) . '">';
    echo '<div class="fab-menu">';
    foreach ( $items as $it ) {
        $ext = ( strpos( $it[1], 'http' ) === 0 ) ? ' target="_blank" rel="noopener"' : '';
        echo '<a href="' . esc_url( $it[1] ) . '"' . $ext . '>' . sahel_fab_icons( $it[2] ) . esc_html( $it[0] ) . '</a>';
    }
    echo '</div>';
    echo '<button class="fab-btn" aria-label="راه‌های ارتباطی" data-tip="راه‌های ارتباطی" data-tip-pos="right" data-tip-style="brand"><span class="fab-pulse"></span>' . sahel_fab_icons( 'phone' ) . '</button>';
    echo '</div>';
}
add_action( 'wp_head', function() {
echo '<style id="sahel-fab-css">
.fab-wrap{position:fixed;z-index:85;display:flex;flex-direction:column;align-items:center;gap:10px}
.fab-wrap.pos-bl{bottom:24px;left:24px}
.fab-wrap.pos-br{bottom:24px;right:24px}
.fab-wrap.pos-ml{top:50%;left:18px;transform:translateY(-50%)}
.fab-wrap.pos-mr{top:50%;right:18px;transform:translateY(-50%)}
.fab-btn{width:56px;height:56px;border-radius:50%;background:var(--fab-bg,#b08968);color:var(--fab-icon,#fff);display:grid;place-items:center;box-shadow:0 14px 34px color-mix(in srgb,var(--fab-bg,#b08968) 45%,transparent);cursor:pointer;border:none;transition:.3s cubic-bezier(.4,0,.2,1);position:relative}
.fab-btn:hover{transform:scale(1.1)}
.fab-btn svg{width:24px;height:24px;stroke:currentColor}
.fab-btn .fab-pulse{position:absolute;inset:-6px;border-radius:50%;border:2px solid var(--fab-bg,#b08968);opacity:.5;animation:fabpulse 2s infinite}
@keyframes fabpulse{0%{transform:scale(.8);opacity:.6}100%{transform:scale(1.3);opacity:0}}
.fab-menu{display:none;flex-direction:column;gap:8px;min-width:172px}
.fab-wrap.open .fab-menu{display:flex;animation:appleBlurIn .3s cubic-bezier(.4,0,.2,1)}
.fab-menu a{display:flex;align-items:center;gap:9px;padding:9px 14px;border-radius:99px;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow);font-size:.78rem;font-weight:800;color:var(--ink);transition:.25s cubic-bezier(.4,0,.2,1);white-space:nowrap}
.fab-menu a:hover{border-color:var(--line2);color:var(--caramel);transform:translateY(-3px) scale(1.05)}
.fab-menu a svg{width:17px;height:17px;stroke:var(--fab-bg,#b08968);flex-shrink:0}
@media(max-width:920px){
.fab-wrap.pos-bl,.fab-wrap.pos-br{bottom:calc(88px + env(safe-area-inset-bottom))}
.fab-btn{width:50px;height:50px}
}
</style>';
}, 97 );

/* تبلیغ شناور گوشه سایت — موقعیت/طراحی/اندازه تصویر/فونت همه از تنظیمات قابل تغییرن */
function sahel_promo_render() {
    if ( ! get_theme_mod( 'sahel_promo_on', 0 ) ) { return; }
    $img = get_theme_mod( 'sahel_promo_img', '' );
    if ( ! $img ) { return; }
    $title = get_theme_mod( 'sahel_promo_title', '' );
    $link = get_theme_mod( 'sahel_promo_link', '' );
    if ( ! $link ) { $link = '#'; }
    $pos = get_theme_mod( 'sahel_promo_pos', 'br' );
    $style = get_theme_mod( 'sahel_promo_style', 'card' );
    $size = (int) get_theme_mod( 'sahel_promo_size', 130 );
    $fs = (int) get_theme_mod( 'sahel_promo_font_size', 13 );
    $closable = get_theme_mod( 'sahel_promo_closable', 1 );
    $ext = ( strpos( $link, 'http' ) === 0 ) ? ' target="_blank" rel="noopener"' : '';
    $tip = '';
    if ( $style === 'circle' && $title ) {
        $tip_pos = in_array( $pos, array( 'bl', 'tl' ), true ) ? 'right' : 'left';
        $tip = ' data-tip="' . esc_attr( $title ) . '" data-tip-pos="' . $tip_pos . '"';
    }
    echo '<div class="promo-float pos-' . esc_attr( $pos ) . ' style-' . esc_attr( $style ) . '" id="sahelPromo" style="--promo-size:' . $size . 'px;--promo-fs:' . $fs . 'px">';
    echo '<a class="promo-link" href="' . esc_url( $link ) . '"' . $ext . $tip . '>';
    echo '<img src="' . esc_url( $img ) . '" alt="">';
    if ( $title && $style !== 'circle' ) { echo '<span class="promo-cap">' . esc_html( $title ) . '</span>'; }
    echo '</a>';
    if ( $closable ) { echo '<button class="promo-close" aria-label="بستن" data-tip="بستن" data-tip-style="light">&times;</button>'; }
    echo '</div>';
}
add_action( 'wp_head', function() {
echo '<style id="sahel-promo-css">
.promo-float{position:fixed;z-index:84}
.promo-float.pos-bl{bottom:24px;left:24px}
.promo-float.pos-br{bottom:24px;right:24px}
.promo-float.pos-tl{top:100px;left:24px}
.promo-float.pos-tr{top:100px;right:24px}
.promo-link{display:block;transition:.3s cubic-bezier(.4,0,.2,1)}
.promo-close{position:absolute;top:-10px;inset-inline-end:-10px;width:26px;height:26px;border-radius:50%;background:#fff;border:1px solid var(--line);box-shadow:var(--shadow);display:grid;place-items:center;font-size:16px;line-height:1;color:var(--ink);cursor:pointer;z-index:2;padding:0}
.promo-close:hover{background:var(--ink);color:#fff}
.promo-float.style-card .promo-link{display:flex;flex-direction:column;background:#fff;border-radius:18px;box-shadow:var(--shadow-lg);overflow:hidden;width:var(--promo-size,130px)}
.promo-float.style-card .promo-link img{width:100%;aspect-ratio:1/1;object-fit:cover;display:block}
.promo-float.style-card .promo-cap{padding:8px 10px;font-size:var(--promo-fs,13px);font-weight:800;color:var(--ink);text-align:center}
.promo-float.style-card .promo-link:hover{transform:translateY(-4px)}
.promo-float.style-circle .promo-link{width:var(--promo-size,130px);height:var(--promo-size,130px);border-radius:50%;overflow:hidden;box-shadow:var(--shadow-lg);border:3px solid #fff}
.promo-float.style-circle .promo-link img{width:100%;height:100%;object-fit:cover;display:block}
.promo-float.style-circle .promo-link:hover{transform:scale(1.08)}
.promo-float.style-ribbon .promo-link{display:flex;align-items:center;gap:8px;background:var(--grad);color:#fff;padding:10px 16px;border-radius:99px;box-shadow:var(--shadow-lg);width:var(--promo-size,130px)}
.promo-float.style-ribbon .promo-link img{width:34px;height:34px;border-radius:50%;object-fit:cover;flex-shrink:0}
.promo-float.style-ribbon .promo-cap{font-size:var(--promo-fs,13px);font-weight:800;line-height:1.4}
.promo-float.style-ribbon .promo-link:hover{transform:translateY(-3px)}
.promo-float.style-overlay .promo-link{position:relative;width:var(--promo-size,130px);border-radius:18px;overflow:hidden;box-shadow:var(--shadow-lg)}
.promo-float.style-overlay .promo-link img{width:100%;aspect-ratio:3/4;object-fit:cover;display:block}
.promo-float.style-overlay .promo-cap{position:absolute;bottom:0;inset-inline:0;padding:16px 12px 12px;background:linear-gradient(to top,rgba(0,0,0,.82),transparent);color:#fff;font-size:var(--promo-fs,13px);font-weight:800}
.promo-float.style-overlay .promo-link:hover img{transform:scale(1.06)}
@media(max-width:920px){
.promo-float.pos-bl,.promo-float.pos-br{bottom:calc(88px + env(safe-area-inset-bottom))}
}
</style>';
}, 97 );

/* Shell, home, engine functions - same as v4.4 with fixes */
function sahel_shell( $content ) {
    $logo = esc_url( sahel_logo_url() );
    $brand = sahel_brand(); $brand_en = sahel_brand_en();
    $cats = sahel_product_cats();
    $fbimg = 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png';
    $ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );
    $hide_desc = get_theme_mod( 'sahel_m_hide_desc', 1 ) ? ' hide-m-desc' : '';
    $pd_full = ( function_exists( 'is_product' ) && is_product() && get_theme_mod( 'sahel_product_fullwidth', 0 ) ) ? ' pd-full' : '';
    $bbar_off = sahel_bottombar_active() ? '' : ' bbar-off';
    $body_cls = 'btnst-' . get_theme_mod( 'sahel_btn_style', 'pill' ) . ' catst-' . get_theme_mod( 'sahel_catcard', '1' ) . ' prodst-' . get_theme_mod( 'sahel_prodcard', '1' ) . ' postst-' . get_theme_mod( 'sahel_postcard', '1' ) . ' ddst-' . get_theme_mod( 'sahel_dd_style', '1' ) . ' sldst-' . get_theme_mod( 'sahel_slider_style', '1' ) . ' hstyle-' . get_theme_mod( 'sahel_header_style', 'classic' ) . $hide_desc . $pd_full . $bbar_off;
    $h_img = get_theme_mod( 'sahel_header_image', '' );
    $h_op = (int) get_theme_mod( 'sahel_header_image_opacity', 30 ) / 100;
    $header_img_style = $h_img ? '--header-img:url(' . esc_url( $h_img ) . ');--header-img-op:' . $h_op . ';' : '';
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?> | <?php echo esc_html( $brand ); ?></title>
<style><?php echo sahel_css(); ?></style>
<?php wp_head(); ?>
</head>
<body class="<?php echo esc_attr( $body_cls ); ?>">
<div id="toast"></div>
<?php if ( get_theme_mod( 'sahel_topbar_on', 0 ) && get_theme_mod( 'sahel_topbar_text', '' ) ) :
    $topbar_hide = ( get_theme_mod( 'sahel_topbar_hide_mobile', 0 ) ? ' hide-m' : '' ) . ( get_theme_mod( 'sahel_topbar_hide_tablet', 0 ) ? ' hide-t' : '' );
?>
<div class="topbar<?php echo esc_attr( $topbar_hide ); ?>" style="background:<?php echo esc_attr( get_theme_mod( 'sahel_topbar_bg', '#1c1c1c' ) ); ?>;color:<?php echo esc_attr( get_theme_mod( 'sahel_topbar_color', '#ffffff' ) ); ?>"><div class="wrap"><?php echo esc_html( get_theme_mod( 'sahel_topbar_text', '' ) ); ?></div></div>
<?php endif; ?>
<header id="header" style="<?php echo esc_attr( $header_img_style ); ?>">
<div class="wrap header-inner">
<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
<span class="brand-logo"><img src="<?php echo $logo; ?>" alt="<?php echo esc_attr( $brand ); ?>"></span>
<span class="brand-name"><?php echo esc_html( $brand ); ?><small><?php echo esc_html( $brand_en ); ?></small></span>
</a>
<nav class="bnav">
<?php echo sahel_render_menu_items(); ?>
</nav>
<div class="hact">
<div class="sh-search">
<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
<input type="search" name="s" placeholder="جستجوی محصول…" autocomplete="off">
<input type="hidden" name="post_type" value="product">
</form>
<div class="sh-live"></div>
</div>
<button class="icon-btn" id="cartBtn" aria-label="سبد خرید" data-tip="سبد خرید" data-tip-pos="bottom">
<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.6"/><circle cx="19" cy="21" r="1.6"/><path d="M2 3h3l2.7 12.4A2 2 0 0 0 9.7 17h9.7a2 2 0 0 0 2-1.6L23 7H6"/></svg>
<span id="cartCount"><?php echo sahel_cart_count(); ?></span>
</button>
<a class="btn btn-primary" href="<?php echo esc_url( sahel_account_url() ); ?>" style="background:var(--header-btn-bg);color:var(--header-btn-text)"><?php echo is_user_logged_in() ? 'حساب کاربری' : 'ورود | ثبت‌نام'; ?></a>
</div>
</div>
</header>
<div id="overlay"></div>
<aside id="cartDrawer">
<div class="cart-head"><h3>سبد خرید <?php echo esc_html( $brand ); ?></h3><button id="closeCart" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>
<div class="drawer-body widget_shopping_cart_content"><?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?></div>
<div class="cart-foot">
<a class="btn btn-primary" href="<?php echo esc_url( sahel_checkout_url() ); ?>">ادامه و پرداخت امن</a>
<a class="cart-foot-link" href="<?php echo esc_url( sahel_cart_url() ); ?>">مشاهده سبد خرید</a>
</div>
</aside>
<?php echo $content; ?>
<?php $footer_hide = ( get_theme_mod( 'sahel_footer_hide_mobile', 0 ) ? ' hide-m' : '' ) . ( get_theme_mod( 'sahel_footer_hide_tablet', 0 ) ? ' hide-t' : '' ); ?>
<?php $footer_brand_short = sahel_brand_short(); ?>
<footer class="fstyle-<?php echo esc_attr( get_theme_mod( 'sahel_footer_style', '1' ) . $footer_hide ); ?>" data-brand="<?php echo esc_attr( $footer_brand_short ? $footer_brand_short : $brand ); ?>">
<?php if ( get_theme_mod( 'sahel_footer_style', '1' ) === '4' ) : ?>
<div class="news foot-news"><div class="wrap"><h4>برای دریافت تخفیف‌ها و اخبار <?php echo esc_html( $brand ); ?> عضو شوید</h4><form><input type="tel" placeholder="شماره موبایل شما" required><button class="btn btn-primary" type="submit">عضویت</button></form></div></div>
<?php endif; ?>
<div class="wrap">
<div class="foot-grid">
<div class="foot-brand"><img src="<?php echo $logo; ?>" alt="<?php echo esc_attr( $brand ); ?>"><p><?php echo esc_html( get_theme_mod( 'sahel_footer_about', $brand . '؛ فروشگاه آنلاین.' ) ); ?></p>
<div class="foot-social">
<a href="<?php echo esc_url( get_theme_mod( 'sahel_instagram', '#' ) ); ?>" aria-label="اینستاگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
<a href="<?php echo esc_url( get_theme_mod( 'sahel_telegram', '#' ) ); ?>" aria-label="تلگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3L2 10.5l6 2.5M22 3l-3 18-8-8M22 3L8 13"/></svg></a>
<a href="<?php echo esc_url( get_theme_mod( 'sahel_whatsapp', '#' ) ); ?>" aria-label="واتساپ"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5z"/></svg></a>
</div>
</div>
<div><h5><?php echo esc_html( get_theme_mod( 'sahel_footer_links_title', 'دسترسی سریع' ) ); ?></h5><?php for ( $fi = 1; $fi <= 4; $fi++ ) : $fl_label = get_theme_mod( 'sahel_footer_link' . $fi . '_label', '' ); $fl_type = get_theme_mod( 'sahel_footer_link' . $fi . '_type', '' ); $fl_val = get_theme_mod( 'sahel_footer_link' . $fi . '_val', '' ); $fl_url = sahel_footer_link_url( $fl_type, $fl_val ); if ( ! $fl_label || ! $fl_type ) { continue; } ?><a href="<?php echo esc_url( $fl_url ); ?>"><?php echo esc_html( $fl_label ); ?></a><?php endfor; ?></div>
<div><h5><?php echo esc_html( $brand ); ?></h5><a href="<?php echo esc_url( sahel_page_url( 'about-us' ) ); ?>">درباره ما</a><a href="<?php echo esc_url( sahel_page_url( 'contact-us' ) ); ?>">تماس با ما</a><?php foreach ( array_slice( $cats, 0, 2 ) as $c ) : ?><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a><?php endforeach; ?></div>
<div><h5>تماس با <?php echo esc_html( $brand ); ?></h5><p>📍 <?php echo esc_html( get_theme_mod( 'sahel_address', 'تهران' ) ); ?></p><p>📞 <?php echo esc_html( get_theme_mod( 'sahel_phone', sahel_fa( '021-220000' ) ) ); ?></p><p>✉️ <?php echo esc_html( get_theme_mod( 'sahel_email', 'info@example.com' ) ); ?></p><p>🕘 <?php echo esc_html( get_theme_mod( 'sahel_hours', 'هر روز ۱۰-۲۱' ) ); ?></p></div>
</div>
<div class="foot-bottom">
<span><?php echo esc_html( get_theme_mod( 'sahel_copyright', '© تمامی حقوق برای ' . $brand . ' محفوظ است.' ) ); ?></span>
<span>🎨 <a class="peecha-link" href="<?php echo esc_url( get_theme_mod( 'sahel_peecha_url', 'https://www.peecha.ir' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_theme_mod( 'sahel_peecha_text', 'طراحی شده توسط پیچا' ) ); ?></a></span>
</div>
</div>
</footer>
<?php sahel_bottombar_render(); ?>
<?php sahel_fab_render(); ?>
<?php sahel_promo_render(); ?>
<div class="sh-searchover" id="shSearchOver">
<div class="so-box">
<button class="so-close" aria-label="بستن" data-tip="بستن" data-tip-style="light"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
<form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<input type="search" name="s" placeholder="جستجو در <?php echo esc_attr( $brand ); ?>…" autocomplete="off">
<input type="hidden" name="post_type" value="product">
<button class="btn btn-primary" type="submit">جستجو</button>
</form>
<div class="sh-live"></div>
<p class="so-hint">مثلاً: نام محصول مورد نظر…</p>
</div>
</div>
<script>
(function(){
var $=jQuery;
var ajaxUrl='<?php echo $ajax_url; ?>';
var BRAND='<?php echo esc_js( $brand ); ?>';
$(document).on('click','#cartBtn,.bbCart',function(){$('#cartDrawer').addClass('show');$('#overlay').addClass('show');});
$(document).on('click','#closeCart,#overlay',function(){$('#cartDrawer').removeClass('show');$('#overlay').removeClass('show');});
$(document).on('click','.dd-toggle',function(e){ if($(window).width()<920 && !$(this).closest('.sh-mnav').length){ e.preventDefault(); $(this).closest('.dd').toggleClass('open'); } });
(function(){ try{ if(sessionStorage.getItem('sahelPromoClosed')==='1'){ $('#sahelPromo').hide(); } }catch(e){} })();
$(document).on('click','.promo-close',function(e){ e.preventDefault(); $(this).closest('.promo-float').fadeOut(200); try{ sessionStorage.setItem('sahelPromoClosed','1'); }catch(e){} });
var ddCloseTimer=null;
$(document).on('mouseenter','.bnav>.dd',function(){ if($(window).width()<920) return; clearTimeout(ddCloseTimer); $('.bnav>.dd.open').not(this).removeClass('open'); $(this).addClass('open'); });
$(document).on('mouseleave','.bnav>.dd',function(){ if($(window).width()<920) return; var el=this; ddCloseTimer=setTimeout(function(){ $(el).removeClass('open'); },250); });
function toast(m){var t=$('#toast');t.text(m).addClass('show');setTimeout(function(){t.removeClass('show');},2600);}
$(document).on('submit','.news form',function(e){
e.preventDefault();
var v=$(this).find('input').val(); var f=$(this);
$.post(ajaxUrl,{action:'sahel_newsletter',phone:v},function(r){
toast(r.msg); if(r.ok){ f[0].reset(); }
});
});
function liveSearch(q,$box){
q=(q||'').trim();
if(q.length<2){ $box.removeClass('show').empty(); return; }
$.getJSON(ajaxUrl,{action:'sahel_live_search',q:q},function(data){
if(!data || !data.length){ $box.addClass('show').html('<div class="lv-empty">نتیجه‌ای یافت نشد 😕</div>'); return; }
var html='';
for(var i=0;i<data.length;i++){ var p=data[i];
html+='<a class="lv-card" href="'+p.link+'"><img src="'+p.img+'" alt=""><div class="lv-t">'+p.title+'</div><div class="lv-p">'+p.price+'</div></a>';
}
$box.addClass('show').html(html);
});
}
var deb=null;
$(document).on('input','.sh-search input[type=search]',function(){
var $b=$(this).closest('.sh-search').find('.sh-live'); var v=$(this).val();
clearTimeout(deb); deb=setTimeout(function(){ liveSearch(v,$b); },220);
});
$(document).on('input','.so-box input[type=search]',function(){
var $b=$(this).closest('.so-box').find('.sh-live'); var v=$(this).val();
clearTimeout(deb); deb=setTimeout(function(){ liveSearch(v,$b); },220);
});
$(document).on('click',function(e){
if(!$(e.target).closest('.sh-search,.so-box').length){ $('.sh-live').removeClass('show'); }
if(!$(e.target).closest('.fab-wrap').length){ $('.fab-wrap').removeClass('open'); }
});
$(document).on('click','.bbSearch',function(){ $('#shSearchOver').addClass('open'); setTimeout(function(){$('#shSearchOver input[type=search]').trigger('focus');},150); });
$(document).on('click','.so-close',function(){ $('#shSearchOver').removeClass('open'); });
$('#shSearchOver').on('click',function(e){ if(e.target===this){ $(this).removeClass('open'); } });
$(document).on('click','.fab-btn',function(e){ e.stopPropagation(); $('.fab-wrap').toggleClass('open'); });
var $sl=$('.slide'),$dt=$('.s-dots button'),si=0,stimer;
function sprog(){clearTimeout(stimer);var $p=$('#sprog');$p.css({transition:'none',width:'0'});setTimeout(function(){$p.css({transition:'width 7s linear',width:'100%'});},40);stimer=setTimeout(function(){sgo(si+1);},7000);}
function sgo(n){if(!$sl.length)return;si=(n+$sl.length)%$sl.length;$sl.removeClass('active').eq(si).addClass('active');$dt.removeClass('active').eq(si).addClass('active');sprog();}
$dt.on('click',function(){sgo($(this).index());});
$(document).on('click','.s-next',function(){sgo(si+1);});
$(document).on('click','.s-prev',function(){sgo(si-1);});
var tx0=null;
$('.slider').on('touchstart',function(e){tx0=e.originalEvent.touches[0].clientX;});
$('.slider').on('touchend',function(e){if(tx0===null)return;var dx=e.originalEvent.changedTouches[0].clientX-tx0;if(dx>40)sgo(si-1);if(dx<-40)sgo(si+1);tx0=null;});
$('.slider').on('mousemove',function(e){
var r=this.getBoundingClientRect();
var x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
$(this).find('.slide.active .slide-bg').css('transform','translate('+(x*-16)+'px,'+(y*-10)+'px) scale(1.1)');
$(this).find('.slide.active .slide-txt').css('transform','translate('+(x*10)+'px,'+(y*6)+'px)');
});
$('.slider').on('mouseleave',function(){$(this).find('.slide-bg').css('transform','');$(this).find('.slide-txt').css('transform','');});
if($sl.length){sgo(0);}
$(document).on('click','.s-arrow',function(){
var box=$(this).closest('.wrap').find('ul.products,.cat-grid')[0];
if(!box)return;
var dir=$(this).hasClass('next')?-1:1;
box.scrollBy({left:dir*320,behavior:'smooth'});
});
function applyCols(){
var n=localStorage.getItem('sahel_cols')||'5';
if(window.innerWidth<=920){ n = window.innerWidth<=600 ? '2' : '3'; }
document.querySelectorAll('ul.products').forEach(function(u){
if(!u.closest('.strip') && !u.closest('.sale-sec')){ u.style.setProperty('--pcols', n); }
});
$('.col-switch button').removeClass('active').filter('[data-cols="'+n+'"]').addClass('active');
}
$(document).on('click','.col-switch button',function(){
localStorage.setItem('sahel_cols', $(this).data('cols'));
applyCols();
});
$(window).on('resize', applyCols);
applyCols();
(function(){
if(!document.body.classList.contains('catst-9') && !document.body.classList.contains('prodst-11')) return;
var sels=[];
if(document.body.classList.contains('catst-9')) sels.push('.cat-card');
if(document.body.classList.contains('prodst-11')) sels.push('.prod-card');
document.querySelectorAll(sels.join(',')).forEach(function(card){
card.addEventListener('mousemove',function(e){
var r=card.getBoundingClientRect();
var x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
card.style.transform='perspective(700px) rotateY('+(x*9)+'deg) rotateX('+(-y*9)+'deg) translateY(-4px)';
});
card.addEventListener('mouseleave',function(){ card.style.transform=''; });
});
})();
$(document).on('mousemove','.logo3d',function(e){
var r=this.getBoundingClientRect();
var x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
$(this).find('.main').css('transform','rotateY('+(x*26)+'deg) rotateX('+(-y*18)+'deg)');
});
$(document).on('mouseleave','.logo3d',function(){$(this).find('.main').css('transform','');});
function faD(s){return String(s).replace(/\d/g,function(d){return '۰۱۲۳۴۵۶۷۸۹'[d];});}
if('IntersectionObserver' in window){
var cio=new IntersectionObserver(function(es){es.forEach(function(en){
if(!en.isIntersecting)return;cio.unobserve(en.target);
var el=en.target,to=+el.getAttribute('data-to'),pre=el.getAttribute('data-prefix')||'',suf=el.getAttribute('data-suffix')||'',st=null;
function step(ts){if(!st)st=ts;var p=Math.min((ts-st)/1400,1);var e2=1-Math.pow(1-p,3);var v=Math.round(to*e2);
try{ el.textContent=pre+v.toLocaleString('fa-IR')+suf; }catch(err){ el.textContent=pre+faD(v)+suf; }
if(p<1)requestAnimationFrame(step);}
requestAnimationFrame(step);
});},{threshold:.4});
document.querySelectorAll('.cnt').forEach(function(el){cio.observe(el);});
}
var end=Date.now()+((2*24+14)*3600+45*60+30)*1000;
function p2(n){return String(n).padStart(2,'0');}
setInterval(function(){var s=Math.max(0,Math.floor((end-Date.now())/1000));
if($('#cdS').length){$('#cdD').text(faD(p2(Math.floor(s/86400))));$('#cdH').text(faD(p2(Math.floor(s/3600)%24)));$('#cdM').text(faD(p2(Math.floor(s/60)%60)));$('#cdS').text(faD(p2(s%60)));}
},1000);
$(document).on('mousemove','.prod-card,.cat-card,.feat,.post-card',function(e){
var r=this.getBoundingClientRect();
this.style.setProperty('--mx',(e.clientX-r.left)+'px');
this.style.setProperty('--my',(e.clientY-r.top)+'px');
});
if('IntersectionObserver' in window){
var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});},{threshold:.12});
document.querySelectorAll('.rv').forEach(function(el){io.observe(el);});
} else { $('.rv').addClass('in'); }
document.querySelectorAll('section[data-btn-color]').forEach(function(sec){
sec.querySelectorAll('.btn-primary').forEach(function(btn){
btn.style.background=sec.getAttribute('data-btn-color');
});
});
document.querySelectorAll('section[data-btn-text]').forEach(function(sec){
sec.querySelectorAll('.btn-primary').forEach(function(btn){
btn.style.color=sec.getAttribute('data-btn-text');
});
});
if(!$('.sh-burger').length){
var $burger=$('<button class="icon-btn sh-burger" aria-label="منو" data-tip="منو" data-tip-pos="left"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>');
$burger.insertBefore($('#cartBtn'));
var loginHtml=$('.hact .btn-primary').length?$('.hact .btn-primary')[0].outerHTML:'';
var $panel=$('<div class="sh-mnav"><div class="sh-mnav-top"><img src="'+$('.brand-logo img').attr('src')+'" alt="'+BRAND+'"><button class="sh-close" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div><nav></nav><div class="sh-login"></div></div>');
$panel.find('nav').html($('.bnav').html());
$panel.find('.sh-login').html(loginHtml);
$('body').append($panel);
$burger.on('click',function(){ $('body').addClass('sh-menu-open'); });
$(document).on('click','.sh-close',function(){ $('body').removeClass('sh-menu-open'); });
$(document).on('click','.sh-mnav nav a',function(){ if(!$(this).hasClass('dd-toggle')){ $('body').removeClass('sh-menu-open'); } });
$(document).on('click','.sh-mnav .dd-toggle',function(e){ e.preventDefault(); $(this).closest('.dd').toggleClass('open'); });
}
})();
</script>
<?php wp_footer(); ?>
</body>
</html>
<?php
}

function sahel_home_html() {
    $ob = '';
    $logo = esc_url( sahel_logo_url() );
    $brand = sahel_brand(); $brand_short = sahel_brand_short(); $brand_en = sahel_brand_en();

    if ( get_theme_mod( 'sahel_slider_on', 1 ) ) {
        $count = max( 1, min( 10, (int) get_theme_mod( 'sahel_slide_count', 3 ) ) );
        $rendered = array();
        for ( $i = 1; $i <= $count; $i++ ) {
            $sd = sahel_slide_data( $i );
            $has_bg = $sd['img'] || $sd['bgtype'] !== 'image';
            if ( ! $has_bg ) { continue; }
            $rendered[] = $sd;
        }
        if ( empty( $rendered ) ) { $rendered[] = sahel_slide_data( 1 ); }
        $slider_full = get_theme_mod( 'sahel_slider_full', 0 ) ? ' is-full' : '';
        $slider_w = (int) get_theme_mod( 'sahel_slider_width', 0 );
        $slider_w_style = $slider_w > 0 ? ' style="--slider-w:' . $slider_w . 'px"' : '';
        $slider_hide = ( get_theme_mod( 'sahel_slider_hide_mobile', 0 ) ? ' hide-m' : '' ) . ( get_theme_mod( 'sahel_slider_hide_tablet', 0 ) ? ' hide-t' : '' ) . ( get_theme_mod( 'sahel_slider_hide_desktop', 0 ) ? ' hide-d' : '' );
        $ob .= '<div class="slider' . $slider_full . $slider_hide . '"' . $slider_w_style . '><div class="slides">';
        foreach ( $rendered as $i => $sd ) {
            $url = $sd['url'] ? $sd['url'] : sahel_shop_url();
            $align = 'al-' . ( $sd['align'] ? $sd['align'] : 'right' );
            $ovl_style = '--ovl-rgb:' . sahel_rgb_str( $sd['ovl_color'] ) . ';--ovl-o:' . ( (int) $sd['ovl_opacity'] / 100 );
            $ob .= '<section class="slide" style="' . esc_attr( $ovl_style ) . '"><div class="sb-wrap">';
            if ( $sd['bgtype'] === 'color' ) {
                $ob .= '<div class="slide-bg-color" style="background:' . esc_attr( $sd['bgcolor1'] ) . '"></div>';
            } elseif ( $sd['bgtype'] === 'gradient' ) {
                $ob .= '<div class="slide-bg-color" style="background:linear-gradient(135deg,' . esc_attr( $sd['bgcolor1'] ) . ',' . esc_attr( $sd['bgcolor2'] ) . ')"></div>';
            } else {
                $ob .= '<img class="slide-bg" src="' . esc_url( $sd['img'] ) . '" alt="">';
            }
            $ob .= '</div><div class="slide-ovl"></div>';
            $ob .= '<div class="wrap"><div class="slide-txt ' . esc_attr( $align ) . '">';
            if ( $sd['pill'] ) { $ob .= '<span class="pill"><i></i>' . esc_html( $sd['pill'] ) . '</span>'; }
            if ( $sd['t1'] || $sd['t2'] ) {
                $ob .= '<h1>' . esc_html( $sd['t1'] ) . ( $sd['t1'] && $sd['t2'] ? '<br>' : '' ) . ( $sd['t2'] ? '<span class="grad-text">' . esc_html( $sd['t2'] ) . '</span>' : '' ) . '</h1>';
            }
            if ( $sd['desc'] ) { $ob .= '<p>' . esc_html( $sd['desc'] ) . '</p>'; }
            if ( $sd['btn'] ) { $ob .= '<div class="hero-cta"><a class="btn btn-primary" href="' . esc_url( $url ) . '">' . esc_html( $sd['btn'] ) . '</a></div>'; }
            $ob .= '</div></div></section>';
        }
        $ob .= '</div>';
        $ob .= '<button class="s-nav s-next" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>';
        $ob .= '<button class="s-nav s-prev" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>';
        $ob .= '<div class="s-dots">';
        foreach ( $rendered as $i => $sd ) { $ob .= '<button' . ( $i === 0 ? ' class="active"' : '' ) . '></button>'; }
        $ob .= '</div><div class="s-progress" id="sprog"></div></div>';
    }

    $parts = array();

    $sec = sahel_sec( 'stats' );
    if ( $sec['on'] ) {
        $st1 = get_theme_mod( 'sahel_stat1', '8000' ); $st1l = get_theme_mod( 'sahel_stat1_label', 'مشتری خوشحال' );
        $st2 = get_theme_mod( 'sahel_stat2', '450' ); $st2l = get_theme_mod( 'sahel_stat2_label', 'محصول متنوع' );
        $st3 = get_theme_mod( 'sahel_stat3', '98' ); $st3l = get_theme_mod( 'sahel_stat3_label', 'رضایت خرید' );
        $st4 = get_theme_mod( 'sahel_stat4_text', 'سراسر کشور' ); $st4l = get_theme_mod( 'sahel_stat4_label', 'ارسال سریع' );
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="statband rv">';
        $h .= '<div class="stat"><b class="grad-text cnt" data-to="' . esc_attr( $st1 ) . '" data-prefix="+">۰</b><span>' . esc_html( $st1l ) . '</span></div>';
        $h .= '<div class="stat"><b class="grad-text cnt" data-to="' . esc_attr( $st2 ) . '" data-prefix="+">۰</b><span>' . esc_html( $st2l ) . '</span></div>';
        $h .= '<div class="stat"><b class="grad-text cnt" data-to="' . esc_attr( $st3 ) . '" data-suffix="٪">۰</b><span>' . esc_html( $st3l ) . '</span></div>';
        $h .= '<div class="stat"><b class="grad-text">' . esc_html( $st4 ) . '</b><span>' . esc_html( $st4l ) . '</span></div>';
        $h .= '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'marquee' );
    if ( $sec['on'] ) {
        $mq = get_theme_mod( 'sahel_marquee_text', $brand . ' ✦ ظرافت ✦ آرامش ✦ ضمانت ✦ پیراهن ✦ بافت ✦ ارسال ' );
        $mq_effect = get_theme_mod( 'sahel_marquee_effect', 'slide' );
        $mq_dir = get_theme_mod( 'sahel_marquee_dir', 'rtl' ) === 'ltr' ? ' dir-ltr' : '';
        $parts[] = array( $sec['order'], '<div class="marquee effect-' . esc_attr( $mq_effect ) . $mq_dir . '"><div class="marquee-track"><span>' . esc_html( $mq ) . '</span><span>' . esc_html( $mq ) . '</span></div></div>' );
    }

    $sec = sahel_sec( 'cats' );
    if ( $sec['on'] ) {
        $fb = array(
            'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png',
            'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1865f2236-b927-47e7-a5b7-b901ba0a1d00.png',
            'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/11e690877-afd4-4401-b5ca-37d6c4a8349c.png',
            'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1b8181a51-ba2d-484a-a5b0-4b4d8548d5f1.png',
        );
        $cats = sahel_product_cats_flat();
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section id="categories"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap">';
        $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '<a class="sec-link" href="' . esc_url( sahel_shop_url() ) . '">مشاهده همه ←</a>' . sahel_arrows() );
        $h .= '<div class="cat-grid">';
        $i = 0;
        foreach ( $cats as $c ) {
            $h .= '<a class="cat-card rv" href="' . esc_url( get_term_link( $c ) ) . '"><img loading="lazy" src="' . esc_url( sahel_cat_img( $c, $fb[ $i % 4 ] ) ) . '" alt=""><div class="ovl"></div><div class="txt"><h3>' . esc_html( $c->name ) . '</h3><p>' . sahel_num( $c->count ) . ' محصول</p></div></a>';
            $i++;
        }
        $h .= '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'offer' );
    $of = sahel_top_sale();
    if ( $sec['on'] && $of ) {
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section id="offer" style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="offer rv"><div class="offer-inner"><div>';
        $h .= '<h3>🤎 ' . esc_html( $sec['title'] ) . '</h3><h2>' . esc_html( $of['p']->get_name() ) . '</h2>';
        $h .= '<p class="od">' . ( $sec['sub'] ? esc_html( $sec['sub'] ) : 'این محصول ویژه را با ' . sahel_fa( $of['bp'] ) . '٪ تخفیف و ارسال رایگان دریافت کن.' ) . '</p>';
        $h .= '<div class="count"><div class="cell"><b id="cdD">۰۲</b><span>روز</span></div><div class="cell"><b id="cdH">۱۴</b><span>ساعت</span></div><div class="cell"><b id="cdM">۴۵</b><span>دقیقه</span></div><div class="cell"><b id="cdS">۳۰</b><span>ثانیه</span></div></div>';
        $h .= '<button type="button" data-product_id="' . esc_attr( $of['p']->get_id() ) . '" class="btn btn-light add_to_cart_button ajax_add_to_cart">افزودن به سبد با ' . sahel_fa( $of['bp'] ) . '٪ تخفیف</button>';
        $h .= '</div><div class="offer-visual"><span class="offer-off">' . sahel_fa( $of['bp'] ) . '٪</span>' . $of['p']->get_image( 'large' ) . '</div></div></div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    if ( class_exists( 'WooCommerce' ) ) {
        $sec = sahel_sec( 'new' );
        if ( $sec['on'] ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], sahel_arrows() );
            $h .= '<div class="strip">' . do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ) . '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
        $sec = sahel_sec( 'sale' );
        if ( $sec['on'] ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section class="sale-sec"' . $bg['style'] . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= '<div class="sec-head rv"><div><h2>' . esc_html( $sec['title'] ) . '</h2><p>' . esc_html( $sec['sub'] ) . '</p></div><div style="display:flex;gap:10px;align-items:center"><a class="sec-link" style="background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2);color:#e7cfb8" href="' . esc_url( sahel_shop_url() ) . '">مشاهده همه 🤎</a>' . sahel_arrows() . '</div></div>';
            $h .= '<div class="strip">' . do_shortcode( '[products limit="8" columns="4" on_sale="true"]' ) . '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
        $sec = sahel_sec( 'best' );
        if ( $sec['on'] ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], sahel_arrows() );
            $h .= '<div class="strip">' . do_shortcode( '[products limit="8" columns="4" best_selling="true"]' ) . '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'brands' );
    if ( $sec['on'] ) {
        $mode = get_theme_mod( 'sahel_brands_mode', 'marquee' );
        $logos = array();
        for ( $i = 1; $i <= 8; $i++ ) {
            $img = get_theme_mod( 'sahel_brand_logo' . $i, '' );
            $nm = get_theme_mod( 'sahel_brand_name' . $i, '' );
            if ( $img ) { $logos[] = array( $img, $nm ); }
        }
        if ( $logos ) {
            $bg = sahel_sec_bg_attr( $sec );
            $mode_class = $mode === 'marquee' ? '' : ( $mode === 'float' ? ' is-float' : ' is-static' );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
            $h .= '<div class="brands-strip' . $mode_class . '"><div class="brands-track">';
            foreach ( $logos as $lg ) { $h .= '<div class="brand-partner" title="' . esc_attr( $lg[1] ) . '"><img src="' . esc_url( $lg[0] ) . '" alt="' . esc_attr( $lg[1] ) . '"></div>'; }
            if ( $mode === 'marquee' ) { foreach ( $logos as $lg ) { $h .= '<div class="brand-partner" title="' . esc_attr( $lg[1] ) . '"><img src="' . esc_url( $lg[0] ) . '" alt="' . esc_attr( $lg[1] ) . '"></div>'; } }
            $h .= '</div></div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'features' );
    if ( $sec['on'] ) {
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap features">';
        $h .= '<div class="feat rv"><h4>ارسال سریع</h4><p>ارسال به سراسر کشور با بسته‌بندی ویژه</p></div>';
        $h .= '<div class="feat rv"><h4>ضمانت اصالت</h4><p>ضمانت اصالت پارچه و کیفیت دوخت</p></div>';
        $h .= '<div class="feat rv"><h4>۷ روز بازگشت</h4><p>بدون قید و شرط تا ۷ روز مهلت بازگشت</p></div>';
        $h .= '<div class="feat rv"><h4>پرداخت امن</h4><p>درگاه بانکی معتبر و پرداخت در محل</p></div>';
        $h .= '</div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'campaign' );
    if ( $sec['on'] ) {
        $btn = get_theme_mod( 'sahel_campaign_btn', 'مشاهده کالکشن' );
        $url = get_theme_mod( 'sahel_campaign_url', '' );
        if ( ! $url ) { $url = sahel_shop_url(); }
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="campaign rv">';
        $h .= '<h2>' . esc_html( $sec['title'] ) . '</h2><p>' . esc_html( $sec['sub'] ) . '</p>';
        $h .= '<a class="btn btn-light" href="' . esc_url( $url ) . '">' . esc_html( $btn ) . '</a>';
        $h .= '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'look' );
    if ( $sec['on'] ) {
        $imgs = array();
        for ( $i = 1; $i <= 4; $i++ ) { $im = get_theme_mod( 'sahel_look' . $i, '' ); if ( $im ) { $imgs[] = $im; } }
        if ( $imgs ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
            $h .= '<div class="look-grid">';
            foreach ( $imgs as $im ) {
                $h .= '<a class="look-item rv" href="' . esc_url( sahel_shop_url() ) . '"><img loading="lazy" src="' . esc_url( $im ) . '" alt=""><span class="lk-cap">مشاهده استایل ←</span></a>';
            }
            $h .= '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'testi' );
    if ( $sec['on'] ) {
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap">';
        $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
        $h .= '<div class="testi-grid">';
        $has = false;
        for ( $i = 1; $i <= 3; $i++ ) {
            $txt = get_theme_mod( 'sahel_testi' . $i . '_txt', '' );
            $nm = get_theme_mod( 'sahel_testi' . $i . '_name', '' );
            $rl = get_theme_mod( 'sahel_testi' . $i . '_role', '' );
            if ( ! $txt ) { continue; }
            $has = true;
            $av = function_exists( 'mb_substr' ) ? mb_substr( $nm, 0, 1 ) : substr( $nm, 0, 2 );
            $h .= '<div class="testi rv"><div class="stars">★★★★★</div><p>«' . esc_html( $txt ) . '»</p><div class="who"><span class="av">' . esc_html( $av ) . '</span><div><b>' . esc_html( $nm ) . '</b><span>' . esc_html( $rl ) . '</span></div></div></div>';
        }
        $h .= '</div></div></section>';
        if ( $has ) { $parts[] = array( $sec['order'], $h ); }
    }

    $sec = sahel_sec( 'faq' );
    if ( $sec['on'] ) {
        $items = array();
        for ( $i = 1; $i <= 4; $i++ ) {
            $q = get_theme_mod( 'sahel_faq' . $i . '_q', '' );
            $a = get_theme_mod( 'sahel_faq' . $i . '_a', '' );
            if ( $q && $a ) { $items[] = array( $q, $a ); }
        }
        if ( $items ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
            $h .= '<div class="faq-list">';
            foreach ( $items as $it ) {
                $h .= '<details class="faq-item rv"><summary>' . esc_html( $it[0] ) . '</summary><div class="fa-a">' . esc_html( $it[1] ) . '</div></details>';
            }
            $h .= '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'blog' );
    if ( $sec['on'] ) {
        $posts = get_posts( array( 'post_type' => 'post', 'numberposts' => 3 ) );
        if ( $posts ) {
            $bg = sahel_sec_bg_attr( $sec );
            $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
            $h .= $bg['overlay'];
            $h .= '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '<a class="sec-link" href="' . esc_url( sahel_page_url( 'blog' ) ) . '">همه مقالات ←</a>' );
            $h .= '<div class="blog-grid">';
            foreach ( $posts as $post ) {
                $thumb = get_the_post_thumbnail( $post->ID, 'medium_large' );
                if ( ! $thumb ) { $thumb = '<img src="https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png" alt="">'; }
                $h .= '<article class="post-card rv">' . $thumb . '<div class="pb"><span class="pdate">' . sahel_fa( get_the_date( 'Y/m/d', $post ) ) . '</span><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( wp_trim_words( $post->post_excerpt ? $post->post_excerpt : $post->post_content, 18 ) ) . '</p><a class="more" href="' . get_permalink( $post ) . '">ادامه مطلب ←</a></div></article>';
            }
            $h .= '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'about' );
    if ( $sec['on'] ) {
        $about_img = get_theme_mod( 'sahel_about_image', '' );
        $about_txt = get_theme_mod( 'sahel_about_home_text', $brand . ' با یک باور ساده متولد شد.' );
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="about-home rv">';
        if ( $about_img ) { $h .= '<div class="ah-img"><img src="' . esc_url( $about_img ) . '" alt=""></div>'; }
        else { $h .= '<div class="logo3d"><div class="logo3d-glow"></div><div class="logo3d-ring"></div><div class="logo3d-float"><img class="main" src="' . $logo . '" alt=""></div></div>'; }
        $h .= '<div class="ah-txt"><h2><span class="grad-text">' . esc_html( $sec['title'] ) . '</span></h2><p>' . esc_html( $about_txt ) . '</p>';
        if ( $sec['sub'] ) { $h .= '<p>' . esc_html( $sec['sub'] ) . '</p>'; }
        $h .= '<div style="margin-top:18px"><a class="btn btn-ghost" href="' . esc_url( sahel_page_url( 'about-us' ) ) . '">بیشتر درباره ' . esc_html( $brand_short ) . ' ←</a></div>';
        $h .= '</div></div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'insta' );
    if ( $sec['on'] ) {
        $ig = get_theme_mod( 'sahel_instagram', '#' );
        $txt = get_theme_mod( 'sahel_insta_text', 'ما را دنبال کنید.' );
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="insta-strip rv"><div><h3>📸 ما را در اینستاگرام دنبال کنید</h3><p>' . esc_html( $txt ) . '</p></div><a class="btn btn-primary" href="' . esc_url( $ig ) . '" target="_blank" rel="noopener">دنبال کردن</a></div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'news' );
    if ( $sec['on'] ) {
        $bg = sahel_sec_bg_attr( $sec );
        $h = '<section style="padding-top:10px"' . ( $sec['full'] ? ' class="sec-full ' . $bg['class'] . '"' : ' class="' . $bg['class'] . '"' ) . $bg['style'] . ( $sec['btn_color'] ? ' data-btn-color="' . esc_attr( $sec['btn_color'] ) . '"' : '' ) . ( $sec['btn_text'] ? ' data-btn-text="' . esc_attr( $sec['btn_text'] ) . '"' : '' ) . '>';
        $h .= $bg['overlay'];
        $h .= '<div class="wrap"><div class="news rv"><h2>' . ( $sec['title'] ? esc_html( $sec['title'] ) : 'عضو <span class="grad-text">باشگاه ' . esc_html( $brand ) . '</span> شو 🤎' ) . '</h2><p>' . ( $sec['sub'] ? esc_html( $sec['sub'] ) : 'اولین نفری باش که از کالکشن‌های جدید باخبر می‌شه' ) . '</p><form><input type="tel" placeholder="شماره موبایل" required><button class="btn btn-primary" type="submit">عضویت</button></form></div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    usort( $parts, function( $a, $b ) { return $a[0] <=> $b[0]; } );
    foreach ( $parts as $p ) { $ob .= $p[1]; }
    return $ob;
}

add_filter( 'template_include', 'sahel_engine', 999 );
function sahel_engine( $template ) {
    if ( is_admin() || isset( $_GET['elementor-preview'] ) ) { return $template; }
    $t = '';
    if ( is_front_page() ) { $t = 'home'; }
    elseif ( function_exists( 'WC' ) && ( is_shop() || is_product_taxonomy() ) ) { $t = 'archive'; }
    elseif ( function_exists( 'WC' ) && is_product() ) { $t = 'product'; }
    elseif ( is_404() ) { $t = '404'; }
    elseif ( is_page() ) { $t = 'page'; }
    elseif ( is_single() && 'post' === get_post_type() ) { $t = 'post'; }
    if ( ! $t ) { return $template; }
    ob_start();
    try {
        if ( 'home' === $t ) { sahel_shell( sahel_home_html() ); }
        elseif ( 'archive' === $t ) {
            $brand = sahel_brand();
            $h = '<main class="wrap shop-main"><nav class="crumbs">' . woocommerce_breadcrumb( array( 'echo' => false ) ) . '</nav>';
            $banner = get_theme_mod( 'sahel_shop_banner', '' );
            if ( $banner ) {
                $h .= '<div class="page-banner rv in"><img src="' . esc_url( $banner ) . '" alt=""><div class="pb-ovl"></div><div class="pb-txt"><h1>' . esc_html( get_theme_mod( 'sahel_shop_title', 'فروشگاه ' . $brand ) ) . '</h1><p>' . esc_html( get_theme_mod( 'sahel_shop_sub', 'استایل تو، امضای تو' ) ) . '</p></div></div>';
            }
            if ( get_theme_mod( 'sahel_shop_filters', 1 ) ) {
                $h .= '<div class="catbar"><a class="' . ( is_shop() ? 'active' : '' ) . '" href="' . esc_url( sahel_shop_url() ) . '">همه</a>';
                foreach ( sahel_product_cats_tree() as $node ) { $c = $node['term']; $h .= '<a class="' . ( is_product_category( $c->slug ) ? 'active' : '' ) . '" href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a>'; }
                if ( is_product_category() ) {
                    $cur = get_queried_object();
                    $kids = get_terms( 'product_cat', array( 'parent' => $cur->term_id, 'hide_empty' => false ) );
                    if ( $kids && ! is_wp_error( $kids ) ) { foreach ( $kids as $k ) { $h .= '<a class="catbar-sub' . ( is_product_category( $k->slug ) ? ' active' : '' ) . '" href="' . esc_url( get_term_link( $k ) ) . '">↳ ' . esc_html( $k->name ) . '</a>'; } }
                }
                $h .= '</div>';
                ob_start(); woocommerce_result_count(); woocommerce_catalog_ordering();
                $h .= '<div class="shop-toolbar">' . ob_get_clean();
                if ( get_theme_mod( 'sahel_shop_stock', 1 ) ) {
                    $on = isset( $_GET['mstock'] ) && $_GET['mstock'] === 'in';
                    if ( $on ) { $h .= '<a class="stock-toggle on" href="' . esc_url( remove_query_arg( 'mstock' ) ) . '">✔ فقط کالاهای موجود</a>'; }
                    else { $h .= '<a class="stock-toggle" href="' . esc_url( add_query_arg( 'mstock', 'in' ) ) . '">فقط کالاهای موجود</a>'; }
                }
                $h .= '<div class="col-switch"><span>ستون‌ها:</span><button data-cols="3">۳</button><button data-cols="4">۴</button><button data-cols="5">۵</button></div></div>';
            }
            if ( woocommerce_product_loop() ) {
                $h .= '<ul class="products">';
                while ( have_posts() ) { the_post(); ob_start(); wc_get_template_part( 'content', 'product' ); $h .= ob_get_clean(); }
                $h .= '</ul>';
                ob_start(); woocommerce_pagination(); $h .= '<div class="shop-pagination">' . ob_get_clean() . '</div>';
            } else {
                $h .= '<div class="empty-state"><p>محصولی یافت نشد 😕</p><a class="btn btn-ghost" href="' . esc_url( sahel_shop_url() ) . '">مشاهده همه محصولات</a></div>';
            }
            $h .= '</main>';
            sahel_shell( $h );
        } elseif ( 'product' === $t ) {
            $brand_short = sahel_brand_short();
            $h = '<main class="wrap pd-main"><nav class="crumbs">' . woocommerce_breadcrumb( array( 'echo' => false ) ) . '</nav>';
            while ( have_posts() ) {
                the_post();
                $p = wc_get_product( get_the_ID() );
                $h .= '<div class="pd-wrap"><div class="pd-gallery">';
                ob_start(); woocommerce_show_product_images(); $h .= ob_get_clean();
                $h .= '</div><div class="pd-summary"><div class="pd-cats">' . wc_get_product_category_list( $p->get_id() ) . '</div>';
                ob_start(); woocommerce_template_single_title(); woocommerce_template_single_rating(); $h .= ob_get_clean();
                $h .= '<div class="pd-pricebox"><span class="price">' . $p->get_price_html() . '</span></div>';
                $h .= '<div class="pd-excerpt">' . wp_kses_post( wpautop( $p->get_short_description() ) ) . '</div>';
                ob_start(); woocommerce_template_single_add_to_cart(); $h .= ob_get_clean();
                if ( get_theme_mod( 'sahel_product_trust', 1 ) ) {
                    $h .= '<div class="pd-trust"><span>✔ ضمانت اصالت و دوخت</span><span>🚚 ارسال سریع</span><span>↩ ۷ روز مهلت بازگشت</span><span>🔒 پرداخت امن</span></div>';
                }
                ob_start(); woocommerce_template_single_meta(); $h .= ob_get_clean();
                $h .= '</div></div>';
                if ( get_theme_mod( 'sahel_product_tabs', 1 ) ) {
                    $h .= '<div class="pd-tabs">'; ob_start(); woocommerce_output_product_data_tabs(); $h .= ob_get_clean(); $h .= '</div>';
                }
                if ( get_theme_mod( 'sahel_product_related', 1 ) ) {
                    $h .= '<div class="pd-related"><h3 class="pd-related-title">محصولات <span>مرتبط</span> ' . esc_html( $brand_short ) . '</h3>';
                    ob_start(); woocommerce_output_related_products(); $h .= ob_get_clean(); $h .= '</div>';
                }
            }
            $h .= '</main>';
            sahel_shell( $h );
        } elseif ( '404' === $t ) {
            $brand = sahel_brand();
            sahel_shell( '<main class="wrap" style="padding:80px 24px"><div class="empty-state"><p style="font-size:3rem;font-weight:900">۴۰۴</p><p>' . esc_html( get_theme_mod( 'sahel_404_text', 'صفحه پیدا نشد!' ) ) . '</p><a class="btn btn-primary" href="' . esc_url( home_url( '/' ) ) . '">بازگشت به خانه ' . esc_html( $brand ) . '</a></div></main>' );
        } elseif ( 'page' === $t ) {
            $el = false;
            if ( class_exists( '\Elementor\Plugin' ) ) { $d = \Elementor\Plugin::$instance->documents->get( get_queried_object_id() ); $el = $d ? $d->is_built_with_elementor() : false; }
            if ( $el ) {
                $h = '<div style="position:relative;z-index:1">';
                while ( have_posts() ) { the_post(); ob_start(); the_content(); $h .= ob_get_clean(); }
                $h .= '</div>';
                sahel_shell( $h );
            } else {
                $brand = sahel_brand();
                $h = '<main class="wrap" style="padding:40px 24px 70px">';
                while ( have_posts() ) {
                    the_post();
                    $slug = get_post_field( 'post_name', get_the_ID() );
                    if ( $slug === 'about-us' || $slug === 'contact-us' ) {
                        $is_about = ( $slug === 'about-us' );
                        $img = $is_about ? get_theme_mod( 'sahel_about_image', '' ) : get_theme_mod( 'sahel_contact_image', '' );
                        $h .= '<div class="page-hero rv in">';
                        if ( $img ) { $h .= '<div class="page-hero-img"><img src="' . esc_url( $img ) . '" alt=""></div>'; }
                        else { $h .= '<div class="logo3d"><div class="logo3d-glow"></div><div class="logo3d-ring"></div><div class="logo3d-float"><img class="main" src="' . esc_url( sahel_logo_url() ) . '" alt=""></div><img class="refl" src="' . esc_url( sahel_logo_url() ) . '" alt="" aria-hidden="true"></div>'; }
                        $h .= '<div class="page-hero-txt"><h1>' . ( $is_about ? 'درباره <span class="grad-text">' . esc_html( $brand ) . '</span>' : 'تماس با <span class="grad-text">' . esc_html( $brand ) . '</span>' ) . '</h1><p>' . ( $is_about ? 'داستان و ارزش‌های ' . esc_html( $brand ) . '.' : 'از شما می‌شنویم.' ) . '</p></div>';
                        $h .= '</div>';
                        $h .= '<article class="page-card rv in"><div class="page-content">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
                        if ( ! $is_about ) {
                            $map = get_theme_mod( 'sahel_contact_map', '' );
                            if ( $map ) { $h .= '<div class="contact-grid"><div class="map-box">' . $map . '</div></div>'; }
                        }
                        $h .= '</article>';
                    } elseif ( $slug === 'blog' ) {
                        $h .= '<div class="sec-head"><div><h2>مقالات <span>' . esc_html( $brand ) . '</span></h2><p>راهنمای انتخاب</p></div></div><div class="blog-grid">';
                        $posts = get_posts( array( 'post_type' => 'post', 'numberposts' => 9 ) );
                        if ( $posts ) {
                            foreach ( $posts as $post ) {
                                $thumb = get_the_post_thumbnail( $post->ID, 'medium_large' );
                                if ( ! $thumb ) { $thumb = '<img src="https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png" alt="">'; }
                                $h .= '<article class="post-card rv">' . $thumb . '<div class="pb"><span class="pdate">' . sahel_fa( get_the_date( 'Y/m/d', $post ) ) . '</span><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( wp_trim_words( $post->post_excerpt ? $post->post_excerpt : $post->post_content, 18 ) ) . '</p><a class="more" href="' . get_permalink( $post ) . '">ادامه مطلب ←</a></div></article>';
                            }
                        } else {
                            $h .= '<p style="color:var(--muted);font-weight:700">هنوز مطلبی منتشر نشده است.</p>';
                        }
                        $h .= '</div>';
                    } else {
                        $h .= '<article class="page-card rv in"><h1 class="page-title">' . get_the_title() . '</h1><div class="page-content">' . apply_filters( 'the_content', get_the_content() ) . '</div></article>';
                    }
                }
                $h .= '</main>';
                sahel_shell( $h );
            }
        } else {
            $h = '<main class="wrap" style="padding:40px 24px 70px">';
            while ( have_posts() ) { the_post(); $h .= '<article class="page-card rv in"><h1 class="page-title">' . get_the_title() . '</h1><div class="page-content">' . apply_filters( 'the_content', get_the_content() ) . '</div></article>'; }
            $h .= '</main>';
            sahel_shell( $h );
        }
        $html = ob_get_clean();
        echo $html;
        exit;
    } catch ( \Throwable $e ) {
        ob_end_clean();
        echo '<html dir="rtl"><body style="font-family:Tahoma;padding:30px;background:#faeae5;color:#c65a3f"><h2>خطای قالب:</h2><pre dir="ltr" style="background:#fff;padding:14px;border-radius:10px">' . esc_html( $e->getMessage() ) . ' — خط ' . esc_html( $e->getLine() ) . '</pre></body></html>';
        exit;
    }
}

add_action( 'customize_register', 'sahel_extra_styles_register' );
function sahel_extra_styles_register( $w ) {
    $w->add_setting( 'sahel_radius', array( 'default' => 22, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_radius', array( 'label' => 'شعاع گوشه', 'section' => 'sahel_styles', 'type' => 'number' ) );
}
add_action( 'wp_head', function() {
    $rad = (int) get_theme_mod( 'sahel_radius', 22 );
    echo '<style id="sahel-extra">:root{--rad:' . $rad . 'px}
.btn{border-radius:var(--rad)}
.prod-card,.cat-card,.post-card,.feat{border-radius:var(--rad)}
.page-card{border-radius:calc(var(--rad) + 6px)}
.dd-panel{border-radius:calc(var(--rad) + 4px)}
.news{border-radius:calc(var(--rad) + 10px)}
.offer,.page-banner{border-radius:calc(var(--rad) + 6px)}
.pd-wrap{border-radius:calc(var(--rad) + 10px)}
.pd-tabs{border-radius:calc(var(--rad) + 6px)}
.icon-btn{border-radius:calc(var(--rad) * .7)}
.sh-search form,.so-box input[type=search],.news input{border-radius:calc(var(--rad) * .8)}
.sh-mnav nav>a,.sh-close{border-radius:var(--rad)}
.lv-card{border-radius:var(--rad)}
body{font-size:var(--fs,16px)}
h1,h2,h3,h4,h5,h6,.btn,.brand-name,.prod-name,.cat-card h3{font-family:var(--font-head,var(--font))}
body.btnst-pill .btn{border-radius:999px}
body.btnst-round .btn{border-radius:var(--rad)}
body.btnst-square .btn{border-radius:6px}
body.btnst-outline .btn-primary{background:transparent;color:var(--caramel);border:2px solid var(--caramel);border-radius:var(--rad)}
body.btnst-outline .btn-primary:hover{background:var(--caramel);color:var(--btnt)}
body.btnst-soft .btn-primary{background:color-mix(in srgb,var(--c2) 25%,#fff);color:color-mix(in srgb,var(--c1) 80%,#000);border:1px solid var(--line2);border-radius:var(--rad)}
body.btnst-glass .btn-primary{background:color-mix(in srgb,var(--c1) 18%,rgba(255,255,255,.4));backdrop-filter:blur(10px);color:color-mix(in srgb,var(--c1) 85%,#000);border:1px solid color-mix(in srgb,var(--c1) 35%,transparent);border-radius:var(--rad)}
body.btnst-neon .btn-primary{background:transparent;color:var(--c1);border:2px solid var(--c1);box-shadow:0 0 18px color-mix(in srgb,var(--c1) 45%,transparent);border-radius:var(--rad)}
body.btnst-neon .btn-primary:hover{background:var(--c1);color:var(--btnt)}
body.catst-5 .cat-card{background:rgba(255,255,255,.55);backdrop-filter:blur(12px);border-color:rgba(255,255,255,.7)}
body.catst-6 .cat-card{border:none;box-shadow:none;background:transparent}
body.catst-6 .cat-card:hover{box-shadow:var(--shadow-lg);background:var(--cardbg)}
body.catst-7 .cat-card{background:rgba(255,255,255,.45);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.65);box-shadow:0 8px 32px color-mix(in srgb,var(--c1) 16%,transparent)}
body.catst-7 .cat-card:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 20px 50px color-mix(in srgb,var(--c1) 30%,transparent);border-color:var(--line2)}
body.catst-7 .cat-card::after{content:"";position:absolute;inset:0;border-radius:inherit;background:linear-gradient(135deg,color-mix(in srgb,var(--c2) 28%,transparent),transparent 55%);pointer-events:none;z-index:1}
body.catst-8 .cat-card{background:transparent;border:none;box-shadow:none;padding:0 0 4px}
body.catst-8 .cat-card img{border-radius:50%;aspect-ratio:1/1;border:5px solid transparent;background:linear-gradient(var(--bg),var(--bg)) padding-box,linear-gradient(140deg,var(--c2),var(--c1)) border-box;transition:.4s cubic-bezier(.4,0,.2,1)}
body.catst-8 .cat-card:hover img{transform:scale(1.06) rotate(4deg);box-shadow:0 14px 34px color-mix(in srgb,var(--c1) 35%,transparent)}
body.catst-9 .cat-card{perspective:700px;transform-style:preserve-3d;will-change:transform}
body.catst-9 .cat-card img{transform:translateZ(18px)}
body.catst-2 .cat-card{position:relative;padding:0;overflow:hidden;height:220px}
body.catst-2 .cat-card img{position:absolute;inset:0;width:100%;height:100%;border-radius:22px;border:none}
body.catst-2 .cat-card .ovl{display:block;position:absolute;inset:0;background:linear-gradient(to top,rgba(10,8,6,.8),rgba(10,8,6,.15) 55%,transparent);border-radius:22px}
body.catst-2 .cat-card .txt{position:absolute;inset-inline:0;bottom:0;padding:16px;text-align:start;z-index:2}
body.catst-2 .cat-card h3{color:#fff}
body.catst-2 .cat-card p{background:rgba(255,255,255,.18);color:#fff;backdrop-filter:blur(6px)}
body.catst-3 .cat-card{flex:0 0 280px;width:280px;display:flex;align-items:center;gap:14px;padding:10px}
body.catst-3 .cat-card img{width:90px;height:90px;flex-shrink:0;aspect-ratio:1/1}
body.catst-3 .cat-card .txt{padding:0;text-align:start;flex:1}
body.catst-10 .cat-grid{flex-direction:column;overflow:visible;mask-image:none;-webkit-mask-image:none}
body.catst-10 .cat-card{flex:none;width:100%;display:flex;align-items:center;gap:18px;padding:14px 20px}
body.catst-10 .cat-card img{width:170px;height:100px;flex-shrink:0;border-radius:14px}
body.catst-10 .cat-card .txt{position:static;padding:0;text-align:start;flex:1}
body.catst-10 .cat-card h3{font-size:1rem}
body.catst-11 .cat-card{border:none;box-shadow:none;background:color-mix(in srgb,var(--c2) 14%,transparent);border-radius:20px}
body.catst-11 .cat-card img{border-radius:14px;border:none;background:transparent}
body.catst-11 .cat-card:hover{background:color-mix(in srgb,var(--c2) 26%,transparent);transform:translateY(-4px)}
body.catst-11 .cat-card p{background:#fff}
body.catst-12 .cat-card{background:transparent;border:1.5px solid var(--line2);box-shadow:none;border-radius:18px}
body.catst-12 .cat-card img{border-radius:12px;border:none}
body.catst-12 .cat-card:hover{border-color:var(--c1);transform:none;box-shadow:0 8px 20px color-mix(in srgb,var(--c1) 12%,transparent)}
body.catst-12 .cat-card p{background:transparent;padding:3px 0;color:var(--muted)}
body.catst-13 .cat-card{background:var(--cardbg);border:none;box-shadow:0 4px 20px color-mix(in srgb,var(--ink) 6%,transparent);border-radius:26px;padding:14px;overflow:visible}
body.catst-13 .cat-card img{border-radius:18px}
body.catst-13 .cat-card .txt{position:relative;padding:12px 0 0;text-align:center}
body.catst-13 .cat-card p{position:absolute;top:-32px;inset-inline-end:6px;background:var(--grad);color:#fff;box-shadow:var(--shadow)}
body.prodst-5 .prod-card{background:rgba(255,255,255,.6);backdrop-filter:blur(12px);border-color:rgba(255,255,255,.7)}
body.prodst-6 .prod-card{border:1px solid var(--c1);box-shadow:none}
body.prodst-6 .prod-card:hover{box-shadow:0 0 22px color-mix(in srgb,var(--c1) 35%,transparent)}
body.prodst-7 .prod-media{margin:0;aspect-ratio:1/1;border-radius:var(--rad) var(--rad) 0 0}
body.prodst-7 .prod-card{border-radius:var(--rad)}
body.prodst-8 .prod-card{background:rgba(255,255,255,.5);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.7);box-shadow:0 8px 30px color-mix(in srgb,var(--ink) 8%,transparent)}
body.prodst-8 .prod-card:hover{transform:translateY(-8px) scale(1.01);box-shadow:0 24px 60px color-mix(in srgb,var(--c1) 28%,transparent)}
body.prodst-8 .prod-media::after{content:"";position:absolute;inset:0;background:linear-gradient(160deg,rgba(255,255,255,.4),transparent 45%);pointer-events:none}
body.prodst-9 .prod-card{background:color-mix(in srgb,var(--footerbg) 93%,#fff);border:1px solid color-mix(in srgb,var(--c1) 40%,transparent);box-shadow:none}
body.prodst-9 .prod-card:hover{border-color:var(--c1);box-shadow:0 0 22px color-mix(in srgb,var(--c1) 40%,transparent),0 0 60px color-mix(in srgb,var(--c1) 16%,transparent);transform:translateY(-6px)}
body.prodst-9 .prod-name a{color:#fff}
body.prodst-9 .prod-cat{color:var(--sand)}
body.prodst-9 .prod-desc{color:rgba(255,255,255,.55)}
body.prodst-9 .prod-price .amount,body.prodst-9 .prod-price ins{color:var(--sand)}
body.prodst-9 .prod-price del{color:rgba(255,255,255,.45)}
body.prodst-9 .prod-media{background:rgba(255,255,255,.05)}
body.prodst-9 .badge.hot{background:rgba(255,255,255,.12);color:var(--sand);border-color:color-mix(in srgb,var(--c2) 50%,transparent)}
body.prodst-9 .badge.new,body.prodst-9 .badge.best{background:rgba(255,255,255,.1);color:var(--cream);border-color:rgba(255,255,255,.25)}
body.prodst-10 .prod-card{border:none}
body.prodst-10 .prod-media{overflow:hidden}
body.prodst-10 .prod-media::after{content:"";position:absolute;top:0;bottom:0;width:45%;left:-70%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-20deg);transition:left .65s ease;pointer-events:none}
body.prodst-10 .prod-card:hover .prod-media::after{left:130%}
body.prodst-10 .prod-card:hover{transform:translateY(-10px);box-shadow:var(--shadow-lg)}
body.prodst-11 .prod-card{perspective:700px;transform-style:preserve-3d;will-change:transform}
body.prodst-11 .prod-media{transform:translateZ(22px)}
body.prodst-12 .prod-body{position:relative;padding-top:26px}
body.prodst-12 .prod-body a.button{position:absolute!important;top:-24px!important;inset-inline-end:14px!important;width:46px!important;height:46px!important;border-radius:50%!important;background-color:#fff!important;box-shadow:0 10px 26px color-mix(in srgb,var(--ink) 22%,transparent)!important;border:1px solid var(--line)!important}
body.prodst-12 .prod-body a.button:hover{transform:translateY(-4px) scale(1.08)!important}
body.prodst-12 .prod-price{align-self:center}
body.prodst-13 .prod-card{background:transparent;border:none;box-shadow:none;border-radius:0}
body.prodst-13 .prod-card:hover{transform:none;box-shadow:none}
body.prodst-13 .prod-media{margin:0;border-radius:16px}
body.prodst-13 .prod-body{padding:12px 2px}
body.prodst-13 .prod-name{font-size:.84rem}
body.prodst-13 .prod-body a.button{background-color:var(--ink)!important;border:none!important;border-radius:10px!important}
body.prodst-14 .prod-card{border-radius:14px}
body.prodst-14 .prod-media{border-radius:10px}
body.prodst-14 .badge{border-radius:0;clip-path:polygon(0 0,100% 0,100% 100%,15% 100%);padding:6px 16px 6px 10px}
body.prodst-14 .prod-body a.button{border-radius:8px!important}
body.prodst-15 .prod-card{border:none;box-shadow:var(--shadow);border-radius:26px;overflow:visible}
body.prodst-15 .prod-media{margin:0;border-radius:26px 26px 0 0}
body.prodst-15 .prod-body{padding:14px 16px 26px}
body.prodst-15 .prod-cat,body.prodst-15 .prod-vars{display:none}
body.prodst-15 .prod-name{font-size:.88rem;margin-bottom:2px}
body.prodst-15 .prod-desc{-webkit-line-clamp:2;min-height:0}
body.prodst-15 .prod-price{width:100%;margin-top:2px}
body.prodst-15 .prod-body a.button{position:absolute!important;bottom:-16px!important;inset-inline-start:16px!important;margin:0!important;width:46px!important;height:46px!important;border-radius:50%!important;background-color:color-mix(in srgb,var(--cream) 55%,#fff)!important;border:1px solid var(--line)!important;box-shadow:var(--shadow-lg)!important}
body.prodst-15 .prod-body a.button:hover{transform:translateY(-4px)!important}
body.prodst-15 .badge.variant{display:inline-block;background:color-mix(in srgb,#2f6fed 12%,#fff);color:#2f6fed;border:1px solid color-mix(in srgb,#2f6fed 30%,transparent)}
body.prodst-2 .prod-card{border:none;box-shadow:none;background:transparent}
body.prodst-2 .prod-card:hover{box-shadow:none;transform:none}
body.prodst-2 .prod-card:hover .prod-media img{transform:scale(1.04)}
body.prodst-2 .prod-media{margin:0;border-radius:12px}
body.prodst-2 .prod-body{padding:12px 2px}
body.prodst-3 .prod-card{border:1px solid var(--line);border-top:3px solid var(--c1);border-radius:10px}
body.prodst-3 .prod-card:hover{border-top-color:var(--c2)}
body.prodst-4 .prod-card{background:color-mix(in srgb,var(--ink) 82%,transparent);backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.12);box-shadow:0 14px 34px rgba(0,0,0,.35)}
body.prodst-4 .prod-card:hover{border-color:rgba(255,255,255,.25);box-shadow:0 20px 46px rgba(0,0,0,.45)}
body.prodst-4 .prod-name a{color:#fff}
body.prodst-4 .prod-cat{color:var(--sand)}
body.prodst-4 .prod-desc{color:rgba(255,255,255,.55)}
body.prodst-4 .prod-price .amount,body.prodst-4 .prod-price ins{color:#fff}
body.prodst-4 .prod-price del{color:rgba(255,255,255,.4)}
body.prodst-4 .prod-media{background:rgba(255,255,255,.06)}
body.postst-2 .post-card{border:none;box-shadow:none;background:transparent;border-radius:0}
body.postst-2 .post-card:hover{box-shadow:none}
body.postst-2 .post-card img{border-radius:14px}
body.postst-2 .post-card .pb{padding:14px 2px}
body.postst-3 .post-card{position:relative;min-height:280px;display:flex;flex-direction:column;justify-content:flex-end}
body.postst-3 .post-card img{position:absolute;inset:0;width:100%;height:100%;aspect-ratio:auto}
body.postst-3 .post-card::before{content:"";position:absolute;inset:0;background:linear-gradient(to top,rgba(10,8,6,.85),rgba(10,8,6,.15) 55%,transparent);z-index:1}
body.postst-3 .post-card .pb{position:relative;z-index:2}
body.postst-3 .post-card h3,body.postst-3 .post-card .more{color:#fff}
body.postst-3 .post-card p{color:rgba(255,255,255,.75)}
body.postst-3 .post-card .pdate{color:rgba(255,255,255,.6)}
body.postst-4 .post-card{display:flex;align-items:stretch}
body.postst-4 .post-card img{width:180px;flex-shrink:0;aspect-ratio:1/1;height:auto}
body.postst-4 .post-card .pb{flex:1}
@media(max-width:640px){body.postst-4 .post-card{flex-direction:column}body.postst-4 .post-card img{width:100%;aspect-ratio:16/9}}
body.postst-5 .post-card{background:transparent;border:none;box-shadow:none;border-radius:0;display:flex;align-items:center;gap:14px;padding-bottom:16px;border-bottom:1px solid var(--line)}
body.postst-5 .post-card img{width:64px;height:64px;flex-shrink:0;border-radius:50%;aspect-ratio:1/1}
body.postst-5 .post-card .pb{padding:0;flex:1;gap:4px}
body.postst-5 .post-card h3{font-size:.82rem}
body.postst-5 .post-card:hover{transform:none}
body.postst-6 .post-card{border:none;box-shadow:var(--shadow-lg);border-radius:24px}
body.postst-6 .post-card img{aspect-ratio:4/3}
body.postst-6 .post-card .pdate{display:inline-block;background:var(--grad);color:#fff;padding:3px 12px;border-radius:99px;font-weight:800;width:fit-content}
body.postst-6 .post-card .pb{padding:16px}
body.postst-6 .post-card:hover{transform:translateY(-6px)}
.campaign{position:relative;overflow:hidden;border-radius:28px;background:var(--grad);padding:46px 30px;text-align:center;color:#fff;box-shadow:var(--shadow-lg)}
.campaign::before{content:"";position:absolute;inset:0;background:radial-gradient(520px 260px at 80% 0%,rgba(255,255,255,.25),transparent 60%),radial-gradient(400px 220px at 10% 100%,rgba(255,255,255,.12),transparent 60%)}
.campaign h2{font-size:clamp(1.3rem,3vw,2rem);font-weight:900;position:relative}
.campaign p{opacity:.92;margin:10px auto 22px;position:relative;max-width:600px;line-height:2}
.campaign .btn{position:relative}
.look-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.look-item{position:relative;border-radius:22px;overflow:hidden;aspect-ratio:3/4;display:block;box-shadow:var(--shadow)}
.look-item img{width:100%;height:100%;object-fit:cover;transition:.7s cubic-bezier(.4,0,.2,1)}
.look-item:hover img{transform:scale(1.08)}
.look-item .lk-cap{position:absolute;bottom:0;inset-inline:0;padding:34px 16px 14px;background:linear-gradient(to top,rgba(15,10,6,.85),transparent);color:#fff;font-weight:800;font-size:.85rem;opacity:0;transform:translateY(10px);transition:.35s cubic-bezier(.4,0,.2,1)}
.look-item:hover .lk-cap{opacity:1;transform:none}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.testi{background:var(--cardbg);border:1px solid var(--line);border-radius:22px;padding:22px;box-shadow:var(--shadow);transition:.35s cubic-bezier(.4,0,.2,1)}
.testi:hover{transform:translateY(-6px) scale(1.02);box-shadow:var(--shadow-lg);border-color:var(--line2)}
.testi .stars{color:var(--c2);letter-spacing:3px;font-size:.95rem;margin-bottom:10px}
.testi p{color:var(--muted);line-height:2;font-size:.84rem}
.testi .who{display:flex;align-items:center;gap:10px;margin-top:14px}
.testi .av{width:42px;height:42px;border-radius:50%;background:var(--grad);color:#fff;display:grid;place-items:center;font-weight:900;flex-shrink:0}
.testi .who b{display:block;font-size:.82rem}
.testi .who span{color:var(--muted);font-size:.68rem}
.faq-list{display:grid;gap:12px;max-width:840px;margin:0 auto}
.faq-item{background:var(--cardbg);border:1px solid var(--line);border-radius:18px;overflow:hidden;transition:.25s cubic-bezier(.4,0,.2,1)}
.faq-item summary{cursor:pointer;padding:16px 20px;font-weight:800;font-size:.9rem;display:flex;justify-content:space-between;align-items:center;gap:10px;list-style:none}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::after{content:"+";font-size:1.4rem;color:var(--caramel);transition:.25s cubic-bezier(.4,0,.2,1);line-height:1}
.faq-item[open] summary::after{transform:rotate(45deg)}
.faq-item .fa-a{padding:0 20px 16px;color:var(--muted);line-height:2;font-size:.84rem}
.faq-item[open]{border-color:var(--line2);box-shadow:var(--shadow)}
.insta-strip{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;background:var(--cardbg);border:1px solid var(--line);border-radius:24px;padding:26px 30px;box-shadow:var(--shadow)}
section.sec-al-center .insta-strip{justify-content:center;text-align:center}
section.sec-al-left .insta-strip{flex-direction:row-reverse}
.insta-strip h3{font-weight:900;font-size:1.05rem}
.insta-strip p{color:var(--muted);font-size:.8rem;margin-top:6px}
@media(max-width:800px){.look-grid{grid-template-columns:repeat(2,1fr)}.testi-grid{grid-template-columns:1fr}}
@media(max-width:700px){.insta-strip{justify-content:center;text-align:center}body.catst-10 .cat-card img{width:110px;height:80px}}
</style>';
}, 99 );

add_action( 'wp_head', function() {
    $hop = (int) get_theme_mod( 'sahel_header_opacity', 82 );
    echo '<style id="sahel-v41">
#header{background:color-mix(in srgb,var(--headerbg) ' . $hop . '%,transparent)}
.slide .slide-ovl{background:linear-gradient(to left,rgba(var(--ovl-rgb,250,247,242),calc(var(--ovl-o,.95)*.95)) 14%,rgba(var(--ovl-rgb,250,247,242),calc(var(--ovl-o,.95)*.72)) 38%,rgba(var(--ovl-rgb,250,247,242),calc(var(--ovl-o,.95)*.18)) 68%,rgba(var(--ovl-rgb,250,247,242),0) 100%)}
@media(max-width:920px){
.slide .slide-ovl{background:linear-gradient(to top,rgba(var(--ovl-rgb,250,247,242),var(--ovl-o,.95)) 30%,rgba(var(--ovl-rgb,250,247,242),calc(var(--ovl-o,.95)*.75)) 58%,rgba(var(--ovl-rgb,250,247,242),calc(var(--ovl-o,.95)*.15)) 100%)}
}
</style>';
}, 96 );

add_action( 'wp_head', function() {
    echo '<style id="sahel-hstyle">
body.hstyle-modern .header-inner{flex-wrap:wrap;justify-content:center;row-gap:8px;padding-block:10px}
body.hstyle-modern .brand{order:1;flex:0 0 100%;justify-content:center}
body.hstyle-modern .bnav{order:3;flex:0 0 100%;justify-content:center;margin-inline-start:0}
body.hstyle-modern .hact{order:2;flex:0 0 100%;justify-content:center}
body.hstyle-minimal .hact .sh-search{display:none}
body.hstyle-minimal .hact .login-btn{display:none}
body.hstyle-minimal .bnav{margin-inline-end:auto;margin-inline-start:0}
body.hstyle-glass #header{background:color-mix(in srgb,var(--headerbg) 45%,transparent);backdrop-filter:blur(28px) saturate(1.8);-webkit-backdrop-filter:blur(28px) saturate(1.8);border-bottom-color:color-mix(in srgb,#fff 35%,transparent);box-shadow:0 8px 32px color-mix(in srgb,var(--c1) 14%,transparent)}
body.hstyle-compact .header-inner{padding-block:6px}
body.hstyle-compact .brand-logo img,body.hstyle-compact .brand img{height:38px}
body.hstyle-compact .brand-name{font-size:1rem}
body.hstyle-compact .bnav a,body.hstyle-compact .dd-toggle{padding:6px 12px;font-size:.82rem}
body.hstyle-compact .icon-btn{width:38px;height:38px}
body.hstyle-compact .sh-search{padding:6px 10px}
.topbar{font-size:.78rem;text-align:center;padding:7px 12px}
.topbar .wrap{padding:0 24px}
body.hstyle-bold #header{background:var(--grad)!important;backdrop-filter:none;-webkit-backdrop-filter:none;border-bottom:none;box-shadow:0 8px 24px color-mix(in srgb,var(--c1) 30%,transparent)}
body.hstyle-bold .brand-name,body.hstyle-bold .brand-name small,body.hstyle-bold .bnav>a,body.hstyle-bold .dd-toggle{color:#fff}
body.hstyle-bold .bnav>a:hover,body.hstyle-bold .dd-toggle:hover{background:rgba(255,255,255,.18);color:#fff}
body.hstyle-bold .icon-btn svg{stroke:#fff}
body.hstyle-bold .icon-btn:hover{background:rgba(255,255,255,.18)}
body.hstyle-bold .sh-search form{background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.3)}
body.hstyle-bold .sh-search input[type=search]{color:#fff}
body.hstyle-bold .sh-search input[type=search]::placeholder{color:rgba(255,255,255,.75)}
body.hstyle-underline .bnav>a,body.hstyle-underline .dd-toggle{background:none!important;border-radius:0;position:relative}
body.hstyle-underline .bnav>a::after,body.hstyle-underline .dd-toggle::after{content:"";position:absolute;bottom:2px;right:50%;left:50%;height:2px;background:var(--caramel);transition:.3s cubic-bezier(.4,0,.2,1)}
body.hstyle-underline .bnav>a:hover::after,body.hstyle-underline .dd-toggle:hover::after,body.hstyle-underline .dd.open .dd-toggle::after{right:14%;left:14%}
body.hstyle-border #header{border-bottom:3px solid var(--ink);backdrop-filter:none;-webkit-backdrop-filter:none}
body.hstyle-sidebar .bnav{display:none}
body.hstyle-sidebar .sh-burger{display:grid}
body.hstyle-sidebar .header-inner{justify-content:space-between}
body.hstyle-boxed{padding-top:14px}
body.hstyle-boxed #header{position:sticky;top:14px;max-width:1260px;margin:0 auto;border-radius:24px;border:1px solid var(--line);box-shadow:var(--shadow-lg)}
body.hstyle-boxed .header-inner{padding-block:8px}
@media(max-width:920px){
body.hstyle-boxed{padding-top:0}
body.hstyle-boxed #header{position:sticky;top:0;border-radius:0;border:none;box-shadow:none}
body.hstyle-modern .header-inner{padding-block:8px}
.topbar{font-size:.7rem;padding:6px 10px}
}
</style>';
}, 97 );

add_action( 'wp_head', function() {
echo '<style id="sahel-cart-fix">
#cartDrawer{width:min(380px,94vw)}
#cartDrawer .drawer-body{overflow-y:auto;max-height:calc(100vh - 150px);padding:12px}
#cartDrawer ul.cart_list,#cartDrawer ul.product_list_widget,#cartDrawer .drawer-body ul{display:flex!important;flex-direction:column;gap:8px;margin:0;padding:0;list-style:none}
#cartDrawer ul.cart_list li,#cartDrawer .drawer-body li{display:flex!important;align-items:center;gap:10px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:8px}
#cartDrawer ul.cart_list li img,#cartDrawer .drawer-body li img{width:48px!important;height:48px!important;object-fit:cover!important;border-radius:10px;flex-shrink:0}
#cartDrawer ul.cart_list li a:not(.remove),#cartDrawer .drawer-body li a:not(.remove){font-size:.72rem;font-weight:700;line-height:1.5;flex:1;color:var(--ink)}
#cartDrawer ul.cart_list li .quantity,#cartDrawer .drawer-body li .quantity{display:block;font-size:.64rem;color:var(--caramel);font-weight:800}
#cartDrawer ul.cart_list li .remove,#cartDrawer .drawer-body li .remove{margin-inline-start:0;color:var(--hot);font-weight:900;flex-shrink:0}
</style>';
}, 100 );

add_action( 'template_redirect', function() {
    if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
        header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
        header( 'Pragma: no-cache' );
    }
} );
add_filter( 'template_include', function( $template ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        $h = '<main class="wrap" style="padding:40px 24px 70px"><div class="page-card rv in">' . do_shortcode( '[woocommerce_cart]' ) . '</div></main>';
        sahel_shell( $h );
        exit;
    }
    return $template;
}, 998 );

add_filter( 'gettext', function( $translated, $text, $domain ) {
    if ( $domain !== 'woocommerce' ) { return $translated; }
    $map = array(
        'Home' => 'خانه', 'Free shipping' => 'ارسال رایگان', 'Free!' => 'رایگان!',
        'Add to cart' => 'افزودن به سبد', 'View cart' => 'مشاهده سبد', 'Proceed to checkout' => 'ادامه و پرداخت',
        'Checkout' => 'پرداخت', 'Cart' => 'سبد خرید', 'Subtotal' => 'جمع جزء', 'Total' => 'جمع',
        'Quantity' => 'تعداد', 'Remove this item' => 'حذف این مورد',
        'Your cart is currently empty.' => 'سبد خرید شما در حال حاضر خالی است.',
        'No products in the cart.' => 'سبد خرید خالی است.',
        'Return to shop' => 'بازگشت به فروشگاه', 'Continue shopping' => 'ادامه خرید',
        'Shipping' => 'ارسال', 'Description' => 'توضیحات', 'Additional information' => 'اطلاعات تکمیلی',
        'Reviews' => 'دیدگاه‌ها', 'Reviews (%d)' => 'دیدگاه‌ها (%d)', 'Related products' => 'محصولات مرتبط',
        'You may also like…' => 'شاید بپسندید…', 'Out of stock' => 'ناموجود', 'In stock' => 'موجود',
        'Sale!' => 'تخفیف!', 'Select options' => 'انتخاب گزینه‌ها', 'Choose an option' => 'یک گزینه انتخاب کنید',
        'Clear' => 'پاک‌کردن', 'Read more' => 'ادامه مطلب', 'Default sorting' => 'مرتب‌سازی پیش‌فرض',
        'Sort by popularity' => 'محبوب‌ترین', 'Sort by latest' => 'جدیدترین',
        'Sort by price: low to high' => 'قیمت: کم به زیاد', 'Sort by price: high to low' => 'قیمت: زیاد به کم',
        'Showing all %d results' => 'نمایش همه %d نتیجه', 'Showing the single result' => 'نمایش یک نتیجه',
        'Showing %1$d–%2$d of %3$d results' => 'نمایش %1$d–%2$d از %3$d نتیجه',
        'Search results:' => 'نتایج جستجو:', 'No results found' => 'نتیجه‌ای یافت نشد',
        'SKU:' => 'شناسه:', 'N/A' => '—', 'Category:' => 'دسته‌بندی:', 'Categories:' => 'دسته‌بندی‌ها:',
        'Tag:' => 'برچسب:', 'Tags:' => 'برچسب‌ها:', 'Update cart' => 'به‌روزرسانی سبد',
        'Apply coupon' => 'اعمال کد تخفیف', 'Coupon code' => 'کد تخفیف',
        'Have a coupon?' => 'کد تخفیف دارید؟', 'Click here to enter your code' => 'برای واردکردن کد تخفیف اینجا کلیک کنید',
        'Billing details' => 'جزئیات صورتحساب', 'Your order' => 'سفارش شما', 'Place order' => 'ثبت سفارش',
        'Payment method' => 'روش پرداخت', 'Ship to a different address?' => 'ارسال به آدرس دیگر؟',
        'Order notes' => 'یادداشت سفارش', 'First name' => 'نام', 'Last name' => 'نام خانوادگی',
        'Town / City' => 'شهر', 'State / County' => 'استان', 'Postcode / ZIP' => 'کد پستی',
        'Phone' => 'تلفن', 'Email address' => 'آدرس ایمیل', 'Username' => 'نام کاربری',
        'Password' => 'رمز عبور', 'Log in' => 'ورود', 'Register' => 'ثبت‌نام',
        'Lost your password?' => 'رمز عبور را فراموش کرده‌اید؟', 'Remember me' => 'مرا به خاطر بسپار',
        'My account' => 'حساب کاربری', 'Logout' => 'خروج', 'Orders' => 'سفارش‌ها',
        'Downloads' => 'دانلودها', 'Addresses' => 'آدرس‌ها', 'Account details' => 'جزئیات حساب',
        'Dashboard' => 'پیشخوان', 'Add a review' => 'افزودن دیدگاه', 'Submit' => 'ثبت',
        'Save' => 'ذخیره', 'Apply' => 'اعمال',
    );
    if ( isset( $map[ $text ] ) ) { return $map[ $text ]; }
    return $translated;
}, 20, 3 );

add_action( 'wp_footer', function() {
echo '<script>
(function($){
var FA="۰۱۲۳۴۵۶۷۸۹";
function faStr(s){ return String(s).replace(/\d/g,function(d){return FA[d];}); }
function faWalk(root){
if(!root) return;
var w=document.createTreeWalker(root,NodeFilter.SHOW_TEXT,null,false),n,nodes=[];
while(n=w.nextNode()){
var p=n.parentNode,t=p?p.nodeName:"";
if(t==="SCRIPT"||t==="STYLE"||t==="TEXTAREA"||t==="INPUT"||t==="SELECT"||t==="OPTION"||t==="CODE"||t==="PRE") continue;
if(/\d/.test(n.nodeValue)) nodes.push(n);
}
for(var i=0;i<nodes.length;i++){ nodes[i].nodeValue=faStr(nodes[i].nodeValue); }
}
function persianize(){ faWalk(document.body); }
$(document).ready(function(){ persianize(); });
$(document).ajaxComplete(function(){ setTimeout(persianize,120); });
$(document.body).on("added_to_cart",function(){ setTimeout(persianize,150); });
})(jQuery);
</script>';
}, 101 );