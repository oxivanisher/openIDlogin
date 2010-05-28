<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	#make sure the user has a chat id. if not, create one at once (before fetchUsers();)
	$bool = 1;
	$sql = mysql_query("SELECT id,subscr FROM ".$GLOBALS[cfg][chat][usertable]." WHERE openid='".$_SESSION[openid_identifier]."';");
	while ($row = mysql_fetch_array($sql)) {
		unset ($GLOBALS[chat][subscr]);
		$GLOBALS[chat][subscr] = unserialize($row[subscr]);
		$bool = 0;
	}
	if ($bool) {
		$sql2 = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][usertable]." (openid,lastonline,subscr) VALUES ('".$_SESSION[openid_identifier].
						"', '".time()."', '".serialize(array())."');");
		unset ($GLOBALS[chat][subscr]);
		$GLOBALS[chat][subscr] = array();
	}

	if ($_POST[myjob] != "update") fetchUsers();

	#send chat -> functions
	switch($_POST[myjob]) {
		case "chat":
			$data = getChatChannel($_POST[id]);
			if (($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]) OR ($_SESSION[isadmin]) OR
														in_array($GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat], unserialize($data[allowed]))) {
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][msgtable]." (sender,channel,timestamp,message) VALUES ('".
							$GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]."', '".$_POST[channel]."', '".time()."', '".encodeme($_POST[chat])."');");

				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][chat][channeltable]." SET lastmessage='".time()."' WHERE id='".$_POST[channel]."';");
				$GLOBALS[html] .= "<h3>Chat to channel ".$_POST[channel]." sent!</h3>";
				$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
			} else {
				$GLOBALS[html] .= "<h3>Message NOT sent!</h3>";
				$GLOBALS[myreturn][msg] = "notsent";
			}
		break;

		#edit channel -> functions
		case "editchannel":
			$data = getChatChannel($_POST[id]);
			if (($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]) OR ($_SESSION[isadmin])) {
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][chat][channeltable]." SET name='".$_POST[name]."', allowed='".serialize($_POST[allowed])."' WHERE id='".$_POST[id]."';");
				$GLOBALS[html] .= "<h3>Channel ".$_POST[channel]." edited!</h3>";
				$GLOBALS[myreturn][msg] = "edited"; #FIXME ok check (error/sent)
			} else {
				$GLOBALS[html] .= "<h3>Channel NOT edited!</h3>";
				$GLOBALS[myreturn][msg] = "notedited";
			}
		break;

		#create channel -> functions
		case "createchannel":
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][channeltable]." SET owner='".$GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]."', name='".$_POST[name]."', allowed='".
							serialize($_POST[allowed])."', created='".time()."';");
			$GLOBALS[html] .= "<h3>Channel ".$_POST[name]." created!</h3>";
			$GLOBALS[myreturn][msg] = "created"; #FIXME ok check (error/sent)
		break;

		#delete channel -> functions
		case "deletechannel":
			$data = getChatChannel($_POST[id]);
			if (($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]) OR ($_SESSION[isadmin])) {
				$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][chat][channeltable]." WHERE id='".$_POST[id]."';");
				$swl2 = mysql_query("DELETE FROM ".$GLOBALS[cfg][chat][msgtable]." WHERE channel='".$_POST[id]."';");
				$GLOBALS[html] .= "<h3>Channel ".$_POST[id]." and all related messages deleted!</h3>";
				$GLOBALS[myreturn][msg] = "deleted"; #FIXME ok check (error/sent)
			} else {
				$GLOBALS[html] .= "<h3>Channel NOT deleted!</h3>";
				$GLOBALS[myreturn][msg] = "notdeleted";
			}
		break;

		#join channel -> functions
		case "joinchannel":
			$data = getChatChannel($_POST[id]);
			if ((in_array($GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat], unserialize($data[allowed]))) OR ($_SESSION[isadmin])) {
				array_push($GLOBALS[chat][subscr], $_POST[id]);
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][chat][usertable]." SET subscr='".serialize($GLOBALS[chat][subscr])."' WHERE openid='".$_SESSION[openid_identifier]."';");
				$GLOBALS[html] .= "<h3>Channel joined!</h3>";
				$GLOBALS[myreturn][msg] = "joined";
			} else {
				$GLOBALS[html] .= "<h3>Channel NOT joined!</h3>";
				$GLOBALS[myreturn][msg] = "notjoined";
			}
		break;

		#leave channel -> functions
		case "leavechannel":
			$tmpsub = array();
			foreach ($GLOBALS[chat][subscr] as $mysubscr => $myid) {
				if ($myid != $_POST[id])
					array_push($tmpsub, $myid);
			}
			unset ($GLOBALS[chat][subscr]);
			$GLOBALS[chat][subscr] = $tmpsub;
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][chat][usertable]." SET subscr='".serialize($GLOBALS[chat][subscr])."' WHERE openid='".$_SESSION[openid_identifier]."';");
			$GLOBALS[html] .= "<h3>Channel left!</h3>";
			$GLOBALS[myreturn][msg] = "leaved"; #FIXME ok check (error/sent)
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
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='editchannel' />";
			$GLOBALS[html] .= "<input type='hidden' name='id' value='".$data[id]."' />";
			$GLOBALS[html] .= "Name: <input type='text' name='name' value='".$data[name]."' /><br />";
			$GLOBALS[html] .= "Allowed:<br />".genAllowedCheckbox(unserialize($data[allowed]));
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";
			if ($_SESSION[isadmin] OR ($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]]))
				$GLOBALS[html] .= "<br /><br /><br /><a href='?module=".$_POST[module]."&myjob=deletechannel&id=".$data[id]."'>Delete this channel (!)</a>";
		break;

		case "viewcreatechannel":
			$data = getChatChannel($_POST[id]);
			$GLOBALS[html] .= "<h3>New Channel</h3>";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='createchannel' />";
			$GLOBALS[html] .= "Name: <input type='text' name='name' value='".$data[name]."' /><br />";
			$GLOBALS[html] .= "Allowed:<br />".genAllowedCheckbox();
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";
		break;

		case "viewchannellist":
			#list channels
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."'>View Messages</a> | <a href='?module=".$_POST[module]."&myjob=viewchannellist'>List Channels</a> | <a href='?module=".$_POST[module]."&myjob=viewcreatechannel'>Create Channel</a></h3>";
			$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
			$cnt = 0; $ncnt = 0;
			$GLOBALS[html] .= "<tr><th>Name</th><th>Owner</th><th>Created</th><th>Last message</th><th>Join / Leave</th></tr>";
			foreach (getAllChatChannels() as $data) {
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=vieweditchannel&id=".$data[id]."'>".$data[name]."</a></td>";
				$GLOBALS[html] .= "<td>".$data[ownername]."</td>";
				$GLOBALS[html] .= "<td>".$data[created]."</td>";
				$GLOBALS[html] .= "<td>".$data[lastmessage]."</td>";


				if (in_array($data[id], $GLOBALS[chat][subscr])) {
						$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=leavechannel&id=".$data[id]."'>Leave</a></td>";
				} elseif (in_array($GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat], unserialize($data[allowed]))) {
						$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=joinchannel&id=".$data[id]."'>Join</a></td>";
				} elseif ($_SESSION[isadmin]) {
						$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=joinchannel&id=".$data[id]."'>Admin Join</a></td>";
				} else {
						$GLOBALS[html] .= "<td>Not authorized</td>";
				}


				$GLOBALS[html] .= "</tr>";
			}
			$GLOBALS[html] .= "</table>";
		break;

		default:
			$mymsgs = getMyChatMessages();
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."'>View Messages</a> | <a href='?module=".$_POST[module]."&myjob=viewchannellist'>List Channels</a> | <a href='?module=".$_POST[module].
												"&myjob=viewcreatechannel'>Create Channel</a></h3>";
			$GLOBALS[html] .= "<br />";
			$GLOBALS[html] .= "<table><tr>";
			foreach ($mymsgs[chan] as $mychan) {
				$GLOBALS[html] .= "<form action='?' method='POST'>";
				$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
				$GLOBALS[html] .= "<input type='hidden' name='myjob' value='chat' />";
				$GLOBALS[html] .= "<input type='hidden' name='channel' value='".$mychan[id]."' />";
				$GLOBALS[html] .= "<td valign='bottom'>";
				$GLOBALS[html] .= "<b>".$mychan[name]."</b><br />";
				foreach ($mymsgs[msg][$mychan[id]] as $mymsg) {
					$GLOBALS[html] .= $mymsg[sender].": ".$mymsg[msg]."<br />";
				}

				$GLOBALS[html] .= "<input type='text' name='chat' value='' size='20'/>";
				$GLOBALS[html] .= "<input type='submit' name='submit' value='ok' />";

				$GLOBALS[html] .= "</td>";
				$GLOBALS[html] .= "</form>";
			}
			$GLOBALS[html] .= "</tr></table>";


	}
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
