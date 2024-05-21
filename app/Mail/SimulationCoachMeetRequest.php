<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\Helper;

class SimulationCoachMeetRequest extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $emailfrom = ($this->data->meeting_details['useremail'])?  ($this->data->meeting_details['useremail']) : env('MAIL_FROM_ADDRESS');
        $mname = ($this->data->simulation_details['first_name']) ? $this->data->simulation_details['first_name'] . ' ' . $this->data->simulation_details['last_name'] : env('MAIL_FROM_NAME');
        return $this->from($address = $emailfrom, $name = $mname )
            ->subject('Meeting Request for Profit Acceleration Simulator Report for '.$this->data->simulation_details['first_name'] . ' ' . $this->data->simulation_details['last_name'])
            ->replyTo($address = $emailfrom, $name = $mname)
            ->view('emails.simulations.request_meet')
            ->with([
                'user_name' => $this->data->simulation_details['first_name'] . ' ' . $this->data->simulation_details['last_name'],
                'user_email' => $this->data->simulation_details['email'],
                "phone_number" => $this->data->meeting_details['phonenumber'],
                'meet_details' => $this->data->meeting_details['meetdetails'],
                'coach' => $this->data->simulation_details['coach_first_name'] . ' ' . $this->data->simulation_details['coach_last_name'],
                'report_link' => Helper::getReactAppBaseOrigin().'simulator/report/' . $this->data->simulation_details['uuid'],
                'subject' => 'Meeting Request for Profit Acceleration Simulator Report for ' . $this->data->simulation_details['first_name'] . ' ' . $this->data->simulation_details['last_name']
            ]);
    }
}
