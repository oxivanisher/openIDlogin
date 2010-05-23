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
				msg ("Recieved MSG from authorized user: ".$jid[0].$GLOBALS[tempnames][$jid[0]]);
				$tmpi = explode(":", $content);
				if (count($tmpi) > 1) {
					msg ("format ok");

					for ($i = 1; $i <= count($tmpi); $i++) {
						$mycontent .= $tmpi[$i];
					}
					$tmprec = $GLOBALS[tempnames][trim(strtolower($tmpi[0]))];
					$tmpcont = str_replace($tmpi[0].': ', '', $content);
					$sql = mysql_query("INSERT INTO ".$GLOBALS[cfg][messagetable]." (sender,receiver,timestamp,subject,message,new,xmpp) VALUES ('".
								$GLOBALS[xmpp][$jid[0]]."', '".$tmprec."', '".time()."', '".$jid[1]."', '".utf8_decode($tmpcont)."    ', '1', '1');");
					if ($sql)
						$this->sendMessage($fromJid, "Message to ".$tmprec." sent!");
					else
						$this->sendMessage($fromJid, "System Error! Please inform the Admin!");
				} else {
					msg ("Malformated!");
					$this->sendMessage($fromJid, "Please use the following convention to send a message:\nReceiver: Message\n\nExample:\nwillhelm: hogger raid?");
				}
			} else {
				msg ("Received MSG from unauthorized user (".$fromJid."). dropping it.");
				$this->sendMessage($fromJid, "You are not authorized to use this bot!");
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
      $this->sendStatus($status);
      
      if($this->logDB) {
        // Save the presence in the database
        $timestamp = date('Y-m-d H:i:s');
        $query = "INSERT INTO presence (FromJid,Status,Timestamp) value ('$fromJid','$status','$timestamp')";
        $this->mysql->setData($query);
      }
    }
    
    function setStatus() {
      // Set a custom status or use $this->status
      $this->sendStatus($this->status);
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
