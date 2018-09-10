<?php namespace Waavi\Mailman;

use Swift_Message;
use Illuminate\Queue\QueueManager;
use Illuminate\Log\Writer;
use Illuminate\Mail\Message;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssInline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use \App;
use Illuminate\Support\Facades\Event;

class Mailman {

	/**
	 * The application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app;

	/**
	 *	Css folder to use. By default this points to the folder specified in config.php
	 *	@var string
	 */
	protected $cssFolder;

	/**
	 *	Css file to use. By default this points to the file and folder specified in config.php
	 *	@var string
	 */
	protected $cssFile;

	/**
	 *	The Illuminate\Mail\Message instance.
	 *	@var array
	 */
	protected $message;

	/**
	 *	Data to render the email view.
	 *	@var array
	 */
	protected $data;

	/**
	 *	Selected locale for the email.
	 *	@var string(2)
	 */
	protected $locale;

	/**
	 * The Swift Mailer instance. Configured using app/mail.php
	 * @var Swift_Mailer
	 */
	protected $swift;

	/**
	 * The log writer instance.
	 *
	 * @var \Illuminate\Log\Writer
	 */
	protected $logger;

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
	protected $pretending = false;

	/**
	 *	Mailman constructor.
	 *	@param \Illuminate\Foundation\Application $app
	 *	@param string Path to the css file relative to the css folder as specified in config.php.
	 */
	public function __construct()
	{
		//$this->swift 			= App::make('swift.mailer');
		$this->message 		= new Message(new Swift_Message);
		$this->cssFolder 	= App::make('path.public').Config::get('nexogy/mailman::css.folder');
		$this->data 			= array();
		$this->locale 		= null;
		$this->pretending = Config::get('mail.pretend');
		$this->setCss(Config::get('nexogy/mailman::css.file'));
		$this->setQueue(App::make('queue'));
		$this->setLogger(App::make('log'));
		// Set from:
		if (Config::get('mail.from.address')) {
			$this->from(Config::get('mail.from.address'), Config::get('mail.from.address.name'));
		}
	}

	/**
	 *	Create a new Mailman instance.
	 *
	 *	@param string 	$view 	View name.
	 *	@param string   $css 		Css filename or path inside the css folder.
	 *	@return Waavi\Mailman current object instance, allows for method chaining.
	 */
	public static function make($view, $data = null)
	{
		$mailer = new static;
		$mailer->view = $view;
		$mailer->data = $data ?: [];
		return $mailer;
	}

	/**
	 * Add a piece of data to the view.
	 *
	 * @param  string|array  $key
	 * @param  mixed   $value
	 * @return \Illuminate\View\View
	 */
	public function with($key, $value = null)
	{
		if (is_array($key)) {
			$this->data = array_merge($this->data, $key);
		} else {
			$this->data[$key] = $value;
		}
		return $this;
	}

	/**
	 *	Set the message locale.
	 *	@param string $locale
	 *	@return Waavi\Mailman current object instance, allows for method chaining.
	 */
	public function setLocale($locale)
	{
		$this->locale = $locale;
		return $this;
	}

	/**
	 *	Set the css file to use.
	 *	@param string   $css 	Css filename or path inside the css folder.
	 *	@return Waavi\Mailman current object instance, allows for method chaining.
	 */
	public function setCss($css)
	{
		if ($css)	$this->cssFile = $this->cssFolder.'/'.$css;
		return $this;
	}

	/**
	 *	Return the mail as html.
	 *	@return string
	 */
	public function show()
	{
		// Set the email's locale:
		$currentLocale 	= Lang::getLocale();
		$newLocale 			= $this->locale ?: Lang::getLocale();
		Lang::setLocale($newLocale);

		// Generate HTML:
		$html 					= View::make($this->view, $this->data)->render();
		$css 						= File::get($this->cssFile);
		$inliner 				= new CssInline($html, $css);
		$body 					= $inliner->convert();

		// Return App locale to former value:
		Lang::setLocale($currentLocale);

		return $body;
	}

	/**
	 *	Get the Swift Message required to send an email.
	 *
	 *	@return Message
	 */
	protected function getMessageForSending()
	{
		$message 	= $this->message->getSwiftMessage();
		$message->setBody($this->show(), 'text/html');
		return $message;
	}

	/**
	 *	Return the mail as html.
	 *	@return boolean
	 */
	public function send($message = null, $safe_mode = false, $host = '', $port = '')
	{
		$message = $message ?: $this->getMessageForSending();
        Event::fire('mailer.sending', array($message));

		if($this->pretending){
			return $this->logMessage($message);
		} else {
			$mailer = App::make('mailer');

			if ($safe_mode) {
				$backup_mailer = $mailer->getSwiftMailer();
				$safe_transport = \Swift_SmtpTransport::newInstance($host, $port, '');
				$safe_mailer = new \Swift_Mailer($safe_transport);
				$mailer->setSwiftMailer($safe_mailer);
			} 
			$sender = $mailer->getSwiftMailer();

			$response = $sender->send($message);
			Event::fire('mailer.sent', array($message, $response));

			if ($safe_mode)
				$mailer->setSwiftMailer($backup_mailer);

			return $response;
		}
	}

	/**
	 * Set the log writer instance.
	 *
	 * @param  \Illuminate\Log\Writer  $logger
	 * @return \Illuminate\Mail\Mailer
	 */
	public function setLogger(Writer $logger)
	{
		$this->logger = $logger;
		return $this;
	}

	/**
	 * Log that a message was sent.
	 *
	 * @param  Swift_Message  $message
	 * @return void
	 */
	protected function logMessage($message)
	{
		$emails = implode(', ', array_keys($message->getTo()));

		$this->logger->info("Pretending to mail message to: {$emails}");
	}

	/**
	 * Set the queue manager instance.
	 *
	 * @param  \Illuminate\Queue\QueueManager  $queue
	 * @return \Illuminate\Mail\Mailer
	 */
	public function setQueue(QueueManager $queue)
	{
		$this->queue = $queue;
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
		if ($this->queue) {
			$this->queue->push('mailman@handleQueuedMessage', array('message' => serialize($this->getMessageForSending())), $queue);
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
		if ($this->queue) {
			$this->queue->later($delay, 'mailman@handleQueuedMessage', array('message' => serialize($this->getMessageForSending())), $queue);
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
	 *	Other functions like to, from, attach, etc... should be implemented through the Illuminate\Mail\Message class.
	 *	Route calls to these functions to the current Message object.
	 *	@param string $name Illuminate\Mail\Message method
	 *	@param mixed $arguments array or string of arguments.
	 *	@return Waavi\Mailman current object instance, allows for method chaining.
	 */
	public function __call($name, $arguments)
	{
		if (is_array($arguments)) {
			call_user_func_array(array($this->message, $name), $arguments);
		} else {
			call_user_func(array($this->message, $name), $arguments);
		}
		return $this;
	}
}
