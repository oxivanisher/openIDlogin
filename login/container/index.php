<?php


#only load as module?
if ($_SESSION[loggedin] == 1) {

	#init stuff
	fetchUsers();

	#get games
	$sql = "SELECT ";

	#get player infos

	#change user rights form
	$GLOBALS[html] .= "<hr />";
	$GLOBALS[html] .= "<h2>Table</h2>";

	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
