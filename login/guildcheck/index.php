<?php


#only load as module?
if ($_SESSION[loggedin] == 1) {

	#init stuff
#	fetchUsers();


	if ($_POST[mydo] == "analyze") {
		#change user rights form	
		$GLOBALS[html] .= "<hr />";
		$GLOBALS[html] .= "<h2>Analyse Resultat:</h2>";
		$content = stripslashes ( $_POST[content]);
		if ($content) {
			$charxml = new SimpleXMLElement($content);
			$maina = array();
			foreach ($charxml as $mychar) {
				# guildnote, officernote
				if (strtolower(substr((string) $mychar->g, 0, 1)) == "a") {
					$tmpmain2 = explode(" ",  substr((string) $mychar->g, 2, strlen((string) $mychar->g)));
					$tmpmain = strtolower($tmpmain2[0]);
					if (! empty($tmpmain))
						$alta[strtolower((string) $mychar->c)] = $tmpmain;
					else $GLOBALS[html] .= "No acceptable comment for char: ".(string) $mychar->c." (Wrong Format)<br />";
						
				} elseif (strtolower(substr((string) $mychar->g, 0, 1)) == "m") {
					array_push($maina, strtolower((string) $mychar->c));
		
				} else {
					$GLOBALS[html] .= "No acceptable comment for char: ".(string) $mychar->c." (A/M Missing!)<br />";
		
				}
			}
		
			asort($maina);
		
			$retw = ""; $reto = "";
			$cw = 0; $co = 0;
			foreach ($maina as $mychar) {
				$tmptext = "";
				$tmpb = "";
				$bool = true;
				foreach ($alta as $key => $value) {
					if ($value == $mychar) {
						if ($bool) $bool = false;
						else $tmpb = ", ";
						$tmptext .= $tmpb.ucfirst($key);
						unset($alta[$key]);
					}
				}
				if ($bool) {
					$co++;
					$reto .= ucfirst($mychar).", ";
				} else {
					$cw++;
					$retw .= "&nbsp;&nbsp;".ucfirst($mychar).": ".$tmptext."<br />";
				}
			}
			$GLOBALS[html] .= "<h2>Mains without Twinks (".$co."):</h2><br />".$reto."<br /><br />";
			$GLOBALS[html] .= "<h2>Mains with Twinks(".$cw."):</h2><br />".$retw."<br /><br />";
		
		
		
			$tmpa = array();
			$GLOBALS[html] .= "Showing list of missing Main / Alt assigments:<br />";
			foreach ($alta as $key => $value) {
				array_push($tmpa, $value);
			}
			asort($tmpa);
			$tmpa = array_unique($tmpa);
			foreach ($tmpa as $main) {
				$bool = true; $tmp = "";
				$GLOBALS[html] .= "&nbsp;&nbsp;".ucfirst($main).": ";
				foreach ($alta as $key => $value) {
					if ($value == $main) {
						if ($bool) $bool = false;
						else $tmp = ", ";
						$GLOBALS[html] .= $tmp.ucfirst($key);
						unset($alta[$key]);
					}
				}
				$GLOBALS[html] .= "<br />";
			}
		} else {
			$GLOBALS[html] .= "<h3>Es wurden keine Daten &uuml;bermittelt.</h3>";
		}
	} else {
		$GLOBALS[html] .= "<h3>Wie funktioniert das?</h3>";
		$GLOBALS[html] .= "1) Das WOW Addon runterladen und installieren: <a href=''>XML Guild Export</a><br />";
		$GLOBALS[html] .= "2) Im WOW '/exportguild' ausf&uuml;hren.<br />";
		$GLOBALS[html] .= "3) Den Text aus dem Fenster im WOW in das Textfeld unten einf&uuml;gen.<br />";
		$GLOBALS[html] .= "4) Submit dr&uuml;cken.<br /><br />";
		$GLOBALS[html] .= "<form action='?' method='post' accept-charset='UTF-8'>";
		$GLOBALS[html] .= "<input type='hidden' name='module' value='".$_POST[module]."'>";
		$GLOBALS[html] .= "<input type='hidden' name='mydo' value='analyze'>";
		$GLOBALS[html] .= "<textarea name='content' cols='80' rows='10'></textarea><br />";
		$GLOBALS[html] .= "<input type='submit' name='submit' value='submit'>";
		$GLOBALS[html] .= "</form>";
	}

	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
