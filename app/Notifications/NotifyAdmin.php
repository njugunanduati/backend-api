<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NotifyAdmin extends Notification
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
        $current_user_first_name = $this->notice->current_user_first_name;
        $current_user_last_name = $this->notice->current_user_last_name;
        $current_user_email = $this->notice->current_user_email;
        $new_user_first_name = $this->notice->new_user_first_name;
        $new_user_last_name = $this->notice->new_user_last_name;
        $new_user_email = $this->notice->new_user_email;

        $subject = 'NEW Single User Added Under '.$current_user_first_name.' '.$current_user_last_name;

        return (new MailMessage)
                    ->subject($subject)
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Hello!')
                    ->line('Just wanted to notify you that a new single user:')
                    ->line('  First Name: '.$new_user_first_name)
                    ->line('  Last Name: '.$new_user_last_name)
                    ->line('  Email: '.$new_user_email)
                    ->line('Has been added under '.$current_user_first_name.' '.$current_user_last_name.' ('.$current_user_email.') account.')
                    ->line('Thank you.');
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
