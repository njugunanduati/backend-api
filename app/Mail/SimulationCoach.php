<?php

namespace App\Mail;

use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\Helper;


class SimulationCoach extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The simulation instance.
     *
     * @var Simulation
     */
    public $simulation;
    public $alt_email;

    /**
     * Create a new message instance.
     *
     * @param  \App\Model\Simulation  $simulation
     * @param  $alt_email
     * @return void
     */
    public function __construct(Simulation $simulation, String  $alt_email = null)
    {
        $this->simulation = $simulation;
        $this->alt_email = $alt_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $emailfrom = $this->simulation->email;
        $mname = $this->simulation->first_name . ' ' . $this->simulation->last_name;
        return $this->from($address = $emailfrom, $name = $mname)
            ->subject('Profit Acceleration Simulator Report for ' . $this->simulation->first_name . ' ' . $this->simulation->last_name,)
            ->cc($this->alt_email)
            ->replyTo($address = $emailfrom, $name = $mname)
            ->view('emails.simulations.coach')
            ->with([
                'simulation_data' => $this->simulation,
                'user_name' => $this->simulation->first_name . ' ' . $this->simulation->last_name,
                'user_email' => $this->simulation->email,
                'coach' => $this->simulation->coach_first_name . ' ' . $this->simulation->coach_last_name,
                'report_link' => Helper::getReactAppBaseOrigin().'simulator/report/' . $this->simulation->uuid,
                'subject' => 'Profit Acceleration Simulator Report for ' . $this->simulation->first_name . ' ' . $this->simulation->last_name,
            ]);
    }
}
