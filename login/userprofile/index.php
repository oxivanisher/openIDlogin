<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
		#init stuff

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
			$alreadyused = 0;
			$sql = "SELECT openid FROM ".$GLOBALS[cfg][userprofiletable]." WHERE nickname LIKE '".$_POST[nickname]."';";
			$sqlr = mysql_query($sql);
			while ($row = mysql_fetch_array($sqlr))
				if ($row[openid] != $_SESSION[openid_identifier])
					$alreadyused = 1;
			if ($alreadyused) {
				$GLOBALS[html] .= "<h2>Fehler! Dieser Name wird bereits verwendet!</h2>";
			} elseif ((! empty($_POST[nickname])) AND 
				(! empty($_POST[email])) AND
				(! empty($_POST[surname])) AND
				(! empty($_POST[forename]))) {

				#armory chars setup
				if ($_POST[chars]) {
					$tnames = explode(",", $_POST[chars]);
					$nnames = array();
					$mybool = false;
					foreach ($tnames as $tname) {
						array_push($nnames, ucfirst(trim($tname)));
						$mybool = true;
					}
					if ($mybool) $rnames = serialize(array_unique($nnames));
					else $rnames = "";
				} else {
					$rnames = "";
				}
				
				#update oom profile db
				$sql = "UPDATE ".$GLOBALS[cfg][userprofiletable]." SET nickname='".$_POST[nickname]."', email='".$_POST[email].
								"', surname='".$_POST[surname]."', forename='".$_POST[forename]."', dob='".$_POST[dob].
								"', mob='".$_POST[mob]."', yob='".$_POST[yob]."', sex='".$_POST[sex]."', icq='".$_POST[icq].
								"', msn='".$_POST[msn]."', skype='".$_POST[skype]."', usertitle='".$_POST[usertitle]."', avatar='".$_POST[avatar].
								"', signature='".$_POST[signature]."', website='".$_POST[website]."', motto='".$_POST[motto].
								"', armorychars='".$rnames."', accurate='1' WHERE openid='".$_SESSION[openid_identifier]."';";
				$sqlq = mysql_query($sql);
				if ($sqlq) {
					$GLOBALS[html] .= "- ";
					sysmsg ("OOM Profile updated", 1);
					$GLOBALS[html] .= "<br />";
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("OOM Profile update failed", 1);
					$GLOBALS[html] .= "<br />";
				}
	
				#smf db
				if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][smf]) {
					$tmpsex = 0;
					if ($tmp[sex] == "M")
						$tmpsex = 1;
					elseif ($tmp[sex] == "F")
						$tmpsex = 2;
					#date not working
					$sql = "UPDATE smf_members SET member_name='".$_POST[nickname]."', real_name='".$_POST[nickname].
									"', email_address='".$_POST[email]."', birthdate='".date($_POST[yob]."-".$_POST[mob]."-".$_POST[dob]).
									"', gender='".$tmpsex."', icq='".$_POST[icq]."', msn='".$_POST[msn]."', Jabber='".$_POST[jid].
									"', usertitle='".$_POST[usertitle]."', avatar='".$_POST[avatar].
									"', signature='".$_POST[signature]."', website_url='".$_POST[website]."', usertitle='".$_POST[motto].
									"' WHERE openid_uri='".$_SESSION[openid_identifier]."';";
					$sqlq = mysql_query($sql);
					if ($sqlq) {
						$GLOBALS[html] .= "- ";
						sysmsg ("SMF Profile updated", 1);
						$GLOBALS[html] .= "<br />";
					} else {
						$GLOBALS[html] .= "- ";
						sysmsg ("SMF Profile update failed", 1);
						$GLOBALS[html] .= "<br />";
					}
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("No SMF Raider Profile found", 1);
					$GLOBALS[html] .= "<br />";
				}
	
				#phpraider
				if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][phpraider]) {
					$sql = "UPDATE phpraider_profile SET username='".$_POST[nickname]."', user_email='".$_POST[email].
									"' WHERE username='".$GLOBALS[users][byuri][$_SESSION[openid_identifier]][name]."';";
					$sqlq = mysql_query($sql);
					if ($sqlq) {
						$GLOBALS[html] .= "- ";
						sysmsg ("PHP Raider Profile updated", 1);
						$GLOBALS[html] .= "<br />";
					} else {
						$GLOBALS[html] .= "- ";
						sysmsg ("PHP Raider Profile update failed", 1);
						$GLOBALS[html] .= "<br />";
					}
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("No PHP Raider Profile found", 1);
					$GLOBALS[html] .= "<br />";
				}
	
				#eqdkp
				if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][eqdkp]) {
					$sql = "UPDATE eqdkp_users SET username='".$_POST[nickname]."', user_email='".$_POST[email].
									"', birthday='".$_POST[dob].".".$_POST[mob].".".$_POST[yob]."', icq='".$_POST[icq]."', msn='".$_POST[msn].
									"' WHERE username='".$GLOBALS[users][byuri][$_SESSION[openid_identifier]][name]."';";
					$sqlq = mysql_query($sql);
					if ($sqlq) {
						$GLOBALS[html] .= "- ";
						sysmsg ("EQDKP Profile updated", 1);
						$GLOBALS[html] .= "<br />";
					} else {
						$GLOBALS[html] .= "- ";
						sysmsg ("EQDKP Profile update failed", 1);
						$GLOBALS[html] .= "<br />";
					}
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("No EQDKP Profile found", 1);
					$GLOBALS[html] .= "<br />";
				}
	
				#wordpress
				if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][wordpress]) {
					$sql = "UPDATE wp_users SET user_login='".$_POST[nickname]."', user_nicename='".$_POST[nickname].
									"', display_name='".$_POST[nickname]."',user_email='".$_POST[email].
									"' WHERE user_url='".$_SESSION[openid_identifier]."';";
					$sqlq = mysql_query($sql);
					if ($sqlq) {
						$GLOBALS[html] .= "- ";
						sysmsg ("Blog Profile updated", 1);
						$GLOBALS[html] .= "<br />";
					} else {
						$GLOBALS[html] .= "- ";
						sysmsg ("Blog Profile update failed", 1);
						$GLOBALS[html] .= "<br />";
					}
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("No Blog Profile found", 1);
					$GLOBALS[html] .= "<br />";
				}
	
				#wiki
				if ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][mediawiki]) {
					$sql = "UPDATE WIKI_user SET user_name='".$_POST[nickname]."', user_real_name='".$_POST[nickname].
									"', user_email='".$_POST[email].
									"' WHERE user_id='".$GLOBALS[users][byuri][$_SESSION[openid_identifier]][mediawiki]."';";
					$sqlq = mysql_query($sql);
					if ($sqlq) {
						$GLOBALS[html] .= "- ";
						sysmsg ("Wiki Profile updated", 1);
						$GLOBALS[html] .= "<br />";
					} else {
						$GLOBALS[html] .= "- ";
						sysmsg ("Wiki Profile update failed", 1);
						$GLOBALS[html] .= "<br />";
					}
				} else {
					$GLOBALS[html] .= "- ";
					sysmsg ("No Wiki Profile found", 1);
					$GLOBALS[html] .= "<br />";
				}

				#jid / xmpp setup
				$cbool = 0;
				$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][msg][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
				while ($crow = mysql_fetch_array($csql)) {
					$cbool = 1;
					$tmpname = $crow[xmpp];
				}
					
				if ($cbool) {
					$sql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][xmpptable]." SET xmpp='".strtolower($_POST[jid]).
									"' WHERE openid='".$_SESSION[openid_identifier]."';");
				} else {
					if ($_POST[jid]) {	
						$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][xmpptable]." (openid,xmpp) VALUES ('".
									$_SESSION[openid_identifier]."', '".strtolower($_POST[jid])."');");
					}
				}
				$GLOBALS[html] .= "- ";
				sysmsg ("XMPP Setting updated!", 1);
				$GLOBALS[html] .= "<br />";
				

		} else {
			$GLOBALS[html] .= "<h2>Profile NICHT aktualisiert. Wichtige Felder wurden nicht ausgef&uuml;llt!</h2>";
		}
		#end functions
		} elseif ($_POST[mydo] == "reloadrole") {
			applyProfile ($_SESSION[openid_identifier], $GLOBALS[users][byuri][$_SESSION[openid_identifier]][role]);
		}
		#get jid
		$csql = mysql_query("SELECT xmpp FROM ".$GLOBALS[cfg][msg][xmpptable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		while ($crow = mysql_fetch_array($csql))
			$myjid = $crow[xmpp];

		#get armory chars
		fetchUsers();
		$tmpnames = ""; $tbool = true;; $tmp = "";
		if(! empty($GLOBALS[users][byuri][$_SESSION[openid_identifier]][armorychars])) {
		  array_unique($GLOBALS[users][byuri][$_SESSION[openid_identifier]][armorychars]);
		  foreach ($GLOBALS[users][byuri][$_SESSION[openid_identifier]][armorychars] as $mychar) {
		    if ($tbool) $tbool = false;
		    else $tmp = ", ";
		    $tmpnames .= $tmp.$mychar;
		  }
		}

		#get profile informations
		$sql = mysql_query("SELECT * FROM ".$GLOBALS[cfg][userprofiletable]." WHERE openid='".$_SESSION[openid_identifier]."';");
		while ($row = mysql_fetch_array($sql)) {
			$cont = templGetFile("form.html");
			if (! $row[accurate])
					$GLOBALS[html] .= "<h2>Deine Profildaten sind nicht aktuell!</h2>"
														."F&uuml;ll bitte die Felder aus und speichere dein Profil bevor du den IMBA Admin weiter brauchen kannst.<br />";
			$cont = templReplText($cont, "DOB", templGenDropdown("dob", 1, 31, $row[dob]));
			$cont = templReplText($cont, "MOB", templGenDropdown("mob", 1, 12, $row[mob]));
			$cont = templReplText($cont, "YOB", templGenDropdown("yob", 1930, 2000, $row[yob]));
			$cont = templReplText($cont, "MODULE", $_POST[module]);
			$cont = templReplText($cont, "MYJOB", "updateprofile");
			$cont = templReplText($cont, "SIGNATURE", $row[signature]);
			$cont = templReplText($cont, "RANKIMG", $GLOBALS[cfg][profile][$row[role]][icon]);
			$cont = templReplText($cont, "RANKNAME", $GLOBALS[cfg][profile][$row[role]][name]);
			$cont = templReplText($cont, "NICK", $row[nickname]);
			$cont = templReplText($cont, "FORENAME", $row[forename]);
			$cont = templReplText($cont, "SURNAME", $row[surname]);
			$cont = templReplText($cont, "EMAIL", $row[email]);
			$cont = templReplText($cont, "ICQ", $row[icq]);
			$cont = templReplText($cont, "MSN", $row[msn]);
			$cont = templReplText($cont, "SKYPE", $row[skype]);
			$cont = templReplText($cont, "USERTITLE", $row[usertitle]);
			$cont = templReplText($cont, "AVATAR", $row[avatar]);
			$cont = templReplText($cont, "WEBSITE", $row[website]);
			$cont = templReplText($cont, "MOTTO", $row[motto]);
			$cont = templReplText($cont, "JID", $myjid);
			$cont = templReplText($cont, "CHARS", $tmpnames);

			$tmpgender = "male";
			if ($row[sex] == "F")
				$tmpgender = "female";
			$cont = templReplText($cont, "GENDER", $tmpgender);
		}
		$GLOBALS[html] .= $cont;

	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
