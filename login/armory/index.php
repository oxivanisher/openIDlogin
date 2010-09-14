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

function fetchCharacter ($charname) {
	#name, timestamp, content, level, genderid, classid, raceid, ilevelavg
	$mychar = "";
	$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][armory][charcachetable].
					" WHERE name LIKE '%".$charname."%';");
	while ($row = mysql_fetch_array($sql)) {
			$mychar[timestamp]	= $row[timestamp];
			$mychar[content]		= $row[content];
	}
	#check if char is in db and accurate, if not, fetch online
	if (empty($mychar[content])) {
		sysmsg ("Fetching nonexisting Char from Armory: ".$charname, 3);
		$mychar[content] = fetchXML ("n", $charname);
		$mychar[timestamp] = time();
		$char = new SimpleXMLElement($mychar[content]);
		$sql = "INSERT INTO ".$GLOBALS[cfg][armory][charcachetable]." SET ".
					"name='".$char->characterInfo->character['name']."', ".
					"timestamp='".$mychar[timestamp]."', ".
					"content='".mysql_real_escape_string($mychar[content])."', ".
					"level='".$char->characterInfo->character['level']."', ".
					"genderid='".$char->characterInfo->character['genderId']."', ".
					"classid='".$char->characterInfo->character['classId']."', ".
					"raceid='".$char->characterInfo->character['raceId']."', ".
					"ilevelavg='"."0"."';";
		if (! empty($char->characterInfo->character['name']))
			$sqlr = mysql_query($sql);
	} else {
		if (($mychar[timestamp] + $GLOBALS[armorychartimeout])  < time()) {
			sysmsg ("Fetching data from Armory due old Database entry for Char: ".$charname, 3);
			$mychar[content] = fetchXML ("n", $charname);
			$mychar[timestamp] = time();
			$char = new SimpleXMLElement($mychar[content]);
			$sql = "UPDATE ".$GLOBALS[cfg][armory][charcachetable]." SET ".
						"timestamp='".$mychar[timestamp]."', ".
						"content='".mysql_real_escape_string($mychar[content])."', ".
						"level='".$char->characterInfo->character['level']."', ".
						"genderid='".$char->characterInfo->character['genderId']."', ".
						"classid='".$char->characterInfo->character['classId']."', ".
						"raceid='".$char->characterInfo->character['raceId']."', ".
						"ilevelavg='"."0"."' WHERE name='".$char->characterInfo->character['name']."';";
			$sqlr = mysql_query($sql);
		} else {
			sysmsg ("Fetching data from Database for Char: ".$charname, 3);
			$char = new SimpleXMLElement($mychar[content]);
		}
	}
	#set the array to the found data
	$mychar[name]				= (string) $char->characterInfo->character['name'];
	$mychar[timestamp]	= (string) $char->characterInfo->character['timestamp'];
	$mychar[level]			= (string) $char->characterInfo->character['level'];
	$mychar[genderid]		= (string) $char->characterInfo->character['genderId'];
	$mychar[classid]		= (string) $char->characterInfo->character['classId'];
	$mychar[raceid]			= (string) $char->characterInfo->character['raceId'];
	$mychar[ilevelavg]	= (string) $char->characterInfo->items->item[0]->item['level'];

	if (empty($char->characterInfo->character['name'])) {
		sysmsg ("ERROR fetching character info for ".$charname."!", 3);
		return null;
	} else {
		return $mychar;
	}
}

#only load as module?
if ($_SESSION[loggedin] == 1) {
	# module functions
	if ($_POST[mydo] == "savechars") {
		if (($_POST[user] == $_SESSION[openid_identifier]) OR ($_SESSION[isadmin])) {
			$tnames = explode(",", $_POST[chars]);
			$nnames = array();
			foreach ($tnames as $tname) {
				array_push($nnames, trim($tname));
			}

			$sql = "UPDATE ".$GLOBALS[cfg][userprofiletable]." SET armorychars='".serialize($nnames).
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
	
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>Name</th><th>Level</th><th>Geschlecht</th><th>Klasse</th><th>Rasse</th><th>Itemlevel Durchschnitt</th></tr>";
		if ($GLOBALS[users][byuri][$_POST[user]][armorychars])
		foreach ($GLOBALS[users][byuri][$_POST[user]][armorychars] as $mycharname) {
			if ($char = fetchCharacter($mycharname)) {
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td>".$char[name]."</td>";
				$GLOBALS[html] .= "<td>".$char[level]."</td>";
				$GLOBALS[html] .= "<td>".showName("gender", $char[genderid])."</td>";
				$GLOBALS[html] .= "<td>".showName("class", $char[classid])."</td>";
				$GLOBALS[html] .= "<td>".showName("race", $char[raceid])."</td>";
				$GLOBALS[html] .= "<td>".$char[ilevelavg]."</td>";
				$GLOBALS[html] .= "</tr>";
			} else {
				$GLOBALS[html] .= "Charakter ".$mycharname." wurde in der Armory nicht gefunden.<br />";
			}
		}
		$GLOBALS[html] .= "</table>";
	
	#show overview
	} else {
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>User</th><th>Charakter</th></tr>";
		foreach ($GLOBALS[users][byuri] as $myuser) {
			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&mydo=showdetail&user=".$myuser[uri]."'>".$myuser[name]."</a></td>";
			$GLOBALS[html] .= "<td>";
			$bool = true; $tmp = "";
			if (! empty($myuser[armorychars])) {
				foreach ($myuser[armorychars] as $mycharname) {
					if ($char = fetchCharacter($mycharname)) {
						if ($bool) $bool = false;
						else $tmp = ", ";

						$GLOBALS[html] .= $tmp."<abbr title='".showName("race", $char[raceid])." ".showName("class", $char[classid]).
															" ".$char[ilevelavg]."'>".$char[name]." ".$char[level]."</abbr>";
					}
				}
			} else $GLOBALS[html] .= "&nbsp;";

			$GLOBALS[html] .= "</td>";
			$GLOBALS[html] .= "</tr>";
		}
		$GLOBALS[html] .= "</table>";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
