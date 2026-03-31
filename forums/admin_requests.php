<?php


 //
 // This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 // This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 // You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>. 
// Tell header.php to use the admin template
define('PUN_ADMIN_CONSOLE', 1);

define('PUN_ROOT', dirname(__FILE__).'/');
require PUN_ROOT.'include/common.php';
require PUN_ROOT.'include/common_admin.php';


if (!$pun_user['is_admmod'])
	message($lang_common['No permission']);

// Load the admin_index.php language file
require PUN_ROOT.'lang/'.$admin_language.'/admin_index.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_type'], $_POST['request_action'], $_POST['request_user_id']))
{
	confirm_referrer('admin_requests.php');
	check_csrf($_POST['csrf_token']);

	$request_type = $_POST['request_type'];
	$request_action = $_POST['request_action'];
	$resetid = intval($_POST['request_user_id']);

	if ($request_type === 'discord')
	{
		if ($request_action === 'approve' && isset($_POST['request_new_value']))
		{
			$resetidnew = $db->escape(pun_trim($_POST['request_new_value']));
			$db->query('UPDATE '.$db->prefix.'users SET discord=\''.$resetidnew.'\', discord_new=NULL, discord_reason=NULL WHERE id='.$resetid) or error('Unable to update user', __FILE__, __LINE__, $db->error());
			redirect('admin_requests.php', 'Request was approved');
		}
		else if ($request_action === 'decline')
		{
			$db->query('UPDATE '.$db->prefix.'users SET discord_new=NULL, discord_reason=NULL WHERE id='.$resetid) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
			redirect('admin_requests.php', 'Request was declined');
		}
	}
	else if ($request_type === 'hwid')
	{
		if ($request_action === 'approve' && isset($_POST['request_new_hwid'], $_POST['request_new_ip']))
		{
			$resetidnew = intval($_POST['request_new_hwid']);
			$resetipnew = $db->escape(pun_trim($_POST['request_new_ip']));
			$db->query('UPDATE '.$db->prefix.'users SET hwid='.$resetidnew.', hwid_new=NULL, hwid_reason=NULL, hwid_ip=\''.$resetipnew.'\', hwid_ip_new=NULL, parts=newparts, newparts=NULL WHERE id='.$resetid) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
			redirect('admin_requests.php', 'Request was approved');
		}
		else if ($request_action === 'decline')
		{
			$db->query('UPDATE '.$db->prefix.'users SET hwid_new=NULL, hwid_reason=NULL, hwid_ip_new=NULL WHERE id='.$resetid) or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());
			redirect('admin_requests.php', 'Request was declined');
		}
	}

	redirect('admin_requests.php', 'Unknown parameters');
}


$page_title = array(pun_htmlspecialchars($pun_config['o_board_title']), $lang_admin_common['Admin'], "Requests");
define('PUN_ACTIVE_PAGE', 'requests');
require PUN_ROOT.'header.php';


generate_admin_menu('requests');
							
?>


	<div class="block">
		<h2><span>Discord</span></h2>
		<div id="adintro" class="box">
			<div class="inbox">
			
					
				<table><thead>
						<tr>
							<th class="tc3" scope="col">User</th>
							<th class="tc3" scope="col">Discord ID</th>
								<th class="tc3" scope="col">IP</th>
							<th class="tc3" scope="col">Requested ID</th>
							<th class="tc3" scope="col">Requested IP</th>
							<th class="tc3" scope="col">Reason</th>
							<th class="tc3" scope="col">Actions</th>
					
						</tr>
						<tbody>
						<?php 
						
						$result44 = $db->query('SELECT * FROM '.$db->prefix.'users WHERE discord_reason IS NOT NULL') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

						
						foreach($result44 as $item): ?>
						
						<tr>
						
						
						<td class="tc3"><a href="profile.php?id=<?php echo $item['id'] ?>"><?php echo colorize_group($item['username'],$item['group_id']) ?></a></td>
							<td class="tc3"><?php echo $item['discord'] ?></td>
							<td class="tc3"><?php echo $item['discord_ip'] ?></td>
							<td class="tc3"><?php echo $item['discord_new'] ?></td>
							<td class="tc3"><?php echo $item['discord_ip_new'] ?></td>
							<td class="tc3"><?php echo pun_htmlspecialchars($item['discord_reason']) ?></td>
							<td class="tc3">
								<form method="post" action="admin_requests.php" style="display:inline;margin:0">
									<div>
										<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
										<input type="hidden" name="request_type" value="discord" />
										<input type="hidden" name="request_action" value="approve" />
										<input type="hidden" name="request_user_id" value="<?php echo $item['id'] ?>" />
										<input type="hidden" name="request_new_value" value="<?php echo pun_htmlspecialchars($item['discord_new']) ?>" />
										<button type="submit" style="background:none;border:0;color:#71c4ff;cursor:pointer;font:inherit;padding:0;text-decoration:underline">Approve</button>
									</div>
								</form>
								/
								<form method="post" action="admin_requests.php" style="display:inline;margin:0">
									<div>
										<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
										<input type="hidden" name="request_type" value="discord" />
										<input type="hidden" name="request_action" value="decline" />
										<input type="hidden" name="request_user_id" value="<?php echo $item['id'] ?>" />
										<button type="submit" style="background:none;border:0;color:#71c4ff;cursor:pointer;font:inherit;padding:0;text-decoration:underline">Decline</button>
									</div>
								</form>
							</td>
							
							
							
							
							
</tr>


<?php endforeach; ?> 
						<?php if(empty($result44) || !$db->num_rows($result44)): ?> 
						<tr>
							<td colspan="8" style="text-align: center;">Discord ID requests are empty.</td>
						<tr>
						<?php endif; ?> 


								</tbody>
								
					</thead>
					</table>
					
					</div>
		</div>

		<h2 class="block2"><span>Hardware</span></h2>
		<div id="adintro" class="box">
			<div class="inbox">
		
	
					<table><thead>
						<tr>
							<th class="tc3" scope="col">User</th>
							<th class="tc3" scope="col">Parts</th>
							<th class="tc3" scope="col">IP</th>
							<th class="tc3" scope="col">New Parts</th>
							<th class="tc3" scope="col">Requested IP</th>
							<th class="tc3" scope="col">Reason</th>
							<th class="tc3" scope="col">Actions</th>
					
						</tr>
						<tbody>
						<?php 
						
						$result447 = $db->query('SELECT * FROM '.$db->prefix.'users WHERE hwid_reason IS NOT NULL') or error('Unable to fetch user info', __FILE__, __LINE__, $db->error());

						
						foreach($result447 as $item): ?>
						
						<tr>
						
						
						<td class="tc3"><a href="profile.php?id=<?php echo $item['id'] ?>"><?php echo colorize_group($item['username'],$item['group_id']) ?></a></td>
							
							<td class="tc3"><?php echo pun_htmlspecialchars($item['parts']) ?></td>
							<td class="tc3"><?php echo $item['registration_ip'] ?></td>
							<td class="tc3"><?php echo pun_htmlspecialchars($item['newparts']) ?></td>
							<td class="tc3"><?php echo $item['hwid_ip_new'] ?></td>
							<td class="tc3"><?php echo pun_htmlspecialchars($item['hwid_reason']) ?></td>
							<td class="tc3">
								<form method="post" action="admin_requests.php" style="display:inline;margin:0">
									<div>
										<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
										<input type="hidden" name="request_type" value="hwid" />
										<input type="hidden" name="request_action" value="approve" />
										<input type="hidden" name="request_user_id" value="<?php echo $item['id'] ?>" />
										<input type="hidden" name="request_new_hwid" value="<?php echo intval($item['hwid_new']) ?>" />
										<input type="hidden" name="request_new_ip" value="<?php echo pun_htmlspecialchars($item['hwid_ip_new']) ?>" />
										<button type="submit" style="background:none;border:0;color:#71c4ff;cursor:pointer;font:inherit;padding:0;text-decoration:underline">Approve</button>
									</div>
								</form>
								/
								<form method="post" action="admin_requests.php" style="display:inline;margin:0">
									<div>
										<input type="hidden" name="csrf_token" value="<?php echo pun_csrf_token(); ?>" />
										<input type="hidden" name="request_type" value="hwid" />
										<input type="hidden" name="request_action" value="decline" />
										<input type="hidden" name="request_user_id" value="<?php echo $item['id'] ?>" />
										<button type="submit" style="background:none;border:0;color:#71c4ff;cursor:pointer;font:inherit;padding:0;text-decoration:underline">Decline</button>
									</div>
								</form>
							</td>
							
							
							
							
							
</tr>


<?php endforeach; ?> 
						<?php if(empty($result447) || !$db->num_rows($result447)): ?> 
						<tr>
							<td colspan="8" style="text-align: center;">Hardware ID requests are empty.</td>
						<tr>
						<?php endif; ?> 


								</tbody>
								
					</thead>
					</table>
				
			
		</div>
	</div>
	<div class="clearer"></div>
</div>





<?php

require PUN_ROOT.'footer.php';

							
							
		
