<?php namespace Waavi\Mailman;

use Illuminate\Queue\QueueManager;
use Swift_Mailer;

class Mailer
{
    /**
     *  Swift Mailer
     *  @var Swift_Mailer
     */
    protected $mailer;

    /**
     *  The Message instance.
     *  @var Message
     */
    protected $message;

    /**
     *  The queue manager instance.
     *
     *  @var \Illuminate\Queue\QueueManager
     */
    protected $queueManager;

    /**
     *    Mailman constructor.
     *    @param \Illuminate\Foundation\Application $app
     *    @param string Path to the css file relative to the css folder as specified in config.php.
     */
    public function __construct(Swift_Mailer $mailer, Message $message)
    {
        $this->mailer  = $mailer;
        $this->message = $message;
    }

    /**
     *  Set the queue manager instance.
     *
     *  @param  \Illuminate\Queue\QueueManager  $queue
     *  @return self
     */
    public function setQueueManager(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
        return $this;
    }

    /**
     *  Return the HTML representation of the email to be sent.
     *
     *  @return string
     */
    public function show()
    {
        return $this->message->getBody();
    }

    /**
     *  Send the email.
     *
     *  @param  Swift_Message   Optional
     *  @return boolean
     */
    public function send()
    {
        $swiftMessage = $this->message->getSwiftMessage();
        return $this->mailer->send($swiftMessage);
    }

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string  $queue
     * @return void
     */
    public function queue($queue = null)
    {
        if ($this->queueManager) {
            $this->queueManager->push(new SendEmailJob($message->getSwiftMessage()), $queue);
        }
    }

    /**
     * Queue a new e-mail message for sending on the given queue.
     *
     * @param  string  $queue
     * @return void
     */
    public function queueOn($queue)
    {
        return $this->queue($queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  int  $delay
     * @param  string  $queue
     * @return void
     */
    public function later($delay, $queue = null)
    {
        if ($this->queueManager) {
            $this->queueManager->later($delay, new SendEmailJob($message->getSwiftMessage()), $queue);
        }
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds on the given queue.
     *
     * @param  string  $queue
     * @param  int  $delay
     * @return void
     */
    public function laterOn($queue, $delay)
    {
        return $this->later($delay, $queue);
    }

    /**
     *    Other functions like to, from, attach, etc... should be implemented through the Illuminate\Mail\Message class.
     *    Route calls these methods on the current Message object.
     *
     *    @param string $name Illuminate\Mail\Message method
     *    @param mixed $arguments array or string of arguments.
     *    @return App\Utils\Mailman current object instance, allows for method chaining.
     */
    public function __call($name, $arguments)
    {
        if (is_array($arguments)) {
            call_user_func_array([$this->message, $name], $arguments);
        } else {
            call_user_func([$this->message, $name], $arguments);
        }
        return $this;
    }
}
