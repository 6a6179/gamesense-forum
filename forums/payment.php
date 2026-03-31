<?php
define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';

if ($pun_user['is_guest'])
	redirect('login.php', 'Please log in to continue.');

if ($pun_user['g_read_board'] == '0')
	message($lang_common['No view']);

$action = isset($_GET['action']) ? $_GET['action'] : null;
$allowed_plans = array(
	'30' => '30 days - $0.00',
	'90' => '90 days - $0.00',
	'365' => '365 days - $0.00',
	'1825' => '5 years - $0.00',
);

$premium_group_id = 5;
$result = $db->query('SELECT g_id FROM '.$db->prefix.'groups WHERE g_title=\'Premium\' ORDER BY g_id ASC LIMIT 1') or error('Unable to fetch premium group', __FILE__, __LINE__, $db->error());
if ($db->num_rows($result))
	list($premium_group_id) = $db->fetch_row($result);

if (isset($_POST['form_sent']))
{
	confirm_referrer('payment.php');
	check_csrf($_POST['csrf_token']);

	$game = isset($_POST['game']) ? pun_trim($_POST['game']) : '';
	$plan = isset($_POST['plan']) ? pun_trim($_POST['plan']) : '';

	if ($game !== 'csgo' || !isset($allowed_plans[$plan]))
		message($lang_common['Bad request'], false, '404 Not Found');

	$days = (int) $plan;
	$now = new DateTime('now');
	$expires_at = clone $now;

	$current_expiry = forum_parse_datetime($pun_user['csgo']);
	if ($current_expiry !== null && $current_expiry > $now)
		$expires_at = $current_expiry;

	$expires_at->modify('+'.$days.' days');
	$new_group_id = ((int) $pun_user['group_id'] === (int) $pun_config['o_default_user_group']) ? (int) $premium_group_id : (int) $pun_user['group_id'];

	$db->query('UPDATE '.$db->prefix.'users SET csgo=\''.$db->escape($expires_at->format('Y-m-d H:i:s')).'\', group_id='.$new_group_id.' WHERE id='.(int) $pun_user['id']) or error('Unable to update subscription', __FILE__, __LINE__, $db->error());
	forum_sync_chat_user_role($pun_user['id'], $new_group_id);

	redirect('payment.php?action=complete&amp;days='.$days, 'Payment completed');
}

$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), 'Payment');
$page_head = array(
	'payment_css' => '<style type="text/css">.payment-field { box-sizing: border-box; width: 20em; padding: 5px; } #stripe-elements-card { box-sizing: border-box; width: 20em; padding: 5px; background-color: #212122; border: 1px solid #3e3e3e; }</style>'
);
define('PUN_ACTIVE_PAGE', 'premium');
require PUN_ROOT.'header.php';

$is_member = ((int) $pun_user['group_id'] === (int) $pun_config['o_default_user_group']);
$expires_text = null;
$current_expiry = forum_parse_datetime($pun_user['csgo']);
if ($current_expiry !== null)
	$expires_text = format_time($current_expiry->getTimestamp(), false, null, null, false, true);
else if (!empty($pun_user['csgo']))
	$expires_text = pun_htmlspecialchars($pun_user['csgo']);
?>
<div class="blockform">
	<h2><span><?php echo $is_member ? 'Buy GameSense' : 'Extend GameSense'; ?></span></h2>
	<div class="box">
<?php if ($action === 'complete'): ?>
		<div id="confirmation" class="fakeform">
			<div class="status success">
				<div class="flex-row">
					<div class="flex-col flex-30">
						<img class="statusimg" src="/static/img/checkmark.svg" alt="Success" />
					</div>
					<div class="flex-col flex-70">
						<h1>Success!</h1>
						<p class="note">Your subscription has been extended successfully.</p>
<?php if ($expires_text !== null): ?>
						<p class="note">Premium now expires on <strong><?php echo $expires_text; ?></strong>.</p>
<?php endif; ?>
						<p class="note"><a href="index.php">Return to the forum index</a></p>
					</div>
				</div>
			</div>
		</div>
<?php else: ?>
		<div id="confirmation" class="fakeform hidden">
			<div class="status success hidden">
				<div class="flex-row">
					<div class="flex-col flex-30">
						<img class="statusimg" src="/static/img/checkmark.svg" alt="Success" />
					</div>
					<div class="flex-col flex-70">
						<h1>Success!</h1>
						<p class="note">Your subscription has been extended successfully.</p>
					</div>
				</div>
			</div>
			<br />
			<div class="status error hidden">
				<div class="flex-row">
					<div class="flex-col flex-30">
						<img class="statusimg" src="/static/img/warning.svg" alt="Warning" />
					</div>
					<div class="flex-col flex-70">
						<h1>Payment failed</h1>
						<p>It looks like your payment could not be completed at this time. Avoid using a VPN and be sure to enter your details carefully.</p>
						<p class="error-message"></p>
					</div>
				</div>
			</div>
			<div class="status wechat hidden">
				<div class="flex-row">
					<div id="wechat_qr" class="flex-col flex-30">
					</div>
					<div class="flex-col flex-70">
						<h1>WeChat Pay</h1>
						<p class="note">This page will be automatically updated upon completing your payment.</p>
					</div>
				</div>
			</div>
		</div>

		<div id="payment-container">
			<div class="centered">
				<div id="loading" class="spinner hidden centered">
					<div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div>
				</div>
			</div>
			<form id="payment_options" method="post" action="payment.php?game=csgo">
				<div class="inform">
					<input type="hidden" name="form_sent" value="1" />
					<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
					<fieldset>
						<div class="fakeform">
							<table class="aligntop">
								<tbody>
									<tr>
										<th scope="row">Username</th>
										<td><input class="payment-field" id="username" type="text" name="username" value="<?php echo pun_htmlspecialchars($pun_user['username']); ?>" maxlength="80" /></td>
									</tr>
									<tr>
										<th scope="row">Email</th>
										<td><input class="payment-field" id="billing_email" type="text" name="email" value="<?php echo pun_htmlspecialchars($pun_user['email']); ?>" maxlength="80" /></td>
									</tr>
									<tr>
										<th scope="row">Game</th>
										<td>
											<select id="game" class="payment-field" name="game">
												<option value="csgo" selected="selected">Counter-Strike: Global Offensive</option>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">Plan</th>
										<td>
											<select id="plan" class="payment-field" name="plan">
<?php foreach ($allowed_plans as $plan_days => $label): ?>
												<option value="<?php echo $plan_days; ?>"><?php echo $label; ?></option>
<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">Method</th>
										<td>
											<select id="pmethod" class="payment-field" name="pmethod">
												<option value="card">Card</option>
											</select>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</fieldset>
					<div class="centered">
						<input class="button" id="payment_options_submit" type="submit" value="Continue" />
					</div>
				</div>
			</form>

			<form id="billing_info" method="post" class="hidden">
				<div id="billinginfocc">
					<div class="inform">
						<fieldset>
							<div class="fakeform">
								<p>All sensitive cardholder data is transmitted directly through our payment processor using TLS. GameSense is fully compliant with the <a href="https://www.pcisecuritystandards.org/pci_security/" target="_blank">PCI Data Security Standards</a>.</p>
								<table class="aligntop">
									<tbody>
										<tr>
											<th scope="row">Full name</th>
											<td><input class="payment-field fullname required" id="billing_name" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">Billing address (line 1)</th>
											<td><input class="payment-field address_line1 required" id="billing_address_line1" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">Billing address (line 2)</th>
											<td><input class="payment-field" id="billing_address_line2" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">City</th>
											<td><input class="payment-field" id="billing_address_city" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">State / Province / Region</th>
											<td><input class="payment-field" id="billing_address_state" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">Postal code</th>
											<td><input class="payment-field" id="billing_address_postal_code" type="text" maxlength="80" autocomplete="off" /></td>
										</tr>
										<tr>
											<th scope="row">Country</th>
											<td>
												<select class="payment-field" id="billing_address_country"></select>
											</td>
										</tr>
										<tr>
											<th scope="row">Card details</th>
											<td>
												<div id="stripe-elements-card">
													<div id="card-element"></div>
												</div>
												<div id="card_error_box" class="inform hidden">
													<ul id="card-errors">
														<li id="card-error"></li>
													</ul>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</fieldset>
						<div class="centered">
							<input class="button" id="billing_info_submit" type="submit" value="Submit" />
						</div>
					</div>
				</div>
			</form>
		</div>
<?php endif; ?>
	</div>
</div>

<?php
require PUN_ROOT.'footer.php';
