<?php #get start time of script
$m_time = explode(" ",microtime());
$m_time = $m_time[0] + $m_time[1];
$starttime = $m_time;

#load config
require_once('../conf.inc.php');

#do the mysql connection
$con = @mysql_pconnect($GLOBALS[cfg][mysqlHost], $GLOBALS[cfg][mysqlUser], $GLOBALS[cfg][mysqlPW])
or exit("Connection failed.");
@mysql_select_db ($GLOBALS[cfg][mysqlDB], $con)
or exit("Database not found.");
mysql_query('set character set utf8;');
mysql_set_charset('UTF8',$con);

require_once('../functions.inc.php');

$sql = mysql_query("SELECT name,value FROM ".$GLOBALS[cfg][settingstable]." WHERE 1;");
while ($row = mysql_fetch_array($sql))
	$GLOBALS[$row[name]] = $row[value];


$_POST[module] = "armory_bot";
############# LOAD COMPLETE ###############

if ($_GET['name']) {
		$char = fetchArmoryCharacter(utf8_encode($_GET['name']));
    echo ucfirst($_GET[name]) . ';';
    echo $char[classid] . ';';
    echo $char[factionid] . ';';
    echo $char[raceid] . ';';
    echo $char[level] . ';';  
    echo $char[guildname] . ';';
    echo $char[genderid] . '';
} else {
	$sql = "SELECT name,timestamp FROM ".$GLOBALS[cfg][armory][charcachetable].
					" WHERE timestamp<".(time() - $GLOBALS[armorychartimeout]).
					" ORDER BY timestamp ASC, level DESC, name ASC;";
	$sqlr = mysql_query($sql);

	#command line char update
	$countt = 0;
	$countu = 0;
	$names = "";
	while ($row = mysql_fetch_array($sqlr)) {
		$countt++;
		if ($GLOBALS[armorycharupdatecount]	< $GLOBALS[armorycharmaxupdate]) {
			$char = fetchArmoryCharacter($row[name]);
			sleep(1);
			if (! $GLOBALS[armorydown]) {
				$countu++;
				$names .= " ".$row[name];
			}
		}
	}
	if ($countt OR $countu) {
		sysmsg ("Updated ".$countu." of ".$countt." chars ->".$names, 1);
	}
	if ($GLOBALS[armorydown])
		sysmsg ("Armory down.", 1);
}

?>
