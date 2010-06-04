<?php


#only load as module?
if ($_SESSION[loggedin] == 1) {

### Functions! ###

#send message -> functions
switch ($_POST[myjob]) {

	#send message function
	case "sendmessage":
//		print_r($GLOBALS); exit;
		if (empty($_POST[subject])) {
			if ($_POST[ajax]) {
				$_POST[subject] = "AJAX GUI";
			} else {
				$_POST[subject] = "Unknown Source!";
			}
		}
		$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
						$_SESSION[openid_identifier]."', '".$_POST[user]."', '".time()."', '".encodeme($_POST[subject]).
						"', '".encodeme($_POST[message])."', '1', '1');");
		if ($sql) {
			sysmsg ("Message to ".$_POST[user]." sent!");
			$GLOBALS[myreturn][msg] = "sent";
		} else {
			sysmsg ("Message to ".$_POST[user]." NOT sent!", 1);
			$GLOBALS[myreturn][msg] = "notsent";
		}
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#admin mass mailer
	case "massmail":
		fetchUsers();
		if ($_SESSION[isadmin]) {
			foreach ($GLOBALS[users][byuri] as $myuri)
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
								$_SESSION[openid_identifier]."', '".$myuri[uri]."', '".time()."', '".encodeme($_POST[subject]).
								"', '".encodeme($_POST[message])."', '1', '1');");

#			if ($sql) {
				sysmsg ("Message to everyone sent!", 1);
				$GLOBALS[myreturn][msg] = "sent";
#			} else {
#				$GLOBALS[html] .= "<h3>Message to everyone NOT sent!</h3>";
#				$GLOBALS[myreturn][msg] = "notsent";
#			}
			
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
			sysmsg ("Message deleted!", 1);
			$GLOBALS[myreturn][msg] = "deleted";
		} else {
			sysmsg ("Nice try .. Message NOT deleted, since it's not yours!!", 0);
			$GLOBALS[myreturn][msg] = "error";
		}
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#delete all message -> functions
	case "deleteallmessage":
		$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE receiver='".$_SESSION[openid_identifier]."';");
		if ($sql) {
			sysmsg ("All your messages where deleted!", 1);
			$GLOBALS[myreturn][msg] = "alldeleted";
		} else {
			sysmsg ("All your messages where NOT deleted!", 1);
			$GLOBALS[myreturn][msg] = "allnotdeleted";
		}
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#mark all viewed -> functions
	case "allviewed":
		$sql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][msgtable]." SET new='0' WHERE receiver='".$_SESSION[openid_identifier]."';");

		if ($sql) {
			sysmsg ("All your messages are now read!", 1);
			$GLOBALS[myreturn][msg] = "allviewed";
		} else {
			sysmsg ("Your messages are NOT marked read!", 1);
			$GLOBALS[myreturn][msg] = "notallviewed";
		}


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
		sysmsg ("XMPP Setting updated!", 1);
		updateTimestamp($_SESSION[openid_identifier]);
	break;

	#set user status
	case "setstatus":
		$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='".
    	    	$_POST[status]."' WHERE openid='".$_SESSION[openid_identifier]."';");
		sysmsg ("Userststus set");
	break;

	#get user status
	case "getstatus":
		$bool = 0;
		$sql = mysql_query("SELECT status FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE openid='".$_POST[name]."';");
		while ($row = mysql_fetch_array($sql)) {
			$GLOBALS[myreturn][status] = $row[status];
			$bool = 1;
		}
		if ($bool)
			$GLOBALS[myreturn][msg] = "on";
		else
			$GLOBALS[myreturn][msg] = "nok";
		
		sysmsg ("Fetched User status from ".$_POST[name]);
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

		$GLOBALS[html] .= "Hier kannst du deine Jabberid mit dem Alptr&ouml;im Messaging verbinden. Wenn du dies machst, erh&auml;lst du alle Nachrichten auf deinem Instant Messanger ohne auf der Homepage online zu sein. Du kannst auch Nachrichten von deinem Instant Messanger aus an die Benutzer der Homepage senden.<br />";
		$GLOBALS[html] .= "<br />";
		$GLOBALS[html] .= "<b>Infos:</b><br /><ul>";
		$GLOBALS[html] .= "<li>Google Talk (Google): Einfach deine gtalk Adresse. (Meistens wie die Email)</li>";
		$GLOBALS[html] .= "<li><a href='http://www.swissjabber.ch'>Swisjabber</a> Baispiel: willhelm@swissjabber.ch</li>";
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



	case "readmessages":
		fetchUsers();
#		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
#					" WHERE (receiver='".$_SESSION[openid_identifier]."' AND sender='".$GLOBALS[users][byname][strtolower($_POST[name])].
#					"') OR (receiver='".$GLOBALS[users][byname][strtolower($_POST[name])]."' AND sender='".$_SESSION[openid_identifier].
#					"') ORDER BY timestamp DESC LIMIT 0,15;");
		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
					" WHERE (receiver='".$_SESSION[openid_identifier]."' AND sender='".$_POST[name].
					"') OR (receiver='".$_POST[name]."' AND sender='".$_SESSION[openid_identifier].
					"') ORDER BY timestamp DESC LIMIT 0,15;");
		$cnt = 0; $bool = 0;
		while ($row = mysql_fetch_array($sql)) {
	#			$GLOBALS[myreturn][message][$cnt][sender] = $row[sender];
	#			$GLOBALS[myreturn][message][$cnt][receiver] = $row[receiver];
	#			$GLOBALS[myreturn][message][$cnt][sendername] = $GLOBALS[users][byuri][$row[sender]][name];
	#			$GLOBALS[myreturn][message][$cnt][receivername] = $GLOBALS[users][byuri][$row[receiver]][name];
	#			$GLOBALS[myreturn][message][$cnt][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);
	#			$GLOBALS[myreturn][message][$cnt][subject] = $row[subject];

				if ($row['new'] == 1) {
					if (($row[receiver] == $_SESSION[openid_identifier]) AND ($row[timestamp] < (time() - 10)))
						$sql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][msgtable]." SET new='0' WHERE id='".$row[id]."';");
					$new = 1;
				} else
					$new = 0;


				if ($new)	$mynewreturn  = "<b>";
				else			$mynewreturn  = "";

				$mynewreturn .= 						$GLOBALS[users][byuri][$row[sender]][name]." ".getNiceAge($row[timestamp]);

				if ($new)	$mynewreturn .= ":</b> ";
				else			$mynewreturn .= ": <i>";

				$mynewreturn .= 						str_replace("\n", "<br />", $row[message]);

				if ($new)	$mynewreturn .= "";
				else			$mynewreturn .= "</i>";


				$GLOBALS[myreturn][message][$cnt][id] = $row[id];
				$GLOBALS[myreturn][message][$cnt][msg] = $mynewreturn;

				$cnt++;
				$bool = 1;
		}
		if ($bool) {
			$GLOBALS[myreturn][msg] = "ok";
		} else {
			$GLOBALS[myreturn][msg] = "nok";
		}
		$GLOBALS[myreturn][message] = array_reverse($GLOBALS[myreturn][message]);

		updateTimestamp($_SESSION[openid_identifier]);
	break;


	#list messages for ajax
	case "list":
	#		$GLOBALS[myreturn][messages][$cnt][id] = $row[id];
#			$GLOBALS[myreturn][messages][$cnt][sender] = $row[sender];
#			$GLOBALS[myreturn][messages][$cnt][receiver] = $row[receiver];
	#		$GLOBALS[myreturn][messages][$cnt][sendername] = $GLOBALS[users][byuri][$row[sender]][name];
#			$GLOBALS[myreturn][messages][$cnt][receivername] = $GLOBALS[users][byuri][$row[receiver]][name];
#			$GLOBALS[myreturn][messages][$cnt][age] = getAge($row[timestamp]);
#			$GLOBALS[myreturn][messages][$cnt][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);
#			$GLOBALS[myreturn][messages][$cnt][subject] = $row[subject];
	#		$GLOBALS[myreturn][messages][$cnt][message] = $row[message];
	#		$GLOBALS[myreturn][messages][$cnt]['new'] = $row['new'];

#			$tcnt = count($GLOBALS[myreturn][$GLOBALS[users][byuri][$row[sender]][name]]);
#			if (! is_array($GLOBALS[myreturn][messages][$GLOBALS[users][byuri][$row[sender]][name]]))
#				$GLOBALS[myreturn][messages][$GLOBALS[users][byuri][$row[sender]][name]] = array();
#			array_push($GLOBALS[myreturn][messages][$GLOBALS[users][byuri][$row[sender]][name]], $row[message]);
#			$tcnt = count($tmparr[$tmpname]);
#				$tmparr[count($tmparr)][name][$tmpname][count($tmparr[name][$tmpname])] = array("msg", $row[message]));
#				array_push($tmparr[count($tmparr)][name][$tmpname][count($tmparr[name][$tmpname])], array("msg", $row[message]));
#				$tmparr[name][count($tmparr)][$tmpname][count($tmparr[name][$tmpname])][count($tmparr[name][$tmpname][msg])][msg] = $row[message];

		fetchUsers();
		$tmpuser = ""; $tcnt = 0; $ncnt = 0; $bool = 0;	$tarray = array(); $farray = array();
		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
					" WHERE receiver='".$_SESSION[openid_identifier]."' ORDER BY sender DESC, timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {
			$tmpname = $GLOBALS[users][byuri][$row[sender]][name];

			if ($row['new'] == "1") $ncnt++;

			if ($tmpuser != $tmpname) {
				$tcnt++;
//				$tmparr[$tcnt][namea] = $tmpname;
				$tmpuser = $tmpname;
			}
/*			if ($tmpuser != $tmpname) {
				$tmpuser = $tmpname;

				if ($bool) {
					array_push($farray, $tarray);
					unset($tarray);
					$tarray = array();
				}
				$bool = 1;
			}
			array_push($tarray, array("msg", $row[message]));
*/
			$tmparr[$tcnt][namea][$tmpname][count($tmparr[$tcnt][$tmpname])][msg] = $row[message];
//			$tmparr[count($tmparr)][$tmpname][count($tmparr[$tmpname][msg])][msg] = $row[message];

//				$tmparr[name][count($tmparr)][$tmpname][count($tmparr[name][$tmpname])][msg] = $tmpname;

#				$tmparr[name][$tmpname][count($tmparr[name][$tmpname])] = $row[message];
	}
	$GLOBALS[myreturn][messages] = $tmparr;
	$GLOBALS[myreturn][newmsgs] = $ncnt;

	break;

	case "getusers":
		#return user:online status   0 off, 1 on, 2 idle
		fetchUsers();
		$cnt = 1;
		foreach ($GLOBALS[users][byuri] as $myuser) {
			$GLOBALS[myreturn][users][$cnt][name] = $myuser[name];
			$GLOBALS[myreturn][users][$cnt][openid] = $myuser[uri];
#			$GLOBALS[myreturn][users][$myuser[name]] = $myuser[uri];
			$cnt++;
		}
	break;

	case "getopenid":
		fetchUsers();
		$GLOBALS[myreturn][openid] = $GLOBALS[users][byname][strtolower($_POST[name])];
	break;

	case "getname":
		fetchUsers();
		$GLOBALS[myreturn][name] = $GLOBALS[users][byname][$_POST[openid]];
	break;


	default:
		fetchUsers();
		$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."'>List Messages</a> | <a href='?module=".$_POST[module]."&myjob=composemessage'>Compose Message</a>";
		$GLOBALS[html] .= " | <a href='?module=".$_POST[module]."&myjob=setupxmppform'>Setup Jabber Traversal (XMPP)</a></h3>";
		$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
		$cnt = 0; $ncnt = 0; $GLOBALS[jsonobject] = 0;
		$GLOBALS[html] .= "<tr><th>From</th><th>Age</th><th>Message</th><th>Time</th><th>Delete</th></tr>";
		$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][msg][msgtable].
					" WHERE receiver='".$_SESSION[openid_identifier]."' ORDER BY timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {

			if ($row['new'] == "1") $ncnt++;

			if (($row[receiver] == $_SESSION[openid_identifier]) AND ($row['new'] == "1")) $tmp = "color: lime; ";
			else $tmp = "";

			$GLOBALS[html] .= "<tr style='".$tmp."'>";
			$GLOBALS[html] .= "<td style='vertical-align: top;'>".genMsgUrl($row[sender])."</td>";
			$GLOBALS[html] .= "<td style='vertical-align: top;'>".getAge($row[timestamp])."</td>";

			$GLOBALS[html] .= "<td style='vertical-align: top;'>";
				$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&myjob=readmessage&id=".$row[id]."'>";
			$GLOBALS[html] .= str_replace("\n", "<br />", $row[message]);
				$GLOBALS[html] .= "</a>";
			$GLOBALS[html] .= "</td>";

			$GLOBALS[html] .= "<td style='vertical-align: top;'>".strftime($GLOBALS[cfg][strftime], $row[timestamp])."</td>";

			if ($row[receiver] == $_SESSION[openid_identifier]) {
				$GLOBALS[html] .= "<td style='vertical-align: top;'><a href='?module=".$_POST[module]."&myjob=deletemessage&id=".
				$row[id]."'>Delete</a></td>";
			} else $GLOBALS[html] .= "<td>&nbsp;</td>";

			$GLOBALS[html] .= "</tr>";

			$cnt++;
		}
		$GLOBALS[html] .= "</table>";

		$GLOBALS[html] .= "<center><a href='?module=".$_POST[module]."&myjob=allviewed'>Mark all messages as read.</a>  |";  
		$GLOBALS[html] .= " <a href='?module=".$_POST[module]."&myjob=deleteallmessage'>Delete all messages (!)</a></center>";

		$GLOBALS[myreturn][newmessages] = $ncnt;

		if ($_SESSION[isadmin]) {
			$GLOBALS[html] .= "<br /><br /><hr /><br /><h3>Admin mass mailer</h3>";
			$GLOBALS[html] .= "<form action='?' method='POST'>";
			$GLOBALS[html] .= "<input type='hidden' name='myjob' value='massmail' />";
			$GLOBALS[html] .= "<input type='hidden' name='subject' value='MASS/HTML GUI' />";
			$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
			$GLOBALS[html] .= "<textarea name='message' cols='50' rows='5'></textarea><br />";
			$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
			$GLOBALS[html] .= "</form>";			
		}

//		updateTimestamp($_SESSION[openid_identifier]);
	break;
}
} else {
	sysmsg ("You are not logged in!", 1);
}

?>
