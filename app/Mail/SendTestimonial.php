<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTestimonial extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var User
     */
    public $coach;
    
    /**
     * The user instance.
     *
     * @var User
     */
    public $client;

    /**
     * The testimonial string instance.
     *
     * @var String
     */
    public $testimonial;

    /**
     * The rating string instance.
     *
     * @var String
     */
    public $rating;


    /**
     * Create a new message instance.
     *
     * @param  \App\Model\Referral  $referral
     * @return void
     */
    public function __construct(User $coach, User $client, $testimonial, $rating)
    {
        $this->coach = $coach;
        $this->client = $client;
        $this->testimonial = $testimonial;
        $this->rating = $rating;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $emailfrom = $this->client->email;
        $mname = $this->client->first_name . ' ' . $this->client->last_name;
        return $this->from($address = $emailfrom, $name = $mname )
            ->subject('New Testimonial From '.$this->client->first_name . ' ' . $this->client->last_name)
            ->replyTo($address = $emailfrom, $name = $mname)
            ->view('emails.testimonials.coach')
            ->with([
                'rating' => $this->rating,
                'testimonial' => $this->testimonial,
                'client_phone' => $this->client->phone_number || '',
                'client_email' => $this->client->email,
                'client_name' => $this->client->first_name . ' ' . $this->client->last_name,
                'coach_name' => $this->coach->first_name . ' ' . $this->coach->last_name,
            ]);
    }
}