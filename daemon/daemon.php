 <?php

#jabber transport from openIDlogin to XMPP Server

#set the path of the framework
$GLOBALS[cfg][logindir]			= "/srv/www/instances/alptroeim.ch/htdocs/login";

#read config and functions
require_once($GLOBALS[cfg][logindir].'/conf.inc.php');
require_once($GLOBALS[cfg][logindir].'/functions.inc.php');


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
	$GLOBALS[cfg][daemon][xmpp][host],
	$GLOBALS[cfg][daemon][xmpp][port],
	$GLOBALS[cfg][daemon][xmpp][user],
	$GLOBALS[cfg][daemon][xmpp][pass],
	$GLOBALS[cfg][daemon][xmpp][domain],

	$GLOBALS[cfg][daemon][logdb][host],
	$GLOBALS[cfg][daemon][logdb][dbname],
	$GLOBALS[cfg][daemon][logdb][user],
	$GLOBALS[cfg][daemon][logdb][pass],
	$GLOBALS[cfg][daemon][logEnable],
	$GLOBALS[cfg][daemon][logDB]
	);

try {
  /* Initiate the connection */
  msg (":Initiating connection");
  $jaxl->connect();
	$myoldstring = "A"; $mystring = "B";
	fetchUsers();

  msg (":Setting timer to 0");
  $GLOBALS[timer] = 9999999999999999999;
  /* Communicate with Jabber Server */
  msg (":Start communicating with Server");
  while($jaxl->isConnected) {
  	if ($GLOBALS[timer] < (time() - $GLOBALS[cfg][daemon][sleeptime]) ) {
			$GLOBALS[timer] = time();
			fetchUsers();
#			msg ("Yippie! I'm triggered :D");



			$cnt = 0;
			unset($GLOBALS[message]);
			$msql = mysql_query("SELECT id,sender,receiver,timestamp,subject,message FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE xmpp='1';");
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
				if (isset($GLOBALS[users][byuri][$mymsg[receiver]][xmpp])) {
					$msg = "Receiver has XMPP";
					$jaxl->sendMessage($GLOBALS[users][byuri][$mymsg[receiver]][xmpp], utf8_encode($GLOBALS[users][byuri][$mymsg[sender]][utf8name]).": ".
									utf8_encode($mymsg[subject])."\n".utf8_encode($mymsg[message]));
				} else {
					$msg = "Receiver has no XMPP";
				}

				$rsql = mysql_query("UPDATE ".$GLOBALS[cfg][msg][msgtable]." SET xmpp='0' WHERE id='".$mymsg[id]."';");
				msg ("MSG From: ".utf8_encode($GLOBALS[users][byuri][$mymsg[sender]][name])."; To: ".
						utf8_encode($GLOBALS[users][byuri][$mymsg[receiver]][name])." (".utf8_encode($msg).")");
			}
		}
    $jaxl->getXML();
  }
}
catch(Exception $e) {
  die($e->getMessage());
}

?>
