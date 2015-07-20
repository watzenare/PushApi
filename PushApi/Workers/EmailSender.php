<?php

/**
 * Worker that retrives data from the mail queue, transforms that information into a mail
 * message and sends the message to its receiver. It is stored the data and the result of
 * sending the message.
 */

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

// Geting the mail template and adding it into the message
ob_start();
include_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "templates/mail.html";
$template = ob_get_contents();
ob_end_clean();

use \PushApi\PushApi;
use \PushApi\Controllers\QueueController;

// Initializing the PushApi and it's services
$pushApi = new PushApi(null);

$mail = $pushApi->getContainerService(PushApi::MAIL);
$queue = new QueueController();

/**
 * Pops all items from the mail queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = $queue->getFromQueue(QueueController::EMAIL);
while ($data != null) {
    // If message is outdated, it is discard and another one is get
    if (!isset($data->timeToLive)
        || isset($data->timeToLive) && (strtotime($data->timeToLive) <= strtotime(Date("Y-m-d h:i:s a")))) {
        $data = $queue->getFromQueue(QueueController::EMAIL);
        continue;
    }

    // Checking if message has got delay time and if it can be sent or if it is not the time yet
    if (isset($data->delay) && (strtotime($data->delay) > strtotime(Date("Y-m-d h:i:s a")))) {
        // Add the notification to the queue again
        $queue->addToQueue($data, QueueController::EMAIL);
        // Get a new notification message
        $data = $queue->getFromQueue(QueueController::EMAIL);
        continue;
    }

    if (isset($template)) {
        // Replacing the template text content with the notification message and adding it into the message
        $mailTemplate = str_replace("<textNotificationMessage>", $data->message, $template);
        $trackingParams = '<img src="http://pushapi.com:90/tracking/px.gif?receiver=' . urlencode($data->to) . '&amp;theme=' . $data->theme . '">';
        $mailTemplate = str_replace("<trackingParams>", $trackingParams, $mailTemplate);
        $mail->setTemplate($mailTemplate);
    }

    // Checking if there's set some customized subject from the mail
    if (isset($data->subject)) {
        $subject = $data->subject;
    } else {
        $subject = null;
    }

    if ($mail->setMessage($data->to, $subject, $data->theme, $data->message)) {
        $result = $mail->send();
        error_log("Redis_mail_queue: " . json_encode($data) . " Send_result: " . $result . PHP_EOL, 3, PROD_SEND_LOG);
    }

    $data = $queue->getFromQueue(QueueController::EMAIL);
}
