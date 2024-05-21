<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class CoachAttachmentMailable extends Mailable
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
        if (isset($this->details['client_name']) && strlen($this->details['client_name']) > 0) {
            $greetings = 'Hi ' . $this->details['client_name'];
        }

        $amail = $this
            ->from($address = $mfrom, $name = $mname)
            ->subject($this->details['subject'])
            ->cc($this->details['copy'])
            ->bcc($this->details['bcopy'])
            ->replyTo($address = $mfrom, $name = $mname)
            ->view('emails.coach')
            ->with([
                'user' => $this->details['user'],
                'greetings' => $greetings,
                'content' => $this->details['messages'],
                'summary' => isset($this->details['summary']) ? $this->details['summary'] : [],
                'company' => $mcompany,
                'footer' => 'Powered by Profit Acceleration Software',
            ]);

        if ($this->details['type'] == 'local') {
            $path = storage_path('app/pdfs/' . $this->details['file_name']);
            $amail->attach($path, [
                'as' => $this->details['file_name'],
                'mime' => 'application/pdf'
            ]);
        } else {
            foreach ($this->details['attachments'] as $file) {
                $amail->attach($file);
            }
        }

        return $amail;
    }
}
