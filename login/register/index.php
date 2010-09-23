<?php
#only load as module?
if ($_SESSION[loggedin] == 1) {
	# this user is logged in
	updateTimestamp($_SESSION[openid_identifier]);

	#check for officer or higher
	if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][role] >= 6) {
		sysmsg ("You are allowed to use this Module", 2);
		if ($_POST[mydo] == "approve") {
			$GLOBALS[html] .= "- "; sysmsg ("Aproove the user ".$_POST[applicant], 1); $GLOBALS[html] .= "<br />";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][userapplicationtable]." SET state='1',answer='".$_POST[answer]
							."' WHERE openid='".$_POST[applicant]."';");

			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userapplicationtable]." WHERE openid='".$_POST[applicant]."';");
			while ($row = mysql_fetch_array($sql)) {
				foreach ($row as $key => $value)
					$tmp[$key] = $value;
			}

			#do the db stuff
			#oom openid
			$sql = "INSERT INTO ".$GLOBALS[cfg][userprofiletable].
				" (openid,nickname,email,surname,forename,dob,mob,yob,sex,icq,msn,skype,accurate,role) VALUES".
				" ('".$tmp[openid]."', '".$tmp[nickname]."', '".$tmp[email]."', '".$tmp[surname]."', '".$tmp[forename].
				"', '".$tmp[dob]."', '".$tmp[mob]."', '".$tmp[yob]."', '".$tmp[sex]."', '".$tmp[icq]."', '".$tmp[msn].
				"', '".$tmp[skype]."', '0', '5');";
			$sqlq = mysql_query($sql);

			#oom xmpp
			if ($tmp[jid])
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][xmpptable]." SET openid='".$tmp[openid]."', xmpp='".$tmp[jid]."';");

			#smf
			$tmpsex = 0;
			if ($tmp[sex] == "M")
				$tmpsex = 1;
			elseif ($tmp[sex] == "F")
				$tmpsex = 2;
			$sql = "INSERT INTO smf_members".
				" (openid_uri,member_name,email_address,birthdate,gender,icq,msn,real_name,lngfile) VALUES".
				" ('".$tmp[openid]."', '".$tmp[nickname]."', '".$tmp[email].
				"', '".$tmp[yob]."-".$tmp[mob]."-".$tmp[dob]."', '".$tmpsex."', '".$tmp[icq]."', '".$tmp[msn].
				"', '".$tmp[nickname]."', 'german-utf8');";
			$sqlq = mysql_query($sql);

			#show sucess message
			sendMail($tmp[email], "Herzlich Willkommen (Deine Bewerbung)", "Deine Bewerbung zur Gilde Alptroeim wurde akzeptiert!\nHerzlich Willkommen!");
			applyProfile ($tmp[openid], '5');

			if ($tmp[sex] == "F")
				$padawan = "unserer neuesten Mitstreiterin";
			else
				$padawan = "unserem neuesten Mitstreiter";

			informUsers ("Ein herzliches Willkommen ".$padawan.": ".$tmp[nickname], "5");
			sysmsg ("User accepted to guild: ".$tmp[nickname].", ".$tmp[openid], 1);
		} elseif ($_POST[mydo] == "deny") {
			#sendMail(, "Absage (Deine Bewerbung)", "Deine Bewerbung wurde leider abgelehnt. Dies ist die Nachricht dazu:\n".$_POST[answer]);
			$GLOBALS[html] .= "- "; sysmsg ("Deny the user ".$_POST[applicant], 1); $GLOBALS[html] .= "<br />";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][userapplicationtable]." SET state='2',answer='".$_POST[answer]."' WHERE openid='".$_POST[applicant]."';");
			#show deny message
			informUsers ("Benutzer ".$_POST[applicant]." wurde abgewiesen.", "6");
			sysmsg ("User denied: ".$tmp[nickname].", ".$tmp[openid], 1);
		}

		if ($_POST[mydo] == "showapplicant") {
			$GLOBALS[html] .= "- "; sysmsg ("Showing applicant details of ".$_POST[applicant], 1); $GLOBALS[html] .= "<br /><br />";
			#showapplicant detail chart
			$GLOBALS[html] .= "<table>";
			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userapplicationtable]." WHERE openid='".$_POST[applicant]."';");
			while ($row = mysql_fetch_array($sql)) {
			#display list of applicants
				#$_POST[applicant]
				$GLOBALS[html] .= "<form action='?' method='POST'>";
				$GLOBALS[html] .= "<input type='hidden' name='applicant' value='".$_POST[applicant]."' />";
				$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
				$GLOBALS[html] .= "<tr><td>Nickname:</td><td>".$row[nickname]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Email:</td><td>".$row[email]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Surname:</td><td>".$row[surname]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Forename:</td><td>".$row[forename]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Sex:</td><td>".$row[sex]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Birthday:</td><td>".$row[dob].".".$row[mob].".".$row[yob]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>JabberID:</td><td>".$row[jid]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>ICQ#</td><td>".$row[icq]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>MSN:</td><td>".$row[msn]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Skype:</td><td>".$row[skype]."</td></tr>";
				$GLOBALS[html] .= "<tr><td valign='top'>Application:</td><td>".str_replace("\n", "<br />", $row[comment])."</td></tr>";
				$GLOBALS[html] .= "<tr><td>WOW Chars:</td>";
				$GLOBALS[html] .= "<td>";
				foreach (unserialize($row[wowchars]) as $mycharname) {
					if ($char = fetchArmoryCharacter($mycharname)) {
						$GLOBALS[html] .= genArmoryIlvlHtml($char[ilevelavg],$char[level]).
															"<span class='".genArmoryClassClass($char[classid])."' title='".showArmoryName("race", $char[raceid]).
															", ".showArmoryName("gender", $char[genderid]).", ".showArmoryName("faction", $char[factionid])."'>".$char[name]." ".
															"</span>";
						} else {
							$GLOBALS[html] .= genArmoryIlvlHtml(0,"00").$mycharname;
						}
				}
				$GLOBALS[html] .= "</td></tr>";
				$GLOBALS[html] .= "<tr><td>Application Age:</td><td>".getAge($row[timestamp])."</td></tr>";
				$GLOBALS[html] .= "<tr><td colspan='2'><hr /></td></tr>";
				$GLOBALS[html] .= "<tr><td valign='top'>Answer:</td><td>";
				$GLOBALS[html] .= "<textarea name='answer' rows='4' cols='50'>Begr&uuml;ndung an den Bewerber</textarea></td></tr>";
				$GLOBALS[html] .= "<tr><td valign='top'>&nbsp;</td><td>";
					$GLOBALS[html] .= "<input type='radio' name='mydo' value='approve' />Approve&nbsp;&nbsp;";
					$GLOBALS[html] .= "<input type='radio' name='mydo' value='deny' />Deny&nbsp;&nbsp;";
					$GLOBALS[html] .= "<input type='radio' name='mydo' value='showapplicant' checked='checked' /> Nothing";
					$GLOBALS[html] .= "</td></tr>";
				$GLOBALS[html] .= "<tr><td>&nbsp;</td><td><input type='submit' name='submit' value='submit' /></td></tr>";
				$GLOBALS[html] .= "</form>";

			}
			$GLOBALS[html] .= "</table>";

		} else {
			$GLOBALS[html] .= "- "; sysmsg ("Display list of applicants", 1); $GLOBALS[html] .= "<br /><br />";
			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userapplicationtable]." WHERE state='0';");
			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><th>Nickname</th><th>Email</th><th>OpenID</th><th>Birthday</th><th>Characters</th></tr>";
			while ($row = mysql_fetch_array($sql)) {
			#display list of applicants
				#$_POST[applicant]
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&mydo=showapplicant&applicant=".
													$row[openid]."'>".$row[nickname]."</a></td>";
				$GLOBALS[html] .= "<td>".$row[email]."</td>";
				$GLOBALS[html] .= "<td>".$row[openid]."</td>";
				$GLOBALS[html] .= "<td>".$row[dob].".".$row[mob].".".$row[yob]."</td>";

				$GLOBALS[html] .= "<td>";
				foreach (unserialize($row[wowchars]) as $mycharname) {
					if ($char = fetchArmoryCharacter($mycharname)) {
						$GLOBALS[html] .= genArmoryIlvlHtml($char[ilevelavg],$char[level]).
															"<span class='".genArmoryClassClass($char[classid])."' title='".showArmoryName("race", $char[raceid]).
															", ".showArmoryName("gender", $char[genderid]).", ".showArmoryName("faction", $char[factionid])."'>".$char[name]." ".
															"</span>";
						} else {
							$GLOBALS[html] .= genArmoryIlvlHtml(0,"00").$mycharname;
						}
				}
				$GLOBALS[html] .= "</td>";
				$GLOBALS[html] .= "</tr>";
			}
			$GLOBALS[html] .= "</table>";
		}
	} else {
		$GLOBALS[html] .= "- "; sysmsg ("You are not allowed to use this Module", 1); $GLOBALS[html] .= "<br />";
	}

} elseif (($_POST[mydo] == "save") AND ($_SESSION[registred] == 1)) {
#	$GLOBALS[standalonedesign] = 1;
	# save profile informations

	#generate WOW Character array
	$myWowChars = array();
	for ($i = 1; $i < 50; $i++) {
		$myname = "char".$i."Name";
		if (isset($_POST[$myname]))
			if (! empty($_POST[$myname]))
				array_push($myWowChars, chop($_POST[$myname]));
	}

	$sql = "INSERT INTO ".$GLOBALS[cfg][userapplicationtable].
		" (openid,nickname,email,surname,forename,dob,mob,yob,sex,jid,icq,msn,skype,comment,wowchars,timestamp) VALUES ".
		"('".$_POST[openid]."','".$_POST[nickname]."','".$_POST[email]."','".$_POST[surname]."','".$_POST[forename].
		"','".$_POST[dob]."','".$_POST[mob]."','".$_POST[yob]."','".$_POST[sex]."','".$_POST[jid]."','".$_POST[icq].
		"','".$_POST[msn]."','".$_POST[skype]."','".$_POST[comment]."','".serialize($myWowChars)."','".time()."');";
	$sqlr = mysql_query($sql);

	# show "you will be accepted" text

	sendMail($_POST[email], "Deine Bewerbung", "Deine Bewerbung wurde im System gespeichert.");
	sysmsg ("Saved application of ".$_SESSION[newopenid], 1);
	informUsers ($_POST[nickname]." hat sich auf der Website beworben.", "7");
	killCookies();
	setcookie (session_id(), "", time() - 3600);
	session_destroy();
	session_write_close();

	$GLOBALS[html] .= templGetFile("success.html");

} elseif ($_SESSION[registerme] == 1) {
	# stage 2 of openid register
#	$GLOBALS[standalonedesign] = 1;
	$success = openid_verify();
	fetchUsers();

#FIXME enable me! check if user is already in profile db

	foreach ($GLOBALS[users][byuri] as $myUser) {
		if ($myUser[uri] == $GLOBALS[newopenid]) {
			sysmsg ("Registration: OpenID already used by ".$myUser[name], 1);
			$GLOBALS[html] .= "<br />";
			$_SESSION[registred] = 0;
			$success = 0;
		}
	}

	if ($success) {
		sysmsg ("Register OpenID: ".$GLOBALS[newopenid]." verification sucessful!", 2);
		$_SESSION[toregister] = 0;
		$_SESSION[tosave] = 1;
		$_SESSION[newopenid] = $GLOBALS[newopenid];

		$cont = templGetFile("register.html");
		$cont = templReplText($cont, "OPENID", $GLOBALS[newopenid]);
		$GLOBALS[html] .= $cont;
	} else {
		$GLOBALS[redirect] = 1;
		sysmsg ("Register OpenID: ".$GLOBALS[newopenid]." Verification failed on registration.", 1);
		killCookies();
		session_destroy();
	}


} elseif ($_POST[mydo] == "registerme") {
	# stage 1 of openid register
	$_SESSION[registerme] = 1;
	$_SESSION[registred] = 0;
	$_SESSION[registering] = 1;
	$_SESSION[tmp][referer] = "";
	$GLOBALS[standalonedesign] = 1;
	openid_auth();

} else {
	# show the openid formular (default view)

	$GLOBALS[html] .= templGetFile("welcome.html");
}


?>
