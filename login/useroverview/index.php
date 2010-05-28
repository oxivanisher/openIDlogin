<?php
#only load as module?
if ($_SESSION[loggedin] == 1) {

#	$sqlv = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE 1;");
#	while ($rowv = mysql_fetch_array($sqlv))
#		$GLOBALS[html] .= "&nbsp;- found admin: ".$rowv[openid]."<br />";

	#functions
#	if (($_POST[myjob] == "applyprofile") and (! empty($_POST[user])) and (! empty($_POST[profile]))) {
#	} elseif (($_POST[myjob] == "registeruser") and (! empty($_POST[newuser])) and (! empty($_POST[newurl]))) {
#	}

	#init stuff

	#draw user table

	$sqla = mysql_query("SELECT id FROM ".$GLOBALS[cfg][admintablename]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($rowa = mysql_fetch_array($sqla)) {
		$admin = 1;
	}

	if ($admin) {
		$GLOBALS[html] .= "&nbsp;= you are admin";
	} else {
		$GLOBALS[html] .= "&nbsp;= you are user";
	}

	#fetching users from openid
	$sqls = mysql_query("SELECT openid,timestamp FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE 1;");
	while ($rows = mysql_fetch_array($sqls)) {
		$GLOBALS[module][$rows[openid]][smf] = $rows[openid];
		$GLOBALS[module][$rows[openid]][online] = $rows[timestamp];
	}

	#fetching users from smf
	$sqls = mysql_query("SELECT member_name,openid_uri FROM ".$GLOBALS[cfg][usernametable]." WHERE openid_uri<>'';");
	while ($rows = mysql_fetch_array($sqls)) {
		$GLOBALS[module][$rows[openid_uri]][smf] = utf8_decode($rows[member_name]);
		$GLOBALS[module][$rows[openid_uri]][name] = utf8_decode($rows[openid_uri]);
	}

	#fetching users from wordpress
	$sqlw = mysql_query("SELECT user_id,url FROM wp_openid_identities WHERE 1;");
	while ($roww = mysql_fetch_array($sqlw)) {
		$sqlp = mysql_query("SELECT user_nicename FROM wp_users WHERE ID='".$roww[user_id]."';");
		while ($rowp = mysql_fetch_array($sqlp)) {
			$GLOBALS[module][$roww[url]][wordpress] = $rowp[user_nicename];
		}
	}

	#fetching users from wiki
	$sqli = mysql_query("SELECT uoi_user,uoi_openid FROM WIKI_user_openid WHERE 1;");
	while ($rowi = mysql_fetch_array($sqli)) {
		$sqlk = mysql_query("SELECT user_name FROM WIKI_user WHERE user_id='".$rowi[uoi_user]."';");
		while ($rowk = mysql_fetch_array($sqlk)) {
			$GLOBALS[module][$rowi[uoi_openid]][wiki] = $rowk[user_name];
		}
	}

	#show user table
	$GLOBALS[html] .= "<hr>";
	$GLOBALS[html] .= "<h2>Registred users</h2>";
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Forum</th><th>Blog</th><th>Wiki</th><th>Last online</th>";
	if ($admin) $GLOBALS[html] .= "<th>OpenID</th>";
	$GLOBALS[html] .= "</tr>";
	foreach ($GLOBALS[module] as $myoid) {
		if (! empty($myoid[name])) {
			if ( $myoid[online] > ( time() - $GLOBALS[cfg][lastonlinetimeout])) $tmp = "color: lime;";
			elseif ( $myoid[online] > ( time() - $GLOBALS[cfg][lastidletimeout])) $tmp = "color: orange;";
			else $tmp = "";
			$GLOBALS[html] .= "<tr style='".$tmp."'><td>".genMsgUrl($myoid[smf])."</td><td>".$myoid[wordpress]."</td><td>".
							$myoid[wiki]."</td><td>".getAge($myoid[online])."</td>";
			if ($admin) $GLOBALS[html] .= "<td>".$myoid[name]."</td>";
			$GLOBALS[html] .= "</tr>";
		}
	}
	$GLOBALS[html] .= "</table>";

} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
