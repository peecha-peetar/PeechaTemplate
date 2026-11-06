<?php
/**
 * پنل پیچا — صفحه‌ی «تغییر قیمت»: ویرایش سریع قیمت همه‌ی محصولات و حالت‌ها در یک صفحه،
 * تغییر گروهی (درصد/مبلغ/گرد کردن) با پیش‌نمایش، و ذخیره‌ی یک‌جا. مناسب موبایل و دسکتاپ.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class PP_Prices {

	public static function init() {
		add_action( 'wp_ajax_pp_price_batch', array( __CLASS__, 'ajax_batch' ) );
	}

	public static function page() {
		$s    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore
		$cat  = isset( $_GET['cat'] ) ? sanitize_title( wp_unslash( $_GET['cat'] ) ) : ''; // phpcs:ignore
		$type = isset( $_GET['ptype'] ) ? sanitize_key( $_GET['ptype'] ) : ''; // phpcs:ignore
		$sale = ! empty( $_GET['onsale'] ); // phpcs:ignore
		$per  = isset( $_GET['per'] ) ? absint( $_GET['per'] ) : 40; // phpcs:ignore
		$per  = in_array( $per, array( 20, 40, 80, 150 ), true ) ? $per : 40;
		$page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore

		echo '<div class="pp-head"><h1>تغییر قیمت <small>قیمت‌ها را عوض کنید و یک‌جا ذخیره کنید</small></h1></div>';
		echo '<form class="pp-filters" method="get"><input type="hidden" name="page" value="' . esc_attr( PP_SLUG ) . '"><input type="hidden" name="tab" value="prices">';
		echo '<input type="search" name="s" value="' . esc_attr( $s ) . '" placeholder="نام یا کد محصول…"><select name="cat"><option value="">همه‌ی دسته‌ها</option>';
		$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false, 'number' => 300 ) );
		if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $t ) { echo '<option value="' . esc_attr( $t->slug ) . '"' . selected( $cat, $t->slug, false ) . '>' . esc_html( $t->name ) . '</option>'; } }
		echo '</select><select name="ptype"><option value="">همه‌ی انواع</option><option value="simple"' . selected( $type, 'simple', false ) . '>ساده</option><option value="variable"' . selected( $type, 'variable', false ) . '>متغیر</option></select>';
		echo '<select name="per">'; foreach ( array( 20, 40, 80, 150 ) as $n ) { echo '<option value="' . $n . '"' . selected( $per, $n, false ) . '>' . esc_html( Peecha_Panel::fa( $n ) ) . ' در صفحه</option>'; } echo '</select>';
		echo '<label class="pp-chk"><input type="checkbox" name="onsale" value="1"' . checked( $sale, true, false ) . '> فقط حراجی‌ها</label><button class="pp-btn ghost">فیلتر</button></form>';

		$args = array( 'limit' => $per, 'page' => $page, 'paginate' => true, 'status' => array( 'publish', 'draft', 'pending', 'private' ), 'orderby' => 'title', 'order' => 'ASC', 'type' => in_array( $type, array( 'simple', 'variable' ), true ) ? array( $type ) : array( 'simple', 'variable', 'external' ) );
		if ( '' !== $s ) {
			$by_sku = wc_get_product_id_by_sku( $s );
			if ( $by_sku ) { $pp = wc_get_product( $by_sku ); $args['include'] = array( $pp && $pp->get_parent_id() ? $pp->get_parent_id() : $by_sku ); } else { $args['s'] = $s; }
		}
		if ( $cat ) { $args['category'] = array( $cat ); }
		if ( $sale ) { $ids = wc_get_product_ids_on_sale(); $par = array(); foreach ( $ids as $i ) { $pp = wp_get_post_parent_id( $i ); $par[] = $pp ? $pp : $i; } $args['include'] = $par ? array_values( array_unique( $par ) ) : array( 0 ); }
		$res = wc_get_products( $args );

		/* ابزار تغییر گروهی */
		echo '<section class="pp-box pp-bulk"><h2>تغییر گروهی <small>روی ردیف‌های انتخاب‌شده (یا همه‌ی ردیف‌های این صفحه اگر چیزی انتخاب نشده). اول پیش‌نمایش می‌بینید، بعد ذخیره می‌کنید.</small></h2><div class="pp-bulk-row">';
		echo '<select id="ppbOp"><option value="inc_pct">افزایش درصدی</option><option value="dec_pct">کاهش درصدی</option><option value="inc_amt">افزایش مبلغ ثابت</option><option value="dec_amt">کاهش مبلغ ثابت</option><option value="set">قیمت یکسان</option><option value="sale_pct">قیمت ویژه = اصلی منهای درصد</option><option value="clear_sale">حذف قیمت ویژه</option></select>';
		echo '<input id="ppbVal" type="text" inputmode="decimal" placeholder="مقدار (مثلاً ۱۰)" dir="ltr">';
		echo '<select id="ppbField"><option value="regular">قیمت اصلی</option><option value="sale">قیمت ویژه</option><option value="both">هر دو</option></select>';
		echo '<select id="ppbRound"><option value="0">بدون گرد کردن</option><option value="100">گرد به ۱۰۰</option><option value="1000" selected>گرد به ۱٬۰۰۰</option><option value="10000">گرد به ۱۰٬۰۰۰</option><option value="100000">گرد به ۱۰۰٬۰۰۰</option></select>';
		echo '<button type="button" class="pp-btn" id="ppbApply">اعمال (پیش‌نمایش)</button></div></section>';

		if ( ! $res->products ) { echo '<section class="pp-box"><p class="pp-empty">محصولی پیدا نشد.</p></section>'; return; }
		echo '<section class="pp-box flush"><div class="pp-table-wrap"><table class="pp-table pp-prices"><thead><tr><th class="cb"><input type="checkbox" id="ppAll" aria-label="انتخاب همه"></th><th></th><th>محصول</th><th>قیمت اصلی</th><th>قیمت ویژه</th><th></th></tr></thead><tbody>';
		foreach ( $res->products as $p ) {
			if ( $p->is_type( 'variable' ) ) {
				echo '<tr class="pp-pgroup"><td class="cb"><input type="checkbox" class="ppGroup" data-group="' . (int) $p->get_id() . '" aria-label="انتخاب همه‌ی حالت‌ها"></td><td class="pp-thumb">' . wp_kses_post( $p->get_image( array( 40, 40 ) ) ) . '</td><td colspan="4"><b>' . esc_html( $p->get_name() ) . '</b><small>محصول متغیر — ' . esc_html( Peecha_Panel::fa( count( $p->get_children() ) ) ) . ' حالت</small></td></tr>';
				foreach ( array_slice( $p->get_children(), 0, 100 ) as $vid ) { $v = wc_get_product( $vid ); if ( $v ) { self::row( $v, $p ); } }
			} else {
				self::row( $p, null );
			}
		}
		echo '</tbody></table></div></section>';
		echo '<div class="pp-savebar" id="ppSavebar" hidden><span id="ppSaveCount"></span><button type="button" class="pp-btn ghost" id="ppUndo">لغو تغییرها</button><button type="button" class="pp-btn" id="ppSaveAll">ذخیره‌ی همه</button></div>';
		$base = array_filter( array( 's' => $s, 'cat' => $cat, 'ptype' => $type, 'per' => 40 !== $per ? $per : '', 'onsale' => $sale ? 1 : '' ) );
		if ( (int) $res->max_num_pages > 1 ) {
			$max = (int) $res->max_num_pages;
			echo '<nav class="pp-pager">';
			if ( $page > 1 ) { echo '<a href="' . esc_url( Peecha_Panel::url( 'prices', $base + array( 'paged' => $page - 1 ) ) ) . '">→ قبلی</a>'; }
			echo '<span>صفحه‌ی ' . esc_html( Peecha_Panel::fa( $page ) ) . ' از ' . esc_html( Peecha_Panel::fa( $max ) ) . '</span>';
			if ( $page < $max ) { echo '<a href="' . esc_url( Peecha_Panel::url( 'prices', $base + array( 'paged' => $page + 1 ) ) ) . '">بعدی ←</a>'; }
			echo '</nav>';
		}
	}

	private static function row( $p, $parent ) {
		$id = $p->get_id();
		$name = $parent ? wc_get_formatted_variation( $p, true, false, false ) : $p->get_name();
		echo '<tr class="pp-prc' . ( $parent ? ' var' : '' ) . '" data-id="' . (int) $id . '"' . ( $parent ? ' data-group="' . (int) $parent->get_id() . '"' : '' ) . '>';
		echo '<td class="cb"><input type="checkbox" class="ppSel" aria-label="انتخاب"></td>';
		echo '<td class="pp-thumb">' . ( $parent ? '<span class="pp-vdot">↳</span>' : wp_kses_post( $p->get_image( array( 40, 40 ) ) ) ) . '</td>';
		echo '<td data-l="محصول"><b>' . esc_html( '' !== (string) $name ? $name : '#' . $id ) . '</b>' . ( $p->get_sku() ? '<small>کد: ' . esc_html( $p->get_sku() ) . '</small>' : '' ) . '</td>';
		foreach ( array( 'regular' => array( 'قیمت اصلی', $p->get_regular_price( 'edit' ) ), 'sale' => array( 'قیمت ویژه', $p->get_sale_price( 'edit' ) ) ) as $f => $x ) {
			echo '<td data-l="' . esc_attr( $x[0] ) . '"><div class="pp-pin"><input class="pp-in ppP" type="text" inputmode="numeric" data-f="' . $f . '" data-orig="' . esc_attr( $x[1] ) . '" value="' . esc_attr( $x[1] ) . '" dir="ltr"' . ( 'sale' === $f ? ' placeholder="—"' : '' ) . ' aria-label="' . esc_attr( $x[0] ) . '"><small class="ppFmt"></small></div></td>';
		}
		echo '<td class="pp-act"><span class="pp-rmsg" aria-live="polite"></span></td></tr>';
	}

	/* ذخیره‌ی گروهی: [{id, regular, sale}] */
	public static function ajax_batch() {
		check_ajax_referer( 'pp_price', 'nonce' );
		if ( ! current_user_can( 'edit_products' ) ) { wp_send_json_error( array( 'msg' => 'دسترسی ندارید' ), 403 ); }
		$items = json_decode( wp_unslash( isset( $_POST['items'] ) ? (string) $_POST['items'] : '[]' ), true ); // phpcs:ignore -- هر مقدار پایین جداگانه پاک‌سازی می‌شود
		if ( ! is_array( $items ) || count( $items ) > 400 ) { wp_send_json_error( array( 'msg' => 'داده نامعتبر است' ), 400 ); }
		$out = array(); $parents = array(); $saved = 0;
		$num = function ( $v ) { $v = trim( Peecha_Panel::en( sanitize_text_field( (string) $v ) ) ); return '' === $v ? '' : ( is_numeric( $v ) && (float) $v >= 0 ? wc_format_decimal( $v ) : null ); };
		foreach ( $items as $it ) {
			$id = isset( $it['id'] ) ? absint( $it['id'] ) : 0;
			$p = $id ? wc_get_product( $id ) : false;
			if ( ! $p || ! in_array( $p->get_type(), array( 'simple', 'variation', 'external' ), true ) ) { $out[ $id ] = array( 'ok' => false, 'msg' => 'پیدا نشد' ); continue; }
			$check = $p->get_parent_id() ? $p->get_parent_id() : $id;
			if ( ! current_user_can( 'edit_product', $check ) ) { $out[ $id ] = array( 'ok' => false, 'msg' => 'دسترسی ندارید' ); continue; }
			$reg = array_key_exists( 'regular', $it ) ? $num( $it['regular'] ) : $p->get_regular_price( 'edit' );
			$sale = array_key_exists( 'sale', $it ) ? $num( $it['sale'] ) : $p->get_sale_price( 'edit' );
			if ( null === $reg || null === $sale ) { $out[ $id ] = array( 'ok' => false, 'msg' => 'عدد نامعتبر' ); continue; }
			if ( '' !== $sale && ( '' === $reg || (float) $sale >= (float) $reg ) ) { $out[ $id ] = array( 'ok' => false, 'msg' => 'قیمت ویژه باید کمتر از اصلی باشد' ); continue; }
			$p->set_regular_price( $reg );
			$p->set_sale_price( $sale );
			if ( '' === $sale ) { $p->set_date_on_sale_from( '' ); $p->set_date_on_sale_to( '' ); }
			$p->save();
			if ( $p->get_parent_id() ) { $parents[ $p->get_parent_id() ] = 1; }
			$saved++;
			$out[ $id ] = array( 'ok' => true, 'regular' => $p->get_regular_price( 'edit' ), 'sale' => $p->get_sale_price( 'edit' ) );
		}
		foreach ( array_keys( $parents ) as $pid ) { if ( class_exists( 'WC_Product_Variable' ) ) { WC_Product_Variable::sync( $pid ); } wc_delete_product_transients( $pid ); }
		if ( method_exists( 'Peecha_Panel', 'flush_stats' ) ) { Peecha_Panel::flush_stats(); }
		wp_send_json_success( array( 'saved' => $saved, 'items' => $out ) );
	}
}
PP_Prices::init();
