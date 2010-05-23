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
		$tmpuri = $GLOBALS[cfg][openid_identifier_base].$_POST[ssoInpUsername];
	}

	$_SESSION[openid_identifier] = $tmpuri;

	if (empty($tmpuri)) {
		$GLOBALS[myreturn][msg] = "Expected an OpenID URL.";
		header('X-JSON: '.json_encode($GLOBALS[myreturn]).')');
		echo json_encode($GLOBALS[myreturn]);
		exit(0);
	}
	#FIXME ... i only support 1 onlenid site ... not really open -.-
	return $tmpuri;
}

function openid_auth() {
    $openid = getOpenIDURL();
    $consumer = getConsumer();

	if (!isValidURL($openid)) {
		$_SESSION[error] = "ssoInpUsername_no_valid_url";
		$GLOBALS[refresh] = 1;
		return;
	}

    // Begin the OpenID authentication process.
    $auth_request = $consumer->begin($openid);

    // No auth request means we can't begin OpenID.
    if (!$auth_request) {
        $GLOBALS[myreturn][msg] = "invalid OpenID";
    }

    $sreg_request = Auth_OpenID_SRegRequest::build(
                                     // Required
                                     array('nickname'),
                                     // Optional
                                     array('fullname', 'email'));

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
            $GLOBALS[myreturn][msg] = "Could not redirect to server: " . $redirect_url->message;
        } else {
            // Send redirect.
			$GLOBALS[myreturn][msg] = "redirect";
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
            $GLOBALS[myreturn][msg] = "Could not redirect to server: " . $form_html->message;
        } else {
			$GLOBALS[submitform] = 1;
			$GLOBALS[html] = $form_html;
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
        $GLOBALS[html] = 'Verification cancelled.';
    } else if ($response->status == Auth_OpenID_FAILURE) {
        // Authentication failed; display the error message.
        $GLOBALS[html] = "OpenID authentication failed: " . $response->message;
    } else if ($response->status == Auth_OpenID_SUCCESS) {
        // This means the authentication succeeded; extract the
        // identity URL and Simple Registration data (if it was
        // returned).
        $openid = $response->getDisplayIdentifier();
        $esc_identity = escape($openid);

		$_SESSION[loggedin] = 1;
		$GLOBALS[myreturn][loggedin] = 1;

        $GLOBALS[html] = sprintf('You have successfully verified ' .
                           '<a href="%s">%s</a> as your identity.',
                           $esc_identity, $esc_identity);

        if ($response->endpoint->canonicalID) {
            $escaped_canonicalID = escape($response->endpoint->canonicalID);
            $GLOBALS[html] .= '  (XRI CanonicalID: '.$escaped_canonicalID.') ';
        }

        $sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

        $sreg = $sreg_resp->contents();

        if (@$sreg['email']) {
			$_SESSION[user][email] = escape($sreg['email']);
			$GLOBALS[myreturn][user][email] = escape($sreg['email']);
        }

        if (@$sreg['nickname']) {
   			$_SESSION[user][nickname] = escape($sreg['nickname']);
			$GLOBALS[myreturn][user][nickname] = escape($sreg['nickname']);
        }

        if (@$sreg['fullname']) {
   			$_SESSION[user][fullname] = escape($sreg['fullname']);
			$GLOBALS[myreturn][user][fullname] = escape($sreg['fullname']);        
		}

	$pape_resp = Auth_OpenID_PAPE_Response::fromSuccessResponse($response);

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
    }
}

?>
