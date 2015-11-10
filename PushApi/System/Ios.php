<?php

namespace PushApi\System;

use \PushApi\PushApiException;
use \PushApi\System\INotification;
use \PushApi\Models\User;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
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
    private $appleExpiry;
    private $appleIdentifiersList = [];

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
                "sound" => "default",
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
            $this->message["aps"]["payload"]["url"] = $redirect;
            $this->message = json_encode($this->message);
            return true;
        }

        return false;
    }


    /**
     * Obtains the user id from the DB and caches it into a local array variable
     * @param  string $deviceToken The target device token
     * @return int              The push user id
     */
    private function getIdentifier($deviceToken)
    {
        if (!isset($this->appleIdentifiersList[$deviceToken])) {
            $user = new User;
            $user = User::where('ios_id', $deviceToken)->first();
            $this->appleIdentifiersList[$deviceToken] = (int) $user->id;
        }

        return $this->appleIdentifiersList[$deviceToken];
    }

    /**
     * Returns the expiry time of the message which can be alive
     * @return integer The expiry time of the message
     */
    private function getExpiry()
    {
        if (isset($this->appleExpiry)) {
            return $this->appleExpiry;
        } else {
            $this->appleExpiry = time() + (90 * 24 * 60 * 60);
        }

        return $this->appleExpiry;
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
    private function getCertificateFile()
    {
        if (DEBUG) {
            return ROOT_DIR . DIRECTORY_SEPARATOR . CERTIFICATE_PATH_DEVELOP;
        } else {
            return ROOT_DIR . DIRECTORY_SEPARATOR . CERTIFICATE_PATH;
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

    /**
     * Building the binary notification (see the structure) and sending to its receiver/s
     * @link https://developer.apple.com/library/ios/documentation/NetworkingInternet/Conceptual/RemoteNotificationsPG/Chapters/CommunicatingWIthAPS.html#//apple_ref/doc/uid/TP40008194-CH101-SW6
     * DeviceToken(32bytes) - Payload(<=2kilobytes) - NotificationIdentifier(4bytes) - ExpirationDate(4bytes) - Priority(1byte)
     * @param  stirng $deviceToken The target device token that will receive the message
     * @param  string $payload     A json with the message that will be send to APNS
     * @return string              The apple message constructed
     */
    private function generateBinaryMessage($deviceToken, $payload)
    {
        if (!isset($deviceToken) || !isset($payload)) {
            throw new PushApiException(PushApiException::NO_DATA);
        }

        return pack("C", 1) . pack("N", $this->getIdentifier($deviceToken)) . pack("N", $this->getExpiry()) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;
    }

    /**
     * Sending the message to the server
     *
     * INFO:
     * When you send a notification that is accepted by APNs, nothing is returned. When you
     * send a notification that is malformed or otherwise unintelligible, APNs returns an error-
     * response packet and closes the connection. Any notifications that you sent after the
     * malformed notification using the same connection are discarded, and must be resent.
     * @param  Socket $apns        The connection to APNS
     * @param  ApnsMessage $apnsMessage The Binary APNS message
     * @return Array              An array with the response after sending the message
     */
    private function sendToApns($apns, $apnsMessage)
    {
        fwrite($apns, $apnsMessage, strlen($apnsMessage));
        return $this->getResponseDescription($apns);
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
        stream_context_set_option($ctx, 'ssl', 'local_cert', $this->getCertificateFile());
        stream_context_set_option($ctx, 'ssl', 'passphrase', $this->getPrivateKeyPassword());

        // Open a connection to the APNS server
        $apns = stream_socket_client($this->getServerUrl(), $error, $errorString, 2, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

        /**
         * This allows fread() to return right away when there are no errors. But it can also miss errors
         * during last seconds of sending, as there is a delay before error is returned. Workaround is to
         * pause briefly AFTER sending last notification, and then do one more fread() to see if anything
         * else is there.
         */
        stream_set_blocking($apns, 0);

        if (!$apns) {
            throw new PushApiException(PushApiException::CONNECTION_FAILED, "iOs SSL connection failed: $error $errorString");
        }
        // Checking receiver/s && sending the message && check the response
        if (is_array($this->recipient)) {
            foreach($this->recipient as $deviceToken) {
                $apnsMessage = $this->generateBinaryMessage($deviceToken, $this->message);
                $response = $this->sendToApns($apns, $apnsMessage);
                if ($response['code'] != self::SUCCESS) {
                    $response['deviceToken'] = $deviceToken;
                    $result[] = $response;
                }
            }
        } else {
            $apnsMessage = $this->generateBinaryMessage($this->recipient, $this->message);
            $response = $this->sendToApns($apns, $apnsMessage);
            if ($response['code'] != self::SUCCESS) {
                $response['deviceToken'] = $deviceToken;
                $result[] = $response;
            }
        }

        // Close the connection to the server
        fclose($apns);

        return (isset($result) ? $result : true);
    }

    /**
     * Transforms the APNS response code into an improved description of the response (string description)
     * @param  int  $response  The APNS response code
     * @return string  The description of the response code
     */
    private function getResponseDescription($response)
    {
        // byte1=always 8, byte2=StatusCode, bytes3,4,5,6=identifier(rowID). Should return nothing if OK.
        // NOTE: Make sure you set stream_set_blocking($apns, 0) or else fread will pause your script
        // and wait forever when there is no response to be sent.
        $response = fread($response, 6);

        if ($response) {
            //unpack the error response (first byte 'command" should always be 8)
            $errorResponse = unpack('Ccommand/Cstatus_code/Nidentifier', $response);

            $error = array(
                'command' => $errorResponse['command'],
                'id' => $errorResponse['identifier'],
                'code' => $errorResponse['status_code'],
            );

            if (isset($this->descriptionByCode[$errorResponse['status_code']])) {
                    $error['message'] = $this->descriptionByCode[$errorResponse['status_code']];
                } else {
                    $error['message'] = "Unexpected APNS error code";
                }

            return $error;
        }

        return array(
            'code' => (int) $response
        );
    }

    /**
     * Review the errors with the feedback of APNS
     * @param  array $result The array of the errors that APNS has warned
     * @return boolean         The result of the validation
     */
    public function checkResults($result)
    {
        if (!is_array($result)) {
            return false;
        }

        foreach ($result as $target) {
            if ($target['code'] == self::INVALID_TOKEN || $target['code'] == self::INVALID_TOKEN_SIZE) {
                // Deleting the user token
                $user = new User;
                $user = User::where('ios_id', $target['deviceToken'])->first();
                if (isset($user)) {
                    $user->ios_id = "0";
                    $user->update();
                }
            }
        }
    }
}