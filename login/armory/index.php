<?php

#functions
function loadItems () {
	# load items into memory
	#id, icon, level, quality, type, name, timespamp
#	$GLOBALS[cfg][armory][itemcachetable]

}

function fetchItem ($item) {
	# check if item is in db

	# if not, get it from armory and save data
	#id, icon, level, quality, type, name, timespamp
#	$GLOBALS[cfg][armory][itemcachetable]
	# $item = new SimpleXMLElement(fetchArmoryXML ("i", $item));

	#set item info in $a_i[]
}

#only load as module?
if ($_SESSION[loggedin] == 1) {
	# module functions
	if ($_POST[mydo] == "savechars") {
		if (($_POST[user] == $_SESSION[openid_identifier]) OR ($_SESSION[isadmin])) {
			if ($_POST[chars]) {
				$tnames = explode(",", $_POST[chars]);
				$nnames = array();
				$mybool = false;
				foreach ($tnames as $tname) {
					array_push($nnames, trim($tname));
					$mybool = true;
				}
				if ($mybool) $rnames = serialize($nnames);
				else $rnames = "";
			} else {
				$rnames = "";
			}

			$sql = "UPDATE ".$GLOBALS[cfg][userprofiletable]." SET armorychars='".$rnames.
							"' WHERE openid='".$_POST[user]."';";
			$sqlr = mysql_query($sql);
		}
	}

	#init stuff
	fetchUsers();
 
	#show character detail and on own profile input field for characters
	if ($_POST[mydo] == "showdetail") {
		$GLOBALS[html] .= "<hr />";
		if (($_POST[user] == $_SESSION[openid_identifier]) OR ($_SESSION[isadmin])) {
			#change user rights form
			$tmpnames = ""; $tbool = true;; $tmp = "";
			if(! empty($GLOBALS[users][byuri][$_POST[user]][armorychars])) {
				array_unique($GLOBALS[users][byuri][$_POST[user]][armorychars]);
				foreach ($GLOBALS[users][byuri][$_POST[user]][armorychars] as $mychar) {
					if ($tbool) $tbool = false;
					else $tmp = ", ";
					$tmpnames .= $tmp.$mychar;
				}
			}

			$GLOBALS[html] .= "Deine Charakter (Kommagetrennt): ";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='mydo' value='savechars' />";
			$GLOBALS[html] .= "<input type='hidden' name='user' value='".$_POST[user]."' />";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<input type='text' name='chars' value='".$tmpnames."' size='40' />";
			$GLOBALS[html] .= "<input type='submit' name='save' value='save' />";
			$GLOBALS[html] .= "</form><hr />";
			$GLOBALS[html] .= "<br />";
		}

		$GLOBALS[html] .= "<h3>Charakter von ".$GLOBALS[users][byuri][$_POST[user]][name]."</h3>";
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>Klasse</th><th>Name</th><th>Level</th><th>Geschlecht</th><th>Rasse</th><th>Itemlevel Durchschnitt</th></tr>";
		if ($GLOBALS[users][byuri][$_POST[user]][armorychars])
		foreach ($GLOBALS[users][byuri][$_POST[user]][armorychars] as $mycharname) {
			if ($char = fetchArmoryCharacter($mycharname)) {
				$GLOBALS[html] .= "<tr class='".genArmoryClassClass($char[classid])."'>";
				$GLOBALS[html] .= "<td>&nbsp;</td>";
				$GLOBALS[html] .= "<td>".$char[name]."</td>";
				$GLOBALS[html] .= "<td>".$char[level]."</td>";
				$GLOBALS[html] .= "<td>".showArmoryName("gender", $char[genderid])."</td>";
#				$GLOBALS[html] .= "<td>".showArmoryName("class", $char[classid])."</td>";
				$GLOBALS[html] .= "<td>".showArmoryName("race", $char[raceid])."</td>";
				$GLOBALS[html] .= "<td>".$char[ilevelavg]."</td>";
				$GLOBALS[html] .= "</tr>";
			} else {
				$GLOBALS[html] .= "Charakter ".$mycharname." wurde in der Armory nicht gefunden.<br />";
			}
		}
		$GLOBALS[html] .= "</table>";
	
	#show overview list (default)
	} else {
		$max = 6;
		$GLOBALS[html] .= "<table>\n";
		$GLOBALS[html] .= "<tr><th>User</th><th colspan='".$max."'>Charakter</th></tr>\n";

		foreach ($GLOBALS[users][byuri] as $myuser) {
			$count = 0;
			$GLOBALS[html] .= "<tr><td><a href='?module=".$_POST[module]."&mydo=showdetail&user=".$myuser[uri]."'>".
												$myuser[name]." (".count($myuser[armorychars]).")</a></td>\n";
			#new object			
			if (! empty($myuser[armorychars])) {
				foreach ($myuser[armorychars] as $mycharname) {
					$count++;
					if ($count == $max) {
						$GLOBALS[html] .= "</tr><tr><td>&nbsp</td><td>\n";
						$count = 1;
					} else {
						$GLOBALS[html] .= "<td>\n";
					}
					$char = "";
					if ($char = fetchArmoryCharacter($mycharname)) {
						$GLOBALS[html] .= genArmoryIlvlHtml($char[ilevelavg],$char[level]).
															"<span class='".genArmoryClassClass($char[classid])."' title='".showArmoryName("race", $char[raceid]).
															", ".showArmoryName("gender", $char[genderid]).", ".showArmoryName("faction", $char[factionid])."'>".$char[name]." ".
															"</span>\n";
					} else {
						$GLOBALS[html] .= genArmoryIlvlHtml(0,"00").$mycharname;
					}
					$GLOBALS[html] .= "</td>\n";
				}
			} else $GLOBALS[html] .= "<td colspan='".$max."'>Keine Charakter Eingetragen</td></tr>\n"; continue;
			if ($count == 0)
				$GLOBALS[html] .= "</tr>\n";
			else
				$GLOBALS[html] .= "<td colspan='".($max - $count)."'>&nbsp</td></tr>\n";
			$GLOBALS[html] .= "<tr><td colspan='".($max + 1)."'>aaa</td></tr>\n";
		}
		$GLOBALS[html] .= "</table>\n";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
