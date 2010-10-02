<?php

# $GLOBALS[cfg][mg][gamestable]:
# id
# name
# url
# comment

# $GLOBALS[cfg][mg][namestable]:
# openid
# gameid
# name

# $GLOBALS[cfg][mg][gamestable]

#only load as module?
if ($_SESSION[loggedin] == 1) {
#check if we are allowed to do see admin stuff
	if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][role] > 6)
  	$admin = true;
	else
		$admin = false;

	if ($admin AND ($_POST[mydo] == "addgame")) {
		$sql = "INSERT INTO ".$GLOBALS[cfg][mg][gamestable]." SET name='".$_POST[name].
						"',url='".$_POST[url]."',comment='".$_POST[comment]."';";
		$sqlr = mysql_query($sql);
	} elseif ($admin AND ($_POST[mydo] == "savegame")) {
		$sql = "UPDATE ".$GLOBALS[cfg][mg][gamestable]." SET name='".$_POST[name].
						"',url='".$_POST[url]."',comment='".$_POST[comment]."' WHERE id='".$_POST[id]."';";
		$sqlr = mysql_query($sql);
	} elseif ($_POST[mydo] == "save") {
		$sql = "SELECT gameid FROM ".$GLOBALS[cfg][mg][namestable]." WHERE openid='".
						$_SESSION[openid_identifier]."' AND gameid='".$_POST[id]."';";
		$sqlr = mysql_query($sql);
		$fount = false;
		while ($row = mysql_fetch_array($sqlr))
			$found = true;
		if ($found) {
			$sql = "UPDATE ".$GLOBALS[cfg][mg][namestable]." SET name='".$_POST[name].
							"' WHERE openid='".$_SESSION[openid_identifier]."' AND gameid='".$_POST[id]."';";
			$sqlr = mysql_query($sql);
		} else {
			$sql = "INSERT INTO ".$GLOBALS[cfg][mg][namestable]." SET name='".$_POST[name].
							"', openid='".$_SESSION[openid_identifier]."', gameid='".$_POST[id]."';";
			$sqlr = mysql_query($sql);
		}
	}

	$mg = getMultigamingGames();
	#init stuff
	fetchUsers();

	$sql = "SELECT gameid,name FROM ".$GLOBALS[cfg][mg][namestable]." WHERE openid='".$_SESSION[openid_identifier]."';";
	$sqlr = mysql_query($sql);
	while ($row = mysql_fetch_array($sqlr))
		$mygames[$row[gameid]] = $row[name];

	$GLOBALS[html] .= "<h2>Liste deiner Spiele</h2>";
	$GLOBALS[html] .= "<table>";
	$GLOBALS[html] .= "<tr><th>Game</th><th>Charakter</th></tr>";
	foreach ($mg as $id => $game) {
		$GLOBALS[html] .= "<tr><td>";
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='mydo' value='save' />";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='id' value='".$id."' />";
		$GLOBALS[html] .= "<abbr title='".$game[comment]."'>".$game[name]."</abbr></td><td>";
		$GLOBALS[html] .= "<input type='text' name='name' value='".$mygames[$id]."' size='30' />&nbsp;";
		$GLOBALS[html] .= "<input type='submit' name='speichern' value='speichern' />";
		$GLOBALS[html] .= "</form></td></tr>";		
	}
	$GLOBALS[html] .= "</table>";

	if ($admin) {
		$GLOBALS[html] .= "<br /><h2>Admin</h2><hr />";
		$GLOBALS[html] .= "<h3>Spiele Editieren</h3>";
		foreach ($mg as $id => $game) {
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='mydo' value='savegame' />";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<input type='hidden' name='id' value='".$id."' />";

			$GLOBALS[html] .= "<input type='text' name='name' value='".$game[name]."' size='8' />";
			$GLOBALS[html] .= "<input type='text' name='url' value='".$game[url]."' size='20' />";
			$GLOBALS[html] .= "<input type='text' name='comment' value='".$game[comment]."' size='30' />&nbsp;";
			$GLOBALS[html] .= "<input type='submit' name='speichern' value='speichern' />";
			$GLOBALS[html] .= "</form><br />";
		}

		$GLOBALS[html] .= "<br /><h3>Spiel Hinzuf&uuml;gen</h3>";
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='mydo' value='addgame' />";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='text' name='name' value='Kurzname' size='8' />";
		$GLOBALS[html] .= "<input type='text' name='url' value='Website' size='20' />";
		$GLOBALS[html] .= "<input type='text' name='comment' value='Korrekter Name' size='30' />&nbsp;";
		$GLOBALS[html] .= "<input type='submit' name='speichern' value='speichern' />";
		$GLOBALS[html] .= "</form>";
	}

	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
