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
			fetchUsers();
			applyProfile($_POST[user], $_POST[profile]);

		#register opeinid to user
		} elseif (($_POST[myjob] == "registeruser") and (! empty($_POST[newuser])) and (! empty($_POST[newurl]))) {
			$GLOBALS[html] .= "<h3>=&gt; Registring SMF User ID ".$_POST[newuser]." to ".$_POST[newurl]."</h3>";
			if (isValidURL($_POST[newurl])) {
				$GLOBALS[html] .= "- ".$_POST[newurl]." is a valid URL<br />";
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][usernametable]." SET openid_uri='".$_POST[newurl]."' WHERE id_member='".$_POST[newuser]."';");

				$sql = "SELECT email_address,icq,msn,usertitle,avatar,signature,website_url,personal_text,member_name ".
								"FROM smf_members WHERE openid_uri='".$_POST[newurl]."';";
				$sqlq = mysql_query($sql);
				while ($row = mysql_fetch_array($sqlq)) {
					$temail = $row[email_address];
					$ticq = $row[icq];
					$tmsn = $row[msn];
					$tusertitle = $row[usertitle];
					$tavatar = $row[avatar];
					$tsignature = $row[signature];
					$twebsite = $row[website_url];
					$tmotto = $row[personal_text];
					$tname = $row[member_name];
				}
				
				$sql = "INSERT INTO ".$GLOBALS[cfg][userprofiletable].
								" (openid,nickname,email,icq,msn,usertitle,avatar,signature,website,motto,accurate,role) VALUES ".
								"('".$_POST[newurl]."','".$tname."', ".
								"'".$temail."', '".$ticq."', '".$tmsn."', '".$tusertitle."', '".$tavatar."', '".$tsignature."', '".
								$twebsite."', '".$tmotto."', '0', '0');";
				$sqlq = mysql_query($sql);

				fetchUsers();
				applyProfile($_POST[newurl], '5');

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
		$GLOBALS[html] .= "<h2>Change User Rights</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='applyprofile' />";
		$GLOBALS[html] .= "<td>".$uDropdown." to ".$pDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<h2>Register User with Portal System</h2>";
		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='registeruser' />";
		$GLOBALS[html] .= "<td>".$smfuDropdown." to <input type='text' name='newurl' value='' size='40' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

	} else {
		sysmsg ("You are not allowed to use this module!", 0);
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
