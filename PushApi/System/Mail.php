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
    private $template = null;

    private $message;

    public function __construct()
    {
        // Mail
        $this->transport = \Swift_MailTransport::newInstance();

        // Other ways to create the Transport:
        // $this->transport = \Swift_MailTransport::newInstance(MAIL_SERVER, MAIL_SERVER_PORT);

        // Sendmail
        // $this->transport = \Swift_SendmailTransport::newInstance("/usr/sbin/sendmail -t");
        // $this->transport = \Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');

        // Create the Mailer using your created Transport
        $this->mailer = \Swift_Mailer::newInstance($this->transport);

        // Rate limit to 250 emails per-minute
        $this->mailer->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
          250, \Swift_Plugins_ThrottlerPlugin::MESSAGES_PER_MINUTE
        ));

        // Rate limit to 60MB per-minute
        $this->mailer->registerPlugin(new \Swift_Plugins_ThrottlerPlugin(
            1024 * 1024 * 60, \Swift_Plugins_ThrottlerPlugin::BYTES_PER_MINUTE
        ));
    }

    public function setMessage($to, $subject, $theme, $text, $from = false)
    {
        if (!$from) {
            $from = MAIL_FROM;
        }

        // If there isn not set a customized subject, it is displayed a default subject
        if (!isset($subject)) {
            $subject = $this->subjectTransformer($theme);
        }

        $this->message = \Swift_Message::newInstance();
        $this->message
            ->setReturnPath($from)
            ->setReplyTo($from);
        $this->message
            ->setFrom(array(
                $from => $from
            ))
            ->setTo(array(
                $to => $to
            ))
            ->setSubject($subject)
            ->setBody($text);

        if (isset($this->template)) {
            $this->message->addPart($this->template, 'text/html');
        }

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

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function send()
    {
        if (!isset($this->message)) {
            throw new PushApiException(PushApiException::NO_DATA, "Can't send without mail message");
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