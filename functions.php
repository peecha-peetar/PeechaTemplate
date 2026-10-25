<?php
/** Peecha v5.0 — طراحی فلت، بدون کانتینرهای اضافه — فارسی */
if ( ! defined( 'ABSPATH' ) ) exit;

/* قفل دامنه */
// define( 'PEECHA_DOMAINS', array( 'sahelstyle.ir', 'www.sahelstyle.ir' ) );
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

function sahel_brand()       { return get_theme_mod( 'sahel_brand', 'ساحل استایل' ); }
function sahel_brand_short() { return get_theme_mod( 'sahel_brand_short', 'ساحل' ); }
function sahel_brand_en()    { return get_theme_mod( 'sahel_brand_en', 'SAHEL STYLE' ); }
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
function sahel_page_url( $slug ) { $p = get_page_by_path( $slug ); return $p ? get_permalink( $p ) : home_url( '/' ); }
function sahel_top_sale() {
    if ( ! function_exists( 'wc_get_products' ) ) return null;
    $ids = wc_get_products( array( 'limit' => -1, 'status' => 'publish', 'return' => 'ids', 'on_sale' => true ) );
    $best = null; $bp = 0;
    foreach ( (array) $ids as $id ) { $p = wc_get_product( $id ); if ( ! $p ) continue;
        $r = (float) $p->get_regular_price(); $s = $p->get_sale_price();
        if ( $r > 0 && $s !== '' ) { $pc = round( ( ( $r - $s ) / $r ) * 100 ); if ( $pc > $bp ) { $bp = $pc; $best = $p; } } }
    return $best ? array( 'p' => $best, 'bp' => $bp ) : null;
}
function sahel_arrows() {
    return '<div class="strip-arrows"><button class="s-arrow prev" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button><button class="s-arrow next" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button></div>';
}
function sahel_slide_data( $i ) {
    $d = array(
        1 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1762ba743-5206-44f2-8090-f738bd071c0d.png', 'pill' => 'کالکشن ساحلی ۱۴۰۵', 't1' => 'مثل ساحل؛', 't2' => 'آرام و درخشان', 'desc' => 'پیراهن‌های ساحلی و مجلسی با پارچه‌های خنک و برش‌های ظریف.', 'btn' => 'خرید پیراهن ساحلی', 'url' => '' ),
        2 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1316896db-bd87-4b94-afa0-087689252253.png', 'pill' => 'پاییز و زمستان', 't1' => 'بافت‌های گرم؛', 't2' => 'ظرافتِ ساحل', 'desc' => 'بافت، ژاکت و ست‌های چهارفصل با رنگ‌های نود.', 'btn' => 'مشاهده بافت‌ها', 'url' => '' ),
        3 => array( 'img' => 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1cab5e6b5-d545-4e84-a529-fbfb06285707.png', 'pill' => 'اکسسوری', 't1' => 'استایلت را', 't2' => 'امضا کن', 'desc' => 'کیف، کمربند و زیورآلات مینیمال.', 'btn' => 'مشاهده اکسسوری‌ها', 'url' => '' ),
    );
    $b = isset( $d[ $i ] ) ? $d[ $i ] : array( 'img' => '', 'pill' => '', 't1' => '', 't2' => '', 'desc' => '', 'btn' => '', 'url' => '' );
    return array(
        'img' => get_theme_mod( 'sahel_slide' . $i . '_image', $b['img'] ),
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
} );

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
    wp_enqueue_script( 'jquery' );
    if ( function_exists( 'WC' ) ) { wp_enqueue_script( 'wc-add-to-cart' ); wp_enqueue_script( 'wc-cart-fragments' ); }
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
    if ( strlen( $phone ) < 10 ) { wp_send_json( array( 'ok' => 0, 'msg' => 'شماره موبایل معتبر نیست' ) ); }
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
    $about = '<h2>' . sahel_brand() . '؛ ظرافت، آرامش، درخشش</h2><p>' . sahel_brand() . ' با یک باور ساده متولد شد: هر خانم لیاقت لباس‌هایی را دارد که هم زیبا باشند هم راحت.</p>';
    $contact = '<h2>راه‌های ارتباط با ' . sahel_brand() . '</h2><p>📍 ' . get_theme_mod( 'sahel_address', 'تهران، خیابان ساحل، پاساژ استایل، پلاک ۱۲' ) . '</p>';
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

/* کارت محصول: در صورت نبود یا قدیمی بودن قالب کارت، نسخه فلت بازنویسی می‌شود */
add_action( 'init', function() {
    if ( ! function_exists( 'wc_get_products' ) ) return;
    $file = get_stylesheet_directory() . '/woocommerce/content-product.php';
    $cur = file_exists( $file ) ? file_get_contents( $file ) : '';
    if ( $cur && strpos( $cur, "wc_product_class( 'prod-card'" ) !== false && strpos( $cur, 'prod-desc' ) !== false && strpos( $cur, 'prod-vars' ) !== false ) return;
    $safe = <<<'SHCARD'
<?php
global $product;
if ( empty( $product ) || ! $product->is_visible() ) { return; }
$sd = wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 14 );
?>
<li <?php wc_product_class( 'prod-card', $product ); ?>>
<a class="prod-media" href="<?php the_permalink(); ?>">
<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ); ?>
<?php if ( $product->is_on_sale() ) : $br = (float) $product->get_regular_price(); $bs = $product->get_sale_price(); $bp = $br > 0 && $bs !== '' ? round( ( ( $br - $bs ) / $br ) * 100 ) : 0; ?>
<span class="badge hot"><?php echo sahel_fa( $bp ); ?>٪ تخفیف</span>
<?php else : $bd = $product->get_date_created(); ?>
<span class="badge <?php echo ( $bd && $bd->getTimestamp() > time() - 15 * DAY_IN_SECONDS ) ? 'new">جدید' : 'best">' . esc_html( function_exists( 'sahel_brand_short' ) ? sahel_brand_short() : 'ساحل' ); ?></span>
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
</li>
SHCARD;
    @mkdir( dirname( $file ), 0755, true );
    @file_put_contents( $file, $safe );
}, 5 );

/* تعریف بخش‌های صفحه اصلی */
function sahel_home_sec_defs() {
    $b = sahel_brand();
    return array(
        'stats'    => array( 'نوار آمار و اعداد', '', '', 1 ),
        'marquee'  => array( 'نوار متحرک', '', '', 2 ),
        'cats'     => array( 'دسته‌بندی‌ها', 'دسته‌بندی‌های ' . $b, 'از ساحل تا مهمانی — استایل تو، امضای تو', 3 ),
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
        'bg_opacity' => (int) get_theme_mod( 'sahel_sec_' . $key . '_bg_opacity', 100 ),
        'btn_color'  => get_theme_mod( 'sahel_sec_' . $key . '_btn_color', '' ),
        'btn_text'   => get_theme_mod( 'sahel_sec_' . $key . '_btn_text', '' ),
    );
}
function sahel_sec_head_html( $title, $sub, $extra = '' ) {
    if ( ! $title && ! $sub && ! $extra ) { return ''; }
    return '<div class="sec-head rv"><div>' . ( $title ? '<h2>' . esc_html( $title ) . '</h2>' : '' ) . ( $sub ? '<p>' . esc_html( $sub ) . '</p>' : '' ) . '</div>' . ( $extra ? '<div class="sec-tools">' . $extra . '</div>' : '' ) . '</div>';
}
/* پس‌زمینه، چینش و رنگ دکمه‌ی هر بخش؛ تصویر پس‌زمینه با ::before رسم می‌شود تا کانتینر اضافه لازم نباشد */
function sahel_sec_bg_attr( $sec ) {
    $class = array( 'sec-al-' . ( in_array( $sec['align'], array( 'right', 'center', 'left' ), true ) ? $sec['align'] : 'right' ) );
    $style = array();
    if ( $sec['full'] ) { $class[] = 'sec-full'; }
    if ( $sec['bg_img'] ) {
        $class[] = 'has-bg'; $class[] = 'has-bg-img';
        $style[] = '--sec-bg-img:url(' . esc_url( $sec['bg_img'] ) . ')';
        $style[] = '--sec-bg-op:' . ( max( 0, min( 100, (int) $sec['bg_opacity'] ) ) / 100 );
    }
    if ( $sec['bg_color'] ) { $class[] = 'has-bg'; $style[] = 'background-color:' . $sec['bg_color']; }
    if ( $sec['bg_grad'] ) { $class[] = 'has-bg'; $style[] = 'background-image:' . $sec['bg_grad']; }
    if ( $sec['btn_color'] ) { $class[] = 'has-btn-color'; $style[] = '--sec-btn-bg:' . $sec['btn_color']; }
    if ( $sec['btn_text'] ) { $class[] = 'has-btn-text'; $style[] = '--sec-btn-text:' . $sec['btn_text']; }
    return array( 'class' => implode( ' ', array_unique( $class ) ), 'style' => implode( ';', $style ), 'overlay' => '' );
}
function sahel_sec_open( $sec, $id = '', $extra_class = '' ) {
    $bg = sahel_sec_bg_attr( $sec );
    return '<section' . ( $id ? ' id="' . esc_attr( $id ) . '"' : '' ) . ' class="' . esc_attr( trim( $extra_class . ' ' . $bg['class'] ) ) . '"' . ( $bg['style'] ? ' style="' . esc_attr( $bg['style'] ) . '"' : '' ) . '>';
}
function sahel_post_card( $post ) {
    $thumb = get_the_post_thumbnail( $post->ID, 'medium_large', array( 'loading' => 'lazy' ) );
    if ( ! $thumb ) { $thumb = '<img src="https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png" alt="" loading="lazy">'; }
    return '<article class="post-card rv">' . $thumb . '<div class="pb"><span class="pdate">' . sahel_fa( get_the_date( 'Y/m/d', $post ) ) . '</span><h3>' . esc_html( get_the_title( $post ) ) . '</h3><p>' . esc_html( wp_trim_words( $post->post_excerpt ? $post->post_excerpt : $post->post_content, 18 ) ) . '</p><a class="more" href="' . esc_url( get_permalink( $post ) ) . '">ادامه مطلب ←</a></div></article>';
}

/* پنل سفارشی‌سازی - مرتب‌شده */
add_action( 'customize_register', function( $w ) {
    /* ===== هویت برند ===== */
    $w->add_section( 'sahel_brand', array( 'title' => '۱. هویت برند', 'priority' => 29 ) );
    $w->add_setting( 'sahel_brand', array( 'default' => 'ساحل استایل', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_brand', array( 'label' => 'نام برند', 'section' => 'sahel_brand' ) );
    $w->add_setting( 'sahel_brand_short', array( 'default' => 'ساحل', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_brand_short', array( 'label' => 'نام کوتاه برند', 'section' => 'sahel_brand' ) );
    $w->add_setting( 'sahel_brand_en', array( 'default' => 'SAHEL STYLE', 'sanitize_callback' => 'sanitize_text_field' ) );
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
            'glass' => 'شیشه‌ای (نیمه‌شفاف)',
            'compact' => 'فشرده (کوچک و جمع‌وجور)'
        )
    ) );

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
    $w->add_setting( 'sahel_mnav_carded', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_mnav_carded', array( 'label' => '💳 منوی موبایل: سطح آخر کارتی', 'section' => 'sahel_menu', 'type' => 'checkbox' ) );

    /* ===== اسلایدر ===== */
    $w->add_section( 'sahel_slider', array( 'title' => '۴. اسلایدر', 'priority' => 30 ) );
    $w->add_setting( 'sahel_slide_count', array( 'default' => 3, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_count', array( 'label' => 'تعداد اسلایدها', 'section' => 'sahel_slider', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 10 ) ) );
    $w->add_setting( 'sahel_slide_height', array( 'default' => 620, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_height', array( 'label' => 'ارتفاع اسلاید دسکتاپ', 'section' => 'sahel_slider', 'type' => 'number' ) );
    $w->add_setting( 'sahel_slide_height_m', array( 'default' => 480, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slide_height_m', array( 'label' => 'ارتفاع اسلاید موبایل', 'section' => 'sahel_slider', 'type' => 'number' ) );
    $w->add_setting( 'sahel_slider_full', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_slider_full', array( 'label' => '↔ اسلایدر تمام‌عرض', 'section' => 'sahel_slider', 'type' => 'checkbox' ) );

    $slide_max = min( 10, max( 1, (int) get_theme_mod( 'sahel_slide_count', 3 ) ) + 1 );
    for ( $i = 1; $i <= $slide_max; $i++ ) {
        $w->add_section( 'sahel_slide' . $i, array( 'title' => 'اسلاید ' . sahel_fa( $i ), 'priority' => 30 ) );
        $w->add_setting( 'sahel_slide' . $i . '_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_slide' . $i . '_image', array( 'label' => 'تصویر اسلاید', 'section' => 'sahel_slide' . $i ) ) );
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
    $w->add_control( 'sahel_btn_style', array( 'label' => 'طراحی دکمه‌ها', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array( 'pill' => 'پیل', 'round' => 'گوشه‌گرد', 'square' => 'مربع', 'outline' => 'دورخط', 'soft' => 'نرم', 'glass' => 'رنگ ثانویه روشن', 'neon' => 'تیره' ) ) );
    $w->add_setting( 'sahel_catcard', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_catcard', array( 'label' => 'کارت دسته‌بندی', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک', '2' => 'متن روی تصویر', '3' => 'افقی', '4' => 'دایره‌ای', '5' => 'زمینه رنگی روشن', '6' => 'مینیمال',
        '7' => 'رنگی تخت', '8' => 'حلقه‌ای', '9' => 'سه‌بعدی Tilt', '10' => 'بنری عریض' ) ) );
    $w->add_setting( 'sahel_prodcard', array( 'default' => '1', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_prodcard', array( 'label' => 'کارت محصول', 'section' => 'sahel_styles', 'type' => 'select', 'choices' => array(
        '1' => 'کلاسیک', '2' => 'مینیمال', '3' => 'خط رنگی', '4' => 'تیره', '5' => 'زمینه رنگی روشن', '6' => 'دورخط رنگی', '7' => 'مجله‌ای',
        '8' => 'قاب داخلی', '9' => 'تیره با دورخط', '10' => 'درخشش عبوری', '11' => 'سه‌بعدی Tilt' ) ) );

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
    $w->add_section( 'sahel_stats', array( 'title' => '۷. آمار', 'priority' => 33 ) );
    $w->add_setting( 'sahel_stat1', array( 'default' => '8000', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat1', array( 'label' => 'عدد ۱', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat1_label', array( 'default' => 'مشتری خوشحال', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat1_label', array( 'label' => 'متن ۱', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat2', array( 'default' => '450', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat2', array( 'label' => 'عدد ۲', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat2_label', array( 'default' => 'محصول متنوع', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat2_label', array( 'label' => 'متن ۲', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat3', array( 'default' => '98', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat3', array( 'label' => 'عدد ۳ (٪)', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat3_label', array( 'default' => 'رضایت خرید', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat3_label', array( 'label' => 'متن ۳', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat4_text', array( 'default' => 'سراسر کشور', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat4_text', array( 'label' => 'متن ۴', 'section' => 'sahel_stats' ) );
    $w->add_setting( 'sahel_stat4_label', array( 'default' => 'ارسال سریع', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_stat4_label', array( 'label' => 'متن ۴', 'section' => 'sahel_stats' ) );

    /* ===== رنگ‌ها و فونت ===== */
    $w->add_section( 'sahel_colors', array( 'title' => '۸. رنگ‌ها و فونت', 'priority' => 34 ) );
    $cols = array(
        'sahel_c1' => array( 'رنگ اصلی', '#b08968' ),
        'sahel_c2' => array( 'رنگ ثانویه', '#d4a373' ),
        'sahel_hot' => array( 'رنگ تأکیدی', '#c65a3f' ),
        'sahel_bg' => array( 'پس‌زمینه سایت', '#faf7f2' ),
        'sahel_ink' => array( 'رنگ متن', '#33261c' ),
        'sahel_muted' => array( 'رنگ کم‌رنگ', '#8b7a6b' ),
        'sahel_headerbg' => array( 'پس‌زمینه هدر', '#faf7f2' ),
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
    $w->add_setting( 'sahel_header_opacity', array( 'default' => 82, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_header_opacity', array( 'label' => 'شفافیت هدر (٪)', 'section' => 'sahel_colors', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 100 ) ) );
    $w->add_setting( 'sahel_font', array( 'default' => 'vazirmatn', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_font', array( 'label' => 'فونت بدنه', 'section' => 'sahel_colors', 'type' => 'select', 'choices' => $fonts ) );
    $w->add_setting( 'sahel_heading_font', array( 'default' => '', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_heading_font', array( 'label' => 'فونت تیترها', 'section' => 'sahel_colors', 'type' => 'select', 'choices' => array( '' => 'پیش‌فرض' ) + $fonts ) );
    $w->add_setting( 'sahel_body_size', array( 'default' => 16, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_body_size', array( 'label' => 'اندازه فونت', 'section' => 'sahel_colors', 'type' => 'number' ) );

    /* ===== فروشگاه ===== */
    $w->add_section( 'sahel_shop', array( 'title' => '۹. فروشگاه', 'priority' => 35 ) );
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
    $w->add_section( 'sahel_product', array( 'title' => '۱۰. محصول', 'priority' => 36 ) );
    $w->add_setting( 'sahel_product_trust', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_trust', array( 'label' => 'نوار اعتماد', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_tabs', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_tabs', array( 'label' => 'تب‌ها', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_related', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_related', array( 'label' => 'محصولات مرتبط', 'section' => 'sahel_product', 'type' => 'checkbox' ) );
    $w->add_setting( 'sahel_product_fullwidth', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_product_fullwidth', array( 'label' => 'تمام‌عرض', 'section' => 'sahel_product', 'type' => 'checkbox' ) );

    /* ===== صفحات ===== */
    $w->add_section( 'sahel_pages', array( 'title' => '۱۱. صفحات', 'priority' => 37 ) );
    $w->add_setting( 'sahel_about_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_about_image', array( 'label' => 'تصویر درباره ما', 'section' => 'sahel_pages' ) ) );
    $w->add_setting( 'sahel_contact_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_contact_image', array( 'label' => 'تصویر تماس با ما', 'section' => 'sahel_pages' ) ) );
    $w->add_setting( 'sahel_contact_map', array( 'default' => '', 'sanitize_callback' => 'wp_kses_post' ) );
    $w->add_control( 'sahel_contact_map', array( 'label' => 'کد نقشه', 'section' => 'sahel_pages', 'type' => 'textarea' ) );
    $w->add_setting( 'sahel_404_text', array( 'default' => 'صفحه پیدا نشد!', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_404_text', array( 'label' => 'متن ۴۰۴', 'section' => 'sahel_pages' ) );

    /* ===== فوتر ===== */
    $w->add_section( 'sahel_footer', array( 'title' => '۱۲. فوتر', 'priority' => 38 ) );
    $w->add_setting( 'sahel_copyright', array( 'default' => '© تمامی حقوق برای ' . sahel_brand() . ' محفوظ است.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_copyright', array( 'label' => 'کپی‌رایت', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_footer_about', array( 'default' => sahel_brand() . '؛ فروشگاه آنلاین.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_footer_about', array( 'label' => 'متن درباره', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_address', array( 'default' => 'تهران', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_address', array( 'label' => 'آدرس', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_phone', array( 'default' => sahel_fa( '021-220000' ), 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_phone', array( 'label' => 'تلفن', 'section' => 'sahel_footer' ) );
    $w->add_setting( 'sahel_email', array( 'default' => 'info@sahelstyle.ir', 'sanitize_callback' => 'sanitize_text_field' ) );
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

    /* ===== مارکی ===== */
    $w->add_section( 'sahel_marquee', array( 'title' => '۱۳. مارکی', 'priority' => 39 ) );
    $w->add_setting( 'sahel_marquee_text', array( 'default' => sahel_brand() . ' ✦ ظرافت ✦ آرامش ✦ ضمانت ✦ پیراهن ✦ بافت ✦ ارسال ', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_marquee_text', array( 'label' => 'متن (با ✦ جدا کنید)', 'section' => 'sahel_marquee' ) );
    $w->add_setting( 'sahel_marquee_color', array( 'default' => '#8b7a6b', 'sanitize_callback' => 'sanitize_hex_color' ) );
    $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_marquee_color', array( 'label' => 'رنگ متن', 'section' => 'sahel_marquee' ) ) );
    $w->add_setting( 'sahel_marquee_size', array( 'default' => 15, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_marquee_size', array( 'label' => 'اندازه فونت', 'section' => 'sahel_marquee', 'type' => 'number' ) );
    /* v4.5: افکت مارکی */
    $w->add_setting( 'sahel_marquee_effect', array( 'default' => 'slide', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_marquee_effect', array(
        'label' => '🎬 افکت مارکی',
        'section' => 'sahel_marquee',
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
    $w->add_control( 'sahel_marquee_speed', array( 'label' => 'سرعت (ثانیه — کمتر = سریع‌تر)', 'section' => 'sahel_marquee', 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 60 ) ) );

    /* ===== بخش‌های صفحه اصلی ===== */
    $w->add_section( 'sahel_sections', array( 'title' => '۱۴. بخش‌های صفحه اصلی', 'description' => 'تنظیمات هر بخش (فعال‌سازی، تیتر، تمام‌عرض، تراز، پس‌زمینه، رنگ دکمه)', 'priority' => 40 ) );
    $grad_presets = array(
        '' => '— بدون گرادیان —',
        'linear-gradient(135deg,#faf7f2,#e7cfb8)' => 'کرم ملایم',
        'linear-gradient(135deg,#b08968,#d4a373)' => 'کارامل',
        'linear-gradient(135deg,#241a12,#4a2f1d)' => 'قهوه‌ای تیره',
        'linear-gradient(135deg,#c65a3f,#b08968)' => 'تخفیف',
        'linear-gradient(135deg,#faf7f2 0%,transparent 100%)' => 'محو',
    );
    foreach ( sahel_home_sec_defs() as $key => $def ) {
        $w->add_setting( 'sahel_sec_' . $key . '_on', array( 'default' => 1, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_on', array( 'label' => '✔ نمایش: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_order', array( 'default' => $def[3], 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_order', array( 'label' => '🔃 ترتیب: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 20 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_title', array( 'default' => $def[1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_sec_' . $key . '_title', array( 'label' => 'تیتر: ' . $def[0], 'section' => 'sahel_sections' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_sub', array( 'default' => $def[2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_sec_' . $key . '_sub', array( 'label' => 'زیرتیتر: ' . $def[0], 'section' => 'sahel_sections' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_full', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_full', array( 'label' => '↔ تمام‌عرض: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'checkbox' ) );
        $w->add_setting( 'sahel_sec_' . $key . '_align', array( 'default' => 'right', 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_sec_' . $key . '_align', array( 'label' => '⚖ تراز: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'select', 'choices' => array( 'right' => 'راست', 'center' => 'وسط', 'left' => 'چپ' ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_img', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_sec_' . $key . '_bg_img', array( 'label' => '🖼 تصویر پس‌زمینه: ' . $def[0], 'section' => 'sahel_sections' ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_bg_color', array( 'label' => '🎨 رنگ: ' . $def[0], 'section' => 'sahel_sections' ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_grad', array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_sec_' . $key . '_bg_grad', array( 'label' => '🌈 گرادیان: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'select', 'choices' => $grad_presets ) );
        $w->add_setting( 'sahel_sec_' . $key . '_bg_opacity', array( 'default' => 100, 'sanitize_callback' => 'absint' ) );
        $w->add_control( 'sahel_sec_' . $key . '_bg_opacity', array( 'label' => '💫 شفافیت: ' . $def[0], 'section' => 'sahel_sections', 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 100 ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_btn_color', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_btn_color', array( 'label' => '🎯 رنگ دکمه: ' . $def[0], 'section' => 'sahel_sections' ) ) );
        $w->add_setting( 'sahel_sec_' . $key . '_btn_text', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
        $w->add_control( new WP_Customize_Color_Control( $w, 'sahel_sec_' . $key . '_btn_text', array( 'label' => '🎯 رنگ متن دکمه: ' . $def[0], 'section' => 'sahel_sections' ) ) );
    }
    $w->add_setting( 'sahel_about_home_text', array( 'default' => sahel_brand() . ' با یک باور ساده متولد شد.', 'sanitize_callback' => 'sanitize_textarea_field' ) );
    $w->add_control( 'sahel_about_home_text', array( 'label' => 'متن درباره در صفحه اصلی', 'section' => 'sahel_sections', 'type' => 'textarea' ) );

    /* ===== برندها ===== */
    $w->add_section( 'sahel_brands', array( 'title' => '۱۵. برندها', 'priority' => 41 ) );
    $w->add_setting( 'sahel_brands_mode', array( 'default' => 'marquee', 'sanitize_callback' => 'sanitize_key' ) );
    $w->add_control( 'sahel_brands_mode', array( 'label' => 'حالت نمایش', 'section' => 'sahel_brands', 'type' => 'select', 'choices' => array( 'marquee' => 'مارکی', 'float' => 'شناور', 'static' => 'ایستا' ) ) );
    $w->add_setting( 'sahel_brands_speed', array( 'default' => 30, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_brands_speed', array( 'label' => 'سرعت (ثانیه)', 'section' => 'sahel_brands', 'type' => 'number', 'input_attrs' => array( 'min' => 10, 'max' => 90 ) ) );
    for ( $i = 1; $i <= 8; $i++ ) {
        $w->add_setting( 'sahel_brand_logo' . $i, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_brand_logo' . $i, array( 'label' => 'لوگو ' . sahel_fa( $i ), 'section' => 'sahel_brands' ) ) );
        $w->add_setting( 'sahel_brand_name' . $i, array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_brand_name' . $i, array( 'label' => 'نام ' . sahel_fa( $i ), 'section' => 'sahel_brands' ) );
    }

    /* ===== نوار پایین موبایل ===== */
    $w->add_section( 'sahel_bottombar', array( 'title' => '۱۶. نوار پایین موبایل', 'priority' => 42 ) );
    $bb_choices = array( '' => '— غیرفعال —', 'home' => 'خانه', 'shop' => 'فروشگاه', 'cart' => 'سبد', 'contact' => 'تماس', 'search' => 'جستجو', 'account' => 'حساب', 'blog' => 'مقالات', 'about' => 'درباره' );
    $bb_defs = array( 'shop', 'cart', 'contact', 'search' );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_bb' . $i, array( 'default' => $bb_defs[ $i - 1 ], 'sanitize_callback' => 'sanitize_key' ) );
        $w->add_control( 'sahel_bb' . $i, array( 'label' => 'آیتم ' . sahel_fa( $i ), 'section' => 'sahel_bottombar', 'type' => 'select', 'choices' => $bb_choices ) );
    }

    /* ===== دکمه شناور ===== */
    $w->add_section( 'sahel_fab', array( 'title' => '۱۷. دکمه شناور تماس', 'priority' => 43 ) );
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

    /* ===== محتوای بخش‌های جدید ===== */
    $w->add_section( 'sahel_newsecs', array( 'title' => '۱۸. محتوای بخش‌های جدید', 'priority' => 44 ) );
    $w->add_setting( 'sahel_campaign_btn', array( 'default' => 'مشاهده کالکشن', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_campaign_btn', array( 'label' => 'دکمه بنر کمپین', 'section' => 'sahel_newsecs' ) );
    $w->add_setting( 'sahel_campaign_url', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
    $w->add_control( 'sahel_campaign_url', array( 'label' => 'لینک بنر کمپین', 'section' => 'sahel_newsecs' ) );
    $look_defs = array(
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1762ba743-5206-44f2-8090-f738bd071c0d.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1316896db-bd87-4b94-afa0-087689252253.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/1cab5e6b5-d545-4e84-a529-fbfb06285707.png',
        'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png',
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_look' . $i, array( 'default' => $look_defs[ $i - 1 ], 'sanitize_callback' => 'esc_url_raw' ) );
        $w->add_control( new WP_Customize_Image_Control( $w, 'sahel_look' . $i, array( 'label' => 'تصویر گالری ' . sahel_fa( $i ), 'section' => 'sahel_newsecs' ) ) );
    }
    $testi_defs = array(
        array( 'کیفیت عالی بود.', 'نگار احمدی', 'تهران' ),
        array( 'ارسال سریع.', 'سارا محمدی', 'اصفهان' ),
        array( 'راضی هستم.', 'مریم رضایی', 'شیراز' ),
    );
    for ( $i = 1; $i <= 3; $i++ ) {
        $w->add_setting( 'sahel_testi' . $i . '_txt', array( 'default' => $testi_defs[ $i - 1 ][0], 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_txt', array( 'label' => 'نظر ' . sahel_fa( $i ), 'section' => 'sahel_newsecs', 'type' => 'textarea' ) );
        $w->add_setting( 'sahel_testi' . $i . '_name', array( 'default' => $testi_defs[ $i - 1 ][1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_name', array( 'label' => 'نام ' . sahel_fa( $i ), 'section' => 'sahel_newsecs' ) );
        $w->add_setting( 'sahel_testi' . $i . '_role', array( 'default' => $testi_defs[ $i - 1 ][2], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_testi' . $i . '_role', array( 'label' => 'شهر ' . sahel_fa( $i ), 'section' => 'sahel_newsecs' ) );
    }
    $faq_defs = array(
        array( 'شرایط ارسال؟', '۲۴-۴۸ ساعت' ),
        array( 'بازگشت کالا؟', '۷ روز' ),
        array( 'ضمانت اصالت؟', 'بله' ),
        array( 'سایزبندی؟', 'راهنمای سایز دارد' ),
    );
    for ( $i = 1; $i <= 4; $i++ ) {
        $w->add_setting( 'sahel_faq' . $i . '_q', array( 'default' => $faq_defs[ $i - 1 ][0], 'sanitize_callback' => 'sanitize_text_field' ) );
        $w->add_control( 'sahel_faq' . $i . '_q', array( 'label' => 'سؤال ' . sahel_fa( $i ), 'section' => 'sahel_newsecs' ) );
        $w->add_setting( 'sahel_faq' . $i . '_a', array( 'default' => $faq_defs[ $i - 1 ][1], 'sanitize_callback' => 'sanitize_textarea_field' ) );
        $w->add_control( 'sahel_faq' . $i . '_a', array( 'label' => 'پاسخ ' . sahel_fa( $i ), 'section' => 'sahel_newsecs', 'type' => 'textarea' ) );
    }
    $w->add_setting( 'sahel_insta_text', array( 'default' => 'ما را دنبال کنید.', 'sanitize_callback' => 'sanitize_text_field' ) );
    $w->add_control( 'sahel_insta_text', array( 'label' => 'متن اینستاگرام', 'section' => 'sahel_newsecs' ) );
} );

/* ساخت منو */
function sahel_render_menu_items() {
    $html = '';
    for ( $i = 1; $i <= 12; $i++ ) {
        $label = trim( (string) get_theme_mod( 'sahel_m' . $i . '_label', '' ) );
        $type  = get_theme_mod( 'sahel_m' . $i . '_type', '' );
        $val   = trim( (string) get_theme_mod( 'sahel_m' . $i . '_val', '' ) );
        if ( ! $label ) { continue; }
        if ( $type === 'cats' ) {
            $html .= '<div class="dd"><a href="' . esc_url( sahel_shop_url() ) . '" class="dd-toggle">' . esc_html( $label ) . ' <svg viewBox="0 0 24 24" fill="none" stroke-width="2.4"><path d="M6 9l6 6 6-6"/></svg></a>';
            $html .= '<div class="dd-menu">';
            $fbimg = 'https://image.qwenlm.ai/public_source/593f1887-5498-4920-ad1d-3f467426cd05/16752e62d-5c58-45c7-891c-b787a0344a6c.png';
            foreach ( sahel_product_cats_tree() as $node ) {
                $c = $node['term'];
                $html .= '<div class="dd-group"><a class="dd-card" href="' . esc_url( get_term_link( $c ) ) . '"><img src="' . esc_url( sahel_cat_img( $c, $fbimg ) ) . '" alt=""><span><b>' . esc_html( $c->name ) . '</b><small>' . sahel_num( $c->count ) . ' محصول</small></span></a>';
                if ( ! empty( $node['children'] ) ) {
                    $html .= '<div class="dd-sub">';
                    foreach ( $node['children'] as $chnode ) { $cc = $chnode['term']; $html .= '<a class="dd-sublink" href="' . esc_url( get_term_link( $cc ) ) . '">' . esc_html( $cc->name ) . '</a>'; }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }
            $html .= '<a class="dd-all" href="' . esc_url( sahel_shop_url() ) . '">همه محصولات ←</a></div></div>';
        } else {
            $url = '';
            if ( $type === 'home' ) { $url = home_url( '/' ); }
            elseif ( $type === 'shop' ) { $url = sahel_shop_url(); }
            elseif ( $type === 'url' ) { $url = $val; }
            elseif ( $type === 'page' ) { $url = sahel_page_url( $val ); }
            else { continue; }
            $html .= '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
    }
    return $html;
}

/* CSS — طراحی فلت: بدون سایه، گرادیان، شیشه و جابه‌جایی هنگام هاور تا هیچ المانی زیر کانتینر بریده نشود */
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
    $br_speed = (int) get_theme_mod( 'sahel_brands_speed', 30 );
    $header_text = get_theme_mod( 'sahel_header_text_color', '#33261c' );
    $header_btn_bg = get_theme_mod( 'sahel_header_btn_color', '#b08968' );
    $header_btn_text = get_theme_mod( 'sahel_header_btn_text', '#ffffff' );
    $mq_speed = (int) get_theme_mod( 'sahel_marquee_speed', 26 );
    $hop = max( 0, min( 100, (int) get_theme_mod( 'sahel_header_opacity', 82 ) ) );
    $rad = (int) get_theme_mod( 'sahel_radius', 12 );

    $dyn = ':root{--c1:' . $c1 . ';--c2:' . $c2 . ';--hot:' . $hot . ';--bg:' . $bg . ';--ink:' . $ink . ';--muted:' . $muted . ';--headerbg:' . $hbg . ';--footerbg:' . $fbg
        . ';--m-h1:' . $mh1 . 'px;--m-p:' . $mp . 'px;--m-ov:' . ( $mov / 100 )
        . ';--font:\'' . $font . '\',system-ui,Tahoma,sans-serif;--font-head:\'' . $hfont . '\',system-ui,Tahoma,sans-serif;--font-header:\'' . $hfont2 . '\',system-ui,Tahoma,sans-serif'
        . ';--fs:' . $fsize . 'px;--pricec:' . $pricec . ';--btnt:' . $btnt . ';--carticon:' . $carticon . ';--carticonh:' . $carticonh . ';--cardbg:' . $cardbg . ';--ftext:' . $ftext
        . ';--mqc:' . $mqc . ';--mqs:' . $mqs . 'px;--sh:' . $sh . 'px;--shm:' . $shm . 'px;--br-speed:' . $br_speed . 's;--mq-speed:' . $mq_speed . 's'
        . ';--header-text:' . $header_text . ';--header-btn-bg:' . $header_btn_bg . ';--header-btn-text:' . $header_btn_text . ';--hop:' . $hop . '%;--rad:' . $rad . 'px}';
    if ( $hop < 100 ) { $dyn .= '#header{-webkit-backdrop-filter:blur(12px);backdrop-filter:blur(12px)}'; }

    $static = <<<'SHCSS'
:root{--caramel:var(--c1);--sand:var(--c2);--cream:color-mix(in srgb,var(--c2) 16%,var(--cardbg));--tint:color-mix(in srgb,var(--c1) 10%,var(--cardbg));--line:color-mix(in srgb,var(--ink) 12%,transparent);--line2:var(--c1);--grad:var(--c1);--grad2:var(--c2);--shadow:none;--shadow-lg:none;--r:var(--rad);--rad-sm:calc(var(--rad) * .65);--dark-line:rgba(255,255,255,.14)}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--font);font-size:var(--fs);line-height:1.8;background:var(--bg);color:var(--ink);overflow-x:hidden}
::selection{background:var(--c1);color:var(--btnt)}
::-webkit-scrollbar{width:9px;height:9px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:color-mix(in srgb,var(--c1) 60%,var(--bg));border-radius:99px}
img{max-width:100%;height:auto;display:block}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}
input,select,textarea{font-family:inherit;font-size:inherit;color:var(--ink)}
h1,h2,h3,h4,h5,h6,.btn,.brand-name,.prod-name,.cat-card h3{font-family:var(--font-head)}
a:focus-visible,button:focus-visible,summary:focus-visible{outline:2px solid var(--c1);outline-offset:2px}
.wrap{max-width:1260px;margin-inline:auto;padding-inline:24px}
.grad-text{color:var(--c1)}
.hot-text{color:var(--c2)}
.rv{opacity:0;transition:opacity .6s ease}
.rv.in{opacity:1}
@keyframes rise{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
@keyframes slideZoom{from{transform:scale(1.06)}to{transform:scale(1)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes bump{40%{transform:scale(1.3)}}

/* دکمه‌ها */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:11px 26px;border:2px solid transparent;border-radius:var(--rad);font-weight:800;font-size:.92rem;line-height:1.5;white-space:nowrap;transition:background-color .2s,border-color .2s,color .2s}
.btn-primary{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.btn-primary:hover{background:color-mix(in srgb,var(--c1) 82%,#000);border-color:color-mix(in srgb,var(--c1) 82%,#000)}
.btn-ghost{background:transparent;border-color:var(--line);color:var(--ink)}
.btn-ghost:hover{border-color:var(--c1);color:var(--c1)}
.btn-light{background:#fff;border-color:#fff;color:color-mix(in srgb,var(--c1) 75%,#000)}
.btn-light:hover{background:transparent;color:#fff}
body.btnst-pill .btn{border-radius:999px}
body.btnst-round .btn{border-radius:var(--rad)}
body.btnst-square .btn{border-radius:4px}
body.btnst-outline .btn-primary{background:transparent;color:var(--c1)}
body.btnst-outline .btn-primary:hover{background:var(--c1);color:var(--btnt)}
body.btnst-soft .btn-primary{background:var(--tint);border-color:var(--tint);color:color-mix(in srgb,var(--c1) 75%,#000)}
body.btnst-soft .btn-primary:hover{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
body.btnst-glass .btn-primary{background:color-mix(in srgb,var(--c2) 22%,var(--cardbg));border-color:color-mix(in srgb,var(--c2) 45%,var(--cardbg));color:color-mix(in srgb,var(--c1) 75%,#000)}
body.btnst-glass .btn-primary:hover{background:var(--c2);border-color:var(--c2);color:var(--btnt)}
body.btnst-neon .btn-primary{background:var(--ink);border-color:var(--ink);color:var(--bg)}
body.btnst-neon .btn-primary:hover{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
section.has-btn-color .btn-primary{background:var(--sec-btn-bg)!important;border-color:var(--sec-btn-bg)!important}
section.has-btn-text .btn-primary{color:var(--sec-btn-text)!important}

/* هدر */
#header{position:sticky;top:0;z-index:50;background:color-mix(in srgb,var(--headerbg) var(--hop),transparent);border-bottom:1px solid var(--line);font-family:var(--font-header)}
#header::before{content:"";position:absolute;inset:0;z-index:-1;background:var(--header-img,none) center/cover no-repeat;opacity:var(--header-img-op,0);pointer-events:none}
.header-inner{display:flex;align-items:center;gap:16px;min-height:72px;padding-block:10px}
.brand{display:flex;align-items:center;gap:10px;flex-shrink:0}
.brand img{height:48px;width:auto;object-fit:contain}
.brand-name{font-weight:900;font-size:1.15rem;line-height:1.35;color:var(--header-text)}
.brand-name small{display:block;font-size:.6rem;font-weight:600;color:var(--muted);letter-spacing:3px}
.bnav{display:flex;align-items:center;gap:2px;flex-wrap:wrap;margin-inline-start:auto}
.bnav>a,.dd-toggle{display:flex;align-items:center;gap:6px;padding:8px 14px;border-radius:var(--rad-sm);font-size:.88rem;font-weight:700;color:var(--header-text);transition:background-color .2s,color .2s}
.bnav>a:hover,.dd-toggle:hover,.dd.open>.dd-toggle{color:var(--c1);background:var(--tint)}
.dd-toggle svg{width:12px;height:12px;stroke:currentColor;transition:transform .2s}
.dd.open>.dd-toggle svg{transform:rotate(180deg)}
.dd{position:relative}
.dd-menu{position:absolute;top:100%;inset-inline-start:0;z-index:70;margin-top:8px;width:min(820px,92vw);max-height:calc(100vh - 120px);overflow-y:auto;display:none;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:4px;padding:10px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);animation:fadeIn .2s ease}
.dd.open>.dd-menu{display:grid}
@media(hover:hover) and (min-width:921px){
.bnav .dd::after{content:"";position:absolute;inset-inline:0;top:100%;height:10px;display:none}
.bnav .dd:hover::after{display:block}
.bnav .dd:hover>.dd-menu{display:grid}
.bnav .dd:hover>.dd-toggle{color:var(--c1);background:var(--tint)}
.bnav .dd:hover>.dd-toggle svg{transform:rotate(180deg)}
}
.dd-group{display:flex;flex-direction:column;gap:4px;padding:2px}
.dd-card{display:flex;align-items:center;gap:10px;padding:8px;border-radius:var(--rad-sm);transition:background-color .2s,color .2s}
.dd-card:hover{background:var(--tint);color:var(--c1)}
.dd-card img{width:44px;height:44px;border-radius:var(--rad-sm);object-fit:cover;flex-shrink:0;background:var(--cream)}
.dd-card span{display:flex;flex-direction:column;line-height:1.5}
.dd-card b{font-size:.85rem;font-weight:800}
.dd-card small{color:var(--muted);font-size:.68rem}
.dd-sub{display:flex;flex-wrap:wrap;gap:4px;padding-inline-start:62px}
.dd-sublink{padding:2px 10px;border:1px solid var(--line);border-radius:999px;font-size:.72rem;font-weight:700;color:var(--muted);transition:border-color .2s,color .2s}
.dd-sublink:hover{border-color:var(--c1);color:var(--c1)}
.dd-all{grid-column:1/-1;display:block;margin-top:4px;padding:10px;border-top:1px solid var(--line);text-align:center;color:var(--c1);font-weight:800;font-size:.8rem;transition:background-color .2s}
.dd-all:hover{background:var(--tint)}

.sh-search{position:relative}
.sh-search form{display:flex;align-items:center;gap:8px;width:220px;padding:8px 14px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);transition:border-color .2s}
.sh-search form:focus-within{border-color:var(--c1)}
.sh-search input[type=search]{width:100%;border:none;outline:none;background:transparent;font-size:.85rem}
.sh-search input[type=search]::placeholder{color:var(--muted)}
.sh-search svg{width:16px;height:16px;stroke:var(--muted);flex-shrink:0}
.sh-live{position:absolute;top:calc(100% + 8px);inset-inline-start:0;z-index:90;width:400px;max-width:92vw;max-height:430px;overflow-y:auto;display:none;padding:10px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad)}
.sh-live.show{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;animation:fadeIn .2s ease}
.lv-card{display:block;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);overflow:hidden;transition:border-color .2s}
.lv-card:hover{border-color:var(--c1)}
.lv-card img{width:100%;aspect-ratio:1/.9;object-fit:cover;background:var(--cream)}
.lv-card .lv-t{padding:6px 9px 0;font-size:.7rem;font-weight:700;line-height:1.7;color:var(--ink);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.lv-card .lv-p{padding:2px 9px 8px;color:var(--pricec);font-size:.7rem;font-weight:800}
.lv-card .lv-p del{color:var(--muted);font-weight:500;margin-inline-end:6px}
.lv-empty{grid-column:1/-1;padding:16px;text-align:center;color:var(--muted);font-size:.78rem;font-weight:700}

.hact{display:flex;align-items:center;gap:10px;margin-inline-start:auto}
.icon-btn{position:relative;width:44px;height:44px;display:grid;place-items:center;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);transition:background-color .2s,border-color .2s}
.icon-btn:hover{background:var(--tint);border-color:var(--c1)}
.icon-btn svg{width:20px;height:20px;stroke:var(--ink)}
#cartCount{position:absolute;top:-7px;left:-7px;min-width:20px;height:20px;padding:0 5px;display:grid;place-items:center;background:var(--hot);color:#fff;border:2px solid var(--headerbg);border-radius:999px;font-size:.66rem;font-weight:800;line-height:1}
#cartCount.bump{animation:bump .4s}
#header .btn-login,.sh-mnav .btn-login{background:var(--header-btn-bg);border-color:var(--header-btn-bg);color:var(--header-btn-text)}
#header .btn-login{padding:9px 20px}
#header .btn-login:hover,.sh-mnav .btn-login:hover{background:color-mix(in srgb,var(--header-btn-bg) 82%,#000);border-color:color-mix(in srgb,var(--header-btn-bg) 82%,#000)}
.sh-burger{display:none}

/* مدل‌های هدر */
.hdr-modern .header-inner{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;row-gap:8px}
.hdr-modern .brand{grid-column:2;grid-row:1}
.hdr-modern .hact{grid-column:3;grid-row:1;justify-self:end;margin:0}
.hdr-modern .bnav{grid-column:1/-1;grid-row:2;justify-content:center;margin:0;padding-top:8px;border-top:1px solid var(--line)}
.hdr-minimal #header{border-bottom-color:transparent}
.hdr-minimal .brand-name{display:none}
.hdr-minimal .hact{margin-inline-start:0}
.hdr-minimal .sh-search form{width:170px}
.hdr-glass #header{background:color-mix(in srgb,var(--headerbg) 62%,transparent);-webkit-backdrop-filter:blur(16px);backdrop-filter:blur(16px)}
.hdr-compact .header-inner{min-height:56px;padding-block:6px;gap:12px}
.hdr-compact .brand img{height:36px}
.hdr-compact .brand-name{font-size:1rem}
.hdr-compact .brand-name small{display:none}
.hdr-compact .icon-btn{width:38px;height:38px}
.hdr-compact .bnav>a,.hdr-compact .dd-toggle{padding:6px 10px;font-size:.82rem}
.hdr-compact #header .btn-login{padding:6px 14px;font-size:.82rem}
.hdr-compact .sh-search form{padding:6px 12px}

/* اسلایدر */
.slider{position:relative;min-height:var(--sh,620px);overflow:hidden;background:var(--cream)}
.slider.is-full .wrap{max-width:none;padding-inline:40px}
.slide{position:absolute;inset:0;display:flex;align-items:center;opacity:0;visibility:hidden;transition:opacity .8s ease,visibility .8s}
.slide.active{opacity:1;visibility:visible}
.slide-bg{position:absolute;inset:0;width:100%;height:100%!important;object-fit:cover;object-position:center top}
.slide.active .slide-bg{animation:slideZoom 9s ease forwards}
.slide-ovl{position:absolute;inset:0;background:linear-gradient(to left,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .95)) 15%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .7)) 40%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .15)) 70%,rgba(var(--ovl-rgb),0))}
.slide.al-left .slide-ovl{background:linear-gradient(to right,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .95)) 15%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .7)) 40%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * .15)) 70%,rgba(var(--ovl-rgb),0))}
.slide.al-center .slide-ovl{background:rgba(var(--ovl-rgb),calc(var(--ovl-o) * .7))}
.slide .wrap{position:relative;width:100%;padding-block:60px}
.slide-txt{max-width:600px}
.slide-txt.al-center{margin-inline:auto;text-align:center}
.slide-txt.al-left{margin-inline-start:auto;text-align:left}
.slide-txt.al-center .hero-cta{justify-content:center}
.slide-txt.al-left .hero-cta{justify-content:flex-end}
.slide.active .slide-txt>*{animation:rise .7s ease both}
.slide.active .slide-txt>*:nth-child(2){animation-delay:.1s}
.slide.active .slide-txt>*:nth-child(3){animation-delay:.2s}
.slide.active .slide-txt>*:nth-child(4){animation-delay:.3s}
.slide-txt .pill{display:inline-flex;align-items:center;gap:8px;padding:5px 14px;border-radius:999px;background:var(--c1);color:var(--btnt);font-size:.76rem;font-weight:800}
.slide-txt .pill i{width:6px;height:6px;border-radius:50%;background:currentColor}
.slide-txt h1{margin:18px 0 14px;font-size:clamp(2rem,4.6vw,3.6rem);font-weight:900;line-height:1.35}
.slide-txt p{margin-bottom:26px;color:var(--muted);font-size:1rem;line-height:2.1}
.hero-cta{display:flex;gap:12px;flex-wrap:wrap}
.slide-brand{position:absolute;z-index:3;top:12%;left:5%;display:flex;align-items:center;gap:10px;padding:8px 18px 8px 10px;background:var(--cardbg);border:1px solid var(--line);border-radius:999px}
.slide-brand img{height:38px;width:auto;object-fit:contain}
.slide-brand b{display:block;font-size:.8rem;line-height:1.4}
.slide-brand small{font-size:.6rem;color:var(--muted)}
.s-nav{position:absolute;top:50%;z-index:6;width:46px;height:46px;margin-top:-23px;display:grid;place-items:center;background:var(--cardbg);border:1px solid var(--line);border-radius:50%;transition:background-color .2s,border-color .2s}
.s-nav:hover{background:var(--c1);border-color:var(--c1)}
.s-nav svg{width:18px;height:18px;stroke:var(--ink)}
.s-nav:hover svg{stroke:var(--btnt)}
.s-next{left:20px}
.s-prev{right:20px}
.s-dots{position:absolute;bottom:18px;left:50%;z-index:6;display:flex;gap:6px;transform:translateX(-50%)}
.s-dots button{width:24px;height:4px;border-radius:999px;background:color-mix(in srgb,var(--ink) 22%,transparent);transition:width .3s,background-color .3s}
.s-dots button.active{width:40px;background:var(--c1)}
.s-progress{position:absolute;bottom:0;right:0;z-index:6;width:0;height:3px;background:var(--c1)}

/* نوار آمار */
.statband{display:grid;grid-template-columns:repeat(4,1fr);row-gap:14px}
.stat{padding:8px 12px;text-align:center;border-inline-start:1px solid var(--line)}
.stat:first-child{border-inline-start:0}
.stat b{display:block;font-size:1.6rem;font-weight:900;line-height:1.4;color:var(--c1)}
.stat span{color:var(--muted);font-size:.78rem;font-weight:700}

/* نوار متحرک */
.marquee{direction:ltr;overflow:hidden;padding:12px 0;background:var(--cardbg);border-block:1px solid var(--line)}
.marquee-track{display:flex;width:max-content;animation:marqueeSlide var(--mq-speed,26s) linear infinite}
.marquee.effect-fade .marquee-track{animation:marqueeFade var(--mq-speed,26s) ease-in-out infinite}
.marquee.effect-bounce .marquee-track{animation:marqueeBounce var(--mq-speed,26s) ease-in-out infinite}
.marquee.effect-scale .marquee-track{animation:marqueeScale var(--mq-speed,26s) ease-in-out infinite}
.marquee.effect-wave .marquee-track{animation:marqueeWave var(--mq-speed,26s) ease-in-out infinite}
@keyframes marqueeSlide{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes marqueeFade{0%,100%{opacity:1;transform:translateX(0)}50%{opacity:.6;transform:translateX(-50%)}}
@keyframes marqueeBounce{0%,100%{transform:translateX(0)}50%{transform:translateX(-50%) translateY(-5px)}}
@keyframes marqueeScale{0%,100%{transform:translateX(0) scale(1)}50%{transform:translateX(-50%) scale(1.04)}}
@keyframes marqueeWave{0%{transform:translateX(0) rotate(0)}50%{transform:translateX(-25%) rotate(1deg)}100%{transform:translateX(-50%) rotate(0)}}
.marquee span{display:flex;gap:26px;padding-inline:13px;white-space:nowrap;font-size:var(--mqs,15px);font-weight:800;color:var(--mqc)}
.marquee b{color:var(--c1)}
.marquee em{font-style:normal;color:var(--hot)}

/* بخش‌ها */
body>section{position:relative;padding:36px 0}
body>section.has-bg{padding:56px 0}
section.sec-full>.wrap{max-width:none;padding-inline:28px}
section.has-bg-img::before{content:"";position:absolute;inset:0;background:var(--sec-bg-img) center/cover no-repeat;opacity:var(--sec-bg-op,1);pointer-events:none}
section.has-bg-img>.wrap{position:relative}
section.sec-al-center .wrap{text-align:center}
section.sec-al-center .sec-head{justify-content:center}
section.sec-al-center .sec-head h2{justify-content:center}
section.sec-al-left .wrap{text-align:left}
section.sec-al-left .sec-head{flex-direction:row-reverse}
.sec-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--line)}
.sec-head h2{display:flex;align-items:center;gap:10px;font-size:clamp(1.25rem,2.4vw,1.7rem);font-weight:900;line-height:1.4}
.sec-head h2::before{content:"";width:10px;height:10px;border-radius:3px;background:var(--c1);flex-shrink:0}
.sec-head h2 span{color:var(--c1)}
.sec-head p{margin-top:4px;color:var(--muted);font-size:.82rem}
.sec-tools{display:flex;align-items:center;gap:8px}
.sec-link{display:inline-flex;align-items:center;padding:7px 18px;border:1px solid var(--line);border-radius:999px;color:var(--c1);font-weight:800;font-size:.78rem;transition:background-color .2s,border-color .2s,color .2s}
.sec-link:hover{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.strip-arrows{display:flex;gap:6px}
.s-arrow{width:38px;height:38px;display:grid;place-items:center;background:var(--cardbg);border:1px solid var(--line);border-radius:50%;transition:background-color .2s,border-color .2s}
.s-arrow:hover{background:var(--c1);border-color:var(--c1)}
.s-arrow svg{width:16px;height:16px;stroke:var(--ink)}
.s-arrow:hover svg{stroke:var(--btnt)}

/* ردیف‌های افقی: فاصله‌ی داخلی کافی تا حاشیه و فوکوس کارت‌ها بریده نشود */
.cat-grid,.strip ul.products{display:flex!important;flex-wrap:nowrap!important;gap:14px;overflow-x:auto;overscroll-behavior-x:contain;scroll-snap-type:x proximity;scrollbar-width:none;padding:6px!important;margin:-6px -6px 0!important}
.cat-grid::-webkit-scrollbar,.strip ul.products::-webkit-scrollbar{display:none}
.strip ul.products li.product{flex:0 0 240px!important;width:240px!important;scroll-snap-align:start}

/* کارت دسته‌بندی */
.cat-card{flex:0 0 160px;display:flex;flex-direction:column;gap:10px;padding:10px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);scroll-snap-align:start;transition:border-color .2s,background-color .2s}
.cat-card:hover{border-color:var(--c1)}
.cat-card img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:var(--rad-sm);background:var(--cream);transition:border-color .2s,outline-color .2s}
.cat-card .txt{text-align:center}
.cat-card h3{font-size:.86rem;font-weight:800;line-height:1.5;transition:color .2s}
.cat-card:hover h3{color:var(--c1)}
.cat-card p{display:inline-block;margin-top:4px;padding:1px 10px;border-radius:999px;background:var(--tint);color:var(--c1);font-size:.64rem;font-weight:800}
body.catst-2 .cat-card{flex-basis:180px;position:relative;padding:0;overflow:hidden;border:0}
body.catst-2 .cat-card img{aspect-ratio:3/4;border-radius:0}
body.catst-2 .cat-card .txt{position:absolute;inset-inline:0;bottom:0;padding:10px;background:color-mix(in srgb,var(--ink) 78%,transparent);transition:background-color .2s}
body.catst-2 .cat-card:hover .txt{background:var(--c1)}
body.catst-2 .cat-card h3,body.catst-2 .cat-card:hover h3{color:#fff}
body.catst-2 .cat-card p{background:rgba(255,255,255,.2);color:#fff}
body.catst-3 .cat-card{flex-basis:230px;flex-direction:row;align-items:center}
body.catst-3 .cat-card img{width:64px;flex-shrink:0}
body.catst-3 .cat-card .txt{text-align:start}
body.catst-4 .cat-card,body.catst-8 .cat-card{background:transparent;border:0;padding:4px}
body.catst-4 .cat-card img{border-radius:50%;outline:2px solid transparent;outline-offset:3px}
body.catst-4 .cat-card:hover img{outline-color:var(--c1)}
body.catst-5 .cat-card{background:var(--tint);border-color:transparent}
body.catst-5 .cat-card:hover{border-color:var(--c1)}
body.catst-6 .cat-card{background:transparent;border-color:transparent}
body.catst-6 .cat-card:hover{background:var(--cardbg);border-color:var(--line)}
body.catst-7 .cat-card{background:var(--c1);border-color:var(--c1)}
body.catst-7 .cat-card:hover{background:color-mix(in srgb,var(--c1) 82%,#000)}
body.catst-7 .cat-card h3,body.catst-7 .cat-card:hover h3{color:var(--btnt)}
body.catst-7 .cat-card p{background:rgba(255,255,255,.2);color:var(--btnt)}
body.catst-8 .cat-card img{border-radius:50%;border:3px solid var(--c2)}
body.catst-8 .cat-card:hover img{border-color:var(--c1)}
body.catst-10 .cat-grid{flex-direction:column;overflow:visible}
body.catst-10 .cat-card{flex:none;width:100%;flex-direction:row;align-items:center;gap:18px;padding:12px 18px}
body.catst-10 .cat-card img{width:160px;height:96px;aspect-ratio:auto;flex-shrink:0}
body.catst-10 .cat-card .txt{flex:1;text-align:start}
body.catst-10 .cat-card h3{font-size:1rem}
body.catst-9 .cat-card,body.prodst-11 ul.products li.prod-card{transition:transform .15s ease-out,border-color .2s}

/* کارت محصول */
ul.products{display:grid!important;grid-template-columns:repeat(var(--pcols,5),minmax(0,1fr))!important;gap:14px;list-style:none;margin:0!important;padding:0!important}
ul.products::before,ul.products::after{display:none!important}
ul.products li.product{float:none!important;width:auto!important;min-width:0;margin:0!important;padding:0!important;text-align:start!important}
ul.products li.prod-card{display:flex;flex-direction:column;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);transition:border-color .2s,background-color .2s}
ul.products li.prod-card:hover{border-color:var(--c1)}
.prod-media{position:relative;display:block;aspect-ratio:1/1;overflow:hidden;background:var(--cream);border-radius:calc(var(--rad) - 1px) calc(var(--rad) - 1px) 0 0}
.prod-media img{width:100%!important;height:100%!important;margin:0!important;object-fit:cover;transition:transform .4s ease}
.prod-card:hover .prod-media img{transform:scale(1.04)}
.badge{position:absolute;top:10px;inset-inline-start:10px;z-index:1;padding:2px 10px;border-radius:999px;font-size:.64rem;font-weight:800;line-height:1.7}
.badge.hot{background:var(--hot);color:#fff}
.badge.new{background:var(--c1);color:var(--btnt)}
.badge.best{background:var(--cardbg);color:var(--ink);border:1px solid var(--line)}
.prod-body{flex:1;display:flex;flex-wrap:wrap;align-items:flex-end;align-content:flex-start;gap:6px;padding:12px}
.prod-cat{width:100%;color:var(--c1);font-size:.66rem;font-weight:800}
.prod-name{width:100%;min-height:3.2em;font-size:.84rem;font-weight:800;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-name a{transition:color .2s}
.prod-name a:hover{color:var(--c1)}
.prod-desc{width:100%;color:var(--muted);font-size:.68rem;line-height:1.9;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-vars{width:100%;display:flex;flex-wrap:wrap;gap:4px}
.prod-vars span{padding:1px 9px;border-radius:999px;background:var(--tint);color:var(--c1);font-size:.62rem;font-weight:800}
.prod-price{flex:1;min-width:0;margin-top:auto;line-height:1.5}
.prod-price .amount{font-size:.9rem;font-weight:900;color:var(--pricec)}
.prod-price ins{text-decoration:none}
.prod-price del{display:block;color:var(--muted);font-size:.66rem}
.prod-price del .amount{font-size:.66rem;font-weight:500;color:var(--muted)}
.prod-body a.button{position:relative;flex-shrink:0;display:block!important;width:40px!important;height:40px!important;min-height:0!important;margin:0!important;padding:0!important;background:var(--cardbg)!important;border:1px solid var(--line)!important;border-radius:var(--rad-sm)!important;color:transparent!important;font-size:0!important;line-height:0!important;overflow:hidden;transition:background-color .2s,border-color .2s}
.prod-body a.button::before{content:"";position:absolute;inset:0;margin:auto;width:20px;height:20px;background:var(--carticon);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM7.1 14.9h10.4c.75 0 1.41-.41 1.75-1.02l3.58-6.49A1 1 0 0 0 22 6.9H5.21l-.94-2H1v2h2l3.6 7.59-1.35 2.45C4.52 15.37 5.48 17 7 17h12v-2H7.42l1.09-2.09z'/%3E%3C/svg%3E") center/contain no-repeat}
.prod-body a.button::after{display:none!important}
.prod-body a.button:hover{background:var(--c1)!important;border-color:var(--c1)!important}
.prod-body a.button:hover::before{background:var(--carticonh)}
.prod-body a.button.loading{opacity:.55;pointer-events:none}
.prod-body a.button.added::before{-webkit-mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z'/%3E%3C/svg%3E");mask-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z'/%3E%3C/svg%3E")}
.prod-body a.added_to_cart{order:9;width:100%;padding-top:2px;text-align:center;color:var(--c1);font-size:.7rem;font-weight:800}
body.prodst-2 ul.products li.prod-card{background:transparent;border-color:transparent}
body.prodst-2 .prod-media{border-radius:var(--rad)}
body.prodst-2 .prod-body{padding:10px 2px}
body.prodst-3 ul.products li.prod-card{border-top:3px solid var(--c1)}
body.prodst-5 ul.products li.prod-card{background:var(--tint);border-color:transparent}
body.prodst-5 ul.products li.prod-card:hover{border-color:var(--c1)}
body.prodst-6 ul.products li.prod-card{border:2px solid var(--c1)}
body.prodst-6 ul.products li.prod-card:hover{border-color:var(--hot)}
body.prodst-7 ul.products li.prod-card{background:transparent;border-color:transparent}
body.prodst-7 .prod-media{aspect-ratio:4/5;border-radius:var(--rad)}
body.prodst-7 .prod-body{padding:12px 0}
body.prodst-7 .prod-name{font-size:.92rem}
body.prodst-8 ul.products li.prod-card{padding:8px}
body.prodst-8 .prod-media{border-radius:var(--rad-sm)}
body.prodst-8 .prod-body{padding:10px 4px 4px}
body.prodst-4 ul.products li.prod-card,body.prodst-9 ul.products li.prod-card{background:var(--footerbg);border-color:var(--footerbg)}
body.prodst-9 ul.products li.prod-card{border-color:color-mix(in srgb,var(--c1) 50%,var(--footerbg))}
body.prodst-4 ul.products li.prod-card:hover,body.prodst-9 ul.products li.prod-card:hover{border-color:var(--c2)}
body.prodst-10 .prod-media::after{content:"";position:absolute;top:0;bottom:0;left:-70%;width:45%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.5),transparent);transform:skewX(-20deg);transition:left .6s ease;pointer-events:none}
body.prodst-10 .prod-card:hover .prod-media::after{left:130%}

/* کارت‌های تیره: بخش تخفیف و مدل‌های تیره کارت محصول */
.sale-sec .prod-media,body.prodst-4 .prod-media,body.prodst-9 .prod-media{background:rgba(255,255,255,.06)}
.sale-sec .prod-name a,body.prodst-4 .prod-name a,body.prodst-9 .prod-name a{color:#fff}
.sale-sec .prod-cat,body.prodst-4 .prod-cat,body.prodst-9 .prod-cat{color:var(--c2)}
.sale-sec .prod-desc,body.prodst-4 .prod-desc,body.prodst-9 .prod-desc{color:rgba(255,255,255,.6)}
.sale-sec .prod-vars span,body.prodst-4 .prod-vars span,body.prodst-9 .prod-vars span{background:rgba(255,255,255,.1);color:var(--c2)}
.sale-sec .prod-price .amount,body.prodst-4 .prod-price .amount,body.prodst-9 .prod-price .amount{color:var(--c2)}
.sale-sec .prod-price del,.sale-sec .prod-price del .amount,body.prodst-4 .prod-price del,body.prodst-4 .prod-price del .amount,body.prodst-9 .prod-price del,body.prodst-9 .prod-price del .amount{color:rgba(255,255,255,.45)}
.sale-sec .prod-body a.button,body.prodst-4 .prod-body a.button,body.prodst-9 .prod-body a.button{background:rgba(255,255,255,.06)!important;border-color:var(--dark-line)!important}
.sale-sec .prod-body a.button::before,body.prodst-4 .prod-body a.button::before,body.prodst-9 .prod-body a.button::before{background:var(--c2)}
.sale-sec .prod-body a.button:hover,body.prodst-4 .prod-body a.button:hover,body.prodst-9 .prod-body a.button:hover{background:var(--c2)!important;border-color:var(--c2)!important}
.sale-sec .prod-body a.button:hover::before,body.prodst-4 .prod-body a.button:hover::before,body.prodst-9 .prod-body a.button:hover::before{background:var(--footerbg)}
.sale-sec .prod-body a.added_to_cart,body.prodst-4 .prod-body a.added_to_cart,body.prodst-9 .prod-body a.added_to_cart{color:var(--c2)}
.sale-sec .badge.best,body.prodst-4 .badge.best,body.prodst-9 .badge.best{background:rgba(255,255,255,.12);color:#fff;border-color:var(--dark-line)}

/* بخش تخفیف‌ها */
.sale-sec{padding:44px 0;background:var(--footerbg);color:var(--ftext)}
.sale-sec .sec-head{border-bottom-color:var(--dark-line)}
.sale-sec .sec-head h2{color:#fff}
.sale-sec .sec-head h2::before{background:var(--c2)}
.sale-sec .sec-head p{color:var(--ftext);opacity:.75}
.sale-sec .sec-link{border-color:var(--dark-line);color:var(--ftext)}
.sale-sec .sec-link:hover{background:var(--c2);border-color:var(--c2);color:var(--footerbg)}
.sale-sec .s-arrow{background:transparent;border-color:var(--dark-line)}
.sale-sec .s-arrow svg{stroke:var(--ftext)}
.sale-sec .s-arrow:hover{background:var(--c2);border-color:var(--c2)}
.sale-sec .s-arrow:hover svg{stroke:var(--footerbg)}
.sale-sec ul.products li.prod-card{background:rgba(255,255,255,.04);border-color:var(--dark-line)}
.sale-sec ul.products li.prod-card:hover{border-color:var(--c2)}
.sale-sec ul.products li.product{flex:0 0 210px!important;width:210px!important}
.sale-sec .prod-cat,.sale-sec .prod-desc{display:none}

/* پیشنهاد ویژه */
.offer-sec{background:var(--footerbg);color:var(--ftext);padding:48px 0}
.offer{display:grid;grid-template-columns:1.1fr .9fr;gap:32px;align-items:center}
.offer h3{color:var(--c2);font-size:.82rem;font-weight:800}
.offer h2{margin:8px 0;color:#fff;font-size:clamp(1.3rem,2.6vw,1.9rem);font-weight:900;line-height:1.5}
.offer .od{margin-bottom:16px;color:var(--ftext);font-size:.88rem;line-height:1.9;opacity:.85}
.count{display:flex;gap:8px;margin-bottom:18px}
.count .cell{min-width:62px;padding:8px 6px;text-align:center;border:1px solid var(--dark-line);border-radius:var(--rad-sm)}
.count .cell b{display:block;color:#fff;font-size:1.3rem;font-weight:900;line-height:1.4}
.count .cell span{font-size:.64rem;color:var(--ftext)}
.offer-visual{position:relative}
.offer-visual img{width:100%;aspect-ratio:16/11;object-fit:cover;border-radius:var(--rad)}
.offer-off{position:absolute;top:12px;inset-inline-start:12px;z-index:1;padding:4px 14px;border-radius:999px;background:var(--hot);color:#fff;font-size:.9rem;font-weight:900}

/* مزایا */
.features{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.feat{padding:22px 18px;text-align:center;background:var(--cardbg);border:1px solid var(--line);border-top:3px solid var(--c1);border-radius:var(--rad);transition:border-color .2s}
.feat:hover{border-color:var(--c1)}
.feat h4{margin-bottom:6px;font-size:.95rem;font-weight:900}
.feat p{color:var(--muted);font-size:.78rem;line-height:1.9}

/* برندها */
.brands-strip{direction:ltr;overflow:hidden;padding-block:4px}
.brands-track{display:flex;gap:14px;width:max-content;animation:brandslide var(--br-speed,30s) linear infinite}
.brands-strip:hover .brands-track{animation-play-state:paused}
.brands-strip.is-float,.brands-strip.is-static{overflow:visible}
.brands-strip.is-float .brands-track,.brands-strip.is-static .brands-track{width:auto;flex-wrap:wrap;justify-content:center;animation:none}
.brands-strip.is-float .brand-item{animation:brandFloat 4s ease-in-out infinite}
.brands-strip.is-float .brand-item:nth-child(even){animation-delay:-2s}
@keyframes brandslide{to{transform:translateX(-50%)}}
@keyframes brandFloat{50%{transform:translateY(-6px)}}
.brand-item{flex-shrink:0;height:64px;min-width:120px;display:flex;align-items:center;justify-content:center;padding:10px 20px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);transition:border-color .2s}
.brand-item img{max-height:40px;width:auto;object-fit:contain;filter:grayscale(1);opacity:.7;transition:filter .2s,opacity .2s}
.brand-item:hover{border-color:var(--c1)}
.brand-item:hover img{filter:none;opacity:1}

/* کمپین */
.campaign-sec{background:var(--c1);color:var(--btnt);padding:56px 0}
.campaign{text-align:center}
.campaign h2{font-size:clamp(1.3rem,3vw,2rem);font-weight:900}
.campaign p{max-width:600px;margin:10px auto 22px;line-height:2;opacity:.92}
.campaign .btn-light:hover{color:var(--btnt);border-color:var(--btnt)}

/* گالری استایل */
.look-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
.look-item{position:relative;display:block;aspect-ratio:3/4;overflow:hidden;border-radius:var(--rad);background:var(--cream)}
.look-item img{width:100%;height:100%!important;object-fit:cover;transition:transform .5s ease}
.look-item:hover img{transform:scale(1.05)}
.look-item .lk-cap{position:absolute;bottom:0;inset-inline:0;padding:12px 16px;background:color-mix(in srgb,var(--ink) 80%,transparent);color:#fff;font-size:.85rem;font-weight:800;opacity:0;transition:opacity .25s}
.look-item:hover .lk-cap,.look-item:focus-visible .lk-cap{opacity:1}

/* نظرات */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.testi{display:flex;flex-direction:column;padding:22px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);transition:border-color .2s}
.testi:hover{border-color:var(--c1)}
.testi .stars{margin-bottom:8px;color:var(--c2);letter-spacing:3px;font-size:.95rem}
.testi p{color:var(--muted);font-size:.86rem;line-height:2}
.testi .who{display:flex;align-items:center;gap:10px;margin-top:auto;padding-top:14px}
.testi .av{width:42px;height:42px;display:grid;place-items:center;flex-shrink:0;border-radius:50%;background:var(--c1);color:var(--btnt);font-weight:900}
.testi .who b{display:block;font-size:.84rem;line-height:1.5}
.testi .who span{color:var(--muted);font-size:.7rem}

/* سؤالات متداول */
.faq-list{display:grid;gap:10px;max-width:840px;margin-inline:auto;text-align:start}
.faq-item{background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);transition:border-color .2s}
.faq-item[open],.faq-item:hover{border-color:var(--c1)}
.faq-item summary{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:14px 18px;list-style:none;cursor:pointer;font-size:.9rem;font-weight:800}
.faq-item summary::-webkit-details-marker{display:none}
.faq-item summary::after{content:"+";color:var(--c1);font-size:1.4rem;line-height:1;transition:transform .2s}
.faq-item[open] summary::after{transform:rotate(45deg)}
.faq-item .fa-a{padding:0 18px 16px;color:var(--muted);font-size:.86rem;line-height:2}

/* مقالات */
.blog-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
.post-card{display:flex;flex-direction:column;overflow:hidden;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);transition:border-color .2s}
.post-card:hover{border-color:var(--c1)}
.post-card img{width:100%;height:auto;aspect-ratio:16/9;object-fit:cover;background:var(--cream)}
.post-card .pb{flex:1;display:flex;flex-direction:column;gap:6px;padding:16px 18px}
.post-card h3{font-size:.94rem;font-weight:800;line-height:1.8}
.post-card p{color:var(--muted);font-size:.8rem;line-height:1.9}
.post-card .more{margin-top:auto;color:var(--c1);font-size:.78rem;font-weight:800}
.post-card .pdate{color:var(--muted);font-size:.68rem}

/* درباره ما */
.about-home{display:grid;grid-template-columns:380px 1fr;gap:36px;align-items:center}
.about-home .ah-img img{width:100%;height:340px!important;object-fit:cover;border-radius:var(--rad)}
.about-home h2{margin-bottom:10px;font-size:1.5rem;font-weight:900}
.about-home p{color:var(--muted);font-size:.92rem;line-height:2.1}
.about-home .ah-more{margin-top:18px}
.logo-tile{width:100%;max-width:340px;aspect-ratio:1/1;display:grid;place-items:center;padding:48px;background:var(--tint);border-radius:var(--rad)}
.logo-tile img{width:100%;height:100%!important;object-fit:contain}

/* اینستاگرام و خبرنامه */
.insta-sec{background:var(--cardbg);border-block:1px solid var(--line)}
.insta-strip{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap}
.insta-strip h3{font-size:1.05rem;font-weight:900}
.insta-strip p{margin-top:4px;color:var(--muted);font-size:.82rem}
.news-sec{background:var(--tint)}
.news{text-align:center}
.news h2{margin-bottom:8px;font-size:1.4rem;font-weight:900}
.news p{margin-bottom:20px;color:var(--muted);font-size:.86rem}
.news form{display:flex;gap:10px;max-width:520px;margin-inline:auto}
.news input{flex:1;min-width:0;padding:12px 18px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);outline:none;transition:border-color .2s}
.news input:focus{border-color:var(--c1)}
body.btnst-pill .news input{border-radius:999px}

/* فروشگاه */
.shop-main{padding-block:24px 64px}
.crumbs{margin-bottom:12px}
.woocommerce-breadcrumb{margin:0!important;color:var(--muted)!important;font-size:.78rem!important}
.woocommerce-breadcrumb a{color:var(--c1)!important}
.page-banner{position:relative;display:flex;align-items:flex-end;min-height:200px;margin-bottom:20px;overflow:hidden;border-radius:var(--rad);background:var(--cream)}
.page-banner img{position:absolute;inset:0;width:100%;height:100%!important;object-fit:cover}
.pb-txt{position:relative;width:100%;padding:16px 22px;background:color-mix(in srgb,#000 55%,transparent);color:#fff}
.pb-txt h1{font-size:1.5rem;font-weight:900;line-height:1.5}
.pb-txt p{margin-top:2px;color:rgba(255,255,255,.85);font-size:.85rem}
.catbar{display:flex;gap:8px;overflow-x:auto;padding:4px;margin:0 -4px 12px;scrollbar-width:none}
.catbar::-webkit-scrollbar{display:none}
.catbar a{flex-shrink:0;padding:7px 18px;background:var(--cardbg);border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:.8rem;font-weight:800;transition:background-color .2s,border-color .2s,color .2s}
.catbar a:hover{border-color:var(--c1);color:var(--c1)}
.catbar a.active{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.catbar a.catbar-sub{border-style:dashed}
.shop-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:20px;padding-block:10px;border-block:1px solid var(--line)}
.shop-toolbar .woocommerce-result-count,.shop-toolbar .woocommerce-ordering{float:none!important;margin:0!important}
.woocommerce-result-count{color:var(--muted);font-size:.8rem}
.woocommerce-ordering select{padding:8px 12px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);font-size:.82rem}
.stock-toggle{padding:7px 16px;background:var(--cardbg);border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:.78rem;font-weight:800;transition:border-color .2s,color .2s}
.stock-toggle:hover{border-color:var(--c1);color:var(--c1)}
.stock-toggle.on{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.col-switch{display:flex;align-items:center;gap:6px}
.col-switch span{margin-inline-end:2px;color:var(--muted);font-size:.72rem;font-weight:700}
.col-switch button{width:34px;height:34px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);color:var(--muted);font-size:.8rem;font-weight:800;transition:border-color .2s,color .2s}
.col-switch button:hover{border-color:var(--c1);color:var(--c1)}
.col-switch button.active{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.shop-pagination{display:flex;justify-content:center;margin-top:32px}
.woocommerce nav.woocommerce-pagination ul,.woocommerce-pagination ul,.nav-links{display:flex;gap:6px;margin:0;padding:0;list-style:none;border:0!important}
.woocommerce nav.woocommerce-pagination ul li{border:0!important;overflow:visible!important}
.woocommerce-pagination ul li a,.woocommerce-pagination ul li span,.nav-links a,.nav-links span{display:grid!important;place-items:center;min-width:40px;height:40px;padding:0 12px!important;background:var(--cardbg)!important;border:1px solid var(--line)!important;border-radius:var(--rad-sm);color:var(--ink)!important;font-weight:800;transition:border-color .2s,color .2s}
.woocommerce-pagination ul li a:hover,.nav-links a:hover{border-color:var(--c1)!important;color:var(--c1)!important}
.woocommerce-pagination ul li .current,.nav-links .current{background:var(--c1)!important;border-color:var(--c1)!important;color:var(--btnt)!important}
.empty-state{display:grid;justify-items:center;gap:16px;padding:60px 20px;text-align:center;color:var(--muted);font-weight:700;border:1px dashed var(--line);border-radius:var(--rad)}

/* صفحات */
.page-main{padding-block:40px 70px}
.page-hero{display:flex;align-items:center;flex-wrap:wrap;gap:40px;padding-bottom:36px;margin-bottom:28px;border-bottom:1px solid var(--line)}
.page-hero-img{width:340px;flex-shrink:0}
.page-hero-img img{width:100%;height:300px!important;object-fit:cover;border-radius:var(--rad)}
.page-hero .logo-tile{width:280px;flex-shrink:0}
.page-hero-txt h1{font-size:clamp(1.6rem,3.4vw,2.4rem);font-weight:900;line-height:1.5}
.page-hero-txt p{margin-top:8px;color:var(--muted);font-size:.95rem;line-height:2}
.page-title{margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--line);font-size:1.6rem;font-weight:900;line-height:1.5}
.page-content{color:var(--muted);line-height:2.2}
.page-content h2,.page-content h3{margin:16px 0 8px;color:var(--ink);font-weight:800}
.page-content p{margin-bottom:12px}
.page-content img{border-radius:var(--rad)}
.page-content a{color:var(--c1)}
.map-box{margin-top:24px}
.map-box iframe{width:100%;height:300px;border:0;border-radius:var(--rad)}

/* صفحه محصول */
.pd-main{padding-block:24px 64px}
body.pd-full .pd-main{max-width:none;padding-inline:30px}
.pd-wrap{display:grid;grid-template-columns:1.05fr .95fr;gap:40px;align-items:start}
.pd-gallery .woocommerce-product-gallery{position:relative;width:100%!important;float:none!important;margin:0!important;opacity:1!important}
.pd-gallery .woocommerce-product-gallery__trigger{position:absolute;top:12px;left:12px;z-index:5}
.pd-gallery .flexslider{height:auto!important;margin:0;background:transparent!important;border:none!important}
.pd-gallery .woocommerce-product-gallery__image,.pd-gallery .flex-viewport{aspect-ratio:1/1;max-height:none!important;overflow:hidden;border-radius:var(--rad);background:var(--cream)}
.pd-gallery .flex-viewport .woocommerce-product-gallery__image{border-radius:0}
.pd-gallery .woocommerce-product-gallery__image img{width:100%;height:100%!important;object-fit:cover}
.pd-gallery .flex-control-thumbs{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px!important;padding:0;list-style:none}
.pd-gallery .flex-control-thumbs li{width:72px!important;float:none!important;margin:0!important}
.pd-gallery .flex-control-thumbs img{aspect-ratio:1/1;object-fit:cover;border:2px solid transparent;border-radius:var(--rad-sm);opacity:1!important;cursor:pointer;transition:border-color .2s}
.pd-gallery .flex-control-thumbs img:hover{border-color:var(--c2)}
.pd-gallery .flex-control-thumbs .flex-active{border-color:var(--c1)}
.pd-cats{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.pd-cats a{padding:3px 14px;border-radius:999px;background:var(--tint);color:var(--c1);font-size:.72rem;font-weight:800;transition:background-color .2s,color .2s}
.pd-cats a:hover{background:var(--c1);color:var(--btnt)}
.pd-summary .product_title{font-size:1.7rem;font-weight:900;line-height:1.5}
.pd-summary .woocommerce-product-rating{display:flex;align-items:center;gap:8px;margin:8px 0}
.pd-summary .star-rating{color:var(--c2)}
.pd-pricebox{display:flex;align-items:center;gap:10px;margin:12px 0 14px;padding-block:12px;border-block:1px solid var(--line)}
.pd-pricebox .price{color:var(--pricec);font-size:1.4rem;font-weight:900}
.pd-pricebox .price del{color:var(--muted);font-size:.9rem;font-weight:500}
.pd-pricebox .price ins{text-decoration:none}
.pd-excerpt p{color:var(--muted);line-height:2.1}
.pd-summary form.cart{display:flex;flex-wrap:wrap;align-items:center;gap:12px;margin-top:20px}
.pd-summary form.variations_form{flex-direction:column;align-items:stretch}
.pd-summary table.variations{width:100%;border-collapse:collapse}
.pd-summary table.variations th,.pd-summary table.variations td{padding:6px 0;text-align:start;vertical-align:middle}
.pd-summary table.variations label{font-size:.85rem;font-weight:800}
.pd-summary table.variations select{width:100%;padding:10px 12px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm)}
.pd-summary .reset_variations{color:var(--hot);font-size:.75rem}
.pd-summary .woocommerce-variation-add-to-cart{display:flex;flex-wrap:wrap;align-items:center;gap:12px}
.pd-summary .qty{width:76px;height:50px;text-align:center;font-weight:800;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm)}
.pd-summary .single_add_to_cart_button{padding:13px 34px!important;background:var(--c1)!important;border:2px solid var(--c1)!important;border-radius:var(--rad)!important;color:var(--btnt)!important;font-size:1rem!important;font-weight:900!important;cursor:pointer;transition:background-color .2s,border-color .2s}
.pd-summary .single_add_to_cart_button:hover{background:color-mix(in srgb,var(--c1) 82%,#000)!important;border-color:color-mix(in srgb,var(--c1) 82%,#000)!important}
body.btnst-pill .pd-summary .single_add_to_cart_button{border-radius:999px!important}
.pd-trust{display:flex;flex-wrap:wrap;gap:8px;margin-top:20px}
.pd-trust span{padding:6px 14px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-size:.74rem;font-weight:800}
.pd-meta,.pd-summary .product_meta{display:grid;gap:4px;margin-top:18px;color:var(--muted);font-size:.8rem}
.pd-summary .product_meta>span{display:block}
.pd-summary .product_meta a{color:var(--c1);font-weight:700}
.pd-tabs{margin-top:40px;padding-top:24px;border-top:1px solid var(--line)}
.pd-tabs .woocommerce-tabs{margin:0}
.pd-tabs .wc-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 20px!important;padding:0!important;list-style:none;border:0}
.pd-tabs .wc-tabs::before,.pd-tabs .wc-tabs li::before,.pd-tabs .wc-tabs li::after{display:none!important}
.pd-tabs .wc-tabs li{margin:0!important;padding:0!important;background:none!important;border:0!important}
.pd-tabs .wc-tabs li a{display:block;padding:9px 24px;border:1px solid var(--line);border-radius:999px;color:var(--muted);font-weight:800;transition:border-color .2s,color .2s}
.pd-tabs .wc-tabs li a:hover{border-color:var(--c1);color:var(--c1)}
.pd-tabs .wc-tabs li.active a{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.pd-tabs .woocommerce-Tabs-panel{color:var(--muted);line-height:2.2}
.pd-tabs h2{margin:0 0 12px;color:var(--ink);font-size:1rem;font-weight:900}
.pd-related{margin-top:48px}
.pd-related-title{margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid var(--line);font-size:1.25rem;font-weight:900}
.pd-related-title span{color:var(--c1)}
.pd-related section.related>h2{display:none}

/* ووکامرس: سبد، تسویه و حساب کاربری */
table.shop_table{width:100%;overflow:hidden;background:var(--cardbg);border:1px solid var(--line)!important;border-radius:var(--rad)!important;border-collapse:separate!important;border-spacing:0}
table.shop_table th{padding:12px 14px!important;background:var(--tint);font-size:.82rem}
table.shop_table td{padding:12px 14px!important;border-top:1px solid var(--line)!important}
table.shop_table img{width:58px!important;height:58px!important;object-fit:cover;border-radius:var(--rad-sm)}
.woocommerce a.remove{color:var(--hot)!important;font-weight:800}
.woocommerce a.remove:hover{background:var(--hot)!important;color:#fff!important}
.woocommerce .button,.woocommerce button.button,.woocommerce a.button,.woocommerce input.button{padding:10px 22px!important;background:var(--cardbg)!important;border:1px solid var(--c1)!important;border-radius:var(--rad)!important;color:var(--c1)!important;font-weight:800!important;transition:background-color .2s,color .2s}
.woocommerce .button:hover,.woocommerce button.button:hover,.woocommerce a.button:hover,.woocommerce input.button:hover{background:var(--c1)!important;color:var(--btnt)!important}
.woocommerce .button.alt,.woocommerce button.button.alt,.woocommerce a.button.alt,#place_order,.wc-proceed-to-checkout .checkout-button{background:var(--c1)!important;border-color:var(--c1)!important;color:var(--btnt)!important}
.woocommerce .button.alt:hover,#place_order:hover,.wc-proceed-to-checkout .checkout-button:hover{background:color-mix(in srgb,var(--c1) 82%,#000)!important}
body.btnst-pill .woocommerce .button,body.btnst-pill #place_order{border-radius:999px!important}
.woocommerce form .form-row label{display:block;margin-bottom:6px;font-size:.82rem;font-weight:700}
.woocommerce form .form-row input.input-text,.woocommerce form .form-row textarea,.woocommerce form .form-row select,.woocommerce .coupon input.input-text{width:100%;padding:11px 12px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);outline:none;transition:border-color .2s}
.woocommerce form .form-row input.input-text:focus,.woocommerce form .form-row textarea:focus{border-color:var(--c1)}
.woocommerce #payment{padding:18px;background:var(--cardbg)!important;border:1px solid var(--line);border-radius:var(--rad)!important}
.woocommerce #payment ul.payment_methods{display:grid;gap:10px;padding:0;list-style:none;border:0}
.woocommerce #payment div.payment_box{background:var(--tint)!important;border-radius:var(--rad-sm)}
.woocommerce #payment div.payment_box::before{display:none}
.woocommerce-message,.woocommerce-info,.woocommerce-error{margin:0 0 16px!important;padding:12px 18px!important;border:1px solid var(--line)!important;border-inline-start:4px solid var(--c1)!important;border-radius:var(--rad-sm);background:var(--cardbg)!important;color:var(--ink)!important;font-weight:700;list-style:none}
.woocommerce-message::before,.woocommerce-info::before,.woocommerce-error::before{display:none!important}
.woocommerce-error{border-inline-start-color:var(--hot)!important}
.woocommerce-MyAccount-navigation{float:none!important;width:auto!important}
.woocommerce-MyAccount-navigation ul{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px;list-style:none}
.woocommerce-MyAccount-navigation ul li a{display:block;padding:8px 18px;background:var(--cardbg);border:1px solid var(--line);border-radius:999px;color:var(--muted);font-weight:700;transition:border-color .2s,color .2s}
.woocommerce-MyAccount-navigation ul li a:hover{border-color:var(--c1);color:var(--c1)}
.woocommerce-MyAccount-navigation ul li.is-active a{background:var(--c1);border-color:var(--c1);color:var(--btnt)}
.woocommerce-MyAccount-content{float:none!important;width:auto!important}
.col2-set{display:grid;grid-template-columns:1fr 1fr;gap:28px}
.col2-set::before,.col2-set::after{display:none!important}
.col2-set .col-1,.col2-set .col-2{float:none!important;width:auto!important}

/* فوتر */
footer{padding-top:48px;background:var(--footerbg);color:var(--ftext)}
.foot-grid{display:grid;grid-template-columns:1.3fr 1fr 1fr 1.2fr;gap:36px;padding-bottom:36px}
.foot-grid h5{margin-bottom:12px;color:#fff;font-size:.95rem;font-weight:900}
.foot-grid a,.foot-grid p{display:block;color:var(--ftext);font-size:.84rem;line-height:2.3}
.foot-grid a{transition:color .2s}
.foot-grid a:hover{color:var(--c2)}
.foot-brand img{height:56px;width:auto;margin-bottom:10px;object-fit:contain}
.foot-social{display:flex;gap:8px;margin-top:12px}
.foot-social a{width:40px;height:40px;display:grid;place-items:center;border:1px solid var(--dark-line);border-radius:var(--rad-sm);transition:background-color .2s,border-color .2s}
.foot-social a:hover{background:var(--c2);border-color:var(--c2)}
.foot-social svg{width:18px;height:18px;stroke:#fff}
.foot-social a:hover svg{stroke:var(--footerbg)}
.foot-bottom{display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;padding:16px 0;border-top:1px solid var(--dark-line);font-size:.76rem}
.foot-bottom b{color:var(--c2)}
.peecha-link{padding:2px 10px;border:1px solid var(--dark-line);border-radius:999px;color:var(--c2);font-weight:800;transition:background-color .2s,color .2s}
.peecha-link:hover{background:var(--c2);color:var(--footerbg)}

/* کشوی سبد خرید */
#overlay{position:fixed;inset:0;z-index:90;background:color-mix(in srgb,var(--footerbg) 50%,transparent);opacity:0;pointer-events:none;transition:opacity .3s}
#overlay.show{opacity:1;pointer-events:auto}
#cartDrawer{position:fixed;top:0;bottom:0;left:0;z-index:95;width:min(380px,94vw);display:flex;flex-direction:column;background:var(--cardbg);border-right:1px solid var(--line);transform:translateX(-105%);transition:transform .35s ease}
#cartDrawer.show{transform:none}
.cart-head{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid var(--line)}
.cart-head h3{font-size:.95rem;font-weight:900}
#closeCart{width:34px;height:34px;display:grid;place-items:center;border:1px solid var(--line);border-radius:var(--rad-sm);transition:border-color .2s}
#closeCart:hover{border-color:var(--hot)}
#closeCart svg{width:15px;height:15px;stroke:var(--ink)}
.drawer-body{flex:1;overflow-y:auto;padding:12px}
.drawer-body ul{display:flex!important;flex-direction:column;gap:8px;margin:0;padding:0;list-style:none}
.drawer-body li{position:relative;display:flex!important;align-items:center;gap:10px;padding:8px!important;border:1px solid var(--line);border-radius:var(--rad-sm)}
.drawer-body li img{width:48px!important;height:48px!important;margin:0!important;object-fit:cover;border-radius:var(--rad-sm);flex-shrink:0;float:none!important}
.drawer-body li a:not(.remove){flex:1;display:flex;align-items:center;gap:10px;color:var(--ink);font-size:.74rem;font-weight:700;line-height:1.5}
.drawer-body li .quantity{display:block;color:var(--c1);font-size:.66rem;font-weight:800}
.drawer-body li a.remove{position:static!important;flex-shrink:0;order:9;width:24px;height:24px;display:grid!important;place-items:center;border-radius:50%;color:var(--hot)!important;font-weight:900}
.drawer-body li a.remove:hover{background:var(--hot)!important;color:#fff!important}
.drawer-body .woocommerce-mini-cart__empty-message{padding:30px 10px;text-align:center;color:var(--muted);font-size:.82rem;font-weight:700}
.drawer-body .woocommerce-mini-cart__total{display:flex;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);font-size:.86rem;font-weight:800}
.drawer-body .woocommerce-mini-cart__buttons{display:none}
.cart-foot{display:grid;gap:8px;padding:12px;border-top:1px solid var(--line)}
.cart-foot .btn{width:100%}
.cart-foot-link{text-align:center;color:var(--muted);font-size:.76rem;font-weight:700}
.cart-foot-link:hover{color:var(--c1)}
#toast{position:fixed;bottom:24px;left:50%;z-index:120000;padding:12px 24px;background:var(--footerbg);color:#fff;border-radius:999px;font-size:.84rem;font-weight:700;opacity:0;pointer-events:none;transform:translate(-50%,20px);transition:opacity .3s,transform .3s}
#toast.show{opacity:1;transform:translate(-50%,0)}

/* جستجوی تمام‌صفحه */
.sh-searchover{position:fixed;inset:0;z-index:120;display:none;align-items:flex-start;justify-content:center;padding:90px 22px;background:var(--bg)}
.sh-searchover.open{display:flex;animation:fadeIn .2s ease}
.so-box{position:relative;width:100%;max-width:560px}
.so-box form{display:flex;gap:10px}
.so-box input[type=search]{flex:1;min-width:0;padding:12px 18px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad);font-size:.95rem;outline:none}
.so-box input[type=search]:focus{border-color:var(--c1)}
.so-box .btn{flex-shrink:0}
.so-box .sh-live{position:static;width:100%;max-height:320px;margin-top:12px}
.so-close{position:absolute;top:-58px;inset-inline-start:0;width:42px;height:42px;display:grid;place-items:center;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm)}
.so-close svg{width:18px;height:18px;stroke:var(--ink)}
.so-hint{margin-top:14px;text-align:center;color:var(--muted);font-size:.76rem;font-weight:700}

/* منوی موبایل */
.sh-mnav{position:fixed;inset:0;z-index:110;overflow-y:auto;padding:20px 22px 46px;background:var(--bg);transform:translateY(-102%);transition:transform .4s ease}
body.sh-menu-open{overflow:hidden}
body.sh-menu-open .sh-mnav{transform:none}
.sh-mnav-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--line)}
.sh-mnav-top img{height:46px;width:auto;object-fit:contain}
.sh-close{width:42px;height:42px;display:grid;place-items:center;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm)}
.sh-close svg{width:18px;height:18px;stroke:var(--ink)}
.sh-mnav nav{display:flex;flex-direction:column}
.sh-mnav nav>a,.sh-mnav .dd-toggle{display:flex;justify-content:space-between;align-items:center;width:100%;padding:14px 4px;border-bottom:1px solid var(--line);border-radius:0;background:transparent;color:var(--ink);font-size:1rem;font-weight:800;transition:color .2s}
.sh-mnav nav>a:hover,.sh-mnav .dd-toggle:hover,.sh-mnav .dd.open>.dd-toggle{color:var(--c1);background:transparent}
.sh-mnav .dd{position:static}
.sh-mnav .dd-menu{position:static;width:100%;max-height:none;margin:0;padding:8px 0 12px;overflow:visible;grid-template-columns:1fr;gap:6px;background:transparent;border:0;border-radius:0;animation:none}
.sh-mnav .dd-card{padding:8px 4px;font-size:.95rem;font-weight:700}
.sh-mnav .dd-card img{display:none}
.sh-mnav .dd-sub{padding-inline-start:4px}
.sh-mnav .dd-all{margin-top:4px;border:1px solid var(--line);border-radius:var(--rad-sm)}
body.mnav-carded .sh-mnav .dd-sub{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;padding:6px 0}
body.mnav-carded .sh-mnav .dd-sublink{padding:10px 12px;background:var(--cardbg);border:1px solid var(--line);border-radius:var(--rad-sm);color:var(--ink);font-size:.8rem}
body.mnav-carded .sh-mnav .dd-sublink:hover{border-color:var(--c1);color:var(--c1)}
.sh-mnav .sh-login{margin-top:18px}
.sh-mnav .sh-login .btn{width:100%}

/* نوار پایین موبایل و دکمه شناور تماس */
.bn-bottombar{display:none}
.bb-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;padding:7px 4px;border-radius:var(--rad-sm);color:var(--muted);font-size:.64rem;font-weight:800;transition:background-color .2s,color .2s}
.bb-item svg{width:20px;height:20px;stroke:currentColor}
.bb-item.active,.bb-item:hover{background:var(--tint);color:var(--c1)}
.fab-wrap{position:fixed;z-index:85;display:flex;flex-direction:column;align-items:center;gap:10px}
.fab-wrap.pos-bl{bottom:24px;left:24px}
.fab-wrap.pos-br{bottom:24px;right:24px}
.fab-wrap.pos-ml{top:50%;left:18px;transform:translateY(-50%)}
.fab-wrap.pos-mr{top:50%;right:18px;transform:translateY(-50%)}
.fab-btn{width:54px;height:54px;display:grid;place-items:center;border-radius:50%;background:var(--fab-bg);color:var(--fab-icon);transition:background-color .2s}
.fab-btn:hover{background:color-mix(in srgb,var(--fab-bg) 82%,#000)}
.fab-btn svg{width:24px;height:24px;stroke:currentColor}
.fab-menu{display:none;flex-direction:column;gap:6px;min-width:172px}
.fab-wrap.open .fab-menu{display:flex;animation:fadeIn .2s ease}
.fab-menu a{display:flex;align-items:center;gap:9px;padding:8px 14px;white-space:nowrap;background:var(--cardbg);border:1px solid var(--line);border-radius:999px;color:var(--ink);font-size:.8rem;font-weight:800;transition:border-color .2s,color .2s}
.fab-menu a:hover{border-color:var(--fab-bg);color:var(--fab-bg)}
.fab-menu a svg{width:17px;height:17px;flex-shrink:0;stroke:var(--fab-bg)}

@media(max-width:1080px){
.features{grid-template-columns:repeat(2,1fr)}
.foot-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:920px){
.bnav,.sh-search,.hact .btn-login{display:none!important}
.sh-burger{display:grid}
.header-inner,.hdr-modern .header-inner{display:flex;flex-wrap:nowrap;min-height:64px}
.hdr-modern .brand{flex-direction:row}
.brand img{height:42px}
.brand-name{font-size:1rem}
.slider{min-height:var(--shm,480px)}
.slide-ovl,.slide.al-left .slide-ovl,.slide.al-center .slide-ovl{background:linear-gradient(to top,rgba(var(--ovl-rgb),calc(var(--ovl-o) * var(--m-ov))) 30%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * var(--m-ov) * .75)) 58%,rgba(var(--ovl-rgb),calc(var(--ovl-o) * var(--m-ov) * .15)))}
.slide .wrap{padding-block:40px 56px}
.slide-txt h1{font-size:var(--m-h1)}
.slide-txt p{font-size:var(--m-p)}
body.hide-m-desc .slide-txt p{display:none}
.slide-brand{top:auto;bottom:18%;left:5%}
.s-nav{display:none}
.strip ul.products li.product{flex-basis:200px!important;width:200px!important}
.offer{grid-template-columns:1fr;gap:20px}
.pd-wrap{grid-template-columns:1fr;gap:24px}
.pd-gallery .woocommerce-product-gallery__image,.pd-gallery .flex-viewport{aspect-ratio:4/3}
.col2-set{grid-template-columns:1fr}
.blog-grid{grid-template-columns:repeat(2,1fr)}
.statband{grid-template-columns:repeat(2,1fr)}
.stat:nth-child(odd){border-inline-start:0}
.about-home{grid-template-columns:1fr}
.look-grid{grid-template-columns:repeat(2,1fr)}
.testi-grid{grid-template-columns:1fr}
.bn-bottombar{position:fixed;bottom:0;left:0;right:0;z-index:100;display:flex;gap:6px;padding:6px 10px calc(6px + env(safe-area-inset-bottom));background:var(--bg);border-top:1px solid var(--line)}
body{padding-bottom:72px}
#toast{bottom:90px}
.fab-wrap.pos-bl,.fab-wrap.pos-br{bottom:calc(84px + env(safe-area-inset-bottom))}
.fab-btn{width:50px;height:50px}
}
@media(max-width:700px){
.page-hero{flex-direction:column;justify-content:center;text-align:center;gap:24px}
.page-hero-img,.page-hero .logo-tile{width:100%;max-width:300px}
.insta-strip{flex-direction:column;justify-content:center;text-align:center}
body.catst-10 .cat-card img{width:110px;height:80px}
}
@media(max-width:600px){
.wrap{padding-inline:16px}
ul.products{gap:10px}
.strip ul.products li.product{flex-basis:170px!important;width:170px!important}
.sale-sec ul.products li.product{flex-basis:165px!important;width:165px!important}
.cat-card{flex-basis:136px}
.cat-card h3{font-size:.78rem}
.prod-body{padding:10px}
.prod-name{font-size:.76rem}
.prod-price .amount{font-size:.8rem}
.features{grid-template-columns:1fr}
.foot-grid{grid-template-columns:1fr}
.blog-grid{grid-template-columns:1fr}
.news form,.so-box form{flex-direction:column}
.so-box .btn{width:100%}
.count .cell{min-width:0;flex:1}
}
@media(prefers-reduced-motion:reduce){
*,*::before,*::after{animation-duration:.01ms!important;animation-iteration-count:1!important;transition-duration:.01ms!important;scroll-behavior:auto!important}
.rv{opacity:1}
}
SHCSS;
    return $dyn . $static;
}

function sahel_bottombar_render() {
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
    echo '<nav class="bn-bottombar">';
    for ( $i = 1; $i <= 4; $i++ ) {
        $k = get_theme_mod( 'sahel_bb' . $i, $default4[ $i - 1 ] );
        if ( ! $k || ! isset( $defs[ $k ] ) ) { continue; }
        $d = $defs[ $k ];
        if ( $k === 'search' ) {
            echo '<button class="bb-item bbSearch" aria-label="' . esc_attr( $d[0] ) . '">' . $d[3] . esc_html( $d[0] ) . '</button>';
        } elseif ( $k === 'cart' ) {
            echo '<button class="bb-item bbCart" aria-label="' . esc_attr( $d[0] ) . '">' . $d[3] . esc_html( $d[0] ) . '</button>';
        } else {
            echo '<a class="bb-item' . ( $d[2] ? ' active' : '' ) . '" href="' . esc_url( $d[1] ) . '">' . $d[3] . esc_html( $d[0] ) . '</a>';
        }
    }
    echo '</nav>';
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
    echo '<button class="fab-btn" aria-label="راه‌های ارتباطی">' . sahel_fab_icons( 'phone' ) . '</button>';
    echo '</div>';
}

/* پوسته صفحه: هدر و فوتر مشترک همه صفحات */
function sahel_body_classes() {
    $cls = array(
        'btnst-' . get_theme_mod( 'sahel_btn_style', 'pill' ),
        'catst-' . get_theme_mod( 'sahel_catcard', '1' ),
        'prodst-' . get_theme_mod( 'sahel_prodcard', '1' ),
        'hdr-' . get_theme_mod( 'sahel_header_style', 'classic' ),
    );
    if ( get_theme_mod( 'sahel_m_hide_desc', 1 ) ) { $cls[] = 'hide-m-desc'; }
    if ( function_exists( 'is_product' ) && is_product() && get_theme_mod( 'sahel_product_fullwidth', 0 ) ) { $cls[] = 'pd-full'; }
    if ( get_theme_mod( 'sahel_mnav_carded', 0 ) ) { $cls[] = 'mnav-carded'; }
    return array_map( 'sanitize_html_class', $cls );
}

function sahel_shell_open() {
    $logo = esc_url( sahel_logo_url() );
    $brand = sahel_brand(); $brand_en = sahel_brand_en();
    $h_img = get_theme_mod( 'sahel_header_image', '' );
    $h_op = (int) get_theme_mod( 'sahel_header_image_opacity', 30 ) / 100;
    $header_style = $h_img ? '--header-img:url(' . esc_url( $h_img ) . ');--header-img-op:' . $h_op . ';' : '';
    ?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
<style id="sahel-css"><?php echo sahel_css(); ?></style>
</head>
<body <?php body_class( sahel_body_classes() ); ?>>
<?php if ( function_exists( 'wp_body_open' ) ) { wp_body_open(); } ?>
<div id="toast" role="status" aria-live="polite"></div>
<header id="header"<?php echo $header_style ? ' style="' . esc_attr( $header_style ) . '"' : ''; ?>>
<div class="wrap header-inner">
<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
<img src="<?php echo $logo; ?>" alt="<?php echo esc_attr( $brand ); ?>">
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
<button class="icon-btn" id="cartBtn" aria-label="سبد خرید">
<svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1.6"/><circle cx="19" cy="21" r="1.6"/><path d="M2 3h3l2.7 12.4A2 2 0 0 0 9.7 17h9.7a2 2 0 0 0 2-1.6L23 7H6"/></svg>
<span id="cartCount"><?php echo sahel_cart_count(); ?></span>
</button>
<a class="btn btn-primary btn-login" href="<?php echo esc_url( sahel_account_url() ); ?>"><?php echo is_user_logged_in() ? 'حساب کاربری' : 'ورود | ثبت‌نام'; ?></a>
</div>
</div>
</header>
<div id="overlay"></div>
<aside id="cartDrawer" aria-label="سبد خرید">
<div class="cart-head"><h3>سبد خرید <?php echo esc_html( $brand ); ?></h3><button id="closeCart" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div>
<div class="drawer-body widget_shopping_cart_content"><?php if ( function_exists( 'woocommerce_mini_cart' ) ) { woocommerce_mini_cart(); } ?></div>
<div class="cart-foot">
<a class="btn btn-primary" href="<?php echo esc_url( sahel_checkout_url() ); ?>">ادامه و پرداخت امن</a>
<a class="cart-foot-link" href="<?php echo esc_url( sahel_cart_url() ); ?>">مشاهده سبد خرید</a>
</div>
</aside>
<?php
}

function sahel_shell_close() {
    $logo = esc_url( sahel_logo_url() );
    $brand = sahel_brand();
    $cats = sahel_product_cats();
    $ajax_url = esc_url( admin_url( 'admin-ajax.php' ) );
    ?>
<footer>
<div class="wrap foot-grid">
<div class="foot-brand"><img src="<?php echo $logo; ?>" alt="<?php echo esc_attr( $brand ); ?>"><p><?php echo esc_html( get_theme_mod( 'sahel_footer_about', $brand . '؛ فروشگاه آنلاین.' ) ); ?></p>
<div class="foot-social">
<a href="<?php echo esc_url( get_theme_mod( 'sahel_instagram', '#' ) ); ?>" aria-label="اینستاگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
<a href="<?php echo esc_url( get_theme_mod( 'sahel_telegram', '#' ) ); ?>" aria-label="تلگرام"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 3L2 10.5l6 2.5M22 3l-3 18-8-8M22 3L8 13"/></svg></a>
<a href="<?php echo esc_url( get_theme_mod( 'sahel_whatsapp', '#' ) ); ?>" aria-label="واتساپ"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 21l2-5.6A8.5 8.5 0 1 1 21 11.5z"/></svg></a>
</div>
</div>
<div><h5>دسترسی سریع</h5><a href="<?php echo esc_url( home_url( '/' ) ); ?>">صفحه اصلی</a><a href="<?php echo esc_url( sahel_shop_url() ); ?>">فروشگاه</a><a href="<?php echo esc_url( sahel_page_url( 'blog' ) ); ?>">مقالات</a></div>
<div><h5><?php echo esc_html( $brand ); ?></h5><a href="<?php echo esc_url( sahel_page_url( 'about-us' ) ); ?>">درباره ما</a><a href="<?php echo esc_url( sahel_page_url( 'contact-us' ) ); ?>">تماس با ما</a><?php foreach ( array_slice( $cats, 0, 2 ) as $c ) : ?><a href="<?php echo esc_url( get_term_link( $c ) ); ?>"><?php echo esc_html( $c->name ); ?></a><?php endforeach; ?></div>
<div><h5>تماس با <?php echo esc_html( $brand ); ?></h5><p>📍 <?php echo esc_html( get_theme_mod( 'sahel_address', 'تهران' ) ); ?></p><p>📞 <?php echo esc_html( get_theme_mod( 'sahel_phone', sahel_fa( '021-220000' ) ) ); ?></p><p>✉️ <?php echo esc_html( get_theme_mod( 'sahel_email', 'info@sahelstyle.ir' ) ); ?></p><p>🕘 <?php echo esc_html( get_theme_mod( 'sahel_hours', 'هر روز ۱۰-۲۱' ) ); ?></p></div>
</div>
<div class="wrap foot-bottom">
<span><?php echo esc_html( get_theme_mod( 'sahel_copyright', '© تمامی حقوق برای ' . $brand . ' محفوظ است.' ) ); ?></span>
<span>🎨 <a class="peecha-link" href="<?php echo esc_url( get_theme_mod( 'sahel_peecha_url', 'https://www.peecha.ir' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_theme_mod( 'sahel_peecha_text', 'طراحی شده توسط پیچا' ) ); ?></a></span>
</div>
</footer>
<?php sahel_bottombar_render(); ?>
<?php sahel_fab_render(); ?>
<div class="sh-searchover" id="shSearchOver">
<div class="so-box">
<button class="so-close" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
<form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
<input type="search" name="s" placeholder="جستجو در <?php echo esc_attr( $brand ); ?>…" autocomplete="off">
<input type="hidden" name="post_type" value="product">
<button class="btn btn-primary" type="submit">جستجو</button>
</form>
<div class="sh-live"></div>
<p class="so-hint">مثلاً: پیراهن ساحلی، بافت، کیف…</p>
</div>
</div>
<?php wp_footer(); ?>
<script>
(function($){
if(!$)return;
var ajaxUrl='<?php echo $ajax_url; ?>';
var BRAND='<?php echo esc_js( $brand ); ?>';
function toast(m){var t=$('#toast');t.text(m).addClass('show');clearTimeout(t.data('tm'));t.data('tm',setTimeout(function(){t.removeClass('show');},2600));}
/* سبد خرید */
$(document).on('click','#cartBtn,.bbCart',function(){$('#cartDrawer').addClass('show');$('#overlay').addClass('show');});
$(document).on('click','#closeCart,#overlay',function(){$('#cartDrawer').removeClass('show');$('#overlay').removeClass('show');});
$(document.body).on('added_to_cart',function(){var b=$('#cartCount');b.addClass('bump');setTimeout(function(){b.removeClass('bump');},450);});
/* منوی کشویی */
$(document).on('click','.dd-toggle',function(e){ if($(window).width()<920 && !$(this).closest('.sh-mnav').length){ e.preventDefault(); $(this).closest('.dd').toggleClass('open'); } });
/* باشگاه مشتریان */
$(document).on('submit','.news form',function(e){
e.preventDefault();
var f=$(this),v=f.find('input').val();
$.post(ajaxUrl,{action:'sahel_newsletter',phone:v},function(r){ toast(r.msg); if(r.ok){ f[0].reset(); } });
});
/* جستجوی زنده */
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
$(document).on('input','.sh-search input[type=search],.so-box input[type=search]',function(){
var $b=$(this).closest('.sh-search,.so-box').find('.sh-live'),v=$(this).val();
clearTimeout(deb); deb=setTimeout(function(){ liveSearch(v,$b); },220);
});
$(document).on('click',function(e){
if(!$(e.target).closest('.sh-search,.so-box').length){ $('.sh-live').removeClass('show'); }
if(!$(e.target).closest('.fab-wrap').length){ $('.fab-wrap').removeClass('open'); }
if(!$(e.target).closest('.dd').length){ $('.bnav .dd').removeClass('open'); }
});
$(document).on('click','.bbSearch',function(){ $('#shSearchOver').addClass('open'); setTimeout(function(){$('#shSearchOver input[type=search]').trigger('focus');},150); });
$(document).on('click','.so-close',function(){ $('#shSearchOver').removeClass('open'); });
$('#shSearchOver').on('click',function(e){ if(e.target===this){ $(this).removeClass('open'); } });
$(document).on('keydown',function(e){ if(e.key==='Escape'){ $('#shSearchOver').removeClass('open'); $('#cartDrawer,#overlay').removeClass('show'); $('body').removeClass('sh-menu-open'); } });
$(document).on('click','.fab-btn',function(e){ e.stopPropagation(); $('.fab-wrap').toggleClass('open'); });
/* اسلایدر */
var $sl=$('.slide'),$dt=$('.s-dots button'),si=0,stimer;
function sprog(){clearTimeout(stimer);var $p=$('#sprog');$p.css({transition:'none',width:'0'});setTimeout(function(){$p.css({transition:'width 7s linear',width:'100%'});},40);stimer=setTimeout(function(){sgo(si+1);},7000);}
function sgo(n){if(!$sl.length)return;si=(n+$sl.length)%$sl.length;$sl.removeClass('active').eq(si).addClass('active');$dt.removeClass('active').eq(si).addClass('active');if($sl.length>1){sprog();}}
$dt.on('click',function(){sgo($(this).index());});
$(document).on('click','.s-next',function(){sgo(si+1);});
$(document).on('click','.s-prev',function(){sgo(si-1);});
var tx0=null;
$('.slider').on('touchstart',function(e){tx0=e.originalEvent.touches[0].clientX;});
$('.slider').on('touchend',function(e){if(tx0===null)return;var dx=e.originalEvent.changedTouches[0].clientX-tx0;if(dx>40)sgo(si-1);if(dx<-40)sgo(si+1);tx0=null;});
if($sl.length){sgo(0);}
/* فلش‌های ردیف‌های افقی */
$(document).on('click','.s-arrow',function(){
var box=$(this).closest('section').find('.cat-grid,ul.products')[0];
if(!box)return;
var dir=$(this).hasClass('next')?-1:1;
box.scrollBy({left:dir*Math.max(240,box.clientWidth*.8),behavior:'smooth'});
});
/* تعداد ستون محصولات */
function applyCols(){
var n='5';
try{ n=localStorage.getItem('sahel_cols')||'5'; }catch(err){}
if(window.innerWidth<=920){ n = window.innerWidth<=600 ? '2' : '3'; }
document.querySelectorAll('ul.products').forEach(function(u){
if(!u.closest('.strip')){ u.style.setProperty('--pcols', n); }
});
$('.col-switch button').removeClass('active').filter('[data-cols="'+n+'"]').addClass('active');
}
$(document).on('click','.col-switch button',function(){
try{ localStorage.setItem('sahel_cols', $(this).data('cols')); }catch(err){}
applyCols();
});
$(window).on('resize', applyCols);
applyCols();
/* کارت‌های سه‌بعدی (Tilt) */
(function(){
var b=document.body.classList,sels=[];
if(b.contains('catst-9')) sels.push('.cat-card');
if(b.contains('prodst-11')) sels.push('.prod-card');
if(!sels.length || !window.matchMedia('(hover:hover)').matches) return;
document.querySelectorAll(sels.join(',')).forEach(function(card){
card.addEventListener('mousemove',function(e){
var r=card.getBoundingClientRect();
var x=(e.clientX-r.left)/r.width-.5, y=(e.clientY-r.top)/r.height-.5;
card.style.transform='perspective(800px) rotateY('+(x*6)+'deg) rotateX('+(-y*6)+'deg)';
});
card.addEventListener('mouseleave',function(){ card.style.transform=''; });
});
})();
/* شمارنده‌ها و شمارش معکوس */
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
if($('#cdS').length){setInterval(function(){var s=Math.max(0,Math.floor((end-Date.now())/1000));
$('#cdD').text(faD(p2(Math.floor(s/86400))));$('#cdH').text(faD(p2(Math.floor(s/3600)%24)));$('#cdM').text(faD(p2(Math.floor(s/60)%60)));$('#cdS').text(faD(p2(s%60)));
},1000);}
/* نمایش تدریجی */
if('IntersectionObserver' in window){
var io=new IntersectionObserver(function(es){es.forEach(function(en){if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target);}});},{threshold:.12});
document.querySelectorAll('.rv').forEach(function(el){io.observe(el);});
} else { $('.rv').addClass('in'); }
/* منوی موبایل */
if(!$('.sh-burger').length){
var $burger=$('<button class="icon-btn sh-burger" aria-label="منو"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>');
$burger.insertBefore($('#cartBtn'));
var loginHtml=$('.hact .btn-login').length?$('.hact .btn-login')[0].outerHTML:'';
var $panel=$('<div class="sh-mnav"><div class="sh-mnav-top"><img alt=""><button class="sh-close" aria-label="بستن"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button></div><nav></nav><div class="sh-login"></div></div>');
$panel.find('.sh-mnav-top img').attr({src:$('.brand img').attr('src'),alt:BRAND});
$panel.find('nav').html($('.bnav').html());
$panel.find('.sh-login').html(loginHtml);
$('body').append($panel);
$burger.on('click',function(){ $('body').addClass('sh-menu-open'); });
$(document).on('click','.sh-close',function(){ $('body').removeClass('sh-menu-open'); });
$(document).on('click','.sh-mnav nav a',function(){ if(!$(this).hasClass('dd-toggle')){ $('body').removeClass('sh-menu-open'); } });
$(document).on('click','.sh-mnav .dd-toggle',function(e){ e.preventDefault(); $(this).closest('.dd').toggleClass('open'); });
}
})(window.jQuery);
</script>
</body>
</html>
<?php
}

function sahel_shell( $content ) {
    sahel_shell_open();
    echo $content;
    sahel_shell_close();
}

function sahel_home_html() {
    $ob = '';
    $logo = esc_url( sahel_logo_url() );
    $brand = sahel_brand(); $brand_short = sahel_brand_short(); $brand_en = sahel_brand_en();

    $count = max( 1, min( 10, (int) get_theme_mod( 'sahel_slide_count', 3 ) ) );
    $rendered = array();
    for ( $i = 1; $i <= $count; $i++ ) {
        $sd = sahel_slide_data( $i );
        if ( ! $sd['img'] ) { continue; }
        $rendered[] = $sd;
    }
    if ( empty( $rendered ) ) { $rendered[] = sahel_slide_data( 1 ); }
    $slider_full = get_theme_mod( 'sahel_slider_full', 0 ) ? ' is-full' : '';
    $ob .= '<div class="slider' . $slider_full . '">';
    foreach ( $rendered as $i => $sd ) {
        $url = $sd['url'] ? $sd['url'] : sahel_shop_url();
        $align = 'al-' . ( in_array( $sd['align'], array( 'right', 'center', 'left' ), true ) ? $sd['align'] : 'right' );
        $ovl_style = '--ovl-rgb:' . sahel_rgb_str( $sd['ovl_color'] ) . ';--ovl-o:' . ( (int) $sd['ovl_opacity'] / 100 );
        $ob .= '<div class="slide ' . esc_attr( $align ) . '" style="' . esc_attr( $ovl_style ) . '">';
        $ob .= '<img class="slide-bg" src="' . esc_url( $sd['img'] ) . '" alt=""' . ( $i ? ' loading="lazy"' : '' ) . '><div class="slide-ovl"></div>';
        if ( $i === 0 ) { $ob .= '<div class="slide-brand"><img src="' . $logo . '" alt=""><span><b>' . esc_html( $brand ) . '</b><small>' . esc_html( $brand_en ) . '</small></span></div>'; }
        $ob .= '<div class="wrap"><div class="slide-txt ' . esc_attr( $align ) . '">';
        if ( $sd['pill'] ) { $ob .= '<span class="pill"><i></i>' . esc_html( $sd['pill'] ) . '</span>'; }
        $ob .= '<h1>' . esc_html( $sd['t1'] ) . '<br><span class="grad-text">' . esc_html( $sd['t2'] ) . '</span></h1>';
        if ( $sd['desc'] ) { $ob .= '<p>' . esc_html( $sd['desc'] ) . '</p>'; }
        if ( $sd['btn'] ) { $ob .= '<div class="hero-cta"><a class="btn btn-primary" href="' . esc_url( $url ) . '">' . esc_html( $sd['btn'] ) . '</a></div>'; }
        $ob .= '</div></div></div>';
    }
    if ( count( $rendered ) > 1 ) {
        $ob .= '<button class="s-nav s-next" aria-label="بعدی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg></button>';
        $ob .= '<button class="s-nav s-prev" aria-label="قبلی"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg></button>';
        $ob .= '<div class="s-dots">';
        foreach ( $rendered as $i => $sd ) { $ob .= '<button' . ( $i === 0 ? ' class="active"' : '' ) . ' aria-label="اسلاید ' . sahel_fa( $i + 1 ) . '"></button>'; }
        $ob .= '</div><div class="s-progress" id="sprog"></div>';
    }
    $ob .= '</div>';

    $parts = array();

    $sec = sahel_sec( 'stats' );
    if ( $sec['on'] ) {
        $st1 = get_theme_mod( 'sahel_stat1', '8000' ); $st1l = get_theme_mod( 'sahel_stat1_label', 'مشتری خوشحال' );
        $st2 = get_theme_mod( 'sahel_stat2', '450' ); $st2l = get_theme_mod( 'sahel_stat2_label', 'محصول متنوع' );
        $st3 = get_theme_mod( 'sahel_stat3', '98' ); $st3l = get_theme_mod( 'sahel_stat3_label', 'رضایت خرید' );
        $st4 = get_theme_mod( 'sahel_stat4_text', 'سراسر کشور' ); $st4l = get_theme_mod( 'sahel_stat4_label', 'ارسال سریع' );
        $h = sahel_sec_open( $sec ) . '<div class="wrap statband rv">';
        $h .= '<div class="stat"><b class="cnt" data-to="' . esc_attr( $st1 ) . '" data-prefix="+">۰</b><span>' . esc_html( $st1l ) . '</span></div>';
        $h .= '<div class="stat"><b class="cnt" data-to="' . esc_attr( $st2 ) . '" data-prefix="+">۰</b><span>' . esc_html( $st2l ) . '</span></div>';
        $h .= '<div class="stat"><b class="cnt" data-to="' . esc_attr( $st3 ) . '" data-suffix="٪">۰</b><span>' . esc_html( $st3l ) . '</span></div>';
        $h .= '<div class="stat"><b>' . esc_html( $st4 ) . '</b><span>' . esc_html( $st4l ) . '</span></div>';
        $h .= '</div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'marquee' );
    if ( $sec['on'] ) {
        $mq = get_theme_mod( 'sahel_marquee_text', $brand . ' ✦ ظرافت ✦ آرامش ✦ ضمانت ✦ پیراهن ✦ بافت ✦ ارسال ' );
        $mq_effect = get_theme_mod( 'sahel_marquee_effect', 'slide' );
        $parts[] = array( $sec['order'], '<div class="marquee effect-' . esc_attr( $mq_effect ) . '"><div class="marquee-track"><span>' . esc_html( $mq ) . '</span><span aria-hidden="true">' . esc_html( $mq ) . '</span></div></div>' );
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
        $tools = '<a class="sec-link" href="' . esc_url( sahel_shop_url() ) . '">مشاهده همه ←</a>' . ( get_theme_mod( 'sahel_catcard', '1' ) !== '10' ? sahel_arrows() : '' );
        $h = sahel_sec_open( $sec, 'categories' ) . '<div class="wrap">';
        $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], $tools );
        $h .= '<div class="cat-grid">';
        $i = 0;
        foreach ( $cats as $c ) {
            $h .= '<a class="cat-card rv" href="' . esc_url( get_term_link( $c ) ) . '"><img loading="lazy" src="' . esc_url( sahel_cat_img( $c, $fb[ $i % 4 ] ) ) . '" alt=""><div class="txt"><h3>' . esc_html( $c->name ) . '</h3><p>' . sahel_num( $c->count ) . ' محصول</p></div></a>';
            $i++;
        }
        $h .= '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'offer' );
    $of = $sec['on'] ? sahel_top_sale() : null;
    if ( $of ) {
        $h = sahel_sec_open( $sec, 'offer', 'offer-sec' ) . '<div class="wrap offer rv"><div class="offer-txt">';
        $h .= '<h3>🤎 ' . esc_html( $sec['title'] ) . '</h3><h2>' . esc_html( $of['p']->get_name() ) . '</h2>';
        $h .= '<p class="od">' . ( $sec['sub'] ? esc_html( $sec['sub'] ) : 'این محصول ویژه را با ' . sahel_fa( $of['bp'] ) . '٪ تخفیف و ارسال رایگان دریافت کن.' ) . '</p>';
        $h .= '<div class="count"><div class="cell"><b id="cdD">۰۲</b><span>روز</span></div><div class="cell"><b id="cdH">۱۴</b><span>ساعت</span></div><div class="cell"><b id="cdM">۴۵</b><span>دقیقه</span></div><div class="cell"><b id="cdS">۳۰</b><span>ثانیه</span></div></div>';
        $h .= '<button type="button" data-product_id="' . esc_attr( $of['p']->get_id() ) . '" class="btn btn-light add_to_cart_button ajax_add_to_cart">افزودن به سبد با ' . sahel_fa( $of['bp'] ) . '٪ تخفیف</button>';
        $h .= '</div><div class="offer-visual"><span class="offer-off">' . sahel_fa( $of['bp'] ) . '٪</span>' . $of['p']->get_image( 'large' ) . '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    if ( class_exists( 'WooCommerce' ) ) {
        $strips = array(
            'new'  => array( '', '[products limit="8" columns="4" orderby="date" order="DESC"]' ),
            'sale' => array( 'sale-sec', '[products limit="8" columns="4" on_sale="true"]' ),
            'best' => array( '', '[products limit="8" columns="4" best_selling="true"]' ),
        );
        foreach ( $strips as $key => $st ) {
            $sec = sahel_sec( $key );
            if ( ! $sec['on'] ) { continue; }
            $tools = ( 'sale' === $key ? '<a class="sec-link" href="' . esc_url( sahel_shop_url() ) . '">مشاهده همه 🤎</a>' : '' ) . sahel_arrows();
            $h = sahel_sec_open( $sec, '', trim( $st[0] . ' strip' ) ) . '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], $tools );
            $h .= do_shortcode( $st[1] ) . '</div></section>';
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
            $mode_class = $mode === 'marquee' ? '' : ( $mode === 'float' ? ' is-float' : ' is-static' );
            $h = sahel_sec_open( $sec ) . '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
            $h .= '<div class="brands-strip' . $mode_class . '"><div class="brands-track">';
            $loops = $mode === 'marquee' ? 2 : 1;
            for ( $l = 0; $l < $loops; $l++ ) {
                foreach ( $logos as $lg ) { $h .= '<div class="brand-item" title="' . esc_attr( $lg[1] ) . '"' . ( $l ? ' aria-hidden="true"' : '' ) . '><img src="' . esc_url( $lg[0] ) . '" alt="' . esc_attr( $lg[1] ) . '"></div>'; }
            }
            $h .= '</div></div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'features' );
    if ( $sec['on'] ) {
        $h = sahel_sec_open( $sec ) . '<div class="wrap features">';
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
        $h = sahel_sec_open( $sec, '', 'campaign-sec' ) . '<div class="wrap campaign rv">';
        $h .= '<h2>' . esc_html( $sec['title'] ) . '</h2><p>' . esc_html( $sec['sub'] ) . '</p>';
        $h .= '<a class="btn btn-light" href="' . esc_url( $url ) . '">' . esc_html( $btn ) . '</a>';
        $h .= '</div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'look' );
    if ( $sec['on'] ) {
        $imgs = array();
        for ( $i = 1; $i <= 4; $i++ ) { $im = get_theme_mod( 'sahel_look' . $i, '' ); if ( $im ) { $imgs[] = $im; } }
        if ( $imgs ) {
            $h = sahel_sec_open( $sec ) . '<div class="wrap">';
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
        $cards = '';
        for ( $i = 1; $i <= 3; $i++ ) {
            $txt = get_theme_mod( 'sahel_testi' . $i . '_txt', '' );
            $nm = get_theme_mod( 'sahel_testi' . $i . '_name', '' );
            $rl = get_theme_mod( 'sahel_testi' . $i . '_role', '' );
            if ( ! $txt ) { continue; }
            $av = function_exists( 'mb_substr' ) ? mb_substr( $nm, 0, 1 ) : substr( $nm, 0, 2 );
            $cards .= '<div class="testi rv"><div class="stars">★★★★★</div><p>«' . esc_html( $txt ) . '»</p><div class="who"><span class="av">' . esc_html( $av ) . '</span><div><b>' . esc_html( $nm ) . '</b><span>' . esc_html( $rl ) . '</span></div></div></div>';
        }
        if ( $cards ) {
            $h = sahel_sec_open( $sec ) . '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '' );
            $h .= '<div class="testi-grid">' . $cards . '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
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
            $h = sahel_sec_open( $sec ) . '<div class="wrap">';
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
            $h = sahel_sec_open( $sec ) . '<div class="wrap">';
            $h .= sahel_sec_head_html( $sec['title'], $sec['sub'], '<a class="sec-link" href="' . esc_url( sahel_page_url( 'blog' ) ) . '">همه مقالات ←</a>' );
            $h .= '<div class="blog-grid">';
            foreach ( $posts as $post ) { $h .= sahel_post_card( $post ); }
            $h .= '</div></div></section>';
            $parts[] = array( $sec['order'], $h );
        }
    }

    $sec = sahel_sec( 'about' );
    if ( $sec['on'] ) {
        $about_img = get_theme_mod( 'sahel_about_image', '' );
        $about_txt = get_theme_mod( 'sahel_about_home_text', $brand . ' با یک باور ساده متولد شد.' );
        $h = sahel_sec_open( $sec ) . '<div class="wrap about-home rv">';
        if ( $about_img ) { $h .= '<div class="ah-img"><img src="' . esc_url( $about_img ) . '" alt=""></div>'; }
        else { $h .= '<div class="logo-tile"><img src="' . $logo . '" alt="' . esc_attr( $brand ) . '"></div>'; }
        $h .= '<div class="ah-txt"><h2><span class="grad-text">' . esc_html( $sec['title'] ) . '</span></h2><p>' . esc_html( $about_txt ) . '</p>';
        if ( $sec['sub'] ) { $h .= '<p>' . esc_html( $sec['sub'] ) . '</p>'; }
        $h .= '<a class="btn btn-ghost ah-more" href="' . esc_url( sahel_page_url( 'about-us' ) ) . '">بیشتر درباره ' . esc_html( $brand_short ) . ' ←</a>';
        $h .= '</div></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'insta' );
    if ( $sec['on'] ) {
        $ig = get_theme_mod( 'sahel_instagram', '#' );
        $txt = get_theme_mod( 'sahel_insta_text', 'ما را دنبال کنید.' );
        $h = sahel_sec_open( $sec, '', 'insta-sec' ) . '<div class="wrap insta-strip rv"><div><h3>📸 ما را در اینستاگرام دنبال کنید</h3><p>' . esc_html( $txt ) . '</p></div><a class="btn btn-primary" href="' . esc_url( $ig ) . '" target="_blank" rel="noopener">دنبال کردن</a></div></section>';
        $parts[] = array( $sec['order'], $h );
    }

    $sec = sahel_sec( 'news' );
    if ( $sec['on'] ) {
        $h = sahel_sec_open( $sec, '', 'news-sec' ) . '<div class="wrap news rv"><h2>' . ( $sec['title'] ? esc_html( $sec['title'] ) : 'عضو <span class="grad-text">باشگاه ' . esc_html( $brand ) . '</span> شو 🤎' ) . '</h2><p>' . ( $sec['sub'] ? esc_html( $sec['sub'] ) : 'اولین نفری باش که از کالکشن‌های جدید باخبر می‌شه' ) . '</p><form><input type="tel" placeholder="شماره موبایل" aria-label="شماره موبایل" required><button class="btn btn-primary" type="submit">عضویت</button></form></div></section>';
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
                $h .= '<div class="page-banner"><img src="' . esc_url( $banner ) . '" alt=""><div class="pb-txt"><h1>' . esc_html( get_theme_mod( 'sahel_shop_title', 'فروشگاه ' . $brand ) ) . '</h1><p>' . esc_html( get_theme_mod( 'sahel_shop_sub', 'استایل تو، امضای تو' ) ) . '</p></div></div>';
            } else {
                $h .= '<h1 class="page-title">' . esc_html( woocommerce_page_title( false ) ) . '</h1>';
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
                ob_start(); wc_print_notices(); $h .= ob_get_clean();
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
                    ob_start(); woocommerce_output_product_data_tabs(); $h .= '<div class="pd-tabs">' . ob_get_clean() . '</div>';
                }
                if ( get_theme_mod( 'sahel_product_related', 1 ) ) {
                    ob_start(); woocommerce_output_related_products(); $rel = ob_get_clean();
                    if ( trim( $rel ) ) { $h .= '<div class="pd-related"><h3 class="pd-related-title">محصولات <span>مرتبط</span> ' . esc_html( $brand_short ) . '</h3>' . $rel . '</div>'; }
                }
            }
            $h .= '</main>';
            sahel_shell( $h );
        } elseif ( '404' === $t ) {
            $brand = sahel_brand();
            sahel_shell( '<main class="wrap page-main"><div class="empty-state"><p style="font-size:3rem;font-weight:900;line-height:1.2">۴۰۴</p><p>' . esc_html( get_theme_mod( 'sahel_404_text', 'صفحه پیدا نشد!' ) ) . '</p><a class="btn btn-primary" href="' . esc_url( home_url( '/' ) ) . '">بازگشت به خانه ' . esc_html( $brand ) . '</a></div></main>' );
        } elseif ( 'page' === $t ) {
            $el = false;
            if ( class_exists( '\Elementor\Plugin' ) ) { $d = \Elementor\Plugin::$instance->documents->get( get_queried_object_id() ); $el = $d ? $d->is_built_with_elementor() : false; }
            if ( $el ) {
                $h = '';
                while ( have_posts() ) { the_post(); ob_start(); the_content(); $h .= ob_get_clean(); }
                sahel_shell( $h );
            } else {
                $brand = sahel_brand();
                $h = '<main class="wrap page-main">';
                while ( have_posts() ) {
                    the_post();
                    $slug = get_post_field( 'post_name', get_the_ID() );
                    $content = apply_filters( 'the_content', get_the_content() );
                    if ( $slug === 'about-us' || $slug === 'contact-us' ) {
                        $is_about = ( $slug === 'about-us' );
                        $img = $is_about ? get_theme_mod( 'sahel_about_image', '' ) : get_theme_mod( 'sahel_contact_image', '' );
                        $h .= '<div class="page-hero">';
                        if ( $img ) { $h .= '<div class="page-hero-img"><img src="' . esc_url( $img ) . '" alt=""></div>'; }
                        else { $h .= '<div class="logo-tile"><img src="' . esc_url( sahel_logo_url() ) . '" alt="' . esc_attr( $brand ) . '"></div>'; }
                        $h .= '<div class="page-hero-txt"><h1>' . ( $is_about ? 'درباره <span class="grad-text">' . esc_html( $brand ) . '</span>' : 'تماس با <span class="grad-text">' . esc_html( $brand ) . '</span>' ) . '</h1><p>' . ( $is_about ? 'ظرافت، آرامش، درخشش.' : 'از شما می‌شنویم.' ) . '</p></div>';
                        $h .= '</div>';
                        $h .= '<article class="page-card page-content">' . $content;
                        if ( ! $is_about ) {
                            $map = get_theme_mod( 'sahel_contact_map', '' );
                            if ( $map ) { $h .= '<div class="map-box">' . $map . '</div>'; }
                        }
                        $h .= '</article>';
                    } elseif ( $slug === 'blog' ) {
                        $h .= '<div class="sec-head"><div><h2>مقالات <span>' . esc_html( $brand ) . '</span></h2><p>راهنمای انتخاب</p></div></div>';
                        $posts = get_posts( array( 'post_type' => 'post', 'numberposts' => 9 ) );
                        if ( $posts ) {
                            $h .= '<div class="blog-grid">';
                            foreach ( $posts as $post ) { $h .= sahel_post_card( $post ); }
                            $h .= '</div>';
                        } else {
                            $h .= '<p style="color:var(--muted);font-weight:700">هنوز مطلبی منتشر نشده است.</p>';
                        }
                    } else {
                        $h .= '<article class="page-card"><h1 class="page-title">' . get_the_title() . '</h1><div class="page-content">' . $content . '</div></article>';
                    }
                }
                $h .= '</main>';
                sahel_shell( $h );
            }
        } else {
            $h = '<main class="wrap page-main">';
            while ( have_posts() ) { the_post(); $h .= '<article class="page-card"><h1 class="page-title">' . get_the_title() . '</h1><div class="page-content">' . apply_filters( 'the_content', get_the_content() ) . '</div></article>'; }
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
    $w->add_setting( 'sahel_radius', array( 'default' => 12, 'sanitize_callback' => 'absint' ) );
    $w->add_control( 'sahel_radius', array( 'label' => 'شعاع گوشه', 'section' => 'sahel_styles', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 40 ) ) );
}

add_action( 'template_redirect', function() {
    if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() ) ) {
        header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
        header( 'Pragma: no-cache' );
    }
} );
add_filter( 'template_include', function( $template ) {
    if ( function_exists( 'is_cart' ) && is_cart() ) {
        $h = '<main class="wrap page-main"><div class="page-card">' . do_shortcode( '[woocommerce_cart]' ) . '</div></main>';
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