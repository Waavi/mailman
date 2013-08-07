<?php namespace Waavi\Mailman;

use Swift_Message;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssInline;
use Illuminate\Mail\Message;

class Mailman {

	/**
	 *	From email address.
	 *	@var string
	 */
	protected $app;

	protected $cssFolder;

	protected $cssFile;

	protected $message;

	/**
	 *	Mailman constructor.
	 *	@param \Illuminate\Foundation\Application $app
	 *	@param string Path to the css file relative to the css folder as specified in config.php.
	 */
	public function __construct($app, $css = null)
	{
		$this->app 				= $app;
		$this->swift 			= $this->app['swift.mailer'];
		$this->message 		= new Message(new Swift_Message);
		$this->cssFolder 	= $this->app['path.public'].$app['config']['waavi/mailman::css.folder'];
		$cssFile 					= $css ?: $app['config']['waavi/mailman::css.file'];
		$this->setCss($cssFile);
		// Set from:
		if (is_array($app['config']['mail.from']) && isset($app['config']['mail.from']['address'])) {
			$this->from($app['config']['mail.from']['address'], $app['config']['mail.from']['name']);
		}
	}

	/**
	 *	Create a new Mailman instance.
	 *	@param string 	$view 	View name.
	 *	@param string   $css 		Css filename or path inside the css folder.
	 *	@return Waavi\Mailman\Mailman current object instance, allows for method chaining.
	 */
	public function make($view, $css = null)
	{
		$this->view = $view;
		$this->setCss($css);
		return $this;
	}

	/**
	 *	Set the css file to use.
	 *	@param string   $css 	Css filename or path inside the css folder.
	 *	@return Waavi\Mailman\Mailman current object instance, allows for method chaining.
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
		$html 		= $this->app['view']->make($this->view)->render();
		$css 			= $this->app['files']->get($this->cssFile);
		$inliner 	= new CssInline($html, $css);
		return $inliner->convert();
	}

	/**
	 *	Return the mail as html.
	 *	@return boolean
	 */
	public function send()
	{
		$message 	= $this->message->getSwiftMessage();
		$message->setBody($this->show(), 'text/html');
		return $this->swift->send($message);
	}

	/**
	 *	Other functions like to, from, attach, etc... should be implemented through the Illuminate\Mail\Message class.
	 *	Route calls to these functions to the current Message object.
	 *	@param string $name Illuminate\Mail\Message method
	 *	@param mixed $arguments array or string of arguments.
	 *	@return Waavi\Mailman\Mailman current object instance, allows for method chaining.
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