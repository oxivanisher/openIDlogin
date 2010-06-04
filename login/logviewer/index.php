<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	if ($_SESSION[isdev] == 1) {
		#init stuff
		fetchUsers();

		$alert[0] = "ERROR";
		$alert[1] = "WARNING";
		$alert[2] = "INFO";

		#clear request log
		if ($_POST[myjob] == "clearreqlog") {
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][requestlogtable]." WHERE 1;");
			sysmsg ("Request Log Cleared", 1);
		}	

		#clear system messages
		if ($_POST[myjob] == "clearmsgs") {
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1;");
			sysmsg ("System Messages Cleared", 1);
		}

		#view request log
		if ($_POST[myjob] == "viewreqlog") {
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewreqlog'>= Refresh Request Log</a></h3>";
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=clearreqlog'>&gt; Clear Request Log</a></h3><br />";

			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><th>Timestamp</th><th>Referer</th><th>Input</th><th>Output</th></tr>";
			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][requestlogtable]." WHERE 1 ORDER BY ts DESC;");
			while ($row = mysql_fetch_array($sql)) {

				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td style='vertical-align: top;'>".getNiceAge($row[ts])."</td>";
				$GLOBALS[html] .= "<td style='vertical-align: top;'>".$row[ref]."</td>";
				$GLOBALS[html] .= "<td style='vertical-align: top;'>".str_replace(",", ", ", $row[input])."</td>";
				$GLOBALS[html] .= "<td style='vertical-align: top;'>".str_replace(",", ", ", $row[output])."</td>";
				$GLOBALS[html] .= "</tr>";

			}
			$GLOBALS[html] .= "</table>";

		#view system messages
		} elseif ($_POST[myjob] == "viewsysmsgs") {

			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewsysmsgs'>= Refresh System Messages</a></h3>";
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=clearmsgs'>&gt; Clear System Messages</a></h3><br />";
			$GLOBALS[html] .= "<table>";
			$GLOBALS[html] .= "<tr><th>LVL</th><th>User</th><th>Module</th><th>Message</th><th>IP</th></tr>";
			$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1 ORDER BY timestamp DESC;");
			while ($row = mysql_fetch_array($sql)) {
				$GLOBALS[html] .= "<tr>";
				$GLOBALS[html] .= "<td>".$alert[$row[lvl]]."</td>";
				$GLOBALS[html] .= "<td>".$GLOBALS[users][byuri][$row[user]][name]." ".getNiceAge($row[timestamp])."</td>";
				$GLOBALS[html] .= "<td>".$row[module]."</td>";
				$GLOBALS[html] .= "<td>".$row[msg]."</td>";
				$GLOBALS[html] .= "<td>".$row[ip]."</td>";
	
				$GLOBALS[html] .= "</tr>";
			}
			$GLOBALS[html] .= "</table>";

		#default view
		} else {
			$GLOBALS[html] .= "<br />";
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewsysmsgs'>View System Messages</a></h3><br />";
			$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&myjob=viewreqlog'>View Request Log</a></h3><br />";
		}
	} else {
		sysmsg ("You are not allowed to use this module!", 0);
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!</b>", 1);
}

?>
