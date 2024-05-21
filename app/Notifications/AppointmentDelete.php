<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AppointmentDelete extends Notification
{
    use Queueable;
    
    protected $student;
    protected $appointment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, $appointment)
    {
        $this->student = $student;
        $this->appointment = $appointment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $meeting = Carbon::createFromDate($this->appointment->meeting_time);
        $date = $meeting->format('d M Y');

        return (new MailMessage)
                    ->subject('Flash Coaching Appointment Deleted - PAS')
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Appointment deleted.')
                    ->line('Your Flash Coaching appointment ('.$date.') has been deleted by '.$this->student->first_name .' '.$this->student->last_name.'.')
                    ->line('Please reach out to ' .$this->student->first_name.' on email ('.$this->student->email.') for further details.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
