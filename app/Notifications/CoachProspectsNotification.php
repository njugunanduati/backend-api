<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CoachProspectsNotification extends Notification
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
        
        $subject = 'Your Prospects List is Ready';

        return (new MailMessage)
                    ->subject($subject)
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Hi '.$coach_first_name.',')
                    ->line('Your prospect list is ready. Please check into your PAS dashboard.')
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
