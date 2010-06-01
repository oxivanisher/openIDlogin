<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_SESSION[isdev] == 1) {
		#init stuff
		fetchUsers();

		$alert[0] = "ERROR";
		$alert[1] = "WARNING";
		$alert[2] = "INFO";

		if ($_POST[myjob] == "clearmsgs") {
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1;");
			sysmsg ("System Messages Cleared", 1);
		}

		$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=clearmsgs'>&gt; Clear System Messages</a></h3><br />";
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>LVL</th><th>User</th><th>Module</th><th>Message</th><th>IP</th><th>Session</th></tr>";
		$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1 ORDER BY timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {
			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td>".$alert[$row[lvl]]."</td>";
			$GLOBALS[html] .= "<td>".$GLOBALS[users][byuri][$row[user]][name]." ".getNiceAge($row[timestamp])."</td>";
			$GLOBALS[html] .= "<td>".$row[module]."</td>";
			$GLOBALS[html] .= "<td>".$row[msg]."</td>";
			$GLOBALS[html] .= "<td>".$row[ip]."</td>";
			$GLOBALS[html] .= "<td>".$row[session]."</td>";

			$GLOBALS[html] .= "</tr>";
		}
		$GLOBALS[html] .= "</table>";
	} else {
		sysmsg ("You are not allowed to use this module!", 0);
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
