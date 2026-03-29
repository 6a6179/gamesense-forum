<?php
//for own use please change this otherwise you could
//be faced by exploit
// this file is used to configure the database connection
$db_type = 'mysqli_innodb';
$db_host = 'localhost';
$db_name = 'database';
$db_username = 'username';
$db_password = 'password';
$db_prefix = 'gs_';
$p_connect = false;

// for own use/forum please change this otherwise you could
// be faced by exploits
$cookie_name = 'pun_cookie_2a0439';
$cookie_domain = '';
$cookie_path = '/';
$cookie_secure = 0;
$cookie_seed = '29f615ba38d74704';

// Optional: only these proxy IPs/CIDRs may supply forwarded client IP headers.
// define('FORUM_TRUSTED_PROXIES', array('203.0.113.10', '198.51.100.0/24'));

define('PUN', 1);
