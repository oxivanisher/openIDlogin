<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	#make sure, the user has a chat id
	$bool = 1;
	$sql = mysql_query("SELECT id FROM ".$GLOBALS[cfg][chat][usertable]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($row = mysql_fetch_array($sql))
		$bool = 0;
	if ($bool)
		$sql2 = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][usertable]." (openid,lastonline) VALUES ('".$_SESSION[openid_identifier]."', '".time()."');");

	fetchUsers();





/* marker */
	
	#send chat -> functions
	if ($_POST[myjob] == "chat") {
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][msgtable]." (sender,channel,timestamp,message) VALUES ('".
					$_SESSION[openid_identifier]."', '".$_POST[channel]."', '".time()."', '".encodeme($_POST[chat])."');");
		$GLOBALS[html] .= "<h3>Chat to channel ".$_POST[channel]." sent!</h3>";
		$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)

	}

	#get chat status -> functions (site refresh ajax request)
	elseif ($_POST[myjob] == "status") {
		
		$GLOBALS[myreturn][msg] = "status";
	}

	#get chat update -> functions (update poll ajax request)
	elseif ($_POST[myjob] == "update") {
		
		$GLOBALS[myreturn][msg] = "update";
	}

	#get users -> functions
	elseif ($_POST[myjob] == "getusers") {
		$cnt = 0;
		foreach ($GLOBALS[users][byuri] as $myuri) {
			$GLOBALS[myreturn][users][$cnt][name] = $myuri[name];
			$GLOBALS[myreturn][users][$cnt][openid] = $myuri[uri];
			$cnt++;
		}
	}

	$GLOBALS[html] .= "<h3>List Channels</h3>";
	$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
	$cnt = 0; $ncnt = 0;
	$GLOBALS[html] .= "<tr><th>Name</th><th>Owner</th><th>Created</th><th>Last message</th></tr>";

	$sql = mysql_query("SELECT id,owner,name,allowed,created,lastmessage FROM ".$GLOBALS[cfg][chat][channeltable].
											" WHERE 1 ORDER BY name ASC;");
	while ($row = mysql_fetch_array($sql)) {
		if ($row[owner] == 0) {
			$owner = "Willhelm";
		} elseif (! empty($GLOBALS[users][byuri][$GLOBALS[users][bychat][$row[owner]]][name]))
			$owner = $GLOBALS[users][byuri][$GLOBALS[users][bychat][$row[owner]]][name];
		else
			$owner = $row[owner];

		$GLOBALS[myreturn][channels][$cnt][id] = $row[id];
		$GLOBALS[myreturn][channels][$cnt][created] = strftime($GLOBALS[cfg][strftime], $row[created]);
		$GLOBALS[myreturn][channels][$cnt][lastmessage] = getAge($row[lastmessage]);
		$GLOBALS[myreturn][channels][$cnt][name] = $row[name];
		$GLOBALS[myreturn][channels][$cnt][owner] = $owner;
		$GLOBALS[myreturn][channels][$cnt][ownername] = $owner;


		$GLOBALS[html] .= "<tr>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][channels][$cnt][name]."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][channels][$cnt][owner]."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][channels][$cnt][created]."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][channels][$cnt][lastmessage]."</td>";
		$GLOBALS[html] .= "</tr>";

		$cnt++;
	}
	$GLOBALS[html] .= "</table>";
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
