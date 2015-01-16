<?php

namespace PushApi\System;

use \PushApi\PushApiException;
use \PushApi\System\INotification;
use \PushApi\Models\Theme;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 *
 * Manages the main functionalities that mailing service suplies.
 */
class Mail implements INotification
{
    private $transport;
    private $mailer;
    private $subjects;

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

        // Rate limit to 60MB per-minute
        $this->mailer->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
            1024 * 1024 * 60, \Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE
        ));
    }

    public function setMessage($to, $subject, $message, $from = false)
    {
        if (!$from) {
            $from = MAIL_FROM;
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

    public function getMessage()
    {
        if (isset($this->message)) {
            return $this->message;
        }
        return false;
    }

    public function send()
    {
        if (!isset($this->message)) {
            throw PushApiException(PushApiException::NO_DATA, "Can't send without mail message");
        }

        $numSent = $this->mailer->send($this->message);
        return $numSent;
    }

    /**
     * Transforms the basic internal subject into an improved subject description
     * if it has been introduced before.
     * The subjects already found are loaded into a local variable in order to
     * avoid the overloading of the database.
     * @param  [string] $subject Encoded database subject that needs a translation
     * @return [string] The subject transformed
     */
    private function subjectTransformer($name)
    {
        if (isset($this->subjects[$name])) {
            return $this->subjects[$name];
        } else {
            try {
                $subject = Theme::where('name', $name)->first()->subject;
            } catch (ModelNotFoundException $e) {
                throw new PushApiException(PushApiException::NOT_FOUND);
            }

            // Catching the subject values and returning the translation
            if (isset($subject)) {
                $subject = $subject->toArray();
                $this->subjects[$name] = $subject['description'];
                return $this->subjects[$name];
            } else {
                $this->subjects[$name] = $name;
                return $this->subjects[$name];
            }
        }
    }
}