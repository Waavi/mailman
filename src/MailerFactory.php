<?php namespace Waavi\Mailman;

use Illuminate\Container\Container;
use Illuminate\Mail\Message;
use Illuminate\Queue\QueueManager;
use Swift_Message;

class MailerFactory
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

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
     *    The Illuminate\Mail\Message instance.
     *    @var array
     */
    protected $message;

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
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $queue;

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
    public function __construct(Container $app)
    {
        $this->app = $app;
        $config    = array_merge($app->make('config')->get('mail'), $app->make('config')->get('mailman'));
        $this->loadConfig($config);
        $this->setQueue($app->make('queue'));
    }

    /**
     *  Load config file options
     *
     *  @param array $config
     *  @return void
     */
    protected function loadConfig(array $config)
    {
        $this->prentend = array_get($config, 'pretend', false);

        $this->cssFile = array_get($config, 'cssFile', 'resources/css/email.css');

        $this->defaultFromAddress = array_get($config, 'from.address', null);
        $this->defaultFromName    = array_get($config, 'from.name', null);
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
        $message       = new Message(new Swift_Message);
        $laravelMailer = $this->app->make('mailer')->getSwiftMailer();
        $logger        = $this->app->make('log');
        $viewFactory   = $this->app->make('view');
        $locale        = $this->app->make('lang')->getLocale();

        $mailer = new Mailer($laravelMailer, $logger, $viewFactory, $message, $view, $data, $locale, $this->pretend);
        $mailer
            ->setCss($this->cssFile)
            ->setQueueManager($this->queue);

        if ($this->defaultFromAddress) {
            $mailer->from($this->defaultFromAddress, $this->defaultFromName);
        }

        return $mailer;
    }
}
