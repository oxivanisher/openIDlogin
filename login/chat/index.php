<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {









/* marker */
	
	#send chat -> functions
	if ($_POST[myjob] == "chat") {
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][msgtable]." (sender,receiver,timestamp,message,new) VALUES ('".
					$_SESSION[openid_identifier]."', '".$_POST[user]."', '".time()."', '".encodeme($_POST[chat])."', '1');");
		$GLOBALS[html] .= "<h3>Chat to ".$_POST[user]." sent!</h3>";
		$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)

	}

	#get chat status -> functions
	elseif ($_POST[myjob] == "status") {
		$GLOBALS[myreturn][msg] = "status";

	}

	#get users -> functions
	elseif ($_POST[myjob] == "getusers") {
		$cnt = 0;
		foreach ($GLOBALS[module] as $myuser) {
			$GLOBALS[myreturn][users][$cnt][name] = $myuser[smf];
			$GLOBALS[myreturn][users][$cnt][openid] = $myuser[name];
			$cnt++;
		}
	}

	$GLOBALS[html] .= "<h3>List Messages</h3>";
	$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
	$cnt = 0; $ncnt = 0;
	$GLOBALS[html] .= "<tr><th>From</th><th>To</th><th>Message</th><th>Time</th></tr>";
	$sql = mysql_query("SELECT id,sender,channel,timestamp,message FROM ".$GLOBALS[cfg][chat][msgtable].
			" WHERE receiver='".$_SESSION[openid_identifier]."' OR sender='".$_SESSION[openid_identifier]."' ORDER BY timestamp DESC;");

	while ($row = mysql_fetch_array($sql)) {
		if (empty($GLOBALS[module][$row[sender]][smf]))
			$sender = $row[sender];
		else
			$sender = $GLOBALS[module][$row[sender]][smf];

		if (empty($GLOBALS[module][$row[receiver]][smf]))
			$receiver = $row[receiver];
		else
			$receiver = $GLOBALS[module][$row[receiver]][smf];

		if ($row[receiver] == $_SESSION[openid_identifier]) {
			$GLOBALS[myreturn][chatmessages][$cnt][mine] = 1;
			if ($row['new'] == "1") $ncnt++;
		} else {
			$GLOBALS[myreturn][chatmessages][$cnt][mine] = 0;
		}

		$GLOBALS[myreturn][chatmessages][$cnt][id] = $row[id];
		$GLOBALS[myreturn][chatmessages][$cnt][age] = getAge($row[timestamp]);
		$GLOBALS[myreturn][chatmessages][$cnt][receiver] = $receiver;
		$GLOBALS[myreturn][chatmessages][$cnt][sender] = $sender;
		$GLOBALS[myreturn][chatmessages][$cnt][chat] = utf8_decode(substr($row[chat], 0, 40));
		$GLOBALS[myreturn][chatmessages][$cnt]['new'] = $row['new'];
		$GLOBALS[myreturn][chatmessages][$cnt][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);

		$GLOBALS[html] .= "<tr>";
		$GLOBALS[html] .= "<td>".$sender." | ".$GLOBALS[myreturn][chatmessages][$cnt][age]."</td>";
		$GLOBALS[html] .= "<td>".$receiver."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][chatmessages][$cnt][subject]."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][chatmessages][$cnt][chat]."</td>";
		$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][chatmessages][$cnt][date]."</td>";
		$GLOBALS[html] .= "</tr>";

		$cnt++;
	}
	$GLOBALS[html] .= "</table>";

	$GLOBALS[myreturn][newchats] = $ncnt;
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
