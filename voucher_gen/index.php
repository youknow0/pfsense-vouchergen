<?php
require_once 'functions.php';
require_once 'config.php';

if (file_exists($config->outFile)) {
	echo 'PDF Generation is in progress. Please wait until it is finished.';
	exit;
}

$vars = array();
$vars['token'] = md5($config->token . $_SERVER['REMOTE_ADDR']);
$vars['profiles'] = $config->profiles;

if (!empty($_POST['generate'])) {
	
	// failed csrf token
	if ($vars['token'] != $_POST['token']) {
		header('HTTP/1.1 Bad Request');
		$vars['message'] = 'Invalid request';
		render_template($vars);
	}
	
	$profileId = $_POST['profile'];
	if (!array_key_exists($profileId, $config->profiles)) {
		$vars['message'] = 'Unknown profile!';
		render_template($vars);
	}
	
	$profile = $config->profiles[$profileId];
	
	try {
		echo '<pre>';
		$v = new pfSense_Voucher($config->host, $config->username, $config->password);
		
		$comment = 'AUTO GENERATED from Profile' . $profile->name . ' at ' . strftime('%Y%m%d-%H:%M');
		
		$rollId = $v->generateVoucherRoll($config->zoneName, $profile->minutes, $profile->count, $comment);
		$csv = $v->obtainVoucherRollCsv($config->zoneName, $rollId);
		
		$saveFile = file_put_contents($config->outFile, $csv);
		
		$vars['message'] = 'Vouchers have been created.';
		echo '</pre>';
		render_template($vars);
	} catch (pfSense_Voucher_Exception $e) {
		$vars['message'] = 'Error: ' . $e->getMessage();
		render_template($vars);
	}
	
}

render_template($vars);
