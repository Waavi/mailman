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
        $swift       = Mockery::mock(Swift_Message::class);
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message($swift, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');

        $translator->shouldReceive('getLocale')->andReturn('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $viewFactory->shouldReceive('render')->andReturn('<h1>Hola</h1>');

        $filesystem->shouldReceive('get')->with('cssPath')->andReturn('');

        $body = $message->getBody();
        $this->assertEquals(true, false);
        dd('FUCK=');
        $this->assertEquals('<h1>Hola</h1>', $body);

    }

    /**
     * @test
     */
    public function it_inlines_css()
    {
        $swift       = Mockery::mock(Swift_Message::class);
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message($swift, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');

        $translator->shouldReceive('getLocale')->andReturn('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $viewFactory->shouldReceive('render')->andReturn('<h1>Hola</h1>');

        $filesystem->shouldReceive('get')->with('cssPath')->andReturn('h1 { color: blue; }');

        $this->assertEquals('<h1 style="color: blue">Hola</h1>', $message->getBody());
    }

    /**
     * @test
     */
    public function it_sets_locale()
    {
        $swift       = Mockery::mock(Swift_Message::class);
        $viewFactory = Mockery::mock(ViewFactory::class);
        $filesystem  = Mockery::mock(Filesystem::class);
        $translator  = Mockery::mock(Translator::class);
        $message     = new Message($swift, $viewFactory, $filesystem, $translator);

        $message->setView('view');
        $message->setCss('cssPath');
        $message->setLocale('es');

        $translator->shouldReceive('getLocale')->andReturn('en');
        $translator->shouldReceive('setLocale')->once()->with('es');
        $translator->shouldReceive('setLocale')->once()->with('en');

        $viewFactory->shouldReceive('make')->with('view', [])->andReturn($viewFactory);
        $viewFactory->shouldReceive('render')->andReturn('<h1>Hola</h1>');

        $filesystem->shouldReceive('get')->with('cssPath')->andReturn('h1 { color: blue; }');

        $this->assertEquals('<h1 style="color: blue">Hola</h1>', $message->getBody());
    }
}
