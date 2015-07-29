<?php

namespace PushApi\System;

use \PushApi\PushApiException;
use \PushApi\System\INotification;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Manages the main functionalities that handles iOs notifications sending.
 *
 * Apple Note: The production certificate remains valid for a year, but you want to renew it before
 * the year is over to ensure there is no downtime for your app.
 * The maximum size allowed for a notification payload is 2 kilobytes.
 */
class Ios implements INotification
{
    /**
     * IoS response status codes
     */
    const SUCCESS = 0;
    const PROCESSING_ERROR = 1;
    const MISSING_DEVICE_TOKEN = 2;
    const MISSING_TOPIC = 3;
    const MISSING_PAYLOAD = 4;
    const INVALID_TOKEN_SIZE = 5;
    const INVALID_TOPIC_SIZE = 6;
    const INVALID_PAYLOAD_SIZE = 7;
    const INVALID_TOKEN = 8;
    // The APNs server closed the connection (for example, to perform maintenance).
    const SHUTDOWN = 10;
    const UNKNOWN = 255;

    // Dictionary of the error codes
    private $descriptionByCode = array(
        self::SUCCESS => "No errors encountered",
        self::PROCESSING_ERROR => "Some error while processing",
        self::MISSING_DEVICE_TOKEN => "The device token is missing",
        self::MISSING_TOPIC => "The topic is missing",
        self::MISSING_PAYLOAD => "The payload is missing",
        self::INVALID_TOKEN_SIZE => "The token size is invalid",
        self::INVALID_TOPIC_SIZE => "The topic size is invalid",
        self::INVALID_PAYLOAD_SIZE => "The payload size is invalid",
        self::INVALID_TOKEN => "The token is invalid",
        self::SHUTDOWN => "The APNs server closed the connection",
        self::UNKNOWN => "Unknown error",
    );

    // Device id of the recipient iOs device
    private $recipient;
    // Basic title of the push notification
    private $title = PUSH_TITLE;
    // The push message that will be send to APSN server
    private $message;

    public function setMessage($to, $subject, $theme, $message, $from = false)
    {
        // Setting the receiver
        if (isset($to)) {
            $this->recipient = $to;
        }

        // Setting the title of the notification
        if (isset($subject)) {
            $this->title = $subject;
        }

        // Setting the message dictionary
        $this->message = array(
            "aps" => array(
                "alert" => array(
                    "title" => $this->title,
                    "body" => $message,
                ),
                "sound" => "default"
            ),
        );

        // The message must be encoded as JSON
        $this->message = json_encode($this->message);

        return isset($this->message);
    }

    public function getMessage()
    {
        if (isset($this->message)) {
            return $this->message;
        }

        return false;
    }

    /**
     * Redirect is used with non-native apps that are using the smartphone browser in order to open
     * the app. The redirect value contains the URL where the user will be taken when the notification
     * is received.
     * @param string $redirect The url where the user must be taken
     */
    public function addRedirect($redirect)
    {
        if (!isset($redirect) || empty($redirect)) {
            throw new PushApiException(PushApiException::NO_DATA, "Redirect is not set");
        }

        if (!isset($this->message)) {
            throw new PushApiException(PushApiException::NO_DATA, "Message must be created before adding redirect");
        }

        // If message is set, it should be a JSON string
        if (is_string($this->message) && is_object(json_decode($this->message))) {
            $this->message = json_decode($this->message, true);
            $this->message["data"]["url"] = $redirect;
            $this->message = json_encode($this->message);
            return true;
        }

        return false;
    }

    /**
     * Transforms the APNS response code into an improved description of the response (string description)
     * @param  int  $code  The APNS response code
     * @return string  The description of the response code
     */
    public function getResponseDescription($result)
    {
        if (!is_integer($result)) {
            // byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
            // NOTE: Make sure you set stream_set_blocking($fp, 0) or else fread will pause your script
            // and wait forever when there is no response to be sent.
            $apple_error_response = fread($result, 6);
            // $apple_error_response = fread($fp, 38);

           if ($apple_error_response) {
                //unpack the error response (first byte 'command" should always be 8)
                $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_error_response);

                if (isset($this->descriptionByCode[$error_response['status_code']])) {
                    return $this->descriptionByCode[$error_response['status_code'];
                } else {
                    return "Unexpected APNS error code";
                }
            }
        }
        return false;
    }

    /**
     * Obtains the right APNS server Url depending the environment
     */
    private function getServerUrl($feedBackUrl = false)
    {
        if (DEBUG) {
            if ($feedBackUrl) {
                return APNS_URL_FEEDBACK_DEVELOP;
            }
            return APNS_URL_DEVELOP;
        } else {
            if ($feedBackUrl) {
                return APNS_URL_FEEDBACK;
            }
            return APNS_URL;
        }
    }

    /**
     * Obtains the right certificate depending the environment
     */
    private function getCertificate()
    {
        if (DEBUG) {
            return CERTIFICATE_PATH_DEVELOP;
        } else {
            return CERTIFICATE_PATH;
        }
    }

    /**
     * Obtains the right password for the private key depending the environment
     */
    private function getPrivateKeyPassword()
    {
        if (DEBUG) {
            return CERTIFICATE_PASSPHRASE_DEVELOP;
        } else {
            return CERTIFICATE_PASSPHRASE;
        }
    }

    public function send()
    {
        if (!isset($this->message)) {
            throw new PushApiException(PushApiException::NO_DATA, "Can't send without push message created");
        }

        /**
         * General Provider Requirements:
         * As a provider you communicate with Apple Push Notification Service over a binary interface.
         * This interface is a high-speed, high-capacity interface for providers; it uses a streaming
         * TCP socket design in conjunction with binary content. The binary interface is asynchronous.
         */
        $ctx = stream_context_create();
        stream_context_set_option($ctx, "ssl", "local_cert", $this->getCertificate());
        stream_context_set_option($ctx, "ssl", "passphrase", $this->getPrivateKeyPassword());
        /**
         * This allows fread() to return right away when there are no errors. But it can also miss errors
         * during last seconds of sending, as there is a delay before error is returned. Workaround is to
         * pause briefly AFTER sending last notification, and then do one more fread() to see if anything
         * else is there.
         */
        stream_set_blocking($fp, 0);

        // Open a connection to the APNS server
        $fp = stream_socket_client($this->getServerUrl(), $err, $errstr, 60, STREAM_CLIENT_PERSISTENT, $ctx);

        if (!$fp) {
            throw new PushApiException(PushApiException::CONNECTION_FAILED, "iOs SSL connection failed: $err $errstr");
        }

        /**
         * Build the binary notification (see the structure)
         * @link https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/CommunicatingWIthAPS.html#//apple_ref/doc/uid/TP40008194-CH101-SW6
         * DeviceToken(32bytes) - Payload(<=2kilobytes) - NotificationIdentifier(4bytes) - ExpirationDate(4bytes) - Priority(1byte)
         */
        // $apple_identifier = 1;
        // $apple_expiry = time() + (90 * 24 * 60 * 60);
        // $msg = pack("C", 1) . pack("N", $apple_identifier) . pack("N", $apple_expiry) . pack("n", 32) . pack("H*", str_replace(' ', '', $this->recipient)) . pack("n", strlen($this->message)) . $this->message;
        $msg = chr(0) . pack("n", 32) . pack("H*", $this->recipient) . pack("n", strlen($this->message)) . $this->message;

        /**
         * Sending the message to the server
         *
         * INFO:
         * When you send a notification that is accepted by APNs, nothing is returned. When you
         * send a notification that is malformed or otherwise unintelligible, APNs returns an error-
         * response packet and closes the connection. Any notifications that you sent after the
         * malformed notification using the same connection are discarded, and must be resent.
         */
        // $result = fwrite($fp, $msg);
        $result = fwrite($fp, $msg, strlen($msg));

        // Close the connection to the server
        fclose($fp);

        return $result;
    }
}