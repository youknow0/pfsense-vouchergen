<?php
require_once 'voucher.php';

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

function render_template($vars) {
	foreach ($vars as $k => $v) {
		$$k = $v;
	}
	
	require 'template.php';
	exit;
}
