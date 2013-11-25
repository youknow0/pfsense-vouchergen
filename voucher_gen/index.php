<?php
require_once 'functions.php';
include_config();

$vars = array();
$vars['token'] = md5($config->token . $_SERVER['REMOTE_ADDR']);
$vars['profiles'] = $config->profiles;

if (!empty($_POST['generate'])) {
	
	// failed csrf token
	if ($vars['token'] != $_POST['token']) {
		header('HTTP/1.1 400 Bad Request');
		$vars['message'] = 'Invalid request';
		render_template('template', $vars);
	}
	
	$profileId = $_POST['profile'];
	if (!array_key_exists($profileId, $config->profiles)) {
		$vars['message'] = 'Unknown profile!';
		render_template('template', $vars);
	}
	
	$profile = $config->profiles[$profileId];
	
	try {
		$v = new pfSense_Voucher($config->host, $config->username, $config->password);
		
		$comment = 'AUTO GENERATED from Profile ' . $profile->name . ' at ' . strftime('%Y%m%d-%H:%M');
		
		$rollId = $v->generateVoucherRoll($config->zoneName, $profile->minutes, $profile->count, $comment);
		$csv = $v->obtainVoucherRollCsv($config->zoneName, $rollId);
		
		$csvFilePath = $config->outDir . '/' . strftime($config->outFile);
		$pdfFilePath = $csvFilePath . '.pdf';
		
		$saveFile = file_put_contents($csvFilePath, $csv);
		
		$cmd = 'python ../pdf_gen/wlancodes.py ';
		$cmd .= escapeshellarg($csvFilePath). ' ';
		$cmd .= escapeshellarg($pdfFilePath) . ' ';
		$cmd .= escapeshellarg((int)($profile->minutes / 60));
		
		$ret = -1;
		exec($cmd, null, $ret);
		
		if ($ret != 0) {
			$vars['message'] = 'Failed to create PDF document!';
			render_template('template', $vars);
		} else {
			header('Content-Type: application/pdf');
			echo file_get_contents($pdfFilePath);
		}
		
		
	} catch (pfSense_Voucher_Exception $e) {
		$vars['message'] = 'Error: ' . $e->getMessage();
		render_template('template', $vars);
	}
	
}

render_template('template', $vars);
