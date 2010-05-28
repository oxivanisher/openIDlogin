<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_SESSION[isadmin] == 1) {
		$GLOBALS[html] .= "- you are allowed to use this module<br />";
		$sqlv = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE openid<>'".$_SESSION[openid_identifier]."';");
		while ($rowv = mysql_fetch_array($sqlv))
			$GLOBALS[html] .= "&nbsp;- also allowed: ".$rowv[openid]."<br />";

		#apply profile function
		if (($_POST[myjob] == "applyprofile") and (! empty($_POST[user])) and (! empty($_POST[profile]))) {
			$GLOBALS[html] .= "<h3>=&gt; Changing User ".$_POST[user]." to ".
							$GLOBALS[cfg][profile][$_POST[profile]][name]."</h3>";
			fetchUsers();
	
			#wordpress
			if (! empty($GLOBALS[users][byuri][$_POST[user]][wordpress])) {
				$GLOBALS[html] .= "- modifying wordpress user ".$GLOBALS[users][byuri][$_POST[user]][wordpress]." :)<br />";
				$sql = mysql_query("UPDATE wp_usermeta SET meta_key='".$GLOBALS[cfg][profile][$_POST[profile]][wordpress].
						"'WHERE user_id='".$GLOBALS[users][byuri][$_POST[user]][wordpress]."' AND meta_key='wp_user_level';");
			}

			#smf
			if (! empty($GLOBALS[users][byuri][$_POST[user]][smf])) {
				$GLOBALS[html] .= "- modifying smf user ".$GLOBALS[users][byuri][$_POST[user]][smf]." :)<br />";
				$sql = mysql_query("UPDATE smf_members SET id_group='".$GLOBALS[cfg][profile][$_POST[profile]][smf].
						"', lngfile='german-utf8', additional_groups='' WHERE id_member='".$GLOBALS[users][byuri][$_POST[user]][smf]."';");
			}

			#eqdkp
			if (! empty($GLOBALS[users][byuri][$_POST[user]][eqdkp])) {
				$GLOBALS[html] .= "- modifying eqdkp user ".$GLOBALS[users][byuri][$_POST[user]][eqdkp]." :)<br />";
				$sql = mysql_query("SELECT auth_id,auth_value FROM eqdkp_auth_options WHERE 1 ORDER BY auth_id asc;");
				while ($row = mysql_fetch_array($sql)) {
					$GLOBALS[module][eqdkp][$row[auth_id]] = $row[auth_value];
				}

				$sql = mysql_query("SELECT user_id FROM eqdkp_users WHERE username='".
							$GLOBALS[cfg][profile][$_POST[profile]][eqdkp]."';");
				while ($row = mysql_fetch_array($sql))
					$tmpid = $row[user_id];

				$sql = mysql_query("SELECT auth_id,auth_setting FROM eqdkp_auth_users WHERE user_id='".
						$tmpid."' ORDER BY auth_id asc;");
				while ($row = mysql_fetch_array($sql)) {
					$GLOBALS[module][eqdkp2][$row[auth_id]] = $row[auth_setting];
				}

				$sql = mysql_query("SELECT auth_id,auth_setting FROM eqdkp_auth_users WHERE user_id='".
						$GLOBALS[users][byuri][$_POST[user]][eqdkp]."' ORDER BY auth_id asc;");
				while ($row = mysql_fetch_array($sql)) {
					$GLOBALS[module][eqdkp3][$row[auth_id]] = $row[auth_setting];
				}

				#foreach auth_id
				foreach (array_keys($GLOBALS[module][eqdkp]) as $myname) {
					#is empty
					if (empty($GLOBALS[module][eqdkp3][$myname])) {
						$mode = 1;
					} else {
						$mode = 0;
					}

					if (empty($GLOBALS[module][eqdkp2][$myname])) {
						$value = "N";
					} else {
						$value = $GLOBALS[module][eqdkp2][$myname];
					}

					if ($mode) {
						$sql = mysql_query("INSERT INTO eqdkp_auth_users (user_id, auth_id, auth_setting) VALUES ('".
								$GLOBALS[users][byuri][$_POST[user]][eqdkp]."', '".$myname."', '".$value."');");
#						$GLOBALS[html] .= "&nbsp;- ".$GLOBALS[cfg][sites][eqdkp][$_POST[user]]." AuthID: ".$myname." to ".$value." (new)<br />";
					} else {
						$sql2 = mysql_query("UPDATE eqdkp_auth_users SET auth_setting='".$value."' WHERE user_id='".
								$GLOBALS[users][byuri][$_POST[user]][eqdkp]."' AND auth_id='".$myname."';");
#						$GLOBALS[html] .= "&nbsp;- ".$GLOBALS[cfg][sites][eqdkp][$_POST[user]]." AuthID: ".$myname." to ".$value." (update)<br />";
					}


					$sqlz = mysql_query("UPDATE eqdkp_users SET user_active='1', user_lang='german' WHERE user_id='".$GLOBALS[users][byuri][$_POST[user]][eqdkp]."';");
				}
				

			}
			$GLOBALS[html] .= "<h3>=&gt; Changes done</h3>";

		} elseif (($_POST[myjob] == "registeruser") and (! empty($_POST[newuser])) and (! empty($_POST[newurl]))) {
			$GLOBALS[html] .= "<h3>=&gt; Registring SMF User ID ".$_POST[newuser]." to ".$_POST[newurl]."</h3>";
			
			if (isValidURL($_POST[newurl])) {
				$GLOBALS[html] .= "- ".$_POST[newurl]." is a valid URL<br />";

				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][usernametable]." SET openid_uri='".$_POST[newurl]."' WHERE id_member='".$_POST[newuser]."';");

				$GLOBALS[html] .= "<h3>=&gt; User registred!</h3>";
			} else {
				$GLOBALS[html] .= "<h3>=&gt; Not a valid URL!</h3>";
			}
		}

		#init stuff
		fetchUsers();
		$uDropdown = drawUsersDropdown();
		$pDropdown = drawProfileDropdown();
		$smfuDropdown = drawSmfUsersDropdown();

		#change user rights form
		$GLOBALS[html] .= "<hr />";
		$GLOBALS[html] .= "<h2>Register Forum User with Portal System</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='registeruser' />";
		$GLOBALS[html] .= "<td>OpenID URL: <input type='text' name='newurl' value='' size='40' /> to ".$smfuDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table>";

		$GLOBALS[html] .= "<h2>Change User Rights</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='applyprofile' />";
		$GLOBALS[html] .= "<td>".$uDropdown." to ".$pDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table>";

	} else {
		$GLOBALS[html] .= "<b>= You are not allowed to use this module!</b>";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}


?>
