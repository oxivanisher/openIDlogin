 <?php

#jabber transport from openIDlogin to XMPP Server

#read configs
require_once('./inc/conf.inc.php');
require_once('./inc/functions.inc.php');
require_once($GLOBALS[cfg][logindir].'/conf.inc.php');


$GLOBALS[debug] = 1;

echo "\n";
msg ("connecting to mysql server");
#do the mysql connection
$con = @mysql_pconnect($GLOBALS[cfg][mysqlHost], $GLOBALS[cfg][mysqlUser], $GLOBALS[cfg][mysqlPW])
    or exit("Connection failed.");
@mysql_select_db ($GLOBALS[cfg][mysqlDB], $con)
	or exit("Database not found.");
mysql_set_charset('utf8',$con);

/* Include JAXL Class */
include_once("inc/jaxl.class.php");
 
/* Create an instance of XMPP Class */
$jaxl = new JAXL(
	$GLOBALS[cfg][xmpp][host],
	$GLOBALS[cfg][xmpp][port],
	$GLOBALS[cfg][xmpp][user],
	$GLOBALS[cfg][xmpp][pass],
	$GLOBALS[cfg][xmpp][domain],

	$GLOBALS[cfg][logdb][host],
	$GLOBALS[cfg][logdb][dbname],
	$GLOBALS[cfg][logdb][user],
	$GLOBALS[cfg][logdb][pass],
	$GLOBALS[cfg][logEnable],
	$GLOBALS[cfg][logDB]
	);

try {
  /* Initiate the connection */
  msg (":Initiating connection");
  $jaxl->connect();

	getUsers();

  msg (":Setting timer to 0");
  $GLOBALS[timer] = 9999999999999999999;
  /* Communicate with Jabber Server */
  msg (":Start communicating with Server");
  while($jaxl->isConnected) {
  	if ($GLOBALS[timer] < (time() - $GLOBALS[cfg][sleeptime]) ) {
			$GLOBALS[timer] = time();
			getUsers();
#			msg ("Yippie! I'm triggered :D");

			$cnt = 0;
			unset($GLOBALS[message]);
			$msql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message FROM ".$GLOBALS[cfg][messagetable]." WHERE xmpp='1';");
			while ($mrow = mysql_fetch_array($msql)) {
				$GLOBALS[message][$cnt][id]					= $mrow[id];
				$GLOBALS[message][$cnt][sender]			= $mrow[sender];
				$GLOBALS[message][$cnt][receiver]		= $mrow[receiver];
				$GLOBALS[message][$cnt][timestamp]	= $mrow[timestamp];
				$GLOBALS[message][$cnt][subject]		= $mrow[subject];
				$GLOBALS[message][$cnt][message]		= $mrow[message];
				$cnt++;
			}

			if ($cnt)
			foreach ($GLOBALS[message] as $mymsg) {
				#here goes the magic to send openID msgs to jabber :D
				if (isset($GLOBALS[xmpp][$mymsg[receiver]])) {
					$msg = "Receiver has XMPP";
					$jaxl->sendMessage($GLOBALS[xmpp][$mymsg[receiver]], $GLOBALS[tempnames][$mymsg[sender]].": ".
									utf8_encode($mymsg[subject])."\n".utf8_encode($mymsg[message]));
				} else {
					$msg = "Receiver has no XMPP";
				}

				msg ("MSG From: ".$GLOBALS[tempnames][$mymsg[sender]]."; To: ".$GLOBALS[tempnames][$mymsg[receiver]]." (".$msg.")");
				$rsql = mysql_query("UPDATE ".$GLOBALS[cfg][messagetable]." SET xmpp='0' WHERE id='".$mymsg[id]."';");
			}
		}
    $jaxl->getXML();
  }
}
catch(Exception $e) {
  die($e->getMessage());
}

?>
