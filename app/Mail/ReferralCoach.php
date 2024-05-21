<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReferralCoach extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var User
     */
    public $coach;
    /**
     * The referral instance.
     *
     * @var Referral
     */
    public $referral;

    /**
     * The user instance.
     *
     * @var User
     */
    public $client;

    /**
     * Create a new message instance.
     *
     * @param  \App\Model\Referral  $referral
     * @return void
     */
    public function __construct(User $coach, Referral $referral, User $client)
    {
        $this->coach = $coach;
        $this->referral = $referral;
        $this->client = $client;
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
            ->subject('Business Coaching Referral from '.$this->client->first_name . ' ' . $this->client->last_name)
            ->replyTo($address = $emailfrom, $name = $mname)
            ->view('emails.referrals.coach')
            ->with([
                'referral_data' => $this->referral,
                'referral_name' => $this->referral->first_name . ' ' . $this->referral->last_name,
                'referral_email' => $this->referral->email,
                'referral_phone' => $this->referral->phone_number,
                'client_name' => $this->client->first_name . ' ' . $this->client->last_name,
                'coach_name' => $this->coach->first_name . ' ' . $this->coach->last_name,
                'subject' => 'Business Client Referral offered by ' . $this->client->first_name . ' ' . $this->client->last_name,
            ]);
    }
}