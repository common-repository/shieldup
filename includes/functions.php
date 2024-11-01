<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Displays error, success, info messages
function shieldup_message_resultBlock($su_errors,$su_successes,$su_infos){
	// Error block
	if(is_countable($su_errors) && count($su_errors) > 0){
		echo '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
		<button type="button" class="btn-close"  data-bs-dismiss="alert" aria-label="Close"></button>';
		foreach($su_errors as $su_error){
			echo "<div>-&nbsp; ".esc_html($su_error)."</div>";
		}
		echo "</div>";
	}
	// Success block
	if(is_countable($su_successes) && count($su_successes) > 0){
		echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
		foreach($su_successes as $su_success){
			echo "<div>-&nbsp; ".esc_html($su_success)."</div>";
		}
		echo "</div>";
	}
	// Info block
	if(is_countable($su_infos) && count($su_infos) > 0){
		echo '<div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
		<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
		foreach($su_infos as $su_info){
			echo "<div>-&nbsp; ".esc_html($su_info)."</div>";
		}
		echo "</div>";
	}
}

function shieldup_get_version(){
	echo '<span class="badge bg-secondary">'.esc_html(SHIELD_UP_PLUGIN_TYPE).' Ver. '.esc_html(SHIELD_UP_VERSION).'</span>';
}

function shieldup_check_main_settings() {
	$su_msg = array();
	if (defined("DISABLE_WP_CRON")) {
		if (constant("DISABLE_WP_CRON") == true){
			$su_msg[] = "Your WP Cron Jobs are disabled, in order for this plugin to work properly, please set DISABLE_WP_CRON to 'false' in wp-config.php";
			shieldup_1_hour_cron();
		}
	}
	return $su_msg;
}

function shieldup_db_install() {
 	global $wpdb;

 	$sql = "CREATE TABLE ".$wpdb->prefix."su_access (
    id int(11) NOT NULL AUTO_INCREMENT,
    ip varchar(100) NOT NULL DEFAULT '',
    proxy_ip varchar(100) NOT NULL DEFAULT '',
    http_code varchar(10) NOT NULL DEFAULT '',
    url varchar(512) NOT NULL DEFAULT '',
    possible_bot varchar(100) NOT NULL DEFAULT '',
    user_agent varchar(255) NOT NULL DEFAULT '',
    referer varchar(512) NOT NULL DEFAULT '',
    timestamp timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY  (id),
    KEY ip (ip)
 	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
 	dbDelta( $sql );

  $sql = "CREATE TABLE ".$wpdb->prefix."su_access_archive (
    id int(9) NOT NULL AUTO_INCREMENT,
    ip varchar(100) NOT NULL DEFAULT '',
    200_count int(6) NOT NULL DEFAULT 0,
    404_count int(6) NOT NULL DEFAULT 0,
    possible_bot varchar(100) NOT NULL DEFAULT '',
    user_agent varchar(255) NOT NULL DEFAULT '',
    date date NOT NULL DEFAULT '0000-00-00',
    PRIMARY KEY  (id),
    KEY date (date),
    KEY ip (ip)
 	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  // require_once ABSPATH . 'wp-admin/includes/upgrade.php';
 	dbDelta( $sql );

  $sql = "CREATE TABLE ".$wpdb->prefix."su_date_stats (
    id int(9) NOT NULL AUTO_INCREMENT,
    date date NOT NULL DEFAULT '0000-00-00',
    200_count int(6) NOT NULL DEFAULT 0,
    404_count int(6) NOT NULL DEFAULT 0,
    PRIMARY KEY  (id)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
  // require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( $sql );

  $sql =  "CREATE TABLE ".$wpdb->prefix."su_settings (
    option_id int(9) NOT NULL AUTO_INCREMENT,
    option_group varchar(50) NOT NULL DEFAULT '',
    option_name varchar(150) NOT NULL DEFAULT '',
    option_value text NOT NULL,
    PRIMARY KEY  (option_id),
    UNIQUE KEY option_name (option_name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
  // require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta( $sql );

  $su_settings_init_data = array(
      array(1, 'cloudflare', 'cf_email', ''),
      array(2, 'cloudflare', 'cf_zone_id', ''),
      array(3, 'cloudflare', 'cf_api_key', ''),
      array(4, 'shieldup', 'su_api_key', ''),
      array(5, 'shieldup', 'su_acc_type', '0'),
      array(6, 'shieldup', 'su_membership_level', '0'),
      array(7, 'shieldup', 'su_membership_valid_date', '0000-00-00'),
			array(8, 'shieldup', 'su_max_rows_in_tables', '1000'),
      array(9, 'shieldup', 'su_access_log_delete_days', '30'),
			array(10, 'shieldup', 'su_remove_rule_after_days', '0'),
			array(11, 'shieldup', 'su_archive_log_delete_days', '730'),
      array(12, 'shieldup', 'su_version', '.SHIELD_UP_VERSION.'),
      array(13, 'shieldup', 'su_db_version', '.SHIELD_UP_DB_VERSION.'),
      array(14, 'shieldup', 'su_drop_tables', '0')
  );

  foreach ($su_settings_init_data as $row) {
			$wpdb->insert(
			    $wpdb->prefix . 'su_settings',
			    array(
			        'option_id'    => $row[0],
			        'option_group' => $row[1],
			        'option_name'  => $row[2],
			        'option_value' => $row[3],
			    ),
			    array(
			        '%d',
			        '%s',
			        '%s',
			        '%s',
			    )
			);
  }
}

function shieldup_db_uninstall() {
 	global $wpdb;

  $su_drop_tables = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_drop_tables'");

  if ($su_drop_tables == 1){
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "su_access");

		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "su_access_archive");

		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "su_date_stats");

		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "su_settings");
  }

  // remove cron jobs
  if (wp_next_scheduled ( 'shieldup_cron_hourly' )) {
      wp_clear_scheduled_hook( 'shieldup_cron_hourly' );
  }
}

function shieldup_get_access_stats() {

  	global $wpdb;

    // get user agent
    if (!empty($_SERVER['HTTP_USER_AGENT'])) {
        $user_agent = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
    }else{
       $user_agent = '';
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
      $referer = sanitize_url($_SERVER['HTTP_REFERER']);
    }else{
       $referer = '';
    }

		if (isset($_SERVER['HTTPS']) &&
		    ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
		    isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		    $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
		  	$protocol = 'https://';
		}else{
		   $protocol = 'http://';
		}
    if (!empty($_SERVER['REQUEST_URI'])) {
    	$page_url = $protocol . sanitize_url($_SERVER['HTTP_HOST']) . sanitize_url($_SERVER['REQUEST_URI']);
    }else{
      $page_url = '';
    }

    // Handle IPs
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			$visitor_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
		}else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$visitor_ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
		}else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$visitor_ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
		}else if(isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])) {
			$visitor_ip =sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
		}else if(isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])) {
			$visitor_ip = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
		}else if(isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])) {
			$visitor_ip = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
		}else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
			$visitor_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }else{
      $visitor_ip = '';
    }

    if (!empty($_SERVER['REMOTE_ADDR'])) {
      $proxy_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }else{
      $proxy_ip = '';
    }

    // parse IP string if more than one IP
  	if(preg_match('/,/i', $visitor_ip)){
  		$arr_ips = explode(",", $visitor_ip);
  		$visitor_ip = $arr_ips[0];
  	}

    // parse IP string if more than one IP
  	if(preg_match('/,/i', $proxy_ip)){
  		$arr_ips = explode(",", $proxy_ip);
  		$proxy_ip = $arr_ips[0];
  	}

		// validate IP
		if (!filter_var($visitor_ip, FILTER_VALIDATE_IP,
 												FILTER_FLAG_IPV4 |
 												FILTER_FLAG_IPV6 |
 												FILTER_FLAG_NO_PRIV_RANGE |
 												FILTER_FLAG_NO_RES_RANGE)){
		  // echo 'Not Valid IP';
			$visitor_ip = '';
		}


    // get possible bot
    $possible_bot = '';
    if ( current_user_can( 'manage_options' ) ) {
      $possible_bot = "My IP";
    }else{
			include_once(SHIELD_UP_ABS_PATH.'/includes/possible_agents.php');
      $pattern = '/(' . implode('|', $agents_to_check) . ')/i'; // $pattern = /(one|two|three)/

      if(preg_match($pattern, $user_agent, $matches)){
         $possible_bot = $matches[0];
      }
    }

  $http_code = http_response_code();
  $current_date_time  = date('Y-m-d H:i:s');

  if ($visitor_ip != ''){
    $su_db_result = $wpdb->insert( $wpdb->prefix .'su_access',
      array(
        'ip' => $visitor_ip,
        'proxy_ip' => $proxy_ip,
        'http_code' => $http_code,
        'url' => $page_url,
        'possible_bot' => $possible_bot,
        'user_agent' => $user_agent,
        'referer' => $referer,
        'timestamp' => $current_date_time
      ),
      array(
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
      )
    );

  }
}

function shieldup_get_stuff(){
  if (current_user_can( 'manage_options' )){
    if (is_admin()){
      add_action( 'admin_head', 'shieldup_get_access_stats' );
    }else{
      add_action( 'wp_head', 'shieldup_get_access_stats');
    }
  }else{
    add_action( 'wp_head', 'shieldup_get_access_stats');
  }
}

function shieldup_db_update(){
	global $wpdb;

	$su_version = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_version'");
	$su_db_version = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_db_version'");

	if (version_compare($su_version, SHIELD_UP_VERSION, '<>')){
			$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_version'", SHIELD_UP_VERSION));
	}
	if (version_compare($su_db_version, SHIELD_UP_DB_VERSION, '<>')) {
			$wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_db_version'", SHIELD_UP_DB_VERSION));
	}
}

// archive ips by date
function shieldup_archive_ips() {
	global $wpdb;

	$su_wp_last_date = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_access_archive order by date desc" );
	$yesterday = date('Y-m-d',strtotime("-1 days"));

	if ($yesterday > $su_wp_last_date){
		if ($su_wp_last_date > ''){
			$start_date = new DateTime($su_wp_last_date); // For today/now, don't pass an arg.
			$start_date->modify("+1 day");
			$start_date = $start_date->format("Y-m-d");
			$start_time = $start_date .' 00:00:00';
		}else{
			$start_time = '2023-01-01 00:00:00';
		}

		$end_time = $yesterday.' 23:59:59';
		$su_wp_ip_details = $wpdb->get_results($wpdb->prepare("SELECT ip, user_agent, possible_bot, DATE(timestamp) AS date, SUM(CASE WHEN http_code = '200' THEN 1 ELSE 0 END) AS `200_count`, SUM(CASE WHEN http_code = '404' THEN 1 ELSE 0 END) AS `404_count` FROM " . $wpdb->prefix . "su_access WHERE timestamp BETWEEN %s AND %s GROUP BY date, ip", $start_time, $end_time), ARRAY_A);

		$date_counter = '';
		foreach($su_wp_ip_details as $key => $su_ip_detail){
				$su_db_result = $wpdb->insert( $wpdb->prefix .'su_access_archive',
					array(
						'ip' => $su_ip_detail['ip'],
						'200_count' => $su_ip_detail['200_count'],
						'404_count' => $su_ip_detail['404_count'],
						'possible_bot' => $su_ip_detail['possible_bot'],
						'user_agent' => $su_ip_detail['user_agent'],
						'date' => $su_ip_detail['date']
					),
					array(
						'%s',
						'%d',
						'%d',
						'%s',
						'%s'
					)
				);
		}
  }
}

// archive and create daily requests stats
function shieldup_archive_create_stats() {
	global $wpdb;

	$su_wp_last_date = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_date_stats order by date desc" );
	$yesterday = date('Y-m-d',strtotime("-1 days"));

	if ($yesterday > $su_wp_last_date){
		if ($su_wp_last_date > ''){
			$start_date = new DateTime($su_wp_last_date); // For today/now, don't pass an arg.
			$start_date->modify("+1 day");
			$start_date = $start_date->format("Y-m-d");
			$start_time = $start_date .' 00:00:00';
		}else{
			$start_time = '2023-01-01 00:00:00';
		}

		$end_time = $yesterday.' 23:59:59';
		// get and save daily stats
		$su_wp_ip_daily_stats = $wpdb->get_results($wpdb->prepare("SELECT date, SUM(`200_count`) AS `200_count`, SUM(`404_count`) AS `404_count` FROM " . $wpdb->prefix . "su_access_archive WHERE date BETWEEN %s AND %s GROUP BY date", $start_time, $end_time), ARRAY_A);

		$date_counter = '';
		foreach($su_wp_ip_daily_stats as $key => $su_wp_ip_daily_stat){
				$su_db_result = $wpdb->insert( $wpdb->prefix .'su_date_stats',
					array(
						'200_count' => $su_wp_ip_daily_stat['200_count'],
						'404_count' => $su_wp_ip_daily_stat['404_count'],
						'date' => $su_wp_ip_daily_stat['date']
					),
					array(
						'%d',
						'%d',
						'%s'
					)
				);
		}
  }
}

function shieldup_set_crons() {
	// Schedule the cron job to run every hour
	if (! wp_next_scheduled ( 'shieldup_cron_hourly' )) {
	   wp_schedule_event( time(), 'hourly', 'shieldup_cron_hourly' );
	}
	add_action( 'shieldup_cron_hourly', 'shieldup_1_hour_cron' );
}

function shieldup_1_hour_cron() {
	global $wpdb;
	$current_server_date_time  = date('Y-m-d H:i:s');

  $su_wp_last_date_archive = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_access_archive order by date desc" );
	$su_wp_last_date_stats = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_date_stats order by date desc" );

	$yesterday = date('Y-m-d',strtotime("-1 days"));

	if ($yesterday > $su_wp_last_date_archive){
		 shieldup_archive_ips();
	}
	if ($yesterday > $su_wp_last_date_stats){
		 shieldup_archive_create_stats();
	}

	$days_to_keep = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_access_log_delete_days'");
  if ($days_to_keep > 0){
		$expiration_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days", strtotime($current_server_date_time))); // Calculate the expiration date

		$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "su_access WHERE timestamp < %s", $expiration_date));
		$wpdb->query("OPTIMIZE TABLE ".$wpdb->prefix."su_access");
	}

	$days_to_keep = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_archive_log_delete_days'");
	if ($days_to_keep > 0){
		$expiration_date = date('Y-m-d', strtotime("-$days_to_keep days", strtotime($current_server_date_time))); // Calculate the expiration date

		$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "su_access_archive WHERE date < %s", $expiration_date));
		$wpdb->query("OPTIMIZE TABLE ".$wpdb->prefix."su_access_archive");

		$wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "su_date_stats WHERE date < %s", $expiration_date));
		$wpdb->query("OPTIMIZE TABLE ".$wpdb->prefix."su_date_stats");
	}

}

function shieldup_title_links ( $links ) {
   $su_links = array(
   '<a href="' . admin_url( 'admin.php?page=su_settings' ) . '">Settings</a>',
	 '<a href="https://www.shieldup.me/how_tos_free" target="_blank" >How Tos</a>',
   );

  return array_merge( $links, $su_links );
}

function shieldup_desc_links( $links, $file ) {
	if ( strpos( $file, SHIELD_UP_PLUGIN_MAIN_FILE ) !== false ) {
		$su_links = array(
			'<a href="https://www.shieldup.me/" target="_blank" style="color:#20b347"><b>ShieldUp Pro</b> - Try for 7 days totaly FREE</a>'
			);
    unset( $links[2] );
		$links = array_merge( $links, $su_links );
	}

	return $links;
}

function shieldup_edit_menu_link() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready( function($) {
            $( "ul#adminmenu a[href$='https://www.shieldup.me/how_tos_free']" ).attr( 'target', '_blank' );
            $( "ul#adminmenu a[href$='https://www.shieldup.me']" ).attr( 'target', '_blank' );
        });
    </script>
    <?php
}
?>
