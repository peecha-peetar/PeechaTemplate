<?php
/**
 * Plugin Name:       رستوران پیچا
 * Description:       منوی غذا با مواد حساسیت‌زا و پیشنهاد روز، سفارش سالن یا بیرون‌بر، رزرو میز بدون تداخل ساعت، و برگه قابل چاپ برای QR.
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Peecha
 * Text Domain:       peecha-restaurant
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PEECHA_REST_VER', '1.1.0' );
define( 'PEECHA_REST_FILE', __FILE__ );
define( 'PEECHA_REST_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/front.php';

final class Peecha_Restaurant {

	const OPT = 'peecha_rest_opts';

	public static function init() {
		register_activation_hook( PEECHA_REST_FILE, array( __CLASS__, 'activate' ) );
		add_action( 'init', array( __CLASS__, 'types' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'dish_box' ) );
		add_action( 'save_post_peecha_dish', array( __CLASS__, 'save_dish' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_peecha_rest_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_peecha_rest_status', array( __CLASS__, 'save_status' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_filter( 'manage_peecha_dish_posts_columns', array( __CLASS__, 'dish_cols' ) );
		add_action( 'manage_peecha_dish_posts_custom_column', array( __CLASS__, 'dish_col' ), 10, 2 );
	}

	public static function allergens() {
		return array(
			'gluten'   => 'گلوتن',
			'dairy'    => 'لبنیات',
			'egg'      => 'تخم‌مرغ',
			'nuts'     => 'مغزها',
			'peanut'   => 'بادام‌زمینی',
			'soy'      => 'سویا',
			'fish'     => 'ماهی',
			'shell'    => 'صدف',
			'sesame'   => 'کنجد',
			'mustard'  => 'خردل',
		);
	}

	public static function weekdays() {
		return array( 6 => 'شنبه', 0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه', 4 => 'پنجشنبه', 5 => 'جمعه' );
	}

	public static function defaults() {
		$days = array();
		foreach ( self::weekdays() as $n => $label ) {
			$days[ $n ] = array( 'on' => 1, 'open' => '12:00', 'close' => '23:00' );
		}
		return array(
			'email'     => get_option( 'admin_email' ),
			'slot'      => 90,
			'step'      => 30,
			'party'     => 10,
			'days'      => $days,
			'branches'  => array(
				array( 'name' => 'شعبه مرکزی', 'phone' => '', 'address' => '', 'tables' => "میز ۱|2\nمیز ۲|4\nمیز ۳|4\nمیز ۴|6" ),
			),
		);
	}

	public static function opt() {
		$base  = self::defaults();
		$saved = get_option( self::OPT, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$opt   = $base;
		foreach ( array( 'email', 'slot', 'step', 'party', 'branches' ) as $k ) {
			if ( isset( $saved[ $k ] ) && '' !== $saved[ $k ] && array() !== $saved[ $k ] ) { $opt[ $k ] = $saved[ $k ]; }
		}
		if ( empty( $opt['branches'] ) || ! is_array( $opt['branches'] ) ) { $opt['branches'] = $base['branches']; }
		foreach ( $base['days'] as $n => $d ) {
			if ( isset( $saved['days'][ $n ] ) && is_array( $saved['days'][ $n ] ) ) {
				$opt['days'][ $n ] = array_merge( $d, $saved['days'][ $n ] );
			}
		}
		return $opt;
	}

	public static function branches() {
		$out = array();
		foreach ( self::opt()['branches'] as $i => $b ) {
			if ( ! is_array( $b ) || '' === trim( (string) ( $b['name'] ?? '' ) ) ) { continue; }
			$tables = array();
			foreach ( preg_split( '/\r\n|\r|\n/', (string) ( $b['tables'] ?? '' ) ) as $line ) {
				$line = trim( $line );
				if ( '' === $line ) { continue; }
				$bits = array_map( 'trim', explode( '|', $line ) );
				$seats = isset( $bits[1] ) ? absint( $bits[1] ) : 2;
				if ( $seats < 1 ) { $seats = 2; }
				$tables[] = array( 'name' => $bits[0], 'seats' => $seats );
			}
			if ( ! $tables ) { $tables[] = array( 'name' => 'میز ۱', 'seats' => 4 ); }
			$b['tables'] = $tables;
			$out[ $i ] = $b;
		}
		return $out;
	}

	public static function activate() {
		self::types();
		self::seed();
		flush_rewrite_rules();
	}

	public static function types() {
		register_taxonomy( 'peecha_menu_cat', array( 'peecha_dish' ), array(
			'labels'            => array( 'name' => 'دسته‌های منو', 'singular_name' => 'دسته منو', 'add_new_item' => 'دسته تازه' ),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'menu-cat' ),
		) );
		register_post_type( 'peecha_dish', array(
			'labels'       => array( 'name' => 'غذاها', 'singular_name' => 'غذا', 'add_new_item' => 'غذای تازه', 'edit_item' => 'ویرایش غذا' ),
			'public'       => true,
			'show_in_menu' => false,
			'menu_icon'    => 'dashicons-carrot',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'food' ),
		) );
		foreach ( array( 'peecha_booking' => 'رزرو', 'peecha_rorder' => 'سفارش غذا' ) as $type => $label ) {
			register_post_type( $type, array(
				'labels'              => array( 'name' => $label ),
				'public'              => false,
				'show_ui'             => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
			) );
		}
	}

	private static function page( $slug, $title, $shortcode ) {
		$found = get_page_by_path( $slug );
		if ( $found ) { return (int) $found->ID; }
		return (int) wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_title'   => $title,
			'post_content' => '[' . $shortcode . ']',
		) );
	}

	public static function seed() {
		$menu = self::page( 'restaurant-menu', 'منوی غذا', 'peecha_menu' );
		$book = self::page( 'restaurant-book', 'رزرو میز', 'peecha_reserve' );
		$ord  = self::page( 'restaurant-order', 'سفارش غذا', 'peecha_order' );
		update_option( 'peecha_rest_pages', array( 'menu' => $menu, 'book' => $book, 'order' => $ord ), false );
		if ( get_option( 'peecha_rest_seeded' ) ) { return; }
		$cats = array( 'main' => 'غذای اصلی', 'start' => 'پیش‌غذا', 'drink' => 'نوشیدنی' );
		$ids  = array();
		foreach ( $cats as $slug => $name ) {
			$term = wp_insert_term( $name, 'peecha_menu_cat', array( 'slug' => $slug ) );
			$ids[ $slug ] = is_wp_error( $term ) ? 0 : (int) $term['term_id'];
		}
		$samples = array(
			array( 'کباب کوبیده', 'main', 285000, 'گوشت گوسفندی، پیاز، کره', array( 'gluten' ), 1, 0 ),
			array( 'جوجه کباب', 'main', 265000, 'سینه مرغ، زعفران، روغن زیتون', array(), 0, 0 ),
			array( 'سالاد شیرازی', 'start', 65000, 'خیار، گوجه، پیاز، نعنا', array(), 0, 1 ),
			array( 'ماست موسیر', 'start', 45000, 'ماست، موسیر', array( 'dairy' ), 0, 0 ),
			array( 'دوغ محلی', 'drink', 35000, 'دوغ، نعنا', array( 'dairy' ), 0, 0 ),
		);
		foreach ( $samples as $i => $row ) {
			$pid = wp_insert_post( array(
				'post_type'    => 'peecha_dish',
				'post_status'  => 'publish',
				'post_title'   => $row[0],
				'menu_order'   => $i,
				'post_content' => $row[3],
			) );
			if ( ! $pid || is_wp_error( $pid ) ) { continue; }
			if ( ! empty( $ids[ $row[1] ] ) ) { wp_set_object_terms( $pid, array( (int) $ids[ $row[1] ] ), 'peecha_menu_cat' ); }
			update_post_meta( $pid, '_pr_price', $row[2] );
			update_post_meta( $pid, '_pr_ingredients', $row[3] );
			update_post_meta( $pid, '_pr_allergens', $row[4] );
			update_post_meta( $pid, '_pr_special', $row[5] );
			update_post_meta( $pid, '_pr_veg', $row[6] );
			update_post_meta( $pid, '_pr_available', 1 );
			update_post_meta( $pid, '_pr_spicy', 0 );
		}
		update_option( 'peecha_rest_seeded', 1, false );
		if ( false === get_option( self::OPT, false ) ) { update_option( self::OPT, self::defaults(), false ); }
	}

	public static function dish_box() {
		add_meta_box( 'peecha_dish_meta', 'جزئیات غذا', array( __CLASS__, 'dish_box_html' ), 'peecha_dish', 'normal', 'high' );
	}

	public static function dish_box_html( $post ) {
		wp_nonce_field( 'peecha_dish', 'peecha_dish_nonce' );
		$price = (int) get_post_meta( $post->ID, '_pr_price', true );
		$ing   = (string) get_post_meta( $post->ID, '_pr_ingredients', true );
		$alg   = (array) get_post_meta( $post->ID, '_pr_allergens', true );
		$av    = get_post_meta( $post->ID, '_pr_available', true );
		if ( '' === (string) $av ) { $av = 1; }
		echo '<p><label>قیمت (تومان)<br><input type="number" min="0" step="1000" name="_pr_price" value="' . esc_attr( $price ) . '" style="width:200px"></label></p>';
		echo '<p><label>مواد تشکیل‌دهنده<br><textarea name="_pr_ingredients" rows="3" style="width:100%">' . esc_textarea( $ing ) . '</textarea></label></p>';
		echo '<p>مواد حساسیت‌زا:<br>';
		foreach ( self::allergens() as $k => $lab ) {
			echo '<label style="display:inline-block;margin:4px 12px 4px 0"><input type="checkbox" name="_pr_allergens[]" value="' . esc_attr( $k ) . '"' . checked( in_array( $k, $alg, true ), true, false ) . '> ' . esc_html( $lab ) . '</label>';
		}
		echo '</p><p>';
		echo '<label><input type="checkbox" name="_pr_available" value="1"' . checked( (int) $av, 1, false ) . '> قابل سفارش</label> ';
		echo '<label><input type="checkbox" name="_pr_special" value="1"' . checked( (int) get_post_meta( $post->ID, '_pr_special', true ), 1, false ) . '> پیشنهاد روز</label> ';
		echo '<label><input type="checkbox" name="_pr_veg" value="1"' . checked( (int) get_post_meta( $post->ID, '_pr_veg', true ), 1, false ) . '> گیاهی</label> ';
		echo '<label>تندی <input type="number" min="0" max="3" name="_pr_spicy" value="' . esc_attr( (int) get_post_meta( $post->ID, '_pr_spicy', true ) ) . '" style="width:70px"></label> ';
		echo '<label>زمان آماده‌سازی (دقیقه) <input type="number" min="0" max="180" name="_pr_time" value="' . esc_attr( (int) get_post_meta( $post->ID, '_pr_time', true ) ) . '" style="width:80px"></label>';
		echo '</p>';
	}

	public static function save_dish( $id ) {
		if ( ! isset( $_POST['peecha_dish_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['peecha_dish_nonce'] ) ), 'peecha_dish' ) ) { return; }
		if ( ! current_user_can( 'edit_post', $id ) ) { return; }
		update_post_meta( $id, '_pr_price', isset( $_POST['_pr_price'] ) ? absint( wp_unslash( $_POST['_pr_price'] ) ) : 0 );
		update_post_meta( $id, '_pr_ingredients', isset( $_POST['_pr_ingredients'] ) ? sanitize_textarea_field( wp_unslash( $_POST['_pr_ingredients'] ) ) : '' );
		$alg = array();
		if ( ! empty( $_POST['_pr_allergens'] ) && is_array( $_POST['_pr_allergens'] ) ) {
			foreach ( $_POST['_pr_allergens'] as $k ) {
				$k = sanitize_key( wp_unslash( $k ) );
				if ( isset( self::allergens()[ $k ] ) ) { $alg[] = $k; }
			}
		}
		update_post_meta( $id, '_pr_allergens', $alg );
		update_post_meta( $id, '_pr_available', empty( $_POST['_pr_available'] ) ? 0 : 1 );
		update_post_meta( $id, '_pr_special', empty( $_POST['_pr_special'] ) ? 0 : 1 );
		update_post_meta( $id, '_pr_veg', empty( $_POST['_pr_veg'] ) ? 0 : 1 );
		update_post_meta( $id, '_pr_spicy', isset( $_POST['_pr_spicy'] ) ? min( 3, absint( wp_unslash( $_POST['_pr_spicy'] ) ) ) : 0 );
		update_post_meta( $id, '_pr_time', isset( $_POST['_pr_time'] ) ? min( 180, absint( wp_unslash( $_POST['_pr_time'] ) ) ) : 0 );
	}

	public static function dish_cols( $cols ) {
		$cols['pr_price'] = 'قیمت';
		$cols['pr_flags'] = 'وضعیت';
		return $cols;
	}

	public static function dish_col( $col, $id ) {
		if ( 'pr_price' === $col ) { echo esc_html( peecha_rest_money( (int) get_post_meta( $id, '_pr_price', true ) ) ); }
		if ( 'pr_flags' === $col ) {
			$bits = array();
			if ( ! (int) get_post_meta( $id, '_pr_available', true ) ) { $bits[] = 'ناموجود'; }
			if ( (int) get_post_meta( $id, '_pr_special', true ) ) { $bits[] = 'پیشنهاد روز'; }
			if ( (int) get_post_meta( $id, '_pr_veg', true ) ) { $bits[] = 'گیاهی'; }
			echo esc_html( $bits ? implode( '، ', $bits ) : 'عادی' );
		}
	}

	public static function menu() {
		add_menu_page( 'رستوران', 'رستوران', 'edit_posts', 'peecha-rest', array( __CLASS__, 'screen_home' ), 'dashicons-carrot', 57 );
		add_submenu_page( 'peecha-rest', 'غذاها', 'غذاها', 'edit_posts', 'edit.php?post_type=peecha_dish' );
		add_submenu_page( 'peecha-rest', 'دسته‌های منو', 'دسته‌ها', 'edit_posts', 'edit-tags.php?taxonomy=peecha_menu_cat&post_type=peecha_dish' );
		add_submenu_page( 'peecha-rest', 'رزروها', 'رزروها', 'manage_options', 'peecha-rest-books', array( __CLASS__, 'screen_books' ) );
		add_submenu_page( 'peecha-rest', 'سفارش‌ها', 'سفارش‌ها', 'manage_options', 'peecha-rest-orders', array( __CLASS__, 'screen_orders' ) );
		add_submenu_page( 'peecha-rest', 'تنظیمات رستوران', 'تنظیمات', 'manage_options', 'peecha-rest-settings', array( __CLASS__, 'screen_settings' ) );
		add_submenu_page( 'peecha-rest', 'QR منو', 'QR منو', 'manage_options', 'peecha-rest-qr', array( __CLASS__, 'screen_qr' ) );
	}

	public static function screen_home() {
		$pages = get_option( 'peecha_rest_pages', array() );
		echo '<div class="wrap"><h1>رستوران</h1><p>منو، سفارش و رزرو در این افزونه ذخیره می‌شود و با عوض شدن قالب از بین نمی‌رود.</p><ul>';
		foreach ( array( 'menu' => 'منوی غذا', 'book' => 'رزرو میز', 'order' => 'سفارش' ) as $k => $lab ) {
			if ( ! empty( $pages[ $k ] ) ) { echo '<li><a href="' . esc_url( get_permalink( (int) $pages[ $k ] ) ) . '">' . esc_html( $lab ) . '</a></li>'; }
		}
		echo '</ul><p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=peecha_dish' ) ) . '">غذای تازه</a></p></div>';
	}

	private static function rows( $type ) {
		return get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'posts_per_page' => 80, 'orderby' => 'date', 'order' => 'DESC' ) );
	}

	public static function screen_books() {
		self::table_screen( 'رزروها', self::rows( 'peecha_booking' ), 'book' );
	}

	public static function screen_orders() {
		self::table_screen( 'سفارش‌ها', self::rows( 'peecha_rorder' ), 'order' );
	}

	private static function table_screen( $title, $rows, $kind ) {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1><table class="widefat striped"><thead><tr>';
		if ( 'book' === $kind ) { echo '<th>مهمان</th><th>زمان</th><th>نفر</th><th>میز</th><th>وضعیت</th><th></th>'; }
		else { echo '<th>مشتری</th><th>نوع</th><th>مبلغ</th><th>وضعیت</th><th></th>'; }
		echo '</tr></thead><tbody>';
		if ( ! $rows ) { echo '<tr><td colspan="6">موردی نیست.</td></tr>'; }
		foreach ( $rows as $p ) {
			$status = (string) get_post_meta( $p->ID, '_b_status', true );
			if ( '' === $status ) { $status = (string) get_post_meta( $p->ID, '_o_status', true ); }
			echo '<tr>';
			if ( 'book' === $kind ) {
				$br = peecha_rest_branches();
				$bi = (int) get_post_meta( $p->ID, '_b_branch', true );
				$ti = (int) get_post_meta( $p->ID, '_b_table', true );
				$tn = isset( $br[ $bi ]['tables'][ $ti ]['name'] ) ? $br[ $bi ]['tables'][ $ti ]['name'] : '—';
				echo '<td>' . esc_html( get_post_meta( $p->ID, '_b_name', true ) . ' — ' . get_post_meta( $p->ID, '_b_phone', true ) ) . '</td>';
				echo '<td>' . esc_html( get_post_meta( $p->ID, '_b_date', true ) . ' ' . get_post_meta( $p->ID, '_b_time', true ) ) . '</td>';
				echo '<td>' . esc_html( (string) get_post_meta( $p->ID, '_b_party', true ) ) . '</td><td>' . esc_html( $tn ) . '</td>';
			} else {
				$bits = array();
				$items = get_post_meta( $p->ID, '_o_items', true );
				if ( is_array( $items ) ) {
					foreach ( $items as $ln ) {
						$bits[] = (int) ( $ln['qty'] ?? 0 ) . ' × ' . (string) ( $ln['name'] ?? '' );
					}
				}
				echo '<td>' . esc_html( get_post_meta( $p->ID, '_o_name', true ) . ' — ' . get_post_meta( $p->ID, '_o_phone', true ) );
				if ( $bits ) { echo '<br><span style="color:#646970">' . esc_html( implode( '، ', $bits ) ) . '</span>'; }
				echo '</td>';
				echo '<td>' . esc_html( 'dinein' === get_post_meta( $p->ID, '_o_type', true ) ? 'سالن' : 'بیرون‌بر' ) . '</td>';
				echo '<td>' . esc_html( peecha_rest_money( (int) get_post_meta( $p->ID, '_o_total', true ) ) ) . '</td>';
			}
			echo '<td>' . esc_html( peecha_rest_status_label( $status ) ) . '</td><td>';
			foreach ( array( 'confirmed' => 'تأیید', 'done' => 'انجام شد', 'cancelled' => 'لغو' ) as $st => $lab ) {
				if ( 'book' === $kind && 'done' === $st ) { continue; }
				$url = wp_nonce_url( admin_url( 'admin-post.php?action=peecha_rest_status&id=' . $p->ID . '&st=' . $st ), 'peecha_rest_status_' . $p->ID );
				echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html( $lab ) . '</a> ';
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function save_status() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$st = isset( $_GET['st'] ) ? sanitize_key( wp_unslash( $_GET['st'] ) ) : '';
		if ( ! $id || ! in_array( $st, array( 'confirmed', 'done', 'cancelled' ), true ) || ! current_user_can( 'manage_options' ) ) { wp_die( 'مجاز نیست.' ); }
		check_admin_referer( 'peecha_rest_status_' . $id );
		$type = get_post_type( $id );
		$key  = 'peecha_booking' === $type ? '_b_status' : '_o_status';
		if ( ! in_array( $type, array( 'peecha_booking', 'peecha_rorder' ), true ) ) { wp_die( 'نوع نامعتبر.' ); }
		update_post_meta( $id, $key, $st );
		wp_safe_redirect( admin_url( 'admin.php?page=' . ( 'peecha_booking' === $type ? 'peecha-rest-books' : 'peecha-rest-orders' ) ) );
		exit;
	}

	public static function screen_settings() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$o = self::opt();
		echo '<div class="wrap"><h1>تنظیمات رستوران</h1>';
		if ( isset( $_GET['updated'] ) ) { echo '<div class="updated notice"><p>تنظیمات ذخیره شد.</p></div>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'peecha_rest_settings', 'peecha_rest_settings_nonce' );
		echo '<input type="hidden" name="action" value="peecha_rest_settings">';
		echo '<table class="form-table"><tr><th>ایمیل دریافت</th><td><input type="email" name="email" value="' . esc_attr( $o['email'] ) . '" class="regular-text"></td></tr>';
		echo '<tr><th>مدت هر رزرو (دقیقه)</th><td><input type="number" min="30" max="240" name="slot" value="' . esc_attr( $o['slot'] ) . '"></td></tr>';
		echo '<tr><th>فاصله شروع ساعت‌ها</th><td><input type="number" min="15" max="120" name="step" value="' . esc_attr( $o['step'] ) . '"></td></tr>';
		echo '<tr><th>حداکثر نفر</th><td><input type="number" min="1" max="40" name="party" value="' . esc_attr( $o['party'] ) . '"></td></tr></table>';
		echo '<h2>ساعت کاری</h2><table class="widefat striped"><thead><tr><th>روز</th><th>باز</th><th>از</th><th>تا</th></tr></thead><tbody>';
		foreach ( self::weekdays() as $n => $lab ) {
			$d = $o['days'][ $n ];
			echo '<tr><td>' . esc_html( $lab ) . '</td><td><input type="checkbox" name="day_on[' . esc_attr( $n ) . ']" value="1"' . checked( ! empty( $d['on'] ), true, false ) . '></td>';
			echo '<td><input type="time" name="day_open[' . esc_attr( $n ) . ']" value="' . esc_attr( $d['open'] ) . '"></td>';
			echo '<td><input type="time" name="day_close[' . esc_attr( $n ) . ']" value="' . esc_attr( $d['close'] ) . '"></td></tr>';
		}
		echo '</tbody></table><h2>شعب و میزها</h2><p>هر میز یک خط، به شکل «نام|ظرفیت». مثلاً <code>میز پنجره|4</code></p>';
		$branches = array_values( $o['branches'] );
		for ( $i = 0; $i < 4; $i++ ) {
			$b = isset( $branches[ $i ] ) && is_array( $branches[ $i ] ) ? $branches[ $i ] : array( 'name' => '', 'phone' => '', 'address' => '', 'tables' => '' );
			echo '<div style="border:1px solid #dcdcde;padding:12px;margin:0 0 12px;background:#fff"><h3>شعبه ' . esc_html( (string) ( $i + 1 ) ) . '</h3>';
			echo '<p><input class="regular-text" name="br_name[]" placeholder="نام" value="' . esc_attr( $b['name'] ?? '' ) . '"></p>';
			echo '<p><input class="regular-text" name="br_phone[]" placeholder="تلفن" value="' . esc_attr( $b['phone'] ?? '' ) . '"></p>';
			echo '<p><input class="regular-text" name="br_address[]" placeholder="نشانی" value="' . esc_attr( $b['address'] ?? '' ) . '"></p>';
			$tables = $b['tables'] ?? '';
			if ( is_array( $tables ) ) {
				$lines = array();
				foreach ( $tables as $t ) { $lines[] = $t['name'] . '|' . $t['seats']; }
				$tables = implode( "\n", $lines );
			}
			echo '<p><textarea name="br_tables[]" rows="4" class="large-text" placeholder="میز ۱|4">' . esc_textarea( (string) $tables ) . '</textarea></p></div>';
		}
		echo '<p><button class="button button-primary">ذخیره</button></p></form></div>';
	}

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'مجاز نیست.' ); }
		check_admin_referer( 'peecha_rest_settings', 'peecha_rest_settings_nonce' );
		$days = array();
		foreach ( self::weekdays() as $n => $lab ) {
			$open  = isset( $_POST['day_open'][ $n ] ) ? sanitize_text_field( wp_unslash( $_POST['day_open'][ $n ] ) ) : '12:00';
			$close = isset( $_POST['day_close'][ $n ] ) ? sanitize_text_field( wp_unslash( $_POST['day_close'][ $n ] ) ) : '23:00';
			if ( ! preg_match( '/^\d{2}:\d{2}$/', $open ) ) { $open = '12:00'; }
			if ( ! preg_match( '/^\d{2}:\d{2}$/', $close ) ) { $close = '23:00'; }
			$days[ $n ] = array( 'on' => empty( $_POST['day_on'][ $n ] ) ? 0 : 1, 'open' => $open, 'close' => $close );
		}
		$branches = array();
		$names = isset( $_POST['br_name'] ) && is_array( $_POST['br_name'] ) ? wp_unslash( $_POST['br_name'] ) : array();
		foreach ( $names as $i => $name ) {
			$name = sanitize_text_field( $name );
			if ( '' === $name ) { continue; }
			$branches[] = array(
				'name'    => $name,
				'phone'   => isset( $_POST['br_phone'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['br_phone'][ $i ] ) ) : '',
				'address' => isset( $_POST['br_address'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['br_address'][ $i ] ) ) : '',
				'tables'  => isset( $_POST['br_tables'][ $i ] ) ? sanitize_textarea_field( wp_unslash( $_POST['br_tables'][ $i ] ) ) : '',
			);
		}
		update_option( self::OPT, array(
			'email'    => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : get_option( 'admin_email' ),
			'slot'     => isset( $_POST['slot'] ) ? min( 240, max( 30, absint( $_POST['slot'] ) ) ) : 90,
			'step'     => isset( $_POST['step'] ) ? min( 120, max( 15, absint( $_POST['step'] ) ) ) : 30,
			'party'    => isset( $_POST['party'] ) ? min( 40, max( 1, absint( $_POST['party'] ) ) ) : 10,
			'days'     => $days,
			'branches' => $branches ? $branches : self::defaults()['branches'],
		), false );
		wp_safe_redirect( admin_url( 'admin.php?page=peecha-rest-settings&updated=1' ) );
		exit;
	}

	public static function admin_assets( $hook ) {
		if ( empty( $_GET['page'] ) || 'peecha-rest-qr' !== $_GET['page'] ) { return; }
		wp_enqueue_script( 'peecha-rest-qr', PEECHA_REST_URL . 'assets/qrcode.js', array(), '1.4.4', true );
	}

	public static function screen_qr() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		$pages = get_option( 'peecha_rest_pages', array() );
		$url   = ! empty( $pages['menu'] ) ? get_permalink( (int) $pages['menu'] ) : home_url( '/' );
		echo '<div class="wrap"><h1>منوی QR</h1><p>این نشانی را روی میز چاپ کنید. مشتری بدون نصب برنامه منو را می‌بیند.</p>';
		echo '<p><input class="large-text" readonly value="' . esc_attr( $url ) . '"></p>';
		echo '<div id="peechaRestQr"></div>';
		echo '<script>document.addEventListener("DOMContentLoaded",function(){if(!window.qrcode)return;var q=qrcode(0,"M");q.addData(' . wp_json_encode( $url ) . ');q.make();document.getElementById("peechaRestQr").innerHTML=q.createSvgTag({cellSize:4,margin:8});});</script></div>';
	}
}

Peecha_Restaurant::init();
