<?php
  
  /*
   * Author:
   *    Abhinav Singh
   *
   * Contact:
   *    mailsforabhinav@gmail.com
   *    admin@abhinavsingh.com
   *
   * Site:
   *    http://abhinavsingh.com
   *    http://abhinavsingh.com/blog
   *
   * Source:
   *    http://code.google.com/p/jaxl
   *
   * About:
   *    JAXL stands for "Just Another XMPP Library"
   *    For geeks, JAXL stands for "Jabber XMPP Library"
   *    
   *    I wrote this library while developing Gtalkbots (http://gtalkbots.com)
   *    I have highly customized it to work with Gtalk Servers and inspite of
   *    production level usage at Gtalkbots, I recommend still not to use this
   *    for any live project.
   *    
   *    Feel free to add me in Gtalk and drop an IM.
   *
  */
  
  /*
   * ==================================== IMPORTANT =========================================
   * JAXL extends XMPP and should be the starting point for all your applications
   * You should never try to change XMPP class until you are confident about it
   *
   * Methods you might be interested in:
   *    eventMessage(), eventPresence()
   *    sendMessage($jid,$message), sendStatus($status)
   *    subscribe($jid)
   *    roster('get')
   *    roster('add',$jid) 
   *    roster('remove',$jid)
   *    roster('update',$jid,$name,$groups)
   * ==================================== IMPORTANT =========================================
  */
  
  /* Include XMPP Class */
  include_once("xmpp.class.php");
  
  class JAXL extends XMPP {
    var $bot_status = FALSE;

    function eventMessage($fromJid, $content, $offline = FALSE) {
			$jid = explode("/", $fromJid);
			if (isset($jid[0])) {
				
				$tmpi = explode(":", $content);
				if (count($tmpi) > 1) {
					for ($i = 1; $i <= count($tmpi); $i++) 
						$mycontent .= $tmpi[$i];
					$tmprec = $GLOBALS[tempnames][trim(strtolower($tmpi[0]))];
					$tmpcont = trim(str_replace($tmpi[0].':', '', $content));

					msg ("Recieved MSG from authorized user: ".$jid[0]." -> ".$GLOBALS[xmpp][strtolower($jid[0])]);
					if (trim(strtolower($tmpi[0])) == "cmd") {
						$isadmin = 0;
						$sqlt = mysql_query("SELECT openid FROM oom_openid_usermanager WHERE openid='".$GLOBALS[xmpp][strtolower($jid[0])]."';");
						while ($myrow = mysql_fetch_array($sqlt))
							$isadmin = 1;
						if ($isadmin) {
							if ($tmpcont) {
								msg ("->\tAdmin Message received: ".$tmpcont);
								$this->sendMessage($fromJid, "Admin Message received: ".$tmpcont);

								if ($tmpcont == "exit") {
										msg ("->\t\tExiting");
										$this->sendMessage($fromJid, "Exiting Daemon");
										exit;
								}



							} else {
								msg ("->\tNo Admin command received!");
								$this->sendMessage($fromJid, "No Admin command received!");
							}
						} else {
							msg ("->\tUser is not admin!");
							$this->sendMessage($fromJid, "You are no Admin!");
						}
					} elseif (empty($tmprec)) {
						msg ("->\tNo target User found: ".$tmpi[0]);
						$this->sendMessage($fromJid, "No User '".$tmpi[0]."' found!");
					} else {
						if ($tmpcont) {
							msg ("->\tMessage to ".$tmprec." delivered: ".$tmpcont);
							$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][messagetable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
										$GLOBALS[xmpp][strtolower($jid[0])]."', '".$tmprec."', '".time()."', '".$jid[1]."', '".utf8_decode(trim($tmpcont))."', '1', '1');");
							if ($sql)
								$this->sendMessage($fromJid, "Message to ".$tmprec." sent (".$jid[0].", ".$jid[1].")!");
							else
								$this->sendMessage($fromJid, "Mysql Error! Please inform the Admin!");
						} else {
							msg ("->\tMessage to ".$tmprec." dropped. No content found.");
							$this->sendMessage($fromJid, "No content submitted!");
						}
					}
					} else {
						msg ("Malformated message received from: ".$fromJid."; content: ".$content);
						$this->sendMessage($fromJid, "Please use the following convention to send a message:\nReceiver: Message\n\nExample:\nwillhelm: hogger raid?");
					}
				} else {
					msg ("Received MSG from unauthorized user (".$fromJid."). dropping it.");
					$this->sendMessage($fromJid, "Yout jid strin is strange: !".$fromJid);
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
