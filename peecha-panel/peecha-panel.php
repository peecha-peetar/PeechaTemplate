<?php
/**
 * Plugin Name:       پنل پیچا — مدیریت ساده‌ی فروشگاه
 * Plugin URI:        https://peecha.ir
 * Description:       پنل فارسی، ساده و سریع برای مدیریت فروشگاه ووکامرس: داشبورد فروش، سفارش‌ها با تاریخ شمسی و کد رهگیری، ویرایش سریع قیمت و موجودی، افزودن محصول و لیست مشتریان. داخل پیشخوان وردپرس و با همان ورود امن وردپرس.
 * Version:           1.3.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * Author:            Peecha
 * License:           GPLv2 or later
 * Text Domain:       peecha-panel
 */

defined( 'ABSPATH' ) || exit;

define( 'PP_VER', '1.3.1' );
define( 'PP_FILE', __FILE__ );
define( 'PP_URL', plugin_dir_url( __FILE__ ) );
define( 'PP_SLUG', 'peecha-panel' );
require_once __DIR__ . '/includes/prices.php';

/* سازگاری با جدول‌های جدید سفارش ووکامرس (HPOS) */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PP_FILE, true );
	}
} );

final class Peecha_Panel {

	const CAP = 'manage_woocommerce';

	/* ---------- راه‌اندازی ---------- */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'order_statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'paid_statuses' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( __CLASS__, 'paid_statuses' ) );

		if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'WC' ) ) {
			add_action( 'admin_notices', function () {
				if ( current_user_can( 'activate_plugins' ) ) {
					echo '<div class="notice notice-error"><p>پنل پیچا برای کار به افزونه‌ی ووکامرس نیاز دارد.</p></div>';
				}
			} );
			return;
		}

		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_menu', array( __CLASS__, 'simplify_menu' ), 999 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 31 );
		add_action( 'load-index.php', array( __CLASS__, 'dashboard_redirect' ) );
		add_action( 'load-toplevel_page_' . PP_SLUG, array( __CLASS__, 'clean_screen' ) );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 99, 3 );
		add_filter( 'woocommerce_login_redirect', array( __CLASS__, 'wc_login_redirect' ), 99, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( PP_FILE ), array( __CLASS__, 'plugin_links' ) );

		foreach ( array( 'order_status', 'bulk_status', 'tracking', 'order_note', 'save_product', 'save_settings', 'toggle_view' ) as $a ) {
			add_action( 'admin_post_pp_' . $a, array( __CLASS__, 'act_' . $a ) );
		}
		add_action( 'wp_ajax_pp_quick_save', array( __CLASS__, 'ajax_quick_save' ) );

		/* کش آمار با هر تغییر سفارش پاک می‌شود */
		foreach ( array( 'woocommerce_new_order', 'woocommerce_order_status_changed', 'woocommerce_update_order', 'woocommerce_delete_order', 'woocommerce_trash_order' ) as $h ) {
			add_action( $h, array( __CLASS__, 'flush_stats' ) );
		}

		/* نمایش کد رهگیری به مشتری (صفحه‌ی سفارش در حساب کاربری و ایمیل‌ها) */
		add_action( 'woocommerce_order_details_before_order_table', array( __CLASS__, 'front_tracking' ) );
		add_action( 'woocommerce_email_order_meta', array( __CLASS__, 'email_tracking' ), 20, 3 );
	}

	/* ---------- تنظیمات ---------- */
	public static function opt( $k ) {
		static $o = null;
		if ( null === $o ) {
			$o = wp_parse_args( (array) get_option( 'pp_settings', array() ), array(
				'redirect' => 1,
				'simplify' => 1,
				'shipped'  => 1,
				'low'      => (int) get_option( 'woocommerce_notify_low_stock_amount', 2 ),
				'per_page' => 20,
				'carriers' => "پست پیشتاز\nتیپاکس\nچاپار\nپیک موتوری\nماهکس",
				'track_url'=> 'https://tracking.post.ir/?id={code}',
			) );
		}
		return isset( $o[ $k ] ) ? $o[ $k ] : null;
	}

	/* ---------- ابزارها ---------- */
	public static function fa( $s ) {
		return str_replace( array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ), array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ), (string) $s );
	}
	/* تبدیل اعداد فارسی/عربی ورودی کاربر به انگلیسی */
	public static function en( $s ) {
		return str_replace( array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', '٬', ',' ), array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '', '' ), (string) $s );
	}
	public static function num_in( $key, $src = null ) {
		$src = null === $src ? $_POST : $src; // phpcs:ignore
		if ( ! isset( $src[ $key ] ) ) { return null; }
		$v = trim( self::en( sanitize_text_field( wp_unslash( $src[ $key ] ) ) ) );
		if ( '' === $v ) { return ''; }
		return wc_format_decimal( $v );
	}
	public static function g2j( $gy, $gm, $gd ) {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
		$days %= 12053;
		$jy   += 4 * (int) ( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) { $jy += (int) ( ( $days - 1 ) / 365 ); $days = ( $days - 1 ) % 365; }
		if ( $days < 186 ) { $jm = 1 + (int) ( $days / 31 ); $jd = 1 + ( $days % 31 ); }
		else { $jm = 7 + (int) ( ( $days - 186 ) / 30 ); $jd = 1 + ( ( $days - 186 ) % 30 ); }
		return array( $jy, $jm, $jd );
	}
	public static function jmonth( $m ) {
		$n = array( 1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		return isset( $n[ $m ] ) ? $n[ $m ] : '';
	}
	/* $local_ts: زمان «محلی» سایت به صورت تایم‌استمپ (offset اعمال شده) */
	public static function jdate( $local_ts, $time = true, $long = false ) {
		if ( ! $local_ts ) { return '—'; }
		list( $y, $m, $d ) = self::g2j( (int) gmdate( 'Y', $local_ts ), (int) gmdate( 'n', $local_ts ), (int) gmdate( 'j', $local_ts ) );
		$out = $long ? ( $d . ' ' . self::jmonth( $m ) . ' ' . $y ) : sprintf( '%04d/%02d/%02d', $y, $m, $d );
		if ( $time ) { $out .= ' — ' . gmdate( 'H:i', $local_ts ); }
		return self::fa( $out );
	}
	public static function wc_jdate( $dt, $time = true ) {
		return ( $dt && is_object( $dt ) ) ? self::jdate( $dt->getOffsetTimestamp(), $time ) : '—';
	}
	public static function gmt_jdate( $gmt, $time = false ) {
		$ts = $gmt ? strtotime( $gmt . ' UTC' ) : 0;
		return $ts ? self::jdate( $ts + (int) wp_timezone()->getOffset( new DateTime( '@' . $ts ) ), $time ) : '—';
	}
	public static function ago( $dt ) {
		if ( ! $dt || ! is_object( $dt ) ) { return ''; }
		return self::fa( human_time_diff( $dt->getTimestamp(), time() ) ) . ' پیش';
	}
	public static function money( $n ) {
		return self::fa( html_entity_decode( wp_strip_all_tags( wc_price( (float) $n ) ), ENT_QUOTES, 'UTF-8' ) );
	}
	public static function url( $tab = 'dashboard', $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => PP_SLUG, 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}
	public static function hpos() {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}
	public static function statuses() {
		$fa = array(
			'pending'    => 'در انتظار پرداخت',
			'processing' => 'در حال آماده‌سازی',
			'on-hold'    => 'در انتظار بررسی',
			'pp-shipped' => 'ارسال شده',
			'completed'  => 'تحویل شده',
			'cancelled'  => 'لغو شده',
			'refunded'   => 'مسترد شده',
			'failed'     => 'ناموفق',
			'checkout-draft' => 'پیش‌نویس',
		);
		$out = array();
		foreach ( wc_get_order_statuses() as $k => $label ) {
			$s = 'wc-' === substr( $k, 0, 3 ) ? substr( $k, 3 ) : $k;
			$out[ $s ] = isset( $fa[ $s ] ) ? $fa[ $s ] : $label;
		}
		return $out;
	}
	public static function status_label( $s ) {
		$all = self::statuses();
		return isset( $all[ $s ] ) ? $all[ $s ] : wc_get_order_status_name( $s );
	}
	public static function badge( $s ) {
		return '<span class="pp-badge st-' . esc_attr( $s ) . '">' . esc_html( self::status_label( $s ) ) . '</span>';
	}
	/* مرحله‌ی بعدی منطقی هر سفارش برای دکمه‌ی یک‌کلیکی */
	public static function next_step( $s ) {
		$ship = (int) self::opt( 'shipped' );
		$map  = array(
			'pending'    => array( 'processing', 'تأیید و آماده‌سازی' ),
			'on-hold'    => array( 'processing', 'تأیید و آماده‌سازی' ),
			'processing' => $ship ? array( 'pp-shipped', 'ارسال شد' ) : array( 'completed', 'تحویل شد' ),
			'pp-shipped' => array( 'completed', 'تحویل شد' ),
		);
		return isset( $map[ $s ] ) ? $map[ $s ] : null;
	}

	/* ---------- وضعیت «ارسال شده» ---------- */
	public static function register_status() {
		if ( ! (int) self::opt( 'shipped' ) ) { return; }
		register_post_status( 'wc-pp-shipped', array(
			'label'                     => 'ارسال شده',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: count */
			'label_count'               => _n_noop( 'ارسال شده <span class="count">(%s)</span>', 'ارسال شده <span class="count">(%s)</span>', 'peecha-panel' ),
		) );
	}
	public static function order_statuses( $st ) {
		if ( ! (int) self::opt( 'shipped' ) ) { return $st; }
		$out = array();
		foreach ( $st as $k => $v ) {
			$out[ $k ] = $v;
			if ( 'wc-processing' === $k ) { $out['wc-pp-shipped'] = 'ارسال شده'; }
		}
		if ( ! isset( $out['wc-pp-shipped'] ) ) { $out['wc-pp-shipped'] = 'ارسال شده'; }
		return $out;
	}
	public static function paid_statuses( $st ) {
		if ( (int) self::opt( 'shipped' ) && is_array( $st ) && ! in_array( 'pp-shipped', $st, true ) ) { $st[] = 'pp-shipped'; }
		return $st;
	}

	/* ---------- منو، ورود و ساده‌سازی پیشخوان ---------- */
	public static function full_view() {
		return current_user_can( 'manage_options' ) || ! (int) self::opt( 'simplify' ) || get_user_meta( get_current_user_id(), 'pp_full_view', true );
	}
	public static function menu() {
		$n     = (int) wc_orders_count( 'processing' );
		$title = 'پنل فروشگاه' . ( $n ? ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . esc_html( self::fa( $n ) ) . '</span></span>' : '' );
		add_menu_page( 'پنل فروشگاه', $title, self::CAP, PP_SLUG, array( __CLASS__, 'render' ), 'dashicons-store', 2 );
	}
	public static function simplify_menu() {
		if ( self::full_view() || ! current_user_can( self::CAP ) ) { return; }
		$hide = apply_filters( 'pp_hidden_menus', array( 'index.php', 'edit-comments.php', 'tools.php', 'woocommerce-marketing', 'wc-admin&path=/analytics/overview', 'wc-admin&path=/marketing', 'jetpack', 'separator1', 'separator2' ) );
		foreach ( $hide as $slug ) { remove_menu_page( $slug ); }
	}
	public static function dashboard_redirect() {
		if ( ! wp_doing_ajax() && current_user_can( self::CAP ) && ! self::full_view() ) {
			wp_safe_redirect( self::url() );
			exit;
		}
	}
	private static function wants_panel( $user ) {
		return $user instanceof WP_User && (int) self::opt( 'redirect' ) && user_can( $user, self::CAP ) && ! user_can( $user, 'manage_options' );
	}
	public static function login_redirect( $to, $requested, $user ) {
		if ( ! self::wants_panel( $user ) ) { return $to; }
		$adm = admin_url();
		if ( '' === (string) $requested || untrailingslashit( $requested ) === untrailingslashit( $adm ) || untrailingslashit( $to ) === untrailingslashit( $adm ) ) {
			return self::url();
		}
		return $to;
	}
	public static function wc_login_redirect( $to, $user ) {
		return self::wants_panel( $user ) ? self::url() : $to;
	}
	public static function admin_bar( $bar ) {
		if ( ! current_user_can( self::CAP ) ) { return; }
		$bar->add_node( array( 'id' => 'pp-panel', 'title' => '<span class="ab-icon dashicons dashicons-store" style="top:2px"></span>پنل فروشگاه', 'href' => self::url() ) );
		if ( (int) self::opt( 'simplify' ) && ! current_user_can( 'manage_options' ) ) {
			$full = (bool) get_user_meta( get_current_user_id(), 'pp_full_view', true );
			$bar->add_node( array( 'parent' => 'pp-panel', 'id' => 'pp-view', 'title' => $full ? 'نمای ساده‌ی پیشخوان' : 'نمای کامل پیشخوان', 'href' => wp_nonce_url( admin_url( 'admin-post.php?action=pp_toggle_view' ), 'pp_toggle_view' ) ) );
		}
	}
	public static function plugin_links( $l ) {
		array_unshift( $l, '<a href="' . esc_url( self::url() ) . '">باز کردن پنل</a>' );
		return $l;
	}
	public static function clean_screen() {
		/* اعلان‌های افزونه‌های دیگر در پنل نمایش داده نشوند تا صفحه ساده و خلوت بماند */
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
		remove_all_actions( 'network_admin_notices' );
		remove_all_actions( 'user_admin_notices' );
	}
	public static function assets( $hook ) {
		if ( 'toplevel_page_' . PP_SLUG !== $hook ) { return; }
		wp_enqueue_style( 'pp-panel', PP_URL . 'assets/panel.css', array(), PP_VER );
		$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard'; // phpcs:ignore
		if ( 'edit' === $tab ) { wp_enqueue_media(); }
		wp_enqueue_script( 'pp-panel', PP_URL . 'assets/panel.js', array(), PP_VER, true );
		if ( 'prices' === $tab ) { wp_enqueue_script( 'pp-prices', PP_URL . 'assets/prices.js', array( 'pp-panel' ), PP_VER, true ); }
		wp_localize_script( 'pp-panel', 'PP', array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'pp_quick' ),
			'pnonce' => wp_create_nonce( 'pp_price' ),
			'cur'   => html_entity_decode( get_woocommerce_currency_symbol() ),
		) );
	}

	/* ---------- آمار داشبورد (یک کوئری سبک + کش ۵ دقیقه‌ای) ---------- */
	public static function flush_stats() { delete_transient( 'pp_stats' ); }
	private static function paid_in() {
		return array_map( function ( $s ) { return 'wc-' . $s; }, wc_get_is_paid_statuses() );
	}
	private static function sales_since( $gmt ) {
		global $wpdb;
		$st = self::paid_in();
		$ph = implode( ',', array_fill( 0, count( $st ), '%s' ) );
		if ( self::hpos() ) {
			$t   = $wpdb->prefix . 'wc_orders';
			$sql = "SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s FROM {$t} WHERE type='shop_order' AND status IN ($ph) AND date_created_gmt >= %s";
		} else {
			$sql = "SELECT COUNT(*) AS c, COALESCE(SUM(pm.meta_value+0),0) AS s FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_order_total' WHERE p.post_type='shop_order' AND p.post_status IN ($ph) AND p.post_date_gmt >= %s";
		}
		$r = $wpdb->get_row( $wpdb->prepare( $sql, array_merge( $st, array( $gmt ) ) ), ARRAY_A ); // phpcs:ignore
		return array( 'c' => $r ? (int) $r['c'] : 0, 's' => $r ? (float) $r['s'] : 0 );
	}
	private static function daily( $days, $gmt_from, $offset ) {
		global $wpdb;
		$st = self::paid_in();
		$ph = implode( ',', array_fill( 0, count( $st ), '%s' ) );
		if ( self::hpos() ) {
			$t   = $wpdb->prefix . 'wc_orders';
			$sql = "SELECT DATE(DATE_ADD(date_created_gmt, INTERVAL %d SECOND)) AS d, COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s FROM {$t} WHERE type='shop_order' AND status IN ($ph) AND date_created_gmt >= %s GROUP BY d";
		} else {
			$sql = "SELECT DATE(DATE_ADD(p.post_date_gmt, INTERVAL %d SECOND)) AS d, COUNT(*) AS c, COALESCE(SUM(pm.meta_value+0),0) AS s FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_order_total' WHERE p.post_type='shop_order' AND p.post_status IN ($ph) AND p.post_date_gmt >= %s GROUP BY d";
		}
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( (int) $offset ), $st, array( $gmt_from ) ) ), ARRAY_A ); // phpcs:ignore
		$by = array();
		foreach ( (array) $rows as $r ) { $by[ $r['d'] ] = array( (int) $r['c'], (float) $r['s'] ); }
		return $by;
	}
	public static function stats() {
		$cached = get_transient( 'pp_stats' );
		if ( is_array( $cached ) ) { return $cached; }
		$tz    = wp_timezone();
		$now   = new DateTimeImmutable( 'now', $tz );
		$today = $now->setTime( 0, 0 );
		$sat   = $today->modify( '-' . ( ( (int) $today->format( 'w' ) + 1 ) % 7 ) . ' days' ); /* هفته از شنبه */
		list( , , $jd ) = self::g2j( (int) $today->format( 'Y' ), (int) $today->format( 'n' ), (int) $today->format( 'j' ) );
		$mstart = $today->modify( '-' . ( $jd - 1 ) . ' days' ); /* اول ماه شمسی */
		$g      = function ( $d ) { return $d->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ); };
		$from14 = $today->modify( '-13 days' );
		$daily  = self::daily( 14, $g( $from14 ), $now->getOffset() );
		$chart  = array();
		for ( $i = 0; $i < 14; $i++ ) {
			$d   = $from14->modify( '+' . $i . ' days' );
			$key = $d->format( 'Y-m-d' );
			list( , $jm, $jdd ) = self::g2j( (int) $d->format( 'Y' ), (int) $d->format( 'n' ), (int) $d->format( 'j' ) );
			$chart[] = array( 'l' => self::fa( $jdd ) . ' ' . self::jmonth( $jm ), 'c' => isset( $daily[ $key ] ) ? $daily[ $key ][0] : 0, 's' => isset( $daily[ $key ] ) ? $daily[ $key ][1] : 0 );
		}
		$s = array(
			'today' => self::sales_since( $g( $today ) ),
			'week'  => self::sales_since( $g( $sat ) ),
			'month' => self::sales_since( $g( $mstart ) ),
			'chart' => $chart,
			'time'  => time(),
		);
		set_transient( 'pp_stats', $s, 5 * MINUTE_IN_SECONDS );
		return $s;
	}
	public static function low_stock( $limit = 8 ) {
		$q = new WP_Query( array(
			'post_type'      => array( 'product', 'product_variation' ),
			'post_status'    => array( 'publish', 'private' ),
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'meta_key'       => '_stock', // phpcs:ignore
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore
				array( 'key' => '_manage_stock', 'value' => 'yes' ),
				array( 'key' => '_stock', 'value' => (int) self::opt( 'low' ), 'compare' => '<=', 'type' => 'NUMERIC' ),
			),
		) );
		return array( 'ids' => $q->posts, 'total' => (int) $q->found_posts );
	}

	/* ---------- صفحه ---------- */
	public static function render() {
		if ( ! current_user_can( self::CAP ) ) { wp_die( 'دسترسی ندارید.' ); }
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard'; // phpcs:ignore
		$tabs = array(
			'dashboard' => array( 'داشبورد', 'dashicons-chart-area' ),
			'orders'    => array( 'سفارش‌ها', 'dashicons-cart' ),
			'products'  => array( 'محصولات', 'dashicons-products' ),
			'prices'    => array( 'تغییر قیمت', 'dashicons-tag' ),
			'edit'      => array( 'افزودن محصول', 'dashicons-plus-alt' ),
			'customers' => array( 'مشتریان', 'dashicons-groups' ),
		);
		if ( current_user_can( 'manage_options' ) ) { $tabs['settings'] = array( 'تنظیمات', 'dashicons-admin-generic' ); }
		$active = 'order' === $tab ? 'orders' : $tab;
		if ( 'edit' === $tab && ! empty( $_GET['id'] ) ) { $active = 'products'; } // phpcs:ignore

		echo '<div class="pp" dir="rtl">';
		echo '<header class="pp-top pp-noprint"><div class="pp-brand"><span class="dashicons dashicons-store"></span><div><b>' . esc_html( get_bloginfo( 'name' ) ) . '</b><small>پنل مدیریت فروشگاه</small></div></div>';
		echo '<nav class="pp-nav">';
		foreach ( $tabs as $k => $t ) {
			echo '<a class="' . ( $active === $k ? 'on' : '' ) . '" href="' . esc_url( self::url( $k ) ) . '"><span class="dashicons ' . esc_attr( $t[1] ) . '"></span>' . esc_html( $t[0] ) . '</a>';
		}
		echo '</nav><div class="pp-top-x"><a class="pp-btn ghost sm" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">مشاهده‌ی سایت ↗</a></div></header>';
		self::notice();
		echo '<main class="pp-main">';
		switch ( $tab ) {
			case 'orders':    self::page_orders(); break;
			case 'order':     self::page_order(); break;
			case 'products':  self::page_products(); break;
			case 'prices':    class_exists( 'PP_Prices' ) ? PP_Prices::page() : self::page_products(); break;
			case 'edit':      self::page_edit(); break;
			case 'customers': self::page_customers(); break;
			case 'settings':  current_user_can( 'manage_options' ) ? self::page_settings() : self::page_dashboard(); break;
			default:          self::page_dashboard();
		}
		echo '</main></div>';
	}

	private static function notice() {
		$m = isset( $_GET['pp_msg'] ) ? sanitize_key( $_GET['pp_msg'] ) : ''; // phpcs:ignore
		$t = array(
			'status'   => array( 'ok', 'وضعیت سفارش به‌روز شد.' ),
			'bulk'     => array( 'ok', 'وضعیت سفارش‌های انتخاب‌شده به‌روز شد.' ),
			'tracking' => array( 'ok', 'کد رهگیری ذخیره شد.' ),
			'note'     => array( 'ok', 'یادداشت اضافه شد.' ),
			'saved'    => array( 'ok', 'محصول ذخیره شد.' ),
			'settings' => array( 'ok', 'تنظیمات ذخیره شد.' ),
			'err'      => array( 'err', 'عملیات انجام نشد. دوباره تلاش کنید.' ),
			'sku'      => array( 'err', 'این کد محصول (SKU) قبلاً برای محصول دیگری ثبت شده است؛ بقیه‌ی تغییرات ذخیره شد.' ),
			'sale'     => array( 'err', 'قیمت فروش ویژه باید کمتر از قیمت اصلی باشد؛ قیمت ویژه ذخیره نشد.' ),
			'name'     => array( 'err', 'نام محصول را وارد کنید.' ),
		);
		if ( isset( $t[ $m ] ) ) { echo '<div class="pp-toast ' . esc_attr( $t[ $m ][0] ) . ' pp-noprint">' . esc_html( $t[ $m ][1] ) . '</div>'; }
	}

	private static function pager( $page, $max, $base_args, $tab ) {
		if ( $max < 2 ) { return; }
		echo '<nav class="pp-pager">';
		if ( $page > 1 ) { echo '<a href="' . esc_url( self::url( $tab, array_merge( $base_args, array( 'paged' => $page - 1 ) ) ) ) . '">→ قبلی</a>'; }
		echo '<span>صفحه‌ی ' . esc_html( self::fa( $page ) ) . ' از ' . esc_html( self::fa( $max ) ) . '</span>';
		if ( $page < $max ) { echo '<a href="' . esc_url( self::url( $tab, array_merge( $base_args, array( 'paged' => $page + 1 ) ) ) ) . '">بعدی ←</a>'; }
		echo '</nav>';
	}

	/* ---------- داشبورد ---------- */
	private static function page_dashboard() {
		$s    = self::stats();
		$proc = (int) wc_orders_count( 'processing' );
		$hold = (int) wc_orders_count( 'on-hold' );
		$low  = self::low_stock();
		$out  = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'stock_status' => 'outofstock', 'limit' => 1, 'paginate' => true, 'return' => 'ids' ) ) : null;
		$outn = $out ? (int) $out->total : 0;

		$u = wp_get_current_user();
		echo '<div class="pp-hello"><h1>سلام ' . esc_html( $u->display_name ) . ' 👋</h1><p>' . esc_html( self::jdate( current_time( 'timestamp' ), false, true ) ) . ' — خلاصه‌ی فروشگاه شما</p></div>'; // phpcs:ignore

		echo '<section class="pp-cards">';
		foreach ( array( 'today' => 'فروش امروز', 'week' => 'فروش این هفته', 'month' => 'فروش این ماه' ) as $k => $l ) {
			echo '<div class="pp-card stat"><small>' . esc_html( $l ) . '</small><b>' . esc_html( self::money( $s[ $k ]['s'] ) ) . '</b><span>' . esc_html( self::fa( $s[ $k ]['c'] ) ) . ' سفارش</span></div>';
		}
		echo '<a class="pp-card stat warn" href="' . esc_url( self::url( 'orders', array( 'status' => 'processing' ) ) ) . '"><small>سفارش‌های منتظر ارسال</small><b>' . esc_html( self::fa( $proc ) ) . '</b><span>' . ( $hold ? esc_html( self::fa( $hold ) ) . ' سفارش هم در انتظار بررسی' : 'مشاهده و آماده‌سازی ←' ) . '</span></a>';
		echo '</section>';

		/* نمودار ۱۴ روز */
		$max = 0;
		foreach ( $s['chart'] as $c ) { $max = max( $max, $c['s'] ); }
		echo '<section class="pp-grid2"><div class="pp-box"><div class="pp-box-h"><h2>فروش ۱۴ روز اخیر</h2></div><div class="pp-chart" role="img" aria-label="نمودار فروش ۱۴ روز اخیر">';
		foreach ( $s['chart'] as $c ) {
			$h = $max > 0 ? max( 3, round( $c['s'] / $max * 100 ) ) : 3;
			echo '<div class="pp-bar" title="' . esc_attr( $c['l'] . ': ' . self::money( $c['s'] ) . ' — ' . self::fa( $c['c'] ) . ' سفارش' ) . '"><i style="height:' . (int) $h . '%"></i><em>' . esc_html( $c['l'] ) . '</em></div>';
		}
		echo '</div></div>';

		/* موجودی کم */
		echo '<div class="pp-box"><div class="pp-box-h"><h2>موجودی رو به اتمام</h2><a href="' . esc_url( self::url( 'products', array( 'stock' => 'low' ) ) ) . '">همه ←</a></div>';
		if ( ! $low['ids'] && ! $outn ) {
			echo '<p class="pp-empty">✔ موجودی همه‌ی محصولات کافی است.</p>';
		} else {
			if ( $outn ) { echo '<a class="pp-alert" href="' . esc_url( self::url( 'products', array( 'stock' => 'outofstock' ) ) ) . '">⚠ ' . esc_html( self::fa( $outn ) ) . ' محصول ناموجود است</a>'; }
			echo '<ul class="pp-list">';
			foreach ( $low['ids'] as $id ) {
				$p = wc_get_product( $id );
				if ( ! $p ) { continue; }
				$pid = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
				echo '<li><span class="pp-thumb">' . wp_kses_post( $p->get_image( array( 40, 40 ) ) ) . '</span><a href="' . esc_url( self::url( 'products', array( 's' => $p->get_parent_id() ? get_the_title( $pid ) : $p->get_name() ) ) ) . '">' . esc_html( $p->get_name() ) . '</a><b class="pp-qty' . ( (int) $p->get_stock_quantity() <= 0 ? ' zero' : '' ) . '">' . esc_html( self::fa( (int) $p->get_stock_quantity() ) ) . ' عدد</b></li>';
			}
			echo '</ul>';
		}
		echo '</div></section>';

		/* سفارش‌های اخیر */
		$orders = wc_get_orders( array( 'limit' => 6, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ) );
		echo '<section class="pp-box"><div class="pp-box-h"><h2>آخرین سفارش‌ها</h2><a href="' . esc_url( self::url( 'orders' ) ) . '">همه‌ی سفارش‌ها ←</a></div>';
		self::orders_table( $orders, false );
		echo '</section>';
	}

	/* ---------- سفارش‌ها ---------- */
	private static function orders_table( $orders, $bulk = true ) {
		if ( ! $orders ) { echo '<p class="pp-empty">سفارشی پیدا نشد.</p>'; return; }
		echo '<div class="pp-table-wrap"><table class="pp-table orders"><thead><tr>';
		if ( $bulk ) { echo '<th class="cb"><input type="checkbox" class="pp-check-all" aria-label="انتخاب همه"></th>'; }
		echo '<th>سفارش</th><th>تاریخ</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th class="pp-noprint">اقدام</th></tr></thead><tbody>';
		foreach ( $orders as $o ) {
			if ( ! is_a( $o, 'WC_Order' ) ) { continue; }
			$id    = $o->get_id();
			$name  = trim( $o->get_formatted_billing_full_name() );
			$phone = $o->get_billing_phone();
			$city  = $o->get_shipping_city() ? $o->get_shipping_city() : $o->get_billing_city();
			$link  = self::url( 'order', array( 'id' => $id ) );
			$next  = self::next_step( $o->get_status() );
			echo '<tr>';
			if ( $bulk ) { echo '<td class="cb"><input type="checkbox" name="ids[]" value="' . (int) $id . '" form="pp-bulk" aria-label="انتخاب سفارش ' . (int) $id . '"></td>'; }
			echo '<td data-l="سفارش"><a class="pp-oid" href="' . esc_url( $link ) . '">#' . esc_html( self::fa( $o->get_order_number() ) ) . '</a><small>' . esc_html( self::fa( $o->get_item_count() ) ) . ' کالا</small></td>';
			echo '<td data-l="تاریخ">' . esc_html( self::wc_jdate( $o->get_date_created() ) ) . '<small>' . esc_html( self::ago( $o->get_date_created() ) ) . '</small></td>';
			echo '<td data-l="مشتری"><b>' . esc_html( $name ? $name : 'مهمان' ) . '</b><small>' . esc_html( self::fa( $phone ) ) . ( $city ? ' · ' . esc_html( $city ) : '' ) . '</small></td>';
			echo '<td data-l="مبلغ"><b>' . esc_html( self::money( $o->get_total() ) ) . '</b><small>' . esc_html( $o->get_payment_method_title() ) . '</small></td>';
			echo '<td data-l="وضعیت">' . self::badge( $o->get_status() ) . '</td>'; // phpcs:ignore
			echo '<td class="pp-act pp-noprint">';
			if ( $next ) {
				echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="pp_order_status"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="status" value="' . esc_attr( $next[0] ) . '"><input type="hidden" name="back" value="' . esc_attr( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) . '">';
				wp_nonce_field( 'pp_order_status_' . $id, '_ppn', false );
				echo '<button class="pp-btn sm">' . esc_html( $next[1] ) . '</button></form>';
			}
			echo '<a class="pp-btn ghost sm" href="' . esc_url( $link ) . '">جزئیات</a></td></tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function page_orders() {
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : ''; // phpcs:ignore
		$s      = isset( $_GET['s'] ) ? trim( self::en( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) ) : ''; // phpcs:ignore
		$cust   = isset( $_GET['customer'] ) ? absint( $_GET['customer'] ) : 0; // phpcs:ignore
		$page   = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$all    = self::statuses();
		unset( $all['checkout-draft'] );
		if ( $status && ! isset( $all[ $status ] ) ) { $status = ''; }

		echo '<div class="pp-head"><h1>سفارش‌ها</h1><form class="pp-search" method="get"><input type="hidden" name="page" value="' . esc_attr( PP_SLUG ) . '"><input type="hidden" name="tab" value="orders">' . ( $status ? '<input type="hidden" name="status" value="' . esc_attr( $status ) . '">' : '' ) . '<input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="شماره سفارش، نام، موبایل یا ایمیل…"><button class="pp-btn">جستجو</button></form></div>';

		echo '<div class="pp-pills">';
		echo '<a class="' . ( ! $status ? 'on' : '' ) . '" href="' . esc_url( self::url( 'orders' ) ) . '">همه</a>';
		foreach ( $all as $k => $l ) {
			$n = (int) wc_orders_count( $k );
			if ( ! $n && $k !== $status ) { continue; }
			echo '<a class="st-' . esc_attr( $k ) . ( $status === $k ? ' on' : '' ) . '" href="' . esc_url( self::url( 'orders', array( 'status' => $k ) ) ) . '">' . esc_html( $l ) . ' <i>' . esc_html( self::fa( $n ) ) . '</i></a>';
		}
		echo '</div>';

		$args = array(
			'limit'    => (int) self::opt( 'per_page' ),
			'page'     => $page,
			'paginate' => true,
			'orderby'  => 'date',
			'order'    => 'DESC',
			'type'     => 'shop_order',
			'status'   => $status ? array( 'wc-' . $status ) : array_map( function ( $k ) { return 'wc-' . $k; }, array_keys( $all ) ),
		);
		if ( $cust ) { $args['customer_id'] = $cust; }
		if ( '' !== $s ) {
			$s = ltrim( $s, '#' );
			if ( self::hpos() ) {
				$args['s'] = $s;
			} else {
				$ids = function_exists( 'wc_order_search' ) ? wc_order_search( $s ) : array();
				if ( ctype_digit( $s ) ) { $ids[] = (int) $s; }
				$args['post__in'] = $ids ? array_map( 'absint', $ids ) : array( 0 );
			}
		}
		$res = wc_get_orders( $args );

		if ( $cust ) {
			$cu = get_userdata( $cust );
			echo '<p class="pp-filter-note">سفارش‌های مشتری: <b>' . esc_html( $cu ? $cu->display_name : '#' . $cust ) . '</b> — <a href="' . esc_url( self::url( 'orders' ) ) . '">حذف فیلتر ×</a></p>';
		}

		echo '<section class="pp-box flush">';
		echo '<form id="pp-bulk" class="pp-bulk pp-noprint" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="pp_bulk_status"><input type="hidden" name="back" value="' . esc_attr( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '' ) . '">';
		wp_nonce_field( 'pp_bulk_status', '_ppn', false );
		echo '<span>تغییر وضعیت موارد انتخاب‌شده به</span><select name="status">';
		foreach ( $all as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '">' . esc_html( $l ) . '</option>'; }
		echo '</select><button class="pp-btn sm" data-confirm="وضعیت سفارش‌های انتخاب‌شده تغییر کند؟">اعمال</button></form>';
		self::orders_table( $res->orders, true );
		echo '</section>';
		self::pager( $page, (int) $res->max_num_pages, array_filter( array( 'status' => $status, 's' => $s, 'customer' => $cust ) ), 'orders' );
	}

	private static function page_order() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$o  = $id ? wc_get_order( $id ) : false;
		if ( ! $o || 'shop_order' !== $o->get_type() ) { echo '<p class="pp-empty">سفارش پیدا نشد.</p>'; return; }
		$st   = $o->get_status();
		$post = esc_url( admin_url( 'admin-post.php' ) );

		echo '<div class="pp-head"><h1><a class="pp-back pp-noprint" href="' . esc_url( self::url( 'orders' ) ) . '" aria-label="بازگشت">→</a> سفارش #' . esc_html( self::fa( $o->get_order_number() ) ) . ' ' . self::badge( $st ) . '</h1>'; // phpcs:ignore
		echo '<div class="pp-head-x pp-noprint"><button type="button" class="pp-btn ghost" data-print>🖨 چاپ فاکتور / برچسب</button>';
		if ( current_user_can( 'edit_shop_orders' ) ) { echo '<a class="pp-btn ghost" href="' . esc_url( $o->get_edit_order_url() ) . '">ویرایشگر کامل</a>'; }
		echo '</div></div>';
		echo '<p class="pp-sub">ثبت: ' . esc_html( self::wc_jdate( $o->get_date_created() ) ) . ( $o->get_date_paid() ? ' · پرداخت: ' . esc_html( self::wc_jdate( $o->get_date_paid() ) ) : '' ) . ' · ' . esc_html( $o->get_payment_method_title() ) . '</p>';

		/* دکمه‌های وضعیت */
		echo '<div class="pp-steps pp-noprint">';
		foreach ( self::statuses() as $k => $l ) {
			if ( 'checkout-draft' === $k ) { continue; }
			if ( $k === $st ) { echo '<span class="pp-step on st-' . esc_attr( $k ) . '">' . esc_html( $l ) . '</span>'; continue; }
			echo '<form method="post" action="' . $post . '"><input type="hidden" name="action" value="pp_order_status"><input type="hidden" name="id" value="' . (int) $id . '"><input type="hidden" name="status" value="' . esc_attr( $k ) . '">'; // phpcs:ignore
			wp_nonce_field( 'pp_order_status_' . $id, '_ppn', false );
			$danger = in_array( $k, array( 'cancelled', 'refunded', 'failed' ), true );
			echo '<button class="pp-step st-' . esc_attr( $k ) . '"' . ( $danger ? ' data-confirm="' . esc_attr( 'وضعیت به «' . $l . '» تغییر کند؟' ) . '"' : '' ) . '>' . esc_html( $l ) . '</button></form>';
		}
		echo '</div>';

		echo '<div class="pp-grid2 order">';
		/* اقلام */
		echo '<section class="pp-box"><div class="pp-box-h"><h2>اقلام سفارش</h2></div><table class="pp-table items"><tbody>';
		foreach ( $o->get_items() as $item ) {
			$p   = $item->get_product();
			$img = $p ? $p->get_image( array( 56, 56 ) ) : '';
			echo '<tr><td class="pp-thumb">' . wp_kses_post( $img ) . '</td><td><b>' . esc_html( $item->get_name() ) . '</b>' . wp_kses_post( wc_display_item_meta( $item, array( 'echo' => false, 'before' => '<div class="pp-meta">', 'after' => '</div>', 'separator' => ' · ' ) ) ) . ( $p && $p->get_sku() ? '<small>کد: ' . esc_html( $p->get_sku() ) . '</small>' : '' ) . '</td><td class="n">× ' . esc_html( self::fa( $item->get_quantity() ) ) . '</td><td class="n"><b>' . esc_html( self::money( $item->get_total() ) ) . '</b></td></tr>';
		}
		echo '</tbody></table><dl class="pp-totals">';
		echo '<dt>جمع کالاها</dt><dd>' . esc_html( self::money( $o->get_subtotal() ) ) . '</dd>';
		if ( (float) $o->get_total_discount() ) { echo '<dt>تخفیف</dt><dd>−' . esc_html( self::money( $o->get_total_discount() ) ) . '</dd>'; }
		echo '<dt>ارسال' . ( $o->get_shipping_method() ? ' (' . esc_html( $o->get_shipping_method() ) . ')' : '' ) . '</dt><dd>' . esc_html( self::money( $o->get_shipping_total() ) ) . '</dd>';
		foreach ( $o->get_fees() as $fee ) { echo '<dt>' . esc_html( $fee->get_name() ) . '</dt><dd>' . esc_html( self::money( $fee->get_total() ) ) . '</dd>'; }
		if ( (float) $o->get_total_tax() ) { echo '<dt>مالیات</dt><dd>' . esc_html( self::money( $o->get_total_tax() ) ) . '</dd>'; }
		echo '<dt class="t">مبلغ کل</dt><dd class="t">' . esc_html( self::money( $o->get_total() ) ) . '</dd></dl>';
		if ( $o->get_customer_note() ) { echo '<div class="pp-cnote"><b>یادداشت مشتری:</b> ' . esc_html( $o->get_customer_note() ) . '</div>'; }
		echo '</section>';

		/* مشتری و آدرس */
		$addr = $o->has_shipping_address() ? $o->get_formatted_shipping_address() : $o->get_formatted_billing_address();
		$phone = $o->get_billing_phone();
		echo '<aside class="pp-side"><section class="pp-box pp-label"><div class="pp-box-h"><h2>گیرنده</h2></div>';
		echo '<p class="pp-cust"><b>' . esc_html( trim( $o->get_formatted_shipping_full_name() ) ? $o->get_formatted_shipping_full_name() : $o->get_formatted_billing_full_name() ) . '</b></p>';
		if ( $phone ) { echo '<p><a class="pp-tel" href="tel:' . esc_attr( $phone ) . '">📞 ' . esc_html( self::fa( $phone ) ) . '</a></p>'; }
		if ( $o->get_billing_email() ) { echo '<p class="pp-ltr"><a href="mailto:' . esc_attr( $o->get_billing_email() ) . '">' . esc_html( $o->get_billing_email() ) . '</a></p>'; }
		echo '<address>' . ( $addr ? wp_kses( self::fa( $addr ), array( 'br' => array() ) ) : 'آدرسی ثبت نشده است.' ) . '</address>';
		$pc = $o->get_shipping_postcode() ? $o->get_shipping_postcode() : $o->get_billing_postcode();
		if ( $pc ) { echo '<p>کد پستی: <b>' . esc_html( self::fa( $pc ) ) . '</b></p>'; }
		if ( $o->get_customer_id() ) { echo '<p class="pp-noprint"><a href="' . esc_url( self::url( 'orders', array( 'customer' => $o->get_customer_id() ) ) ) . '">سایر سفارش‌های این مشتری ←</a></p>'; }
		echo '</section>';

		/* کد رهگیری */
		$code    = $o->get_meta( '_pp_tracking_code' );
		$carrier = $o->get_meta( '_pp_tracking_carrier' );
		echo '<section class="pp-box pp-noprint"><div class="pp-box-h"><h2>ارسال و کد رهگیری</h2></div><form method="post" action="' . $post . '" class="pp-form">'; // phpcs:ignore
		echo '<input type="hidden" name="action" value="pp_tracking"><input type="hidden" name="id" value="' . (int) $id . '">';
		wp_nonce_field( 'pp_tracking_' . $id, '_ppn', false );
		echo '<label>روش ارسال<select name="carrier">';
		$carriers = array_filter( array_map( 'trim', explode( "\n", (string) self::opt( 'carriers' ) ) ) );
		if ( $carrier && ! in_array( $carrier, $carriers, true ) ) { $carriers[] = $carrier; }
		foreach ( $carriers as $c ) { echo '<option' . selected( $carrier, $c, false ) . '>' . esc_html( $c ) . '</option>'; }
		echo '</select></label><label>کد رهگیری<input type="text" name="code" value="' . esc_attr( $code ) . '" dir="ltr" inputmode="numeric" autocomplete="off"></label>';
		if ( 'processing' === $st && (int) self::opt( 'shipped' ) ) { echo '<label class="pp-chk"><input type="checkbox" name="mark" value="1" checked> وضعیت سفارش «ارسال شده» شود</label>'; }
		echo '<label class="pp-chk"><input type="checkbox" name="notify" value="1" checked> کد رهگیری برای مشتری ایمیل شود</label><button class="pp-btn">ذخیره‌ی کد رهگیری</button></form></section>';

		/* یادداشت‌ها */
		echo '<section class="pp-box pp-noprint"><div class="pp-box-h"><h2>یادداشت‌ها</h2></div><form method="post" action="' . $post . '" class="pp-form">'; // phpcs:ignore
		echo '<input type="hidden" name="action" value="pp_order_note"><input type="hidden" name="id" value="' . (int) $id . '">';
		wp_nonce_field( 'pp_order_note_' . $id, '_ppn', false );
		echo '<textarea name="note" rows="2" placeholder="یادداشت جدید…" required></textarea><label class="pp-chk"><input type="checkbox" name="customer" value="1"> برای مشتری هم ارسال شود</label><button class="pp-btn ghost">افزودن یادداشت</button></form><ul class="pp-notes">';
		foreach ( wc_get_order_notes( array( 'order_id' => $id, 'limit' => 15 ) ) as $n ) {
			echo '<li class="' . ( $n->customer_note ? 'cust' : '' ) . '"><div>' . wp_kses_post( wpautop( wptexturize( $n->content ) ) ) . '</div><small>' . esc_html( self::wc_jdate( $n->date_created ) ) . ( $n->added_by && 'system' !== $n->added_by ? ' · ' . esc_html( $n->added_by ) : '' ) . ( $n->customer_note ? ' · برای مشتری' : '' ) . '</small></li>';
		}
		echo '</ul></section></aside></div>';
	}

	/* ---------- اکشن‌های سفارش ---------- */
	private static function back( $msg, $fallback ) {
		$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : ''; // phpcs:ignore
		$to   = $back ? $back : $fallback;
		$to   = remove_query_arg( 'pp_msg', $to );
		wp_safe_redirect( add_query_arg( 'pp_msg', $msg, $to ) );
		exit;
	}
	private static function guard( $nonce_action, $cap = self::CAP ) {
		if ( ! current_user_can( $cap ) ) { wp_die( 'دسترسی ندارید.', 403 ); }
		check_admin_referer( $nonce_action, '_ppn' );
	}
	private static function valid_status( $s ) {
		$s = sanitize_key( $s );
		return array_key_exists( $s, self::statuses() ) ? $s : '';
	}
	public static function act_order_status() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
		self::guard( 'pp_order_status_' . $id );
		$st = self::valid_status( isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : '' ); // phpcs:ignore
		$o  = wc_get_order( $id );
		if ( ! $o || ! $st ) { self::back( 'err', self::url( 'orders' ) ); }
		$o->update_status( $st, 'از پنل فروشگاه: ', true );
		self::back( 'status', self::url( 'order', array( 'id' => $id ) ) );
	}
	public static function act_bulk_status() {
		self::guard( 'pp_bulk_status' );
		$st  = self::valid_status( isset( $_POST['status'] ) ? wp_unslash( $_POST['status'] ) : '' ); // phpcs:ignore
		$ids = isset( $_POST['ids'] ) ? array_slice( array_filter( array_map( 'absint', (array) $_POST['ids'] ) ), 0, 200 ) : array(); // phpcs:ignore
		if ( ! $st || ! $ids ) { self::back( 'err', self::url( 'orders' ) ); }
		foreach ( $ids as $id ) {
			$o = wc_get_order( $id );
			if ( $o && 'shop_order' === $o->get_type() ) { $o->update_status( $st, 'تغییر گروهی از پنل فروشگاه: ', true ); }
		}
		self::back( 'bulk', self::url( 'orders' ) );
	}
	public static function act_tracking() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
		self::guard( 'pp_tracking_' . $id );
		$o = wc_get_order( $id );
		if ( ! $o ) { self::back( 'err', self::url( 'orders' ) ); }
		$code    = substr( preg_replace( '/[^A-Za-z0-9\-]/', '', self::en( sanitize_text_field( wp_unslash( isset( $_POST['code'] ) ? $_POST['code'] : '' ) ) ) ), 0, 60 ); // phpcs:ignore
		$carrier = substr( sanitize_text_field( wp_unslash( isset( $_POST['carrier'] ) ? $_POST['carrier'] : '' ) ), 0, 60 ); // phpcs:ignore
		$o->update_meta_data( '_pp_tracking_code', $code );
		$o->update_meta_data( '_pp_tracking_carrier', $carrier );
		$o->save();
		if ( $code ) {
			$msg = sprintf( 'سفارش شما با %1$s ارسال شد. کد رهگیری: %2$s', $carrier ? $carrier : 'پست', $code );
			$url = self::track_link( $code, $carrier );
			if ( $url ) { $msg .= "\n" . 'پیگیری مرسوله: ' . $url; }
			$o->add_order_note( $msg, ! empty( $_POST['notify'] ) ? 1 : 0, true ); // phpcs:ignore
			if ( ! empty( $_POST['mark'] ) && 'processing' === $o->get_status() && (int) self::opt( 'shipped' ) ) { // phpcs:ignore
				$o->update_status( 'pp-shipped', '', true );
			}
		}
		self::back( 'tracking', self::url( 'order', array( 'id' => $id ) ) );
	}
	public static function act_order_note() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
		self::guard( 'pp_order_note_' . $id );
		$o    = wc_get_order( $id );
		$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : ''; // phpcs:ignore
		if ( ! $o || '' === trim( $note ) ) { self::back( 'err', self::url( 'order', array( 'id' => $id ) ) ); }
		$o->add_order_note( $note, ! empty( $_POST['customer'] ) ? 1 : 0, true ); // phpcs:ignore
		self::back( 'note', self::url( 'order', array( 'id' => $id ) ) );
	}
	public static function track_link( $code, $carrier ) {
		$tpl = (string) self::opt( 'track_url' );
		if ( ! $code || ! $tpl || ( $carrier && false === mb_strpos( $carrier, 'پست' ) ) ) { return ''; }
		return str_replace( '{code}', rawurlencode( $code ), $tpl );
	}
	public static function front_tracking( $order ) {
		if ( ! is_a( $order, 'WC_Order' ) ) { return; }
		$code = $order->get_meta( '_pp_tracking_code' );
		if ( ! $code ) { return; }
		$c   = $order->get_meta( '_pp_tracking_carrier' );
		$url = self::track_link( $code, $c );
		echo '<div class="woocommerce-info pp-track-front">📦 سفارش شما' . ( $c ? ' با ' . esc_html( $c ) : '' ) . ' ارسال شد. کد رهگیری: <b dir="ltr">' . esc_html( $code ) . '</b>' . ( $url ? ' — <a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">پیگیری مرسوله</a>' : '' ) . '</div>';
	}
	public static function email_tracking( $order, $sent_to_admin = false, $plain = false ) {
		if ( $sent_to_admin || ! is_a( $order, 'WC_Order' ) ) { return; }
		$code = $order->get_meta( '_pp_tracking_code' );
		if ( ! $code ) { return; }
		$c = $order->get_meta( '_pp_tracking_carrier' );
		if ( $plain ) { echo "\n" . 'کد رهگیری' . ( $c ? ' (' . $c . ')' : '' ) . ': ' . $code . "\n"; return; } // phpcs:ignore
		echo '<p style="margin:0 0 16px">📦 کد رهگیری' . ( $c ? ' (' . esc_html( $c ) . ')' : '' ) . ': <b>' . esc_html( $code ) . '</b></p>';
	}

	/* ---------- محصولات ---------- */
	private static function page_products() {
		$s     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$cat   = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : ''; // phpcs:ignore
		$stock = isset( $_GET['stock'] ) ? sanitize_key( $_GET['stock'] ) : ''; // phpcs:ignore
		$page  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore

		echo '<div class="pp-head"><h1>محصولات</h1><a class="pp-btn" href="' . esc_url( self::url( 'edit' ) ) . '">+ افزودن محصول</a></div>';
		echo '<form class="pp-filters" method="get"><input type="hidden" name="page" value="' . esc_attr( PP_SLUG ) . '"><input type="hidden" name="tab" value="products">';
		echo '<input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="نام یا کد محصول…"><select name="cat"><option value="">همه‌ی دسته‌ها</option>';
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 300 ) );
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '"' . selected( $cat, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } }
		echo '</select><select name="stock"><option value="">همه‌ی موجودی‌ها</option>';
		foreach ( array( 'instock' => 'موجود', 'outofstock' => 'ناموجود', 'low' => 'رو به اتمام', 'onbackorder' => 'پیش‌فروش' ) as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $stock, $k, false ) . '>' . esc_html( $l ) . '</option>'; }
		echo '</select><button class="pp-btn ghost">فیلتر</button></form>';

		$args = array( 'limit' => (int) self::opt( 'per_page' ), 'page' => $page, 'paginate' => true, 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'orderby' => 'date', 'order' => 'DESC' );
		if ( '' !== $s ) {
			$by_sku = wc_get_product_id_by_sku( $s );
			if ( $by_sku ) { $pp = wc_get_product( $by_sku ); $args['include'] = array( $pp && $pp->get_parent_id() ? $pp->get_parent_id() : $by_sku ); }
			else { $args['s'] = $s; }
		}
		if ( $cat ) { $args['category'] = array( $cat ); }
		if ( in_array( $stock, array( 'instock', 'outofstock', 'onbackorder' ), true ) ) { $args['stock_status'] = $stock; }
		if ( 'low' === $stock ) {
			$low = self::low_stock( 200 );
			$ids = array();
			foreach ( $low['ids'] as $lid ) { $par = wp_get_post_parent_id( $lid ); $ids[] = $par ? $par : $lid; }
			$args['include'] = $ids ? array_values( array_unique( $ids ) ) : array( 0 );
		}
		$res = wc_get_products( $args );

		if ( ! $res->products ) { echo '<section class="pp-box"><p class="pp-empty">محصولی پیدا نشد.</p></section>'; return; }
		echo '<section class="pp-box flush"><div class="pp-table-wrap"><table class="pp-table products"><thead><tr><th></th><th>محصول</th><th>قیمت اصلی</th><th>قیمت ویژه</th><th>موجودی</th><th></th></tr></thead><tbody>';
		foreach ( $res->products as $p ) {
			self::product_row( $p, false );
			if ( $p->is_type( 'variable' ) ) {
				$kids = array_slice( $p->get_children(), 0, 40 );
				foreach ( $kids as $vid ) { $v = wc_get_product( $vid ); if ( $v ) { self::product_row( $v, $p->get_id() ); } }
			}
		}
		echo '</tbody></table></div></section>';
		self::pager( $page, (int) $res->max_num_pages, array_filter( array( 's' => $s, 'cat' => $cat, 'stock' => $stock ) ), 'products' );
	}

	private static function product_row( $p, $parent ) {
		$id       = $p->get_id();
		$variable = $p->is_type( 'variable' );
		$editable = ! $variable && ( $p->is_type( 'simple' ) || $p->is_type( 'variation' ) || $p->is_type( 'external' ) );
		$status   = get_post_status( $parent ? $parent : $id );
		$stl      = array( 'publish' => '', 'draft' => 'پیش‌نویس', 'pending' => 'در انتظار بررسی', 'private' => 'خصوصی' );
		$name     = $parent ? wc_get_formatted_variation( $p, true, false, false ) : $p->get_name();
		echo '<tr class="pp-prow' . ( $parent ? ' var pp-var-of-' . (int) $parent : '' ) . '" data-id="' . (int) $id . '"' . ( $parent ? ' hidden' : '' ) . '>';
		echo '<td class="pp-thumb">' . ( $parent ? '<span class="pp-vdot">↳</span>' : wp_kses_post( $p->get_image( array( 48, 48 ) ) ) ) . '</td>';
		echo '<td data-l="محصول"><b>' . esc_html( $name ? $name : $p->get_name() ) . '</b><small>';
		if ( $p->get_sku() ) { echo 'کد: ' . esc_html( $p->get_sku() ) . ' · '; }
		if ( $variable ) { echo 'متغیر — ' . esc_html( self::fa( count( $p->get_children() ) ) ) . ' حالت · ' . wp_kses_post( self::fa( $p->get_price_html() ) ); }
		elseif ( ! $editable ) { echo esc_html( wc_get_product_types()[ $p->get_type() ] ?? $p->get_type() ); }
		if ( ! empty( $stl[ $status ] ) ) { echo ' <i class="pp-tag">' . esc_html( $stl[ $status ] ) . '</i>'; }
		echo '</small></td>';
		if ( $editable ) {
			echo '<td data-l="قیمت اصلی"><input class="pp-in" type="text" inputmode="numeric" name="regular" value="' . esc_attr( $p->get_regular_price() ) . '" dir="ltr"></td>';
			echo '<td data-l="قیمت ویژه"><input class="pp-in" type="text" inputmode="numeric" name="sale" value="' . esc_attr( $p->get_sale_price() ) . '" dir="ltr" placeholder="—"></td>';
			echo '<td data-l="موجودی">';
			if ( $p->managing_stock() ) {
				echo '<input class="pp-in qty" type="number" step="1" name="stock" value="' . esc_attr( (int) $p->get_stock_quantity() ) . '" dir="ltr">';
			} else {
				echo '<select class="pp-in" name="stock_status">';
				foreach ( array( 'instock' => 'موجود', 'outofstock' => 'ناموجود', 'onbackorder' => 'پیش‌فروش' ) as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $p->get_stock_status(), $k, false ) . '>' . esc_html( $l ) . '</option>'; }
				echo '</select>';
			}
			echo '</td><td class="pp-act"><button type="button" class="pp-btn sm pp-qsave">ذخیره</button>';
		} else {
			$stock_txt = $p->managing_stock() ? self::fa( (int) $p->get_stock_quantity() ) . ' عدد' : ( 'outofstock' === $p->get_stock_status() ? 'ناموجود' : 'موجود' );
			echo '<td colspan="2" class="pp-muted">' . ( $variable ? 'قیمت هر حالت را جداگانه ویرایش کنید' : '' ) . '</td><td>' . esc_html( $stock_txt ) . '</td><td class="pp-act">';
			if ( $variable ) { echo '<button type="button" class="pp-btn sm ghost pp-vtoggle" data-for="' . (int) $id . '">حالت‌ها ▾</button>'; }
		}
		if ( ! $parent ) {
			if ( $p->is_type( 'simple' ) || $p->is_type( 'variable' ) ) { echo '<a class="pp-btn sm ghost" href="' . esc_url( self::url( 'edit', array( 'id' => $id ) ) ) . '">ویرایش</a>'; }
			elseif ( current_user_can( 'edit_product', $id ) ) { echo '<a class="pp-btn sm ghost" href="' . esc_url( get_edit_post_link( $id, 'raw' ) ) . '">ویرایش</a>'; }
			if ( 'publish' === $status ) { echo '<a class="pp-btn sm ghost" href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener" aria-label="مشاهده">↗</a>'; }
		}
		echo '<span class="pp-rmsg" aria-live="polite"></span></td></tr>';
	}

	public static function ajax_quick_save() {
		check_ajax_referer( 'pp_quick', 'nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$p  = $id ? wc_get_product( $id ) : false;
		if ( ! $p ) { wp_send_json_error( array( 'msg' => 'محصول پیدا نشد' ), 404 ); }
		$check = $p->get_parent_id() ? $p->get_parent_id() : $id;
		if ( ! current_user_can( 'edit_product', $check ) ) { wp_send_json_error( array( 'msg' => 'دسترسی ندارید' ), 403 ); }
		if ( $p->is_type( 'variable' ) || $p->is_type( 'grouped' ) ) { wp_send_json_error( array( 'msg' => 'این نوع محصول ویرایش سریع ندارد' ), 400 ); }

		$reg  = self::num_in( 'regular' );
		$sale = self::num_in( 'sale' );
		$warn = '';
		if ( null !== $reg ) { $p->set_regular_price( $reg ); }
		if ( null !== $sale ) {
			$r = null !== $reg ? $reg : $p->get_regular_price();
			if ( '' !== $sale && '' !== $r && (float) $sale >= (float) $r ) { $warn = 'قیمت ویژه باید کمتر از اصلی باشد'; }
			else { $p->set_sale_price( $sale ); }
		}
		if ( isset( $_POST['stock'] ) && $p->managing_stock() ) {
			$q = self::num_in( 'stock' );
			if ( '' !== $q && null !== $q ) { wc_update_product_stock( $p, (int) $q, 'set', true ); }
		}
		if ( isset( $_POST['stock_status'] ) && ! $p->managing_stock() ) {
			$ss = sanitize_key( wp_unslash( $_POST['stock_status'] ) );
			if ( in_array( $ss, array( 'instock', 'outofstock', 'onbackorder' ), true ) ) { $p->set_stock_status( $ss ); }
		}
		$p->save();
		if ( $p->get_parent_id() && function_exists( 'wc_delete_product_transients' ) ) { WC_Product_Variable::sync( $p->get_parent_id() ); wc_delete_product_transients( $p->get_parent_id() ); }
		self::flush_stats();
		$p = wc_get_product( $id );
		wp_send_json_success( array(
			'msg'     => $warn ? $warn : 'ذخیره شد ✔',
			'warn'    => (bool) $warn,
			'regular' => $p->get_regular_price(),
			'sale'    => $p->get_sale_price(),
			'stock'   => $p->managing_stock() ? (int) $p->get_stock_quantity() : null,
		) );
	}

	/* ---------- افزودن / ویرایش محصول (ساده و متغیر) ---------- */
	private static function attr_taxonomies() {
		$out = array();
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) { return $out; }
		foreach ( (array) wc_get_attribute_taxonomies() as $t ) {
			$tax = wc_attribute_taxonomy_name( $t->attribute_name );
			if ( ! taxonomy_exists( $tax ) ) { continue; }
			$terms = get_terms( array( 'taxonomy' => $tax, 'hide_empty' => false, 'number' => 150, 'orderby' => 'name' ) );
			$out[ $tax ] = array( 'label' => $t->attribute_label, 'terms' => is_wp_error( $terms ) ? array() : $terms );
		}
		return $out;
	}
	/* ویژگی‌های محصول به شکل ساده: [key, label, tax, options(value=>label), visible, variation] */
	private static function product_attrs( $p ) {
		$list = array();
		if ( ! $p ) { return $list; }
		foreach ( $p->get_attributes() as $key => $a ) {
			if ( ! is_a( $a, 'WC_Product_Attribute' ) ) { continue; }
			$opts = array();
			if ( $a->is_taxonomy() ) {
				foreach ( $a->get_terms() ? $a->get_terms() : array() as $t ) { $opts[ $t->slug ] = $t->name; }
			} else {
				foreach ( $a->get_options() as $o ) { $opts[ $o ] = $o; }
			}
			$list[] = array(
				'key'   => $key,
				'label' => $a->is_taxonomy() ? wc_attribute_label( $a->get_name() ) : $a->get_name(),
				'tax'   => $a->is_taxonomy() ? $a->get_name() : '',
				'ids'   => $a->is_taxonomy() ? array_map( 'intval', $a->get_options() ) : array(),
				'opts'  => $opts,
				'vis'   => $a->get_visible(),
				'var'   => $a->get_variation(),
			);
		}
		return $list;
	}
	private static function attr_row( $i, $a, $taxes ) {
		$tax = $a ? $a['tax'] : '';
		echo '<div class="pp-attr" data-i="' . esc_attr( $i ) . '"><div class="pp-attr-h">';
		echo '<select name="attr[' . esc_attr( $i ) . '][tax]" class="pp-attr-tax"><option value="">✎ ویژگی دلخواه (متنی)</option>';
		foreach ( $taxes as $tk => $t ) { echo '<option value="' . esc_attr( $tk ) . '"' . selected( $tax, $tk, false ) . '>' . esc_html( $t['label'] ) . '</option>'; }
		echo '</select><input type="text" class="pp-attr-name" name="attr[' . esc_attr( $i ) . '][name]" placeholder="نام ویژگی (مثلاً سایز)" value="' . esc_attr( $a && ! $tax ? $a['label'] : '' ) . '"' . ( $tax ? ' hidden' : '' ) . '>';
		echo '<button type="button" class="pp-btn sm ghost pp-attr-x" aria-label="حذف ویژگی">حذف</button></div>';
		foreach ( $taxes as $tk => $t ) {
			echo '<div class="pp-attr-terms" data-tax="' . esc_attr( $tk ) . '"' . ( $tax === $tk ? '' : ' hidden' ) . '>';
			foreach ( $t['terms'] as $term ) {
				$on = $a && $tax === $tk && in_array( (int) $term->term_id, $a['ids'], true );
				echo '<label class="pp-term"><input type="checkbox" name="attr[' . esc_attr( $i ) . '][terms][]" value="' . (int) $term->term_id . '"' . checked( $on, true, false ) . '><span>' . esc_html( $term->name ) . '</span></label>';
			}
			echo '</div>';
		}
		$vals = $a && ! $tax ? implode( ' | ', array_values( $a['opts'] ) ) : '';
		echo '<input type="text" class="pp-attr-vals" name="attr[' . esc_attr( $i ) . '][values]" value="' . esc_attr( $vals ) . '" placeholder="' . esc_attr( $tax ? 'مقدار جدید (اختیاری) — مثلاً: طوسی | سرمه‌ای' : 'مقدارها را با | جدا کنید — مثلاً: S | M | L | XL' ) . '">';
		echo '<div class="pp-attr-f"><label class="pp-chk"><input type="checkbox" name="attr[' . esc_attr( $i ) . '][var]" value="1"' . checked( $a ? $a['var'] : true, true, false ) . '> برای ساخت تنوع‌ها (سایز/رنگ قابل انتخاب)</label><label class="pp-chk"><input type="checkbox" name="attr[' . esc_attr( $i ) . '][vis]" value="1"' . checked( $a ? $a['vis'] : true, true, false ) . '> نمایش در مشخصات محصول</label></div></div>';
	}
	private static function var_row( $k, $v, $vattrs ) {
		$vals = $v ? $v->get_attributes() : array();
		$img  = $v ? (int) $v->get_image_id() : 0;
		$n    = 'var[' . esc_attr( $k ) . ']';
		echo '<tr class="pp-vrow' . ( $v && 'private' === $v->get_status() ? ' off' : '' ) . '"><td class="pp-vimg-c"><input type="hidden" name="' . $n . '[id]" value="' . (int) ( $v ? $v->get_id() : 0 ) . '"><input type="hidden" name="' . $n . '[image]" value="' . (int) $img . '"><button type="button" class="pp-vimg" title="تصویر این تنوع">' . ( $img ? wp_get_attachment_image( $img, array( 48, 48 ) ) : '<span>+🖼</span>' ) . '</button>'; // phpcs:ignore
		do_action( 'pp_var_row_image', $n, $v ); /* v1.3.1: تصاویر بیشتر هر تنوع (از قالب) */
		echo '</td>';
		echo '<td class="pp-vattrs" data-l="حالت">';
		foreach ( $vattrs as $a ) {
			$cur = isset( $vals[ $a['key'] ] ) ? $vals[ $a['key'] ] : '';
			echo '<select name="' . $n . '[a][' . esc_attr( $a['key'] ) . ']" aria-label="' . esc_attr( $a['label'] ) . '"><option value="">هر ' . esc_html( $a['label'] ) . '</option>'; // phpcs:ignore
			foreach ( $a['opts'] as $val => $lab ) { echo '<option value="' . esc_attr( $val ) . '"' . selected( (string) $cur, (string) $val, false ) . '>' . esc_html( $lab ) . '</option>'; }
			echo '</select>';
		}
		echo '</td>';
		$ms = $v ? $v->get_manage_stock() : false;
		echo '<td data-l="قیمت اصلی"><input type="text" inputmode="numeric" dir="ltr" class="pp-in" name="' . $n . '[regular]" value="' . esc_attr( $v ? $v->get_regular_price() : '' ) . '"></td>'; // phpcs:ignore
		echo '<td data-l="قیمت ویژه"><input type="text" inputmode="numeric" dir="ltr" class="pp-in" name="' . $n . '[sale]" value="' . esc_attr( $v ? $v->get_sale_price() : '' ) . '" placeholder="—"></td>'; // phpcs:ignore
		echo '<td data-l="موجودی"><input type="number" step="1" dir="ltr" class="pp-in qty" name="' . $n . '[stock]" value="' . esc_attr( $ms ? (int) $v->get_stock_quantity() : '' ) . '" placeholder="∞" title="خالی = بدون شمارش موجودی"><select class="pp-in pp-vss" name="' . $n . '[ss]"' . ( $ms ? ' hidden' : '' ) . '>'; // phpcs:ignore
		foreach ( array( 'instock' => 'موجود', 'outofstock' => 'ناموجود', 'onbackorder' => 'پیش‌فروش' ) as $sk => $sl ) { echo '<option value="' . esc_attr( $sk ) . '"' . selected( $v ? $v->get_stock_status() : 'instock', $sk, false ) . '>' . esc_html( $sl ) . '</option>'; }
		echo '</select></td>';
		echo '<td data-l="کد (SKU)"><input type="text" dir="ltr" class="pp-in" name="' . $n . '[sku]" value="' . esc_attr( $v ? $v->get_sku( 'edit' ) : '' ) . '"></td>'; // phpcs:ignore
		echo '<td class="pp-act"><label class="pp-chk" title="فعال"><input type="checkbox" name="' . $n . '[on]" value="1"' . checked( $v ? 'private' !== $v->get_status() : true, true, false ) . '> فعال</label><label class="pp-chk pp-del" title="حذف این تنوع"><input type="checkbox" name="' . $n . '[del]" value="1"> حذف</label></td></tr>'; // phpcs:ignore
	}
	private static function page_edit() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
		$p  = $id ? wc_get_product( $id ) : null;
		if ( $id && ( ! $p || ! current_user_can( 'edit_product', $id ) ) ) { echo '<p class="pp-empty">محصول پیدا نشد یا اجازه‌ی ویرایش آن را ندارید.</p>'; return; }
		if ( ! $id && ! current_user_can( 'edit_products' ) ) { echo '<p class="pp-empty">اجازه‌ی افزودن محصول ندارید.</p>'; return; }
		if ( $p && ! $p->is_type( 'simple' ) && ! $p->is_type( 'variable' ) ) {
			echo '<section class="pp-box"><p>این محصول از نوع «' . esc_html( $p->get_type() ) . '» است و در <a href="' . esc_url( get_edit_post_link( $id, 'raw' ) ) . '">ویرایشگر کامل ووکامرس</a> ویرایش می‌شود.</p></section>';
			return;
		}
		$type    = $p && $p->is_type( 'variable' ) ? 'variable' : ( isset( $_GET['type'] ) && 'variable' === $_GET['type'] ? 'variable' : 'simple' ); // phpcs:ignore
		$v       = function ( $m, $d = '' ) use ( $p ) { return $p ? $p->$m( 'edit' ) : $d; };
		$cats    = $p ? $p->get_category_ids() : array();
		$img     = $p ? (int) $p->get_image_id() : 0;
		$gallery = $p ? array_map( 'intval', $p->get_gallery_image_ids() ) : array();
		$status  = $p ? get_post_status( $id ) : 'publish';
		$taxes   = self::attr_taxonomies();
		$attrs   = self::product_attrs( $p );
		$tags    = $p ? wp_get_post_terms( $id, 'product_tag', array( 'fields' => 'names' ) ) : array();

		echo '<div class="pp-head"><h1>' . ( $p ? 'ویرایش: ' . esc_html( $p->get_name() ) : 'افزودن محصول جدید' ) . '</h1>';
		if ( $p ) { echo '<div class="pp-head-x"><a class="pp-btn ghost" href="' . esc_url( get_permalink( $id ) ) . '" target="_blank" rel="noopener">مشاهده ↗</a><a class="pp-btn ghost" href="' . esc_url( get_edit_post_link( $id, 'raw' ) ) . '">ویرایشگر کامل</a></div>'; }
		echo '</div>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pp-edit" data-type="' . esc_attr( $type ) . '"><input type="hidden" name="action" value="pp_save_product"><input type="hidden" name="id" value="' . (int) $id . '">';
		wp_nonce_field( 'pp_save_product_' . $id, '_ppn', false );

		echo '<div class="pp-types" role="radiogroup" aria-label="نوع محصول">';
		foreach ( array( 'simple' => array( 'محصول ساده', 'یک قیمت و یک موجودی' ), 'variable' => array( 'محصول متغیر', 'چند حالت مثل سایز و رنگ، هر کدام با قیمت و موجودی خودش' ) ) as $tk => $tl ) {
			echo '<label class="pp-type"><input type="radio" name="ptype" value="' . esc_attr( $tk ) . '"' . checked( $type, $tk, false ) . '><b>' . esc_html( $tl[0] ) . '</b><small>' . esc_html( $tl[1] ) . '</small></label>';
		}
		echo '</div>';

		echo '<div class="pp-grid2 edit"><div>';
		echo '<section class="pp-box pp-form"><label>نام محصول *<input type="text" name="name" required maxlength="200" value="' . esc_attr( $v( 'get_name' ) ) . '"></label>';
		echo '<label>توضیح کوتاه (کنار عکس محصول)<textarea name="short" rows="3">' . esc_textarea( $v( 'get_short_description' ) ) . '</textarea></label>';
		echo '<label>توضیحات کامل<textarea name="desc" rows="7">' . esc_textarea( $v( 'get_description' ) ) . '</textarea></label></section>';

		/* قیمت و موجودی */
		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>قیمت و موجودی</h2></div>';
		echo '<p class="pp-note pp-only-variable">قیمت و موجودی هر حالت در بخش «تنوع‌ها» پایین همین صفحه تعیین می‌شود.</p>';
		echo '<div class="pp-row3 pp-only-simple">';
		echo '<label>قیمت اصلی (' . esc_html( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ) . ')<input type="text" inputmode="numeric" name="regular" dir="ltr" value="' . esc_attr( 'simple' === $type ? $v( 'get_regular_price' ) : '' ) . '"></label>';
		echo '<label>قیمت ویژه (اختیاری)<input type="text" inputmode="numeric" name="sale" dir="ltr" value="' . esc_attr( 'simple' === $type ? $v( 'get_sale_price' ) : '' ) . '"></label>';
		echo '<label>کد محصول / SKU<input type="text" name="sku" dir="ltr" value="' . esc_attr( $v( 'get_sku' ) ) . '"></label></div>';
		$ms = $p ? $p->get_manage_stock() : true;
		echo '<div class="pp-only-simple"><label class="pp-chk"><input type="checkbox" name="manage" value="1"' . checked( $ms, true, false ) . ' data-toggle=".pp-stock-q,.pp-stock-s"> مدیریت تعداد موجودی</label><div class="pp-row3">';
		echo '<label class="pp-stock-q"' . ( $ms ? '' : ' hidden' ) . '>تعداد موجود<input type="number" name="stock" step="1" dir="ltr" value="' . esc_attr( $p ? (int) $p->get_stock_quantity() : 1 ) . '"></label>';
		echo '<label class="pp-stock-s"' . ( $ms ? ' hidden' : '' ) . '>وضعیت موجودی<select name="stock_status">';
		foreach ( array( 'instock' => 'موجود', 'outofstock' => 'ناموجود', 'onbackorder' => 'پیش‌فروش' ) as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $p ? $p->get_stock_status() : 'instock', $k, false ) . '>' . esc_html( $l ) . '</option>'; }
		echo '</select></label></div></div>';
		echo '<div class="pp-row3"><label>وزن (' . esc_html( get_option( 'woocommerce_weight_unit', 'kg' ) ) . ')<input type="text" inputmode="decimal" name="weight" dir="ltr" value="' . esc_attr( $v( 'get_weight' ) ) . '"></label>';
		echo '<label class="pp-only-variable">کد محصول اصلی / SKU<input type="text" name="sku_v" dir="ltr" value="' . esc_attr( $v( 'get_sku' ) ) . '"></label></div></section>';

		/* ویژگی‌ها */
		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>ویژگی‌ها (سایز، رنگ، جنس و…)</h2></div><p class="pp-note">ویژگی‌های سراسری فروشگاه (مثل رنگ) را از فهرست انتخاب کنید یا یک ویژگی دلخواه بسازید.</p><div class="pp-attrs">';
		$i = 0;
		foreach ( $attrs as $a ) { self::attr_row( $i++, $a, $taxes ); }
		if ( ! $attrs ) { self::attr_row( $i++, null, $taxes ); }
		echo '</div><template id="pp-attr-tpl">';
		self::attr_row( '__I__', null, $taxes );
		echo '</template><button type="button" class="pp-btn ghost sm pp-attr-add" data-next="' . (int) $i . '">+ افزودن ویژگی</button></section>';

		/* تنوع‌ها */
		$vattrs = array_values( array_filter( $attrs, function ( $a ) { return $a['var']; } ) );
		echo '<section class="pp-box pp-form pp-only-variable"><div class="pp-box-h"><h2>تنوع‌ها (حالت‌های قابل خرید)</h2></div>';
		echo '<div class="pp-gen"><label class="pp-chk"><input type="checkbox" name="gen_all" value="1"' . checked( ! $p || ! $p->is_type( 'variable' ) || ! $p->get_children(), true, false ) . '> با ذخیره، <b>همه‌ی ترکیب‌های</b> ویژگی‌ها خودکار ساخته شوند (فقط ترکیب‌هایی که هنوز نیستند)</label>';
		echo '<div class="pp-row3"><label>قیمت اصلی برای همه<input type="text" inputmode="numeric" dir="ltr" name="bulk_regular" class="pp-bulk-in" data-f="regular"></label><label>قیمت ویژه برای همه<input type="text" inputmode="numeric" dir="ltr" name="bulk_sale" class="pp-bulk-in" data-f="sale"></label><label>موجودی برای همه<input type="number" step="1" dir="ltr" name="bulk_stock" class="pp-bulk-in" data-f="stock"></label></div>';
		echo '<button type="button" class="pp-btn ghost sm pp-bulk-apply">اعمال روی همه‌ی ردیف‌های پایین</button> <small class="pp-inline">این مقادیر روی تنوع‌های تازه‌ساخته‌شده هم اعمال می‌شود.</small></div>';
		if ( $p && $p->is_type( 'variable' ) && $vattrs ) {
			$kids = array_slice( $p->get_children(), 0, 150 );
			echo '<div class="pp-table-wrap"><table class="pp-table pp-vars"><thead><tr><th></th><th>حالت</th><th>قیمت اصلی</th><th>قیمت ویژه</th><th>موجودی</th><th>SKU</th><th></th></tr></thead><tbody>';
			$k = 0;
			foreach ( $kids as $vid ) { $vv = wc_get_product( $vid ); if ( $vv ) { self::var_row( $k++, $vv, $vattrs ); } }
			echo '</tbody></table></div><template id="pp-var-tpl">';
			self::var_row( '__K__', null, $vattrs );
			echo '</template><button type="button" class="pp-btn ghost sm pp-var-add" data-next="' . (int) $k . '">+ افزودن یک تنوع</button>';
			if ( ! $kids ) { echo '<p class="pp-note">هنوز تنوعی ساخته نشده؛ گزینه‌ی «همه‌ی ترکیب‌ها» را روشن کنید و ذخیره بزنید.</p>'; }
		} else {
			echo '<p class="pp-note">ویژگی‌ها را وارد کنید (مثلاً سایز: S | M | L) و ذخیره بزنید؛ تنوع‌ها خودکار ساخته می‌شوند و بعد قیمت و موجودی و تصویر هر کدام را همین‌جا تنظیم می‌کنید.</p>';
		}
		echo '</section></div>';

		/* ستون کناری */
		echo '<aside><section class="pp-box pp-form"><div class="pp-box-h"><h2>انتشار</h2></div><label>وضعیت<select name="status">';
		$sts = array( 'publish' => 'منتشر شده', 'draft' => 'پیش‌نویس' );
		if ( ! current_user_can( 'publish_products' ) ) { $sts = array( 'pending' => 'ارسال برای بررسی', 'draft' => 'پیش‌نویس' ); }
		foreach ( $sts as $k => $l ) { echo '<option value="' . esc_attr( $k ) . '"' . selected( $status, $k, false ) . '>' . esc_html( $l ) . '</option>'; }
		echo '</select></label><label class="pp-chk"><input type="checkbox" name="featured" value="1"' . checked( $p ? $p->get_featured() : false, true, false ) . '> محصول ویژه ⭐</label><button class="pp-btn block">' . ( $p ? 'ذخیره‌ی تغییرات' : 'افزودن محصول' ) . '</button></section>';

		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>تصویر اصلی</h2></div><div class="pp-media" data-multiple="0"><input type="hidden" name="image" value="' . (int) $img . '"><div class="pp-media-list">' . ( $img ? '<figure data-id="' . (int) $img . '">' . wp_get_attachment_image( $img, 'thumbnail' ) . '<button type="button" class="pp-media-x" aria-label="حذف">×</button></figure>' : '' ) . '</div><button type="button" class="pp-btn ghost sm pp-media-add">انتخاب تصویر</button></div></section>';
		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>گالری تصاویر</h2></div><div class="pp-media" data-multiple="1"><input type="hidden" name="gallery" value="' . esc_attr( implode( ',', $gallery ) ) . '"><div class="pp-media-list">';
		foreach ( $gallery as $gid ) { echo '<figure data-id="' . (int) $gid . '">' . wp_get_attachment_image( $gid, 'thumbnail' ) . '<button type="button" class="pp-media-x" aria-label="حذف">×</button></figure>'; }
		echo '</div><button type="button" class="pp-btn ghost sm pp-media-add">افزودن تصاویر</button></div></section>';
		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>دسته‌بندی</h2></div><div class="pp-cats">';
		self::cat_checks( 0, $cats, 0 );
		echo '</div></section>';
		echo '<section class="pp-box pp-form"><div class="pp-box-h"><h2>برچسب‌ها</h2></div><label>با ویرگول جدا کنید<input type="text" name="tags" value="' . esc_attr( implode( '، ', (array) $tags ) ) . '" placeholder="تابستانه، نخی، پرفروش"></label></section>';
		do_action( 'pp_edit_side_boxes', $p ); /* v1.3: جعبه‌های اضافه (مثلاً ویژگی‌ها و مشاور از قالب) */
		echo '</aside></div></form>';
	}
	private static function cat_checks( $parent, $sel, $depth ) {
		if ( $depth > 4 ) { return; }
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => $parent ) );
		if ( is_wp_error( $terms ) ) { return; }
		foreach ( $terms as $t ) {
			echo '<label class="pp-chk" style="padding-inline-start:' . (int) ( $depth * 18 ) . 'px"><input type="checkbox" name="cats[]" value="' . (int) $t->term_id . '"' . checked( in_array( (int) $t->term_id, $sel, true ), true, false ) . '> ' . esc_html( $t->name ) . '</label>';
			self::cat_checks( $t->term_id, $sel, $depth + 1 );
		}
	}
	private static function split_vals( $s ) {
		return array_values( array_unique( array_filter( array_map( 'trim', preg_split( '/[|،,]+/u', (string) $s ) ), 'strlen' ) ) );
	}
	private static function price_pair( $reg, $sale ) {
		$reg  = null === $reg ? '' : (string) $reg;
		$sale = null === $sale ? '' : (string) $sale;
		if ( '' !== $sale && '' !== $reg && (float) $sale >= (float) $reg ) { return array( $reg, '', true ); }
		return array( $reg, $sale, false );
	}
	public static function act_save_product() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
		if ( ! current_user_can( $id ? 'edit_product' : 'edit_products', $id ) ) { wp_die( 'دسترسی ندارید.', 403 ); }
		check_admin_referer( 'pp_save_product_' . $id, '_ppn' );
		$type = isset( $_POST['ptype'] ) && 'variable' === $_POST['ptype'] ? 'variable' : 'simple'; // phpcs:ignore
		if ( $id ) {
			$old = wc_get_product( $id );
			if ( ! $old || ! ( $old->is_type( 'simple' ) || $old->is_type( 'variable' ) ) ) { self::back( 'err', self::url( 'products' ) ); }
			$cls = WC_Product_Factory::get_product_classname( $id, $type );
			$p   = $old->get_type() === $type ? $old : new $cls( $id );
		} else {
			$p = 'variable' === $type ? new WC_Product_Variable() : new WC_Product_Simple();
		}
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : ''; // phpcs:ignore
		if ( '' === trim( $name ) ) { self::back( 'name', self::url( 'edit', $id ? array( 'id' => $id ) : array() ) ); }
		$msg = 'saved';

		$p->set_name( $name );
		$p->set_short_description( wp_kses_post( wp_unslash( isset( $_POST['short'] ) ? $_POST['short'] : '' ) ) ); // phpcs:ignore
		$p->set_description( wp_kses_post( wp_unslash( isset( $_POST['desc'] ) ? $_POST['desc'] : '' ) ) ); // phpcs:ignore

		$sku = wc_clean( wp_unslash( isset( $_POST[ 'variable' === $type ? 'sku_v' : 'sku' ] ) ? $_POST[ 'variable' === $type ? 'sku_v' : 'sku' ] : '' ) ); // phpcs:ignore
		if ( $sku !== $p->get_sku( 'edit' ) ) {
			if ( '' === $sku || wc_product_has_unique_sku( $id, $sku ) ) { try { $p->set_sku( $sku ); } catch ( Exception $e ) { $msg = 'sku'; } }
			else { $msg = 'sku'; }
		}
		if ( 'simple' === $type ) {
			list( $r, $s, $bad ) = self::price_pair( self::num_in( 'regular' ), self::num_in( 'sale' ) );
			if ( $bad ) { $msg = 'sale'; }
			$p->set_regular_price( $r );
			$p->set_sale_price( $s );
			$manage = ! empty( $_POST['manage'] ); // phpcs:ignore
			$p->set_manage_stock( $manage );
			if ( $manage ) { $q = self::num_in( 'stock' ); $p->set_stock_quantity( '' === $q || null === $q ? 0 : (int) $q ); }
			else {
				$ss = isset( $_POST['stock_status'] ) ? sanitize_key( $_POST['stock_status'] ) : 'instock'; // phpcs:ignore
				$p->set_stock_status( in_array( $ss, array( 'instock', 'outofstock', 'onbackorder' ), true ) ? $ss : 'instock' );
			}
		} else {
			$p->set_manage_stock( false );
		}
		$w = self::num_in( 'weight' );
		if ( null !== $w ) { $p->set_weight( $w ); }
		$p->set_featured( ! empty( $_POST['featured'] ) ); // phpcs:ignore

		$st = isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'draft'; // phpcs:ignore
		if ( 'publish' === $st && ! current_user_can( 'publish_products' ) ) { $st = 'pending'; }
		$p->set_status( in_array( $st, array( 'publish', 'draft', 'pending' ), true ) ? $st : 'draft' );

		$img = isset( $_POST['image'] ) ? absint( $_POST['image'] ) : 0; // phpcs:ignore
		$p->set_image_id( $img && wp_attachment_is_image( $img ) ? $img : '' );
		$gal = isset( $_POST['gallery'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['gallery'] ) ) ) ) ) : array(); // phpcs:ignore
		$p->set_gallery_image_ids( array_values( array_filter( array_slice( $gal, 0, 30 ), 'wp_attachment_is_image' ) ) );
		$cats  = isset( $_POST['cats'] ) ? array_filter( array_map( 'absint', (array) $_POST['cats'] ) ) : array(); // phpcs:ignore
		$valid = array();
		foreach ( $cats as $c ) { if ( term_exists( $c, 'product_cat' ) ) { $valid[] = $c; } }
		$p->set_category_ids( $valid );

		/* ویژگی‌ها */
		$attrs   = array();
		$can_new = current_user_can( 'manage_product_terms' ) || current_user_can( 'edit_products' );
		$pos     = 0;
		foreach ( isset( $_POST['attr'] ) ? (array) wp_unslash( $_POST['attr'] ) : array() as $key => $row ) { // phpcs:ignore
			if ( '__I__' === (string) $key || ! is_array( $row ) ) { continue; }
			$tax  = isset( $row['tax'] ) ? sanitize_title( $row['tax'] ) : '';
			$vals = self::split_vals( isset( $row['values'] ) ? sanitize_text_field( $row['values'] ) : '' );
			$a    = new WC_Product_Attribute();
			if ( $tax && taxonomy_exists( $tax ) && 0 === strpos( $tax, 'pa_' ) ) {
				$ids = array_filter( array_map( 'absint', isset( $row['terms'] ) ? (array) $row['terms'] : array() ) );
				if ( $can_new ) {
					foreach ( $vals as $nv ) {
						$ex = term_exists( $nv, $tax );
						if ( ! $ex ) { $ex = wp_insert_term( $nv, $tax ); }
						if ( ! is_wp_error( $ex ) && ! empty( $ex['term_id'] ) ) { $ids[] = (int) $ex['term_id']; }
					}
				}
				$ids = array_values( array_unique( array_filter( $ids, function ( $t ) use ( $tax ) { return (bool) term_exists( (int) $t, $tax ); } ) ) );
				if ( ! $ids ) { continue; }
				$a->set_id( wc_attribute_taxonomy_id_by_name( $tax ) );
				$a->set_name( $tax );
				$a->set_options( $ids );
			} else {
				$nm = isset( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';
				if ( '' === trim( $nm ) || ! $vals ) { continue; }
				$a->set_id( 0 );
				$a->set_name( $nm );
				$a->set_options( $vals );
			}
			$a->set_position( $pos++ );
			$a->set_visible( ! empty( $row['vis'] ) );
			$a->set_variation( 'variable' === $type && ! empty( $row['var'] ) );
			$attrs[] = $a;
		}
		$p->set_attributes( $attrs );
		$new_id = $p->save();
		do_action( 'pp_save_product_after', $new_id );

		/* تنوع‌ها */
		if ( 'variable' === $type ) {
			$p      = wc_get_product( $new_id );
			$vattrs = array_values( array_filter( self::product_attrs( $p ), function ( $a ) { return $a['var']; } ) );
			$allowed = array();
			foreach ( $vattrs as $a ) { $allowed[ $a['key'] ] = array_map( 'strval', array_keys( $a['opts'] ) ); }
			$existing = array();
			$kids     = array_map( 'intval', $p->get_children() );
			foreach ( isset( $_POST['var'] ) ? (array) wp_unslash( $_POST['var'] ) : array() as $key => $row ) { // phpcs:ignore
				if ( '__K__' === (string) $key || ! is_array( $row ) ) { continue; }
				$vid = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
				if ( $vid && ! in_array( $vid, $kids, true ) ) { continue; }
				$vv = $vid ? wc_get_product( $vid ) : new WC_Product_Variation();
				if ( ! $vv ) { continue; }
				if ( ! empty( $row['del'] ) ) { if ( $vid ) { $vv->delete( true ); } continue; }
				$va = array();
				foreach ( $allowed as $ak => $opts ) {
					$val = isset( $row['a'][ $ak ] ) ? (string) $row['a'][ $ak ] : '';
					$va[ $ak ] = in_array( $val, $opts, true ) ? $val : '';
				}
				$vv->set_parent_id( $new_id );
				$vv->set_attributes( $va );
				list( $r, $s, $bad ) = self::price_pair( self::num_in( 'regular', $row ), self::num_in( 'sale', $row ) );
				if ( $bad ) { $msg = 'sale'; }
				$vv->set_regular_price( $r );
				$vv->set_sale_price( $s );
				$q = self::num_in( 'stock', $row );
				if ( '' !== (string) $q && null !== $q ) { $vv->set_manage_stock( true ); $vv->set_stock_quantity( (int) $q ); }
				else {
					$vv->set_manage_stock( false );
					$ss = isset( $row['ss'] ) ? sanitize_key( $row['ss'] ) : 'instock';
					$vv->set_stock_status( in_array( $ss, array( 'instock', 'outofstock', 'onbackorder' ), true ) ? $ss : 'instock' );
				}
				$vsku = isset( $row['sku'] ) ? wc_clean( $row['sku'] ) : '';
				if ( $vsku !== $vv->get_sku( 'edit' ) ) {
					if ( '' === $vsku || wc_product_has_unique_sku( $vid, $vsku ) ) { try { $vv->set_sku( $vsku ); } catch ( Exception $e ) { $msg = 'sku'; } }
					else { $msg = 'sku'; }
				}
				$vimg = isset( $row['image'] ) ? absint( $row['image'] ) : 0;
				$vv->set_image_id( $vimg && wp_attachment_is_image( $vimg ) ? $vimg : '' );
				$vv->set_status( empty( $row['on'] ) ? 'private' : 'publish' );
				$vv->save();
				do_action( 'pp_save_variation_after', $vv->get_id(), $row ); /* v1.3.1 */
				$existing[] = wp_json_encode( $va );
			}
			foreach ( $kids as $kid ) { /* حالت‌هایی که در فرم نبودند (مثلاً بیش از ۱۵۰) */
				$kv = wc_get_product( $kid );
				if ( $kv && $kv->get_parent_id() === $new_id ) { $existing[] = wp_json_encode( array_intersect_key( array_merge( array_fill_keys( array_keys( $allowed ), '' ), $kv->get_attributes() ), $allowed ) ); }
			}
			if ( ! empty( $_POST['gen_all'] ) && $allowed ) { // phpcs:ignore
				$combos = array( array() );
				foreach ( $allowed as $ak => $opts ) {
					$next = array();
					foreach ( $combos as $c ) { foreach ( $opts as $o ) { $next[] = $c + array( $ak => $o ); if ( count( $next ) > 200 ) { break 2; } } }
					$combos = $next;
				}
				$br = self::num_in( 'bulk_regular' ); $bs = self::num_in( 'bulk_sale' ); $bq = self::num_in( 'bulk_stock' );
				list( $br, $bs ) = self::price_pair( $br, $bs );
				$made = 0;
				foreach ( $combos as $c ) {
					if ( count( $c ) !== count( $allowed ) || in_array( wp_json_encode( $c ), $existing, true ) ) { continue; }
					if ( ++$made > 100 ) { break; }
					$nv = new WC_Product_Variation();
					$nv->set_parent_id( $new_id );
					$nv->set_attributes( $c );
					$nv->set_regular_price( $br );
					$nv->set_sale_price( $bs );
					if ( '' !== (string) $bq && null !== $bq ) { $nv->set_manage_stock( true ); $nv->set_stock_quantity( (int) $bq ); }
					$nv->set_status( 'publish' );
					$nv->save();
				}
			}
			WC_Product_Variable::sync( $new_id );
			if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients( $new_id ); }
		}

		/* برچسب‌ها */
		if ( isset( $_POST['tags'] ) ) { // phpcs:ignore
			wp_set_post_terms( $new_id, self::split_vals( sanitize_text_field( wp_unslash( $_POST['tags'] ) ) ), 'product_tag', false ); // phpcs:ignore
		}
		self::flush_stats();
		self::back( $msg, self::url( 'edit', array( 'id' => $new_id ) ) );
	}

	/* ---------- مشتریان ---------- */
	private static function page_customers() {
		$s    = isset( $_GET['s'] ) ? trim( self::en( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) ) : ''; // phpcs:ignore
		$page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore
		$per  = (int) self::opt( 'per_page' );
		$args = array( 'role__in' => array( 'customer' ), 'number' => $per, 'paged' => $page, 'orderby' => 'registered', 'order' => 'DESC', 'count_total' => true );
		if ( '' !== $s ) {
			if ( preg_match( '/^[0-9+ ]{4,}$/', $s ) ) {
				$args['meta_query'] = array( array( 'key' => 'billing_phone', 'value' => str_replace( ' ', '', $s ), 'compare' => 'LIKE' ) ); // phpcs:ignore
			} else {
				$args['search']         = '*' . $s . '*';
				$args['search_columns'] = array( 'user_login', 'user_email', 'display_name', 'user_nicename' );
			}
		}
		$q = new WP_User_Query( $args );
		echo '<div class="pp-head"><h1>مشتریان <small>' . esc_html( self::fa( $q->get_total() ) ) . ' نفر</small></h1><form class="pp-search" method="get"><input type="hidden" name="page" value="' . esc_attr( PP_SLUG ) . '"><input type="hidden" name="tab" value="customers"><input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="نام، ایمیل یا موبایل…"><button class="pp-btn">جستجو</button></form></div>';
		$users = $q->get_results();
		if ( ! $users ) { echo '<section class="pp-box"><p class="pp-empty">مشتری‌ای پیدا نشد.</p></section>'; return; }
		echo '<section class="pp-box flush"><div class="pp-table-wrap"><table class="pp-table"><thead><tr><th>مشتری</th><th>موبایل</th><th>شهر</th><th>عضویت</th><th>سفارش‌ها</th><th>جمع خرید</th><th></th></tr></thead><tbody>';
		foreach ( $users as $u ) {
			$name  = trim( get_user_meta( $u->ID, 'billing_first_name', true ) . ' ' . get_user_meta( $u->ID, 'billing_last_name', true ) );
			$phone = get_user_meta( $u->ID, 'billing_phone', true );
			$cnt   = (int) wc_get_customer_order_count( $u->ID );
			echo '<tr><td data-l="مشتری"><b>' . esc_html( $name ? $name : $u->display_name ) . '</b><small class="pp-ltr">' . esc_html( $u->user_email ) . '</small></td>';
			echo '<td data-l="موبایل">' . ( $phone ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( self::fa( $phone ) ) . '</a>' : '—' ) . '</td>';
			echo '<td data-l="شهر">' . esc_html( get_user_meta( $u->ID, 'billing_city', true ) ? get_user_meta( $u->ID, 'billing_city', true ) : '—' ) . '</td>';
			echo '<td data-l="عضویت">' . esc_html( self::gmt_jdate( $u->user_registered ) ) . '</td>';
			echo '<td data-l="سفارش‌ها"><b>' . esc_html( self::fa( $cnt ) ) . '</b></td>';
			echo '<td data-l="جمع خرید">' . esc_html( self::money( wc_get_customer_total_spent( $u->ID ) ) ) . '</td>';
			echo '<td class="pp-act">' . ( $cnt ? '<a class="pp-btn sm ghost" href="' . esc_url( self::url( 'orders', array( 'customer' => $u->ID ) ) ) . '">سفارش‌ها</a>' : '' ) . '</td></tr>';
		}
		echo '</tbody></table></div></section>';
		self::pager( $page, (int) ceil( $q->get_total() / max( 1, $per ) ), array_filter( array( 's' => $s ) ), 'customers' );
	}

	/* ---------- تنظیمات ---------- */
	private static function page_settings() {
		echo '<div class="pp-head"><h1>تنظیمات پنل</h1></div><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pp-box pp-form narrow"><input type="hidden" name="action" value="pp_save_settings">';
		wp_nonce_field( 'pp_save_settings', '_ppn', false );
		$c = function ( $k, $l, $d = '' ) { echo '<label class="pp-chk"><input type="checkbox" name="' . esc_attr( $k ) . '" value="1"' . checked( (int) self::opt( $k ), 1, false ) . '> ' . esc_html( $l ) . ( $d ? '<small>' . esc_html( $d ) . '</small>' : '' ) . '</label>'; };
		$c( 'redirect', 'مدیر فروشگاه بعد از ورود مستقیم به این پنل بیاید', 'برای کاربرانی با نقش «مدیر فروشگاه». مدیر کل سایت همان پیشخوان معمول را می‌بیند.' );
		$c( 'simplify', 'منوهای پیچیده‌ی پیشخوان برای مدیر فروشگاه پنهان شود', 'مدیر فروشگاه از نوار بالا می‌تواند «نمای کامل پیشخوان» را روشن کند.' );
		$c( 'shipped', 'وضعیت «ارسال شده» به سفارش‌ها اضافه شود', 'مراحل: در حال آماده‌سازی ← ارسال شده ← تحویل شده' );
		echo '<div class="pp-row3"><label>آستانه‌ی «موجودی کم»<input type="number" min="0" name="low" value="' . esc_attr( (int) self::opt( 'low' ) ) . '"></label><label>تعداد ردیف در هر صفحه<input type="number" min="5" max="100" name="per_page" value="' . esc_attr( (int) self::opt( 'per_page' ) ) . '"></label></div>';
		echo '<label>روش‌های ارسال (هر خط یکی)<textarea name="carriers" rows="5">' . esc_textarea( (string) self::opt( 'carriers' ) ) . '</textarea></label>';
		echo '<label>لینک پیگیری پست (به‌جای کد بنویسید {code})<input type="url" name="track_url" dir="ltr" value="' . esc_attr( (string) self::opt( 'track_url' ) ) . '"></label>';
		echo '<button class="pp-btn">ذخیره‌ی تنظیمات</button></form>';
	}
	public static function act_save_settings() {
		self::guard( 'pp_save_settings', 'manage_options' );
		$o = array(
			'redirect'  => empty( $_POST['redirect'] ) ? 0 : 1, // phpcs:ignore
			'simplify'  => empty( $_POST['simplify'] ) ? 0 : 1, // phpcs:ignore
			'shipped'   => empty( $_POST['shipped'] ) ? 0 : 1, // phpcs:ignore
			'low'       => min( 10000, absint( isset( $_POST['low'] ) ? $_POST['low'] : 2 ) ), // phpcs:ignore
			'per_page'  => min( 100, max( 5, absint( isset( $_POST['per_page'] ) ? $_POST['per_page'] : 20 ) ) ), // phpcs:ignore
			'carriers'  => sanitize_textarea_field( wp_unslash( isset( $_POST['carriers'] ) ? $_POST['carriers'] : '' ) ), // phpcs:ignore
			'track_url' => esc_url_raw( wp_unslash( isset( $_POST['track_url'] ) ? $_POST['track_url'] : '' ) ), // phpcs:ignore
		);
		update_option( 'pp_settings', $o, false );
		self::flush_stats();
		wp_safe_redirect( self::url( 'settings', array( 'pp_msg' => 'settings' ) ) );
		exit;
	}
	public static function act_toggle_view() {
		if ( ! current_user_can( self::CAP ) ) { wp_die( 'دسترسی ندارید.', 403 ); }
		check_admin_referer( 'pp_toggle_view' );
		$uid = get_current_user_id();
		if ( get_user_meta( $uid, 'pp_full_view', true ) ) { delete_user_meta( $uid, 'pp_full_view' ); wp_safe_redirect( self::url() ); }
		else { update_user_meta( $uid, 'pp_full_view', 1 ); wp_safe_redirect( admin_url( 'index.php' ) ); }
		exit;
	}
}

add_action( 'plugins_loaded', array( 'Peecha_Panel', 'init' ) );
