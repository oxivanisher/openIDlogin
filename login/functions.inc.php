<?php

function setCookies () {
	#set smf cookie
	$GLOBALS[html] .= "<b>checking for smf user:</b><br />";
	$sql = mysql_query("SELECT id_member,member_name,passwd,password_salt,email_address FROM smf_members WHERE openid_uri='".$_SESSION[openid_identifier]."';");
	while ($row = mysql_fetch_array($sql)) {
		$data = array($row[id_member], sha1($row[passwd] . $row[password_salt]), (time() + 3600), 0);
		setcookie('alpCookie',serialize($data),(time() + 3600),$GLOBALS[cfg][cookiepath],$GLOBALS[cfg][cookiedomain]);
		$nametransfer = $row[member_name];
		$tmpemailtransfer = $row[email_address];
		$GLOBALS[html] .= "- ".$row[member_name]." found!<br /><b>= setting cookie</b><br />";
		$_SESSION[sites][smf][$row[member_name]] = "cookie";
		$_SESSION[myname] = $row[member_name];
		if ($row[member_name] == "") return 0;
	}
	if (empty($_SESSION[sites][smf]))
		$_SESSION[sites][smf] = -1;
	$GLOBALS[html] .= "<br />";
	
	#set eqdkp cookie
	$mybool = 1;
	$GLOBALS[html] .= "<b>checking for eqdkp user:</b><br />";
	$sql = mysql_query("SELECT user_password,user_id,username FROM eqdkp_users WHERE username='".strtolower($nametransfer)."';");
	while ($row = mysql_fetch_array($sql)) {
		$mybool = 0;
		$data[auto_login_id] = $row[user_password];
		$data[user_id] = $row[user_id];
		setcookie('eqdkp_data',serialize($data),(time() + 3600),$GLOBALS[cfg][cookiepath],'');
		$GLOBALS[html] .= "- ".$row[username]." found!<br /><b>= setting cookie</b><br />";
		$_SESSION[sites][eqdkp][$row[username]] = "cookie";
	}

	if (($mybool) AND (! empty($nametransfer))) {
		$sql2 = mysql_query("INSERT INTO eqdkp_users (username, user_email, user_password) VALUES ('".strtolower($nametransfer).
				"', '".$tmpemailtransfer."', '".md5(rand())."');");

		$sql = mysql_query("SELECT user_password,user_id,username FROM eqdkp_users WHERE username='".strtolower($nametransfer)."';");
		while ($row = mysql_fetch_array($sql)) {
			$data[auto_login_id] = $row[user_password];
			$data[user_id] = $row[user_id];
			setcookie('eqdkp_data',serialize($data),(time() + 3600),$GLOBALS[cfg][cookiepath],'');
			$GLOBALS[html] .= "- ".$row[username]." created!<br /><b>= setting cookie</b><br />";
			$_SESSION[sites][eqdkp][$row[username]] = "cookie";
		}
	}

	if (empty($_SESSION[sites][eqdkp]))
		$_SESSION[sites][eqdkp] = -1;
	$GLOBALS[html] .= "<br />";
}

function killCookies () {
	#unset eqdkp cookies
	setcookie ('eqdkp_data', "", time() - 3600,$GLOBALS[cfg][cookiepath],'');
	setcookie ('eqdkp_sid', "", time() - 3600,$GLOBALS[cfg][cookiepath],'');

	#unset smf cookie
	setcookie ('alpCookie', serialize(array(0, '', 0)), time() - 3600,$GLOBALS[cfg][cookiepath],$GLOBALS[cfg][cookiedomain]);
}

function isValidURL($url) {
	return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function checkSites () {

	foreach ($GLOBALS[cfg][sites] as $mysite) {
		$GLOBALS[html] .= "<b>checking ".$mysite[name]." for ".$_SESSION[openid_identifier]."</b>:<br />"; 

		if ((! empty($mysite[ltable])) and (! empty($mysite[lafield])) and (! empty($mysite[lqfield]))) {
			$GLOBALS[html] .= "- looking for ".$_SESSION[openid_identifier]." in ".$mysite[ltable]." -&gt; ".$mysite[lqfield]."<br />";
			$lsql = mysql_query("SELECT ".$mysite[lafield]." FROM ".$mysite[ltable]." WHERE ".$mysite[lqfield]."='".$_SESSION[openid_identifier]."';");
			while ($lrow = mysql_fetch_array($lsql)) {
				$mytmpname = $lrow[$mysite[lafield]];
			}
		} else {
			$mytmpname = $_SESSION[openid_identifier];
		}

		$GLOBALS[html] .= "- looking for ".$mytmpname." in ".$mysite[utable]." -&gt; ".$mysite[uqfield]."<br />"; 
		$usql = mysql_query("SELECT ".$mysite[uafield]." FROM ".$mysite[utable]." WHERE ".$mysite[uqfield]."='".$mytmpname."';");
		while ($urow = mysql_fetch_array($usql)) {
			$myresult = $urow[$mysite[uafield]];
		}

		if (empty($myresult)) {
			$GLOBALS[html] .= "<b>= notfound</b>";
			$_SESSION[sites][$mysite[name]] = -1;
		} else {
			$GLOBALS[html] .= "<b>= found: ".$myresult."</b>";
			$_SESSION[sites][$mysite[name]][$myresult] = "form";
#			$_SESSION[sites][$mysite[name]][form] = 2;
		}
		$GLOBALS[html] .= "<br /><br />";

	}
}

#fetch users function
function fetchUsers () {
	#smf
	$count = 0;
	$sql = mysql_query("SELECT openid_uri,member_name FROM smf_members WHERE 1;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[openid_uri])) {
			$GLOBALS[module][all][$row[openid_uri]] = utf8_decode($row[member_name]);
			$GLOBALS[module][sites][smf][$row[openid_uri]] = $row[member_name];
			$GLOBALS[module][sites][openid][utf8_decode($row[member_name])] = $row[openid_uri];
			$count++;
		}
	$GLOBALS[html] .= "= ".$count." users found.<br />";

	#eqdkp
	$count = 0;
	foreach ($GLOBALS[module][sites][smf] as $myopenid => $myusername) {
		$sql = mysql_query("SELECT user_id FROM eqdkp_users WHERE username='".strtolower($myusername."';"));
		while ($row = mysql_fetch_array($sql)) {
			if (! empty($row[user_id])) {
				$GLOBALS[module][sites][eqdkp][$myopenid] = $row[user_id];
				$count++;
			}
		}
	}

	#mediawiki
	$count = 0;
	$sql = mysql_query("SELECT uoi_user,uoi_openid FROM WIKI_user_openid WHERE 1;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[uoi_user])) {
			$GLOBALS[module][sites][mediawiki][$row[uoi_openid]] = $row[uoi_user];
			$count++;
		}

	#wordpress
	$count = 0;
	$sql = mysql_query("SELECT user_id,url FROM wp_openid_identities WHERE 1;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[url])) {
			$GLOBALS[module][sites][wordpress][$row[url]] = $row[user_id];
			$count++;
		}
}

#draw users dropdown
function drawUsersDropdown($selected = FALSE) {
	$tmphtml = "";

	$tmphtml .= "<select name='user'>";
	$tmphtml .= "<option value=''>Choose User</option>";
	asort ($GLOBALS[module][all]);
	foreach ($GLOBALS[module][all] as $myurl => $myname) {
		if ($selected == $myname) $stmp = " selected";
		else $stmp = "";
		$tmphtml .= "<option value='".$myurl."'".$stmp.">".$myname." (".$myurl.")</option>";
	}
	$tmphtml .= "</select>";

	return $tmphtml;
}

#draw profile dropdown
function drawProfileDropdown() {
	$tmphtml = "";

	$tmphtml .= "<select name='profile'>";
	$tmphtml .= "<option value=''>Choose Profile</option>";
	foreach ($GLOBALS[cfg][module][profile] as $myname => $myprofile) {
		$tmphtml .= "<option value='".$myname."'>".$myprofile[name]."</option>";
	}
	$tmphtml .= "</select>";

	return $tmphtml;
}

#draw smf users dropdown
function drawSmfUsersDropdown() {
	$tmphtml = "";

	$tmphtml .= "<select name='newuser'>";
	$tmphtml .= "<option value=''>Choose User</option>";
	$sqlt = mysql_query("SELECT member_name,id_member FROM smf_members WHERE openid_uri='' ORDER BY member_name ASC;");
	while ($rowt = mysql_fetch_array($sqlt)) {
		$tmphtml .= "<option value='".$rowt[id_member]."'>".utf8_decode($rowt[member_name])."</option>";
	}
	$tmphtml .= "</select>";

	return $tmphtml;
}

function getAge($timestamp) {
        $ageOfMsg = time() - $timestamp;
        if ($timestamp == 0) {
                $ageOfMsgReturn = "";
        } elseif ($ageOfMsg < '60') {
                $ageOfMsgReturn = $ageOfMsg." sec(s)";
        } elseif ($ageOfMsg > '59' && $ageOfMsg < '3600') {
                $ageOfMsg = round(($ageOfMsg/60),1);
                $ageOfMsgReturn = $ageOfMsg." min(s)";
        } elseif ($ageOfMsg >= '3600' && $ageOfMsg < '86400') {
                $ageOfMsg = round(($ageOfMsg/3600),1);
                $ageOfMsgReturn = $ageOfMsg." hr(s)";
        } elseif ($ageOfMsg >= '86400' && $ageOfMsg < '604800') {
                $ageOfMsg = round(($ageOfMsg/86400),1);
                $ageOfMsgReturn = $ageOfMsg." day(s)";
        } elseif ($ageOfMsg >= '604800' && $ageOfMsg < '31449600') {
                $ageOfMsg = round(($ageOfMsg/604800),1);
                $ageOfMsgReturn = $ageOfMsg." week(s)";
        } else  {
                $ageOfMsg = round(($ageOfMsg/31449600),1);
                $ageOfMsgReturn = $ageOfMsg." year(s)";
        }
        return $ageOfMsgReturn;
}

function genMsgUrl($user) {
	return "<a href='?module=messaging&myjob=composemessage&user=".$GLOBALS[module][sites][openid][$user]."'>".$user."</a>";
}

function checkSession () {
	$bool = 1;
	$sql = mysql_query("SELECT hash FROM ".$GLOBALS[cfg][sessiontable]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($row = mysql_fetch_array($sql))
		if ($_SESSION[hash] == $row[hash])
			$bool = 0;

	if ($bool) {
		$_POST[ssoInpLogout] = 1;
		$GLOBALS[forcelogout] = 1;
	} else $GLOBALS[forcelogout] = 0;
}

function generateHash () {
	$result = "";
	$charPool = '0123456789abcdefghijklmnopqrstuvwxyz';
	for($p = 0; $p<15; $p++)
		$result .= $charPool[mt_rand(0,strlen($charPool)-1)];
	return sha1(md5(sha1($result)));
}

function createSession () {
	$_SESSION[hash] = generateHash(); $bool = 0;
	$sqlq = mysql_query("SELECT hash FROM ".$GLOBALS[cfg][sessiontable]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($rowq = mysql_fetch_array($sqlq))
		$bool = 1;

	if ($bool)
		$sql = mysql_query("UPDATE ".$GLOBALS[cfg][sessiontable]." SET hash='".$_SESSION[hash]."' WHERE openid='".$_SESSION[openid_identifier]."';");
	else
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][sessiontable]." (openid,hash) VALUES ('".$_SESSION[openid_identifier]."', '".$_SESSION[hash]."');");
}

function getOnlineUsers () {
	#last online implementation
	$bool = false; $cnt = 0;
	$ocnt = 0; $ousers = ""; $obool = 1; $otmp = ""; $onlineusersarray = array();
	$icnt = 0; $iusers = ""; $ibool = 1; $itmp = ""; $idleusersarray = array();
	$onlinesql = mysql_query("SELECT openid,timestamp,name FROM oom_openid_lastonline WHERE 1;");
	while ($orow = mysql_fetch_array($onlinesql)) {
		if (empty($GLOBALS[module][all][$orow[openid]])) continue;
		if ($orow[name] == '0') continue;
		$cnt++;
		if ($orow[openid] == $_SESSION[openid_identifier]) $bool = true;
		if ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastonlinetimeout] )) { $ocnt++; if ($obool) $obool = 0;
			else $otmp = ", "; $ousers .= $otmp.$orow[name]; array_push($onlineusersarray, $orow[name]); continue; }
		if ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastidletimeout] )) { $icnt++; if ($ibool) $ibool = 0;
			else $itmp = ", "; $iusers .= $itmp.$orow[name]; array_push($idleusersarray, $orow[name]); continue; }
	}

	$GLOBALS[onlineusers] = $ocnt;
	$GLOBALS[idleusers] = $icnt;
	$GLOBALS[maxusers] = $cnt;

	$GLOBALS[onlinenames] = $ousers;
	$GLOBALS[idlenames] = $iusers;
	$GLOBALS[onlinearray] = $onlineusersarray;
	$GLOBALS[idlearray] = $idleusersarray;

	$GLOBALS[online][isintable] = $bool;


}

function updateLastOnline () {
	#magic
	if ($GLOBALS[online][isintable])
		mysql_query("UPDATE oom_openid_lastonline SET timestamp='".time()."' WHERE openid='".$_SESSION[openid_identifier]."';");
	else if ((! empty($_SESSION[openid_identifier]) AND (! empty($_SESSION[myname]))))
		mysql_query("INSERT INTO oom_openid_lastonline (openid,timestamp,name) VALUES ('".$_SESSION[openid_identifier]."', '".time()."', '".$_SESSION[myname]."');");
}

function jasonOut () {
	#return json as header and exit on ajax requests
	if (($_POST[job] == "update") OR ($_POST[job] == "status") OR ($_POST[ajax] == 1)) {
		$GLOBALS[myreturn][openid_identifier] = $_SESSION[openid_identifier];

		if ($GLOBALS[debug]) {
			$GLOBALS[myreturn][error] = $_SESSION[error];
			$GLOBALS[myreturn][sites] = $_SESSION[sites];
		}


		$GLOBALS[myreturn][felloffline] = $GLOBALS[forcelogout];

		$GLOBALS[myreturn][newmsgs] = 0;
		if ($_SESSION[loggedin] AND (! $_POST[ajax])) {
			$GLOBALS[myreturn][onlinenames] = $GLOBALS[onlinenames];
			$GLOBALS[myreturn][idlenames] = $GLOBALS[idlenames];
			$GLOBALS[myreturn][onlinearray] = $GLOBALS[onlinearray];
			$GLOBALS[myreturn][idlearray] = $GLOBALS[idlearray];

			$sql = mysql_query("SELECT id FROM oom_openid_messages WHERE receiver='".$_SESSION[openid_identifier]."' AND new='1' ORDER BY timestamp DESC;");
			while ($row = mysql_fetch_array($sql)) {
				$GLOBALS[myreturn][newmsgs]++;
				$GLOBALS[myreturn][newmsgid] = $row[id];
			}

#			if ($_POST[job] == "update") {
#				$sql = mysql_query("SELECT timestamp FROM oom_openid_lastonline WHERE openid='".$_SESSION[openid_identifier]."';");
#				while ($row = mysql_fetch_array($sql))
#					$tmpts = $row[timestamp];
#				if ($tmpts < (time() - $GLOBALS[cfg][lastidletimeout]))
#					$GLOBALS[myreturn][felloffline] = 0;
#			}
		}

		if (! $_POST[ajax]) {
			$GLOBALS[myreturn][onlineusers] = $GLOBALS[onlineusers];
			$GLOBALS[myreturn][idleusers] = $GLOBALS[idleusers];
			if ($GLOBALS[debug]) {
				$GLOBALS[myreturn][maxusers] = "X".rand(0, 9);
			} else
				$GLOBALS[myreturn][maxusers] = $GLOBALS[maxusers];
		}


		if ($GLOBALS[freshlogin]) {
			$GLOBALS[myreturn][freshlogin] = 1;
			$GLOBALS[freshlogin] = 0;
		}	else $GLOBALS[myreturn][freshlogin] = 0;

		$m_time = explode(" ",microtime());
		$totaltime = (($m_time[0] + $m_time[1]) - $starttime);
		$GLOBALS[myreturn][rutime][round($totaltime,3)];

		header('X-JSON: '.json_encode($GLOBALS[myreturn]).'');
		#javascript exit :D
		exit;
	}
}
?>
