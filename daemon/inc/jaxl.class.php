<?php
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  class JAXL extends XMPP {
    var $bot_status = FALSE;

    function eventMessage($fromJid, $content, $offline = FALSE) {
			$tmpskip = 0;

			#do we have an ok sender?
			$jid = explode("/", $fromJid);
			if (isset($jid[0])) {

				#does the sender have a entry in the xmpp database?
				if (empty($GLOBALS[users][byxmpp][strtolower($jid[0])])) {
					msg ("Recieved MSG from unauthorized user: ".$jid[0]);
					$this->sendMessage($fromJid, "You are not allowed to use this bot! Please setup your XMPP Traversal on the homepage.");
					sysmsg ("Recieved MSG from unauthorized user: ".$jid[0], 1, "Unknown", $jid[1]);
				} else {
					msg ("Recieved MSG from authorized user: ".$jid[0]." -> ".$GLOBALS[users][byxmpp][strtolower($jid[0])]);

					#lastonline update in db
					updateTimestamp($GLOBALS[users][byxmpp][strtolower($jid[0])]);

					#check for admin rights
					$isadmin = 0;
					$sqlt = mysql_query("SELECT openid FROM ".$GLOBALS[cfg][admintablename]." WHERE openid='".$GLOBALS[users][byxmpp][strtolower($jid[0])]."';");
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
					$help .= "r:message | reply to the last message\n";

					$help .= "\nAvailable Commands:\n";
					$help .= "!help | show this help\n";
					$help .= "!users | show a list of all users\n";
					$help .= "!status <your status> | set your web status (currently you cant use : )\n";
					$help .= "!recent | show recent messages (max 10)\n";
					$help .= "!lastseen | shows when which users where last online\n";
					if ($isadmin) {
						$help .= "\nAdmin Commands:\n";
						$help .= "!exit | exit daemon (will restart)\n";
						$help .= "!roster | refresh the roster (do some magic)\n";
						$help .= "!mass | send a massmailer message (all users will receive it!)\n";
						$help .= "!sysmsgs | show the latest 20 system messages\n";
						$help .= "!clearcache | clears the fucking eqdkp cache\n";
					}

					## what do we have to do? ##

					#check and split for message
					$tmpi = explode(":", $content);


				#is the message a comand message?
				if (substr($content, 0, 1) == "!") {
						msg ("\tCommand mode enabled.");

						#admin commands
						if ($isadmin) {

							#exit the daemon
							if ($content == "!exit") {
								msg ("\tExiting...");
								$this->sendMessage($fromJid, "Deamon exiting!");
								sysmsg ("Exiting Daemon...", 1, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								exit;

							#show latest system messages
							} elseif ($content == "!sysmsgs") {
								msg ("\tShowing system messages");

								$tmpret = "";
								$sql = mysql_query("SELECT timestamp,user,msg FROM ".$GLOBALS[cfg][systemmsgsdb]." WHERE 1 ORDER BY timestamp ASC LIMIT 0,20;");
								while ($row = mysql_fetch_array($sql)) {
									if (! empty($GLOBALS[users][byuri][$row[user]][name]))
										$tuser = $GLOBALS[users][byuri][$row[user]][name];
									else
										$tuser = $row[user];

									$tmpret .= $tuser." ".getNiceAge($row[timestamp]).":\n".$row[msg]."\n\n";
								}
								$this->sendMessage($fromJid, "Showing system messages:\n".$tmpret);
								sysmsg ("Showing system messages", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								$tmpskip = 1;

							#reset everyone in the database to xmpp offline
							} elseif ($content == "!roster") {
								$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='',xmppstatus='0' WHERE 1;");
								msg ("\tUpdating roster...");
								$this->sendMessage($fromJid, "Roster update in progress...");
								sysmsg ("Updating roster", 1, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								$tmpskip = 1;
								$GLOBALS[updateroster] = 1;

							#clear fucking eqdkp cache heavy dirty oxi workaround
							} elseif ($content == "!clearcache") {
								system('rm -rf /srv/www/instances/alptroeim.ch/htdocs/site/templates/cache/*');
								msg ("\tClearing cache...");
								$this->sendMessage($fromJid, "Clearing cache!");
								sysmsg ("Clearing cache...", 1, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								$tmpskip = 1;

							#mass mailer
							} elseif (substr($content, 0, 5) == "!mass") {
								$mymassmsg = str_replace("!mass", "", $content);

								if (! empty($mymassmsg)) {
									foreach ($GLOBALS[users][byuri] as $myuri)
										$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
														$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."', '".$myuri[uri]."', '".time()."', 'MASS/XMPP/".$jid[1]."', '".
														utf8_decode(encodeme($mymassmsg))."', '1', '1');");

									msg ("\tMassmail sent!");
									$this->sendMessage($fromJid, "\tMassmail sent!");
									sysmsg ("Massmail sent!", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								} else {
									msg ("\tNo message found.");
									$this->sendMessage($fromJid, "No text found!");
									sysmsg ("No massmail message found.", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								}
								$tmpskip = 1;
							}
						}
						if ($tmpskip) {
							true;
						} elseif ($content == "!users") {
							msg ("\tShowing all users");
							sysmsg ("Showing all users...", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);

							#get last online timestamps
							$sql = mysql_query("SELECT timestamp,openid FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE 1;");
							while ($row = mysql_fetch_array($sql))
								$GLOBALS[tmp][online][$row[openid]] = $row[timestamp];
							
							#create output
							$boolon = 1; $booli = 1; $booloff = 1; $tmpon = ""; $tmpi = ""; $tmpoff = "";
							$reton = ""; $reti = ""; $retoff = ""; $cnton = 0; $cnti = 0; $cntoff = 0; $boolj = 1; $tmpj = ""; $cntj = 0;
							foreach ($GLOBALS[users][byuri] as $myuri) {
								if ($myuri[online] > (time() - $GLOBALS[cfg][lastonlinetimeout])) {
									if ($boolon) $boolon = 0;
									else $tmpon = ", ";
									$reton .= $tmpon.$myuri[name];
									$cnton++;
								} elseif ($myuri[online] > (time() - $GLOBALS[cfg][lastidletimeout])) {
									if ($booli) $booli = 0;
									else $tmpi = ", ";
									$reti .= $tmpi.$myuri[name];
									$cnti++;
								} else {
									if ($booloff) $booloff = 0;
									else $tmpoff = ", ";
									$retoff .= $tmpoff.$myuri[name];
									$cntoff++;
								}

								#check for xmpp
								if (! empty($myuri[xmpp])) {
									if ($boolj) $boolj = 0;
									else $tmpj = ", ";
									$retj .= $tmpj.$myuri[name];
									$cntj++;
								} 
							}
							
							#create users online output
							$mymessage = "\n";
							if ($cnton) $mymessage .= $cnton." Users online:\n".$reton."\n\n";
							if ($cnti) $mymessage .= $cnti." Users afk:\n".$reti."\n\n";
							if ($cntoff) $mymessage .= $cntoff." Users offline:\n".$retoff."\n\n";
							if ($cntj) $mymessage .= $cntj." Users with Jabber Traversal:\n".$retj."\n\n";
							$mymessage .= "Total ".($cnton + $cnti + $cntoff);

							$this->sendMessage($fromJid, xmppencode($mymessage));

						#recent messages
						} elseif ($content == "!recent") {
							$cnt = 0; $out = "";
							$sql = mysql_query("SELECT sender,subject,timestamp,message FROM ".$GLOBALS[cfg][msg][msgtable].
											" WHERE receiver='".$GLOBALS[users][byxmpp][strtolower($jid[0])]."' ORDER BY timestamp ASC LIMIT 10;");
							while ($row = mysql_fetch_array($sql)) {
								$out .= $GLOBALS[users][byuri][$row[sender]][name]." | ".getAge($row[timestamp])." | ".$row[subject]."\n".$row[message]."\n\n";
								$cnt++;
							}

							msg ("\tShowing recent messages");
							$this->sendMessage($fromJid, "Showing recent ".$count." messages:\n".xmppencode($out));
							sysmsg ("Showing recent messages", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);

						#set status
						} elseif (substr($content, 0, 7) == "!status") {
							$mywebstatus = str_replace("'", '"', trim( str_replace("!status", "", $content) ) );

							if (! empty($mywebstatus)) {
								$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='".
												$mywebstatus."' WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");

								msg ("\tWebsite status set.");
								$this->sendMessage($fromJid, "Status set to: ".trim( str_replace("!status", "", xmppencode($content)) ));
								sysmsg ("Website status set", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								} else {
								msg ("\tNo status found.");
								$this->sendMessage($fromJid, "No status set!");
								sysmsg ("No website status found to set", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
							}

						#get last seen users
						} elseif (substr($content, 0, 9) == "!lastseen") {
							$myuser = str_replace("'", '"', trim( str_replace("!lastseen", "", $content) ) );

							$tmpuser = $GLOBALS[users][byname][strtolower($myuser)];
							if ($tmpuser) {
								$this->sendMessage($fromJid, "User ".$myuser." last seen: ".getAge($GLOBALS[users][byuri][$tmpuser][online]));
							} else {
								$tmpout = "\n";
								foreach ($GLOBALS[users][byuri] as $myuri)
									$tmpout .= xmppencode($myuri[name])." ".getAge($myuri[online])."\n";

								$this->sendMessage($fromJid, $tmpout);
							}
#							if (! empty($mywebstatus)) {
#								$sql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='".
#												$mywebstatus."' WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");
#
#								msg ("\tWebsite status set.");
#								$this->sendMessage($fromJid, "Status set to: ".trim( str_replace("!status", "", xmppencode($content)) ));
#							} else {
#								msg ("\tNo status found.");
#								$this->sendMessage($fromJid, "No status set!");
#							}
						sysmsg ("Last seen message sent.", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);


						#show the help
						} elseif ($content == "!help") {
							msg ("\tShowing help");
							$this->sendMessage($fromJid, "Showing help:\n".xmppencode($help));
							sysmsg ("Show help", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);

						#no such command
						} else {
							msg ("\tNo command found: ".$content);
							$this->sendMessage($fromJid, "No such command.\n".xmppencode($help));
						sysmsg ("No command found", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
						}

					#send message
					} elseif (count($tmpi) > 1) {
						$ismessage = 1;
						for ($i = 1; $i <= count($tmpi); $i++) 
							$message .= $tmpi[$i];
						$rec = $GLOBALS[users][byname][utf8_decode(strtolower(trim($tmpi[0])))];
						$cont = trim(str_replace($tmpi[0].':', '', $content));

						if (trim(strtolower($tmpi[0])) == "r") {
							$rrec = "";
							$sqlr = mysql_query("SELECT sender FROM ".$GLOBALS[cfg][msg][msgtable]." WHERE receiver='".
											$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."' AND sender<>'".
											$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."' ORDER BY timestamp DESC LIMIT 1;");
							while ($rowr = mysql_fetch_array($sqlr))
								$rrec = $rowr[sender];
							if (! empty($rrec)) {
								msg ("->\tReply triggered to: ".$rrec);
								$rec = $rrec;
								sysmsg ("Reply triggered to: ".$rrec, 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
							}
						}

						#is there a target user?
						if (empty($rec)) {
							msg ("->\tNo target User found: ".$tmpi[0]);
							$this->sendMessage($fromJid, "No User '".$tmpi[0]."' found!");
							sysmsg ("No target User found", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);

						#is there content?
						} else {
							if ($cont) {
								if (count($cont) > 1000)
									$cont = substr($cont, 0, 1000);
								msg ("->\tMessage to ".$rec." delivered.");
								$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][msg][msgtable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
												$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."', '".$rec."', '".time()."', 'XMPP/".$jid[1]."', '".
												utf8_decode(encodeme($cont))."', '1', '1');");

								#sql ok?
								if ($sql) {
									$this->sendMessage($fromJid, "Message to ".$rec." sent (".$jid[0].", ".$jid[1].")!");
									sysmsg ("Message successfully delivered to: ".$rec, 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								} else {
									$this->sendMessage($fromJid, "Mysql Error! Please inform the Admin!");
									sysmsg ("Mysql Error delivering message to: ".$rec, 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
								}
							} else {
								msg ("->\tMessage to ".$rec." dropped. No content found.");
								$this->sendMessage($fromJid, "No content submitted!");
								sysmsg ("Message to ".$rec." dropped. No content found.", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
							}
						}

						#malformated message
					} else {
						msg ("\tMalformated message");
						$this->sendMessage($fromJid, "Malformated message!\n".xmppencode($help));
						sysmsg ("Malformated message", 2, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
					}
				}
			} else {
				msg ("Message received, but something is not ok! jid: ".$fromJid."; content: ".$content);
				sysmsg ("Message received, but something is not ok! jid: ".$fromJid."; content: ".$content, 0, $GLOBALS[users][bylowxmpp][strtolower($jid[0])], $jid[0]);
			}

			#show online users as status message
			$cnton = 0; $cnti = 0; $cntoff = 0;
			foreach ($GLOBALS[users][byuri] as $myuri) {
				if (! empty($myuri[online]))
					if ($myuri[online] > (time() - $GLOBALS[cfg][lastonlinetimeout])) {
						$cnton++;
					} elseif ($myuri[online] > (time() - $GLOBALS[cfg][lastidletimeout])) {
						$cnti++;
					} else {
						$cntoff++;
					}
				}
			
			$mystring = "on: ".$cnton." | afk: ".$cnti." | off: ".$cntoff;
			if ($myoldstring != $mystring) {
				$this->sendStatus($mystring);
				$myoldstring = $mystring;
			}

			if($this->logDB) {
  	      // Save the message in the database
    	    $timestamp = date('Y-m-d H:i:s');
      	  $query = "INSERT INTO message (FromJid,Message,Timestamp) value ('$fromJid','$content','$timestamp')";
        	$this->mysql->setData($query);
      }
    }
    
    function eventPresence($fromJid, $status, $photo) {
			$jid = explode("/", $fromJid);
			$tmpres = $jid[1];
			if ($fromJid == $this->jid) return true;
			msg ("->\teventPresence from: ".$fromJid);

			#the user went offline
			if ($status == "unavailable") {
				msg ("->\t\tUser went offline.");
				$newdata = array();
				$xsql = mysql_query("SELECT status FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");
				while ($xrow = mysql_fetch_array($xsql))
					$data = unserialize($xrow[status]);

				if (empty($data)) {
					$newdata = array();
				} else {
					foreach ($data as $myres)
						if (! empty($myres))
							if ($myres != $tmpres)
								array_push($newdata, $myres);
				}

				if (count($newdata) > 0)
					$xsql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='".serialize($newdata).
															"',xmppstatus='1' WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");
				else
					$xsql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET status='".serialize($newdata).
															"',xmppstatus='0' WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");

			#the user came online / changed status
			} else {  #if ($status == "online") {
				msg ("->\t\tgot status: ".$status);
#				$this->sendPresence("subscribed", $fromJid);
				$newdata = array();
				$xsql = mysql_query("SELECT status FROM ".$GLOBALS[cfg][lastonlinedb]." WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");
				while ($xrow = mysql_fetch_array($xsql))
					$data = unserialize($xrow[status]);

				if (! empty($data)) {
					foreach ($data as $myres) {
						if (! empty($myres))
							if ($myres != $tmpres) {
								array_push($newdata, $myres);
						}
					}
					array_push($newdata, $tmpres);
				} else {
					array_push($newdata, $tmpres);
				}
			
				$xsql = mysql_query("UPDATE ".$GLOBALS[cfg][lastonlinedb]." SET xmppstatus='1',status='".serialize($newdata).
														"' WHERE openid='".$GLOBALS[users][bylowxmpp][strtolower($jid[0])]."';");
			}


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
