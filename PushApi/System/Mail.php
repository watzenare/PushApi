<?php

namespace PushApi\System;

use \Slim\Log;
use \PushApi\PushApi;
use \PushApi\PushApiException;
use \PushApi\Models\Subject;

/**
 * @author Eloi Ballarà Madrid <eloi@tviso.com>
 * @copyright 2015 Eloi Ballarà Madrid <eloi@tviso.com>
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * Documentation @link https://push-api.readme.io/
 *
 * Manages the main functionalities that mailing service supplies.
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
        // Create the SMTP Transport
        $this->transport = \Swift_SmtpTransport::newInstance(MAIL_SERVER, MAIL_SERVER_PORT);

        // Using the localhost Transport
        // $this->transport = \Swift_MailTransport::newInstance();

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
            ->setSubject($subject);

        // If there is an HTML template set, it is edited to get the plain text
        if (isset($this->template)) {
            $this->message
                ->addPart($this->template, 'text/html')
                ->setBody($this->__makePlainText($text), 'text/plain');
        } else {
            $this->message->setBody($text, 'text/plain');
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
            PushApi::log(__METHOD__ . " - Can't send without mail message", Log::INFO);
            throw new PushApiException(PushApiException::NO_DATA, "Can't send without mail message");
        }

        try {
            $numSent = $this->mailer->send($this->message);
        } catch (\Exception $e) {
            // If something happens, restart the connection
            $this->mailer->getTransport()->stop();
            // Cooldown time
            sleep(5);
            PushApi::log(__METHOD__ . " - " . $e->getMessage() . ", restarting the mail connection", Log::INFO);
            return false;
        }

        return $numSent;
    }

    /**
     * Transforms the basic internal subject into an improved subject description
     * if it has been introduced before.
     * The subjects already found are loaded into a local variable in order to
     * avoid the overloading of the database.
     * @param  string $subject Encoded database subject that needs a translation
     * @return string The subject transformed
     */
    private function subjectTransformer($name)
    {
        if (isset($this->subjects[$name])) {
            return $this->subjects[$name];
        } else {
            try {
                $subject = Subject::getSubjectByThemeName($name);
            } catch (PushApiException $e) {
                PushApi::log(__METHOD__ . " - Subject not found, cannot be translated", Log::DEBUG);
                return false;
            }

            // Catching the subject values and returning the translation or returning the subject directly if there's no description.
            if (isset($subject)) {
                $this->subjects[$name] = $subject['description'];
                return $this->subjects[$name];
            } else {
                $this->subjects[$name] = $name;
                return $this->subjects[$name];
            }
        }
    }

    /**
     * Removes generates a plain text from an HTML template.
     * @param $text
     * @return mixed|string
     */
    private function __makePlainText($text)
    {
        // Converts all separator chars to a single space
        $text = preg_replace('/\s+/', " ", $text);

        // Removes style tag
        $text = preg_replace('/<style (.*?)<\/style>/', '', $text);

        // Adds new lines for block elements
        $text = preg_replace('/<(td|tr|div|p|table)/', "\n<$1", $text);

        // Removes HTML tags except links
        $text = trim(strip_tags($text, "<a>"));

        // Process links to obtain URL
        $text = preg_replace('/<a .*?href="(.+?)".*?>(.+?)<\/a>/', '$2 ($1)', $text);

        // Removes empty lines
        $text = preg_replace('/^\s+$/m', '', $text);

        return  $text;
    }
}
