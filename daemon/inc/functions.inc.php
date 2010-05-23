<?php

#msg function
function msg ($msg) {
	if ($GLOBALS[debug])
	echo strftime("%a %d %b %H:%M:%S %Y", time())."\t".$msg."\n";
}


#get users from db's
function getUsers() {
	#load users from db (oom_.._xmpp)
	unset ($GLOBALS[xmpp]);
	$sql = mysql_query("SELECT openid,xmpp FROM ".$GLOBALS[cfg][xmpptable]." WHERE 1;");
	while ($row = mysql_fetch_array($sql)) {
		$GLOBALS[xmpp][$row[xmpp]] = $row[openid];
		$GLOBALS[xmpp][$row[openid]] = $row[xmpp];
	}

	#get smf usernames for lookup
	unset ($GLOBALS[tempnames]);
	$smfsql = mysql_query("SELECT member_name,openid_uri FROM smf_members WHERE openid_uri<>'';");
	while ($smfrow = mysql_fetch_array($smfsql)) {
		$GLOBALS[tempnames][$smfrow[openid_uri]] = $smfrow[member_name];
		$GLOBALS[tempnames][strtolower($smfrow[member_name])] = $smfrow[openid_uri];
	}
}



?>
