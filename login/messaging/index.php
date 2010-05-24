<?php
require_once('./'.$GLOBALS[cfg][moduledir].'/'.$_POST[module].'/inc/conf.inc.php');

function encodeme($me) {
return utf8_encode(mysql_real_escape_string(str_replace('&', '&amp;', $me)));
}

#only load as module?
if ($_SESSION[loggedin] == 1) {

#helper functions

#fetching users from openid
$sqls = mysql_query("SELECT openid,timestamp FROM oom_openid_lastonline WHERE 1;");
while ($rows = mysql_fetch_array($sqls)) {
$GLOBALS[module][$rows[openid]][smf] = $rows[openid];
$GLOBALS[module][$rows[openid]][online] = $rows[timestamp];
}

#fetching users from smf
$sqls = mysql_query("SELECT member_name,openid_uri FROM smf_members WHERE openid_uri<>'';");
while ($rows = mysql_fetch_array($sqls)) {
$GLOBALS[module][$rows[openid_uri]][smf] = $rows[member_name];
$GLOBALS[module][$rows[openid_uri]][name] = $rows[openid_uri];
}


#send message -> functions
if ($_POST[myjob] == "sendmessage") {
$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][module][tablename]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
$_SESSION[openid_identifier]."', '".$_POST[user]."', '".time()."', '".encodeme($_POST[subject]).
"', '".encodeme($_POST[message])."', '1', '1');");
$GLOBALS[html] .= "<h3>Message to ".$_POST[user]." sent!</h3>";
$GLOBALS[myreturn][msg] = "sent"; #FIXME ok check (error/sent)
}

#delete message -> functions
elseif ($_POST[myjob] == "deletemessage") {
$cbool = 0;
$csql = mysql_query("SELECT receiver FROM ".$GLOBALS[cfg][module][tablename]." WHERE id='".$_POST[id]."';");
while ($crow = mysql_fetch_array($csql))
if ($crow[receiver] == $_SESSION[openid_identifier])
$cbool = 1;

if ($cbool) {
$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][module][tablename]." WHERE id='".$_POST[id]."';");
$GLOBALS[html] .= "<h3>Message deleted!</h3>";
$GLOBALS[myreturn][msg] = "deleted"; #FIXME ok check (error/sent)
} else {
$GLOBALS[html] .= "<h3>Nice try .. Message NOT deleted, since it's not yours!!</h3>";
$GLOBALS[myreturn][msg] = "error";
}
}
#setup xmpp
elseif ($_POST[myjob] == "setupxmpp") {
$cbool = 0;
$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][module][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
while ($crow = mysql_fetch_array($csql)) {
$cbool = 1;
$tmpname = $crow[xmpp];
}

if ($cbool) {
$sql = mysql_query("UPDATE ".$GLOBALS[cfg][module][xmpptable]." SET xmpp='".strtolower($_POST[user]).
"' WHERE openid='".$_SESSION[openid_identifier]."';");

} else {
$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][module][xmpptable]." (openid,xmpp) VALUES ('".
$_SESSION[openid_identifier]."', '".strtolower($_POST[user])."');");
}
$GLOBALS[html] .= "<h3>XMPP Setting updated!!</h3>";

}

#init stuff

#draw user table

if ($_POST[myjob] == "composemessage") {
$GLOBALS[html] .= "<h2>New Message</h2>";
$GLOBALS[html] .= "<form action='?' method='POST'>";
$GLOBALS[html] .= "<input type='hidden' name='myjob' value='sendmessage' />";
$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
$GLOBALS[html] .= "TO: ".drawUsersDropdown($GLOBALS[module][all][$_POST[user]])."<br />";;
$GLOBALS[html] .= "Subject: <input type='text' size='50' name='subject' /><br />";
$GLOBALS[html] .= "Message:<br /><textarea name='message' cols='50' rows='5' >Message</textarea><br />";
$GLOBALS[html] .= "<input type='submit' name='submit' value='submit' />";
$GLOBALS[html] .= "</form>";


} elseif ($_POST[myjob] == "setupxmppform") {
$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][module][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
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


} elseif ($_POST[myjob] == "readmessage") {
$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][module][tablename].
" WHERE id='".$_POST[id]."' AND receiver='".$_SESSION[openid_identifier]."' ORDER BY timestamp DESC;");
while ($row = mysql_fetch_array($sql)) {
if (($row[sender] == $_SESSION[openid_identifier]) OR ($row[receiver] == $_SESSION[openid_identifier])) {

# if (empty($GLOBALS[module][$row[sender]][smf]))
# $sender = $row[sender];
# else
$sender = $GLOBALS[module][$row[sender]][smf];

# if (empty($GLOBALS[module][$row[receiver]][smf]))
# $receiver = $row[receiver];
# else
$receiver = $GLOBALS[module][$row[receiver]][smf];

$GLOBALS[myreturn][message][id] = $row[id];
$GLOBALS[myreturn][message][sender] = $sender;
$GLOBALS[myreturn][message][receiver] = $receiver;
$GLOBALS[myreturn][message][sendername] = $GLOBALS[module][$row[sender]][name];
$GLOBALS[myreturn][message][age] = getAge($row[timestamp]);
$GLOBALS[myreturn][message][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);
$GLOBALS[myreturn][message][subject] = utf8_decode($row[subject]);
$GLOBALS[myreturn][message][message] = utf8_decode($row[message]);

if ($row[receiver] == $_SESSION[openid_identifier])
$sqlr = mysql_query("UPDATE ".$GLOBALS[cfg][module][tablename]." SET new='0', xmpp='0' WHERE id='".$row[id]."';");

}
}

$GLOBALS[html] .= "<h2>Read Message</h2>";
$GLOBALS[html] .= "From: ".genMsgUrl($sender)."<br />";
$GLOBALS[html] .= "To: ".genMsgUrl($receiver)."<br />";
$GLOBALS[html] .= "Date: ".$GLOBALS[myreturn][message][date]."<br />";
$GLOBALS[html] .= "Age: ".$GLOBALS[myreturn][message][age]."<br /><br />";
$GLOBALS[html] .= "<b>Subject: ".$GLOBALS[myreturn][message][subject]."</b>";
$GLOBALS[html] .= "<pre>".$GLOBALS[myreturn][message][message]."</pre><br />";

} elseif ($_POST[myjob] == "getusers") {
$cnt = 0;
foreach ($GLOBALS[module] as $myuser) {
$GLOBALS[myreturn][users][$cnt][name] = $myuser[smf];
$GLOBALS[myreturn][users][$cnt][openid] = $myuser[name];
$cnt++;
}
} else {
$GLOBALS[] .= "<hr>";

$GLOBALS[html] .= "<h3>List Messages | <a href='?module=".$_POST[module]."&myjob=composemessage'>Compose Message</a>";
$GLOBALS[html] .= " | <a href='?module=".$_POST[module]."&myjob=setupxmppform'>Setup Jabber Traversal (XMPP)</a></h3>";
$GLOBALS[html] .= "<table width='100%' class='tablesorter'>";
$cnt = 0; $ncnt = 0;
$GLOBALS[html] .= "<tr><th>From</th><th>To</th><th>Subject</th><th>Message</th><th>Time</th><th>Delete</th></tr>";
$sql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message,new FROM ".$GLOBALS[cfg][module][tablename].
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
$GLOBALS[myreturn][messages][$cnt][mine] = 1;
if ($row['new'] == "1") $ncnt++;
} else {
$GLOBALS[myreturn][messages][$cnt][mine] = 0;
}

$GLOBALS[myreturn][messages][$cnt][id] = $row[id];
$GLOBALS[myreturn][messages][$cnt][age] = getAge($row[timestamp]);
$GLOBALS[myreturn][messages][$cnt][receiver] = $receiver;
$GLOBALS[myreturn][messages][$cnt][sender] = $sender;
$GLOBALS[myreturn][messages][$cnt][subject] = utf8_decode($row[subject]);
$GLOBALS[myreturn][messages][$cnt][message] = utf8_decode(substr($row[message], 0, 40));
$GLOBALS[myreturn][messages][$cnt]['new'] = $row['new'];
$GLOBALS[myreturn][messages][$cnt][date] = strftime($GLOBALS[cfg][strftime], $row[timestamp]);

if (($row[receiver] == $_SESSION[openid_identifier]) AND ($row['new'] == "1")) $tmp = "color: lime;";
else $tmp = "";


$GLOBALS[html] .= "<tr style='".$tmp."'>";
$GLOBALS[html] .= "<td>".genMsgUrl($sender)." | ".$GLOBALS[myreturn][messages][$cnt][age]."</td>";
$GLOBALS[html] .= "<td>".$receiver."</td>";
$GLOBALS[html] .= "<td>".$GLOBALS[myreturn][messages][$cnt][subject]."</td>";


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
} else {
$GLOBALS[html] .= "<td></td>";
}

$GLOBALS[html] .= "</tr>";

$cnt++;
}
$GLOBALS[html] .= "</table>";

$GLOBALS[myreturn][newmessages] = $ncnt;
}
} else {
$GLOBALS[html] .= "<b>= You are not logged in!</b>";
}

?>
