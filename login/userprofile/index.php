<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
		#init stuff


		#check for existing entry in table
		$bool = 1;
		$sql = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][userprofiletable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		while ($row = mysql_fetch_array($sql))
			$bool = 0;
		if ($bool)
			$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][userprofiletable]." (openid) VALUES ('".$_SESSION[openid_identifier]."');");


		#function for js frontent to store data
		if ($_POST[myjob] == "safedata") {
			$bool = 0;
			$sql = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE openid='".$_SESSION[openid_identifier]."';");
			$sql = mysql_query("DELETE FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1;");
			while ($row = mysql_fetch_array($sql))
				$bool = 1;

			if ($bool)
				$sql = mysql_query("UPDATE ".$GLOBALS[cfg][systemmsgsdb]." SET data='".$_POST[data]."', timestamp='".time()."';");
			else
				$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][systemmsgsdb]." (openid,timestamp,data) VALUES ('".
														$_SESSION[openid_identifier]."', '".time()."', '".$_POST[data]."');");

			sysmsg ("User Profile Data from JavaScript stored.", 1);

		#function for js frontent to read stored data
		} elseif ($_POST[myjob] == "readdata") {
			$sql = mysql_query("SELECT data FROM ".$GLOBALS[cfg][frontendsafetable]." WHERE openid='".$_SESSION[openid_identifier]."';");
			while ($row = mysql_fetch_array($sql)) {
				$GLOBALS[myreturn][data] = $row[data];
			}
			sysmsg ("User Profile Data from JavaScript read.", 1);


		#update profile
		} elseif ($_POST[myjob] == "updateprofile") {
			$sql = mysql_query("UPDATE ".$GLOBALS[cfg][userprofiletable]." SET nickname='".$_POST[nickname]."', email='".$_POST[email].
													"', surname='".$_POST[surname]."', forename='".$_POST[forename]."', country='".$_POST[country].
													"', postal='".$_POST[postal]."', dob='".$_POST[dob]."' WHERE openid='".$_SESSION[openid_identifier]."';");
			if ($sql)
				sysmsg ("Profile updated", 2);
			else
				sysmsg ("Profile update failed", 2);

		#end functions
		}

		$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userprofiletable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		$GLOBALS[html] .= "<h2>Profil Einstellungen</h2>";
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<form action='?' method='POST'>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."' />";
		$GLOBALS[html] .= "<input type='hidden' name='myjob' value='updateprofile' />";
		while ($row = mysql_fetch_array($sql)) {
			$GLOBALS[html] .= "<tr><td>Nickname</td><td><input type='text' name='nickname' value='".$row[nickname]."' size='20' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>Vorname</td><td><input type='text' name='forename' value='".$row[forename]."' size='20' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>Zuname</td><td><input type='text' name='surname' value='".$row[surname]."' size='20' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>Email</td><td><input type='text' name='email' value='".$row[email]."' size='20' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>Land</td><td><input type='text' name='country' value='".$row[country]."' size='2' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>PLZ</td><td><input type='text' name='postal' value='".$row[postal]."' size='20' /></td></tr>";
			$GLOBALS[html] .= "<tr><td>Date of Birth</td><td><input type='text' name='dob' value='".$row[dob]."' size='10' /></td></tr>";
			$GLOBALS[html] .= "<tr><td colspan='2'>&nbsp;</td></tr>";

			$sqla = mysql_query("SELECT name FROM ".$GLOBALS[cfg][profiletable]." WHERE role='".$row[role]."';");
			while ($rowa = mysql_fetch_array($sqla))
				$GLOBALS[html] .= "<tr><td>Rolle</td><td>".$rowa[name]."</td></tr>";
		}
		$GLOBALS[html] .= "<tr><td>&nbsp;</td><td><input type='submit' name='submit' value='submit' /> <input type='reset' name='reset' value='reset' /></td></tr>";
		$GLOBALS[html] .= "</table>";


	if ($_SESSION[isdev] == 1) {
		$GLOBALS[html] .= "<br /><h2>Dev: Overview saved Data for JavaScript</h2>";
		$GLOBALS[html] .= "<table>";
		$GLOBALS[html] .= "<tr><th>User</th><th>Data</th></tr>";
		$sql = mysql_query("SELECT openid,timestamp,data FROM ".$GLOBALS[cfg][frontendsafetable]." WHERE 1 ORDER BY timestamp DESC;");
		while ($row = mysql_fetch_array($sql)) {
			$GLOBALS[html] .= "<tr>";
			$GLOBALS[html] .= "<td>".$GLOBALS[users][byuri][$row[openid]][name]."</td>";
			$GLOBALS[html] .= "<td>".getNiceAge($row[timestamp])."</td>";
			$GLOBALS[html] .= "<td>".$row[data]."</td>";
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
