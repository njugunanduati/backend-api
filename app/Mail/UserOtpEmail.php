<?php

namespace App\Mail;

use App\Models\LoginOtp;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class UserOtpEmail extends Mailable
{
    use Queueable;

    /**
     * The login otp instance.
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
        $greetings = 'Hello';
        if (isset($this->details['first_name']) && strlen($this->details['first_name']) > 0) {
            $greetings = 'Hi ' . $this->details['first_name'];
        }

        return $this
            ->to($this->details['to'], $this->details['first_name'].' '.$this->details['last_name'])
            ->from($this->details['from'], 'Profit Acceleration Software')
            ->subject($this->details['subject'])
            ->replyTo($this->details['from'], 'Profit Acceleration Software')
            ->view('emails.logins.loginotp')
            ->with([
                'first_name' => $this->details['first_name'],
                'last_name' => $this->details['last_name'],
                'otp' => $this->details['otp'],
                'greetings' => $greetings,
                'salutation' => 'Profit Acceleration Software',
                'footer' => 'Powered by Profit Acceleration Software',
            ]);
    }
}
