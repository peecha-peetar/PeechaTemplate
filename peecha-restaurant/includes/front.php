<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function peecha_rest_money( $n ) {
	$s = number_format( (int) $n );
	if ( function_exists( 'sahel_fa' ) ) { $s = sahel_fa( $s ); }
	return $s . ' تومان';
}

function peecha_rest_status_label( $st ) {
	$map = array( 'pending' => 'در انتظار', 'confirmed' => 'تأیید شده', 'done' => 'انجام شد', 'cancelled' => 'لغو شده' );
	return isset( $map[ $st ] ) ? $map[ $st ] : $st;
}

function peecha_rest_branches() { return Peecha_Restaurant::branches(); }

function peecha_rest_page( $key ) {
	$pages = get_option( 'peecha_rest_pages', array() );
	return ! empty( $pages[ $key ] ) ? get_permalink( (int) $pages[ $key ] ) : home_url( '/' );
}

function peecha_rest_dishes() {
	$q = new WP_Query( array(
		'post_type'      => 'peecha_dish',
		'post_status'    => 'publish',
		'posts_per_page' => 80,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
	) );
	return $q->posts;
}

function peecha_rest_nav( $current ) {
	$items = array( 'menu' => 'منو', 'order' => 'سفارش', 'book' => 'رزرو میز' );
	$h = '<nav class="prm-nav">';
	foreach ( $items as $key => $label ) {
		$h .= '<a class="' . ( $current === $key ? '' : 'is-quiet' ) . '" href="' . esc_url( peecha_rest_page( $key ) ) . '">' . esc_html( $label ) . '</a>';
	}
	return $h . '</nav>';
}

function peecha_rest_available( $id ) {
	$av = get_post_meta( $id, '_pr_available', true );
	return '' === (string) $av || (int) $av === 1;
}

function peecha_rest_item( $p, $force = false ) {
	if ( ! $force && ! peecha_rest_available( $p->ID ) ) { return ''; }
	$terms = get_the_terms( $p->ID, 'peecha_menu_cat' );
	$slug  = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
	$img   = get_the_post_thumbnail_url( $p->ID, 'medium' );
	$alg   = (array) get_post_meta( $p->ID, '_pr_allergens', true );
	$names = Peecha_Restaurant::allergens();
	$veg   = (int) get_post_meta( $p->ID, '_pr_veg', true );
	$spicy = (int) get_post_meta( $p->ID, '_pr_spicy', true );
	$day   = (int) get_post_meta( $p->ID, '_pr_special', true );
	$mins  = (int) get_post_meta( $p->ID, '_pr_time', true );
	$price = (int) get_post_meta( $p->ID, '_pr_price', true );
	$ing   = (string) get_post_meta( $p->ID, '_pr_ingredients', true );
	$title = get_the_title( $p );
	$h = '<article class="prm-item" data-cat="' . esc_attr( $slug ) . '" data-veg="' . $veg . '" data-hot="' . ( $spicy ? 1 : 0 ) . '" data-day="' . $day . '" data-text="' . esc_attr( $title . ' ' . $ing ) . '">';
	if ( $img ) { $h .= '<img src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '" loading="lazy">'; }
	else { $h .= '<div class="prm-ph" aria-hidden="true">' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : '' ) . '</div>'; }
	$h .= '<div><h4><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( $title ) . '</a></h4>';
	if ( $ing ) { $h .= '<p>' . esc_html( $ing ) . '</p>'; }
	$h .= '<div class="prm-tags">';
	if ( $day ) { $h .= '<span class="is-day">پیشنهاد امروز</span>'; }
	if ( $veg ) { $h .= '<span class="is-veg">گیاهی</span>'; }
	if ( $spicy ) { $h .= '<span class="is-hot">تندی ' . esc_html( str_repeat( '•', $spicy ) ) . '</span>'; }
	if ( $mins ) { $h .= '<span>' . esc_html( ( function_exists( 'sahel_fa' ) ? sahel_fa( (string) $mins ) : (string) $mins ) . ' دقیقه' ) . '</span>'; }
	foreach ( $alg as $k ) { if ( isset( $names[ $k ] ) ) { $h .= '<span>' . esc_html( $names[ $k ] ) . '</span>'; } }
	$h .= '</div></div><div class="prm-buy"><b>' . esc_html( peecha_rest_money( $price ) ) . '</b>';
	if ( peecha_rest_available( $p->ID ) ) {
		$h .= '<button type="button" class="prm-add" data-id="' . (int) $p->ID . '" data-name="' . esc_attr( $title ) . '" data-price="' . $price . '">افزودن</button>';
	} else {
		$h .= '<span>ناموجود</span>';
	}
	return $h . '</div></article>';
}

function peecha_rest_head( $kicker, $title, $lead, $current ) {
	return '<header class="prm-top"><div><p class="prm-kicker">' . esc_html( $kicker ) . '</p><h2>' . esc_html( $title ) . '</h2><p class="prm-lead">' . esc_html( $lead ) . '</p></div>' . peecha_rest_nav( $current ) . '</header>';
}

function peecha_rest_menu_html() {
	$posts = peecha_rest_dishes();
	$grouped = array();
	foreach ( $posts as $p ) {
		$terms = get_the_terms( $p->ID, 'peecha_menu_cat' );
		$name = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'سایر';
		$slug = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->slug : '';
		if ( ! isset( $grouped[ $name ] ) ) { $grouped[ $name ] = array( 'slug' => $slug, 'posts' => array() ); }
		$grouped[ $name ]['posts'][] = $p;
	}
	$h = '<div class="prm">' . peecha_rest_head( 'منوی سالن', 'غذا، نوشیدنی، پیشنهاد امروز', 'هر ردیف قیمت، مواد و حساسیت‌زا را نشان می‌دهد. افزودن، غذا را در سبد همین مرورگر نگه می‌دارد.', 'menu' );
	$h .= '<div class="prm-tools"><input class="prm-search" type="search" placeholder="جستجو در نام یا مواد" aria-label="جستجوی غذا">';
	$h .= '<div class="prm-filters"><button type="button" class="on" data-filter="">همه</button>';
	foreach ( $grouped as $name => $group ) {
		if ( '' === $group['slug'] ) { continue; }
		$h .= '<button type="button" data-filter="' . esc_attr( $group['slug'] ) . '">' . esc_html( $name ) . '</button>';
	}
	$h .= '<button type="button" data-diet="veg">گیاهی</button><button type="button" data-diet="hot">تند</button><button type="button" data-diet="day">پیشنهاد امروز</button></div></div>';
	foreach ( $grouped as $name => $group ) {
		$chunk = '';
		foreach ( $group['posts'] as $p ) { $chunk .= peecha_rest_item( $p ); }
		if ( '' === $chunk ) { continue; }
		$h .= '<section class="prm-section"><h3>' . esc_html( $name ) . '</h3>' . $chunk . '</section>';
	}
	$h .= '<p class="prm-empty" hidden>با این فیلتر غذایی پیدا نشد.</p>';
	$h .= '<aside class="prm-dock" hidden><b data-dock></b><a href="' . esc_url( peecha_rest_page( 'order' ) ) . '">ادامه سفارش</a></aside></div>';
	return $h;
}

function peecha_rest_day( $date ) {
	$opt = Peecha_Restaurant::opt();
	$ts  = strtotime( $date . ' 12:00:00' );
	if ( ! $ts ) { return null; }
	$w = (int) wp_date( 'w', $ts );
	$d = isset( $opt['days'][ $w ] ) ? $opt['days'][ $w ] : null;
	if ( ! $d || empty( $d['on'] ) ) { return null; }
	return $d;
}

function peecha_rest_bookings( $date, $branch ) {
	return get_posts( array(
		'post_type'      => 'peecha_booking',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
		'meta_query'     => array(
			array( 'key' => '_b_date', 'value' => $date ),
			array( 'key' => '_b_branch', 'value' => (string) $branch ),
			array( 'key' => '_b_status', 'value' => array( 'pending', 'confirmed' ), 'compare' => 'IN' ),
		),
	) );
}

function peecha_rest_slots( $date, $branch, $party ) {
	$day = peecha_rest_day( $date );
	$branches = peecha_rest_branches();
	if ( ! $day || ! isset( $branches[ $branch ] ) ) { return array(); }
	$opt = Peecha_Restaurant::opt();
	$party = max( 1, min( (int) $opt['party'], (int) $party ) );
	$dur = max( 30, (int) $opt['slot'] ) * 60;
	$step = max( 15, (int) $opt['step'] ) * 60;
	$start = strtotime( $date . ' ' . $day['open'] );
	$end = strtotime( $date . ' ' . $day['close'] );
	if ( ! $start || ! $end || $end <= $start ) { return array(); }
	$busy = array();
	foreach ( peecha_rest_bookings( $date, $branch ) as $b ) {
		$t = strtotime( $date . ' ' . get_post_meta( $b->ID, '_b_time', true ) );
		$busy[] = array( (int) get_post_meta( $b->ID, '_b_table', true ), $t, $t + $dur );
	}
	$now = current_time( 'timestamp' );
	$out = array();
	for ( $t = $start; $t + $dur <= $end; $t += $step ) {
		if ( $t < $now ) { continue; }
		foreach ( $branches[ $branch ]['tables'] as $i => $table ) {
			if ( (int) $table['seats'] < $party ) { continue; }
			$free = true;
			foreach ( $busy as $row ) {
				if ( $row[0] === $i && $t < $row[2] && ( $t + $dur ) > $row[1] ) { $free = false; break; }
			}
			if ( $free ) { $out[] = array( 'time' => wp_date( 'H:i', $t ), 'table' => $i ); break; }
		}
	}
	return $out;
}

function peecha_rest_assign( $date, $branch, $party, $time ) {
	foreach ( peecha_rest_slots( $date, $branch, $party ) as $slot ) {
		if ( $slot['time'] === $time ) { return (int) $slot['table']; }
	}
	return -1;
}

function peecha_rest_reserve_html() {
	$branches = peecha_rest_branches();
	$opt = Peecha_Restaurant::opt();
	$branch = isset( $_GET['pr_branch'] ) ? absint( $_GET['pr_branch'] ) : (int) array_key_first( $branches );
	$party = isset( $_GET['pr_party'] ) ? absint( $_GET['pr_party'] ) : 2;
	$date = isset( $_GET['pr_date'] ) ? sanitize_text_field( wp_unslash( $_GET['pr_date'] ) ) : wp_date( 'Y-m-d' );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { $date = wp_date( 'Y-m-d' ); }
	$msg = isset( $_GET['pr_msg'] ) ? sanitize_key( wp_unslash( $_GET['pr_msg'] ) ) : '';
	$h = '<div class="prm">' . peecha_rest_head( 'رزرو میز', 'ساعتی که میز واقعاً آزاد است', 'ظرفیت میز با تعداد نفر سنجیده می‌شود و دو رزرو روی یک میز هم‌پوشانی ندارند. لغو با پیوند ایمیل است.', 'book' );
	if ( 'ok' === $msg ) { $h .= '<p class="prm-note">درخواست ثبت شد. تا تأیید رستوران، رزرو قطعی نیست.</p>'; }
	if ( 'cancelled' === $msg ) { $h .= '<p class="prm-note">رزرو لغو شد و میز دوباره آزاد است.</p>'; }
	if ( 'bad' === $msg ) { $h .= '<p class="prm-note prm-bad">این ساعت دیگر آزاد نیست یا نام و موبایل کامل نبود.</p>'; }
	if ( 'wait' === $msg ) { $h .= '<p class="prm-note prm-bad">درخواست قبلی هنوز باز است. یک دقیقه بعد دوباره بفرستید.</p>'; }
	$place = isset( $branches[ $branch ] ) ? $branches[ $branch ] : null;
	$h .= '<div class="prm-book"><form class="prm-panel prm-form" method="get"><label>شعبه<select name="pr_branch">';
	foreach ( $branches as $i => $b ) { $h .= '<option value="' . (int) $i . '"' . selected( $branch, $i, false ) . '>' . esc_html( $b['name'] ) . '</option>'; }
	$h .= '</select></label><label>تاریخ<input type="date" name="pr_date" required value="' . esc_attr( $date ) . '"></label>';
	$h .= '<label>تعداد نفر<input type="number" name="pr_party" min="1" max="' . (int) $opt['party'] . '" value="' . esc_attr( $party ) . '"></label>';
	if ( $place && ! empty( $place['address'] ) ) { $h .= '<p class="prm-lead">' . esc_html( $place['address'] ) . '</p>'; }
	$h .= '<button class="prm-go">ساعت‌های آزاد</button></form>';
	$slots = peecha_rest_slots( $date, $branch, $party );
	$h .= '<div class="prm-panel">';
	if ( ! $slots ) { $h .= '<p>برای این روز و تعداد نفر، میز آزادی نیست. تاریخ یا تعداد را عوض کنید.</p>'; }
	else {
		$h .= '<form class="prm-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="peecha_rest_book">';
		$h .= wp_nonce_field( 'peecha_rest_book', 'peecha_rest_book_nonce', true, false );
		$h .= '<input type="hidden" name="branch" value="' . (int) $branch . '"><input type="hidden" name="date" value="' . esc_attr( $date ) . '"><input type="hidden" name="party" value="' . (int) $party . '">';
		$h .= '<p class="prm-kicker">ساعت‌های آزاد</p><div class="prm-slots">';
		foreach ( $slots as $s ) { $h .= '<label><input type="radio" name="time" value="' . esc_attr( $s['time'] ) . '" required> ' . esc_html( $s['time'] ) . '</label>'; }
		$h .= '</div><label>نام<input name="guest" required maxlength="80"></label><label>موبایل<input name="phone" required maxlength="20" inputmode="tel"></label><label>ایمیل برای لغو<input type="email" name="email"></label><label>یادداشت<textarea name="note" maxlength="300" placeholder="مناسبت، صندلی کودک، حساسیت غذایی"></textarea></label><button class="prm-go">ثبت رزرو</button></form>';
	}
	return $h . '</div></div></div>';
}

function peecha_rest_order_html() {
	$branches = peecha_rest_branches();
	$msg = isset( $_GET['pr_msg'] ) ? sanitize_key( wp_unslash( $_GET['pr_msg'] ) ) : '';
	$h = '<div class="prm">' . peecha_rest_head( 'سفارش', 'سالن یا بیرون‌بر', 'مبلغ نهایی موقع ثبت از قیمت غذا در پیشخوان حساب می‌شود. عدد روی دکمه فقط برای دیدن سبد است.', 'order' );
	if ( 'ok' === $msg ) { $h .= '<p class="prm-note">سفارش ثبت شد و در انتظار تأیید رستوران است.</p>'; }
	if ( 'bad' === $msg ) { $h .= '<p class="prm-note prm-bad">سبد خالی بود یا نام و موبایل کامل نبود.</p>'; }
	$h .= '<div class="prm-split"><div>';
	foreach ( peecha_rest_dishes() as $p ) { $h .= peecha_rest_item( $p ); }
	$h .= '</div><form class="prm-form prm-cart" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><p class="prm-kicker">سبد</p><div class="prm-lines">از منو یک غذا اضافه کنید.</div><div class="prm-sum" hidden><span>جمع</span><b></b></div><input type="hidden" name="action" value="peecha_rest_order"><input type="hidden" name="items" value="">';
	$h .= wp_nonce_field( 'peecha_rest_order', 'peecha_rest_order_nonce', true, false );
	$h .= '<label>نوع سفارش<select name="otype"><option value="takeaway">بیرون‌بر</option><option value="dinein">سالن</option></select></label><label>شعبه<select name="branch">';
	foreach ( $branches as $i => $b ) { $h .= '<option value="' . (int) $i . '">' . esc_html( $b['name'] ) . '</option>'; }
	$h .= '</select></label><label>نام<input name="guest" required maxlength="80"></label><label>موبایل<input name="phone" required maxlength="20" inputmode="tel"></label><label>ساعت تحویل یا حضور<input type="time" name="when"></label><label>یادداشت<textarea name="note" maxlength="300" placeholder="بدون پیاز، سس جدا"></textarea></label><button class="prm-go">ثبت سفارش</button></form></div></div>';
	return $h;
}

function peecha_rest_single_html( $p ) {
	$ing = (string) get_post_meta( $p->ID, '_pr_ingredients', true );
	$img = get_the_post_thumbnail_url( $p->ID, 'large' );
	$title = get_the_title( $p );
	$h = '<div class="prm prm-single">';
	if ( $img ) { $h .= '<img class="prm-cover" src="' . esc_url( $img ) . '" alt="' . esc_attr( $title ) . '">'; }
	else { $h .= '<div class="prm-ph prm-cover">' . esc_html( function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1 ) : '' ) . '</div>'; }
	$h .= '<div>' . peecha_rest_head( 'جزئیات غذا', $title, $ing ? $ing : 'مواد این غذا جداگانه ثبت نشده.', 'menu' );
	$price = (int) get_post_meta( $p->ID, '_pr_price', true );
	$h .= '<div class="prm-buy" style="text-align:right;margin:8px 0 12px"><b>' . esc_html( peecha_rest_money( $price ) ) . '</b> ';
	if ( peecha_rest_available( $p->ID ) ) {
		$h .= '<button type="button" class="prm-add" data-id="' . (int) $p->ID . '" data-name="' . esc_attr( $title ) . '" data-price="' . $price . '">افزودن به سفارش</button>';
	} else { $h .= '<span>این غذا الان موجود نیست.</span>'; }
	$h .= '</div><div class="prm-tags">';
	$names = Peecha_Restaurant::allergens();
	if ( (int) get_post_meta( $p->ID, '_pr_special', true ) ) { $h .= '<span class="is-day">پیشنهاد امروز</span>'; }
	if ( (int) get_post_meta( $p->ID, '_pr_veg', true ) ) { $h .= '<span class="is-veg">گیاهی</span>'; }
	$spicy = (int) get_post_meta( $p->ID, '_pr_spicy', true );
	if ( $spicy ) { $h .= '<span class="is-hot">تندی ' . esc_html( str_repeat( '•', $spicy ) ) . '</span>'; }
	$mins = (int) get_post_meta( $p->ID, '_pr_time', true );
	if ( $mins ) { $h .= '<span>' . esc_html( ( function_exists( 'sahel_fa' ) ? sahel_fa( (string) $mins ) : (string) $mins ) . ' دقیقه' ) . '</span>'; }
	foreach ( (array) get_post_meta( $p->ID, '_pr_allergens', true ) as $k ) {
		if ( isset( $names[ $k ] ) ) { $h .= '<span>' . esc_html( $names[ $k ] ) . '</span>'; }
	}
	$h .= '</div>';
	$content = apply_filters( 'the_content', $p->post_content );
	if ( trim( wp_strip_all_tags( $content ) ) !== '' && trim( wp_strip_all_tags( $content ) ) !== $ing ) {
		$h .= '<div class="prm-copy">' . $content . '</div>';
	}
	$h .= '<aside class="prm-dock" hidden><b data-dock></b><a href="' . esc_url( peecha_rest_page( 'order' ) ) . '">ادامه سفارش</a></aside></div></div>';
	return $h;
}

function peecha_rest_shell( $html ) {
	if ( function_exists( 'sahel_shell' ) ) { sahel_shell( $html ); exit; }
	status_header( 200 );
	echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>';
	wp_head();
	echo '</head><body class="prm-standalone">';
	echo $html; // phpcs:ignore -- parts are escaped in builders
	wp_footer();
	echo '</body></html>';
	exit;
}

add_shortcode( 'peecha_menu', 'peecha_rest_menu_html' );
add_shortcode( 'peecha_reserve', 'peecha_rest_reserve_html' );
add_shortcode( 'peecha_order', 'peecha_rest_order_html' );

add_action( 'wp_enqueue_scripts', function () {
	$load = is_singular( 'peecha_dish' ) || is_post_type_archive( 'peecha_dish' ) || is_tax( 'peecha_menu_cat' );
	if ( ! $load && is_singular() ) {
		$post = get_post();
		if ( $post && ( has_shortcode( $post->post_content, 'peecha_menu' ) || has_shortcode( $post->post_content, 'peecha_reserve' ) || has_shortcode( $post->post_content, 'peecha_order' ) ) ) { $load = true; }
	}
	if ( ! $load ) { return; }
	wp_enqueue_style( 'peecha-rest', PEECHA_REST_URL . 'assets/restaurant.css', array(), PEECHA_REST_VER );
	wp_enqueue_script( 'peecha-rest', PEECHA_REST_URL . 'assets/restaurant.js', array(), PEECHA_REST_VER, true );
} );

add_action( 'template_redirect', function () {
	if ( isset( $_GET['peecha_cancel'] ) ) {
		$token = sanitize_text_field( wp_unslash( $_GET['peecha_cancel'] ) );
		$found = get_posts( array( 'post_type' => 'peecha_booking', 'post_status' => 'publish', 'posts_per_page' => 1, 'meta_key' => '_b_token', 'meta_value' => $token ) );
		if ( $found ) {
			$when = strtotime( get_post_meta( $found[0]->ID, '_b_date', true ) . ' ' . get_post_meta( $found[0]->ID, '_b_time', true ) );
			if ( $when && $when > current_time( 'timestamp' ) ) { update_post_meta( $found[0]->ID, '_b_status', 'cancelled' ); }
		}
		wp_safe_redirect( add_query_arg( 'pr_msg', 'cancelled', peecha_rest_page( 'book' ) ) );
		exit;
	}
	if ( is_singular( 'peecha_dish' ) ) {
		$p = get_queried_object();
		peecha_rest_shell( '<main class="wrap">' . peecha_rest_single_html( $p ) . '</main>' );
	}
	if ( is_post_type_archive( 'peecha_dish' ) || is_tax( 'peecha_menu_cat' ) ) {
		peecha_rest_shell( '<main class="wrap" style="padding:32px 0 70px">' . peecha_rest_menu_html() . '</main>' );
	}
} );

add_action( 'admin_post_peecha_rest_book', 'peecha_rest_book' );
add_action( 'admin_post_nopriv_peecha_rest_book', 'peecha_rest_book' );
function peecha_rest_book() {
	$back = peecha_rest_page( 'book' );
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? md5( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) : '0';
	if ( get_transient( 'peecha_rest_b_' . $ip ) ) { wp_safe_redirect( add_query_arg( 'pr_msg', 'wait', $back ) ); exit; }
	if ( ! isset( $_POST['peecha_rest_book_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['peecha_rest_book_nonce'] ) ), 'peecha_rest_book' ) ) {
		wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit;
	}
	$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
	$time = isset( $_POST['time'] ) ? sanitize_text_field( wp_unslash( $_POST['time'] ) ) : '';
	$name = isset( $_POST['guest'] ) ? sanitize_text_field( wp_unslash( $_POST['guest'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$branch = isset( $_POST['branch'] ) ? absint( $_POST['branch'] ) : 0;
	$party = isset( $_POST['party'] ) ? absint( $_POST['party'] ) : 0;
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) || '' === $name || '' === $phone ) {
		wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit;
	}
	$table = peecha_rest_assign( $date, $branch, $party, $time );
	if ( $table < 0 ) { wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit; }
	$lock = 'peecha_rest_lock_' . md5( $branch . '|' . $date . '|' . $time . '|' . $table );
	if ( get_transient( $lock ) ) { wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit; }
	set_transient( $lock, 1, 3 * MINUTE_IN_SECONDS );
	$token = wp_generate_password( 20, false, false );
	$id = wp_insert_post( array( 'post_type' => 'peecha_booking', 'post_status' => 'publish', 'post_title' => $name . ' ' . $date . ' ' . $time ) );
	if ( ! $id ) { wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit; }
	update_post_meta( $id, '_b_branch', $branch );
	update_post_meta( $id, '_b_table', $table );
	update_post_meta( $id, '_b_date', $date );
	update_post_meta( $id, '_b_time', $time );
	update_post_meta( $id, '_b_party', $party );
	update_post_meta( $id, '_b_name', $name );
	update_post_meta( $id, '_b_phone', $phone );
	update_post_meta( $id, '_b_note', isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '' );
	update_post_meta( $id, '_b_status', 'pending' );
	update_post_meta( $id, '_b_token', $token );
	set_transient( 'peecha_rest_b_' . $ip, 1, MINUTE_IN_SECONDS );
	$opt = Peecha_Restaurant::opt();
	$cancel = add_query_arg( 'peecha_cancel', $token, home_url( '/' ) );
	wp_mail( $opt['email'], 'رزرو میز تازه', $name . ' ' . $phone . "\n" . $date . ' ' . $time . ' — ' . $party . ' نفر' );
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( $email ) { wp_mail( $email, 'درخواست رزرو ثبت شد', "رزرو شما در انتظار تأیید است.\nلغو: " . $cancel ); }
	wp_safe_redirect( add_query_arg( 'pr_msg', 'ok', $back ) );
	exit;
}

add_action( 'admin_post_peecha_rest_order', 'peecha_rest_order' );
add_action( 'admin_post_nopriv_peecha_rest_order', 'peecha_rest_order' );
function peecha_rest_order() {
	$back = peecha_rest_page( 'order' );
	if ( ! isset( $_POST['peecha_rest_order_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['peecha_rest_order_nonce'] ) ), 'peecha_rest_order' ) ) {
		wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit;
	}
	$name = isset( $_POST['guest'] ) ? sanitize_text_field( wp_unslash( $_POST['guest'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$raw = isset( $_POST['items'] ) ? json_decode( wp_unslash( $_POST['items'] ), true ) : array();
	$lines = array();
	$total = 0;
	if ( is_array( $raw ) ) {
		foreach ( array_slice( $raw, 0, 30 ) as $row ) {
			$id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
			$qty = isset( $row['qty'] ) ? min( 20, absint( $row['qty'] ) ) : 0;
			if ( ! $id || ! $qty || 'peecha_dish' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) { continue; }
			if ( ! (int) get_post_meta( $id, '_pr_available', true ) ) { continue; }
			$price = (int) get_post_meta( $id, '_pr_price', true );
			$lines[] = array( 'id' => $id, 'name' => get_the_title( $id ), 'qty' => $qty, 'price' => $price );
			$total += $price * $qty;
		}
	}
	if ( ! $lines || '' === $name || '' === $phone ) { wp_safe_redirect( add_query_arg( 'pr_msg', 'bad', $back ) ); exit; }
	$type = ( isset( $_POST['otype'] ) && 'dinein' === $_POST['otype'] ) ? 'dinein' : 'takeaway';
	$id = wp_insert_post( array( 'post_type' => 'peecha_rorder', 'post_status' => 'publish', 'post_title' => $name . ' ' . $total ) );
	update_post_meta( $id, '_o_name', $name );
	update_post_meta( $id, '_o_phone', $phone );
	update_post_meta( $id, '_o_type', $type );
	update_post_meta( $id, '_o_branch', isset( $_POST['branch'] ) ? absint( $_POST['branch'] ) : 0 );
	update_post_meta( $id, '_o_when', isset( $_POST['when'] ) ? sanitize_text_field( wp_unslash( $_POST['when'] ) ) : '' );
	update_post_meta( $id, '_o_note', isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '' );
	update_post_meta( $id, '_o_items', $lines );
	update_post_meta( $id, '_o_total', $total );
	update_post_meta( $id, '_o_status', 'pending' );
	$opt = Peecha_Restaurant::opt();
	$body = $name . ' ' . $phone . "\n" . ( 'dinein' === $type ? 'سالن' : 'بیرون‌بر' ) . "\n";
	foreach ( $lines as $ln ) { $body .= $ln['qty'] . ' × ' . $ln['name'] . "\n"; }
	$body .= 'جمع: ' . $total;
	wp_mail( $opt['email'], 'سفارش غذا', $body );
	wp_safe_redirect( add_query_arg( 'pr_msg', 'ok', $back ) );
	exit;
}
