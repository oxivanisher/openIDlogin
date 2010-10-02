<?php

#FIXME: on each serialized array, i should also check for not longer existent chat channels!

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_POST[myjob] != "update") fetchUsers();

	#send chat -> functions
	switch($_POST[myjob]) {
		#edit channel -> functions
		case "editchannel":
			$data = getChatChannel($_POST[id]);
			if (($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]) OR ($_SESSION[isadmin])) {
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][chat][channeltable]." SET name='".encodeme($_POST[name])."', allowed='".serialize($_POST[allowed])."' WHERE id='".$_POST[id]."';");
				sysmsg ("Channel ".$_POST[channel]." edited!");
				$GLOBALS[myreturn][msg] = "edited";
			} else {
				sysmsg ("Channel NOT edited!", 1);
				$GLOBALS[myreturn][msg] = "notedited";
			}
		break;

		#create channel -> functions
		case "createchannel":
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][chat][channeltable]." SET owner='".$GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat].
							"', name='".encodeme($_POST[name])."', allowed='".serialize($_POST[allowed])."', created='".time()."';");
			if ($sql) {
				sysmsg ("Channel ".$_POST[name]." created!");
				$GLOBALS[myreturn][msg] = "created";
			} else {
				sysmsg ("Channel ".$_POST[name]." NOT created!");
				$GLOBALS[myreturn][msg] = "notcreated";
			}
		break;

		#delete channel -> functions
		case "deletechannel":
			$data = getChatChannel($_POST[id]);
			if (($data[owner] == $GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat]) OR ($_SESSION[isadmin])) {
				$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][chat][channeltable]." WHERE id='".$_POST[id]."';");
				$swl2 = mysql_query("DELETE FROM ".$GLOBALS[cfg][chat][msgtable]." WHERE channel='".$_POST[id]."';");
				sysmsg ("Channel ".$_POST[id]." and all related messages deleted!");
				$GLOBALS[myreturn][msg] = "deleted"; #FIXME ok check (error/sent)
			} else {
				sysmsg ("Channel ".$_POST[id]." NOT deleted!", 1);
				$GLOBALS[myreturn][msg] = "notdeleted";
			}
		break;

		#join channel -> functions
		case "joinchannel":
			$data = getChatChannel($_POST[id]);
			if ((in_array($GLOBALS[users][byuri][$_SESSION[openid_identifier]][chat], unserialize($data[allowed]))) OR ($_SESSION[isadmin])) {
				array_push($GLOBALS[chat][subscr], $_POST[id]);
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET chatsubscr='".serialize($GLOBALS[chat][subscr])."' WHERE openid='".$_SESSION[openid_identifier]."';");
				sysmsg ("Channel ".$_POST[id]." joined!");
				$GLOBALS[myreturn][msg] = "joined";
			} else {
				sysmsg ("Channel ".$_POST[id]." NOT joined!", 1);
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
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET chatsubscr='".serialize($GLOBALS[chat][subscr])."' WHERE openid='".$_SESSION[openid_identifier]."';");
			sysmsg ("Channel ".$_POST[id]." left!");
			$GLOBALS[myreturn][msg] = "leaved"; #FIXME ok check (error/sent)
		break;

	}

	switch($_POST[myjob]) {
		case "vieweditchannel":
			$data = getChatChannel($_POST[id]);
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewchannellist'>List Channels</a> | <a href='?module=".$_POST[module]."&myjob=viewcreatechannel'>Create Channel</a></h3>";
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
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewchannellist'>List Channels</a> | <a href='?module=".$_POST[module]."&myjob=viewcreatechannel'>Create Channel</a></h3>";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='createchannel' />";
			$GLOBALS[html] .= "Name: <input type='text' name='name' value='".$data[name]."' /><br />";
			$GLOBALS[html] .= "Allowed:<br />".genAllowedCheckbox();
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";
		break;

		default:				#case "viewchannellist":
			#list channels
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewchannellist'>List Channels</a> | <a href='?module=".$_POST[module]."&myjob=viewcreatechannel'>Create Channel</a></h3>";
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
	}
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
