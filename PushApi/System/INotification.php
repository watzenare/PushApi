<?php

namespace PushApi\System;

use \PushApi\PushApiException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Contains the basic functions that all notifications emisors must implement
 * in order to get a better message definition.
 */
interface INotification
{
    /**
     * Prepares a message in order to be send.
     * @param  [string]  $to      Adress wanted to send to
     * @param  [string]  $subject Subject encoded from database wanted to send
     * @param  [string]  $message Message wanted to display
     * @param  [string]  $from    Adress wanted to send from
     * @return [boolean]          Asserts if the message creation has worked succesfully
     */
    public function setMessage($to, $subject, $theme, $message, $from = false);

    /**
     * Returns the message if it is already set
     * @return Swift_Message/json The message that depending of its destination can have a type or another
     */
    public function getMessage();

    /**
     * Sends a message to its destination given a prepared message.
     * @return [int] Number of mails sent
     */
    public function send();
}