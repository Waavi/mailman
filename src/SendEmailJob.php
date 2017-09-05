<?php
namespace Waavi\Mailman;

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Swift_Message;

class SendEmailJob implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create a new job instance.
     *
     * @param  User  $user
     * @return void
     */
    public function __construct(Swift_Message $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @param  Mailer  $laravelMailer
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        $mailer->getSwiftMailer()->send($this->message);
    }
}
