<html>
	<head>
		<title>JOIN</title>
<link rel="stylesheet" href="http://alptroeim.ch/login.css" type="text/css" media="screen" />
	</head>
	<body style="background-image:url(http://alptroeim.ch/site/games/WoW/template_background.jpg);">
                <form method="get" action="/login.inc.php" name="login">
		<input type="hidden" name="mydo" value="registerme">

	<div style="padding:20px;background-image:url(http://alptroeim.ch/img/transparent1.png);">
		<h1>Bei Alptr&ouml;im bewerben!</h1>

		Um dich bei unserer Gilde zu Bewerben ben&ouml;tigst du eine <a class="regLink"  href="http://de.wikipedia.org/wiki/OpenID" target="_blank">OpenID</a>.<br>
		Wenn du noch keine <a class="regLink" href="http://de.wikipedia.org/wiki/OpenID" target="_blank">OpenID</a> besitzt darfst du dir gerne bei unseren <a href="https://oom.ch/openid/" target="_blank">OpenID-Provider</a><br>
		eine ID erstellen indem du folgenden Link aufrufst: <a href="https://oom.ch/openid/users/register" target="_blank">OpenID bei OOM.ch registrieren</a>
		<hr><br>
		<div><div class="regSpace">Deine OpenID:</div><input class="regOpenID" type="text" name="ssoInpUsername" ></div><br>
		<div><div class="regSpace">&nbsp;</div><input type="submit" name="submit" value="Bewerbung starten"></div><br>
		<hr>
		<h3>Was ist eine OpenID und wieso brauchen wir das?</h3>
		Eine OpenID ist eine art Schl&uuml;ssel welcher f&uuml;r mehrere Web-basierte Dienste benutzt werden kann.<br>
		alptr&ouml;im.ch bietet verschiedene Dienste die alle eine g&uuml;ltige Authentifizierung erfordern, um <br>
		zu vermeiden das immer wieder ein Passwort beim zugriff auf die verschiedenen Dienste eingegeben<br>
		werden muss ist der OpenID Schl&uuml;ssel da, welcher eine dezentrale anmeldung erm&ouml;glicht.<br>
		<br>
		<h3>Habe ich vieleicht schon eine OpenID und wie sehen die aus?</h3><br>
		<div><h4>Google</h4>
			Wenn du einen Google-Account hast zum Beispiel f&uuml;r dein gMail Konto oder alle anderen Google Dienste<br>
			hast du bereits eine OpenID und kannst diese auch f&uuml;r das Anmelden an alptroeim.ch verwenden.<br><br>
			<h5>Wie sehen Google OpenID's aus?</h5>
			<b>Beispiel 1: </b> <i class="regExample">http://openid-provider.appspot.com/DeinGoogleBenutzerName@googlemail.com</i><br>	
			<b>Beispiel 2: </b> <i class="regExample">http://openid-provider.appspot.com/wasauchimmer@gmx.net</i><br>
		</div>				
		<br>
		<div><h4>OOM</h4>
			OOM OpenID's sind von unserem eigenen OpenID-Provider, wer sich hiere eine OpenID macht hat den<br>
			Vorteil das jeweils nur der Benutzername und nicht die ganze OpenID angegebenen werden muss.<br>
			(Achtung! Gross/Kleinschreibung wird unterschieden)<br>
			<a href="https://oom.ch/openid/users/register" target="_blank">jetzt bei OOM.ch registrieren</a><br><br>
			<h5>Wie sehen OOM OpenID's aus?</h5>
			<b>Beispiel 1:</b> <i class="regExample">https://oom.ch/openid/identity/Smogg</i></div>
			<b>Beispiel 2:</b> <i class="regExample">https://oom.ch/openid/identity/deinewahl@beispiel.com</i></div>	
	</div>
	</form>

