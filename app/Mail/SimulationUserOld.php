<?php

namespace App\Mail;

use App\Models\Simulation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Helpers\Helper;


class SimulationUserOld extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The simulation instance.
     *
     * @var Simulation
     */
    public $simulation;

    /**
     * Create a new message instance.
     *
     * @param  \App\Model\Simulation  $simulation
     * @return void
     */
    public function __construct(Simulation $simulation)
    {
        $this->simulation = $simulation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $emailfrom = $this->simulation->coach_email;
        $mname = $this->simulation->coach_first_name . ' ' . $this->simulation->coach_last_name;
        return $this->from($address = $emailfrom, $name = $mname)
                    ->subject('Profit Acceleration Simulator Report from '.$this->simulation->coach_first_name.' '. $this->simulation->coach_last_name)
                    ->replyTo($address = $emailfrom, $name = $mname)
                    ->view('emails.simulations.new_user')
                    ->with([
                        'user_name' => $this->simulation->first_name.' '.$this->simulation->last_name,
                        'coach' => $this->simulation->coach_first_name.' '.$this->simulation->coach_last_name,
                        'report_link' =>  $this->simulation->coach_url.'/simulator/report/?id='.base64_encode($this->simulation->email),
                        'subject' => 'Profit Acceleration Simulator Report',
                    ]);
    }
}
