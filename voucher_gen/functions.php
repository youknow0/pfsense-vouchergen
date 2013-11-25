<?php
require_once 'voucher.php';

function include_config() {
	require 'config.php';
	
	if (stripos($config->outFile, '$PROFILE_ID$') === false) {
		die 'Invalid config: $PROFILE_ID$ not contained in out file name!';
	}
}

function register_profile($name, $count, $minutes) {
	global $config;
	static $i = 1;
	
	$profile = new StdClass();
	$profile->id = $i;
	$profile->name = $name;
	$profile->count = $count;
	$profile->minutes = $minutes;
	
	if (empty($config->profiles)) {
		$config->profiles = array();
	}
	
	$config->profiles[$i] = $profile;
	
	$i++;
}

function render_template($name, $vars) {
	foreach ($vars as $k => $v) {
		$$k = $v;
	}
	
	require $name . '.php';
	exit;
}
