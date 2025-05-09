<?php

if (! defined ( 'CRON' )) {
	exit ( 'CLI script access allowed only' );
}

/*
 * |--------------------------------------------------------------------------
 * | CRON Configuration
 * |--------------------------------------------------------------------------
 */

$flux_config = parse_ini_file ( "/var/lib/flux/flux-config.conf" );
$config ['SERVER_NAME'] = $flux_config ['base_url']; // Your web site url
$config ['CRON_TIME_LIMIT'] = 0; // 0 = no time limit
$config ['argv'] = array (
		"LowBalance" => "lowbalance/low_balance",
		"CurrencyUpdate" => "currencyupdate/update_currency",								
		"GenerateInvoice" => "ProcessInvoice/GenerateInvoice",
		"UpdateBalance" => "ProcessCharges/GetUpdateBalance",
		"ProcessDailyCharges" => "ProcessCharges/ProcessDailyCharges",
		"BillAccountCharges" => "ProcessCharges/BillAccountCharges",		
		"FeedBack" => "feedback/customer_feedback_result/TRUE",
		//CDRs Archive
		"ArchiveCDRs" => "CDRsArchive/ProcessCDRsArchive",
		"FaxSend"=>"faxsend/index",
		"SendFax"=>"sendFax/index",
		"Purge" => "purge/ProcessPurge",
		"BroadcastEmail" => "broadcastemail/broadcast_email",
		"crons" => "crons/index",
		"gitUpdate" => "GitUpdate/executeUpdate",
		"Refactor" => "Refactor/index",
		"gitRollback" => "GitUpdate/executeRollback",
		"ApiEndpoints" => "ApiEndpoints/curl_request",
		"ApiProxy" => "ApiProxy/update_data"
		
);
$config ['CRON_BETA_MODE'] = false; // Beta Mode (useful for blocking submissions for testing)
?>
