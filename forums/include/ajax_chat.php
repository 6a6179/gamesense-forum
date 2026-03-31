<?php

if (!defined('PUN'))
	exit;

function forum_get_shoutbox_content()
{
	static $shoutbox_content = null;

	if ($shoutbox_content !== null)
		return $shoutbox_content;

	if (!defined('AJAX_CHAT_URL'))
		define('AJAX_CHAT_URL', 'chat/');

	if (!defined('AJAX_CHAT_PATH'))
		define('AJAX_CHAT_PATH', PUN_ROOT.'chat/');

	if (!is_file(AJAX_CHAT_PATH.'lib/classes.php'))
	{
		$shoutbox_content = '';
		return $shoutbox_content;
	}

	require_once AJAX_CHAT_PATH.'lib/classes.php';

	$ajaxChat = new CustomAJAXChatShoutBox();
	$shoutbox_content = $ajaxChat->getShoutBoxContent();

	return $shoutbox_content;
}
