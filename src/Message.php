<?php

namespace Waavi\Mailman;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Mail\Message as LaravelMessage;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory as ViewFactory;
use Swift_Message;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class Message extends LaravelMessage
{
    /**
     * The Swift Message instance.
     *
     * @var \Swift_Message
     */
    protected $swift;

    /**
     *  Laravel View Factory
     *
     *  @var Illuminate\View\Factory
     */
    protected $viewFactory;

    /**
     *  Laravel Filsystem
     *
     *  @var Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     *  Laravel Translator
     *
     *  @var Illuminate\Translation\Translator
     */
    protected $translator;

    /**
     *  View to be render in the message
     *
     *  @var string
     */
    protected $view;

    /**
     *  Data used to render the message
     *
     *  @var array
     */
    protected $data;

    /**
     *  Message locale. The current locale will be used if none is set.
     *
     *  @var string
     */
    protected $locale;

    /**
     *  Path to css file
     *
     *  @var string
     */
    protected $cssPath;

    /**
     * Create a new message instance.
     *
     * @param  \Swift_Message  $swift
     * @return void
     */
    public function __construct(Swift_Message $swift, ViewFactory $viewFactory, Filesystem $filesystem, Translator $translator)
    {
        parent::__construct($swift);
        $this->viewFactory = $viewFactory;
        $this->filesystem  = $filesystem;
        $this->translator  = $translator;
        $this->data        = [];
    }

    /**
     *  Set the view to be rendered.
     *
     *  @param  string $locale
     *  @return self
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Add data to the view.
     *
     * @param  string|array     $key
     * @param  mixed            $value
     * @return self
     */
    public function setData($key, $value = null)
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
     *    @param string   $cssPath     Css relative path inside the css folder.
     *    @return Mailman Current object instance, allows for method chaining.
     */
    public function setCss($cssPath)
    {
        $this->cssFile = $cssPath;
        return $this;
    }

    /**
     *    Return the HTML representation of the email to be sent.
     *
     *    @return string
     */
    public function getBody()
    {
        // Set the email's locale:
        $appLocale = $this->translator->getLocale();
        if ($this->locale && $this->locale != $appLocale) {
            $this->translator->setLocale($this->locale);
        }

        // Generate HTML:
        $html    = $this->viewFactory->make($this->view, $this->data)->render();
        $css     = $this->filesystem->get($this->cssFile);
        $inliner = new CssToInlineStyles($html, $css);
        $body    = $inliner->convert();

        // Return App locale to former value:
        if ($this->locale && $this->locale != $appLocale) {
            $this->translator->setLocale($appLocale);
        }

        return $body;
    }

    /**
     * Get the underlying Swift Message instance.
     *
     * @return \Swift_Message
     */
    public function getSwiftMessage()
    {
        $this->swift->setBody($this->getBody(), 'text/html');
        return $this->swift;
    }

}
