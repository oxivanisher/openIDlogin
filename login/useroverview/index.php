<?php
#only load as module?
if ($_SESSION[loggedin] == 1) {
	#apply profile function
	if (($_POST[myjob] == "applyprofile") and (! empty($_POST[user])) and ($_SESSION[isadmin])) {
		fetchUsers();
		applyProfile($_POST[user], $_POST[profile]);
	}

	#init stuff
	fetchUsers();
	updateTimestamp($_SESSION[openid_identifier]);
	$pDropdown = drawProfileDropdown();

	#draw user table
	if ($_SESSION[isadmin]) {
		$GLOBALS[html] .= "&nbsp;= you are admin";
	} else {
		$GLOBALS[html] .= "&nbsp;= you are user";
	}

	#show user table
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Name</th><th>Last online</th><th>Jabber</th>";
	if ($_SESSION[isadmin]) $GLOBALS[html] .= "<th>OpenID</th><th>Change Role</th>";
	$GLOBALS[html] .= "</tr>";
	$count = 0; $bcount = 0;
	$ocnt = 0; $acnt = 0; $dcnt = 0; $wcnt = 0; $mcnt = 0; $m3cnt = 0;
	foreach ($GLOBALS[users][byuri] as $myuri) {
		if (! empty($myuri[name])) {
			if ( $myuri[online] > ( time() - $GLOBALS[cfg][lastonlinetimeout])) $tmp = "color: lime;";
			elseif ( $myuri[online] > ( time() - $GLOBALS[cfg][lastidletimeout])) $tmp = "color: yellow;";
			elseif ($myuri[xmppstatus]) $tmp = "color: orange;";
			else $tmp = "";

			if (! $myuri[role]) {
				$bcount++;
				if (! $_SESSION[isadmin])
					continue;
			}

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
			$count++;

			if ($myuri[online] > (time() - $GLOBALS[cfg][lastonlinetimeout]))
				$ocnt++;
			elseif ($myuri[online] > (time() - $GLOBALS[cfg][lastidletimeout]))
				$acnt++;
			elseif ($myuri[online] > (time() - 86400))
				$dcnt++;
			elseif ($myuri[online] > (time() - 604800))
				$wcnt++;
			elseif ($myuri[online] > (time() - 2419200))
				$mcnt++;
			else
				$m3cnt++;

			if ($_SESSION[isadmin]) {
				$GLOBALS[html] .= "<td>".$myuri[uri]."</td>";

				$GLOBALS[html] .= "<form action='?' method='POST'>";
				$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
				$GLOBALS[html] .= "<input type='hidden' name='myjob' value='applyprofile' />";
				$GLOBALS[html] .= "<input type='hidden' name='user' value='".$myuri[uri]."' />";
				$GLOBALS[html] .= "<td>".$pDropdown."<input type='submit' name='change' value='change'></td>";
				$GLOBALS[html] .= "</form>";
			}
			$GLOBALS[html] .= "</tr>";

		}
	}
	$GLOBALS[html] .= "</table><br /><h3>Total: ".$count."</h3><br />";
	$GLOBALS[html] .= "Online: ".$ocnt;
	$GLOBALS[html] .= ", AFK: ".$acnt."<br /><br />";
	$GLOBALS[html] .= "<b>Zuletzt online vor:</b><ul>";
	$GLOBALS[html] .= "<li>1 Tag: ".$dcnt."</li>";
	$GLOBALS[html] .= "<li>1 Woche: ".$wcnt."</li>";
	$GLOBALS[html] .= "<li>1 Monat: ".$mcnt."</li>";
	$GLOBALS[html] .= "<li>3 Monaten: ".$m3cnt."</li>";
	$GLOBALS[html] .= "</ul>";
	if ($_SESSION[isadmin])
		$GLOBALS[html] .= "Verbannt: ".$bcount;

} else {
	sysmsg ("You are not logged in!", 1);
}

?>
