<?php namespace Waavi\Mailman;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Log\Writer as LogWriter;
use Illuminate\Mail\Message;
use Illuminate\Queue\QueueManager;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory as ViewFactory;
use Swift_Mailer;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssInline;

class Mailer implements MailerContract, MailQueueContract
{
    /**
     *  Swift Mailer
     *  @var Swift_Mailer
     */
    protected $mailer;

    /**
     *  Log Writer used when pretend is set to true.
     *  @var Illuminate\Log\Writer
     */
    protected $logWriter;

    /**
     *  Laravel Filsystem
     *  @var Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     *  Laravel Translator
     *  @var Illuminate\Translation\Translator
     */
    protected $translator;

    /**
     *  Laravel View Factory
     *  @var Illuminate\View\Factory
     */
    protected $viewFactory;

    /**
     *    The Message instance.
     *    @var Illuminate\Mail\Message
     */
    protected $message;

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $queueManager;

    /**
     *    Css folder to use. By default this points to the folder specified in config.php
     *    @var string
     */
    protected $cssFolder;

    /**
     *    Css file to use. By default this points to the file and folder specified in config.php
     *    @var string
     */
    protected $cssFile;

    /**
     *  The view to load
     *  @var string
     */
    protected $view;

    /**
     *    Data to render the email view.
     *    @var array
     */
    protected $data = [];

    /**
     *    Selected locale for the email.
     *    @var string(2)
     */
    protected $locale = null;

    /**
     * Indicates if the actual sending is disabled.
     *
     * @var bool
     */
    protected $prentend = false;

    /**
     *    Mailman constructor.
     *    @param \Illuminate\Foundation\Application $app
     *    @param string Path to the css file relative to the css folder as specified in config.php.
     */
    public function __construct(
        Swift_Mailer $mailer,
        LogWriter    $logWriter,
        Filesystem   $filesystem,
        Translator   $translator,
        ViewFactory  $viewFactory,
        Message      $message,
                     $view,
                     $data,
                     $locale,
                     $pretend) {
        $this->mailer      = $mailer;
        $this->logWriter   = $logWriter;
        $this->filesystem  = $filesystem;
        $this->translator  = $translator;
        $this->viewFactory = $viewFactory;
        $this->message     = $message;
        $this->view        = $view;
        $this->data        = $data;
        $this->locale      = $locale;
        $this->pretend     = $pretend;
    }

    /**
     * Add data to the view. Works just like Laravel's View::with
     *
     * @param  string|array     $key
     * @param  mixed            $value
     * @return \Illuminate\View\View
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_replace_recursive($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }

    /**
     *  Set the locale, by default the current locale will be used.
     *
     *  @param string $locale
     *  @return App\Utils\Mailman current object instance, allows for method chaining.
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     *    Set the css file to use, relative to the css folder.
     *
     *    @param string   $css     Css relative path inside the css folder.
     *    @return Mailman Current object instance, allows for method chaining.
     */
    public function setCss($css)
    {
        $this->cssFile = $css;
        return $this;
    }

    /**
     *    Return the HTML representation of the email to be sent.
     *
     *    @return string
     */
    public function show()
    {
        // Set the email's locale:
        $currentLocale = $this->translator->getLocale();
        $locale        = $this->locale ?: $currentLocale;
        $this->translator->setLocale($locale);

        // Generate HTML:
        $html    = $this->viewFactory->make($this->view, $this->data)->render();
        $css     = $this->filesystem->get($this->cssFile);
        $inliner = new CssInline($html, $css);
        $body    = $inliner->convert();

        // Return App locale to former value:
        $this->translator->setLocale($currentLocale);

        return $body;
    }

    /**
     *    Get the Swift Message required to send an email.
     *
     *    @return Message
     */
    protected function getMessageForSending()
    {
        $message = $this->message->getSwiftMessage();
        $message->setBody($this->show(), 'text/html');
        return $message;
    }

    /**
     *    Return the mail as html.
     *    @return boolean
     */
    public function send($message = null)
    {
        $message = $message ?: $this->getMessageForSending();

        return $this->prentend ? $this->logMessage($message) : $this->mailer->send($message);
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Queue\QueueManager  $queue
     * @return \Illuminate\Mail\Mailer
     */
    public function setQueueManager(QueueManager $queueManager)
    {
        $this->queueManager = $queueManager;
        return $this;
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
            $this->queueManager->push('mailman@handleQueuedMessage', ['message' => serialize($this->getMessageForSending())], $queue);
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
        return $this->queueManager($queue);
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
            $this->queueManager->later($delay, 'mailman@handleQueuedMessage', ['message' => serialize($this->getMessageForSending())], $queue);
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
     * Handle a queued e-mail message job.
     *
     * @param  \Illuminate\Queue\Jobs\Job  $job
     * @param  array  $data
     * @return void
     */
    public function handleQueuedMessage($job, $data)
    {
        if (is_array($data) && isset($data['message']) && $data['message']) {
            $message = unserialize($data['message']);
            $this->send($message);
        }
        $job->delete();
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
