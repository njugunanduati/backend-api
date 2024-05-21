<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TicketMailable extends Mailable
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

        return $this
            ->to($this->details['to'], 'Profit Acceleration Software')
            ->from($this->details['from'], $this->details['first_name'].' '.$this->details['last_name'])
            ->subject($this->details['subject'])
            ->replyTo($this->details['from'], $this->details['first_name'].' '.$this->details['last_name'])
            ->view('emails.ticket')
            ->with([
                'first_name' => $this->details['first_name'],
                'last_name' => $this->details['last_name'],
                'content' => $this->details['message'],
                'ticket_type' => $this->details['ticket_type'],
                'priority' => $this->details['priority'],
                'footer' => 'Powered by Profit Acceleration Software',
            ]);
    }
}
