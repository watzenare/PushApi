<?php

namespace PushApi\System;

use \PushApi\PushApiException;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Manages the main functionalities that mailing service suplies.
 */
class Mail
{
	const MAIL_FROM = "no-reply@your_email.com";

	private $transport;
	private $mailer;
	private $subjectsDictionary = array(
		'user_follow' => 'User followed you on Tviso',
		'user_comment' => 'User commented you on Tviso',
		'user_recommend' => 'User recommended Tviso content',
		'user_vote' => 'User voted your comment',
		'newsletter' => 'News from Tviso, read the message!',
		'movie_on_tv' => "Movie you're following on TV!",
		'queue_subject_1' => 'Improved subject 1',
		'queue_subject_2' => 'Improved subject 2',
		'add_yours' => 'add_yours'
	);
	private $message;

	public function __construct()
	{
		// // Create the Transport
		// $this->transport = Swift_SmtpTransport::newInstance('smtp.gmail.com', 587, 'tls')
		//   ->setUsername('username')
		//   ->setPassword('password');

		// Create the Transport
		$this->transport = \Swift_MailTransport::newInstance(MAIL_SERVER, MAIL_SERVER_PORT);

		// Create the Mailer using your created Transport
		$this->mailer = \Swift_Mailer::newInstance($this->transport);

		// // Rate limit to 60MB per-minute
		// $this->mailer->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
		// 1024 * 1024 * 60, \Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE
		// ));
	}

	/**
	 * Prepares the message that will be sent
	 * @param  string  $to      Adress wanted to send to
	 * @param  string  $subject Subject encoded from database wanted to send
	 * @param  string  $message Message wanted to display
	 * @param  string  $from    Adress wanted to send from
	 * @return boolean          Asserts if the message creation has worked succesfully
	 */
	public function message($to, $subject, $message, $from = false)
	{
		if (!$from) {
			$from = self::MAIL_FROM;
		}

	    $this->message = \Swift_Message::newInstance()
			->setFrom(array(
				$from => $from
			))
			->setTo(array(
				$to => $to
			))
			->setSubject($this->subjectTransformer($subject))
			->setBody($message);
		if (isset($this->message)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Sends a message to its destination given a message.
	 * @return int Number of mails sent
	 */
	public function send()
	{
		if (!isset($this->message)) {
			throw PushApiException(PushApiException::NO_DATA, "Can't send without mail message");
		}

		$numSent = $this->mailer->send($this->message);
	    return $numSent;
	}

	/**
	 * Transforms the basic internal subject into a better subject description if it is introduced
	 * @param  string $subject Encoded database subject that needs a translation
	 * @return string          The subject transformed
	 */
	private function subjectTransformer($subject)
	{
		// If there isn't a translation of the subject it is returned the original subject
		if (array_key_exists($subject, $this->subjectsDictionary)) {
			return $this->subjectsDictionary[$subject];
		} else {
			return $subject;
		}
	}
}