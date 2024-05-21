<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AdminProspectsRequest extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    protected $notice;

    public function __construct($notice)
    {
        $this->notice = $notice;
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
        $coach_first_name = $this->notice->coach_first_name;
        $coach_last_name = $this->notice->coach_last_name;
        $coach_email = $this->notice->coach_email;
        $notify_two = $this->notice->notify_two;
        $notify_three = $this->notice->notify_three;
        $name = $this->notice->name;
        
        $subject = 'A New Prospects List Request from '.$coach_first_name.' '.$coach_last_name;

        $array = explode(";", $name);

        $specifics = '';

        foreach ($array as $key => $value) {
           $specifics.= $value.'\n';
        }

        $greet = 'Hi ' . env('PROSPECTS_NOTIFY_NAME', 'Support') . ',';

        return (new MailMessage)
                    ->subject($subject)
                    ->from(env('EMAIL_FROM', 'Profit Acceleration Software'))
                    ->cc([$notify_two, $notify_three])
                    ->greeting($greet)
                    ->line('There is a new prospect list request from '.$coach_first_name.' '.$coach_last_name.' ('.$coach_email.')')
                    ->line('')
                    ->line($name)
                    ->line('');
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
