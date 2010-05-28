<?php
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  class JAXL extends XMPP {
    var $bot_status = FALSE;

    function eventMessage($fromJid, $content, $offline = FALSE) {


			#do we have an ok sender?
			$jid = explode("/", $fromJid);
			if (isset($jid[0])) {

				#does the sender have a entry in the xmpp database?
				if (empty($GLOBALS[xmpp][strtolower($jid[0])])) {
					msg ("Recieved MSG from unauthorized user: ".$jid[0]);
					$this->sendMessage($fromJid, "You are not allowed to use this bot! Please setup your XMPP Traversal on the homepage.");
				} else {
					msg ("Recieved MSG from authorized user: ".$jid[0]." -> ".$GLOBALS[xmpp][strtolower($jid[0])]);

					#lastonline update in db
					$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET timestamp='".time()."' WHERE openid='".$GLOBALS[xmpp][strtolower($jid[0])]."';");

					#check for admin rights
					$isadmin = 0;
					$sqlt = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE openid='".$GLOBALS[xmpp][strtolower($jid[0])]."';");
					while ($myrow = mysql_fetch_array($sqlt))
						$isadmin = 1;
					if ($isadmin)
						msg ("\tUser has admin rights");
					else
						msg ("\tUser has no admin rights");

					#generate help message
					$help  = "How to communicate with me:\n";
					$help .= "!command | run a command\n";
					$help .= "user:message | send a message to a user\n";

					$help .= "\nAvailable Commands:\n";
					$help .= "!help | show this help\n";
					$help .= "!users | show a list of all users\n";
					$help .= "!recent | show recent messages (max 10)\n";
					if ($isadmin) {
						$help .= "\nAdmin Commands:\n";
						$help .= "!exit | exit daemon (will restart)\n";
					}

					## what do we have to do? ##

					#check and split for message
					$tmpi = explode(":", $content);
					if (count($tmpi) > 1) {
						$ismessage = 1;
						for ($i = 1; $i <= count($tmpi); $i++) 
							$message .= $tmpi[$i];
						$rec = $GLOBALS[tempnames][utf8_decode(trim(strtolower($tmpi[0])))];
						$cont = trim(str_replace($tmpi[0].':', '', $content));

						#is there a target user?
						if (empty($rec)) {
							msg ("->\tNo target User found: ".$tmpi[0]);
							$this->sendMessage($fromJid, "No User '".$tmpi[0]."' found!");

						#is there content?
						} else {
							if ($cont) {
								msg ("->\tMessage to ".$rec." delivered: ".$cont);
								$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
												$GLOBALS[xmpp][strtolower($jid[0])]."', '".$rec."', '".time()."', 'XMPP/".$jid[1]."', '".
												utf8_decode(str_replace("'","\'",trim($cont)))."', '1', '1');");

								#sql ok?
								if ($sql)
									$this->sendMessage($fromJid, "Message to ".utf8_decode($rec)." sent (".$jid[0].", ".$jid[1].")!");
								else
									$this->sendMessage($fromJid, "Mysql Error! Please inform the Admin!");
							} else {
								msg ("->\tMessage to ".$rec." dropped. No content found.");
								$this->sendMessage($fromJid, "No content submitted!");
							}
						}

					#is the message a comand message?
					} elseif (substr($content, 0, 1) == "!") {
						msg ("\tCommand mode enabled.");

						#admin commands
						if ($isadmin) {
							if ($content == "!exit") {
								msg ("\tExiting...");
								$this->sendMessage($fromJid, "Deamon exiting!");
								exit;
							}
						}
						if ($content == "!users") {
							msg ("\tShowing all users");

							#get last online timestamps
							$sql = mysql_query("SELECT timestamp,openid FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE 1;");
							while ($row = mysql_fetch_array($sql))
								$GLOBALS[tmp][online][$row[openid]] = $row[timestamp];
							
							#create output
							$boolon = 1; $booli = 1; $booloff = 1; $tmpon = ""; $tmpi = ""; $tmpoff = "";
							$reton = ""; $reti = ""; $retoff = ""; $cnton = 0; $cnti = 0; $cntoff = 0; $boolj = 1; $tmpj = ""; $cntj = 0;
							$sqla = mysql_query("SELECT member_name,openid_uri FROM ".$GLOBALS[cfg][usernametable]." WHERE openid_uri<>'';");
							while ($rowa = mysql_fetch_array($sqla)) {
								if ($GLOBALS[tmp][online][$rowa[openid_uri]] > (time() - $GLOBALS[cfg][lastonlinetimeout])) {
									if ($boolon) $boolon = 0;
									else $tmpon = ", ";
									$reton .= $tmpon.utf8_encode($rowa[member_name]);
									$cnton++;
								} elseif ($GLOBALS[tmp][online][$rowa[openid_uri]] > (time() - $GLOBALS[cfg][lastidletimeout])) {
									if ($booli) $booli = 0;
									else $tmpi = ", ";
									$reti .= $tmpi.utf8_encode($rowa[member_name]);
									$cnti++;
								} else {
									if ($booloff) $booloff = 0;
									else $tmpoff = ", ";
									$retoff .= $tmpoff.utf8_encode($rowa[member_name]);
									$cntoff++;
								}

								#check for xmpp
								if (isset($GLOBALS[xmpp][$rowa[openid_uri]])) {
									if ($boolj) $boolj = 0;
									else $tmpj = ", ";
									$retj .= $tmpj.utf8_encode($rowa[member_name]);
									$cntj++;
								} 
							}
							$mymessage = $cnton." Users online:\n".$reton."\n\n".$cnti." Users idle:\n".$reti."\n\n".
														$cntoff." Users offline:\n".$retoff."\n\n".$cntj." Users with Jabber Traversal:\n".$retj."\n\nTotal ".($cnton + $cnti + $cntoff);
							$this->sendMessage($fromJid, $mymessage);

						#recent messages
						} elseif ($content == "!recent") {
							$cnt = 0; $out = "";
							$sql = mysql_query("SELECT sender,subject,timestamp,message FROM ".$GLOBALS[cfg][msg][msgtable].
											" WHERE receiver='".$GLOBALS[xmpp][strtolower($jid[0])]."' ORDER BY timestamp ASC LIMIT 10;");
							while ($row = mysql_fetch_array($sql)) {
								$out .= $GLOBALS[tempnames][$row[sender]]." | ".getAge($row[timestamp])." | ".$row[subject]."\n".utf8_encode($row[message])."\n\n";
								$cnt++;
							}

							msg ("\tShowing recent messages");
							$this->sendMessage($fromJid, "Showing recent ".$count." messages:\n".$out);

						#show the help
						} elseif ($content == "!help") {
							msg ("\tShowing help");
							$this->sendMessage($fromJid, "Showing help:\n".$help);

						#no such command
						} else {
							msg ("\tNo command found: ".$content);
							$this->sendMessage($fromJid, "No such command.\n".$help);
						}

					#malformated message
					} else {
						msg ("\tMalformated message");
						$this->sendMessage($fromJid, "Malformated message!\n".$help);
					}
				}
			} else {
				msg ("Message received, but something is not ok! jid: ".$fromJid."; content: ".$content);
			}

			if($this->logDB) {
  	      // Save the message in the database
    	    $timestamp = date('Y-m-d H:i:s');
      	  $query = "INSERT INTO message (FromJid,Message,Timestamp) value ('$fromJid','$content','$timestamp')";
        	$this->mysql->setData($query);
      }
    }
    
    function eventPresence($fromJid, $status, $photo) {
      // Change your status message to your friend's status
//      $this->sendStatus($status);
      
      if($this->logDB) {
        // Save the presence in the database
        $timestamp = date('Y-m-d H:i:s');
        $query = "INSERT INTO presence (FromJid,Status,Timestamp) value ('$fromJid','$status','$timestamp')";
        $this->mysql->setData($query);
      }
    }
    
    function setStatus() {
      // Set a custom status or use $this->status
      $this->sendStatus("Ready for Communication.");
      msg ("Setting Status");

      if(!$this->bot_status) {  
        $this->logger->logger('Starting Cycles');  
        $this->init();  
        $this->bot_status = TRUE;  
      }  
	}

	function init() {
		msg ("init function called");
		$GLOBALS[timer] = time();
		return TRUE;
	}
  }
?>
