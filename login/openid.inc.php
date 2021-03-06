<?php
#initializing openid framework:
require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/FileStore.php";
require_once "Auth/OpenID/SReg.php";
require_once "Auth/OpenID/PAPE.php";

#function definitions:
function &getStore() {
    /**
     * This is where the example will store its OpenID information.
     * You should change this path if you want the example store to be
     * created elsewhere.  After you're done playing with the example
     * script, you'll have to remove this directory manually.
     */
    $store_path = "/tmp/_php_consumer_test";

    if (!file_exists($store_path) &&
        !mkdir($store_path)) {
        print "Could not create the FileStore directory '$store_path'. ".
            " Please check the effective permissions.";
        
		header('X-JSON: '.json_encode($GLOBALS[myreturn]).'');
		echo json_encode($GLOBALS[myreturn]);
		exit(0);
    }

    return new Auth_OpenID_FileStore($store_path);
}

function &getConsumer() {
    /**
     * Create a consumer object using the store object created
     * earlier.
     */
    $store = getStore();
    $consumer =& new Auth_OpenID_Consumer($store);
    return $consumer;
}

function getScheme() {
    $scheme = 'http';
    if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
        $scheme .= 's';
    }
    return $scheme;
}

function getReturnTo() {
	if (! empty($_SESSION[tmp][referer]))
		return $GLOBALS[cfg][returnto]."&ssoInpReferer=".$_SESSION[tmp][referer];
	elseif ($_POST[mydo] == "registerme")
		return $GLOBALS[cfg][trustroot]."login.inc.php?mydo=verifyme";
	else
		return $GLOBALS[cfg][returnto];
}

function getTrustRoot() {
	return $GLOBALS[cfg][trustroot];
}


function getOpenIDURL() {
    // Render a default page if we got a submission without an openid
	// value.

	if (substr($_POST[ssoInpUsername], 0, 4) == "http") {
		$tmpuri = $_POST[ssoInpUsername];
	} else {
		if (in_array(strtolower(trim($_POST[ssoInpUsername])), $GLOBALS[oldusers])) {
			sysmsg ("User with old OpenID logged in: ".$_POST[ssoInpUsername], 1);
			$tmpuri = $GLOBALS[cfg][old_openid_identifier_base].$_POST[ssoInpUsername];
		} else {
			$tmpuri = $GLOBALS[cfg][openid_identifier_base].$_POST[ssoInpUsername];
		}
	}
	$_SESSION[openid_identifier] = $tmpuri;

	if (empty($tmpuri)) {
		sysmsg ("Expected an OpenID URL.", 1);
		header('X-JSON: '.json_encode($GLOBALS[myreturn]).')');
		echo json_encode($GLOBALS[myreturn]);
		exit(0);
	}
	return $tmpuri;
}

function openid_auth() {
    $openid = getOpenIDURL();
    $consumer = getConsumer();

	if (!isValidURL($openid)) {
		$GLOBALS[refresh] = 1;
		return;
	}

    // Begin the OpenID authentication process.
    $auth_request = $consumer->begin($openid);

    // No auth request means we can't begin OpenID.
    if (!$auth_request) {
        sysmsg ("invalid OpenID", 1);
    }

    $sreg_request = Auth_OpenID_SRegRequest::build(
                  // Required
                  array('nickname', 'email', 'fullname'),
                  // Optional
                  array('dob', 'gender', 'postalcode', 'country'));

		if ($sreg_request) {
        $auth_request->addExtension($sreg_request);
    }

    $policy_uris = $_GET['policies'];

    $pape_request = new Auth_OpenID_PAPE_Request($policy_uris);
    if ($pape_request) {
        $auth_request->addExtension($pape_request);
    }

    // Redirect the user to the OpenID server for authentication.
    // Store the token for this authentication so we can verify the
    // response.

    // For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
    // form to send a POST request to the server.
    if ($auth_request->shouldSendRedirect()) {
        $redirect_url = $auth_request->redirectURL(getTrustRoot(),
                                                   getReturnTo());
        // If the redirect URL can't be built, display an error
        // message.
        if (Auth_OpenID::isFailure($redirect_url)) {
            sysmsg ("Could not redirect to server: ".$redirect_url->message, 2);
        } else {
            // Send redirect.
						sysmsg ("Redirecting for OpenID", 2);
						$GLOBALS[myreturn][redirect] = $redirect_url;
            header("Location: ".$redirect_url);
     }
    } else {
        // Generate form markup and render it.
        $form_id = 'openid_message';
        $form_html = $auth_request->formMarkup(getTrustRoot(), getReturnTo(),
						false, array('id' => $form_id, 'name' => $form_id));

        // Display an error if the form markup couldn't be generated;
        // otherwise, render the HTML.
        if (Auth_OpenID::isFailure($form_html)) {
            sysmsg ("Could not redirect to server: ".$form_html->message, 1);
        } else {
					$GLOBALS[submitform] = 1;
					$GLOBALS[html] .= $form_html;
        }
    }
}

function escape($thing) {
    return htmlentities($thing);
}

function openid_verify () {
    $consumer = getConsumer();

    // Complete the authentication process using the server's
    // response.
    $return_to = getReturnTo();
    $response = $consumer->complete($return_to);

    // Check the response status.
    if ($response->status == Auth_OpenID_CANCEL) {
        // This means the authentication was cancelled.
        sysmsg ('Verification cancelled.', 1);
				return false;
    } else if ($response->status == Auth_OpenID_FAILURE) {
        // Authentication failed; display the error message.
				sysmsg ("OpenID authentication failed: " . $response->message, 1);
				return false;
    } else if ($response->status == Auth_OpenID_SUCCESS) {
        // This means the authentication succeeded; extract the
        // identity URL and Simple Registration data (if it was
        // returned).
        $openid = $response->getDisplayIdentifier();
        $esc_identity = escape($openid);

				if (($_POST[mydo] == "verifyme") AND ($_POST[module] == "register")) {
					$_SESSION[registred] = 1;
					$GLOBALS[newopenid] = $esc_identity;
				} else {
					$sql = "SELECT role FROM ".$GLOBALS[cfg][userprofiletable]." WHERE openid='".$esc_identity."';";
					$sqlr = mysql_query($sql);
					$bool = false;
					while ($row = mysql_fetch_array($sqlr))
						if ($row[role] > 0)
							$bool = true;

					if ($bool)
						$_SESSION[loggedin] = 1;
					else {
						$applicant = 0;
						$sql = "SELECT nickname,timestamp,state,answer FROM ".$GLOBALS[cfg][userapplicationtable]." WHERE openid='".$esc_identity."';";
						$sqlr = mysql_query($sql);
						while ($row = mysql_fetch_array($sqlr)) {
							$applicant = 1;
							$appname = $row[nickname];
							$appanswer = $row[answer];
							$appstate = $row[state];
							$appage = getNiceAge($row[timestamp]);
						}

						if ($applicant) {
							$atmp = templGetFile("waiting.html");
							$atmp = templReplText($atmp, "NICK", $appname);	
							$atmp = templReplText($atmp, "ANSWER", $appanswer);	
							$atmp = templReplText($atmp, "STATE", $appstate);	
							$atmp = templReplText($atmp, "AGE", $appage);	
							$GLOBALS[html] .= $atmp;
						} else {
							sysmsg ("Unauthorized access / Banned! ".$esc_identity, 1);
						}
						return false;
					}
				
				}

				$GLOBALS[myreturn][loggedin] = 1;

        sysmsg ('OpenID '.$esc_identity.' successfully verified.', 2);
        if ($response->endpoint->canonicalID) {
            $escaped_canonicalID = escape($response->endpoint->canonicalID);
            $GLOBALS[html] .= '  (XRI CanonicalID: '.$escaped_canonicalID.') ';
        }

        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

        $sreg = $sreg_resp->contents($sreg_resp);

//				print_r($sreg); exit;
				if (@$sreg['email'])
					$_SESSION[user][email] = escape($sreg['email']);

        if (@$sreg['nickname'])
					$_SESSION[user][nickname] = escape($sreg['nickname']);

        if (@$sreg['fullname'])
					$_SESSION[user][fullname] = escape($sreg['fullname']);

        if (@$sreg['dob'])
					$_SESSION[user][dob] = escape($sreg['dob']);

        if (@$sreg['gender'])
					$_SESSION[user][gender] = escape($sreg['gender']);

        if (@$sreg['country'])
					$_SESSION[user][country] = escape($sreg['country']);

        if (@$sreg['language'])
					$_SESSION[user][language] = escape($sreg['language']);

        if (@$sreg['timezone'])
					$_SESSION[user][timezone] = escape($sreg['timezone']);

        if (@$sreg['postalcode'])
					$_SESSION[user][postalcode] = escape($sreg['postalcode']);

//	$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

/*	if ($pape_resp) {
            if ($pape_resp->auth_policies) {
                $success .= "<p>The following PAPE policies affected the authentication:</p><ul>";

                foreach ($pape_resp->auth_policies as $uri) {
                    $escaped_uri = escape($uri);
                    $success .= "<li><tt>$escaped_uri</tt></li>";
                }

                $success .= "</ul>";
            } else {
                $success .= "<p>No PAPE policies affected the authentication.</p>";
            }

            if ($pape_resp->auth_age) {
                $age = escape($pape_resp->auth_age);
                $success .= "<p>The authentication age returned by the " .
                    "server is: <tt>".$age."</tt></p>";
            }

            if ($pape_resp->nist_auth_level) {
                $auth_level = escape($pape_resp->nist_auth_level);
                $success .= "<p>The NIST auth level returned by the " .
                    "server is: <tt>".$auth_level."</tt></p>";
            }

		} else {
   	         $success .= "<p>No PAPE response was sent by the provider.</p>";
		} */
		$GLOBALS[myreturn][msg] .= $success;
		return true;
    }
}

?>
