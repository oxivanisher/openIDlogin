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

	#check if we are allowed to do see admin stuff
	if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][role] > 8)
		$admin = true;
	else
		$admin = false;

	#show user table
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Name</th>";
	if ($admin) $GLOBALS[html] .= "<th>&nbsp;</th>";
	$GLOBALS[html] .= "<th>Last online</th><th>Jabber</th>";
	if ($_SESSION[isadmin]) $GLOBALS[html] .= "<th>OpenID</th>";
	$GLOBALS[html] .= "</tr>";
	$count = 0; $bcount = 0;
	$ocnt = 0; $acnt = 0; $dcnt = 0; $wcnt = 0; $mcnt = 0; $m3cnt = 0;

	#html form hidden fields
	if ($admin) {
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='applyprofile' />";
	}
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

			$GLOBALS[html] .= "<tr style='".$tmp."'>";
			$GLOBALS[html] .= "<td><img src='".$GLOBALS[cfg][profile][$myuri[role]][icon]."' title='".
												$GLOBALS[cfg][profile][$myuri[role]][name]."' width='16' height='16' />&nbsp;";
			$GLOBALS[html] .= genUserLink($myuri[uri])."</td>";

			if ($admin)
				if (($GLOBALS[users][byuri][$_SESSION[openid_identifier]][role] > $myuri[role]) AND ($myuri[uri] != $_SESSION[openid_identifier]))
					$GLOBALS[html] .= "<td><input type='radio' name='user' value='".$myuri[uri]."' /></td>";
				else
					$GLOBALS[html] .= "<td></td>";

			$GLOBALS[html] .= "<td>".getAge($myuri[online])."</td><td>".$xmpptmpf."</td>";

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

			if ($_SESSION[isadmin])
				$GLOBALS[html] .= "<td>".$myuri[uri]."</td>";
			
			$GLOBALS[html] .= "</tr>";

		}
	}
	$GLOBALS[html] .= "</table><br />";
	if ($admin) {
		$GLOBALS[html] .= "Rechte wechseln zu ".$pDropdown." <input type='submit' name='wechseln' value='wechseln' /><br /><br />";
		$GLOBALS[html] .= "</form>";
	}
	$GLOBALS[html] .= "<h3>Total: ".$count."</h3><br />";
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
