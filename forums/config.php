<?php
$db_type = getenv('FORUM_DB_TYPE') ?: 'mysqli_innodb';
$db_host = getenv('FORUM_DB_HOST') ?: 'localhost';
$db_name = getenv('FORUM_DB_NAME') ?: 'gamesense_forum';
$db_username = getenv('FORUM_DB_USER') ?: 'gamesense';
$db_password = getenv('FORUM_DB_PASSWORD') ?: 'CHANGE_PASSWORD';
$db_prefix = getenv('FORUM_DB_PREFIX') ?: 'gs_';
$p_connect = false;

$cookie_name = getenv('FORUM_COOKIE_NAME') ?: 'pun_cookie_64bitspw';
$cookie_domain = getenv('FORUM_COOKIE_DOMAIN') ?: '';
$cookie_path = getenv('FORUM_COOKIE_PATH') ?: '/';
$cookie_secure = (getenv('FORUM_COOKIE_SECURE') ?: '0') === '1' ? 1 : 0;
$cookie_same_site = getenv('FORUM_COOKIE_SAMESITE') ?: 'Lax';
$cookie_seed = getenv('FORUM_COOKIE_SEED') ?: 'CHANGE_PASSWORD_256BITPW';
$allow_insecure_defaults = (getenv('FORUM_ALLOW_INSECURE_DEFAULTS') ?: '0') === '1';

// Optional: only these proxy IPs/CIDRs may supply forwarded client IP headers.
// define('FORUM_TRUSTED_PROXIES', array('203.0.113.10', '198.51.100.0/24'));

define('PUN', 1);
