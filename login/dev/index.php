<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_SESSION[isdev] == 1) {
		$GLOBALS[html] .= "- you are allowed to use this module<br />";
		$sqlv = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE openid<>'".$_SESSION[openid_identifier]."' AND dev='1';");
		while ($rowv = mysql_fetch_array($sqlv))
			$GLOBALS[html] .= "&nbsp;- also allowed: ".$rowv[openid]."<br />";

		#kick user offline
		if (($_POST[myjob] == "kickoffline") and (! empty($_POST[user]))) {
			$GLOBALS[html] .= "<h3>=&gt; Kicking User ID ".$_POST[user]." Offline</h3>";
			#kicking user offline -> forcing logout in browser
			# 1 killing his oom openid internal sessiontable
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][sessiontable]." SET hash='".generateHash()."' WHERE openid='".$_POST[user]."';");
			# 2 setting his timestamp into the past
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
							(time() - $GLOBALS[cfg][lastidletimeout] - 10)."' WHERE openid='".$_POST[user]."';");

			sysmsgs ("Kicked User ".$_POST[user]." offline!",1);

		#send testmail to user
		} elseif (($_POST[myjob] == "sendmail") and (! empty($_POST[user]) and (! empty($_POST[text])))) {
			sysmsg ("Sent testmessage to: ".$_POST[user], 2);
			if ($tmp = sendMail($_POST[user], "Test Message", $_POST[text]))
				$GLOBALS[html] .= "<h3>=&gt; Sending mail to user: ".$_POST[user]." succesful (".$tmp.")</h3>";
			else
				$GLOBALS[html] .= "<h3>=&gt; Sending mail to user: ".$_POST[user]." failed!</h3>";
				


		#push user idle
		} elseif (($_POST[myjob] == "pushidle") and (! empty($_POST[user]))) {
			$GLOBALS[html] .= "<h3>=&gt; Pushing User ID ".$_POST[user]." Idle</h3>";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
							(time() - $GLOBALS[cfg][lastonlinetimeout] - 10)."' WHERE openid='".$_POST[user]."';");
			sysmsg ("User ".$_POST[user]." pushed idle", 1);

		#change user openid
		} elseif (($_POST[myjob] == "changeopenid") and (! empty($_POST[user])) and (! empty($_POST[newurl]))) {

			#change openid in db function
			function changeOpenidUrl ($db, $field) {
				$GLOBALS[html] .= "<h3>=&gt; Changing OpenID in DB ".$db."</h3>";
				$sql = mysql_query("UPDATE ".$db." SET ".$field."='".$_POST[newurl]."' WHERE ".$field."='".$_POST[user]."';");
#				echo "UPDATE ".$db." SET ".$field."='".$_POST[newurl]."' WHERE ".$field."='".$_POST[user]."';\n";
				if ($sql)
					sysmsg ("OpenID Change OK on: ".$db, 1);
				else
					sysmsg ("OpenID Change Not OK on: ".$db, 1);
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
      changeOpenidUrl ($GLOBALS[cfg][admintablename], "openid");
      changeOpenidUrl ($GLOBALS[cfg][systemmsgsdb], "user");
      changeOpenidUrl ($GLOBALS[cfg][frontendsafetable], "openid");
      changeOpenidUrl ($GLOBALS[cfg][userprofiletable], "openid");
			changeOpenidUrl ($GLOBALS[cfg][mg][namestable], "openid");

			#smf
			changeOpenidUrl ("smf_members", "openid_uri");
			
			#wiki
			changeOpenidUrl ("WIKI_user_openid", "uoi_openid");

			#wordpress
			changeOpenidUrl ("wp_openid_identities", "url");
			changeOpenidUrl ("wp_users", "user_url");

		#enablereqdebug
		} elseif ($_POST[myjob] == "enablereqdebug") {
			$_SESSION[reqdebug] = 1;
		#disablereqdebug
		} elseif ($_POST[myjob] == "disablereqdebug") {
			$_SESSION[reqdebug] = 0;
		#setjsdebug
		} elseif ($_POST[myjob] == "setjsdebug") {
			$_SESSION[jsdebug] = $_POST[jsdebug];
		#setjsversion
		} elseif ($_POST[myjob] == "setjsversion") {
			$_SESSION[jsversion] = $_POST[jsversion];
		#setsystemphpdebug
		} elseif ($_POST[myjob] == "setsystemphpdebug") {
			$_SESSION[jsversion] = $_POST[jsversion];
		#setsystemversion
		} elseif ($_POST[myjob] == "setsystemversion") {
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][settingstable]." SET value='".$_POST[version]."' WHERE name='jsversion';");
			sysmsg ("Changed Systemversion to: ".$_POST[version], 1);
		#setsystemphpdebug
		} elseif ($_POST[myjob] == "setsysmsglvl") {
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][settingstable]." SET value='".$_POST[level]."' WHERE name='sysmsglvl';");
			sysmsg ("Changed System PHP Message Level to: ".$_POST[phpdebug], 1);
		#disablephpdebug
		} elseif ($_POST[myjob] == "disablephpdebug") {
			$_SESSION[phpdebug] = 0;
		} elseif ($_POST[myjob] == "enablephpdebug") {
			$_SESSION[phpdebug] = 1;
		}

		#init stuff
		fetchUsers();
		$uDropdown = drawUsersDropdown();
		$pDropdown = drawProfileDropdown();
		$smfuDropdown = drawSmfUsersDropdown();

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		if ($_SESSION[reqdebug] == 0) {
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='enablereqdebug' />";
			$GLOBALS[html] .= "<td><h3>Enable your Request Log &gt; </h3></td>";
		} else {
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='disablereqdebug' />";
			$GLOBALS[html] .= "<td><h3>Disable your Request Log &gt; </h3></td>";
		}
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		if ($_SESSION[phpdebug] == 0) {
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='enablephpdebug' />";
			$GLOBALS[html] .= "<td><h3>Enable your PHP Debug &gt; </h3></td>";
		} else {
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='disablephpdebug' />";
			$GLOBALS[html] .= "<td><h3>Disable your PHP Debug &gt; </h3></td>";
		}
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='setjsversion' />";
		$GLOBALS[html] .= "<td><h3>Set YOUR JavaScript Version (curr: ".$_SESSION[jsversion]."):</h3></td>";
		$GLOBALS[html] .= "<td><input type='text' name='jsversion' value='".$_SESSION[jsversion]."' size='2' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='setjsdebug' />";
		$GLOBALS[html] .= "<td><h3>Set YOUR JavaScript Debug (curr: ".$_SESSION[jsdebug]."):</h3></td>";
		$GLOBALS[html] .= "<td><input type='text' name='jsdebug' value='".$_SESSION[jsdebug]."' size='2' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='setsystemversion' />";
		$GLOBALS[html] .= "<td><h3>Set SYSTEM Version (curr: ".$GLOBALS[jsversion]."):</h3></td>";
		$GLOBALS[html] .= "<td><input type='text' name='version' value='".$GLOBALS[jsversion]."' size='2' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='setsysmsglvl' />";
		$GLOBALS[html] .= "<td><h3>Set SYSTEM PHP Log Level (curr: ".$GLOBALS[sysmsglvl]."):</h3></td>";
		$GLOBALS[html] .= "<td><input type='text' name='level' value='".$GLOBALS[sysmsglvl]."' size='2' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='pushidle' />";
		$GLOBALS[html] .= "<td><h3>Push User Idle:</h3></td>";
		$GLOBALS[html] .= "<td>".$uDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='kickoffline' />";
		$GLOBALS[html] .= "<td><h3>Kick User Offline:</h3></td>";
		$GLOBALS[html] .= "<td>".$uDropdown."</td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='changeopenid' />";
		$GLOBALS[html] .= "<td><h3>Change User OpenID</h3></td>";
		$GLOBALS[html] .= "<td>".$uDropdown." to <input type='text' name='newurl' value='' size='40' /></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='sendmail' />";
		$GLOBALS[html] .= "<td><h3>Send Testmail</h3><br />To: ".$uDropdown."</td>";
		$GLOBALS[html] .= "<td><textarea name='text' cols='20' rows='3'>Text</textarea></td>";
		$GLOBALS[html] .= "<td><input type='submit' name='submit' value='submit' /></td>";
		$GLOBALS[html] .= "</tr></form></table><br />";

		$GLOBALS[html] .= "<table><form action='?' method='POST'><tr>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='sendmail' />";
		$GLOBALS[html] .= "<td><h3>Send Testmail</h3><br />To: <input type='text' size='15' name='user' /></td>";
		$GLOBALS[html] .= "<td><textarea name='text' cols='20' rows='3'>Text</textarea></td>";
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
