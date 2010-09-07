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

	#show user table
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Name</th><th>Last online</th><th>Jabber</th>";
	if ($_SESSION[isadmin]) $GLOBALS[html] .= "<th>OpenID</th>";
	$GLOBALS[html] .= "</tr>";
	foreach ($GLOBALS[users][byuri] as $myuri) {
		if (! empty($myuri[name])) {
			if ( $myuri[online] > ( time() - $GLOBALS[cfg][lastonlinetimeout])) $tmp = "color: lime;";
			elseif ( $myuri[online] > ( time() - $GLOBALS[cfg][lastidletimeout])) $tmp = "color: yellow;";
			elseif ($myuri[xmppstatus]) $tmp = "color: orange;";
			else $tmp = "";

			$xmpptmp = "Ressourcen online: "; $xmpptmp2 = ""; $xmppbool = 1; $xmppcnt = 0;
			if (! empty($myuri[status])) {
				$tres = unserialize($myuri[status]);
				foreach ($tres as $myres) {
					$xmppcnt++;
					if ($xmppbool) $xmppbool = 0;
					else $xmpptmp2 = ", ";
					$xmpptmp .= $xmpptmp2.$myres;
				}
			} else $xmpptmp = "Keine Ressource online";

			if ($myuri[xmpp])
				$xmpptmpf = "<abbr title='".$xmpptmp."'>Ja</abbr>";
			else
				$xmpptmpf = "Nein";

			$GLOBALS[html] .= "<tr style='".$tmp."'><td><img src='".$GLOBALS[cfg][profile][$myuri[role]][icon]."' title='".
												$GLOBALS[cfg][profile][$myuri[role]][name]."' width='16' height='16' />&nbsp;".
												genMsgUrl($myuri[uri])."</td>"."<td>".getAge($myuri[online])."</td><td>".$xmpptmpf."</td>";
			if ($_SESSION[isadmin]) $GLOBALS[html] .= "<td>".$myuri[uri]."</td>";
			$GLOBALS[html] .= "</tr>";
		}
	}
	$GLOBALS[html] .= "</table>";
} else {
	sysmsg ("You are not logged in!", 1);
}

?>
