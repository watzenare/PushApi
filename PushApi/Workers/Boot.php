<?php

// Include configurations and global PushApi constants
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'BootStrap.php';

// $transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 587, 'tls')
//   ->setUsername('username')
//   ->setPassword('password');

// Create the Transport
$transport = Swift_MailTransport::newInstance(MAIL_SERVER, MAIL_SERVER_PORT);

// Create the Mailer using your created Transport
$mailer = Swift_Mailer::newInstance($transport);

$mailFrom = 'no-reply@tviso.com';

$subjectConversion = array(
	'user_follow' => 'User followed you on Tviso',
	'user_comment' => 'User commented you on Tviso',
	'user_recommend' => 'User recommended Tviso content',
	'user_vote' => 'User voted your comment',
	'newsletter' => 'News from Tviso, read the message!',
	'movie_on_tv' => "Movie you're following on TV!",
	'queue_subject' => 'Improved subject'
);