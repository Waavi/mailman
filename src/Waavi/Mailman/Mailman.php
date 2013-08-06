<?php namespace Waavi\Mailman;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles as CssInline;

class Mailman {

	/**
	 *	From email address.
	 *	@var string
	 */
	protected $from;

	/**
	 *
	 *	@param \Illuminate\Foundation\Application $app
	 */
	public function __construct($app, $css = null)
	{
		$this->app = $app;
	}

	public function make($view)
	{
		$this->view = $view;
		return $this;
	}

	public function from($from)
	{
		$this->from = $from;
		return $this;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function show()
	{
		$html = $this->app['view']->make($this->view)->render();
		$css = $this->app['files']->get($this->app['path.public'].'/assets/css/email.css');
		$inliner = new CssInline($html, $css);
		return $inliner->convert();
		return $this->app['view']->make($this->view)->render();
	}


}