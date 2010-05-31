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


		#register opeinid to user
		} elseif (($_POST[myjob] == "registeruser") and (! empty($_POST[newuser])) and (! empty($_POST[newurl]))) {
			$GLOBALS[html] .= "<h3>=&gt; Registring SMF User ID ".$_POST[newuser]." to ".$_POST[newurl]."</h3>";
			
			if (isValidURL($_POST[newurl])) {
				$GLOBALS[html] .= "- ".$_POST[newurl]." is a valid URL<br />";

				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][usernametable]." SET openid_uri='".$_POST[newurl]."' WHERE id_member='".$_POST[newuser]."';");

				$GLOBALS[html] .= "<h3>=&gt; User registred!</h3>";
			} else {
				$GLOBALS[html] .= "<h3>=&gt; Not a valid URL!</h3>";
			}


		#kick user offline
		} elseif (($_POST[myjob] == "kickoffline") and (! empty($_POST[user]))) {
			$GLOBALS[html] .= "<h3>=&gt; Kicking User ID ".$_POST[user]." Offline</h3>";
			#kicking user offline -> forcing logout in browser
			# 1 killing his oom openid internal sessiontable
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][sessiontable]." SET hash='".generateHash()."' WHERE openid='".$_POST[user]."';");
			# 2 setting his timestamp into the past
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
							(time() - $GLOBALS[cfg][lastidletimeout] - 10)."' WHERE openid='".$_POST[user]."';");

		#push user idle
		} elseif (($_POST[myjob] == "pushidle") and (! empty($_POST[user]))) {
			$GLOBALS[html] .= "<h3>=&gt; Pushing User ID ".$_POST[user]." Idle</h3>";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
							(time() - $GLOBALS[cfg][lastonlinetimeout] - 10)."' WHERE openid='".$_POST[user]."';");

		#change user openid
		} elseif (($_POST[myjob] == "changeopenid") and (! empty($_POST[user])) and (! empty($_POST[newurl]))) {

			#change openid in db function
			function changeOpenidUrl ($db, $field) {
				$GLOBALS[html] .= "<h3>=&gt; Changing OpenID in DB ".$db."</h3>";
				$sql = mysql_query("UPDATE ".$db." SET ".$field."='".$new."' WHERE ".$field."='".$old."';");
				if ($sql)
					$GLOBALS[html] .= "- Change OK<br />";
				else
					$GLOBALS[html] .= "- Change NOK<br />";
			}

			#kicking user offline -> forcing logout in browser
			# 1 killing his oom openid internal sessiontable
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][sessiontable]." SET hash='".generateHash()."' WHERE openid='".$_POST[user]."';");
			# 2 setting his timestamp into the past
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
							(time() - $GLOBALS[cfg][lastidletimeout] - 10)."' WHERE openid='".$_POST[user]."';");


			#oom OpenID tables
			changeOpenidUrl ($GLOBALS[cfg][sessiontable], "openid");
			changeOpenidUrl ($GLOBALS[cfg][lastonlinedb], "openid");
			changeOpenidUrl ($GLOBALS[cfg][chat][usertable], "openid");
			changeOpenidUrl ($GLOBALS[cfg][msg][msgtable], "sender");
			changeOpenidUrl ($GLOBALS[cfg][msg][msgtable], "receiver");
			changeOpenidUrl ($GLOBALS[cfg][msg][xmpptable], "openid");

			#smf
			changeOpenidUrl (smf_members, "openid_uri");
			
			#wiki
			changeOpenidUrl (WIKI_user_openid, "uoi_openid");

			#wordpress
			changeOpenidUrl (wp_openid_identities, "url");
			changeOpenidUrl (wp_users, "user_url");
		}


		#init stuff
		fetchUsers();
		$uDropdown = drawUsersDropdown();
		$pDropdown = drawProfileDropdown();
		$smfuDropdown = drawSmfUsersDropdown();

		#change user rights form
		$GLOBALS[html] .= "<hr />";
		$GLOBALS[html] .= "<h2>Register User with Portal System</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='registeruser' />";
		$GLOBALS[html] .= "<td>".$smfuDropdown." to <input type='text' name='newurl' value='' size='40' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<h2>Change User Rights</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='applyprofile' />";
		$GLOBALS[html] .= "<td>".$uDropdown." to ".$pDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<h2>Push User Idle</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='pushidle' />";
		$GLOBALS[html] .= "<td>".$uDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<h2>Kick User Offline</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='kickoffline' />";
		$GLOBALS[html] .= "<td>".$uDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<h2>Change User OpenID</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='changeopenid' />";
		$GLOBALS[html] .= "<td>".$uDropdown." to <input type='text' name='newurl' value='' size='40' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

	} else {
		$GLOBALS[html] .= "<b>= You are not allowed to use this module!</b>";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}


?>
