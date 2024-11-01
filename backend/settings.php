<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$su_errors=array(); $su_successes=array(); $su_infos=array();
global $wpdb;
$su_infos = shieldup_check_main_settings();

// Form submitted, update settings
if (!empty($_POST)) {
	if (isset($_POST['shieldup_submission_nonce']) && wp_verify_nonce(sanitize_key($_POST['shieldup_submission_nonce']), 'shieldup_submission_action')) {
		$su_update_success_flag = 0;

	  if (!empty($_POST["shieldup_general_settings"])){

			if (isset($_POST["shieldup_max_rows_in_tables"]) && !is_numeric($_POST["shieldup_max_rows_in_tables"])) {
				$su_errors[] = "Option for maximum number of data to display in tables can only be a number!";
			}

			if (isset($_POST["shieldup_max_rows_in_tables"]) && ($_POST["shieldup_max_rows_in_tables"] < 100)) {
				$su_errors[] = "Option for maximum number of data to display in tables can't be lower than 100!";
			}

			if (isset($_POST["shieldup_max_rows_in_tables"]) && ($_POST["shieldup_max_rows_in_tables"] > 100000)) {
				$su_errors[] = "Option for maximum number of data to display in tables can't be bigger than 100,000!";
			}

			if (isset($_POST["shieldup_access_log_days"]) && !is_numeric($_POST["shieldup_access_log_days"])) {
				$su_errors[] = "Option for auto delete detailed access logs can only be a number!";
			}

			if (isset($_POST["shieldup_access_log_days"]) && ($_POST["shieldup_access_log_days"] <= 0)) {
				$su_errors[] = "Option for auto delete detailed access logs can't be a zero or negative days! Recommended is 30 days";
			}

			if (isset($_POST["shieldup_archive_log_days"]) && !is_numeric($_POST["shieldup_archive_log_days"])) {
				$su_errors[] = "Option for auto delete all IP logs can only be a number!";
			}

			if (isset($_POST["shieldup_archive_log_days"]) && ($_POST["shieldup_archive_log_days"] < 0)) {
				$su_errors[] = "Option for auto delete all IP logss can't be negative days!";
				$su_errors[] = "Recommended is 0 which is never to delete it, but if you must, use at least 365 days.";
			}

			if (isset($_POST["shieldup_archive_log_days"]) && ($_POST["shieldup_archive_log_days"] > 0 && $_POST["shieldup_archive_log_days"] < 30)) {
				$su_errors[] = "Option for auto delete all IP logs can't be lower than 30 days!";
				$su_errors[] = "Recommended is 0 which is never to delete it, but if you must, use at least 365 days.";
			}

			if (count($su_errors) == 0) {
				$shieldup_max_rows_in_tables = abs($_POST["shieldup_max_rows_in_tables"]);
				if ($wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_max_rows_in_tables'", $shieldup_max_rows_in_tables)) > 0) {
					$su_update_success_flag = 1;
				}
				$shieldup_access_log_days = abs($_POST["shieldup_access_log_days"]);
				if ($wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_access_log_delete_days'", $shieldup_access_log_days)) > 0) {
					$su_update_success_flag = 1;
				}

				$shieldup_archive_log_days = abs($_POST["shieldup_archive_log_days"]);
				if ($wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_archive_log_delete_days'", $shieldup_archive_log_days)) > 0) {
					$su_update_success_flag = 1;
				}

				$shieldup_drop_tables_flag = abs($_POST["shieldup_drop_tables_flag"]);
				if ($wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "su_settings SET option_value = %s WHERE option_name = 'su_drop_tables'", $shieldup_drop_tables_flag)) > 0) {
					$su_update_success_flag = 1;
				}
			}

	  }
		// print success msg
		if ($su_update_success_flag > 0) {
			$su_successes[] = "Settings saved successfully!";
		}
	// Failed Nonce
	}else{
			$su_errors[] = "Oppss Something is worng with submission form, Failed security check!";
	}
}

$su_drop_tables = $wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_drop_tables'");

// Get the number of rows
$row_count_su_access = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."su_access");
$row_count_su_archive = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix."su_access_archive");


// Get the table size in MB
$table_size_in_mb_su_access = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_in_mb
        FROM information_schema.TABLES
        WHERE table_schema = %s
        AND table_name = %s",
        $wpdb->dbname,
        $wpdb->prefix . 'su_access'
    )
);

$table_size_in_mb_su_archive = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_in_mb
        FROM information_schema.TABLES
        WHERE table_schema = %s
        AND table_name = %s",
        $wpdb->dbname,
        $wpdb->prefix . 'su_access_archive'
    )
);

?>
<div class="su-wrapper mt-3">
  <div class="p-2">
    <h3><?php echo esc_html(SHIELD_UP_PLUGIN_NAME); ?> Settings</h3>
  </div>
  <div class="border border-secondary container alignleft bg-white">
    <div id="collapseExample" class="panel panel-primary panel-collapse">
      <div id="su_form_data" class="mt-4 ms-2">

				<?php echo esc_html(shieldup_message_resultBlock($su_errors,$su_successes,$su_infos)); ?>
				<div id="alert-container"></div>

        <ul class="nav nav-tabs" role="tablist">
					<li class="nav-item">
							<a class="nav-link active" href="#general" data-bs-toggle="tab">General</a>
					</li>
					<li class="nav-item ms-auto">
					     <h6 class="" id="su_version"><?php echo esc_html(shieldup_get_version()); ?></h6>
					</li>
        </ul>

        <div id="myTabContent" class="tab-content">

					<div class="tab-pane show active fade" id="general">
					<form role="form" action="admin.php?page=su_settings" method="post">
						 <?php wp_nonce_field('shieldup_submission_action', 'shieldup_submission_nonce'); ?>

             <div class="row g-2 mt-1">
							 <h5 class="mt-3">Display Options</h5>
	 					   <hr class="mt-1 mb-1"/>

							 <div class="col-md-6 form-check-inline">
									<label class="form-label fw-bold mt-1">Maximum data displayed in the dashboard IPs table and detailed IP log table:</label>
									<input type="number" class="form-control form-control-sm" id="shieldup_max_rows_in_tables" name="shieldup_max_rows_in_tables" value="<?php  echo esc_html($wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_max_rows_in_tables'"));   ?>" />
								</div>
							 <p class="help-block">
								 This controls the maximum displayed data, not the data in the database.<br>
								 These tables can get quite large in terms of data size and depending on your computer's capabilities, your browser may crash.<br>
								 If certain dates are not displayed due to reaching the limit, you can narrow it down by selecting a smaller time range.<br>
								 Minimum: 100, Maximum: 100,000, default is 1,000</p>

							 <h5 class="mt-3">Database Options</h5>
							 <hr class="mt-1 mb-1"/>

							 <div class="col-md-6 form-check-inline mt-2">
                 <label class="form-label fw-bold">Auto delete all IP access data after X days:</label>
                 <input type="number" class="form-control form-control-sm" id="shieldup_archive_log_days" name="shieldup_archive_log_days" value="<?php  echo esc_html($wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_archive_log_delete_days'"));   ?>" />
               </div>
							 <p class="help-block"><?php  echo  "<span class=\"fst-italic\">Table has " . esc_html(number_format($row_count_su_archive)) . " rows and it is ". esc_html($table_size_in_mb_su_archive) . " MB in size.</span>"; ?><br><br>
								 How many days do you want to retain basic daily IP access log data in your database?<br>
								 You can decrease it if your log grows larger, which is rare and only occurs on high-traffic websites.<br>
								 The default and recommended setting is 2 years (730 days), with 0 meaning never delete.</p>

							 <div class="col-md-6 form-check-inline mt-3">
                 <label class="form-label fw-bold">Auto delete detailed IP access logs after X days:</label>
                 <input type="number" class="form-control form-control-sm" id="shieldup_access_log_days" name="shieldup_access_log_days" value="<?php  echo esc_html($wpdb->get_var( "SELECT option_value FROM ".$wpdb->prefix."su_settings where option_name = 'su_access_log_delete_days'"));   ?>" />
               </div>
							 <p class="help-block"><?php  echo  "<span class=\"fst-italic\">Table has " . esc_html(number_format($row_count_su_access)) . " rows and it is ". esc_html($table_size_in_mb_su_access) . " MB in size.</span>"; ?><br><br>
								 How many days do you want to retain detailed access log data for each IP in your database?<br>
								 If log becomes bigger, then processing all the data by plugin can get a bit slower.<br>
								 Default: 30 days, Minimum: 1 day.</p>

							 <div class="col-md-3 form-check-inline mt-4">
							 	<label class="form-label fw-bold">Delete all data/tables when uninstalling plugin:</label>
								<select class="form-control form-control-sm" id="shieldup_drop_tables_flag" name="shieldup_drop_tables_flag">
								  <option value="0" <?php if ($su_drop_tables == 0 ) echo 'selected'; ?>>No</option>
								  <option value="1" <?php if ($su_drop_tables == 1 ) echo 'selected'; ?>>Yes</option>
								</select>
							 </div>
							 <p class="help-block">If you want to keep your accumulated data (access logs, controlled IPs, etc.) after deleting this plugin, select 'No'.</p>

							 <input type="hidden" name="shieldup_general_settings" value="1">
							 <div class="col-md-7 mb-3 mt-3">
								 <button type="submit" class="btn btn-primary btn-sm">Save</button>
							 </div>
             </div>
					</form>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
