<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CoachNewStudent extends Mailable
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

        $mfrom = ($this->details['user']) ? $this->details['user']->email : env('EMAIL_FROM');
        $mname = ($this->details['user']) ? $this->details['user']->first_name . ' ' . $this->details['user']->last_name : env('MAIL_FROM_NAME');
        $mcompany = ($this->details['user']) ? $this->details['user']->company : env('APP_NAME');

        $greetings = 'Hello';
        if (isset($this->details['student_name']) && strlen($this->details['student_name']) > 0) {
            $greetings = 'Hi ' . $this->details['student_name'];
        }

        $loginurl = env('STUDENT_URL', 'https://student.profitaccelerationsoftware.com') . '/login';

        return $this
            ->from($address = $mfrom, $name = $mname)
            ->subject($this->details['subject'])
            ->cc($this->details['copy'])
            ->bcc($this->details['bcopy'])
            ->replyTo($address = $mfrom, $name = $mname)
            ->view('emails.newstudent')
            ->with([
                'user' => $this->details['user'],
                'greetings' => $greetings,
                'loginurl' => $loginurl,
                'content' => $this->details['messages'],
                'summary' => isset($this->details['summary']) ? $this->details['summary'] : [],
                'company' => $mcompany,
                'footer' => 'Powered by Profit Acceleration Software',
            ]);
    }
}
