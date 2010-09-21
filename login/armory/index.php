<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	# module functions
	/*
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
*/

	#init stuff
	fetchUsers();
 
	#show character detail and on own profile input field for characters
	if ($_POST[mydo] == "showusercharlist") {
		$GLOBALS[html] .= "<hr />";
/*		if (($_POST[user] == $_SESSION[openid_identifier]) OR ($_SESSION[isadmin])) {
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
*/
		$GLOBALS[html] .= "<h3>Charakter von <a href='?module=".$_POST[module]."&mydo=showusercharlist&user=".
											$_POST[user]."'>".$GLOBALS[users][byuri][$_POST[user]][name]."</a></h3>";
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th style='width:16px;'>&nbsp;</th><th>Name</th><th>Level</th><th>Geschlecht</th>".
											"<th>Rasse</th><th>Itemlevel</th><th>PVP Kills</th><th>Berufe</th><th>Achievments</th></tr>";
		if ($GLOBALS[users][byuri][$_POST[user]][armorychars])
		foreach ($GLOBALS[users][byuri][$_POST[user]][armorychars] as $mycharname) {
			if ($char = fetchArmoryCharacter($mycharname)) {
				$GLOBALS[html] .= "<tr class='".genArmoryClassClass($char[classid])."'>";
				$GLOBALS[html] .= "<td>&nbsp;</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'><a href='?module=".$_POST[module]."&mydo=showchardetail&mycharname=".$char[name]."'>".$char[name]."</a></td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>".$char[level]."</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>".showArmoryName("gender", $char[genderid])."</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>".showArmoryName("race", $char[raceid])."</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>".$char[ilevelavg]."</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>".$char[pvpkills]."</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>";
					$bool = true; $btmp = "";
					if ($char[skills])
					foreach ($char[skills] as $skill) {
						foreach (array_keys($skill) as $id) {
							if ($bool) $bool = false;
							else $btmp = "<br />";
							$GLOBALS[html] .= $btmp.showArmoryName("skill", $id).": ".$skill[$id];
						}
					}
				$GLOBALS[html] .= "</td>";
				$GLOBALS[html] .= "<td style='vertical-align:top;'>";
					$bool = true; $btmp = "";
					if ($char[achievments])
					foreach ($char[achievments] as $achievments) {
						foreach (array_keys($achievments) as $id) {
							if ($bool) $bool = false;
							else $btmp = ", ";
							$GLOBALS[html] .= $btmp.showArmoryName("achievment", $id).": ".$achievments[$id];
						}
					}
				$GLOBALS[html] .= "</td>";
				$GLOBALS[html] .= "</tr>";
			} else {
				$GLOBALS[html] .= "Charakter ".$mycharname." wurde in der Armory nicht gefunden.<br />";
			}
		}
		$GLOBALS[html] .= "</table>";


	#show character sheet
	} elseif ($_POST[mydo] == "showchardetail") {

		$GLOBALS[html] .= "Loading char: <a href='?module=".$_POST[module]."&mydo=showchardetail&mycharname=".$_POST[mycharname]."'>".$_POST[mycharname]."</a><br /><br />";
		if ($char = fetchArmoryCharacter($_POST[mycharname])) {
			$sql = "SELECT content FROM ".$GLOBALS[cfg][armory][charcachetable]." WHERE name LIKE '".$char[name]."';";
			$sqlr = mysql_query($sql);
			while ($row = mysql_fetch_array($sqlr))
				$char[content] = $row[content];
			$mychar = new SimpleXMLElement($char[content]);

			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><td colspan='2'>";
			$GLOBALS[html] .= "<h3 class='".genArmoryClassClass($char[classid])."'>".$char[name]." ".$char[level].", ".showArmoryName("gender", $char[genderid])." ".
												showArmoryName("race", $char[raceid])." ".showArmoryName("class", $char[classid])."</h3>";
			$GLOBALS[html] .= "</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2' align='right'>Profil Alter ".getAge($char[timestamp]).
												", N&auml;chstes update:<br />".genTime($GLOBALS[armorychartimeout] + $char[timestamp])."</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /><br /></td></tr>";

			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td style='vertical-align: top'>";
			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><td>PVP Kills:</td><td>".$char[pvpkills]."</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /></td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'>Berufe:</td></tr>";
			foreach ($char[skills] as $skill)
				foreach (array_keys($skill) as $id)
					$GLOBALS[html] .= "<tr><td>&nbsp;&nbsp;".showArmoryName("skill", $id).":</td><td>".$skill[$id]."</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /></td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'>Achievments:</td></tr>";
			$total = 0;
			foreach ($char[achievments] as $achievments)
				foreach (array_keys($achievments) as $id) {
					$GLOBALS[html] .= "<tr><td>&nbsp;&nbsp;".showArmoryName("achievment", $id).":</td><td>".$achievments[$id]."</td></tr>";
					$total += $achievments[$id];
				}
			$GLOBALS[html] .= "<tr><td colspan='2'><br /></td></tr>";
			$GLOBALS[html] .= "<tr><td>&nbsp;&nbsp;Total:</td><td>".$total."</td></tr>";
			$GLOBALS[html] .= "</table>";
			$GLOBALS[html] .= "</td>";



			$GLOBALS[html] .= "<td style='vertical-align: top'>";
			$GLOBALS[html] .= "Items:<br />";
		  if (count($mychar->characterInfo->characterTab->items->item)) {
  		  $count = 0; $total = 0;
 	  	 	foreach ($mychar->characterInfo->characterTab->items->item as $myitem) {
    	  	$total += (integer) $myitem->attributes()->level;
  	  	  $count++;
    	  	$itemid = (integer) $myitem->attributes()->id;
    	  	$itemslot = (integer) $myitem->attributes()->slot;

					$GLOBALS[html] .= genArmoryItemHtml($itemid).", ".showArmoryName("slot", $itemslot)."<br /><br />";

				}

				$GLOBALS[html] .= "<br />";
				$GLOBALS[html] .= "Items: ".$count."<br />";
				$GLOBALS[html] .= "Itemlevel &#216;: ".round($total/$count)."<br />";

			} else $GLOBALS[html] .= "Keine Iteminformationen vorhanden";
			$GLOBALS[html] .= "</tr>";
			$GLOBALS[html] .= "</table>";

		} else $GLOBALS[html] .= "Character ".$char." not found!";

	#show overview list (default)
	} else {
		$ccount = 0;
		$max = 6;
		$GLOBALS[html] .= "<table>\n";
		$GLOBALS[html] .= "<tr><th>User</th><th colspan='".$max."'>Charakter</th></tr>\n";
		$ucount = 0;
		foreach ($GLOBALS[users][byuri] as $myuser) {
			$count = 0;
			$ucount++;
			$GLOBALS[html] .= "<tr><td><a href='?module=".$_POST[module]."&mydo=showusercharlist&user=".$myuser[uri]."'>".
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
					$ccount++;
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
		$GLOBALS[html] .= "<br />";
		$GLOBALS[html] .= "<h3>Anzahl Member: ".$ucount."; Anzahl Charakter: ".$ccount."</h3>";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
