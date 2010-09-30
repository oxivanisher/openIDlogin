<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	#init stuff
	fetchUsers();

	#show character detail and on own profile input field for characters
	if ($_POST[mydo] == "showusercharlist") {
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
				$GLOBALS[html] .= $mycharname." wurde in der Armory nicht gefunden.<br />";
			}
		}
		$GLOBALS[html] .= "</table>";


	#show character sheet
	} elseif ($_POST[mydo] == "showchardetail") {
		if (($_POST[myjob] == "forcerefresh") AND (! empty($_POST[mycharname]))) {
			$sql = "UPDATE ".$GLOBALS[cfg][armory][charcachetable]." SET timestamp='1' WHERE name LIKE '".$_POST[mycharname]."';";
			$sqlr = mysql_query($sql);
			sysmsg("Force Armory refresh of char: ".$_POST[mycharname], 2);
		}


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
			$GLOBALS[html] .= "Eingetragen bei:";
			foreach (getArmoryUserOfChar($_POST[mycharname]) as $myuser)
				$GLOBALS[html] .= " ".genUserLink($myuser);
			$GLOBALS[html] .= "<br /><br />";

			$GLOBALS[html] .= "</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2' align='right'>";
			$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&mydo=showchardetail&myjob=forcerefresh&mycharname=".$_POST[mycharname]."'><img src='/".$GLOBALS[cfg][moduledir].
												"/reload.png' title='Force Reload' style='width:24px;height:24px;align:right;float:right;padding:5px;' /></a>";
			$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&mydo=showchardetail&mycharname=".$_POST[mycharname]."'><img src='/".$GLOBALS[cfg][moduledir].
												"/refresh.png' title='Refresh' style='width:24px;height:24px;align:right;float:right;padding:5px;' /></a> ";
			$GLOBALS[html] .= "Profil Alter ".getAge($char[timestamp]).", N&auml;chstes update:<br />".genTime($GLOBALS[armorychartimeout] + $char[timestamp])." ";
			$GLOBALS[html] .= "</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /><br /></td></tr>";

			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td style='vertical-align: top'>";
			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><th>PVP Kills:</th><th align='right'>".$char[pvpkills]."</th></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /></td></tr>";
			$GLOBALS[html] .= "<tr><th colspan='2'>Berufe:</th></tr>";
			if (is_array($char[skills]))
				foreach ($char[skills] as $skill)
					foreach (array_keys($skill) as $id)
						$GLOBALS[html] .= "<tr><td>".showArmoryName("skill", $id).":</td><td align='right'>".$skill[$id]."</td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'><br /></td></tr>";
			$GLOBALS[html] .= "<tr><th colspan='2'>Achievments:</th></tr>";
			$total = 0;
			if (is_array($char[achievments]))
				foreach ($char[achievments] as $achievments)
					foreach (array_keys($achievments) as $id) {
						if ($achievments[$id] == 0) continue;
						$GLOBALS[html] .= "<tr><td>".showArmoryName("achievment", $id).":</td><td align='right'>".$achievments[$id]."</td></tr>";
						$total += $achievments[$id];
					}
			$GLOBALS[html] .= "<tr><td><b>Total:</b></td><td align='right'><b>".$total."</b></td></tr>";
			$GLOBALS[html] .= "</table>";
			$GLOBALS[html] .= "</td>";


			#gen filler text for char sheet
			$tmphtml  = "";
			foreach ($mychar->characterInfo->characterTab->talentSpecs->talentSpec as $mytalent) {
				$tmphtml .= "<img src='/img/armory/".$mytalent->attributes()->icon.".png' align='left' style='padding:3px;width:26px;height:26px;' ";
				$tmphtml .= "title='";
				$tmphtml .= $mytalent->attributes()->treeOne."/";
				$tmphtml .= $mytalent->attributes()->treeTwo."/";
				$tmphtml .= $mytalent->attributes()->treeThree;
				$tmphtml .= "' />";
			}
			$tmphtml .= "<br /><table><tr><td>";
			$tmphtml .= "<table>";
			$tmphtml .= "<tr><th colspan='2'>Basis Werte:</th></tr>";
			$tmphtml .= "<tr><td>St&auml;rke</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->strength->attributes()->effective."</td></tr>";
			$tmphtml .= "<tr><td>Beweglichkeit</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->agility->attributes()->effective."</td></tr>";
			$tmphtml .= "<tr><td>Ausdauer</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->stamina->attributes()->effective."</td></tr>";
			$tmphtml .= "<tr><td>Intelligenz</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->intellect->attributes()->effective."</td></tr>";
			$tmphtml .= "<tr><td>Willenskraft</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->spirit->attributes()->effective."</td></tr>";
			$tmphtml .= "<tr><td>R&uuml;stung</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->baseStats->armor->attributes()->effective."</td></tr>";
			$tmphtml .= "</table>";
			$tmphtml .= "</td><td>";
			$tmphtml .= "<table>";
			$tmphtml .= "<tr><th colspan='2'>Wiederst&auml;nde:</th></tr>";
			$tmphtml .= "<tr><td>Arkan</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->arcane->attributes()->value."</td></tr>";
			$tmphtml .= "<tr><td>Feuer</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->fire->attributes()->value."</td></tr>";
			$tmphtml .= "<tr><td>Frost</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->frost->attributes()->value."</td></tr>";
			$tmphtml .= "<tr><td>Heilig</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->holy->attributes()->value."</td></tr>";
			$tmphtml .= "<tr><td>Natur</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->nature->attributes()->value."</td></tr>";
			$tmphtml .= "<tr><td>Schatten</td><td align='right'>".
									(integer) $mychar->characterInfo->characterTab->resistances->shadow->attributes()->value."</td></tr>";
			$tmphtml .= "</table>";
			$tmphtml .= "</td></tr></table>";

			#gen char sheet
			$GLOBALS[html] .= "<td style='vertical-align: top'>";
			$GLOBALS[html] .= "<table><tr><th colspan='2'>Charakter:</th></tr>";
		  if (count($mychar->characterInfo->characterTab->items->item)) {
  		  $count = 0; $total = 0;
 	  	 	foreach ($mychar->characterInfo->characterTab->items->item as $myitem) {
    	  	$total += (integer) $myitem->attributes()->level;
     	  	$itemid = (integer) $myitem->attributes()->id;
    	  	$itemslot = (integer) $myitem->attributes()->slot;
					if (($itemslot == 3) OR ($itemslot == 18) OR ($itemslot == -1)) 
						continue;
	 	  	  $count++;
#					$GLOBALS[html] .= "<tr><td>".showArmoryName("slot", $itemslot)."</td><td>".genArmoryItemHtml($itemid)."</td></tr>";
					$GLOBALS[html] .= "<tr><td>".showArmoryName("slot", $itemslot)."</td><td>".genArmoryItemHtml($myitem, $char[level])."</td></tr>";

				}

				$GLOBALS[html] .= "</table>";
				$GLOBALS[html] .= "Items: ".$count."<br />";
				$GLOBALS[html] .= "Itemlevel &#216;: ".genArmoryIlvlHtml(round($total/$count), round($total/$count))."<br />";

			} else $GLOBALS[html] .= "Keine Iteminformationen vorhanden";
			$GLOBALS[html] .= "</tr>";
			$GLOBALS[html] .= "</table>";
			$GLOBALS[html] .= $tmphtml;

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
			if (is_array($myuser[armorychars]))
				$tmpa = " (".count($myuser[armorychars]).")";
			else
				$tmpa = "";
			$GLOBALS[html] .= "<tr><td><a href='?module=".$_POST[module]."&mydo=showusercharlist&user=".$myuser[uri]."'>".
												$myuser[name].$tmpa."</a></td>\n";
			#new object			
			if (is_array($myuser[armorychars])) {
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
															genArmoryCharHtml($char[name], $char[classid], $char[raceid], $char[genderid], $char[factionid]);
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
	if ($GLOBALS[armorydown] == 1)
		$GLOBALS[html] .= "<br /><h2>Achtung: Die Armory ist zurzeit down!</h2>";


	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
