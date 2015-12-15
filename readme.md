# Better email for Laravel 5

[![Latest Version on Packagist](https://img.shields.io/packagist/v/waavi/mailman.svg?style=flat-square)](https://packagist.org/packages/waavi/mailman)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/Waavi/mailman/master.svg?style=flat-square)](https://travis-ci.org/Waavi/mailman)
[![Total Downloads](https://img.shields.io/packagist/dt/waavi/mailman.svg?style=flat-square)](https://packagist.org/packages/waavi/mailman)

## Introduction

Mailman has all of Laravel's Mail features, plus allows you to:

	- Keep your email css files in an external file, and automatically inline them when sending an email.
	- To set the language in which the email should be sent
	- Ease of use, avoiding an annoying use of callbacks for emails.

## Installation

Require through composer

	composer require waavi/mailman 2.0.x

Or manually edit your composer.json file:

	"require": {
		"waavi/mailman": "2.0.x"
	}

Publish the configuration file:

	php artisan vendor:publish

In config/app.php, add the following entry to the providers array:

	\Waavi\Mailman\MailmanServiceProvider::class,

And to the aliases array:

	'Mailman' => Waavi\Mailman\Facades\Facade::class,

## Usage

### Basic example

Usage is very similar to Laravel's Mail, with no callbacks needed. In fact, Mailman is expected to be used just like you use Views. Say you have an email view in views/emails/email. You may send the email by:

	Mailman::make('emails.email')->to('william@waavi.com')->subject('test')->send();

The from address and general email configuration will be loaded from app/mail.php, whereas email stylesheet configuration is done through the mailman.php config file.

### Passing data to an email's view

Say you want to pass data to the view, you may do so in two ways: through the make method and the with method, just like in Views:

	Mailman::make('emails.welcome', array('user' => $user))->to('user@example.com')->subject('welcome')->send();
	Mailman::make('emails.welcome')->with(['user', $user])->to('user@example.com')->subject('welcome')->send();
	Mailman::make('emails.welcome')->with('user', $user)->to('user@example.com')->subject('welcome')->send();

### Setting the locale

To set the locale in which the email should be sent, you may use the set locale method:

	Mailman::make('emails.basic')->setLocale('es')->to('user@example.com')->subject('hello')->send();

### Setting the css file to use

You may set a different css file than default. The parameter must be the full path relative to the email stylesheets folder:

	Mailman::make('emails.basic')->setCss('../assets/private.css')

### Queue emails

You may queue emails just like with Laravel's Mail. To send an email through a queue:

	Mailman::make('emails.basic')->to...->queue()                  // Queue in default queue.
	Mailman::make('emails.basic')->to...->queue('queue_name')      // Queue in queue_name queue.
	Mailman::make('emails.basic')->to...->later(5)                 // Send email after 5 seconds.
	Mailman::make('emails.basic')->to...->laterOn(5, 'queue_name')

### Fake sending email

If you are using Laravel's Homestead, the recommend way would be to install [MailCatcher](http://blog.bobbyallen.me/2014/10/21/installing-mailcatcher-support-in-laravel-homestead/), however you can also set the pretend option to true in the mail.php config file.

### Get the email as a string

For debugging, it is often useful to be able to print the contents of an email. With Mailman you may get the body of the email using Mailman::show()

	Mailman::make('emails.basic')->to('user@example.com')->subject('hello')->show();

### Common methods (Illuminate\Mail\Message)

All methods in Illuminate\Mail\Message are available through Mailman:

	Mailman::make('emails.basic')->to('john@doe.it')
	Mailman::make('emails.basic')->to('john@doe.it', 'John Doe')      // Set the recipient.

	Mailman::make('emails.basic')->from('john@doe.it')
	Mailman::make('emails.basic')->from('john@doe.it', 'John Doe')    // Set from field.

	Mailman::make('emails.basic')->sender('john@doe.it')
	Mailman::make('emails.basic')->sender('john@doe.it', 'John Doe')  // Set sender.

	Mailman::make('emails.basic')->returnPath('john@doe.it')          // Set return path.

	Mailman::make('emails.basic')->cc('john@doe.it')
	Mailman::make('emails.basic')->cc('john@doe.it', 'John Doe')      // Add carbon copy.

	Mailman::make('emails.basic')->bcc('john@doe.it')
	Mailman::make('emails.basic')->bcc('john@doe.it', 'John Doe')     // Add blind carbon copy.

	Mailman::make('emails.basic')->replyTo('john@doe.it')
	Mailman::make('emails.basic')->replyTo('john@doe.it', 'John Doe') // Add reply to.

	Mailman::make('emails.basic')->subject('Subject text')            // Add subject

	Mailman::make('emails.basic')->priority(5)                        // Set priority level

	Mailman::make('emails.basic')->attach('file/test.pdf', $options)  // Attach file

	Mailman::make('emails.basic')->attachData($data, $name, $options) // Attach in-memory data

	Mailman::make('emails.basic')->embed('file/test.jpg')             // Embed file and get cid

	Mailman::make('emails.basic')->embedData($data, $name, $contentType)  // Embed data
