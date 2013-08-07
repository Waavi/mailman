## Email bundle for Laravel

Laravel has several shortcomings when sending emails. Mailman has the following features that allow for easier email management:

	- External stylesheets that are built inline when sending an email.
	- Ease of use, avoiding an annoying use of callbacks for emails.
	- Setting the language in which the email should be sent
	- Full compatibility with Illuminate\Mail\Message and Illuminate\Mail configuration.
	- Setting a queue system through configuration files.

## Installation

Edit composer.json:

	"require": {
		"waavi/mailman": "*"
	},
	"repositories": [
    {
      "type": "vcs",
      "url":  "git@github.com:Waavi/mailman.git"
    }
  ],

In app/config/app.php, add the following entry to the providers array:

	'Waavi\Mailman\MailmanServiceProvider',

And to the aliases array:

	'Mailman' => 'Waavi\Mailman\Facades\Facade',

## Usage

Usage is very similar to Laravel's Mail, with no callbacks needed. For example, say you've configured Mailman correctly to use your css file, and you have a view called 'email' in the views/emails folder. You may send an email with:

	Mailman::make('emails.email')->to('william@waavi.com')->subject('test')->send();
