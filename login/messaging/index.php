<?php


#only load as module?
if ($_SESSION[loggedin] == 1) {

### Functions! ###

#send message -> functions
switch ($_POST[myjob]) {

	#send message function
	case "sendmessage":
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
						$_SESSION[openid_identifier]."', '".$_POST[user]."', '".time()."', '".encodeme($_POST[subject]).
						"', '".encodeme($_POST[message])."', '1', '1');");
		$GLOBALS[html] .= "<h3>Message to ".$_POST[user]." sent!</h3>";
		$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#admin mass mailer
	case "massmail":
		if ($_SESSION[isadmin]) {
			foreach ($GLOBALS[users][byuri] as $myuri)
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
								$_SESSION[openid_identifier]."', '".$myuri[uri]."', '".time()."', '".encodeme($_POST[subject]).
								"', '".encodeme($_POST[message])."', '1', '1');");

			$GLOBALS[html] .= "<h3>Message sent to everyone!</h3>";
			$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
			updateTimestamp($_SESSION[openid_identifier]);
		}
	break;

	#delete message -> functions
	case "deletemessage":
		$cbool = 0;
		$csql = mysql_query("SELECT receiver FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE id='".$_POST[id]."';");
		while ($crow = mysql_fetch_array($csql))
			if ($crow[receiver] == $_SESSION[openid_identifier])
				$cbool = 1;

		if ($cbool) {
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE id='".$_POST[id]."';");
			$GLOBALS[html] .= "<h3>Message deleted!</h3>";
			$GLOBALS[myreturn][msg] = "deleted"; #FIXME ok check (error/sent)
		} else {
			$GLOBALS[html] .= "<h3>Nice try .. Message NOT deleted, since it's not yours!!</h3>";
		$GLOBALS[myreturn][msg] = "error";
		}
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#delete all message -> functions
	case "deleteallmessage":
		$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE receiver='".$_SESSION[openid_identifier]."';");
		$GLOBALS[html] .= "<h3>All your messages where deleted!</h3>";
		$GLOBALS[myreturn][msg] = "alldeleted"; #FIXME ok check (error/sent)
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#mark all viewed -> functions
	case "allviewed":
		$sql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][msgtable]." SET new='0' WHERE receiver='".$_SESSION[openid_identifier]."';");
		$GLOBALS[html] .= "<h3>All your messages are now read!</h3>";
		$GLOBALS[myreturn][msg] = "allviewed"; #FIXME ok check (error/sent)
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#setup xmpp
	case "setupxmpp":
		$cbool = 0;
		$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][msg][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		while ($crow = mysql_fetch_array($csql)) {
			$cbool = 1;
			$tmpname = $crow[xmpp];
		}

		if ($cbool) {
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][xmpptable]." SET xmpp='".strtolower($_POST[user]).
						"' WHERE openid='".$_SESSION[openid_identifier]."';");
		} else {
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][xmpptable]." (openid,xmpp) VALUES ('".
						$_SESSION[openid_identifier]."', '".strtolower($_POST[user])."');");
		}
		$GLOBALS[html] .= "<h3>XMPP Setting updated!!</h3>";
		updateTimestamp($_SESSION[openid_identifier]);
	break;
}
#init stuff

#draw user table

switch ($_POST[myjob]) {
	case "composemessage":
		$GLOBALS[html] .= "<h2>New Message</h2>";
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='sendmessage' />";
		$GLOBALS[html] .= "<input type='hidden' name='subject' value='HTML GUI' />";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "TO: ".drawUsersDropdown($_POST[user])."<br />";;
		$GLOBALS[html] .= "Message:<br /><textarea name='message' cols='50' rows='5'></textarea><br />";
		$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
		$GLOBALS[html] .= "</form>";
	break;

	case "setupxmppform":
		$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][msg][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		while ($crow = mysql_fetch_array($csql))
			$tmpname = $crow[xmpp];
		$GLOBALS[html] .= "<h2>Setup Jabber Traversal (XMPP)</h2>";

		$GLOBALS[html] .= "Hier kannst du deine Jabberid mit dem Alptr&ouml;im Messaging verbinden. Wenn du dies machst, erh&auml;lst du alle Nachrichten auf deinem Instant Messanger ohne auf der Homepage online zu sein.<br />";
		$GLOBALS[html] .= "<br />";
		$GLOBALS[html] .= "<b>Infos:</b><br /><ul>";
		$GLOBALS[html] .= "<li>Google Talk (Google): Einfach deine gtalk Adresse. (Meistens wie die Email)</li>";
		$GLOBALS[html] .= "<li>Swisjabber z.B. willhelm@swissjabber.ch</li>";
		$GLOBALS[html] .= "<li>Alle Jabber Adressen sehen aus wie Emailadresen.</li>";
		$GLOBALS[html] .= "<li>XMPP ist der Name des Protokolles von Jabber. Die 'Sprache' die die Server miteinander sprechen.</li>";
		$GLOBALS[html] .= "<li><a href='http://www.ulm.ccc.de/~marcel/warum-jabber.htm' target='new'>Warum Jabber besser ist als ICQ/MSN/AIM/Y!</a></li>";
		$GLOBALS[html] .= "<li>OOM Hat auch Einen Jabber Server. Das heisst bei Interesse, k&ouml;nnen wir einen @alptroeim.ch Jabber Server einrichten.</li>";
		$GLOBALS[html] .= "<li>Der Gesammte Facebook Chat funktioniert mit Jabber / XMPP. Sie benutzen sogar die <a href='http://www.process-one.net/en/blogs/article/facebook_chat_supports_xmpp_with_ejabberd/' target='new'>gleiche</a> Serversofware (<a href='http://www.ejabberd.im/' target='new'>ejabberd</a>) wie wir. :)</li>";


		$GLOBALS[html] .= "</ul><br /><br />";
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='setupxmpp' />";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "Deine Jabber Adresse: <input type='text' size='30' name='user' value='".$tmpname."' />";
		$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
		$GLOBALS[html] .= "</form>";
	break;

	case "readmessage":
		fetchUsers();
		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
					" WHERE id='".$_POST[id]."' AND receiver='".$_SESSION[openid_identifier]."' ORDER BY timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {
			if (($row[sender] == $_SESSION[openid_identifier]) OR ($row[receiver] == $_SESSION[openid_identifier])) {

			$GLOBALS[myreturn][message][id] = $row[id];
			$GLOBALS[myreturn][message][sender] = $row[sender];
			$GLOBALS[myreturn][message][receiver] = $row[receiver];
			$GLOBALS[myreturn][message][sendername] = $GLOBALS[users][byuri][$row[sender]][name];
			$GLOBALS[myreturn][message][receivername] = $GLOBALS[users][byuri][$row[receiver]][name];
			$GLOBALS[myreturn][message][age] = getAge($row[timestamp]);
			$GLOBALS[myreturn][message][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);
			$GLOBALS[myreturn][message][subject] = $row[subject];
			$GLOBALS[myreturn][message][message] = $row[message];
		
			if ($row[receiver] == $_SESSION[openid_identifier])
				$sqlr = mysql_query("UPDATE ".$GLOBALS[cfg][msg][msgtable]." SET new='0', xmpp='0' WHERE id='".$row[id]."';");

			}
		}

		$GLOBALS[html] .= "<h2>Read Message</h2>";
		$GLOBALS[html] .= "From: ".genMsgUrl($GLOBALS[myreturn][message][sender])."<br />";
		$GLOBALS[html] .= "To: ".$GLOBALS[myreturn][message][receiver]."<br />";
		$GLOBALS[html] .= "Date: ".$GLOBALS[myreturn][message][date]."<br />";
		$GLOBALS[html] .= "Age: ".$GLOBALS[myreturn][message][age]."<br />";
		$GLOBALS[html] .= "Source: ".$GLOBALS[myreturn][message][subject]."<br />";
		$GLOBALS[html] .= "<big><pre>".$GLOBALS[myreturn][message][message]."</pre></big>";
		$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&myjob=deletemessage&id=".
											$GLOBALS[myreturn][message][id]."'>Delete this message</a>";
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	case "getusers":
		$cnt = 0;
		foreach ($GLOBALS[users][byuri] as $myuser) {
			$GLOBALS[myreturn][users][$cnt][name] = $myuser[uri];
			$GLOBALS[myreturn][users][$cnt][openid] = $myuser[name];
			$cnt++;
		}
	break;

	default:
		$GLOBALS[html] .= "<h3>List Messages | <a href='?module=".$_POST[module]."&myjob=composemessage'>Compose Message</a>";
		$GLOBALS[html] .= " | <a href='?module=".$_POST[module]."&myjob=setupxmppform'>Setup Jabber Traversal (XMPP)</a></h3>";
		$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
		$cnt = 0; $ncnt = 0;
		$GLOBALS[html] .= "<tr><th>From</th><th>Age</th><th>Message</th><th>Time</th><th>Delete</th></tr>";
		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
					" WHERE receiver='".$_SESSION[openid_identifier]."' ORDER BY timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {


			$GLOBALS[myreturn][messages][$cnt][id] = $row[id];
			$GLOBALS[myreturn][messages][$cnt][sender] = $row[sender];
			$GLOBALS[myreturn][messages][$cnt][receiver] = $row[receiver];
			$GLOBALS[myreturn][messages][$cnt][sendername] = $GLOBALS[users][byuri][$row[sender]][name];
			$GLOBALS[myreturn][messages][$cnt][receivername] = $GLOBALS[users][byuri][$row[receiver]][name];
			$GLOBALS[myreturn][messages][$cnt][age] = getAge($row[timestamp]);
			$GLOBALS[myreturn][messages][$cnt][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);
			$GLOBALS[myreturn][messages][$cnt][subject] = $row[subject];
			$GLOBALS[myreturn][messages][$cnt][message] = $row[message];
			$GLOBALS[myreturn][messages][$cnt]['new'] = $row['new'];

			if ($row[receiver] == $_SESSION[openid_identifier]) {
				$GLOBALS[myreturn][messages][$cnt][mine] = 1;
				if ($row['new'] == "1") $ncnt++;
			} else $GLOBALS[myreturn][messages][$cnt][mine] = 0;

			if (($row[receiver] == $_SESSION[openid_identifier]) AND ($row['new'] == "1")) $tmp = "color: lime;";
			else $tmp = "";

			$GLOBALS[html] .= "<tr style='".$tmp."'>";
			$GLOBALS[html] .= "<td>".genMsgUrl($GLOBALS[myreturn][messages][$cnt][sender])."</td>";
			$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][messages][$cnt][age]."</td>";

			$GLOBALS[html] .= "<td>";
			if ($GLOBALS[myreturn][messages][$cnt][mine])
				$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&myjob=readmessage&id=".$GLOBALS[myreturn][messages][$cnt][id]."'>";
			$GLOBALS[html] .= $GLOBALS[myreturn][messages][$cnt][message];
			if ($GLOBALS[myreturn][messages][$cnt][mine])
				$GLOBALS[html] .= "</a>";
			$GLOBALS[html] .= "</td>";

			$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][messages][$cnt][date]."</td>";

			if ($row[receiver] == $_SESSION[openid_identifier]) {
				$GLOBALS[html] .= "<td><a href='?module=".$_POST[module]."&myjob=deletemessage&id=".
				$GLOBALS[myreturn][messages][$cnt][id]."'>Delete</a></td>";
			} else $GLOBALS[html] .= "<td></td>";

			$GLOBALS[html] .= "</tr>";

			$cnt++;
		}
		$GLOBALS[html] .= "</table>";

		$GLOBALS[html] .= "<center><a href='?module=".$_POST[module]."&myjob=allviewed'>Mark all messages as read.</a>  |";  
		$GLOBALS[html] .= " <a href='?module=".$_POST[module]."&myjob=deleteallmessage'>Delete all messages (!)</a></center>";

		$GLOBALS[myreturn][newmessages] = $ncnt;

		if ($_SESSION[isadmin]) {
			$GLOBALS[html] .= "<h3>Admin mass mailer</h3>";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='massmail' />";
			$GLOBALS[html] .= "<input type='hidden' name='subject' value='MASS/HTML GUI' />";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "Message:<br /><textarea name='message' cols='50' rows='5'></textarea><br />";
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";			
		}

		updateTimestamp($_SESSION[openid_identifier]);
	break;
}
} else {
	$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
