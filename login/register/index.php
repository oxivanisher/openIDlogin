<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	# this user is already logged in...
	sysmsg ("You are already registred and logged in!", 1);
	updateTimestamp($_SESSION[openid_identifier]);

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
