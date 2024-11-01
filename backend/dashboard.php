<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$su_errors=array(); $su_successes=array(); $su_infos=array();
$su_infos = shieldup_check_main_settings();

?>
<div class="su-wrapper mt-3">
	<div class="p-2">
		<h3><?php echo esc_html(SHIELD_UP_PLUGIN_NAME); ?> Dashboard</h3>
	</div>
		<div class="border border-secondary container alignleft bg-white">
      <div id="collapseExample" class="panel panel-primary panel-collapse mt-3">

				<label class="fw-bold">Choose Date Range: </label>
				<input type="text" name="shieldup_daterange" value="" />
				<label style="float: right;" class="fw-bold">Current Server Time: <?php echo esc_html(date('Y-m-d H:i:s')); ?></label>
				<div id="loader-wrapper" style="visibility: hidden;">
          <div id="loader"></div>
        </div>

				<?php echo esc_html(shieldup_message_resultBlock($su_errors,$su_successes,$su_infos)); ?>
				<div id="alert-container"></div>

				<div id="tabs" class="mt-4">
						<ul class="nav nav-tabs" role="tablist">
				        <li class="nav-item">
				            <a class="nav-link active" href="#tab-table-1" data-bs-toggle="tab">IPs</a>
				        </li>
				        <li class="nav-item">
				            <a class="nav-link" href="#tab-table-2" data-bs-toggle="tab">/24 Range IPs</a>
				        </li>
								<li class="nav-item">
										<a class="nav-link" href="#tab-table-3" data-bs-toggle="tab">/16 Range IPs</a>
								</li>
								<li class="nav-item">
										<a class="nav-link" href="#tab-table-4" data-bs-toggle="tab">IP Detailed Log</a>
								</li>
								<li class="nav-item">
										<a class="nav-link" href="#tab-table-6" data-bs-toggle="tab">Help</a>
								</li>
								<li class="nav-item ms-auto">
										 <h6 class="" id="su_version"><?php echo esc_html(shieldup_get_version()); ?></h6>
								</li>
				    </ul>

						<div class="tab-content mt-3 mb-4">
				      <div class="tab-pane show fade active" id="tab-table-1">
								<table id="ips_all" class="table table-striped table-bordered" style="width:100%">
							      <thead>
							          <tr>
							              <th>IP</th>
							              <th>Total Requests</th>
														<th>404 Requests</th>
							              <th>Possible Bot</th>
							          </tr>
							      </thead>
							      <tfoot>
							          <tr>
													<th>IP</th>
													<th>Total Requests</th>
													<th>404 Requests</th>
													<th>Possible Bot</th>
							          </tr>
							      </tfoot>
							  </table>
								<div id='su_limit_info_ip' class="su_limit_info"></div>
							</div>
							<div class="tab-pane fade" id="tab-table-2">
								<table id="ips_24_range" class="table table-striped table-bordered " style="width:100%">
							      <thead>
							          <tr>
							              <th>IP</th>
														<th>Total Requests</th>
														<th>404 Requests</th>
							              <th>Possible Bot</th>
							          </tr>
							      </thead>
							      <tfoot>
							          <tr>
													<th>IP</th>
													<th>Total Requests</th>
													<th>404 Requests</th>
													<th>Possible Bot</th>
							          </tr>
							      </tfoot>
							  </table>
								<div id='su_limit_info_24' class="su_limit_info"></div>
							</div>
							<div class="tab-pane fade" id="tab-table-3">
								<table id="ips_16_range" class="table table-striped table-bordered" style="width:100%">
							      <thead>
							          <tr>
							              <th>IP</th>
														<th>Total Requests</th>
														<th>404 Requests</th>
							              <th>Possible Bot</th>
							          </tr>
							      </thead>
							      <tfoot>
							          <tr>
													<th>IP</th>
													<th>Total Requests</th>
													<th>404 Requests</th>
													<th>Possible Bot</th>
							          </tr>
							      </tfoot>
							  </table>
								<div id='su_limit_info_16' class="su_limit_info"></div>
							</div>
							<div class="tab-pane" id="tab-table-4">
								<table id="ips_raw" class="table table-striped table-bordered " style="width:100%">
										<thead>
												<tr>
														<th>IP</th>
														<th>HTTP Code</th>
														<th>URL</th>
														<th>User Agent</th>
														<th>Timestamp</th>
												</tr>
										</thead>
										<tfoot>
												<tr>
													<th>IP</th>
													<th>HTTP Code</th>
													<th>URL</th>
													<th>User Agent</th>
													<th>Timestamp</th>
												</tr>
										</tfoot>
								</table>
								<div id='su_limit_info_detailed' class="su_limit_info"></div>
							</div>
							<div class="tab-pane fade" id="tab-table-6">
								<div class="ms-3">
									<div class="col-md-7 mb-1 mt-1">
										<b>24/Range</b> - This will control range of 256 IPs.
									</div>
									<div class="col-md-7 mb-1">
										<b>16/Range</b> - This will control range of 65,536 IPs.
									</div>
								</div>
							</div>
						</div>
				</div>

				<hr>
				<div id="chart"></div>

		  </div>
    </div>
</div>
