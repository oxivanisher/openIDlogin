<?php
$m_time = explode(" ",microtime());
$m_time = $m_time[0] + $m_time[1];
$starttime = $m_time;

#load config
$GLOBALS[cfg][moduledir] = 'login';
require_once($GLOBALS[cfg][moduledir].'/conf.inc.php');

#do the mysql connection
$con = @mysql_pconnect($GLOBALS[cfg][mysqlHost], $GLOBALS[cfg][mysqlUser], $GLOBALS[cfg][mysqlPW])
    or exit("Connection failed.");
@mysql_select_db ($GLOBALS[cfg][mysqlDB], $con)
    or exit("Database not found.");
mysql_set_charset('utf8',$con);

session_start();

#load functions
global $pape_policy_uris;
$pape_policy_uris = array(PAPE_AUTH_MULTI_FACTOR_PHYSICAL, PAPE_AUTH_MULTI_FACTOR, PAPE_AUTH_PHISHING_RESISTANT);
require_once($GLOBALS[cfg][moduledir].'/functions.inc.php');
require_once($GLOBALS[cfg][moduledir].'/openid.inc.php');

#define empty status var
$GLOBALS[redirect] = 0;
$GLOBALS[myreturn][loggedin] = 0;

#browser debug "woraround". working with POST but accepting GET also.
if (empty($_POST)) $_POST = $_GET;

#one session only magic!
if ((($_POST[job] != "login") OR ($_POST[job] != "verify")) AND ($_SESSION[hash]))
	checkSession();

#find out what we have to do (like magic)
if ($_POST[ssoInpLogout] == 1) {
	$_POST[job] = "logout";
	$_SESSION[user][nickname] = "";
} elseif ($_POST[job] == "verify") {
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
} elseif ($_POST[job] == "status") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "update") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_SESSION[loggedin] == 1) {
	$_POST[job] = "refresh";
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "login") {
	$_POST[job] = "auth";
	$_SESSION[user][nickname] = $_POST[ssoInpUsername];
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} else {
	$_POST[job] = "nix zu tun";
}

#and then do it
switch ($_POST[job]) {
	case "auth":
		if (empty($_POST[ssoInpUsername])) {
			$_SESSION[error] = "no_ssoInpUsername_recieved";
			$GLOBALS[redirect] = 1;
		} else {
			$GLOBALS[html] = "<br /><br /><br /><h2><center>Authorization in progress</center></h2><br /><br /><br />";
			$GLOBALS[myreturn][msg] = "auth in progress";
			$_SESSION[error] = "";

			openid_auth();

		}
		break;

	case "verify":
		openid_verify();

		if ($_SESSION[loggedin]) {
			checkSites();
			setCookies();
			createSession();

			$GLOBALS[myreturn][loggedin] = 1;
			$GLOBALS[myreturn][msg] = "loggedin";
			$GLOBALS[redirect] = 1;
			$GLOBALS[html] = "<br /><br /><br /><h2><center>Identity Verified!</center></h2><br /><br /><br />";
			$_SESSION[error] = "";

		} else {
			$GLOBALS[myreturn][msg] = "auth error";
			$_SESSION[error] = "auth_error";
			$GLOBALS[html] = "<br /><br /><br /><h2><center>Authentification Error:</center></h2><br /><h3><center>".$GLOBALS[html]."</center></h3><br /><br />";
		}
		$GLOBALS[freshlogin] = 1;
		break;
	
	case "refresh":
		fetchUsers();
		setCookies();

		$cookieTarget = str_replace($GLOBALS[cfg][openid_identifier_base], "", $_SESSION[openid_identifier]);
		setcookie ("ssoOldname", $cookieTarget, ( time() + ( 7 * 24 * 3600 )));

		$GLOBALS[myreturn][loggedin] = 1;

		if (! empty($_POST[module])) {
			$GLOBALS[myreturn][msg] = "load module ".$_POST[module];
			$GLOBALS[html] = "";
			if (file_exists('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php')) {
				$GLOBALS[html] .= "<h2><a href='?'>Module Index</a></h2>";
				$GLOBALS[html] .= "<ul><li><h3><a href='?module=".$_POST[module]."'>".$_POST[module]." Index</a></h3></li></ul>";
				include('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php');
				$GLOBALS[html] .= "<br />";
			} else {
				$GLOBALS[html] .= "<b>Module ".$_POST[module]." not found!</b>";
			}
		} else {
			$GLOBALS[myreturn][msg] = "refreshing";
			$GLOBALS[html] = "<h2>Module Index</h2><br />";
			if (! empty($_SESSION[error]))
				$GLOBALS[html] .= "ERROR: ".$_SESSION[error];
				$GLOBALS[html] .= "<ul>";

	
			if (is_dir($GLOBALS[cfg][moduledir])) {
			    if ($dh = opendir($GLOBALS[cfg][moduledir])) {
	        		while (($file = readdir($dh)) !== false) {
								if (is_dir($GLOBALS[cfg][moduledir]."/".$file))
						if (($file != ".") AND ($file != "..") AND ($file != "Auth"))
			            	$GLOBALS[html] .= "<li><h3><a href='?module=".$file."'>$file</a></h3></li>";
			        }
			        closedir($dh);
				}
				$GLOBALS[html] .= "</ul>";
			} 
			$GLOBALS[html] .= "<br />";	
	
		}
		$GLOBALS[html] .= "<br />";	
		$_SESSION[error] = "";
		break;
	
	case "logout":
#		$GLOBALS[myreturn][felloffline] = $GLOBALS[forcelogout];
#		header('X-JSON: '.json_encode($GLOBALS[myreturn]).'');
		$myoldid = $_SESSION[openid_identifier];
		killCookies();
		setcookie (session_id(), "", time() - 3600);
		session_destroy();
		session_write_close();
		$_SESSION[error] = "";
		$GLOBALS[html] = "<br /><br /><br /><h2><center>Logging out...</center></h2><br /><br /><br />";
		$GLOBALS[redirect] = 1;
		break;

	#login.js calls this on page reloads
	case "status":
		fetchUsers();
		getOnlineUsers();
		if ($_SESSION[loggedin]) {
			setCookies();

			$cookieTarget = str_replace($GLOBALS[cfg][openid_identifier_base], "", $_SESSION[openid_identifier]);
			setcookie ("ssoOldname", $cookieTarget, ( time() + ( 7 * 24 * 3600 )));

			$GLOBALS[myreturn][loggedin] = 1;
			updateLastOnline();
		}
		$GLOBALS[myreturn][msg] = "status";
		jasonOut();

		break;

	#login.js calls this on periodically updates
	case "update":
		fetchUsers();
		getOnlineUsers();
		if ($_SESSION[loggedin]) {
			setCookies();

			$cookieTarget = str_replace($GLOBALS[cfg][openid_identifier_base], "", $_SESSION[openid_identifier]);
			setcookie ("ssoOldname", $cookieTarget, ( time() + ( 7 * 24 * 3600 )));

			$GLOBALS[myreturn][loggedin] = 1;
		} 
		$GLOBALS[myreturn][msg] = "status";
		jasonOut();

		break;

	default:
		if (! empty($_SESSION[error])) {
			$_SESSION[error] = "";
		}
		$GLOBALS[myreturn][msg] = "nothing";
		$GLOBALS[html] = "<h2>You are not logged in!</h2>".$_SESSION[error];
}

#last online implementation
if ($_POST[job] == "logout")
	$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".(time() - $GLOBALS[cfg][lastidletimeout] - 1)."' WHERE openid='".$myoldid."';");

#generate final html output
echo "<html><head><title>".$_SERVER[SERVER_NAME]." OpenID Administration</title>";
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css1]."' type='text/css' />\n";
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css2]."' type='text/css' />\n";
echo "</head>";
echo "\n<script language='JavaScript'>\n";
echo "<!--\nfunction ssoRefresh()\n{\n";
echo "if (eval(document.openid_message)) {\n";
echo "document.openid_message.submit();\n";
echo "} else {\n";
echo "tloc = '";
if (! empty($_SESSION[tmp][referer])) {
	echo $_SESSION[tmp][referer];
	$_SESSION[tmp][referer] = "";
} else
  echo $GLOBALS[cfg][targetsite];

	echo "';\n";
	echo "window.parent.location = tloc;";

	echo "}\n";
	echo "}\n-->\n</script>\n";

if ($GLOBALS[redirect]) {
	$GLOBALS[html] .= "<br /><br /><br /><br /><h2><center>-&gt; Redirecting</center></h2><br /><br /><br />";
	echo "<body onLoad='ssoRefresh();' style='".$GLOBALS[cfg][standalonebodystlye]."'>\n";
} elseif ($GLOBALS[submitform]) {
	$GLOBALS[html] .= "<br /><br /><br /><br /><h2><center>-&gt; Submitting</center></h2><br /><br /><br />";
	echo "<body onLoad='ssoRefresh();' style='".$GLOBALS[cfg][standalonebodystlye]."'>\n";
} else {	
	echo "<script type='text/javascript' src='tablesort.js'></script>";
	echo "<script type='text/javascript' src='login.js'></script>";
	echo "<script type='text/javascript' src='prototype.js'></script>";

	?><script language="javascript">
	$(document).ready(function()
 	   {
			     $("#myTable").tablesorter(); 
			} 
	);
	</script><?php

	echo "<body onload='javascript:ssoInit();' style='background-color:transparent;'>";
	echo "<div id='ssologin'></div>";

}

if (! empty($GLOBALS[html])) {
	echo "<div style='background-color:transparent; background-image:url(/img/transparent.png); padding:10px; height:100%;'><h1><center> "
				.$_SERVER[SERVER_NAME]." <img src='/".$GLOBALS[cfg][moduledir]."/openid-icon-100x100.png' width='40' height='40'> OpenID </center></h1>";
	if (! empty($_SESSION[openid_identifier]))
		echo "<center>[ you are logged in as: ".$_SESSION[openid_identifier]." ]</center>";
	echo "<hr /><br />";
	echo $GLOBALS[html]."<br />";

	$round = 3;// The number of decimal places to round the micro time to.
	$m_time = explode(" ",microtime());
	$totaltime = (($m_time[0] + $m_time[1]) - $starttime);
	echo "<hr /><center>Page loading took:". round($totaltime,$round) ." seconds</center><br /><br /></div>";
	echo "</body>\n</html>";
}
if (! empty($_SESSION[error]))
	echo "ERROR: ".$_SESSION[error];


if ($GLOBALS[debug]) echo "\n<!-- ".json_encode($GLOBALS[myreturn])." -->";

#all done. hope you are happy with the result :) baba
exit;
?>

