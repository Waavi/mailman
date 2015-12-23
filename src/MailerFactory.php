<?php namespace Waavi\Mailman;

use Illuminate\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\QueueManager;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory as ViewFactory;
use \Swift_Mailer;
use \Swift_Message;

class MailerFactory
{
    /**
     *    Mailman constructor.
     *    @param \Illuminate\Foundation\Application $app
     *    @param string Path to the css file relative to the css folder as specified in config.php.
     */
    public function __construct(Config $config, Translator $translator, Filesystem $filesystem, QueueManager $queueManager, Swift_Mailer $swiftMailer, ViewFactory $viewFactory)
    {
        $this->config       = $config;
        $this->translator   = $translator;
        $this->filesystem   = $filesystem;
        $this->queueManager = $queueManager;
        $this->swiftMailer  = $swiftMailer;
        $this->viewFactory  = $viewFactory;
    }

    /**
     *  Load the given view and data
     *
     *  @param  string   $view     View name.
     *  @param  string   $data     Data to be used to render the view.
     *  @return Mailman  Current object instance, allows for method chaining.
     */
    public function make($view, $data = [])
    {
        $message = new Message(new Swift_Message, $this->viewFactory, $this->filesystem, $this->translator);
        $message->setView($view);
        $message->with($data);
        $message->setCss($this->config->get('mailman.cssFile'));
        $fromAddress = $this->config->get('mail.from.address');
        $fromName    = $this->config->get('mail.from.name');
        if ($fromAddress) {
            $message->from($fromAddress, $fromName);
        }

        $mailer = new Mailer($this->swiftMailer, $message);
        $mailer->setQueueManager($this->queueManager);

        return $mailer;
    }
}
