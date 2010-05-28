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
	switch($_POST[myjob]) {
		case "chat":
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][msgtable]." (sender,channel,timestamp,message) VALUES ('".
						$_SESSION[openid_identifier]."', '".$_POST[channel]."', '".time()."', '".encodeme($_POST[chat])."');");
			$GLOBALS[html] .= "<h3>Chat to channel ".$_POST[channel]." sent!</h3>";
			$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
		break;

	#edit channel -> functions
		case "chat":
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][msgtable]." (sender,channel,timestamp,message) VALUES ('".
						$_SESSION[openid_identifier]."', '".$_POST[channel]."', '".time()."', '".encodeme($_POST[chat])."');");
			$GLOBALS[html] .= "<h3>Chat to channel ".$_POST[channel]." sent!</h3>";
			$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
		break;

	#get chat status -> functions (site refresh ajax request)
		case "status":
		
			$GLOBALS[myreturn][msg] = "status";
		break;

	#get chat update -> functions (update poll ajax request)
		case "update":
		
			$GLOBALS[myreturn][msg] = "update";
		break;

	#get users -> functions
		case "getusers":
			$cnt = 0;
			foreach ($GLOBALS[users][byuri] as $myuri) {
				$GLOBALS[myreturn][users][$cnt][name] = $myuri[name];
				$GLOBALS[myreturn][users][$cnt][openid] = $myuri[uri];
				$cnt++;
			}
		break;
	}

	switch($_POST[myjob]) {
		case "vieweditchannel":
			$data = getChatChannel($_POST[id]);

			$GLOBALS[html] .= "<h3>Edit Channel</h3>";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='editchannel' />";
			$GLOBALS[html] .= "<input type='hidden' name='id' value='".$data[id]."' />";
			$GLOBALS[html] .= "Name: <input type='text' name='name' value='".$data[name]."' /><br />";
			$GLOBALS[html] .= "Allowed:<br />";
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";
		break;

		default:
			$GLOBALS[html] .= "<h3>List Channels | <a href='?module=".$_POST[module]."&myjob=createchannel'>Create Channel</a></h3>";
			$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
			$cnt = 0; $ncnt = 0;
			$GLOBALS[html] .= "<tr><th>Name</th><th>Owner</th><th>Created</th><th>Last message</th></tr>";
			foreach (getChatChannels() as $data) {
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=vieweditchannel&id=".$data[id]."'>".$data[name]."</a></td>";
				$GLOBALS[html] .= "<td>".$data[ownername]."</td>";
				$GLOBALS[html] .= "<td>".$data[created]."</td>";
				$GLOBALS[html] .= "<td>".$data[lastmessage]."</td>";
				$GLOBALS[html] .= "</tr>";
			}
			$GLOBALS[html] .= "</table>";
	}
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
