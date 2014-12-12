<?php

namespace PushApi\System;

use \PushApi\PushApiException;
use \PushApi\System\INotification;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Manages the main functionalities that handles android notifications sending.
 *
 * Note: If your organization has a firewall that restricts the traffic to or from the Internet,
 * you need to configure it to allow connectivity with GCM in order for your Android devices to
 * receive messages. The ports to open are: 5228, 5229, and 5230. GCM typically only uses 5228,
 * but it sometimes uses 5229 and 5230. GCM doesn't provide specific IPs, so you should allow your
 * firewall to accept outgoing connections to all IP addresses contained in the IP blocks listed in
 * Google's ASN of 15169.
 */
class Android implements INotification
{
	const JSON = 'application/json';

	/**
	 * Android response keys and descriptions
	 */
	// success, no actions required
	const MESSAGE_ID = 'message_id';
	// notification should be resent
	const UNAVAILABLE = 'Unavailable';
	// had an unrecoverable error (maybe the value got corrupted in the database)
	const INVALID_REGISTRATION = 'InvalidRegistration';
	// the registration ID should be updated in the server database
	const REGISTRATION_ID = 'registration_id';
	// registration ID should be removed from the server database because the application was uninstalled from the device
	const NOT_REGISTERED = 'NotRegistered';

	private $url = "https://android.googleapis.com/gcm/send";
	// See documentation in order to get the $apiKey
	private $apiKey = "AIzaSyCHeOCzPlTlwgiqhdG3EZ_sE07FVR2OBSA";
	private $autorization = "Authorization: key=";
	private $contentType = "Content-type: ";
	private $headers = array();

	private $message;

	public function setMessage($to, $subject, $message, $from = false)
	{
		$this->message = array(
			"registration_ids" => $to,
			"collapse_key" => $subject,
			"data" => array(
				"text" => $message
			),
			"delay_while_idle" => true,
			// This parameter allows developers to test a request without actually sending a message.
			"dry_run" => true
		);

		return isset($this->message);
	}

	public function getMessage()
	{
		if (isset($this->message)) {
			return $this->message;
		}
		return false;
	}

	public function send()
	{
		// Preparing HTTP headers
		$this->headers = array(
			$this->autorization . $this->apiKey,
			$this->contentType . self::JSON
		);

		// Preparing HTTP connection
        $ch = curl_init();
 
        // Setting the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $this->url);
 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($this->message));
 
        // Disabling SSL Certificate support temporarly
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 
        // Send POST request to Google Cloud Message Server
        $result = curl_exec($ch);

        // Fetching results or failing if doesn't work
        if ($result === false) {
            die('Problem ocurred while Curl: ' . curl_error($ch));
        }
 
        // Closing the HTTP connection
        curl_close($ch);

		return $result;
	}

	/**
	 * Checks the failures of the results and does the right action foreach case:
	 * - user has uninstalled the app or hasn't that id -> delete the android_id
	 * - user is unreachable -> resend the notification
	 */
	public function checkFailureResults($users, $result)
	{

	}

	/**
	 * Checks into the results for the user that has a new android_id
	 * and uptates with the new id
	 */
	public function checkCanonicalResults($users, $result)
	{
		var_dump($users);
		var_dump($result);
	}
}