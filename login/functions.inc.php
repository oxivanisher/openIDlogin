<?php

function setCookies () {
	#set smf cookie
	$GLOBALS[html] .= "<b>checking for smf user:</b><br />";
	$sql = mysql_query("SELECT id_member,member_name,passwd,password_salt,email_address FROM ".$GLOBALS[cfg][usernametable]." WHERE openid_uri='".$_SESSION[openid_identifier]."';");
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

#daemon function
function getXmppUsers() {
	#load users from db (oom_.._xmpp)
	$count = 0;
	$sql = mysql_query("SELECT openid,xmpp FROM ".$GLOBALS[cfg][msg][xmpptable]." WHERE 1;");
	while ($row = mysql_fetch_array($sql)) {
		if (! empty($GLOBALS[users][byuri][$row[openid]][name])) {
			$GLOBALS[users][byxmpp][$row[xmpp]] = $row[openid];
			$GLOBALS[users][byuri][$row[openid]][xmpp] = $row[xmpp];
			$GLOBALS[users][bylowxmpp][strtolower($row[xmpp])] = $row[openid];

			$count++;
		}
	}
	$GLOBALS[users][count][xmpp] = $count;
}

#fetch users function
function fetchUsers () {
	#cleanup
	unset ($GLOBALS[users]);
	$count = 0;
	$sql = mysql_query("SELECT openid_uri,member_name,id_member FROM ".$GLOBALS[cfg][usernametable]." WHERE 1 ORDER BY member_name;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[openid_uri])) {
			#experimental
			$tmpname = $row[member_name];
			$tmpuri = $row[openid_uri];
			$GLOBALS[users][byname][strtolower($tmpname)] = $tmpuri;
#			$GLOBALS[users][byutf8name][strtolower($row[member_name])] = $tmpuri;
			$GLOBALS[users][byuri][$row[openid_uri]][name] = $tmpname;
#			$GLOBALS[users][byuri][$row[openid_uri]][utf8name] = $row[member_name];
			$GLOBALS[users][byuri][$tmpuri][smf] = $row[id_member];
			$GLOBALS[users][byuri][$tmpuri][uri] = $tmpuri;
			$count++;
		}
	$GLOBALS[users][count][all] = $count;
//	$GLOBALS[html] .= "= ".$count." users found.<br />";

	getXmppUsers();

	#fetching users from openid lastonline db
	$sqls = mysql_query("SELECT openid,timestamp,status,xmppstatus,chatid,chatsubscr FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE 1;");
	while ($rows = mysql_fetch_array($sqls)) {
		if (! empty($GLOBALS[users][byuri][$rows[openid]][name])) {
			$GLOBALS[users][byuri][$rows[openid]][online] = $rows[timestamp];
			$GLOBALS[users][byuri][$rows[openid]][status] = $rows[status];
			$GLOBALS[users][byuri][$rows[openid]][xmppstatus] = $rows[xmppstatus];
			$GLOBALS[users][byuri][$rows[openid]][chat] = $rows[chatid];
			$GLOBALS[users][bychat][$rows[chatid]] = $GLOBALS[users][byuri][$rows[openid]][name];
			if ($_SESSION[openid_identifier] == $rows[openid]) {
				$GLOBALS[chat][subscr] = unserialize($rows[chatsubscr]);
			}
		}
	}

	#eqdkp
	$count = 0;
	$sql = mysql_query("SELECT user_id,username FROM eqdkp_users WHERE 1;"); #username='".strtolower($myurl[name]."';"));
	while ($row = mysql_fetch_array($sql)) {
		foreach ($GLOBALS[users][byuri] as $myurl) {
			if (strtolower($myurl[name]) == strtolower($row[username])) {
				if (! empty($GLOBALS[users][byuri][$myurl[uri]][name])) {
					$GLOBALS[users][byuri][$myurl[uri]][eqdkp] = $row[user_id];
					$count++;
				}
			}
		}
	}
	$GLOBALS[users][count][eqdkp] = $count;

	#mediawiki
	$count = 0;
	$sql = mysql_query("SELECT uoi_user,uoi_openid FROM WIKI_user_openid WHERE 1;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[uoi_user])) {
			if (! empty($GLOBALS[users][byuri][$row[uoi_openid]][name])) {
				$GLOBALS[users][byuri][$row[uoi_openid]][mediawiki] = $row[uoi_user];
				$count++;
			}
		}
	$GLOBALS[users][count][mediawiki] = $count;

	#wordpress
	$count = 0;
	$sql = mysql_query("SELECT user_id,url FROM wp_openid_identities WHERE 1;");
	while ($row = mysql_fetch_array($sql))
		if (! empty($row[url])) {
			if (! empty($GLOBALS[users][byuri][$row[url]][name])) {
				$GLOBALS[users][byuri][$row[url]][wordpress] = $row[user_id];
				$count++;
			}
		}
	$GLOBALS[users][count][wordpress] = $count;

/*	this is now done with the lastonline db
	#get chat users from db
	$count = 0;
	$sql = mysql_query("SELECT id,openid FROM ".$GLOBALS[cfg][chat][usertable]." WHERE 1;");
	while ($row = mysql_fetch_array($sql)) {
		if (! empty($GLOBALS[users][byuri][$row[openid]][name])) {
			$GLOBALS[users][byuri][$row[openid]][chat] = $row[id];
			$GLOBALS[users][bychat][$row[id]] = $row[openid];
		}
	}
	$GLOBALS[users][count][chat] = $count; */
}

#draw users dropdown
function drawUsersDropdown($selected = FALSE) {
	$tmphtml = "";
	$tmphtml .= "<select name='user'>";
	$tmphtml .= "<option value=''>Choose User</option>";

	ksort($GLOBALS[users][byname]);
	foreach ($GLOBALS[users][byname] as $tmpname => $mytmpurl)
	if (! empty($GLOBALS[users][byuri][$mytmpurl])) {
		if ($selected == $GLOBALS[users][byuri][$mytmpurl][uri]) $stmp = " selected";
		else $stmp = "";
		$tmphtml .= "<option value='".$GLOBALS[users][byuri][$mytmpurl][uri]."'".
								$stmp.">".$GLOBALS[users][byuri][$mytmpurl][name]."</option>";
	}
	$tmphtml .= "</select>";
	return $tmphtml;
}

#draw profile dropdown
function drawProfileDropdown() {
	$tmphtml = "<select name='profile'>";
	$tmphtml .= "<option value=''>Choose Profile</option>";
	foreach ($GLOBALS[cfg][profile] as $myname => $myprofile)
		$tmphtml .= "<option value='".$myname."'>".$myprofile[name]."</option>";
	$tmphtml .= "</select>";
	return $tmphtml;
}

#draw smf users without openid dropdown
function drawSmfUsersDropdown() {
	$tmphtml = "";
	$tmphtml .= "<select name='newuser'>";
	$tmphtml .= "<option value=''>Choose User</option>";
	$sqlt = mysql_query("SELECT member_name,id_member FROM ".$GLOBALS[cfg][usernametable]." WHERE openid_uri='' ORDER BY member_name ASC;");
	while ($rowt = mysql_fetch_array($sqlt)) {
		$tmphtml .= "<option value='".$rowt[id_member]."'>".$rowt[member_name]."</option>";
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

function getNiceAge($timestamp) {
	$ageOfMsg = time() - $timestamp;
	if ($timestamp == 0) {
		$ageOfMsgReturn = "noch nie";
	} elseif ($ageOfMsg < '60') {
		$ageOfMsgReturn = "vor ".$ageOfMsg." Sek";
	} elseif ($ageOfMsg < '3600') {
		$ageOfMsg = round(($ageOfMsg/60),1);
		$ageOfMsgReturn = "vor ".$ageOfMsg." Min";
	} elseif ($ageOfMsg <= '86400') {
		$ageOfMsgReturn = strftime("um %H:%M Uhr", $timestamp);
	} elseif ($ageOfMsg <= '604800') {
		$ageOfMsgReturn = strftime("am %A", $timestamp);
	} elseif ($ageOfMsg <= '2419200') {
		$ageOfMsgReturn = strftime("im %B", $timestamp);
	} else  {
		$ageOfMsg = round(($ageOfMsg/31449600),1);
		$ageOfMsgReturn = strftime("anno %Y", $timestamp);
	}
	return $ageOfMsgReturn;
}

function genMsgUrl($user) {
	return "<a href='?module=messaging&myjob=composemessage&user=".$GLOBALS[users][byuri][$user][uri].
					"'>".$GLOBALS[users][byuri][$user][name]."</a>";
}

function checkSession () {
	$bool = 1;
	$sql = mysql_query("SELECT hash FROM ".$GLOBALS[cfg][sessiontable]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($row = mysql_fetch_array($sql))
		if ($_SESSION[hash] == $row[hash])
			$bool = 0;

	if ($bool)	$GLOBALS[forcelogout] = 1;
	else				$GLOBALS[forcelogout] = 0;
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

	$_SESSION[phpdebug] = $GLOBALS[debug];
	$_SESSION[jsdebug] = 0;
	$_SESSION[jsversion] = $GLOBALS[version];
	$_SESSION[reqdebug] = 0;
	$_SESSION[freshlogin] = 0;
}

function getOnlineUsers () {
	#last online implementation
	#unset users array (because this function could be called multiple times)
	unset ($GLOBALS[aryNames], $GLOBALS[aryOpenID], $GLOBALS[aryStatus], $GLOBALS[aryTimes], $GLOBALS[aryNewMessage]);
	$GLOBALS[ajaxuserreturnname] = array();
	$GLOBALS[ajaxuserreturnopenid] = array();
	$GLOBALS[ajaxuserreturnstatus] = array();
	$GLOBALS[ajaxuserreturntimes] = array();
	$GLOBALS[ajaxuserreturnnewmessage] = array(); $newmsgopenid = array();
	$bool = false; $cnt = 0;
	$ocnt = 0; $ousers = ""; $obool = 1; $otmp = "";
	$icnt = 0; $iusers = ""; $ibool = 1; $itmp = "";
	$fcnt = 0; $fbool = 1;
	$firstremote = 1;

 	#create chat channel output.
	foreach (getMyChatChannels() as $mychan) {
		array_push($GLOBALS[ajaxuserreturnname], $mychan[name]);
		array_push($GLOBALS[ajaxuserreturnopenid], $mychan[id]);
		array_push($GLOBALS[ajaxuserreturntimes], $mychan[lastmessage]);
		array_push($GLOBALS[ajaxuserreturnnewmessage], "0");
		array_push($GLOBALS[ajaxuserreturnstatus], "3");
	}

	#get the new messages informations
	$newmsgsql = mysql_query("SELECT receiver,sender FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE new='1';");
	while ($nrow = mysql_fetch_array($newmsgsql))
		array_push($newmsgopenid, array($nrow[receiver], $nrow[sender]));

	#get the users from last online table and go trough them
	$onlinesql = mysql_query("SELECT openid,timestamp,xmppstatus FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE 1 ORDER BY timestamp DESC;");
	while ($orow = mysql_fetch_array($onlinesql)) {
		#catch some eventually problems
		if (empty($GLOBALS[users][byuri][$orow[openid]][name])) continue;
		if ($orow[name] == '0') continue;

		#yes, this is a ok user 
		$cnt++;

		#am i this user here?
		if ($orow[openid] == $_SESSION[openid_identifier]) {
			$bool = true;
			if ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastonlinetimeout] )) {				
				$ocnt++;
			} elseif ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastidletimeout] )) {
				$icnt++;
			} else {
				$fcnt++;
			}
			if ($obool)	$obool = 0;
			else				$otmp = ", ";
			$ousers .= $otmp.$GLOBALS[users][byuri][$orow[openid]][name];
			continue;
		}

		array_push($GLOBALS[ajaxuserreturnname], $GLOBALS[users][byuri][$orow[openid]][name]);
		array_push($GLOBALS[ajaxuserreturnopenid], $orow[openid]);
		array_push($GLOBALS[ajaxuserreturntimes], getNiceAge($orow[timestamp]));

		#count new mwssages
		$mcount = 0;
		foreach ($newmsgopenid as $mymsg) {
			if (($mymsg[0] == $_SESSION[openid_identifier]) AND ($mymsg[1]  == $orow[openid]))
				$mcount++;
		}
		array_push($GLOBALS[ajaxuserreturnnewmessage], $mcount);

		#is the user online?
		if ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastonlinetimeout] )) {
			$ocnt++;
			if ($obool)	$obool = 0;
			else				$otmp = ", ";
			$ousers .= $otmp.$GLOBALS[users][byuri][$orow[openid]][name];
			array_push($GLOBALS[ajaxuserreturnstatus], "1");
			continue;
		}

		#is the user idle?
		if ($orow[timestamp] > ( time() - $GLOBALS[cfg][lastidletimeout] )) {
			$icnt++;
			if ($ibool)	$ibool = 0;
			else				$itmp = ", ";
			$iusers .= $itmp.$GLOBALS[users][byuri][$orow[openid]][name];
			array_push($GLOBALS[ajaxuserreturnstatus], "0");
			continue;

		#or is the user remote online (xmpp jabber daemon)
		} elseif ($orow[xmppstatus]) {
			if ($ibool)	$ibool = 0;
			else				$itmp = ", ";

			if ($firstremote)	{
				$itmp = " | ";
				$firstremote = 0;
			} else $itmp = ", ";

			$iusers .= $itmp.$GLOBALS[users][byuri][$orow[openid]][name];

			array_push($GLOBALS[ajaxuserreturnstatus], "2");
			$icnt++;

		#so, the user is offline then (to infinity and beyond!)
		} else { 
			array_push($GLOBALS[ajaxuserreturnstatus], "-1");
			$fcnt++;
		}
		continue;

		#we shall never reach this point
	}

	$GLOBALS[onlineusers] = $ocnt;
	$GLOBALS[idleusers] = $icnt;
	$GLOBALS[offlineusers] = $fcnt;
	$GLOBALS[maxusers] = $cnt;

	$GLOBALS[onlinenames] = $ousers;
	$GLOBALS[idlenames] = $iusers;
	$GLOBALS[offlinenames] = $fusers;

	$GLOBALS[online][isintable] = $bool;
}

function updateLastOnline () {
	#magic
	if ($GLOBALS[online][isintable])
		mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".
								time()."' WHERE openid='".$_SESSION[openid_identifier]."';");
	else if ((! empty($_SESSION[openid_identifier]) AND (! empty($_SESSION[myname]))))
		mysql_query("INSERT INTO ".$GLOBALS[cfg][lastonlinedb]." (openid,timestamp,name,chatsubscr) VALUES ('".
								$_SESSION[openid_identifier]."', '".time()."', '".$_SESSION[myname]."', 'a:1:{i:0;s:1:\"3\";}');");
}

function jasonOut () {
	#return json as header and exit on ajax requests
	if (($_POST[job] == "update") OR ($_POST[job] == "status") OR
			($_POST[myjob] == "update") OR ($_POST[myjob] == "status") OR
			($_POST[ajax] == 1)) {

		#always send possible openid
		$GLOBALS[myreturn][openid_identifier] = $_SESSION[openid_identifier];

		#send debug to js
		$GLOBALS[myreturn][debug] = $_SESSION[jsdebug];

		#did we fell offline?
		if ($GLOBALS[forcelogout] AND (! $_SESSION[freshlogin]))
			$GLOBALS[myreturn][felloffline] = 1;

		#default update and status requests from js
		if (! $_POST[ajax]) {
			$GLOBALS[myreturn][onlineusers] = $GLOBALS[onlineusers];
			$GLOBALS[myreturn][idleusers] = $GLOBALS[idleusers];

			#if we are logged in, we have to send messaging informations and detailes online informations also
			if ($_SESSION[loggedin]) {
				$GLOBALS[myreturn][newmsgs] = 0;
				$GLOBALS[myreturn][onlinenames] = $GLOBALS[onlinenames];
				$GLOBALS[myreturn][idlenames] = $GLOBALS[idlenames];
#				$GLOBALS[myreturn][offlinenames] = $GLOBALS[offlinenames];

#				$GLOBALS[myreturn][onlinearray] = $GLOBALS[onlinearray];
#				$GLOBALS[myreturn][idlearray] = $GLOBALS[idlearray];
#				$GLOBALS[myreturn][offlinearray] = $GLOBALS[offlinearray];

				$GLOBALS[myreturn][aryNames] = $GLOBALS[ajaxuserreturnname];
				$GLOBALS[myreturn][aryOpenID] = $GLOBALS[ajaxuserreturnopenid];
				$GLOBALS[myreturn][aryStatus] = $GLOBALS[ajaxuserreturnstatus];
				$GLOBALS[myreturn][aryTimes] = $GLOBALS[ajaxuserreturntimes];
				$GLOBALS[myreturn][aryNewMessage] = $GLOBALS[ajaxuserreturnnewmessage];

				$tmppa = array();
				$sql = mysql_query("SELECT id,sender FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE receiver='".
														$_SESSION[openid_identifier]."' AND new='1' ORDER BY timestamp DESC;");
				while ($row = mysql_fetch_array($sql)) {
					$GLOBALS[myreturn][newmsgs]++;
					$GLOBALS[myreturn][newmsgid] = $row[id];
					array_push($tmppa, $GLOBALS[users][byuri][$row[sender]][name]);
				}
				array_unique($tmppa);
				$GLOBALS[myreturn][newmsgsfrom] = $tmppa;
			}
		}

		#do we have a fresh session
		if ($_SESSION[freshlogin]) {
			$GLOBALS[myreturn][freshlogin] = 1;
			$_SESSION[freshlogin] = 0;
		}	else $GLOBALS[myreturn][freshlogin] = 0;

		#debug output
		if ($GLOBALS[debug]) {
			$GLOBALS[myreturn][maxusers] = "X".rand(0, 9);
			$m_time = explode(" ",microtime());
			$totaltime = (($m_time[0] + $m_time[1]) - $starttime);
			$GLOBALS[myreturn][runtime] = round($totaltime,3);
		} else {
			$GLOBALS[myreturn][maxusers] = $GLOBALS[maxusers];
		}

		#request log update of json output
		if ($GLOBALS[reqdebugid])
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][requestlogtable]." SET output='".
							json_encode($GLOBALS[myreturn])."' WHERE id='".$GLOBALS[reqdebugid]."';");

		#should we force send a json object?
		if ($GLOBALS[jsonobject])
			header('X-JSON: '.json_encode($GLOBALS[myreturn], JSON_FORCE_OBJECT));
		else
			header('X-JSON: '.json_encode($GLOBALS[myreturn]));

		#javascript exit :D
		exit;
	}
}

function encodeme($me) {
	return mysql_real_escape_string(htmlspecialchars(str_replace('&', '&amp;', trim($me))));
}

function xmppencode($me) {
	return utf8_encode(htmlspecialchars($me));
}

function msg ($msg) {
	if ($GLOBALS[debug])
	echo strftime($GLOBALS[cfg][strftime], time())."\t".$msg."\n";
}

function updateTimestamp($openid) {
	$sqltsu = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".time()."' WHERE openid='".$openid."';");
}

function getIP() {
	$ip;

	if (getenv("HTTP_CLIENT_IP")) $ip = getenv("HTTP_CLIENT_IP");
	else if(getenv("HTTP_X_FORWARDED_FOR")) $ip = getenv("HTTP_X_FORWARDED_FOR");
	else if(getenv("REMOTE_ADDR")) $ip = getenv("REMOTE_ADDR");
	else $ip = "UNKNOWN";

	return $ip;
}

function getAllChatChannels ($owner = NULL) {
	$tret = array();
	$count = 0;
	if ($owner) $search = " WHERE owner='".$owner."'";
	else $search = " WHERE 1";

	$sql = mysql_query("SELECT id,owner,name,allowed,created,lastmessage FROM ".$GLOBALS[cfg][chat][channeltable].$search.";");
	while ($row = mysql_fetch_array($sql)) {
		if ($row[owner] == 0)
			$owner = "Willhelm";
		else
			$owner = genMsgUrl($GLOBALS[users][byname][strtolower($GLOBALS[users][bychat][$row[owner]])]);  #

		$tret[$count][id] = $row[id];
		$tret[$count][owner] = $row[owner];
		$tret[$count][ownername] = $owner;
		$tret[$count][name] = $row[name];
		$tret[$count][allowed] = $row[allowed];
		$tret[$count][created] = getNiceAge($row[created]);
		$tret[$count][lastmessage] = getNiceAge($row[lastmessage]);
		$count++;
	}
	return $tret;
}

function getMyChatChannels () {
	$tret = array();
	$count = 0;

	$bool = 1; $wtmp = ""; $wsearch = "";
	foreach ($GLOBALS[chat][subscr] as $subs => $chanid) {
		if ($bool) $bool = 0;
		else $wtmp = " OR";
		$wsearch .= $wtmp." id='".$chanid."'";
	}

	if (! $bool) {
	$sql = mysql_query("SELECT id,owner,name,allowed,created,lastmessage FROM ".
							$GLOBALS[cfg][chat][channeltable].$search." WHERE ".$wsearch.";");
	while ($row = mysql_fetch_array($sql)) {
		if ($row[owner] == 0)
			$owner = "Willhelm";
		else
			$owner = $GLOBALS[users][byuri][$GLOBALS[users][bychat][$row[owner]]][name];

		$tret[$count][id] = $row[id];
		$tret[$count][owner] = $row[owner];
		$tret[$count][ownername] = $owner;
		$tret[$count][name] = $row[name];
		$tret[$count][allowed] = $row[allowed];
		$tret[$count][created] = getNiceAge($row[created]);
		$tret[$count][lastmessage] = getNiceAge($row[lastmessage]);
		$count++;
	}
	} 
	return $tret;
}

function getChatChannel ($myid) {
	$tret = array();
	$count = 0;

	$sql = mysql_query("SELECT id,owner,name,allowed,created,lastmessage FROM ".
							$GLOBALS[cfg][chat][channeltable]." WHERE id='".$myid."';");
	while ($row = mysql_fetch_array($sql)) {
		if ($row[owner] == 0)
			$owner = "Willhelm";
		else
			$owner = $GLOBALS[users][byuri][$GLOBALS[users][bychat][$row[owner]]][name];

		$tret[id] = $row[id];
		$tret[owner] = $row[owner];
		$tret[ownername] = $owner;
		$tret[name] = $row[name];
		$tret[allowed] = $row[allowed];
		$tret[created] = strftime($GLOBALS[cfg][strftime], $row[created]);
		$tret[lastmessage] = getNiceAge($row[lastmessage]);
	}
	return $tret;
}

function getMyChatMessages ($since = NULL) {
	$data = getMyChatChannels();
	$tret = array();
	$count = 0;

	if (empty($since)) $stmp = "";
	else $stmp = " AND timestamp<'".$since."'";

	$bool = 1; $wtmp = ""; $wsearch = "";
	foreach ($GLOBALS[chat][subscr] as $subs => $chanid) {
		if ($bool) $bool = 0;
		else $wtmp = " OR";
		$wsearch .= $wtmp." channel='".$chanid."'";
		$tret[msg][$chanid] = array();
	}

	if (! $bool) {
	$sql = mysql_query("SELECT id,sender,channel,timestamp,message FROM ".$GLOBALS[cfg][chat][msgtable].
					" WHERE".$wsearch." ORDER BY timestamp DESC LIMIT 20;");
	while ($row = mysql_fetch_array($sql)) {
		$tret[msg][$count][id] = $row[id];
		$tret[msg][$count][channel] = $row[channel];
		$tret[msg][$count][sender] = $GLOBALS[users][byuri][$GLOBALS[users][bychat][$row[sender]]][name];
		$tret[msg][$count][ts] = getAge($row[timestamp]);
		$tret[msg][$count][msg] = $row[message];
		$count++;
	}
	}
	$tret[chan] = $data;
	return $tret;
}

function getMyChatMessagesFrom ($channel) {
	$tret = array();
	$count = 0;

	$sql = mysql_query("SELECT id,sender,channel,timestamp,message FROM ".$GLOBALS[cfg][chat][msgtable].
					" WHERE channel='".$channel."' ORDER BY timestamp DESC LIMIT 20;");
	while ($row = mysql_fetch_array($sql)) {
		$tret[$count][id] = $row[id];
		$tret[$count][channel] = $row[channel];
		$tret[$count][sender] = $GLOBALS[users][bychat][$row[sender]];
		$tret[$count][timestamp] = getNiceAge($row[timestamp]);
		$tret[$count][msg] = $row[message];
		$count++;
	}
	return $tret;
}

function genAllowedCheckbox ($template = NULL) {
		$tret = "<table>\n"; $walk = 1; $max = 5;
		foreach ($GLOBALS[users][byuri] as $myuri) {
			if ($walk == ($max + 1)) $walk = 1;
			if ($walk == 1) $tret .= "<tr>\n";

			if ($template)
			if (in_array($myuri[chat], $template)) $check = " checked";
			else $check = "";

			if (empty($myuri[chat])) $dis = " DISABLED";
			else $dis = "";

			$tret  .= "<td><input type='checkbox' name='allowed[]' value='".$myuri[chat]."' ".$check.$dis."/> ".$myuri[name]."</td>\n";
			if ($walk == $max) $tret .= "</tr>\n";
			$walk++;
		}
		for ($i = $walk; $i <= $max; $i++) $tret .= "<td>&nbsp</td>\n";
		if ($walk != $max) $tret .= "</tr>\n";
		return $tret."</table>\n";
}


function sysmsg($msg, $lvl = 2, $user = "", $subject = "") {
	switch ($lvl) {
		case 0:
			$rmsg = "ERROR: ";
		break;

		case 1:
			$rmsg = "WARNING: ";
		break;

		case 2:
			$rmsg = "INFO: ";
		break;

		default:
			$rmsg = "UNSET: ";
	}

	if ($GLOBALS[bot]) {
		$thash = $subject;
		$tmodule = "daemon";
		$tuser = $user;
		$tip = "XMPP";
	} else {
		$thash = $_SESSION[hash];
		$tmodule = $_POST[module];
		$tuser = $_SESSION[openid_identifier];
		$tip = getIP();
	}

	if ($lvl <= $GLOBALS[sysmsglvl]) 
		$sqlsysmsg = mysql_query("INSERT INTO ".$GLOBALS[cfg][systemmsgsdb]." (timestamp,user,ip,module,session,msg,lvl) VALUES ".
												"('".time()."', '".$tuser."', '".$tip."', '".$tmodule."', '".$thash."', '".$msg."', '".$lvl."');");

	if ($lvl < 2)
		$GLOBALS[html] .= "<b>".$msg."</b>";

	if ($lvl == 0)
		alert($rmsg.$msg, $tuser);
}

function alert ($msg, $from) {
	$alertsql = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE dev='1';");
	while ($alertrow = mysql_fetch_array($alertsql)) {
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ".
						"('".$from."', '".$alertrow[openid]."', '".time()."', 'SYSTEM ALERT', 'ALERT:\n".$msg."', 1, 1);");
	}
}

function makeClickableURL($url){
	$in=array(
		'`((?:https?|ftp)://\S+[[:alnum:]]/?)`si',
		'`((?<!//)(www\.\S+[[:alnum:]]/?))`si'
	);
	$out=array(
		'<a href="$1" rel="nofollow" target="new" class="ssoMessageLink">$1</a> ',
		'<a href="http://$1" rel="nofollow" target="new" class="ssoMessageLink">$1</a>'
	);
	return preg_replace($in,$out,$url);
}

?>
