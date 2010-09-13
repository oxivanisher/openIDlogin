<?php

#functions

function fetchXML ($type, $target) {
	#FIXME get realm from settings!
	#FIXME get charnames from db
	$realm = "Krag'jin";

	$BASEURL = "http://eu.wowarmory.com/item-info.xml?";
	if ($type == "i")
		$URL = $BSEURL."i=".$target;
	elseif ($type == "n")
		$URL = $BASEURL."r=".$realm."&n=".$target;
	$URL = $URL."&rhtml=n";

	# UserAgent setzen
	$useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.0; de-DE; rv:1.6)
	Gecko/20040206 Firefox/1.0.1";
	ini_set('user_agent',$useragent);
	header('Content-Type: text/html; charset=utf-8');
 
	# CURL initialisieren und XML-Datei laden
	$curl = curl_init();
 
	curl_setopt ($curl, CURLOPT_URL, $URL);
	curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
 
	$load = curl_exec($curl);
 
	curl_close($curl);
 
	# eingelesenen String zu SimpleXMLElement umformen
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
	#load names into memory $a_n[]
	#id, category, iid, name
#		$GLOBALS[cfg][armory][names]

}

function fetchCharacter ($charname) {
	#FIXME need char table!
	#check if char is in db and accurate, if not, fetch online
	# $char = new SimpleXMLElement(fetchXML ("n", $charname));

}

#only load as module?
if ($_SESSION[loggedin] == 1) {

	#init stuff
	fetchUsers();
 
	echo $xml->characterInfo->character['name']." hat das
	Level ".$xml->characterInfo->character['level'];

	#get player infos

	#change user rights form
	$GLOBALS[html] .= "<hr />";
	$GLOBALS[html] .= "<h2>Table</h2>";

	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
