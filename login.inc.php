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
if ((($_POST[job] != "login") OR ($_POST[job] != "verify") OR ($_SESSION[registering] == 1)
	OR ($_POST[mydo] != "registerme")) AND ($_SESSION[hash]))
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
} elseif ($_POST[mydo] == "registerme") {
	$_POST[job] = "refresh";
	$_POST[module] = "register";
} elseif ($_POST[job] == "login") {
	$_POST[job] = "auth";
	$_SESSION[user][nickname] = $_POST[ssoInpUsername];
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "verify") {
	$_SESSION[tmp][referer] = $_POST[ssoInpReferer];
} elseif ($_POST[job] == "module") {
	$_POST[job] = "ajax";
} elseif ($_POST[job] == "hidden") {
	$_POST[job] = "hidden";
} elseif ($_POST[job] == "status") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_POST[job] == "update") {
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} elseif ($_SESSION[loggedin] == 1) {
	$_POST[job] = "refresh";
	$GLOBALS[myreturn][username] = $_SESSION[user][nickname];
} else {
#	echo "bla"; exit;
	$_POST[job] = "refresh";
	$_POST[module] = "register";
}
#did we fell offline? then force logout :)
if ($_POST[job] == "update") {
	$sqla = mysql_query("SELECT timestamp FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($rowa = mysql_fetch_array($sqla))
		if ($rowa[timestamp] < (time() - $GLOBALS[cfg][lastidletimeout]))
			$GLOBALS[forcelogout] = 1;
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
		fetchUsers();

		#are we verified and so logged in?
		if ($_SESSION[loggedin]) {

			#yes! hurray!
			getOnlineUsers();
			setCookies();
			createSession();
			updateLastOnline();

			$GLOBALS[myreturn][loggedin] = 1;
			$GLOBALS[myreturn][msg] = "loggedin";
			$GLOBALS[redirect] = 1;
			$tmp = $GLOBALS[html];
			$GLOBALS[html] = "<br /><br /><br /><h2><center>";
				sysmsg("Identity Verified for:\n".$_SESSION[openid_identifier], 1);
			$GLOBALS[html] .= "</center></h2><center><br /><br /><br />".$tmp."<br /><br />";
			$_SESSION[freshlogin] = 1;


#			checkProfile();

			checkSites();


			$GLOBALS[html] .= "</center>";

			#set the cookie for the "logged out" - "login box"
			$cookieTarget = str_replace($GLOBALS[cfg][openid_identifier_base], "", $_SESSION[openid_identifier]);
			setcookie ("ssoOldname", $cookieTarget, ( time() + ( 14 * 24 * 3600 )));

		} else {
			$GLOBALS[standalonedesign] = 1;
			#nope
			$tmp = $GLOBALS[html];
			$GLOBALS[html] = "";
			$GLOBALS[myreturn][msg] = "auth error";
			$GLOBALS[html] .= "<br /><h3><center>Zugang zum System verweigert.<br />";
			$GLOBALS[html] .= "</center></h2><br /><h3><center>".$tmp."</center></h3><br /><br />";
		}
		break;
	
	#default run mode for html requests (webgui, administration)
	case "refresh":
		#fetch all users and set the cookies
		fetchUsers();
		if ($_SESSION[loggedin])
			setCookies();
		else
			killCookies();

		if ((checkProfile()) AND ($_SESSION[loggedin]))
				$_POST[module] = "userprofile";

		#we should load a module
		if (! empty($_POST[module])) {

			#yes, load the module
			$GLOBALS[myreturn][msg] = "load module ".$_POST[module];
			$GLOBALS[html] = "";
			$show = 1;
			if (file_exists('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php')) {
				if (! $_SESSION[loggedin]) {
					if (($_POST[module] == "register") AND ($_POST[mydo] == "verifyme"))
						$show = 0;
					if (($_POST[module] == "register") AND (empty($_POST[mydo])))
						$show = 0;
				}

				if ($show)
					$GLOBALS[html] .= "<h3><a href='?'><img src='".$GLOBALS[cfg][moduledir]."/home.png' style='width:32px;height:32px;' title='Home'/></a>&nbsp;&nbsp;&nbsp;";

				if (file_exists($GLOBALS[cfg][moduledir].'/'.$_POST[module].'/module.inc.php')) {
					include($GLOBALS[cfg][moduledir].'/'.$_POST[module].'/module.inc.php');
					if ($show)
						$GLOBALS[html] .= "<img src='".$GLOBALS[cfg][moduledir]."/back.png' style='width:32px;height:32px;' ".
															"onclick='javascript:history.back()' title='Back'>&nbsp;&nbsp;&nbsp;<a href='?module=".$_POST[module]."'>".$MODULE[name]."</a></h3><hr />";
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
#			$GLOBALS[html] = "<h2><a href='?'>Modul &Uuml;bersicht</a></h2><br />";
#			$GLOBALS[html] = "<ul>";
			$GLOBALS[html] = "";
				
			#have fun reading this code :P
			if (is_dir($GLOBALS[cfg][moduledir])) {
				if ($dh = opendir($GLOBALS[cfg][moduledir])) {
	      	while (($file = readdir($dh)) !== false) {
						if (is_dir($GLOBALS[cfg][moduledir]."/".$file)) 
							if (($file != ".") AND ($file != "..") AND ($file != "Auth"))
									if (file_exists($GLOBALS[cfg][moduledir].'/'.$file.'/module.inc.php')) {
										include($GLOBALS[cfg][moduledir].'/'.$file.'/module.inc.php');
										if ($MODULE[role] <= $GLOBALS[users][byuri][$_SESSION[openid_identifier]][role]) {
											if (! $MODULE[show])
												continue;
											elseif ($MODULE[dev]) {
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
						}
			      closedir($dh);
				}
				function mynav ($module, $name, $comment) {
					if ($GLOBALS[tmp][marker])
						$cssclass = "imbaRowDark";
					else
						$cssclass = "imbaRowLight";
					if ($GLOBALS[tmp][marker]) $GLOBALS[tmp][marker] = 0;
					else $GLOBALS[tmp][marker] = 1;
#					return "<div style='float:left;clear:both;' class='".$cssclass."'><a href='?module=".
#									$module."'><img src='".$GLOBALS[cfg][moduledir]."/".$module.
#									"/icon.png' style='padding:5px;spaceing:3px;'/></a></div>".
#									"<div style='float:left;' class='".$cssclass."'><h3><a href='?module=".
#									$module."'>".$name."</a></h3>".$comment."<br /><br /></div>\n";
					return "<h3><a href='?module=".$module."'><img src='".$GLOBALS[cfg][moduledir]."/".$module.
								 "/icon.png' style='float:left;padding:5px;spaceing:3px;'/>".$name."</a></h3>".$comment."<br /><br />";
					 
				}

				#write navigation
				$GLOBALS[tmp][marker] = 0;
				foreach ($nav[user] as $mylink) {
					$GLOBALS[html] .= mynav ($mylink[module], $mylink[name], $mylink[comment]);
				}
				if ($_SESSION[isadmin]) {
#					$GLOBALS[html] .= "<div style='float:left;clear:both;'><hr />Administration</div>";
					foreach ($nav[admin] as $mylink) {
						$GLOBALS[html] .= mynav ($mylink[module], $mylink[name], $mylink[comment]);
					}
				}
				if ($_SESSION[isdev]) {
#					$GLOBALS[html] .= "<div style='float:left;clear:both;'><hr />Entwicklung</div>";
					foreach ($nav[dev] as $mylink) {
						$GLOBALS[html] .= mynav ($mylink[module], $mylink[name], $mylink[comment]);
					}
				}
#				$GLOBALS[html] .= "</ul>";
			} 
			$GLOBALS[html] .= "<br />";	
	
		}
		$GLOBALS[html] .= "<br />";	
		break;

	#ajax request means, a request from the javascript gui for a module
	case "ajax":
		$GLOBALS[myreturn][loggedin] = 1;
		if (file_exists('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php'))
			require('/srv/www/instances/alptroeim.ch/htdocs/'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/index.php');
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
			setcookie ("ssoOldname", $cookieTarget, ( time() + ( 14 * 24 * 3600 )));

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
			setcookie ("ssoOldname", $cookieTarget, ( time() + ( 14 * 24 * 3600 )));

			$GLOBALS[myreturn][loggedin] = 1;

		} 
		$GLOBALS[myreturn][msg] = "status";
		jasonOut();

		break;

	case "hidden":
		updateTimestamp($_SESSION[openid_identifier]);
		header("Content-type: image/png");
		echo file_get_contents('1px.png');
		exit();
		break;

	#this is for not logged in users
	default:
		$GLOBALS[myreturn][msg] = "nothing";
		sysmsg ("You are not logged in!");
}

#last online implementation (online timestamp)
if ($_POST[job] == "logout")
	$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".(time() - $GLOBALS[cfg][lastidletimeout])."' WHERE openid='".$myoldid."';");

#generate final html output (this is dirty .. i know)
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
echo "<html><head><title>".$_SERVER[SERVER_NAME]." OpenID Control Center</title>";
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf8" />';
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css1]."' type='text/css' />\n";
echo "<link rel='stylesheet' href='".$GLOBALS[cfg][css2]."' type='text/css' />\n";
echo "<link rel='stylesheet' href='/login/imba.css' type='text/css' />\n";
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
} elseif ($GLOBALS[standalonedesign]) {
	echo "<body style='".$GLOBALS[cfg][standalonebodystlye]."'>\n";
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

	echo "<body onload='javascript:initCharacter();ssoInit();'  style='background-color:transparent;'>";
	echo "<div id='ssologin'></div>";

}

#show our html output
if (! empty($GLOBALS[html])) {
	if (! empty($_SESSION[openid_identifier])) $tmp = "[ you are logged in as: ".$_SESSION[openid_identifier]." ]";
	else $tmp = "Not logged in!";
	echo "<div style='background-color:transparent; background-image:url(/img/transparent.png); padding:10px; height:100%;'><h1><center> "
				.$_SERVER[SERVER_NAME]." <img src='/".$GLOBALS[cfg][moduledir]."/openid-icon-100x100.png' width='40' height='40' title='".
				$tmp."'> <abbr title='User Control Center'>IMBA Admin</abbr></center></h1>";
	echo "<hr />";

	#this is pure cosmetic!!
	if (($_POST[job] == "verify") OR ($_POST[job] == "auth"))
		echo str_replace("\n", "<br />", $GLOBALS[html]);
	else
		echo $GLOBALS[html]."<br />";

	#generate runtime output
	$m_time = explode(" ",microtime());
	$totaltime = (($m_time[0] + $m_time[1]) - $starttime);
	echo "<hr /><center>Page loading took:". round($totaltime,3) ." seconds</center><br /><br /></div>";
	echo "</body>\n</html>";
}

#if debug is enabled, show the json string as html comment
if ($GLOBALS[debug]) echo "\n<!-- ".json_encode($GLOBALS[myreturn])." -->";

#all done. hope you are happy with the result :) baba
exit;
?>

