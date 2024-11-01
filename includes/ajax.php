<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action( 'wp_ajax_shieldup_get_ips', 'shieldup_get_ips_callback' );
add_action( 'wp_ajax_shieldup_get_all_ips', 'shieldup_get_all_ips_callback' );

// get raw access log (all IPs)
function shieldup_get_all_ips_callback() {
	global $wpdb;

	check_ajax_referer( 'ajax-nonce','nonce' );

	$start_time = sanitize_text_field($_POST['start_time']);
	$end_time = sanitize_text_field($_POST['end_time']);
	$clicked_ip = sanitize_text_field($_POST['clicked_ip']);

	// response array
	$su_wp_all_ip_details_all = array();

	if (preg_match('/0\/24/', $clicked_ip)){
		$clicked_ip = str_replace('0/24','',$clicked_ip);
	}else if (preg_match('/0\/16/', $clicked_ip)){
		$clicked_ip = str_replace('0.0/16','',$clicked_ip);
	}

	$su_limit_num_rows = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_max_rows_in_tables'");
	$su_wp_all_ip_details_num_rows = 0;
	
	$su_wp_all_ip_details = $wpdb->get_results($wpdb->prepare("SELECT ip, http_code, url, user_agent, timestamp FROM ". $wpdb->prefix ."su_access WHERE ip LIKE %s AND timestamp BETWEEN %s AND %s", $clicked_ip . '%', $start_time, $end_time), ARRAY_A);
	$su_wp_all_ip_details_num_rows = $wpdb->num_rows;
	if ($su_wp_all_ip_details_num_rows > $su_limit_num_rows){
		$su_wp_all_ip_details_all['all_ip_details'] = array_slice($su_wp_all_ip_details, 0, $su_limit_num_rows);
		$su_wp_all_ip_details_all["possible_rows"]["count"] = number_format($su_wp_all_ip_details_num_rows);
		$su_wp_all_ip_details_all["possible_rows"]["limit"] = number_format($su_limit_num_rows);
	}else{
		$su_wp_all_ip_details_all['all_ip_details'] = $su_wp_all_ip_details;
	}

	echo wp_json_encode($su_wp_all_ip_details_all);
	wp_die();
}

// get each IP in time selected
function shieldup_get_ips_callback() {
	global $wpdb;

	check_ajax_referer( 'ajax-nonce','nonce' );

	$start_time = sanitize_text_field($_POST['start_time']);
	$start_date = date('Y-m-d',strtotime($start_time));
	$end_time = sanitize_text_field($_POST['end_time']);
	$end_date = date('Y-m-d',strtotime($end_time));

	$su_wp_last_date_archive = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_access_archive order by date desc" );
	$su_wp_last_date_stats = $wpdb->get_var( "SELECT date FROM ".$wpdb->prefix."su_date_stats order by date desc" );

	$current_date  = date('Y-m-d');
	$current_date_time  = date('Y-m-d H:i:s');
	$yesterday = date('Y-m-d',strtotime("-1 days"));

	if ($yesterday > $su_wp_last_date_archive){
		 shieldup_archive_ips();
	}
	if ($yesterday > $su_wp_last_date_stats){
		 shieldup_archive_create_stats();
	}

	// Get my IP
	if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
	}else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
	}else if(isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
	}else if(isset($_SERVER['HTTP_X_FORWARDED']) && !empty($_SERVER['HTTP_X_FORWARDED'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED']);
	}else if(isset($_SERVER['HTTP_FORWARDED_FOR']) && !empty($_SERVER['HTTP_FORWARDED_FOR'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_FORWARDED_FOR']);
	}else if(isset($_SERVER['HTTP_FORWARDED']) && !empty($_SERVER['HTTP_FORWARDED'])) {
		$my_ip = sanitize_text_field($_SERVER['HTTP_FORWARDED']);
	}else if(isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
		$my_ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
	}

	$su_wp_ip_details_whole_ip = array();
	$su_limit_num_rows = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_max_rows_in_tables'");
	$su_wp_ip_details_today_ips_only = array();
	$su_wp_ip_details_archive_ips_only = array();

	// get the IPs for today
	if ($end_time > $yesterday.' 23:59:59') {
		if ($start_time < $current_date){
			$start_time_tmp = $current_date .' 00:00:00';
		}else{
			$start_time_tmp = $start_time;
		}
		$su_wp_ip_details_today = $wpdb->get_results($wpdb->prepare("SELECT ip, possible_bot, SUM(CASE WHEN http_code = '200' THEN 1 ELSE 0 END) AS `200_count`, SUM(CASE WHEN http_code = '404' THEN 1 ELSE 0 END) AS `404_count` FROM ".$wpdb->prefix."su_access WHERE timestamp BETWEEN %s AND %s GROUP BY ip ORDER BY (SUM(CASE WHEN http_code = '200' THEN 1 ELSE 0 END) + SUM(CASE WHEN http_code = '404' THEN 1 ELSE 0 END)) DESC LIMIT 100000", $start_time_tmp, $end_time), ARRAY_A);
		$su_wp_ip_details_today_ips_only = array_column($su_wp_ip_details_today, 'ip');
		foreach($su_wp_ip_details_today as $key => $su_ip_detail){
			$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip'] = $su_ip_detail['ip'];
			$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_num'] = 1;
			$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_count'] =  $su_ip_detail['404_count'] + $su_ip_detail['200_count'];
			$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_404_count'] = $su_ip_detail['404_count'];

			if ($my_ip ==  $su_ip_detail['ip']){
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = '<span class="text-danger">My IP</span></span>';
			}else{
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = $su_ip_detail['possible_bot'];
			}
		}
	}

	// get the IPs from the archive, basically all before current day
	if ($start_time < $current_date){
		$su_wp_ip_details_archive = $wpdb->get_results($wpdb->prepare("SELECT ip, possible_bot, SUM(200_count) AS `200_count`, SUM(404_count) AS `404_count` FROM ".$wpdb->prefix."su_access_archive WHERE date BETWEEN %s AND %s GROUP BY ip ORDER BY (SUM(200_count) + SUM(404_count)) DESC LIMIT 100000", $start_date, $end_time), ARRAY_A);
		$su_wp_ip_details_archive_ips_only = array_column($su_wp_ip_details_archive, 'ip');
		foreach($su_wp_ip_details_archive as $key => $su_ip_detail){
			if ($end_time > $yesterday.' 23:59:59') {
				if (!isset($su_wp_ip_details_whole_ip[$su_ip_detail['ip']])){
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip'] = $su_ip_detail['ip'];
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_num'] = 1;
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_count'] =  $su_ip_detail['404_count'] + $su_ip_detail['200_count'];
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_404_count'] = $su_ip_detail['404_count'];

					if ($my_ip ==  $su_ip_detail['ip']){
							$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = '<span class="text-danger">My IP</span></span>';
					}else{
							$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = $su_ip_detail['possible_bot'];
					}
				}else{
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_count'] =  $su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_count'] + $su_ip_detail['404_count'] + $su_ip_detail['200_count'];
					$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_404_count'] = $su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_404_count'] + $su_ip_detail['404_count'];
				}
			}else{
				$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip'] = $su_ip_detail['ip'];
				$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_num'] = 1;
				$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_count'] = $su_ip_detail['404_count'] + $su_ip_detail['200_count'];;
				$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['ip_404_count'] = $su_ip_detail['404_count'];

				if ($my_ip ==  $su_ip_detail['ip']){
					  $su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = '<span class="text-danger">My IP</span></span>';
				}else{
						$su_wp_ip_details_whole_ip[$su_ip_detail['ip']]['possible_bot'] = $su_ip_detail['possible_bot'];
				}
			}
		}
	}

	// Create /24 Range array
  $su_wp_ip_details_24_range = array();
	// Create /16 Range array
	$su_wp_ip_details_16_range = array();

	foreach($su_wp_ip_details_whole_ip as $key => $su_ip_detail){
		if(preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.)/', $su_ip_detail['ip'], $matches)){
			 $su_ip_detail['ip'] = $matches[0].'0/24';

			 if (!isset($su_wp_ip_details_24_range[$su_ip_detail['ip']])){
				  $su_wp_ip_details_24_range[$su_ip_detail['ip']] = $su_ip_detail;
			 }else{
				 $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_count"] = $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_count"] + $su_ip_detail['ip_count'];
				 $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_404_count"] = $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_404_count"] + $su_ip_detail['ip_404_count'];
				 $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_num"] = $su_wp_ip_details_24_range[$su_ip_detail['ip']]["ip_num"] + $su_ip_detail['ip_num'];
			 }
		}

		if(preg_match('/(\d{1,3}\.\d{1,3}\.)/', $su_ip_detail['ip'], $matches)){
			 $su_ip_detail['ip'] = $matches[0].'0.0/16';

			 if (!isset($su_wp_ip_details_16_range[$su_ip_detail['ip']])){
				   $su_wp_ip_details_16_range[$su_ip_detail['ip']] = $su_ip_detail;
			 }else{
				 $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_count"] = $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_count"] + $su_ip_detail['ip_count'];
				 $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_404_count"] = $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_404_count"] + $su_ip_detail['ip_404_count'];
				 $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_num"] =  $su_wp_ip_details_16_range[$su_ip_detail['ip']]["ip_num"] + $su_ip_detail['ip_num'];
			 }
		}
	}

	$su_wp_ip_details_24_range_total_num = count($su_wp_ip_details_24_range);
	uasort($su_wp_ip_details_24_range, function($a, $b) {
			return $b['ip_count'] - $a['ip_count'];
	});
	$su_wp_ip_details_24_range = array_slice($su_wp_ip_details_24_range, 0, $su_limit_num_rows);

	$su_wp_ip_details_16_range_total_num = count($su_wp_ip_details_16_range);
	uasort($su_wp_ip_details_16_range, function($a, $b) {
			return $b['ip_count'] - $a['ip_count'];
	});
	$su_wp_ip_details_16_range = array_slice($su_wp_ip_details_16_range, 0, $su_limit_num_rows);

	uasort($su_wp_ip_details_whole_ip, function($a, $b) {
			return $b['ip_count'] - $a['ip_count'];
	});
	$su_wp_ip_details_whole_ip = array_slice($su_wp_ip_details_whole_ip, 0, $su_limit_num_rows);

	$su_wp_ip_details_24_range = array_values($su_wp_ip_details_24_range);
	$su_wp_ip_details_16_range = array_values($su_wp_ip_details_16_range);
	$su_wp_ip_details_whole_ip = array_values($su_wp_ip_details_whole_ip);

	$period = new DatePeriod(
     new DateTime($start_time),
     new DateInterval('P1D'),
     new DateTime($end_date.' 23:59:59')
	);

	$su_wp_ip_daily_stats = array();
	$su_wp_ip_daily_stats_today = array();

	if ($start_time < $current_date){
		$su_wp_ip_daily_stats = $wpdb->get_results($wpdb->prepare("SELECT date, `200_count`, `404_count` FROM " . $wpdb->prefix . "su_date_stats WHERE date BETWEEN %s AND %s", $start_date, $end_time), ARRAY_A);
	}

	if ($end_time > $yesterday.' 23:59:59') {
		if ($start_time < $current_date){
			$start_time_tmp = $current_date .' 00:00:00';
		}else{
			$start_time_tmp = $start_time;
		}
		$su_wp_ip_daily_stats_today = $wpdb->get_results($wpdb->prepare("SELECT DATE(timestamp) AS date, SUM(CASE WHEN http_code = '200' THEN 1 ELSE 0 END) AS `200_count`, SUM(CASE WHEN http_code = '404' THEN 1 ELSE 0 END) AS `404_count` FROM " . $wpdb->prefix . "su_access WHERE timestamp BETWEEN %s AND %s GROUP BY date", $start_time_tmp, $end_time), ARRAY_A);
	}

	// this is because you might have empty days in previous query
	$temp_days_array = array();
	foreach ($period as $key => $value) {

		$su_wp_ip_daily_stats_key = '';
		$su_wp_ip_daily_stats_key = array_search($value->format('Y-m-d'), array_column($su_wp_ip_daily_stats,'date'));
		if ($su_wp_ip_daily_stats_key === false){
			$temp_days_array["not_found_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["not_found_count"][$key]["y"] = 0;
			$temp_days_array["success_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["success_count"][$key]["y"] = 0;
			$temp_days_array["total_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["total_count"][$key]["y"] = 0;
		}else{
			$temp_days_array["not_found_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["not_found_count"][$key]["y"] = $su_wp_ip_daily_stats[$su_wp_ip_daily_stats_key]["404_count"];
			$temp_days_array["success_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["success_count"][$key]["y"] = $su_wp_ip_daily_stats[$su_wp_ip_daily_stats_key]["200_count"];
			$temp_days_array["total_count"][$key]["x"] = $value->format('Y-m-d');
			$temp_days_array["total_count"][$key]["y"] = $su_wp_ip_daily_stats[$su_wp_ip_daily_stats_key]["200_count"] + $su_wp_ip_daily_stats[$su_wp_ip_daily_stats_key]["404_count"];
		}

		if ($value->format('Y-m-d') > $yesterday.' 23:59:59') {
			$su_wp_ip_daily_stats_today_key = '';
			$su_wp_ip_daily_stats_today_key = array_search($value->format('Y-m-d'), array_column($su_wp_ip_daily_stats_today,'date'));
			if ($su_wp_ip_daily_stats_today_key === false){
				$temp_days_array["not_found_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["not_found_count"][$key]["y"] = 0;
				$temp_days_array["success_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["success_count"][$key]["y"] =  0;
				$temp_days_array["total_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["total_count"][$key]["y"] = 0;
			}else{
				$temp_days_array["not_found_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["not_found_count"][$key]["y"] = $su_wp_ip_daily_stats_today[$su_wp_ip_daily_stats_today_key]["404_count"];
				$temp_days_array["success_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["success_count"][$key]["y"] = $su_wp_ip_daily_stats_today[$su_wp_ip_daily_stats_today_key]["200_count"];
				$temp_days_array["total_count"][$key]["x"] = $value->format('Y-m-d');
				$temp_days_array["total_count"][$key]["y"] = $su_wp_ip_daily_stats_today[$su_wp_ip_daily_stats_today_key]["200_count"] + $su_wp_ip_daily_stats_today[$su_wp_ip_daily_stats_today_key]["404_count"];
			}
		}
	}
	// construct data array to return
  $su_wp_ip_details_all = array();
  $su_wp_ip_details_all['temp_days_array'] = $temp_days_array;
	$su_wp_ip_details_all["ip_details_whole_ip"] = $su_wp_ip_details_whole_ip;
	$su_wp_ip_details_all["ip_details_24_range"] = $su_wp_ip_details_24_range;
	$su_wp_ip_details_all["ip_details_16_range"] = $su_wp_ip_details_16_range;
	// calculate possible records
	$su_wp_ip_details_all_ips_only_arr = array();
	$su_wp_ip_details_all_ips_only_arr = array_unique(array_merge($su_wp_ip_details_today_ips_only,$su_wp_ip_details_archive_ips_only), SORT_REGULAR);
	$su_wp_ip_details_total_num_rows = count($su_wp_ip_details_all_ips_only_arr);
	if ($su_wp_ip_details_total_num_rows > $su_limit_num_rows){
			$su_wp_ip_details_all["possible_rows"]["count"] = number_format($su_wp_ip_details_total_num_rows);
			$su_wp_ip_details_all["possible_rows"]["limit"] = number_format($su_limit_num_rows);
	}
	if ($su_wp_ip_details_24_range_total_num > $su_limit_num_rows){
			$su_wp_ip_details_all["possible_rows_24"]["count"] = number_format($su_wp_ip_details_24_range_total_num);
			$su_wp_ip_details_all["possible_rows_24"]["limit"] = number_format($su_limit_num_rows);
	}
	if ($su_wp_ip_details_16_range_total_num > $su_limit_num_rows){
			$su_wp_ip_details_all["possible_rows_16"]["count"] = number_format($su_wp_ip_details_16_range_total_num);
			$su_wp_ip_details_all["possible_rows_16"]["limit"] = number_format($su_limit_num_rows);
	}

	echo wp_json_encode($su_wp_ip_details_all);
	wp_die();
}
?>
