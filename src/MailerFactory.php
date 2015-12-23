<?php namespace Waavi\Mailman;

use Illuminate\Mail\Message;
use Illuminate\Queue\QueueManager;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory as ViewFactory;
use lluminate\Filesystem\Filesystem;
use \Swift_Mailer;
use \Swift_Message;

class MailerFactory
{
    /**
     *    Css file to use. By default this points to the file and folder specified in config.php
     *    @var string
     */
    protected $cssFile;

    /**
     *  Default from address
     *  @var string
     */
    protected $defaultFromAddress = null;

    /**
     *  Default from name
     *  @var string
     */
    protected $defaultFromName = null;

    /**
     *    Selected locale for the email.
     *    @var string(2)
     */
    protected $defaultLocale = null;

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $queueManager;

    /**
     * Indicates if the actual sending is disabled.
     *
     * @var bool
     */
    protected $prentend = false;

    /**
     *  @var \Swift_Mailer
     */
    protected $swiftMailer;

    /**
     *  @var Illuminate\View\Factory
     */
    protected $viewFactory;

    /**
     *  @var Filesystem
     */
    protected $filesystem;

    /**
     *    Mailman constructor.
     *    @param \Illuminate\Foundation\Application $app
     *    @param string Path to the css file relative to the css folder as specified in config.php.
     */
    public function __construct(Config $config, Translator $translator, Filesystem $filesystem, QueueManager $queueManager, Swift_Mailer $swiftMailer, ViewFactory $viewFactory)
    {
        $this->cssFile            = $config->get('mailman.cssFile');
        $this->defaultFromAddress = $config->get('mail.from.address');
        $this->defaultFromName    = $config->get('mail.from.name');
        $this->defaultLocale      = $config->get('app.locale');
        $this->translator         = $translator;
        $this->filesystem         = $filesystem;
        $this->queueManager       = $queueManager;
        $this->swiftMailer        = $swiftMailer;
        $this->viewFactory        = $viewFactory;
    }

    /**
     *  Load the given view and data
     *
     *  @param  string   $view     View name.
     *  @param  string   $data     Data to be used to render the view.
     *  @return Mailman  Current object instance, allows for method chaining.
     */
    public function make($view, $data = null)
    {
        $message = new Message(new Swift_Message);
        $mailer  = new Mailer($this->swiftMailer, $this->filesystem, $this->viewFactory, $message, $view, $data, $this->defaultLocale);
        $mailer->setCss($this->cssFile)->setQueueManager($this->queueManager);

        if ($this->defaultFromAddress) {
            $mailer->from($this->defaultFromAddress, $this->defaultFromName);
        }

        return $mailer;
    }
}
