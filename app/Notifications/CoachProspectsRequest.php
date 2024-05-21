<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CoachProspectsRequest extends Notification
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
        $name = $this->notice->name;
        
        $subject = 'Your Prospects Request';

        $array = explode(";", $name);

        $specifics = '';

        foreach ($array as $key => $value) {
           $specifics.= $value.'\n';
        }

        return (new MailMessage)
                    ->subject($subject)
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Hi '.$coach_first_name.',')
                    ->line('Your Local Prospect List will be delivered in 2-3 days. These are the specifics you requested for:')
                    ->line('')
                    ->line($name)
                    // ->splitInLines($specifics)
                    // ->line(array_map('trim', preg_split('/\\r\\n|\\r|\\n/', $specifics)))
                    ->line('')
                    ->line('Your list will be delivered directly into your software and you’ll be able to download as an Excel file. We will send an email notification when your list is ready.')
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
