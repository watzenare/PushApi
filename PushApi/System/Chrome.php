<?php

namespace PushApi\System;

use \PushApi\PushApiException;
use \PushApi\System\INotification;
use \PushApi\Models\User;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Manages the main functionalities that handles android notifications sending.
 *
 * Google Note: If your organization has a firewall that restricts the traffic to or from the Internet,
 * you need to configure it to allow connectivity with GCM in order for your Chrome app/extension to
 * receive messages. The ports to open are: 5228, 5229, and 5230. GCM typically only uses 5228,
 * but it sometimes uses 5229 and 5230. GCM doesn't provide specific IPs, so you should allow your
 * firewall to accept outgoing connections to all IP addresses contained in the IP blocks listed in
 * Google's ASN of 15169.
 */
class Chrome implements INotification
{
    public function setMessage($to, $subject, $theme, $message, $from = false)
    {

    }

    public function getMessage()
    {

    }

    public function send()
    {

    }

}