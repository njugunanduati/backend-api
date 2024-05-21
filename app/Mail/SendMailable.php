<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var MailMessage
     */
    public $mailmessage;
    
    /**
     * The subject instance.
     *
     * @var MailSubject
     */
    public $mailsubject;

    /**
     * The subject instance.
     *
     * @var MailCopy
     */
    public $mailcopy;

    /**
     * The fron email address instance.
     *
     * @var MailFrom
     */
    public $mailfrom;

    /**
     * The from name instance.
     *
     * @var MailName
     */
    public $mailname;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($m, $s, $c, $bc, $f, $n)
    {
        $this->mailmessage = $m;
        $this->mailsubject = $s;
        $this->mailcopy = $c;
        $this->mailbcopy = $bc;
        $this->mailfrom = $f ?? env('EMAIL_FROM');
        $this->mailname = $n ?? env('MAIL_FROM_NAME');
    }

     /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        
        return $this
                ->from($address = $this->mailfrom, $name= $this->mailname)
                ->subject($this->mailsubject)
                ->cc($this->mailcopy)
                ->bcc($this->mailbcopy)
                ->replyTo($address = $this->mailfrom, $name= $this->mailname)
                ->view('emails.generic')
                ->with([
                    'content' => $this->mailmessage,
                ]);
    }
}
