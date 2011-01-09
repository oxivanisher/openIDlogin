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
			$sql = "SELECT content,ilevelavg FROM ".$GLOBALS[cfg][armory][charcachetable]." WHERE name LIKE '".$char[name]."';";
			$sqlr = mysql_query($sql);
			while ($row = mysql_fetch_array($sqlr)) {
				$char[content] = $row[content];
				$char[ilvl] = $row[ilevelavg];
			}
			$mychar = new SimpleXMLElement($char[content]);

			$mytitle = "<h3 class='".genArmoryClassClass($char[classid])."'>".$char[name]." ".$char[level].", ".showArmoryName("gender", $char[genderid])." ".
									showArmoryName("race", $char[raceid])." ".showArmoryName("class", $char[classid])."</h3>";

			$myowner = "Eingetragen bei:";
			foreach (getArmoryUserOfChar($_POST[mycharname]) as $myuser)
				$myowner .= " ".genUserLink($myuser);

			$mypinfo  = "<a href='?module=".$_POST[module]."&mydo=showchardetail&myjob=forcerefresh&mycharname=".$_POST[mycharname]."'><img src='/".$GLOBALS[cfg][moduledir].
									"/reload.png' title='Force Reload' style='width:24px;height:24px;align:right;float:right;padding:5px;' /></a>";
#			$mypinfo .= "<a href='?module=".$_POST[module]."&mydo=showchardetail&mycharname=".$_POST[mycharname]."'><img src='/".$GLOBALS[cfg][moduledir].
#									"/refresh.png' title='Refresh' style='width:24px;height:24px;align:right;float:right;padding:5px;' /></a> ";

			$mypvp = "PVP Kills: ".$char[pvpkills];

			$myskills  = "<table style='width:100%;'>";
			$myskills .= "<tr><th colspan='2'>Berufe:</th></tr>";
			if (is_array($char[skills]))
				foreach ($char[skills] as $skill)
					foreach (array_keys($skill) as $id)
						$myskills .= "<tr><td>".showArmoryName("skill", $id).":</td><td align='right'>".$skill[$id]."</td></tr>";
			$myskills .= "</table>";

			$myach = "";
			$myach .= "<table style='width:100%;'><tr><th colspan='2'>Achievments:</th></tr>";
			$total = 0;
			if (is_array($char[achievments]))
				foreach ($char[achievments] as $achievments)
					foreach (array_keys($achievments) as $id) {
						if ($achievments[$id] == 0) continue;
						$myach .= "<tr><td>".showArmoryName("achievment", $id).":</td><td align='right'>".$achievments[$id]."</td></tr>";
						$total += $achievments[$id];
					}
			$myach .= "<tr><td><b>Total:</b></td><td align='right'><b>".$total."</b></td></tr>";
			$myach .= "</table>";

			#gen filler text for char sheet
			$mytalents  = "";
			if (count($mychar->characterInfo->characterTab->talentSpecs->talentSpec))
				foreach ($mychar->characterInfo->characterTab->talentSpecs->talentSpec as $mytalent) {

					$mytalents .= "<img src='/img/wowarmory/".$mytalent->attributes()->icon.
												".png' style='padding:3px;width:26px;height:26px;' ";
					$mytalents .= "title='";
					$mytalents .= $mytalent->attributes()->treeOne."/";
					$mytalents .= $mytalent->attributes()->treeTwo."/";
					$mytalents .= $mytalent->attributes()->treeThree;
					$mytalents .= "' />";
				}

			if (count($mychar->characterInfo->characterTab->baseStats->strength)) {
				$mystats  = "<table style='width:100%;text-align:left;'>";
				$mystats .= "<tr><th colspan='2'>Basis Werte:</th></tr>";
				$mystats .= "<tr><td>St&auml;rke</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->strength->attributes()->effective."</td></tr>";
				$mystats .= "<tr><td>Beweglichkeit</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->agility->attributes()->effective."</td></tr>";
				$mystats .= "<tr><td>Ausdauer</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->stamina->attributes()->effective."</td></tr>";
				$mystats .= "<tr><td>Intelligenz</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->intellect->attributes()->effective."</td></tr>";
				$mystats .= "<tr><td>Willenskraft</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->spirit->attributes()->effective."</td></tr>";
				$mystats .= "<tr><td>R&uuml;stung</td><td align='right'>".
										$mychar->characterInfo->characterTab->baseStats->armor->attributes()->effective."</td></tr>";
				$mystats .= "</table>";
			}

			if (count($mychar->characterInfo->characterTab->resistances->arcane)) {
				$myres  = "<table style='width:100%;text-align:left;'>";
				$myres .= "<tr><th colspan='2'>Wiederst&auml;nde:</th></tr>";
				$myres .= "<tr><td>Arkan</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->arcane->attributes()->value."</td></tr>";
				$myres .= "<tr><td>Feuer</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->fire->attributes()->value."</td></tr>";
				$myres .= "<tr><td>Frost</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->frost->attributes()->value."</td></tr>";
				$myres .= "<tr><td>Heilig</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->holy->attributes()->value."</td></tr>";
				$myres .= "<tr><td>Natur</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->nature->attributes()->value."</td></tr>";
				$myres .= "<tr><td>Schatten</td><td align='right'>".
										$mychar->characterInfo->characterTab->resistances->shadow->attributes()->value."</td></tr>";
				$myres .= "</table>";
			}

			if (count($mychar->characterInfo->characterTab->melee->power)) {
				$mymelee  = "<table style='width:100%;text-align:left;'>";
				$mymelee .= "<tr><th colspan='2'>Nahkampf:</th></tr>";
				$mymelee .= "<tr><td>Kraft</td><td align='right'>".
										$mychar->characterInfo->characterTab->melee->power->attributes()->effective."</td></tr>";
				$mymelee .= "<tr><td>Trefferwertung</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->melee->hitRating->attributes()->increasedHitPercent."%'>".
										$mychar->characterInfo->characterTab->melee->hitRating->attributes()->value."</abbr></td></tr>";
				$mymelee .= "<tr><td>Kritische Treffer</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->melee->critChance->attributes()->rating."'>".
										$mychar->characterInfo->characterTab->melee->critChance->attributes()->percent."%</td></tr>";
				$mymelee .= "<tr><td>Expertiese</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->melee->expertise->attributes()->percent."%'>".
										$mychar->characterInfo->characterTab->melee->expertise->attributes()->value."</td></tr>";
				$mymelee .= "</table>";
			}

			if (count($mychar->characterInfo->characterTab->ranged->power)) {
				$myranged  = "<table style='width:100%;text-align:left;'>";
				$myranged .= "<tr><th colspan='2'>Fernkampf:</th></tr>";
				$myranged .= "<tr><td>Kraft</td><td align='right'>".
										$mychar->characterInfo->characterTab->ranged->power->attributes()->effective."</td></tr>";
				$myranged .= "<tr><td>Trefferwertung</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->ranged->hitRating->attributes()->increasedHitPercent."%'>".
										$mychar->characterInfo->characterTab->ranged->hitRating->attributes()->value."</abbr></td></tr>";
				$myranged .= "<tr><td>Kritische Treffer</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->ranged->critChance->attributes()->rating."'>".
										$mychar->characterInfo->characterTab->ranged->critChance->attributes()->percent."%</td></tr>";
				$myranged .= "<tr><td>Geschwindigkeit</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->ranged->speed->attributes()->hastePercent."%'>".
										$mychar->characterInfo->characterTab->ranged->speed->attributes()->value."</td></tr>";
				$myranged .= "</table>";
			}

			if (count($mychar->characterInfo->characterTab->spell->bonusDamage)) {
				$types = array("arcane", "fire", "frost", "holy", "nature", "shadow");
				$dtotal = 0; $dcount = 0; $dabbr = "";
				foreach ($types as $type) {
					$dtotal += (integer) $mychar->characterInfo->characterTab->spell->bonusDamage->$type->attributes()->value;
					if ($dcount) $dabbr .= ", ".$type.": ".$mychar->characterInfo->characterTab->spell->bonusDamage->$type->attributes()->value;
					else $dabbr .= $type.": ".$mychar->characterInfo->characterTab->spell->bonusDamage->$type->attributes()->value;
					$dcount++;
				}
				$cabbr = ""; $ccount = 0;
				foreach ($types as $type) {
					if ($ccount) $cabbr .= ", ".$type.": ".$mychar->characterInfo->characterTab->spell->critChance->$type->attributes()->percent."%";
					else $cabbr .= $type.": ".$mychar->characterInfo->characterTab->spell->critChance->$type->attributes()->percent."%";
					$cvalue = $mychar->characterInfo->characterTab->spell->critChance->$type->attributes()->percent;
					$ccount++;
				}
				$myspell  = "<table style='width:100%;text-align:left;'>";
				$myspell .= "<tr><th colspan='2'>Zauber:</th></tr>";
				$myspell .= "<tr><td>Zauberschaden</td><td align='right'><abbr title='".$dabbr."'>".(round($dtotal/$dcount))."</abbr></td></tr>";
				$myspell .= "<tr><td>Kritische Treffer</td><td align='right'><abbr title='".$cabbr."'>".$cvalue."%</abbr></td></tr>";
				$myspell .= "<tr><td>Zus&auml;tzliche Heilung</td><td align='right'>".
										$mychar->characterInfo->characterTab->spell->bonusHealing->attributes()->value."</td></tr>";
				$myspell .= "<tr><td>Trefferwertung</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->spell->hitRating->attributes()->increasedHitPercent."%'>".
										$mychar->characterInfo->characterTab->spell->hitRating->attributes()->value."</td></tr>";
				$myspell .= "<tr><td>Geschwindigkeit</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->spell->hasteRating->attributes()->hastePercent."%'>".
										$mychar->characterInfo->characterTab->spell->hasteRating->attributes()->hasteRating."</td></tr>";
				$myspell .= "<tr><td>Mana Regeneration</td><td align='right'>".
										$mychar->characterInfo->characterTab->spell->manaRegen->attributes()->notCasting."</td></tr>";
				$myspell .= "</table>";
			}

			if (count($mychar->characterInfo->characterTab->defenses->armor)) {
				$mydefense  = "<table style='width:100%;text-align:left;'>";
				$mydefense .= "<tr><th colspan='2'>Verteidigung:</th></tr>";
				$mydefense .= "<tr><td>Kraft</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->armor->attributes()->percent."%'>".
										$mychar->characterInfo->characterTab->defenses->armor->attributes()->effective."</td></tr>";
				$mydefense .= "<tr><td>Verteidigung</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->defense->attributes()->increasePercent."%'>".
										$mychar->characterInfo->characterTab->defenses->defense->attributes()->value."</td></tr>";
				$mydefense .= "<tr><td>Ducken</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->dodge->attributes()->rating."'>".
										$mychar->characterInfo->characterTab->defenses->dodge->attributes()->percent."%</td></tr>";
				$mydefense .= "<tr><td>Ausweichen</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->parry->attributes()->rating."'>".
										$mychar->characterInfo->characterTab->defenses->parry->attributes()->percent."%</td></tr>";
				$mydefense .= "<tr><td>Blockieren</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->block->attributes()->rating."'>".
										$mychar->characterInfo->characterTab->defenses->block->attributes()->percent."%</td></tr>";
				$mydefense .= "<tr><td>Abh&auml;rtung</td><td align='right'><abbr title='".$mychar->characterInfo->characterTab->defenses->resilience->attributes()->hitPercent."%'>".
										$mychar->characterInfo->characterTab->defenses->resilience->attributes()->value."</td></tr>";
				$mydefense .= "</table>";
			}

			$qslots = array(0, 1, 2, 5, 6, 7, 9, 14); $slots = array();
			if (count($mychar->characterInfo->characterTab->items->item))
				foreach ($mychar->characterInfo->characterTab->items->item as $myitem)
					if (in_array((integer) $myitem->attributes()->slot, $qslots))
						array_push($slots, (integer) $myitem->attributes()->id);
			$bool = true; $tmp = ""; $pcs = "";
			foreach ($slots as $myslot) {
				if ($bool) $bool = false;
				else $tmp = ":";
				$pcs .= $tmp.$myslot;
			}
			for ($i = 0; $i <= 18; $i++)
				$slot[$i] = "&nbsp;";
			if (count($mychar->characterInfo->characterTab->items->item))
 	  	 	foreach ($mychar->characterInfo->characterTab->items->item as $myitem) {
					if (in_array((integer) $myitem->attributes()->slot, $qslots)) {
						$qpcs = $pcs;
					} else $qpcs = "";
					$slot[(integer) $myitem->attributes()->slot] = genArmoryItemHtml($myitem, $char[level], $qpcs);
				}

			$myilvl = genArmoryIlvlHtml($char[ilvl], $char[ilvl]);

			$cont = templGetFile("charview.html");
			$cont = templReplText($cont, "TITLE", $mytitle);
			$cont = templReplText($cont, "BASESTATS", $mystats);
			$cont = templReplText($cont, "MELEESTATS", $mymelee);
			$cont = templReplText($cont, "RANGEDSTATS", $myranged);
			$cont = templReplText($cont, "SPELLSTATS", $myspell);
			$cont = templReplText($cont, "DEFENSESTATS", $mydefense);
			$cont = templReplText($cont, "RESISTANCES", $myres);
			$cont = templReplText($cont, "TALENTSPECTS", $mytalents);
			$cont = templReplText($cont, "SKILLS", $myskills);
			$cont = templReplText($cont, "PVP", $mypvp);
			$cont = templReplText($cont, "ACHIEVMENTS", $myach);
			$cont = templReplText($cont, "ITEMLEVEL", $myilvl);
			$cont = templReplText($cont, "FORCEUPDATE", $mypinfo);
			$cont = templReplText($cont, "OWNEDBY", $myowner);
			$cont = templReplText($cont, "PROFILEAGE", getAge($char[timestamp]));
			$cont = templReplText($cont, "NEXTPROFILEUPDATE", genTime($GLOBALS[armorychartimeout] + $char[timestamp]));
			for ($i = 0; $i <= 18; $i++)
				$cont = templReplText($cont, "SLOT".$i, $slot[$i]);
			$GLOBALS[html] .= $cont;
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
