<?php
#only load as module?
if ($_SESSION[loggedin] == 1) {
	#init stuff
	fetchUsers();
	updateTimestamp($_SESSION[openid_identifier]);

	#draw user table
	if ($_SESSION[isadmin]) {
		$GLOBALS[html] .= "&nbsp;= you are admin";
	} else {
		$GLOBALS[html] .= "&nbsp;= you are user";
	}

	#fetching users from wordpress
	$sqlw = mysql_query("SELECT user_id,url FROM wp_openid_identities WHERE 1;");
	while ($roww = mysql_fetch_array($sqlw)) {
		$sqlp = mysql_query("SELECT user_nicename FROM wp_users WHERE ID='".$roww[user_id]."';");
		while ($rowp = mysql_fetch_array($sqlp)) {
			$tmpwp[$roww[url]] = $rowp[user_nicename];
		}
	}

	#fetching users from wiki
	$sqli = mysql_query("SELECT uoi_user,uoi_openid FROM WIKI_user_openid WHERE 1;");
	while ($rowi = mysql_fetch_array($sqli)) {
		$sqlk = mysql_query("SELECT user_name FROM WIKI_user WHERE user_id='".$rowi[uoi_user]."';");
		while ($rowk = mysql_fetch_array($sqlk)) {
			$tmpwi[$rowi[uoi_openid]] = $rowk[user_name];
		}
	}

	#show user table
	$GLOBALS[html] .= "<hr>";
	$GLOBALS[html] .= "<h2>Registred users</h2>";
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Forum</th><th>Blog</th><th>Wiki</th><th>Last online</th>";
	if ($_SESSION[isadmin]) $GLOBALS[html] .= "<th>OpenID</th>";
	$GLOBALS[html] .= "</tr>";
	foreach ($GLOBALS[users][byuri] as $myuri) {
		if (! empty($myuri[name])) {
			if ( $myuri[online] > ( time() - $GLOBALS[cfg][lastonlinetimeout])) $tmp = "color: lime;";
			elseif ( $myuri[online] > ( time() - $GLOBALS[cfg][lastidletimeout])) $tmp = "color: orange;";
			else $tmp = "";
			$GLOBALS[html] .= "<tr style='".$tmp."'><td>".genMsgUrl($myuri[uri])."</td><td>".$tmpwp[$myuri[uri]]."</td><td>".
												$tmpwi[$myuri[uri]]."</td><td>".getAge($myuri[online])."</td>";
			if ($_SESSION[isadmin]) $GLOBALS[html] .= "<td>".$myuri[uri]."</td>";
			$GLOBALS[html] .= "</tr>";
		}
	}
	$GLOBALS[html] .= "</table>";
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
