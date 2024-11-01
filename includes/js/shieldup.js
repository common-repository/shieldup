// ShieldUp JS
function su_fetch_ips(su_start_date, su_end_date, callback) {
    document.getElementById("loader-wrapper").style.visibility = "visible";
    var ip_data = '';

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        async: true,
        data: {
            action: 'shieldup_get_ips',
            start_time: su_start_date,
            end_time: su_end_date,
            nonce: ajax_var.nonce   //  pass the nonce here
        },
        error: function (xhr, status, error) {
            var errorMessage = xhr.status + ' - ' + xhr.statusText
            su_create_msg_alert('<b>Ajax Error</b>: ' + errorMessage, "danger");
            callback(ip_data);
        },
        success: function (data) {
            ip_data = data;
            callback(ip_data);
        }
    });
}

function shieldup_get_all_ips(su_start_date, su_end_date, clicked_ip, callback) {
    var ip_data_all = '';

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        // dataSrc: '',
        dataType: 'json',
        async: true,
        data: {
            action: 'shieldup_get_all_ips',
            start_time: su_start_date,
            end_time: su_end_date,
            clicked_ip: clicked_ip,
            nonce: ajax_var.nonce   // pass the nonce here
        },
        error: function (xhr, status, error) {
            var errorMessage = xhr.status + ' - ' + xhr.statusText
            su_create_msg_alert('<b>Ajax Error</b>: ' + errorMessage, "danger");
            callback(ip_data_all);
        },
        success: function (data) {
            ip_data_all = data;
            callback(ip_data_all);
        }
    });
}

function su_create_table(dataset, table_id) {
    var temp_table = jQuery('#' + table_id).DataTable({
        data: dataset,
        columns: [
            {
                data: function (data, type, dataToSet) {
                    ip_num_text = '';
                    if (table_id == 'ips_24_range') {
                        if (data.ip_num == 1) { ip_num_text = '<span>(' + data.ip_num + ' IP)</span>'; } else { ip_num_text = '<span>(' + data.ip_num + ' IPs)</span>'; }
                    }
                    if (table_id == 'ips_16_range') {
                        if (data.ip_num == 1) { ip_num_text = '<span>(' + data.ip_num + ' IP)</span>'; } else { ip_num_text = '<span>(' + data.ip_num + ' IPs)</span>'; }
                    }
                    return '<a class="kita" href="">' + data.ip + '</a> ' + ip_num_text + '<label id="label-copy" class="form-label ms-2 mb-3 fa-regular fal fa-copy" data-bs-toggle="tooltip" title="Copy IP to clipboard."></label><label id="label-log" class="form-label ms-2 mb-3 fa fa-magnifying-glass" data-bs-toggle="tooltip" title="Check IP"></label>';
                }
            },
            { data: 'ip_count', render: jQuery.fn.dataTable.render.number(',', null, null, null) },
            { data: 'ip_404_count', render: jQuery.fn.dataTable.render.number(',', null, null, null) },
            { data: 'possible_bot' }
        ],
        "autoWidth": false,
        "order": [[1, 'desc'], [2, 'asc']]
    });
    return temp_table;
}

function su_create_table_all(dataset, table_id, table_msg) {
    // Raw access log by IP
    jQuery('#'+table_id).DataTable().clear().destroy();

    var temp_table = jQuery('#' + table_id).DataTable({
        data: dataset,
        columns: [
            { data: 'ip' },
            { data: 'http_code' },
            { data: 'url' },
            { data: 'user_agent' },
            { data: 'timestamp' },
        ],
        "language": {
            "emptyTable": table_msg
        },
        "autoWidth": false,
        "order": [[4, 'desc']]
    });
    return temp_table;
}

function su_copy_ip(table_id) {
    jQuery('#' + table_id + ' tbody').on('click', 'label.fa-copy', function (e) {
        var row = jQuery(this).closest('tr');
				var ip_to_ban = jQuery('#'+table_id).DataTable().row(row).data().ip;

        setTimeout(function () {
            jQuery('#copied_tip').remove();
        }, 800);
        jQuery(this).append("<div class='su_tip' id='copied_tip'>IP Copied!</div>");
        // Copy the text inside the text field
        navigator.clipboard.writeText(ip_to_ban);
    });
}

function su_check_ip_externaly(table_id) {
    jQuery('#' + table_id + ' tbody').on('click', 'label.fa-magnifying-glass', function (e) {
        var row = jQuery(this).closest('tr');
				var ip_to_ban = jQuery('#'+table_id).DataTable().row(row).data().ip;
        ip_to_ban = ip_to_ban.replace(/\/16/i, "");
        ip_to_ban = ip_to_ban.replace(/\/24/i, "");
        var url = 'https://ipinfo.io/' + ip_to_ban;
        window.open(url, '_blank').focus();
    });
}

function su_handle_limit_info(info_id,dataSet){
	if (typeof dataSet !== 'undefined'){
		document.getElementById(info_id).innerHTML = 'There are ' + dataSet.count + ' possible IPs available for this time frame, we are showing ' + dataSet.limit + ' IPs with the highest traffic first, to see others change time frame or limit in settings.';
	}else{
		document.getElementById(info_id).innerHTML = '';
	}
}

function su_get_ip_details(table_id) {
    jQuery('#' + table_id + ' tbody').on('click', 'a.kita', function (e) {
        var row = jQuery(this).closest('tr');
				var ip_to_ban = jQuery('#'+table_id).DataTable().row(row).data().ip;
        document.getElementById("loader-wrapper").style.visibility = "visible";
        e.preventDefault();
        shieldup_get_all_ips(su_start_date, su_end_date, ip_to_ban, function (dataSet_all) {
						if (typeof dataSet_all.possible_rows !== 'undefined'){
		 					document.getElementById("su_limit_info_detailed").innerHTML = 'There are ' + dataSet_all.possible_rows.count + ' possible access logs available for this time frame, we are showing ' + dataSet_all.possible_rows.limit + ' most recent first, to see others change time frame or limit in settings.';
		 				}else{
		 					document.getElementById("su_limit_info_detailed").innerHTML = '';
		 				}
            su_create_table_all(dataSet_all.all_ip_details, 'ips_raw', 'There is no detailed access log data available for this IP within the selected date range');
            jQuery('a[href="#tab-table-4"]').tab('show');
            document.getElementById("loader-wrapper").style.visibility = "hidden";
        });
    });
}

function su_create_msg_alert(su_msg_text, su_alert_type) {
    document.getElementById("alert-container").innerHTML = '<div class="alert alert-' + su_alert_type + ' alert-dismissible fade show mt-3" role="alert">' + su_msg_text + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
}


jQuery(document).ready(function () {

    su_start_date = new Date().toISOString().slice(0, 10) + ' 00:00:00';
    su_end_date = new Date().toISOString().slice(0, 10) + ' 23:59:59';

    // Single IP table setup
    su_fetch_ips(su_start_date, su_end_date, function (dataSet) {
				su_handle_limit_info("su_limit_info_ip",dataSet.possible_rows);
				su_create_table(dataSet.ip_details_whole_ip, 'ips_all');

        document.getElementById("loader-wrapper").style.visibility = "hidden";
        su_copy_ip('ips_all');
        su_check_ip_externaly('ips_all');
        su_get_ip_details('ips_all');


        // /24 range table
				su_handle_limit_info("su_limit_info_24",dataSet.possible_rows_24);
        su_create_table(dataSet.ip_details_24_range, 'ips_24_range');
        su_copy_ip('ips_24_range');
        su_check_ip_externaly('ips_24_range');
        su_get_ip_details('ips_24_range');


        // /16 Range table
				su_handle_limit_info("su_limit_info_16",dataSet.possible_rows_16);
        su_create_table(dataSet.ip_details_16_range, 'ips_16_range');
        su_copy_ip('ips_16_range');
        su_check_ip_externaly('ips_16_range');
        su_get_ip_details('ips_16_range');

        // create default empty tables
        su_create_table_all(0, 'ips_raw', 'Click the IP you wish to get more details for the selected date range');

        // Chart setup
        var options = {
            series: [
                {
                    name: "Total Requests",
                    data: dataSet.temp_days_array.total_count
                },
                {
                    name: "200 - Success",
                    data: dataSet.temp_days_array.success_count
                },
                {
                    name: "404 - Not Found",
                    data: dataSet.temp_days_array.not_found_count
                }
            ],
			// colors : ['#2983FF', '#4CAF50', '#dc3545'],
            chart: {
                id: 'kitac',
                height: 350,
                type: 'line',
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'straight'
            },
            title: {
                text: 'Requests by HTTP Code for the selected period of time.',
                align: 'left'
            },
            grid: {
                row: {
                    colors: ['#f3f3f3', 'transparent'], // takes an array which will be repeated on columns
                    opacity: 0.5
                },
            },
            xaxis: {
                type: "datetime"
            }
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();

	// end of dataset call
    });


  // adjust columns
  jQuery(document).on('shown.bs.tab', 'a[data-bs-toggle="tab"]', function (e) {
      jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
  });

  jQuery('input[name="shieldup_daterange"]').daterangepicker({
      "timePicker": true,
      "timePicker24Hour": true,
      //  "autoApply": true,
      "startDate": moment().startOf('day'),
      "endDate": moment().endOf('day'),
      "ranges": {
          'Today': [moment().startOf('day'), moment().endOf('day')],
          'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
          'Last 7 Days': [moment().subtract(6, 'days').startOf('day'), moment().endOf('day')],
          'Last 30 Days': [moment().subtract(29, 'days').startOf('day'), moment().endOf('day')],
          'This Month': [moment().startOf('month'), moment().endOf('day')],
          'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
      },
      "alwaysShowCalendars": true,
      "linkedCalendars": false,
      "opens": "right"
  }, function (start, end, label) {
      // document.getElementById("loader-wrapper").style.visibility = "visible";

      su_start_date = start.format('YYYY-MM-DD HH:mm:ss');
      su_end_date = end.format('YYYY-MM-DD HH:mm:ss');

      su_fetch_ips(su_start_date, su_end_date, function (dataSet) {
          // Single IP table setup
          jQuery('#ips_all').DataTable().clear().destroy();
  				su_handle_limit_info("su_limit_info_ip",dataSet.possible_rows);
          su_create_table(dataSet.ip_details_whole_ip, 'ips_all');

          // /24 Range table
          jQuery('#ips_24_range').DataTable().clear().destroy();
  				su_handle_limit_info("su_limit_info_24",dataSet.possible_rows_24);
          su_create_table(dataSet.ip_details_24_range, 'ips_24_range');

          // /16 Range table
          jQuery('#ips_16_range').DataTable().clear().destroy();
  				su_handle_limit_info("su_limit_info_16",dataSet.possible_rows_16);
          su_create_table(dataSet.ip_details_16_range, 'ips_16_range');

          jQuery('a[href="#tab-table-1"]').tab('show');
          document.getElementById("loader-wrapper").style.visibility = "hidden";

          // Re-create default empty tables
          su_create_table_all(0, 'ips_raw', 'Click the IP you wish to get more details for the selected date range');

          // update chart
          ApexCharts.exec('kitac', 'updateOptions', {
              series: [
                  {
                      name: "Total Requests",
                      data: dataSet.temp_days_array.total_count
                  },
                  {
                      name: "200 - Success",
                      data: dataSet.temp_days_array.success_count
                  },
                  {
                      name: "404 - Not Found",
                      data: dataSet.temp_days_array.not_found_count
                  }
              ],
              title: {
                  text: 'Requests by HTTP Code for the selected period of time.'
              }
          }, false, true);

      });
  });

// End document ready
});
