<?php

namespace Waavi\Mailman\Test;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\Translator;
use Illuminate\View\Factory as ViewFactory;
use Mockery;
use Swift_Message;
use Waavi\Mailman\Message;

class MessageTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @test
     */
    public function it_returns_html()
    {
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message(new Swift_Message, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');

        $translator->shouldReceive('getLocale')->andReturn('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body><h1>Hola</h1></body></html>';
        $viewFactory->shouldReceive('render')->andReturn($html);

        $filesystem->shouldReceive('get')->with(base_path('cssPath'))->andReturn('');
        $this->assertEquals($html, $message->getBody());
    }

    /**
     * @test
     */
    public function it_inlines_css()
    {
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message(new Swift_Message, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');

        $translator->shouldReceive('getLocale')->andReturn('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $viewFactory->shouldReceive('render')->andReturn('<h1>Hola</h1>');

        $filesystem->shouldReceive('get')->with(base_path('cssPath'))->andReturn('h1 { color: blue; }');

        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body><h1 style="color: blue;">Hola</h1></body></html>';
        $this->assertEquals($html, $message->getBody());
    }

    /**
     * @test
     */
    public function it_sets_locale()
    {
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message(new Swift_Message, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');
        $message->setLocale('es');

        $translator->shouldReceive('getLocale')->andReturn('en');
        $translator->shouldReceive('setLocale')->once()->with('es');
        $translator->shouldReceive('setLocale')->once()->with('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $viewFactory->shouldReceive('render')->andReturn('<h1>Hola</h1>');

        $filesystem->shouldReceive('get')->with(base_path('cssPath'))->andReturn('h1 { color: blue; }');

        $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">
<html><body><h1 style="color: blue;">Hola</h1></body></html>';
        $this->assertEquals($html, $message->getBody());
    }
}
