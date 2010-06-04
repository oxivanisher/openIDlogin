<?php #get start time of script
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
mysql_query('set character set utf8;');
mysql_set_charset('UTF8',$con);

#start the php session
session_start();

#load functions
global $pape_policy_uris;
$pape_policy_uris = array(PAPE_AUTH_MULTI_FACTOR_PHYSICAL, PAPE_AUTH_MULTI_FACTOR, PAPE_AUTH_PHISHING_RESISTANT);
require_once($GLOBALS[cfg][moduledir].'/functions.inc.php');
require_once($GLOBALS[cfg][moduledir].'/openid.inc.php');

#request logger if activated (dev function only)
if ($_SESSION[reqdebug]) {
	$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][requestlogtable]." (ts,req,que,scr,ref,input,ip) VALUES ('".time().
					"', '".$_SERVER[REQUEST_URI]."', '".$_SERVER[QUERY_STRING]."', '".$_SERVER[SCRIPT_NAME].
					"', '".$_SERVER[HTTP_REFERER]."', '".json_encode(array("POST" => $_POST, "GET" => $_GET))."', '".getIP()."');");
	$GLOBALS[reqdebugid] = mysql_insert_id();
}

#define vars with default values
$GLOBALS[redirect] = 0;						#don't redirect per default
$GLOBALS[bot] = 0;								#we are not the daemon/bot
$GLOBALS[myreturn][loggedin] = 0;	#default the loggedin json return to 0

#browser debug "woraround". working with POST but accepting GET also. (dev function only)
if (empty($_POST)) $_POST = $_GET;

#one session only magic!
if ((($_POST[job] != "login") OR ($_POST[job] != "verify")) AND ($_SESSION[hash]))
	checkSession();

#get system defaults from settings table
$sql = mysql_query("SELECT name,value FROM ".$GLOBALS[cfg][settingstable]." WHERE 1;");
while ($row = mysql_fetch_array($sql))
	$GLOBALS[$row[name]] = $row[value];

#set systemdebug to user session variable and set javascript version to default
$GLOBALS[debug] = $_SESSION[phpdebug];
$GLOBALS[myreturn][v] = $_SESSION[jsversion];

#is this user admin?
$_SESSION[isadmin] = 0;
$sqla = mysql_query("SELECT dev FROM ".$GLOBALS[cfg][admintablename]." WHERE openid='".$_SESSION[openid_identifier]."';");
while ($rowa = mysql_fetch_array($sqla)) {
	#we are admin!
	$_SESSION[isadmin] = 1;
	#are we developer to?
	$_SESSION[isdev] = $rowa[dev];
}

#find out what we have to do (like magic)
if ($_POST[ssoInpLogout] == 1) {
	$_POST[job] = "logout";
	$_SESSION[user][nickname] = "";
} elseif ($_POST[job] == "login") {
	$_POST[job] = "auth";
	$_SESSION[user][nickname] = $_POST[ssoInpUsername];
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "verify") {
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
} elseif ($_POST[job] == "module") {
	$_POST[job] = "ajax";
} elseif ($_POST[job] == "status") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "update") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_SESSION[loggedin] == 1) {
	$_POST[job] = "refresh";
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} else {
	$_POST[job] = "nix zu tun";
}

#and then do it
switch ($_POST[job]) {

	#authenticate user (1st stage)
	case "auth":

		#do we have a login name?
		if (empty($_POST[ssoInpUsername])) {

			#nope
			$GLOBALS[html] = "<br /><br /><br /><h2><center>";
				sysmsg ("No login received.", 1);
			$GLOBALS[html] .= "</center></h2><br /><br /><br />";
			$GLOBALS[redirect] = 1;
		} else {

			#yes
			$GLOBALS[html] = "<br /><br /><br /><h2><center>";
				sysmsg("Checking Identity for:\n".$_POST[ssoInpUsername], 1);
			$GLOBALS[html] .= "</center></h2>";
			#call openid auth function
			openid_auth();
		}
		break;

	#verify the returned user (2nd stage)
	case "verify":
		openid_verify();

		#are we verified and so logged in?
		if ($_SESSION[loggedin]) {

			#yes! hurray!
			checkSites();
			setCookies();
			createSession();
			updateLastOnline();

			$GLOBALS[myreturn][loggedin] = 1;
			$GLOBALS[myreturn][msg] = "loggedin";
			$GLOBALS[redirect] = 1;
			$GLOBALS[html] = "<br /><br /><br /><h2><center>";
				sysmsg("Identity Verified for:\n".$_SESSION[openid_identifier], 1);
			$GLOBALS[html] .= "</center></h2><br /><br /><br />";
			$_SESSION[freshlogin] = 1;
		} else {

			#nope
			$tmp = $GLOBALS[html];
			$GLOBALS[myreturn][msg] = "auth error";
			$GLOBALS[html] = "<br /><br /><br /><h2><center>";
				sysmsg("Authentification Error!", 1);
			$GLOBALS[html] .= "</center></h2><br /><h3><center>".$tmp."</center></h3><br /><br />";
		}
		break;
	
	#default run mode for html requests (webgui, administration)
	case "refresh":

		#fetch all users and set the cookies
		fetchUsers();
		setCookies();

		#set the cookie for the "logged out" - "login box"
		$cookieTarget = str_replace($GLOBALS[cfg][openid_identifier_base], "", $_SESSION[openid_identifier]);
		setcookie ("ssoOldname", $cookieTarget, ( time() + ( 14 * 24 * 3600 )));

#		#tell json, we are logged in
#		$GLOBALS[myreturn][loggedin] = 1;

		#we should load a module
		if (! empty($_POST[module])) {

			#yes, load the module
			$GLOBALS[myreturn][msg] = "load module ".$_POST[module];
			$GLOBALS[html] = "";
			if (file_exists('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php')) {
				$GLOBALS[html] .= "<h2><a href='?'>Modul &Uuml;bersicht</a> &gt;";
				if (file_exists($GLOBALS[cfg][moduledir].'/'.$_POST[module].'/module.inc.php')) {
					include($GLOBALS[cfg][moduledir].'/'.$_POST[module].'/module.inc.php');
					$GLOBALS[html] .= " <a href='?module=".$_POST[module]."'>".$MODULE[name]."</a></h2>";
					include('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php');
				} else {
					sysmsg ("No Module description found!", 1);
				}
				$GLOBALS[html] .= "<br />";
			} else {
				sysmsg ("Module ".$_POST[module]." not found!", 0);
			}
		} else {

			#nope, show module index
			$GLOBALS[myreturn][msg] = "refreshing";
			$GLOBALS[html] = "<h2><a href='?'>Modul &Uuml;bersicht</a></h2><br />";
			$GLOBALS[html] .= "<ul>";
			
			#have fun reading this code :P
			if (is_dir($GLOBALS[cfg][moduledir])) {
				if ($dh = opendir($GLOBALS[cfg][moduledir])) {
	      	while (($file = readdir($dh)) !== false) {
						if (is_dir($GLOBALS[cfg][moduledir]."/".$file)) 
							if (($file != ".") AND ($file != "..") AND ($file != "Auth"))
									if (file_exists($GLOBALS[cfg][moduledir].'/'.$file.'/module.inc.php')) {
										include($GLOBALS[cfg][moduledir].'/'.$file.'/module.inc.php');
										if ($MODULE[dev]) {
											$nav[dev][$file][module] = $file;
											$nav[dev][$file][name] = $MODULE[name];
											$nav[dev][$file][comment] = $MODULE[comment];
										} elseif ($MODULE[admin]) {
											$nav[admin][$file][module] = $file;
											$nav[admin][$file][name] = $MODULE[name];
											$nav[admin][$file][comment] = $MODULE[comment];
										} else {
											$nav[user][$file][module] = $file;
											$nav[user][$file][name] = $MODULE[name];
											$nav[user][$file][comment] = $MODULE[comment];
										}
									}
						}
			      closedir($dh);
				}
				#write navigation
				foreach ($nav[user] as $mylink)
					$GLOBALS[html] .= "<li><h3><a href='?module=".$mylink[module]."'>".$mylink[name]."</a></h3>".$mylink[comment]."<br /><br /></li>";

				if ($_SESSION[isadmin]) {
					$GLOBALS[html] .= "<hr />";
					foreach ($nav[admin] as $mylink)
						$GLOBALS[html] .= "<li><h3><a href='?module=".$mylink[module]."'>".$mylink[name]."</a></h3>".$mylink[comment]."<br /><br /></li>";
				}
				if ($_SESSION[isdev]) {
					$GLOBALS[html] .= "<hr />";
					foreach ($nav[dev] as $mylink)
						$GLOBALS[html] .= "<li><h3><a href='?module=".$mylink[module]."'>".$mylink[name]."</a></h3>".$mylink[comment]."<br /><br /></li>";
				}
				$GLOBALS[html] .= "</ul>";
			} 
			$GLOBALS[html] .= "<br />";	
	
		}
		$GLOBALS[html] .= "<br />";	
		break;

	#ajax request means, a request from the javascript gui for a module
	case "ajax":
#		setCookies();
		$GLOBALS[myreturn][loggedin] = 1;
		if (file_exists('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php'))
			include('/srv/www/instances/alptroeim.ch/htdocs/'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php');
		jasonOut();
	break;

	#log us out of the system (paranoid version is also default)
	case "logout":
		$myoldid = $_SESSION[openid_identifier];
		killCookies();
		setcookie (session_id(), "", time() - 3600);
		session_destroy();
		session_write_close();
		$GLOBALS[html] = "<br /><br /><br /><h2><center>";
			sysmsg("Logging out...", 1);
		$GLOBALS[html] .= "</center></h2><br /><br /><br />";
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

	#this is for not logged in users
	default:
		$GLOBALS[myreturn][msg] = "nothing";
		sysmsg ("You are not logged in!");
}

#last online implementation (online timestamp)
if ($_POST[job] == "logout")
	$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".(time() - $GLOBALS[cfg][lastidletimeout] - 1)."' WHERE openid='".$myoldid."';");

#generate final html output (this is dirty .. i know)
echo "<html><head><title>".$_SERVER[SERVER_NAME]." OpenID Administration</title>";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf8" />';
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css1]."' type='text/css' />\n";
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css2]."' type='text/css' />\n";
echo "</head>";
echo "\n<script language='JavaScript'>\n";
echo "<!--\nfunction ssoRefresh()\n{\n";
echo "if (eval(document.openid_message)) {\n";
echo "document.openid_message.submit();\n";
echo "} else {\n";
echo "tloc = '";

#handle referes
if (! empty($_SESSION[tmp][referer])) {
	echo $_SESSION[tmp][referer];
	$_SESSION[tmp][referer] = "";
} else
  echo $GLOBALS[cfg][targetsite];

	echo "';\n";
	echo "window.parent.location = tloc;";

	echo "}\n";
	echo "}\n-->\n</script>\n";

#handle redirects
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

#show our html output
if (! empty($GLOBALS[html])) {
	echo "<div style='background-color:transparent; background-image:url(/img/transparent.png); padding:10px; height:100%;'><h1><center> "
				.$_SERVER[SERVER_NAME]." <img src='/".$GLOBALS[cfg][moduledir]."/openid-icon-100x100.png' width='40' height='40'> OpenID </center></h1>";
	if (! empty($_SESSION[openid_identifier]))
		echo "<center>[ you are logged in as: ".$_SESSION[openid_identifier]." ]</center>";
	echo "<hr /><br />";

	#this is pure cosmetic!!
	if (($_POST[job] == "verify") OR ($_POST[job] == "auth"))
		echo str_replace("\n", "<br />", $GLOBALS[html])."<br />";
	else
		echo $GLOBALS[html]."<br />";

	#generate runtime output
	$round = 3;// The number of decimal places to round the micro time to.
	$m_time = explode(" ",microtime());
	$totaltime = (($m_time[0] + $m_time[1]) - $starttime);
	echo "<hr /><center>Page loading took:". round($totaltime,$round) ." seconds</center><br /><br /></div>";
	echo "</body>\n</html>";
}

#if debug is enabled, show the json string as html comment
if ($GLOBALS[debug]) echo "\n<!-- ".json_encode($GLOBALS[myreturn])." -->";

#all done. hope you are happy with the result :) baba
exit;
?>

