<?php
/**
 * Worker that retrives data from the android queue, prepares the HTTP messsage in order
 * to send it to the Google Cloud Messaging server and sends the message. It is logged the
 * queue data and the result of sending the information.
 * Also it is updated user information if it has got a new registration_id, deletes user
 * registration id if this id has been deprecated or resends the notification if the target
 * can't be reached.
 */

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

use \PushApi\PushApi;
use \PushApi\Controllers\QueueController;

// Initializing the PushApi and it's services
$pushApi = (new PushApi(null));

$android = $pushApi->getContainerService(PushApi::ANDROID);
$queue = new QueueController();

/**
 * Pops all items from the android queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = $queue->getFromQueue(QueueController::ANDROID);
while ($data != null) {
    // If message is outdated, it is discard and another one is get
    if (!isset($data->timeToLive)
        || isset($data->timeToLive) && (strtotime($data->timeToLive) <= strtotime(Date("Y-m-d h:i:s a")))) {
        $data = $queue->getFromQueue(QueueController::ANDROID);
        continue;
    }

    // Checking if message has got delay time and if it can be sent or if it is not the time yet
    if (isset($data->delay) && (strtotime($data->delay) > strtotime(Date("Y-m-d h:i:s a")))) {
        // Add the notification to the queue again
        $queue->addToQueue($data, QueueController::ANDROID);
        // Get a new notification message
        $data = $queue->getFromQueue(QueueController::ANDROID);
        continue;
    }

    // Checking if there's set some customized subject
    if (isset($data->subject)) {
        $subject = $data->subject;
    } else {
        $subject = null;
    }

    if ($android->setMessage($data->to, $subject, $data->theme, $data->message)) {
        $android->addRedirect($data->redirect);
        $result = $android->send();
        error_log("Redis_android_queue: " . json_encode($data) . " GCM_result: " . $result . PHP_EOL, 3, ANDROID_SEND_LOG);

        $result = json_decode($result);
        if ($result->failure != 0 || $result->canonical_ids != 0) {
            $android->checkResults($data->to, $result->results);
        }
    }

    $data = $queue->getFromQueue(QueueController::ANDROID);
}