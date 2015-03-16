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
    if (isset($template)) {
        // Replacing the template text content with the notification message and adding it into the message
        $mailTemplate = str_replace("<textNotificationMessage>", $data->message, $template);
        $trackingParams = '<img src="http://pushapi.com:90/tracking/px.gif?receiver=' . urlencode($data->to) . '&amp;theme=' . $data->subject . '">';
        $mailTemplate = str_replace("<trackingParams>", $trackingParams, $mailTemplate);
        $mail->setTemplate($mailTemplate);
    }

    if ($mail->setMessage($data->to, $data->subject, $data->message)) {
        $result = $mail->send();
        error_log("Redis_mail_queue: " . json_encode($data) . " Send_result: " . $result . PHP_EOL, 3, PROD_SEND_LOG);
    }

    $data = $queue->getFromQueue(QueueController::EMAIL);
}
