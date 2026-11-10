<?php
/**
 * Plugin Name:       Peecha License Server
 * Description:       سرور لایسنس و به‌روزرسانی قالب پیچا — فقط روی سایت فروشنده (peecha.ir) نصب شود. فهرست سایت‌هایی که قالب رویشان نصب است، کلیدهای لایسنس و انتشار نسخه‌ها.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Peecha Studio
 * License:           GPL-2.0-or-later
 * Text Domain:       peecha-license
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'PEECHA_LIC_SERVER', '1.0.0' );

final class Peecha_License_Server {

	const OPT   = 'peecha_lic_opts';
	const REL   = 'peecha_lic_releases';
	const KEYS  = 'peecha_lic_keys';
	const DBVER = '1';

	public static function init() {
		register_activation_hook( __FILE__, array( __CLASS__, 'install' ) );
		add_action( 'plugins_loaded', array( __CLASS__, 'maybe_install' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_peecha_lic', array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'pending_notice' ) );
	}

	/* ---------------- نصب ---------------- */
	public static function tables() { global $wpdb; return array( $wpdb->prefix . 'peecha_lic', $wpdb->prefix . 'peecha_site' ); }
	public static function maybe_install() { if ( get_option( 'peecha_lic_dbver' ) !== self::DBVER ) { self::install(); } }
	public static function install() {
		global $wpdb;
		list( $tl, $ts ) = self::tables();
		$cs = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE $tl (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lic_key varchar(64) NOT NULL,
			customer varchar(190) NOT NULL DEFAULT '',
			contact varchar(190) NOT NULL DEFAULT '',
			max_sites int(11) NOT NULL DEFAULT 1,
			status varchar(20) NOT NULL DEFAULT 'active',
			expires datetime NULL DEFAULT NULL,
			beta tinyint(1) NOT NULL DEFAULT 0,
			notes text NULL,
			created datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY lic_key (lic_key)
		) $cs;" );
		dbDelta( "CREATE TABLE $ts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			domain varchar(190) NOT NULL,
			lic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'pending',
			verified tinyint(1) NOT NULL DEFAULT 0,
			theme_ver varchar(20) NOT NULL DEFAULT '',
			wp_ver varchar(20) NOT NULL DEFAULT '',
			php_ver varchar(20) NOT NULL DEFAULT '',
			first_seen datetime NOT NULL,
			last_seen datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY domain (domain),
			KEY lic_id (lic_id)
		) $cs;" );
		update_option( 'peecha_lic_dbver', self::DBVER, false );
		self::keys();
		self::store_dir();
	}

	/* ---------------- کلید امضا ---------------- */
	public static function keys() {
		$k = get_option( self::KEYS );
		if ( is_array( $k ) && ! empty( $k['sk'] ) && ! empty( $k['pk'] ) && ! empty( $k['hm'] ) ) { return $k; }
		$kp = sodium_crypto_sign_keypair();
		$k  = array(
			'sk' => base64_encode( sodium_crypto_sign_secretkey( $kp ) ),
			'pk' => base64_encode( sodium_crypto_sign_publickey( $kp ) ),
			'hm' => base64_encode( random_bytes( 32 ) ),
		);
		add_option( self::KEYS, $k, '', false );
		return get_option( self::KEYS );
	}
	public static function sign( $payload ) {
		$k = self::keys();
		return base64_encode( sodium_crypto_sign_detached( $payload, base64_decode( $k['sk'] ) ) );
	}
	public static function token( $domain, $version, $exp ) {
		$k = self::keys();
		return hash_hmac( 'sha256', $domain . '|' . $version . '|' . (int) $exp, base64_decode( $k['hm'] ) );
	}
	public static function opts() {
		return wp_parse_args( (array) get_option( self::OPT, array() ), array( 'grace' => 7, 'notify' => 1, 'email' => get_option( 'admin_email' ), 'info_url' => home_url( '/' ) ) );
	}

	/* ---------------- ابزار ---------------- */
	public static function clean_domain( $d ) {
		$d = strtolower( trim( (string) $d ) );
		$d = preg_replace( '#^https?://#', '', $d );
		$d = preg_replace( '#[/:?\#].*$#', '', $d );
		$d = preg_replace( '#^www\.#', '', $d );
		if ( '' === $d || strlen( $d ) > 190 || ! preg_match( '/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/', $d ) ) { return ''; }
		return $d;
	}
	public static function is_dev_domain( $d ) {
		if ( 'localhost' === $d || false === strpos( $d, '.' ) ) { return true; }
		if ( preg_match( '/\.(local|test|localhost|invalid|example)$/', $d ) ) { return true; }
		return (bool) filter_var( $d, FILTER_VALIDATE_IP );
	}
	public static function new_key() {
		$raw = strtoupper( bin2hex( random_bytes( 10 ) ) );
		return 'PCH-' . implode( '-', str_split( $raw, 5 ) );
	}
	public static function lic_ok( $lic ) {
		if ( ! $lic || 'active' !== $lic->status ) { return false; }
		return empty( $lic->expires ) || strtotime( $lic->expires . ' UTC' ) > time();
	}

	/* ---------------- نسخه‌ها ---------------- */
	public static function store_dir() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'peecha-releases';
		if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
		if ( is_dir( $dir ) ) {
			if ( ! file_exists( $dir . '/.htaccess' ) ) { @file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" ); } // phpcs:ignore
			if ( ! file_exists( $dir . '/index.php' ) ) { @file_put_contents( $dir . '/index.php', "<?php // Silence is golden.\n" ); } // phpcs:ignore
		}
		return $dir;
	}
	public static function releases() {
		$r = get_option( self::REL, array() );
		return is_array( $r ) ? $r : array();
	}
	/* تازه‌ترین نسخه‌ی منتشرشده برای یک سایت (با درنظرگرفتن کانال و انتشار تدریجی) */
	public static function latest_for( $domain, $beta ) {
		$best = null;
		foreach ( self::releases() as $rel ) {
			if ( empty( $rel['published'] ) || empty( $rel['file'] ) ) { continue; }
			if ( 'beta' === $rel['channel'] && ! $beta ) { continue; }
			$pct = isset( $rel['rollout'] ) ? (int) $rel['rollout'] : 100;
			if ( $pct < 100 && ( hexdec( substr( md5( $domain . '|' . $rel['version'] ), 0, 6 ) ) % 100 ) >= $pct ) { continue; }
			if ( ! $best || version_compare( $rel['version'], $best['version'], '>' ) ) { $best = $rel; }
		}
		return $best;
	}

	/* ---------------- REST ---------------- */
	public static function routes() {
		register_rest_route( 'peecha/v1', '/check', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'rest_check' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( 'peecha/v1', '/download', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'rest_download' ), 'permission_callback' => '__return_true' ) );
	}
	public static function rest_check( WP_REST_Request $req ) {
		global $wpdb;
		list( $tl, $ts ) = self::tables();
		$domain = self::clean_domain( $req->get_param( 'domain' ) );
		$nonce  = preg_replace( '/[^a-f0-9]/', '', (string) $req->get_param( 'nonce' ) );
		if ( '' === $domain || strlen( $nonce ) < 16 || strlen( $nonce ) > 64 ) { return new WP_REST_Response( array( 'error' => 'bad_request' ), 400 ); }
		/* هر دامنه حداکثر هر ۲۰ ثانیه یک بار */
		$tk = 'peecha_lic_t_' . md5( $domain );
		if ( get_transient( $tk ) ) { return new WP_REST_Response( array( 'error' => 'slow_down' ), 429 ); }
		set_transient( $tk, 1, 20 );
		$key  = strtoupper( preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $req->get_param( 'key' ) ) );
		$ver  = substr( preg_replace( '/[^0-9A-Za-z\.\-]/', '', (string) $req->get_param( 'version' ) ), 0, 20 );
		$wpv  = substr( preg_replace( '/[^0-9A-Za-z\.\-]/', '', (string) $req->get_param( 'wp' ) ), 0, 20 );
		$phpv = substr( preg_replace( '/[^0-9A-Za-z\.\-]/', '', (string) $req->get_param( 'php' ) ), 0, 20 );
		$o    = self::opts();
		$now  = time();
		$status = 'pending'; $reason = ''; $grace = 0; $beta = 0; $lic = null;

		if ( self::is_dev_domain( $domain ) ) {
			$status = 'active'; $reason = 'dev';
		} else {
			$site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $ts WHERE domain = %s", $domain ) ); // phpcs:ignore
			$is_new = false;
			if ( ! $site ) {
				/* سقف ایمنی: در هر ساعت حداکثر ۶۰ دامنه‌ی تازه ثبت می‌شود */
				$cnt = (int) get_transient( 'peecha_lic_newcnt' );
				if ( $cnt >= 60 ) { return new WP_REST_Response( array( 'error' => 'busy' ), 429 ); }
				set_transient( 'peecha_lic_newcnt', $cnt + 1, HOUR_IN_SECONDS );
				$wpdb->insert( $ts, array( 'domain' => $domain, 'status' => 'pending', 'first_seen' => gmdate( 'Y-m-d H:i:s', $now ), 'last_seen' => gmdate( 'Y-m-d H:i:s', $now ), 'theme_ver' => $ver, 'wp_ver' => $wpv, 'php_ver' => $phpv ) );
				$site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $ts WHERE domain = %s", $domain ) ); // phpcs:ignore
				$is_new = true;
			}
			if ( ! $site ) { return new WP_REST_Response( array( 'error' => 'server' ), 500 ); }
			$upd = array( 'last_seen' => gmdate( 'Y-m-d H:i:s', $now ), 'theme_ver' => $ver, 'wp_ver' => $wpv, 'php_ver' => $phpv );
			/* تأیید اینکه درخواست واقعاً از همان دامنه آمده (فقط تا وقتی تأیید نشده) */
			if ( ! (int) $site->verified && self::verify_site( $domain, (string) $req->get_param( 'home' ), $nonce ) ) { $upd['verified'] = 1; $site->verified = 1; }
			if ( '' !== $key ) {
				$lic = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tl WHERE lic_key = %s", $key ) ); // phpcs:ignore
				if ( ! $lic ) { $reason = 'badkey'; }
				elseif ( 'active' !== $lic->status ) { $reason = 'revoked'; }
				elseif ( ! self::lic_ok( $lic ) ) { $reason = 'expired'; }
				elseif ( (int) $site->lic_id !== (int) $lic->id ) {
					$used = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ts WHERE lic_id = %d AND status <> 'blocked'", $lic->id ) ); // phpcs:ignore
					if ( $used < (int) $lic->max_sites ) { $upd['lic_id'] = (int) $lic->id; $site->lic_id = (int) $lic->id; } else { $reason = 'limit'; }
				}
			} elseif ( (int) $site->lic_id ) {
				$lic = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tl WHERE id = %d", $site->lic_id ) ); // phpcs:ignore
			}
			$wpdb->update( $ts, $upd, array( 'id' => $site->id ) );
			$own = ( (int) $site->lic_id && $lic && (int) $lic->id === (int) $site->lic_id ) ? $lic : ( (int) $site->lic_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $tl WHERE id = %d", $site->lic_id ) ) : null ); // phpcs:ignore
			if ( 'blocked' === $site->status ) { $status = 'blocked'; $reason = 'blocked'; }
			elseif ( 'allowed' === $site->status ) { $status = 'active'; $reason = 'manual'; }
			elseif ( $own && self::lic_ok( $own ) ) { $status = 'active'; $reason = ''; $beta = (int) $own->beta; }
			else {
				$status = 'pending';
				if ( '' === $reason && $own ) { $reason = 'active' !== $own->status ? 'revoked' : 'expired'; }
				$grace = strtotime( $site->first_seen . ' UTC' ) + max( 0, (int) $o['grace'] ) * DAY_IN_SECONDS;
			}
			if ( $is_new && (int) $site->verified && 'active' !== $status ) { self::notify_new( $domain, $ver ); }
		}

		$payload = array( 'd' => $domain, 'n' => $nonce, 't' => $now, 'u' => $now + 30 * DAY_IN_SECONDS, 's' => $status, 'r' => $reason, 'g' => (int) $grace, 'v' => '', 'c' => 0, 'l' => '', 'p' => '', 'i' => esc_url_raw( (string) $o['info_url'] ) );
		if ( 'active' === $status ) {
			$rel = self::latest_for( $domain, $beta );
			if ( $rel ) {
				$exp = $now + 7 * DAY_IN_SECONDS;
				$payload['v'] = (string) $rel['version'];
				$payload['c'] = empty( $rel['critical'] ) ? 0 : 1;
				$payload['l'] = function_exists( 'mb_substr' ) ? mb_substr( (string) $rel['changelog'], 0, 800 ) : substr( (string) $rel['changelog'], 0, 800 );
				$payload['p'] = add_query_arg( array( 'd' => rawurlencode( $domain ), 'v' => rawurlencode( $rel['version'] ), 'e' => $exp, 'h' => self::token( $domain, $rel['version'], $exp ) ), rest_url( 'peecha/v1/download' ) );
			}
		}
		$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		return new WP_REST_Response( array( 'payload' => $json, 'sig' => self::sign( $json ) ), 200 );
	}
	/* سایت باید همان عددی را که فرستاده، از نشانی خودش هم پس بدهد؛ جلوی ثبت دامنه‌ی جعلی را می‌گیرد */
	private static function verify_site( $domain, $home, $nonce ) {
		$home = esc_url_raw( $home );
		$host = self::clean_domain( $home );
		if ( '' === $home || $host !== $domain || ! preg_match( '#^https?://#', $home ) ) { return false; }
		$cnt = (int) get_transient( 'peecha_lic_vcnt' );
		if ( $cnt >= 120 ) { return false; }
		set_transient( 'peecha_lic_vcnt', $cnt + 1, HOUR_IN_SECONDS );
		$r = wp_safe_remote_get( add_query_arg( 'rest_route', '/peecha-client/v1/ping', trailingslashit( $home ) ), array( 'timeout' => 6, 'redirection' => 1, 'reject_unsafe_urls' => true, 'limit_response_size' => 2048 ) );
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) { return false; }
		$b = json_decode( wp_remote_retrieve_body( $r ), true );
		return is_array( $b ) && isset( $b['h'] ) && hash_equals( hash( 'sha256', $nonce ), (string) $b['h'] );
	}
	private static function notify_new( $domain, $ver ) {
		$o = self::opts();
		if ( empty( $o['notify'] ) || ! is_email( $o['email'] ) ) { return; }
		$cnt = (int) get_transient( 'peecha_lic_mailcnt' );
		if ( $cnt >= 20 ) { return; }
		set_transient( 'peecha_lic_mailcnt', $cnt + 1, DAY_IN_SECONDS );
		wp_mail( $o['email'], 'قالب پیچا روی یک سایت جدید نصب شد: ' . $domain, "قالب پیچا (نسخه‌ی $ver) روی این سایت نصب شده و هنوز لایسنس معتبر ندارد:\n\n$domain\n\nبرای مجاز کردن یا مسدود کردن:\n" . admin_url( 'admin.php?page=peecha-license' ) . "\n\nاگر کاری نکنید، بعد از " . (int) $o['grace'] . " روز تنظیمات و به‌روزرسانی قالب در آن سایت بسته می‌شود (خود سایت کار می‌کند)." );
	}
	public static function rest_download( WP_REST_Request $req ) {
		global $wpdb;
		list( , $ts ) = self::tables();
		$domain = self::clean_domain( rawurldecode( (string) $req->get_param( 'd' ) ) );
		$ver    = preg_replace( '/[^0-9A-Za-z\.\-]/', '', rawurldecode( (string) $req->get_param( 'v' ) ) );
		$exp    = (int) $req->get_param( 'e' );
		$h      = (string) $req->get_param( 'h' );
		if ( '' === $domain || '' === $ver || $exp < time() || ! hash_equals( self::token( $domain, $ver, $exp ), $h ) ) { return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 ); }
		if ( ! self::is_dev_domain( $domain ) ) {
			$st = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $ts WHERE domain = %s", $domain ) ); // phpcs:ignore
			if ( 'blocked' === $st ) { return new WP_REST_Response( array( 'error' => 'forbidden' ), 403 ); }
		}
		$rels = self::releases();
		if ( empty( $rels[ $ver ]['file'] ) || empty( $rels[ $ver ]['published'] ) ) { return new WP_REST_Response( array( 'error' => 'not_found' ), 404 ); }
		$file = self::store_dir() . '/' . basename( $rels[ $ver ]['file'] );
		if ( ! is_file( $file ) ) { return new WP_REST_Response( array( 'error' => 'not_found' ), 404 ); }
		self::stream( $file, 'peecha-' . $ver . '.zip' );
	}
	private static function stream( $file, $name ) {
		while ( ob_get_level() ) { ob_end_clean(); }
		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . preg_replace( '/[^A-Za-z0-9\.\-_]/', '', $name ) . '"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file ); // phpcs:ignore
		exit;
	}

	/* ---------------- بسته‌بندی نسخه: کلید عمومی و نشانی سرور داخل بسته گذاشته می‌شود ---------------- */
	private static function ingest_zip( $tmp ) {
		if ( ! class_exists( 'ZipArchive' ) ) { return new WP_Error( 'zip', 'افزونه‌ی ZipArchive روی سرور فعال نیست.' ); }
		$dir  = self::store_dir();
		$name = 'rel-' . bin2hex( random_bytes( 12 ) ) . '.zip';
		$dest = $dir . '/' . $name;
		if ( ! @move_uploaded_file( $tmp, $dest ) ) { return new WP_Error( 'move', 'ذخیره‌ی فایل ممکن نشد.' ); } // phpcs:ignore
		$zip = new ZipArchive();
		if ( true !== $zip->open( $dest ) ) { @unlink( $dest ); return new WP_Error( 'zip', 'فایل zip معتبر نیست.' ); } // phpcs:ignore
		$root = '';
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$n = $zip->getNameIndex( $i );
			if ( false !== strpos( $n, '..' ) ) { $zip->close(); @unlink( $dest ); return new WP_Error( 'zip', 'مسیر نامعتبر در بسته.' ); } // phpcs:ignore
			if ( preg_match( '#^([^/]+)/style\.css$#', $n, $m ) ) { $root = $m[1]; }
		}
		$css = $root ? $zip->getFromName( $root . '/style.css' ) : false;
		if ( ! $root || false === $css || false === $zip->locateName( $root . '/functions.php' ) || ! preg_match( '/^\s*Version:\s*([0-9][0-9A-Za-z\.\-]*)\s*$/mi', $css, $vm ) ) {
			$zip->close(); @unlink( $dest ); // phpcs:ignore
			return new WP_Error( 'zip', 'این بسته قالب نیست (style.css با سطر Version و functions.php پیدا نشد).' );
		}
		$k   = self::keys();
		$php = "<?php\nif ( ! defined( 'ABSPATH' ) ) { exit; }\n/* این فایل را سرور لایسنس پیچا هنگام بسته‌بندی نسخه می‌سازد. */\nreturn array( 'server' => " . var_export( untrailingslashit( home_url() ), true ) . ", 'pub' => " . var_export( $k['pk'], true ) . " );\n";
		$zip->addFromString( $root . '/license-key.php', $php );
		$zip->close();
		return array( 'file' => $name, 'version' => $vm[1], 'size' => filesize( $dest ), 'sha' => hash_file( 'sha256', $dest ) );
	}

	/* ---------------- مدیریت ---------------- */
	public static function menu() {
		add_menu_page( 'لایسنس پیچا', 'لایسنس پیچا', 'manage_options', 'peecha-license', array( __CLASS__, 'page' ), 'dashicons-admin-network', 58 );
	}
	public static function pending_notice() {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		list( , $ts ) = self::tables();
		$n = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $ts WHERE status = 'pending' AND lic_id = 0 AND verified = 1" ); // phpcs:ignore
		if ( $n && ( empty( $_GET['page'] ) || 'peecha-license' !== $_GET['page'] ) ) { // phpcs:ignore
			echo '<div class="notice notice-warning"><p>قالب پیچا روی <b>' . (int) $n . '</b> سایتِ بدون لایسنس نصب شده است. <a href="' . esc_url( admin_url( 'admin.php?page=peecha-license' ) ) . '">بررسی کنید</a></p></div>';
		}
	}
	private static function back( $tab, $msg = '' ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'peecha-license', 'tab' => $tab, 'msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
		exit;
	}
	public static function handle() {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی ندارید.' ); }
		check_admin_referer( 'peecha_lic' );
		list( $tl, $ts ) = self::tables();
		$do = isset( $_POST['do'] ) ? sanitize_key( $_POST['do'] ) : '';
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		switch ( $do ) {
			case 'site_allow': $wpdb->update( $ts, array( 'status' => 'allowed' ), array( 'id' => $id ) ); self::back( 'sites', 'سایت مجاز شد.' );
			case 'site_block': $wpdb->update( $ts, array( 'status' => 'blocked' ), array( 'id' => $id ) ); self::back( 'sites', 'سایت مسدود شد.' );
			case 'site_reset': $wpdb->update( $ts, array( 'status' => 'pending' ), array( 'id' => $id ) ); self::back( 'sites', 'وضعیت سایت به «در انتظار» برگشت.' );
			case 'site_unlink': $wpdb->update( $ts, array( 'lic_id' => 0 ), array( 'id' => $id ) ); self::back( 'sites', 'سایت از لایسنس جدا شد.' );
			case 'site_delete': $wpdb->delete( $ts, array( 'id' => $id ) ); self::back( 'sites', 'سایت از فهرست حذف شد.' );
			case 'lic_add':
				$exp = isset( $_POST['expires'] ) ? sanitize_text_field( wp_unslash( $_POST['expires'] ) ) : '';
				$wpdb->insert( $tl, array(
					'lic_key'   => self::new_key(),
					'customer'  => isset( $_POST['customer'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['customer'] ) ), 0, 190 ) : '',
					'contact'   => isset( $_POST['contact'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['contact'] ) ), 0, 190 ) : '',
					'max_sites' => isset( $_POST['max_sites'] ) ? max( 1, min( 1000, absint( $_POST['max_sites'] ) ) ) : 1,
					'beta'      => empty( $_POST['beta'] ) ? 0 : 1,
					'expires'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $exp ) ? $exp . ' 23:59:59' : null,
					'notes'     => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '',
					'created'   => gmdate( 'Y-m-d H:i:s' ),
				) );
				self::back( 'lics', 'لایسنس ساخته شد.' );
			case 'lic_revoke': $wpdb->update( $tl, array( 'status' => 'revoked' ), array( 'id' => $id ) ); self::back( 'lics', 'لایسنس لغو شد.' );
			case 'lic_restore': $wpdb->update( $tl, array( 'status' => 'active' ), array( 'id' => $id ) ); self::back( 'lics', 'لایسنس دوباره فعال شد.' );
			case 'lic_sites':
				$wpdb->update( $tl, array( 'max_sites' => isset( $_POST['max_sites'] ) ? max( 1, min( 1000, absint( $_POST['max_sites'] ) ) ) : 1 ), array( 'id' => $id ) );
				self::back( 'lics', 'سقف سایت‌ها ذخیره شد.' );
			case 'rel_add':
				if ( empty( $_FILES['zip']['tmp_name'] ) || ! is_uploaded_file( $_FILES['zip']['tmp_name'] ) || 'zip' !== strtolower( pathinfo( (string) $_FILES['zip']['name'], PATHINFO_EXTENSION ) ) ) { self::back( 'rels', 'فایل zip قالب را انتخاب کنید.' ); } // phpcs:ignore
				if ( (int) $_FILES['zip']['size'] > 60 * MB_IN_BYTES ) { self::back( 'rels', 'حجم فایل بیش از ۶۰ مگابایت است.' ); }
				$r = self::ingest_zip( $_FILES['zip']['tmp_name'] ); // phpcs:ignore
				if ( is_wp_error( $r ) ) { self::back( 'rels', $r->get_error_message() ); }
				$rels = self::releases();
				if ( ! empty( $rels[ $r['version'] ]['file'] ) ) { @unlink( self::store_dir() . '/' . basename( $rels[ $r['version'] ]['file'] ) ); } // phpcs:ignore
				$rels[ $r['version'] ] = array(
					'version' => $r['version'], 'file' => $r['file'], 'size' => $r['size'], 'sha' => $r['sha'],
					'changelog' => isset( $_POST['changelog'] ) ? sanitize_textarea_field( wp_unslash( $_POST['changelog'] ) ) : '',
					'channel' => ( isset( $_POST['channel'] ) && 'beta' === $_POST['channel'] ) ? 'beta' : 'stable',
					'published' => 0, 'rollout' => 100, 'critical' => empty( $_POST['critical'] ) ? 0 : 1, 'date' => gmdate( 'Y-m-d H:i:s' ),
				);
				uksort( $rels, function ( $a, $b ) { return version_compare( $b, $a ); } );
				update_option( self::REL, $rels, false );
				self::back( 'rels', 'نسخه‌ی ' . $r['version'] . ' بارگذاری شد (هنوز منتشر نشده).' );
			case 'rel_update': case 'rel_delete': case 'rel_get':
				$v = isset( $_POST['version'] ) ? preg_replace( '/[^0-9A-Za-z\.\-]/', '', (string) wp_unslash( $_POST['version'] ) ) : '';
				$rels = self::releases();
				if ( ! isset( $rels[ $v ] ) ) { self::back( 'rels', 'نسخه پیدا نشد.' ); }
				if ( 'rel_get' === $do ) {
					$f = self::store_dir() . '/' . basename( $rels[ $v ]['file'] );
					if ( is_file( $f ) ) { self::stream( $f, 'peecha-' . $v . '.zip' ); }
					self::back( 'rels', 'فایل نسخه پیدا نشد.' );
				}
				if ( 'rel_delete' === $do ) {
					@unlink( self::store_dir() . '/' . basename( $rels[ $v ]['file'] ) ); // phpcs:ignore
					unset( $rels[ $v ] ); update_option( self::REL, $rels, false ); self::back( 'rels', 'نسخه حذف شد.' );
				}
				$rels[ $v ]['published'] = empty( $_POST['published'] ) ? 0 : 1;
				$rels[ $v ]['rollout']   = isset( $_POST['rollout'] ) ? max( 1, min( 100, absint( $_POST['rollout'] ) ) ) : 100;
				$rels[ $v ]['critical']  = empty( $_POST['critical'] ) ? 0 : 1;
				$rels[ $v ]['channel']   = ( isset( $_POST['channel'] ) && 'beta' === $_POST['channel'] ) ? 'beta' : 'stable';
				if ( isset( $_POST['changelog'] ) ) { $rels[ $v ]['changelog'] = sanitize_textarea_field( wp_unslash( $_POST['changelog'] ) ); }
				update_option( self::REL, $rels, false );
				self::back( 'rels', 'نسخه‌ی ' . $v . ' ذخیره شد.' );
			case 'opts':
				$em = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				update_option( self::OPT, array(
					'grace'    => isset( $_POST['grace'] ) ? max( 0, min( 90, absint( $_POST['grace'] ) ) ) : 7,
					'notify'   => empty( $_POST['notify'] ) ? 0 : 1,
					'email'    => is_email( $em ) ? $em : get_option( 'admin_email' ),
					'info_url' => isset( $_POST['info_url'] ) ? esc_url_raw( wp_unslash( $_POST['info_url'] ) ) : home_url( '/' ),
				), false );
				self::back( 'opts', 'تنظیمات ذخیره شد.' );
		}
		self::back( 'sites' );
	}
	private static function btn( $do, $label, $fields = array(), $class = 'button button-small', $confirm = '' ) {
		$h = '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline"' . ( $confirm ? ' onsubmit="return confirm(\'' . esc_js( $confirm ) . '\')"' : '' ) . '>' . wp_nonce_field( 'peecha_lic', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="peecha_lic"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
		foreach ( $fields as $k => $v ) { $h .= '<input type="hidden" name="' . esc_attr( $k ) . '" value="' . esc_attr( $v ) . '">'; }
		return $h . '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form> ';
	}
	private static function form_open( $do, $multipart = false ) {
		return '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $multipart ? ' enctype="multipart/form-data"' : '' ) . '>' . wp_nonce_field( 'peecha_lic', '_wpnonce', true, false ) . '<input type="hidden" name="action" value="peecha_lic"><input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
	}
	public static function page() {
		global $wpdb;
		if ( ! current_user_can( 'manage_options' ) ) { return; }
		list( $tl, $ts ) = self::tables();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'sites'; // phpcs:ignore
		$tabs = array( 'sites' => 'سایت‌ها', 'lics' => 'لایسنس‌ها', 'rels' => 'نسخه‌ها و به‌روزرسانی', 'opts' => 'تنظیمات' );
		if ( ! isset( $tabs[ $tab ] ) ) { $tab = 'sites'; }
		echo '<div class="wrap"><h1>لایسنس و به‌روزرسانی قالب پیچا</h1>';
		if ( ! empty( $_GET['msg'] ) ) { echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sanitize_text_field( rawurldecode( wp_unslash( $_GET['msg'] ) ) ) ) . '</p></div>'; } // phpcs:ignore
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $k => $l ) { echo '<a class="nav-tab' . ( $k === $tab ? ' nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'admin.php?page=peecha-license&tab=' . $k ) ) . '">' . esc_html( $l ) . '</a>'; }
		echo '</h2><div style="margin-top:14px">';
		$o = self::opts();

		if ( 'sites' === $tab ) {
			$rows = $wpdb->get_results( "SELECT s.*, l.lic_key, l.customer, l.status AS lic_status, l.expires FROM $ts s LEFT JOIN $tl l ON l.id = s.lic_id ORDER BY (s.status = 'pending' AND s.lic_id = 0) DESC, s.last_seen DESC LIMIT 500" ); // phpcs:ignore
			echo '<p>هر سایتی که قالب رویش فعال شود اینجا ثبت می‌شود. سایتِ بدون لایسنس ' . (int) $o['grace'] . ' روز مهلت دارد؛ بعد از آن فقط تنظیمات و به‌روزرسانی قالب در آن بسته می‌شود.</p>';
			echo '<table class="widefat striped"><thead><tr><th>دامنه</th><th>وضعیت</th><th>لایسنس</th><th>نسخه‌ی قالب</th><th>اولین بار</th><th>آخرین تماس</th><th>کارها</th></tr></thead><tbody>';
			if ( ! $rows ) { echo '<tr><td colspan="7">هنوز سایتی ثبت نشده است.</td></tr>'; }
			foreach ( (array) $rows as $r ) {
				$lic_ok = $r->lic_id && 'active' === $r->lic_status && ( empty( $r->expires ) || strtotime( $r->expires . ' UTC' ) > time() );
				if ( 'blocked' === $r->status ) { $st = '<span style="color:#b32d2e">⛔ مسدود</span>'; }
				elseif ( 'allowed' === $r->status ) { $st = '<span style="color:#00a32a">✔ مجاز (دستی)</span>'; }
				elseif ( $lic_ok ) { $st = '<span style="color:#00a32a">✔ لایسنس معتبر</span>'; }
				else {
					$left = (int) ceil( ( strtotime( $r->first_seen . ' UTC' ) + (int) $o['grace'] * DAY_IN_SECONDS - time() ) / DAY_IN_SECONDS );
					$st = '<span style="color:#996800">⏳ بدون لایسنس — ' . ( $left > 0 ? (int) $left . ' روز مهلت' : 'محدود شده' ) . '</span>';
				}
				if ( ! (int) $r->verified ) { $st .= '<br><small style="color:#646970">دامنه تأیید نشده</small>'; }
				echo '<tr><td><a href="' . esc_url( 'https://' . $r->domain ) . '" target="_blank" rel="noopener noreferrer"><b>' . esc_html( $r->domain ) . '</b></a></td><td>' . $st . '</td>'; // phpcs:ignore
				echo '<td>' . ( $r->lic_id ? '<code>' . esc_html( $r->lic_key ) . '</code><br><small>' . esc_html( $r->customer ) . '</small>' : '—' ) . '</td>';
				echo '<td>' . esc_html( $r->theme_ver ) . '<br><small>WP ' . esc_html( $r->wp_ver ) . ' · PHP ' . esc_html( $r->php_ver ) . '</small></td><td>' . esc_html( get_date_from_gmt( $r->first_seen, 'Y/m/d' ) ) . '</td><td>' . esc_html( get_date_from_gmt( $r->last_seen, 'Y/m/d H:i' ) ) . '</td><td>';
				if ( 'allowed' !== $r->status ) { echo self::btn( 'site_allow', '✔ مجاز است', array( 'id' => $r->id ), 'button button-small button-primary' ); } // phpcs:ignore
				if ( 'blocked' !== $r->status ) { echo self::btn( 'site_block', 'مسدود', array( 'id' => $r->id ), 'button button-small', 'تنظیمات و به‌روزرسانی قالب در این سایت بسته می‌شود. ادامه؟' ); } // phpcs:ignore
				if ( 'pending' !== $r->status ) { echo self::btn( 'site_reset', 'برگشت به حالت عادی', array( 'id' => $r->id ) ); } // phpcs:ignore
				if ( $r->lic_id ) { echo self::btn( 'site_unlink', 'جدا از لایسنس', array( 'id' => $r->id ) ); } // phpcs:ignore
				echo self::btn( 'site_delete', 'حذف', array( 'id' => $r->id ), 'button button-small button-link-delete', 'این سایت از فهرست حذف شود؟' ); // phpcs:ignore
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		} elseif ( 'lics' === $tab ) {
			echo '<h2>ساخت لایسنس جدید</h2>' . self::form_open( 'lic_add' ) // phpcs:ignore
				. '<table class="form-table"><tr><th>نام مشتری</th><td><input type="text" name="customer" class="regular-text" required></td></tr>'
				. '<tr><th>تماس (موبایل یا ایمیل)</th><td><input type="text" name="contact" class="regular-text"></td></tr>'
				. '<tr><th>تعداد سایت مجاز</th><td><input type="number" name="max_sites" value="1" min="1" max="1000" class="small-text"></td></tr>'
				. '<tr><th>تاریخ پایان (میلادی، اختیاری)</th><td><input type="date" name="expires"> <span class="description">خالی = بدون تاریخ پایان</span></td></tr>'
				. '<tr><th>نسخه‌های آزمایشی</th><td><label><input type="checkbox" name="beta" value="1"> این مشتری نسخه‌های کانال «آزمایشی» را هم دریافت کند</label></td></tr>'
				. '<tr><th>یادداشت</th><td><textarea name="notes" rows="2" class="large-text"></textarea></td></tr></table><p><button class="button button-primary">ساخت کلید</button></p></form>';
			$rows = $wpdb->get_results( "SELECT l.*, (SELECT COUNT(*) FROM $ts s WHERE s.lic_id = l.id AND s.status <> 'blocked') AS used FROM $tl l ORDER BY l.id DESC LIMIT 500" ); // phpcs:ignore
			echo '<h2>لایسنس‌ها</h2><table class="widefat striped"><thead><tr><th>کلید</th><th>مشتری</th><th>سایت‌ها</th><th>وضعیت</th><th>پایان</th><th>کارها</th></tr></thead><tbody>';
			if ( ! $rows ) { echo '<tr><td colspan="6">هنوز لایسنسی ساخته نشده است.</td></tr>'; }
			foreach ( (array) $rows as $r ) {
				$ok = self::lic_ok( $r );
				echo '<tr><td><code style="user-select:all">' . esc_html( $r->lic_key ) . '</code></td><td>' . esc_html( $r->customer ) . '<br><small>' . esc_html( $r->contact ) . '</small></td>';
				echo '<td>' . (int) $r->used . ' از ' . self::form_open( 'lic_sites' ) . '<input type="hidden" name="id" value="' . (int) $r->id . '"><input type="number" name="max_sites" value="' . (int) $r->max_sites . '" min="1" max="1000" class="small-text"> <button class="button button-small">ذخیره</button></form></td>'; // phpcs:ignore
				echo '<td>' . ( 'active' !== $r->status ? '<span style="color:#b32d2e">لغو شده</span>' : ( $ok ? '<span style="color:#00a32a">فعال</span>' : '<span style="color:#996800">منقضی</span>' ) ) . ( (int) $r->beta ? ' · آزمایشی' : '' ) . '</td><td>' . ( $r->expires ? esc_html( substr( $r->expires, 0, 10 ) ) : '—' ) . '</td><td>';
				echo 'active' === $r->status ? self::btn( 'lic_revoke', 'لغو', array( 'id' => $r->id ), 'button button-small', 'این لایسنس لغو شود؟ سایت‌های آن بعد از مهلت محدود می‌شوند.' ) : self::btn( 'lic_restore', 'فعال‌سازی دوباره', array( 'id' => $r->id ) ); // phpcs:ignore
				echo '</td></tr>';
			}
			echo '</tbody></table>';
		} elseif ( 'rels' === $tab ) {
			echo '<h2>بارگذاری نسخه‌ی جدید</h2><p>فایل zip قالب را بارگذاری کنید. شماره‌ی نسخه از سطر <code>Version</code> در <code>style.css</code> خوانده می‌شود و کلید عمومی و نشانی همین سرور خودکار داخل بسته گذاشته می‌شود. <b>برای فروش هم همین بسته‌ی ساخته‌شده را به مشتری بدهید</b> (دکمه‌ی «دریافت بسته»).</p>';
			echo self::form_open( 'rel_add', true ) . '<table class="form-table"><tr><th>فایل قالب (zip)</th><td><input type="file" name="zip" accept=".zip" required></td></tr>' // phpcs:ignore
				. '<tr><th>تغییرات این نسخه</th><td><textarea name="changelog" rows="4" class="large-text" placeholder="هر خط یک تغییر"></textarea></td></tr>'
				. '<tr><th>کانال</th><td><select name="channel"><option value="stable">پایدار (همه)</option><option value="beta">آزمایشی (فقط لایسنس‌های آزمایشی)</option></select></td></tr>'
				. '<tr><th>به‌روزرسانی مهم</th><td><label><input type="checkbox" name="critical" value="1"> امنیتی یا ضروری (در سایت مشتری برجسته نمایش داده می‌شود)</label></td></tr></table><p><button class="button button-primary">بارگذاری</button></p></form>';
			$rels = self::releases();
			echo '<h2>نسخه‌ها</h2><p>سایت‌های مجاز تازه‌ترین نسخه‌ی «منتشرشده» را می‌بینند. برای برگرداندن یک نسخه‌ی مشکل‌دار، تیک «منتشر شده» را بردارید تا نسخه‌ی قبلی دوباره آخرین نسخه شود. با «انتشار تدریجی» نسخه اول فقط به درصدی از سایت‌ها می‌رسد.</p>';
			echo '<table class="widefat striped"><thead><tr><th>نسخه</th><th>تاریخ</th><th>تنظیم انتشار</th><th>تغییرات</th><th></th></tr></thead><tbody>';
			if ( ! $rels ) { echo '<tr><td colspan="5">هنوز نسخه‌ای بارگذاری نشده است.</td></tr>'; }
			foreach ( $rels as $v => $r ) {
				echo '<tr><td><b>' . esc_html( $v ) . '</b><br><small>' . esc_html( size_format( (int) $r['size'] ) ) . '</small></td><td>' . esc_html( get_date_from_gmt( $r['date'], 'Y/m/d' ) ) . '</td><td>'
					. self::form_open( 'rel_update' ) . '<input type="hidden" name="version" value="' . esc_attr( $v ) . '">' // phpcs:ignore
					. '<label><input type="checkbox" name="published" value="1"' . checked( ! empty( $r['published'] ), true, false ) . '> منتشر شده</label><br>'
					. '<label>انتشار تدریجی <input type="number" name="rollout" value="' . (int) $r['rollout'] . '" min="1" max="100" class="small-text">٪</label><br>'
					. '<select name="channel"><option value="stable"' . selected( $r['channel'], 'stable', false ) . '>پایدار</option><option value="beta"' . selected( $r['channel'], 'beta', false ) . '>آزمایشی</option></select> '
					. '<label><input type="checkbox" name="critical" value="1"' . checked( ! empty( $r['critical'] ), true, false ) . '> مهم</label></td>'
					. '<td><textarea name="changelog" rows="3" style="width:100%">' . esc_textarea( $r['changelog'] ) . '</textarea><br><button class="button button-small button-primary">ذخیره</button></form></td><td>'
					. self::btn( 'rel_get', '⬇ دریافت بسته', array( 'version' => $v ) ) . self::btn( 'rel_delete', 'حذف', array( 'version' => $v ), 'button button-small button-link-delete', 'این نسخه حذف شود؟' ) . '</td></tr>'; // phpcs:ignore
			}
			echo '</tbody></table>';
		} else {
			$k = self::keys();
			echo self::form_open( 'opts' ) . '<table class="form-table">' // phpcs:ignore
				. '<tr><th>مهلت سایتِ بدون لایسنس (روز)</th><td><input type="number" name="grace" value="' . (int) $o['grace'] . '" min="0" max="90" class="small-text"> <span class="description">بعد از این مدت، تنظیمات و به‌روزرسانی قالب در آن سایت بسته می‌شود؛ خود سایت و خرید مشتریانش کار می‌کند.</span></td></tr>'
				. '<tr><th>پیام نصب جدید</th><td><label><input type="checkbox" name="notify" value="1"' . checked( ! empty( $o['notify'] ), true, false ) . '> با نصب قالب روی سایت جدیدِ بدون لایسنس ایمیل بیاید</label><br><input type="email" name="email" value="' . esc_attr( $o['email'] ) . '" class="regular-text" dir="ltr"></td></tr>'
				. '<tr><th>نشانی صفحه‌ی خرید لایسنس</th><td><input type="url" name="info_url" value="' . esc_attr( $o['info_url'] ) . '" class="regular-text" dir="ltr"> <span class="description">در سایت مشتری به‌عنوان «خرید لایسنس» و «جزئیات نسخه» لینک می‌شود.</span></td></tr>'
				. '</table><p><button class="button button-primary">ذخیره</button></p></form>';
			echo '<h2>کلید عمومی امضا</h2><p>پاسخ‌های این سرور با کلید خصوصی امضا می‌شوند و قالب با این کلید عمومی درستی‌شان را می‌سنجد. این کلید خودکار داخل بسته‌ی هر نسخه گذاشته می‌شود و لازم نیست دستی کاری بکنید. <b>از پایگاه‌داده‌ی این سایت نسخه‌ی پشتیبان بگیرید</b>؛ اگر کلید خصوصی از بین برود، سایت‌های نصب‌شده دیگر پاسخ سرور را نمی‌پذیرند.</p><p><code style="user-select:all" dir="ltr">' . esc_html( $k['pk'] ) . '</code></p>';
			echo '<p>نشانی بررسی لایسنس: <code dir="ltr">' . esc_html( rest_url( 'peecha/v1/check' ) ) . '</code></p>';
		}
		echo '</div></div>';
	}
}
Peecha_License_Server::init();
