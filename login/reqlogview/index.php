<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_SESSION[isdev] == 1) {
		#init stuff

    if ($_POST[myjob] == "clearmsgs") {
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][requestlogtable]." WHERE 1;");
			sysmsg ("Request Log Cleared", 1);
		}	

		$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=clearmsgs'>&gt; Clear Request Log</a></h3><br />";

		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>Timestamp</th><th>Request</th><th>Query</th><th>Script</th><th>IP</th><th>Referer</th><th>Post</th><th>Get</th></tr>";
		$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][requestlogtable]." WHERE 1 ORDER BY ts DESC;");
		while ($row = mysql_fetch_array($sql)) {

			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td>".getNiceAge($row[ts])."</td>";
			$GLOBALS[html] .= "<td>".$row[req]."</td>";
			$GLOBALS[html] .= "<td>".$row[que]."</td>";
			$GLOBALS[html] .= "<td>".$row[scr]."</td>";
			$GLOBALS[html] .= "<td>".$row[ip]."</td>";
			$GLOBALS[html] .= "<td>".$row[ref]."</td>";
			$GLOBALS[html] .= "<td>".$row[post]."</td>";
			$GLOBALS[html] .= "<td>".$row[get]."</td>";
			$GLOBALS[html] .= "</tr>";

		}
		$GLOBALS[html] .= "</table>";

	} else {
		sysmsg ("You are not allowed to use this module!", 0);
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!</b>", 1);
}


?>
