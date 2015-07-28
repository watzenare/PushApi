<?php
/**
 * Worker that retrives data from the iOs queue, prepares the HTTP messsage in order
 * to send it to the APNS server who will send the message to its target. It is logged the
 * queue data and the result of sending the information.
 */

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Controllers\QueueController;

// Initializing the PushApi and it's services
$pushApi = (new PushApi(null));

$ios = $pushApi->getContainerService(PushApi::IOS);
$queue = new QueueController();

/**
 * Pops all items from the iOs queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = $queue->getFromQueue(QueueController::IOS);
while ($data != null) {
    // If message is outdated, it is discard and another one is get
    if (!isset($data->timeToLive)
        || isset($data->timeToLive) && (strtotime($data->timeToLive) <= strtotime(Date("Y-m-d h:i:s a")))) {
        $data = $queue->getFromQueue(QueueController::IOS);
        continue;
    }

    // Checking if message has got delay time and if it can be sent or if it is not the time yet
    if (isset($data->delay) && (strtotime($data->delay) > strtotime(Date("Y-m-d h:i:s a")))) {
        // Add the notification to the queue again
        $queue->addToQueue($data, QueueController::IOS);
        // Get a new notification message
        $data = $queue->getFromQueue(QueueController::IOS);
        continue;
    }

    // Checking if there's set some customized subject
    if (isset($data->subject)) {
        $subject = $data->subject;
    } else {
        $subject = null;
    }

    try {
        if ($ios->setMessage($data->to, $subject, $data->theme, $data->message)) {
            if (isset($data->redirect)) {
                $ios->addRedirect($data->redirect);
            }
            $result = $ios->send();
            error_log("Redis_ios_queue: " . json_encode($data) . " GCM_result: " . $result . PHP_EOL, 3, IOS_SEND_LOG);
        }
    } catch (PushApiException $e) {
        error_log("Redis_ios_queue: " . json_encode($data) . "Error: " . $e->getMessage() . PHP_EOL, 3, IOS_SEND_LOG);
    }

    $data = $queue->getFromQueue(QueueController::IOS);
}