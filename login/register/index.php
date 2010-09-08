<?php
#only load as module?
if ($_SESSION[loggedin] == 1) {
	# this user is logged in
	updateTimestamp($_SESSION[openid_identifier]);

	#check for officer or higher
	if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][role] >= 7) {
		sysmsg ("You are allowed to use this Module", 2);
		if ($_POST[mydo] == "approve") {
			$GLOBALS[html] .= "- "; sysmsg ("Aproove the user ".$_POST[applicant], 1); $GLOBALS[html] .= "<br />";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][userapplicationtable]." SET state='1' WHERE openid='".$_POST[applicant]."';");

			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userapplicationtable]." WHERE openid='".$_POST[applicant]."';");
			while ($row = mysql_fetch_array($sql)) {
				foreach ($row as $key => $value)
					$tmp[$key] = $value;
			}

			#do the db stuff
			#oom openid
			$sql = "INSERT INTO ".$GLOBALS[cfg][userprofiletable].
				" (openid,nickname,email,surname,forename,dob,mob,yob,sex,icq,msn,accurate,role) VALUES".
				" ('".$tmp[openid]."', '".$tmp[nickname]."', '".$tmp[email]."', '".$tmp[surname]."', '".$tmp[forename].
				"', '".$tmp[dob]."', '".$tmp[mob]."', '".$tmp[yob]."', '".$tmp[sex]."', '".$tmp[icq]."', '".$tmp[msn].
				"', '1', '5');";
			$sqlq = mysql_query($sql);

			#oom xmpp
			if ($tmp[jid])
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][xmpptable]." SET openid='".$tmp[openid]."', xmpp='".$tmp[jid]."';");

			#smf
			$tmpsex = 0;
			if ($tmp[sex] == "M")
				$tmpsex = 1;
			elseif ($tmp[sex] == "W")
				$tmpsex = 2;
			$sql = "INSERT INTO smf_members".
				" (openid_uri,member_name,email_address,birthdate,gender,icq,msn,real_name,lngfile) VALUES".
				" ('".$tmp[openid]."', '".$tmp[nickname]."', '".$tmp[email].
				"', '".$tmp[yob]."-".$tmp[mob]."-".$tmp[dob]."', '".$tmpsex."', '".$tmp[icq]."', '".$tmp[msn].
				"', '".$tmp[nickname]."', 'german-utf8');";
			$sqlq = mysql_query($sql);

			#show sucess message
			informUsers ("User ".$tmp[nickname]." accepted to the gild.", "7");
		} elseif ($_POST[mydo] == "deny") {
			$GLOBALS[html] .= "- "; sysmsg ("Deny the user ".$_POST[applicant], 1); $GLOBALS[html] .= "<br />";
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][userapplicationtable]." SET state='2' WHERE openid='".$_POST[applicant]."';");
			#show deny message
			informUsers ("User ".$tmp[nickname]." denied to the gild.", "7");

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
				$GLOBALS[html] .= "<tr><td valign='top'>Application:</td><td>".str_replace("\n", "<br />", $row[comment])."</td></tr>";
				$GLOBALS[html] .= "<tr><td>WOW Chars:</td><td>".$row[wowchars]."</td></tr>";
				$GLOBALS[html] .= "<tr><td>Application Age:</td><td>".getAge($row[timestamp])."</td></tr>";
				$GLOBALS[html] .= "<tr><td colspan='2'><hr /></td></tr>";
				$GLOBALS[html] .= "<tr><td valign='top'>Answer:</td><td>";
				$GLOBALS[html] .= "<textarea name='answer' rows='4' cols='50'>Text for the User</textarea></td></tr>";
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
			$GLOBALS[html] .= "<tr><th>Nickname</th><th>Email</th><th>OpenID</th><th>Birthday</th></tr>";
			while ($row = mysql_fetch_array($sql)) {
			#display list of applicants
				#$_POST[applicant]
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&mydo=showapplicant&applicant=".
													$row[openid]."'>".$row[nickname]."</a></td>";
				$GLOBALS[html] .= "<td>".$row[email]."</td>";
				$GLOBALS[html] .= "<td>".$row[openid]."</td>";
				$GLOBALS[html] .= "<td>".$row[dob].".".$row[mob].".".$row[yob]."</td>";
				$GLOBALS[html] .= "</tr>";
			}
			$GLOBALS[html] .= "</table>";
		}
	} else {
		$GLOBALS[html] .= "- "; sysmsg ("You are not allowed to use this Module", 1); $GLOBALS[html] .= "<br />";
	}

} elseif (($_POST[mydo] == "save") AND ($_SESSION[registred] == 1)) {
	$GLOBALS[standalonedesign] = 1;
	# save profile informations

	#generate WOW Character json
	$myWowChars = $_POST[wowchars];

	$sql = "INSERT INTO ".$GLOBALS[cfg][userapplicationtable].
		" (openid,nickname,email,surname,forename,dob,mob,yob,sex,jid,icq,msn,comment,wowchars,timestamp) VALUES ".
		"('".$_POST[openid]."','".$_POST[nickname]."','".$_POST[email]."','".$_POST[surname]."','".$_POST[forename].
		"','".$_POST[dob]."','".$_POST[mob]."','".$_POST[yob]."','".$_POST[sex]."','".$_POST[jid]."','".$_POST[icq].
		"','".$_POST[msn]."','".$_POST[comment]."','".$myWowChars."','".time()."');";
#	$GLOBALS[html] .= $sql;
	$sqlr = mysql_query($sql);

	# show "you will be accepted" text

	sysmsg ("Saved application of ".$_SESSION[newopenid], 1);
	informUsers ("New application waiting: ".$_POST[nickname], "7");
	killCookies();
	setcookie (session_id(), "", time() - 3600);
	session_destroy();
	session_write_close();
	?>




	<?php

	$GLOBALS[html] .= "<br /><h2><a href='".$GLOBALS[cfg][targetsite]."'>Zur&uuml;ck zur Homepage</a></h2>";

} elseif ($_SESSION[registerme] == 1) {
	# stage 2 of openid register
	$GLOBALS[standalonedesign] = 1;
	openid_verify();
	fetchUsers();

#	print_r($GLOBALS);

/*	foreach ($GLOBALS[users][byuri] as $myUser) {
		if ($myUser[uri] == $GLOBALS[newopenid]) {
			sysmsg ("Registration: OpenID already used by ".$myUser[name], 1);
			$GLOBALS[html] .= "<br />";
			$_SESSION[registred] = 0;
		}
	} */

	if ($_SESSION[registred]) {
		sysmsg ("Register OpenID: ".$GLOBALS[newopenid]." verification sucessful!", 2);
		$_SESSION[toregister] = 0;
		$_SESSION[tosave] = 1;
		$_SESSION[newopenid] = $GLOBALS[newopenid];

		#need mydo: save

		$GLOBALS[html] .= '<form method="get" action="/login.inc.php" name="login">';
		$GLOBALS[html] .= '<input type="hidden" name="mydo" value="save">';
		$GLOBALS[html] .= '<div style="padding:20px;background-image:url(http://alptroeim.ch/img/transparent1.png);">';

		$GLOBALS[html] .= '<h1>Bewerbungformular</h1>';
		$GLOBALS[html] .= 'Bitte f&uuml;lle die folgenden Felder wahrheitsgetreu aus.';
		$GLOBALS[html] .= '<hr><br>';
		$GLOBALS[html] .= '<div><div class="regSpace">OpenID:</div><input class="regOpenID" type="text" name="openid" value="'.$GLOBALS[newopenid].'" readonly="readonly"></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Benutzername:</div><input class="regField" type="text" name="nickname" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">E-Mail:</div><input class="regField" type="text" name="email" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Vorname:</div><input class="regField" type="text" name="forename" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Name:</div><input class="regField" type="text" name="surname" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Geburtsdatum:</div>';

		$GLOBALS[html] .= 'Tag ';
		$GLOBALS[html] .= '<select name="dob">';
		for ($i = 1; $i <= 31; $i++)
			$GLOBALS[html] .= '<option>'.$i.'</option>';
		$GLOBALS[html] .= '</select>';

		$GLOBALS[html] .= ' Monat ';
		$GLOBALS[html] .= '<select name="mob">';
		for ($i = 1; $i <= 12; $i++)
			$GLOBALS[html] .= '<option>'.$i.'</option>';
		$GLOBALS[html] .= '</select>';

 		$GLOBALS[html] .= ' Jahr ';
		$GLOBALS[html] .= '<select name="yob">';
		for ($i = 2000; $i >= 1950; $i--)
			$GLOBALS[html] .= '<option>'.$i.'</option>';
		$GLOBALS[html] .= '</select>';

		$GLOBALS[html] .= '</div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Geschlecht:</div><select class="regField" name="sex" ><option value="M">M&auml;nnlich</option><option value="W">Weiblich</option></select></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Jabber:</div><input class="regField" type="text" name="jid" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">ICQ#:</div><input class="regField" type="text" name="icq" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">MSN:</div><input class="regField" type="text" name="msn" ></div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Bewerbung:</div><textarea class="regArea" name="comment">';
		$GLOBALS[html] .= 'Wieso moechtest du zu Aptroeim?
...

Bei welchen Gilden warst du vorher?
...

Was sind deine Ziele in WoW?
...

Kennst du schon Leute von uns?
...';
		$GLOBALS[html] .= '</textarea> </div>';
		$GLOBALS[html] .= '<div><div class="regSpace">Deine WoW Charakter:</div><input class="regField" type="text" name="wowchars" ></div>';

		$GLOBALS[html] .= '<div><div class="regSpace">&nbsp;</div><input type="submit" name="submit" value="Bewerbung einsenden"></div><br>';
		$GLOBALS[html] .= '</div></form>';

	} else {
		sysmsg ("Register OpenID: ".$GLOBALS[newopenid]." Verification failed on registration.", 1);
		$GLOBALS[html] .= "<br /><h2><a href='".$GLOBALS[cfg][targetsite]."'>Zur&uuml;ck zur Homepage</a></h2>";
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

	$GLOBALS[html] .= '<form method="get" action="/login.inc.php" name="login">';
	$GLOBALS[html] .= '<input type="hidden" name="mydo" value="registerme">';
	$GLOBALS[html] .= '<h1>Bei Alptr&ouml;im bewerben!</h1>';
	$GLOBALS[html] .= 'Um dich bei unserer Gilde zu Bewerben ben&ouml;tigst du eine <a class="regLink" href="http://de.wikipedia.org/wiki/OpenID" target="_blank">OpenID</a>.<br>';
  $GLOBALS[html] .= 'Wenn du noch keine <a class="regLink" href="http://de.wikipedia.org/wiki/OpenID" target="_blank">OpenID</a> ';
	$GLOBALS[html] .= 'besitzt darfst du dir gerne bei unseren <a href="https://oom.ch/openid/" target="_blank">OpenID-Provider</a><br>';
	$GLOBALS[html] .= 'eine ID erstellen indem du folgenden Link aufrufst: <a href="https://oom.ch/openid/users/register" target="_blank">OpenID bei OOM.ch registrieren</a>';
	$GLOBALS[html] .= '<hr><br>';
	$GLOBALS[html] .= '<div><div class="regSpace">Deine OpenID:</div><input class="regOpenID" type="text" name="ssoInpUsername" ></div><br>';
	$GLOBALS[html] .= '<div><div class="regSpace">&nbsp;</div><input type="submit" name="submit" value="Bewerbung starten"></div><br>';
	$GLOBALS[html] .= '<hr>';
	$GLOBALS[html] .= '<h3>Was ist eine OpenID und wieso brauchen wir das?</h3>';
	$GLOBALS[html] .= 'Eine OpenID ist eine art Schl&uuml;ssel welcher f&uuml;r mehrere Web-basierte Dienste benutzt werden kann.<br>';
	$GLOBALS[html] .= 'alptr&ouml;im.ch bietet verschiedene Dienste die alle eine g&uuml;ltige Authentifizierung erfordern, um <br>';
	$GLOBALS[html] .= 'zu vermeiden das immer wieder ein Passwort beim zugriff auf die verschiedenen Dienste eingegeben<br>';
	$GLOBALS[html] .= 'werden muss ist der OpenID Schl&uuml;ssel da, welcher eine dezentrale anmeldung erm&ouml;glicht.<br>';
	$GLOBALS[html] .= '<br>';
	$GLOBALS[html] .= '<h3>Habe ich vielleicht schon eine OpenID und wie sehen die aus?</h3><br>';
	$GLOBALS[html] .= '<div><h4>Google</h4>';
	$GLOBALS[html] .= 'Wenn du einen Google-Account hast zum Beispiel f&uuml;r dein gMail Konto oder alle anderen Google Dienste<br>';
	$GLOBALS[html] .= 'hast du bereits eine OpenID und kannst diese auch f&uuml;r das Anmelden an alptroeim.ch verwenden.<br><br>';
	$GLOBALS[html] .= '<h5>Wie sehen Google OpenIDs aus?</h5>';
	$GLOBALS[html] .= '<b>Beispiel 1: </b> <i class="regExample">http://openid-provider.appspot.com/DeinGoogleBenutzerName@googlemail.com</i><br>';
	$GLOBALS[html] .= '<b>Beispiel 2: </b> <i class="regExample">http://openid-provider.appspot.com/wasauchimmer@gmx.net</i><br>';
	$GLOBALS[html] .= '</div>';
	$GLOBALS[html] .= '<br>';
	$GLOBALS[html] .= '<div><h4>OOM</h4>';
	$GLOBALS[html] .= 'OOM OpenIDs sind von unserem eigenen OpenID-Provider, wer sich hiere eine OpenID macht hat den<br>';
	$GLOBALS[html] .= 'Vorteil das jeweils nur der Benutzername und nicht die ganze OpenID angegebenen werden muss.<br>';
	$GLOBALS[html] .= '(Achtung! Gross/Kleinschreibung wird unterschieden)<br>';
	$GLOBALS[html] .= '<a href="https://oom.ch/openid/users/register" target="_blank">jetzt bei OOM.ch registrieren</a><br><br>';
	$GLOBALS[html] .= '<h5>Wie sehen OOM OpenIDs aus?</h5>';
	$GLOBALS[html] .= '<b>Beispiel 1:</b> <i class="regExample">https://oom.ch/openid/identity/Smogg</i></div>';
	$GLOBALS[html] .= '<b>Beispiel 2:</b> <i class="regExample">https://oom.ch/openid/identity/deinewahl@beispiel.com</i></div>';
	$GLOBALS[html] .= '</div>';
	$GLOBALS[html] .= '</form>';
}


?>
