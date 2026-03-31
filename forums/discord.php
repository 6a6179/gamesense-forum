<?php

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);
if ($pun_user['is_guest'])
	message($lang_common['No permission']);

require __DIR__.'/vendor/autoload.php';

session_start();

function discord_render_page($message, $show_form = false)
{
	header('Content-Type: text/html; charset=UTF-8');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Discord / GameSense</title>
	<style type="text/css">
		body {
			margin: 0;
			font-family: Arial, sans-serif;
			background: #101010;
			color: #eee;
		}
		.page {
			min-height: 100vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 24px;
		}
		.panel {
			width: 100%;
			max-width: 420px;
			padding: 32px;
			border: 1px solid #222;
			background: #171717;
			text-align: center;
		}
		h1 {
			margin: 0 0 16px;
			font-size: 40px;
			font-weight: 700;
		}
		p {
			margin: 0 0 24px;
			font-size: 16px;
			line-height: 1.5;
		}
		button {
			border: 0;
			padding: 12px 18px;
			background: #5865f2;
			color: #fff;
			font-size: 14px;
			cursor: pointer;
		}
	</style>
</head>
<body>
	<div class="page">
		<div class="panel">
			<h1>GameSense</h1>
			<p><?php echo pun_htmlspecialchars($message); ?></p>
<?php if ($show_form): ?>			<form method="post" action="discord.php">
				<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
				<button type="submit" name="redirect" value="1">Continue with Discord</button>
			</form>
<?php endif; ?>		</div>
	</div>
</body>
</html>
<?php

	exit;
}

function discord_clear_state()
{
	if (isset($_SESSION['oauth2state']))
		unset($_SESSION['oauth2state']);
}

function discord_redirect($status)
{
	discord_clear_state();
	header('Location: discord.php?status='.urlencode($status));
	exit;
}

$discord_settings = array(
	'client_id' => getenv('GS_DISCORD_CLIENT_ID'),
	'client_secret' => getenv('GS_DISCORD_CLIENT_SECRET'),
	'redirect_uri' => getenv('GS_DISCORD_REDIRECT_URI'),
	'bot_token' => getenv('GS_DISCORD_BOT_TOKEN'),
	'guild_id' => getenv('GS_DISCORD_GUILD_ID'),
	'premium_role_id' => getenv('GS_DISCORD_PREMIUM_ROLE_ID'),
);

foreach ($discord_settings as $setting_value)
{
	if ($setting_value === false || $setting_value === '')
		discord_render_page('Discord linking unavailable.');
}

$provider = new \Wohali\OAuth2\Client\Provider\Discord(array(
	'clientId' => $discord_settings['client_id'],
	'clientSecret' => $discord_settings['client_secret'],
	'redirectUri' => $discord_settings['redirect_uri'],
));

if (isset($_GET['status']))
{
	switch ($_GET['status'])
	{
		case 'success':
			discord_render_page('Discord account linked successfully.');
			break;
		case 'mismatch':
			discord_render_page('Your Discord account does not match the one already linked. Sign in with the expected account and try again.', true);
			break;
		case 'error':
		default:
			discord_render_page('Discord linking failed. Please try again.', true);
			break;
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redirect']))
{
	check_csrf($_POST['csrf_token']);

	$auth_url = $provider->getAuthorizationUrl(array(
		'scope' => array('guilds.join', 'identify'),
	));
	$_SESSION['oauth2state'] = $provider->getState();

	header('Location: '.$auth_url);
	exit;
}

if (!isset($_GET['code']))
	discord_render_page('Connect your Discord account to continue.', true);

if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state'])
	discord_redirect('error');

try
{
	$result = $db->query('SELECT discord, group_id, csgo FROM '.$db->prefix.'users WHERE id='.(int) $pun_user['id']) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
	if (!$db->num_rows($result))
		discord_redirect('error');

	$forum_user = $db->fetch_assoc($result);
	$token = $provider->getAccessToken('authorization_code', array(
		'code' => $_GET['code'],
	));
	$resource_owner = $provider->getResourceOwner($token);
	$discord_id = (string) $resource_owner->getId();

	$remote_addr = $db->escape(get_remote_address());

	if (!empty($forum_user['discord']) && $forum_user['discord'] !== $discord_id)
	{
		$db->query('UPDATE '.$db->prefix.'users SET discord_new=\''.$db->escape($discord_id).'\', discord_ip_new=\''.$remote_addr.'\' WHERE id='.(int) $pun_user['id']) or error('Unable to update Discord mismatch data', __FILE__, __LINE__, $db->error());
		discord_redirect('mismatch');
	}

	$subscription_is_active = false;
	$subscription_expiry = forum_parse_datetime($forum_user['csgo']);
	if ($subscription_expiry !== null)
		$subscription_is_active = ($subscription_expiry > new DateTime('now'));

	if ($forum_user['group_id'] == 4 || !$subscription_is_active)
		discord_redirect('error');

	$client = new \RestCord\DiscordClient(array(
		'token' => $discord_settings['bot_token']
	));

	$client->guild->addGuildMember(array(
		'guild.id' => (int) $discord_settings['guild_id'],
		'user.id' => (int) $discord_id,
		'access_token' => (string) $token,
		'nick' => $pun_user['username'],
		'roles' => array((int) $discord_settings['premium_role_id'])
	));

	$db->query('UPDATE '.$db->prefix.'users SET discord=\''.$db->escape($discord_id).'\', discord_ip=\''.$remote_addr.'\', discord_new=NULL, discord_ip_new=NULL WHERE id='.(int) $pun_user['id']) or error('Unable to update Discord data', __FILE__, __LINE__, $db->error());

	discord_redirect('success');
}
catch (Exception $e)
{
	discord_redirect('error');
}
