<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class OnboardingEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The details instance.
     *
     * @var Details
     */
    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($d)
    {
        $this->details = $d;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Configure Onboarding Emails to come from the Onboarding Alias email.
        $mfrom = 'onboarding@focused.com';
        $mname = ($this->details['user']) ? $this->details['user']->first_name . ' ' . $this->details['user']->last_name : env('MAIL_FROM_NAME');
        $mcompany = ($this->details['user']) ? $this->details['user']->company : env('APP_NAME');

        $greetings = '';
        if (isset($this->details['client_name']) && strlen($this->details['client_name']) > 0) {
            $greetings = 'Hi ' . $this->details['client_name'];
        }

        $bcc = isset($this->details['bcopy']) ? $this->details['bcopy'] : [];

        return $this
            ->from($address = $mfrom, $name = $mname)
            ->subject($this->details['subject'])
            ->cc($this->details['copy'])
            ->bcc($bcc)
            ->replyTo($address = $mfrom, $name = $mname)
            ->view('emails.onboarding-email')
            ->with([
                'user' => $this->details['user'],
                'greetings' => $greetings,
                'content' => Str::of($this->details['messages'])->markdown([
                    'html_input' => 'strip',
                ]),
                'summary' => isset($this->details['summary']) ? $this->details['summary'] : [],
                'company' => $mcompany,
                'footer' => 'Powered by Profit Acceleration Software',
            ]);
    }
}