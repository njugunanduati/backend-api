<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneralMailable extends Mailable
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

        $mfrom = env('EMAIL_FROM');
        $mname = env('MAIL_FROM_NAME');
        $mcompany = env('APP_NAME');

        $greetings = 'Hello';
        if (isset($this->details['client_name']) && strlen($this->details['client_name']) > 0) {
            $greetings = 'Hi ' . $this->details['client_name'];
        }

        $bcc = isset($this->details['bcopy'])? $this->details['bcopy'] : []; 

        return $this
            ->from($address = $mfrom, $name = $mname)
            ->subject($this->details['subject'])
            ->cc($this->details['copy'])
            ->bcc($bcc)
            ->replyTo($address = $mfrom, $name = $mname)
            ->view('emails.general')
            ->with([
                'greetings' => $greetings,
                'content' => $this->details['messages'],
                'summary' => isset($this->details['summary']) ? $this->details['summary'] : [],
                'company' => $mcompany,
                'footer' => 'Powered by Profit Acceleration Software',
            ]);
    }
}
