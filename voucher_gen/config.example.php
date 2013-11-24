<?php
$config = new StdClass();

// the pfsense host. either a host name or a ip adress.
$config->host = 'my.pfsense.host';

// username and password for logging into pfSense.
// the user must have rights to edit vouchers.
$config->username = 'myuser';
$config->password = 'mypass';

// the captive portal zone name
$config->zoneName = 'my capti';

// a random string, used for csrf checks.
die('change $config->token to a random string unique for your installation!')
$config->token = '';

// the file name under which the generated csv will be saved.
$config->outFile = 'out.csv';

// voucher generation profiles.
// first parameter: name of the profile
// second parameter: number of vouchers
// third parameter: validity in minutes
register_profile('Testing Profile', 100, 120);
register_profile('Testing Profile 2', 200, 120);
