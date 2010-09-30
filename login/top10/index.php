<?php

#only load as module?
if ($_SESSION[loggedin] == 1) {
	#init stuff
	fetchUsers();
	
	$count = 10;

	$top = "";
	foreach ($GLOBALS[users][byuri] as $myuser) {
	  if (! empty($myuser[armorychars])) {
	    foreach ($myuser[armorychars] as $mycharname) {
				if ($char = fetchArmoryCharacter($mycharname)) {
					#pvp
					$top[pvp][$char[name]] = $char[pvpkills];
					
					#ilevel
					$top[ilvl][$char[name]] = $char[ilevelavg];

					#achievments
					if ($char[achievments])
					foreach ($char[achievments] as $achievments) {
					  foreach (array_keys($achievments) as $id) {
							if ($id != 81) {
						    $top[achievment][$id][$char[name]] = $achievments[$id];
								$topa[$char[name]] += $achievments[$id];
							}
					  }
					}					
					#skills
					if ($char[skills])
					foreach ($char[skills] as $skill) {
					  foreach (array_keys($skill) as $id) {
							if (($id != 182) AND ($id != 393) AND ($id != 186))
							  $top[skill][$id][$char[name]] = $skill[$id];
					  }
					}
				}
			}
		}
	}

	function getArray ($where, $what, $count) {
		$tarray = array();
		$rarray = array();
		foreach ($where as $name => $value) {
			array_push($tarray, $value);
		}
		array_unique($tarray);
		arsort($tarray);
		$tcount = 0;
		foreach ($tarray as $tkey) {
			if ($tcount < $count) {
				foreach ($GLOBALS[users][byuri] as $myuser) {
				  if (! empty($myuser[armorychars])) {
				    foreach ($myuser[armorychars] as $mycharname) {
							if ($char = fetchArmoryCharacter($mycharname)) {
								if ($char[$what]) {
									if ($char[$what] == $tkey) {
										if (! in_array($char[name], $rarray)) {
											array_push($rarray, $char[name]);
											$tcount++;
											$GLOBALS[html] .= "<tr><td>".$tkey."</td>";
											$GLOBALS[html] .= "<td>".genArmoryCharHtml($char[name], $char[classid], $char[raceid], $char[genderid], $char[factionid])."</td>";
											$GLOBALS[html] .= "<td>".genUserLink($myuser[uri])."</td></tr>";
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	function getDeepArray ($where, $what, $what2, $count) {
		$tarray = array();
		$rarray = array();
		foreach ($where as $name => $value) {
			array_push($tarray, $value);
		}
		array_unique($tarray);
		arsort($tarray);
		$tcount = 0;
		foreach ($tarray as $tkey) {
			if ($tcount < $count) {
				foreach ($GLOBALS[users][byuri] as $myuser) {
				  if (! empty($myuser[armorychars])) {
				    foreach ($myuser[armorychars] as $mycharname) {
							if ($char = fetchArmoryCharacter($mycharname)) {
								if ($char[$what])
								foreach ($char[$what] as $mywhat) {
									if ($mywhat[$what2] == $tkey) {
										if (! in_array($char[name], $rarray)) {
											array_push($rarray, $char[name]);
											$tcount++;
											$GLOBALS[html] .= "<tr><td>".$tkey."</td>";
											$GLOBALS[html] .= "<td>".genArmoryCharHtml($char[name], $char[classid], $char[raceid], $char[genderid], $char[factionid])."</td>";
											$GLOBALS[html] .= "<td>".genUserLink($myuser[uri])."</td></tr>";
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	function getCheapArray ($where, $count) {
		$tarray = array();
		$rarray = array();
		foreach ($where as $name => $value) {
			array_push($tarray, $value);
		}
		array_unique($tarray);
		arsort($tarray);
		$tcount = 0;
		foreach ($tarray as $tkey) {
			if ($tcount < $count) {
				foreach ($where as $mycharname => $points) {
					if ($points == $tkey) {
						if ($char = fetchArmoryCharacter($mycharname)) {
							if (! in_array($char[name], $rarray)) {
								array_push($rarray, $char[name]);
								$tcount++;
								$GLOBALS[html] .= "<tr><td>".$tkey."</td>";
								$GLOBALS[html] .= "<td>".genArmoryCharHtml($char[name], $char[classid], $char[raceid], $char[genderid], $char[factionid])."</td>";
								$GLOBALS[html] .= "<td>";
								foreach (getArmoryUserOfChar($char[name]) as $myurl)
									$GLOBALS[html] .= genUserLink($myurl);
								$GLOBALS[html] .= "</td></tr>";
							}
						}
					}
				}
			}
		}
	}


	$GLOBALS[html] .= "<h3><a href='?module=".$_POST[module]."&mydo='>Ach. Total / PVP / Itemlevel</a> | ";
	$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&mydo=achievments'>Achievments</a> | ";
	$GLOBALS[html] .= "<a href='?module=".$_POST[module]."&mydo=skills'>Berufe</a></h3>";

	if ($_POST[mydo] == "achievments") {
		$GLOBALS[html] .= "<h3>Achievments</h3>";
		foreach ($top[achievment] as $id => $myach) {
			$GLOBALS[html] .= "<h4>".showArmoryName("achievment", $id)."</h4>";
			$GLOBALS[html] .= "<table><tr><th>Punkte</th><th>Charakter</th><th>Benutzer</th></tr>";
			getDeepArray($myach, "achievments", $id, 10);
			$GLOBALS[html] .= "</table><br />";
		}
	} elseif ($_POST[mydo] == "skills") {

		$GLOBALS[html] .= "<h3>Berufe</h3>";
		foreach ($top[skill] as $id => $myach) {
			$GLOBALS[html] .= "<h4>".showArmoryName("skill", $id)."</h4>";
			$GLOBALS[html] .= "<table><tr><th>Skill</th><th>Charakter</th><th>Benutzer</th></tr>";
			getDeepArray($myach, "skills", $id, 10);
			$GLOBALS[html] .= "</table><br />";
		}
	} else {
		$GLOBALS[html] .= "<h3>Achievments Total</h3>";
		$GLOBALS[html] .= "<table><tr><th>Punkte</th><th>Charakter</th><th>Benutzer</th></tr>";
		getCheapArray($topa, 10);
		$GLOBALS[html] .= "</table><br />";
	
		$GLOBALS[html] .= "<h3>Itemlevel Durchschnitt</h3>";
		$GLOBALS[html] .= "<table><tr><th>Ilvl</th><th>Charakter</th><th>Benutzer</th></tr>";
		getArray($top[ilvl], "ilevelavg", 10);
		$GLOBALS[html] .= "</table><br />";
	
		$GLOBALS[html] .= "<h3>PVP Kills</h3>";
		$GLOBALS[html] .= "<table><tr><th>Kills</th><th>Charakter</th><th>Benutzer</th></tr>";
		getArray($top[pvp], "pvpkills", 10);
		$GLOBALS[html] .= "</table><br />";
	}
	updateTimestamp($_SESSION[openid_identifier]);
} else {
	sysmsg ("You are not logged in!", 1);
}


?>
