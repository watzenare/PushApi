<?php

require 'Boot.php';

use \PushApi\Controllers\QueueController;

// // Mockobject
// $data = array(
//   "to" => "deivit@series.ly",
//   "subject" => "user_follow",
//   "message" => "A user has followed you. You can check it on Tviso webpage."
// );

/**
 * Pops all items from the mail queue and sends messages to the right destination,
 * when there are no more messages into the queue, it dies.
 */
$data = (new QueueController())->getFromQueue(QueueController::EMAIL);
$data = json_decode($data, true);

while ($data != null) {
    // Create the message
    $message = Swift_Message::newInstance()

      // Set the From address with an associative array
      ->setFrom($mailFrom)

      // Set the To addresses with an associative array
      ->setTo($data['to'])

      // Give the message a subject
      ->setSubject($subjectConversion[$data['subject']])

      // Give it a body
      ->setBody($data['message']);

    // Send the message
    $numSent = $mailer->send($message);

    printf("Message sent to " . $data['to'] . "\n", $numSent);

    $data = (new QueueController())->getFromQueue(QueueController::EMAIL);
    $data = json_decode($data, true);
}