<?php

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

use \PushApi\System\Android;
use \PushApi\Controllers\QueueController;

$android = new Android();
$queue = new QueueController();

/**
 * Pops all items from the android queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = $queue->getFromQueue(QueueController::ANDROID);
while ($data != null) {
    if ($android->setMessage($data->to, $data->subject, $data->message)) {
        $result = $android->send();
        error_log("Redis_android_queue: " . json_encode($data) . " GCM_result: " . $result . PHP_EOL, 3, PROD_SEND_LOG);
    }

    $data = $queue->getFromQueue(QueueController::ANDROID);
}