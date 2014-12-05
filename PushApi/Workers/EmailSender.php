<?php

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

use \PushApi\System\Mail;
use \PushApi\Controllers\QueueController;

$mail = new Mail();
$queue = new QueueController();

/**
 * Pops all items from the mail queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = $queue->getFromQueue(QueueController::EMAIL);
$data = json_decode($data, true);
while ($data != null) {
    if ($mail->message($data['to'], $data['subject'], $data['message'])) {
        $numSent = $mail->send();
        printf("Message sent to: " . $data['to'] ."\n", $numSent);
    }

    $data = $queue->getFromQueue(QueueController::EMAIL);
    $data = json_decode($data, true);
}