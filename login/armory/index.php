<?php

#functions

function fetchXML ($type, $target) {
	$BASEURL = "http://eu.wowarmory.com/";
	if ($type == "i")
		$URL = $BASEURL."item-info.xml?i=".$target;
	elseif ($type == "n")
		$URL = $BASEURL."character-sheet.xml?r=".$GLOBALS[realm]."&n=".$target;
	else return 0;
	$URL .= "&rhtml=n";

	$useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; de-DE; rv:1.6) Gecko/20040206 Firefox/1.0.1";
	ini_set('user_agent',$useragent);
	$curl = curl_init();
	curl_setopt ($curl, CURLOPT_URL, $URL);
	curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$load = curl_exec($curl);
	curl_close($curl);
	return $load;
}

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
	# $item = new SimpleXMLElement(fetchXML ("i", $item));

	#set item info in $a_i[]
}

function loadNames () {
	#load names into memory $GLOBALS[armorynames]
	#id, category, iid, name
#		$GLOBALS[cfg][armory][names]
	if (! $GLOBALS[armorynames][init]) {
		$sql = mysql_query("SELECT category,iid,name FROM ".$GLOBALS[cfg][armory][names]." WHERE 1;");
		while ($row = mysql_fetch_array($sql)) {
			$GLOBALS[armorynames][$row[category]][$row[iid]] = $row[name];
			$GLOBALS[armorynames][init] = true;
		}
	}
}

function showName ($category, $id) {
	loadNames();
	#show name, if nonexistant, value
	$ret = $GLOBALS[armorynames][$category][$id];
	if (empty($ret))
		$ret = $id;
	return $ret;
}

function genIlvl ($mychar) {
	if (count($mychar->characterInfo->characterTab->items->item)) {
		$count = 0; $total = 0;
		foreach ($mychar->characterInfo->characterTab->items->item as $myitem) {
			$count++;
			$total += (integer) $myitem->attributes()->level;
		}
		return round($total/$count);
	} else return 0;
}

function fetchCharacter ($charname) {
	if (!isset($GLOBALS[armorycharupdatecount]))
		$GLOBALS[armorycharupdatecount] = 0;

	#name, timestamp, content, level, genderid, classid, raceid, ilevelavg
	$mychar = "";
	unset($char);
	$sql = mysql_query("SELECT timestamp,ilevelavg,name,level,genderid,classid,raceid FROM ".
					$GLOBALS[cfg][armory][charcachetable]." WHERE name LIKE '".$charname."' ORDER BY timestamp ASC;");
					#FIXME geht die suche noch?
	$mychar[level] = null;
	while ($row = mysql_fetch_array($sql)) {
			$mychar[timestamp]	= $row[timestamp];
			$mychar[ilevelavg]	= $row[ilevelavg];
			$mychar[name]				= $row[name];
			$mychar[level]			= $row[level];
			$mychar[gennderid]	= $row[genderid];
			$mychar[classid]		= $row[classid];
			$mychar[raceid]			= $row[raceid];
	}
	#check if char is in db and accurate, if not, fetch online
	if (! $mychar[level]) {
		sysmsg ("Fetching nonexisting Char from Armory: ".$charname, 3);
		$mychar[content] = fetchXML ("n", $charname);
		$mychar[timestamp] = time();
		$char = new SimpleXMLElement($mychar[content]);
		$myilvl = genIlvl($char);
		$sql = "INSERT INTO ".$GLOBALS[cfg][armory][charcachetable]." SET ".
					"name='".$char->characterInfo->character['name']."', ".
					"timestamp='".$mychar[timestamp]."', ".
					"content='".mysql_real_escape_string($mychar[content])."', ".
					"level='".$char->characterInfo->character['level']."', ".
					"genderid='".$char->characterInfo->character['genderId']."', ".
					"classid='".$char->characterInfo->character['classId']."', ".
					"raceid='".$char->characterInfo->character['raceId']."', ".
					"ilevelavg='".$myilvl."';";
		$mychar[ilevelavg] = $myilvl;
		$mychar[name]				= (string) $char->characterInfo->character['name'];
		$mychar[timestamp]	= (string) $char->characterInfo->character['timestamp'];
		$mychar[level]			= (string) $char->characterInfo->character['level'];
		$mychar[genderid]		= (string) $char->characterInfo->character['genderId'];
		$mychar[classid]		= (string) $char->characterInfo->character['classId'];
		$mychar[raceid]			= (string) $char->characterInfo->character['raceId'];
		$mychar[factionid]	= (string) $char->characterInfo->character['factionId'];
		if (! empty($char->characterInfo->character['name']))
			$sqlr = mysql_query($sql);
	} else {
		if ((($mychar[timestamp] + $GLOBALS[armorychartimeout])  < time()) 
		AND ($GLOBALS[armorycharupdatecount] <= $GLOBALS[armorycharmaxupdate])) {
			$GLOBALS[armorycharupdatecount]++;
			$mychar[content] = fetchXML ("n", $charname);
			if (strlen($mychar[content]) > 250) {
				sysmsg ("Fetching data from Armory due old Database entry for Char: ".$charname, 3);
				$mychar[timestamp] = time();
				$char = new SimpleXMLElement($mychar[content]);
				$myilvl = genIlvl($char);
				$sql = "UPDATE ".$GLOBALS[cfg][armory][charcachetable]." SET ".
							"timestamp='".$mychar[timestamp]."', ".
							"content='".mysql_real_escape_string($mychar[content])."', ".
							"level='".$char->characterInfo->character['level']."', ".
							"genderid='".$char->characterInfo->character['genderId']."', ".
							"classid='".$char->characterInfo->character['classId']."', ".
							"raceid='".$char->characterInfo->character['raceId']."', ".
							"ilevelavg='".$myilvl."' WHERE name='".$char->characterInfo->character['name']."';";
				$sqlr = mysql_query($sql);
				$mychar[ilevelavg] = $myilvl;
				$mychar[name]				= (string) $char->characterInfo->character['name'];
				$mychar[timestamp]	= (string) $char->characterInfo->character['timestamp'];
				$mychar[level]			= (string) $char->characterInfo->character['level'];
				$mychar[genderid]		= (string) $char->characterInfo->character['genderId'];
				$mychar[classid]		= (string) $char->characterInfo->character['classId'];
				$mychar[raceid]			= (string) $char->characterInfo->character['raceId'];
				$mychar[factionid]	= (string) $char->characterInfo->character['factionId'];
			} else {
				sysmsg ("Fetching data from Armory failed. Armory probably down. XML Length: ".strlen($mychar[content]), 3);
			}
		} else {
			sysmsg ("Fetching data from Database for Char: ".$charname, 3);
/*			$char = new SimpleXMLElement($mychar[content]);
			$mychar[name]				= (string) $char->characterInfo->character['name'];
			$mychar[timestamp]	= (string) $char->characterInfo->character['timestamp'];
			$mychar[level]			= (string) $char->characterInfo->character['level'];
			$mychar[genderid]		= (string) $char->characterInfo->character['genderId'];
			$mychar[classid]		= (string) $char->characterInfo->character['classId'];
			$mychar[raceid]			= (string) $char->characterInfo->character['raceId'];
			$mychar[factionid]	= (string) $char->characterInfo->character['factionId'];
			*/
		}
	}

	if (empty($mychar[level])) {
		sysmsg ("ERROR fetching character info for ".$charname."!", 3);
		return null;
	} else {
		return $mychar;
	}
}

function genIlvlHtml ($ilvl, $text) {
	$color = "";
	if ($ilvl < 50)
		$color = "#888888";
	elseif ($ilvl < 100)
		$color = "#998888";
	elseif ($ilvl < 200)
		$color = "#aa6666";
	elseif ($ilvl < 220)
		$color = "#cc4444";
	elseif ($ilvl < 240)
		$color = "#dd2222";
	elseif ($ilvl < 250)
		$color = "#ee1111";
	elseif ($ilvl < 260)
		$color = "#ff0000";
	else
		$color = "#48233e";
	return "<span title='Itemlevel Durchschnitt: ".$ilvl."' style='color:".$color.
					"; border-width:1px; border-style:solid; border-color:".$color.";'>".
					$text."</span>";
}

function genClassClass ($ilvl) {
	switch ($ilvl) {
		case "1": $myclass = "inpWarrior"; break;
		case "2": $myclass = "inpPaladin"; break;
		case "3": $myclass = "inpHunter"; break;
		case "4": $myclass = "inpRogue"; break;
		case "5": $myclass = "inpPriest"; break;
		case "6": $myclass = "inpDeathknight"; break;
		case "7": $myclass = "inpShaman"; break;
		case "8": $myclass = "inpMage"; break;
		case "9": $myclass = "inpWarlock"; break;
		case "11": $myclass = "inpDruid"; break;
		default: $myclass = "";
	}
	return $myclass;
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
			if ($char = fetchCharacter($mycharname)) {
				$GLOBALS[html] .= "<tr class='".genClassClass($char[classid])."'>";
				$GLOBALS[html] .= "<td>&nbsp;</td>";
				$GLOBALS[html] .= "<td>".$char[name]."</td>";
				$GLOBALS[html] .= "<td>".$char[level]."</td>";
				$GLOBALS[html] .= "<td>".showName("gender", $char[genderid])."</td>";
#				$GLOBALS[html] .= "<td>".showName("class", $char[classid])."</td>";
				$GLOBALS[html] .= "<td>".showName("race", $char[raceid])."</td>";
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
					if ($char = fetchCharacter($mycharname)) {
						$GLOBALS[html] .= genIlvlHtml($char[ilevelavg],$char[level]).
															"<span class='".genClassClass($char[classid])."' title='".showName("race", $char[raceid]).
															", ".showName("gender", $char[genderid]).", ".showName("faction", $char[factionid])."'>".$char[name]." ".
															"</span>\n";
					} else {
						$GLOBALS[html] .= genIlvlHtml(0,"00").$mycharname;
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
