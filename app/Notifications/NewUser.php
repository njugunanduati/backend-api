<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewUser extends Notification
{
    use Queueable;

    protected $user;
    protected $password;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$password)
    {
        $this->user = $user;
        $this->password = $password;
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
        $url = url(env('FRONTEND_URL').'/login');
        return (new MailMessage)
                    ->subject('New Client Account Details - PAS')
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Welcome to Profit Acceleration Software - PAS Account Information')
                    ->line('Here are your account details for your account at Profit Acceleration Software.')
                    ->line('Username - '.$this->user->email)
                    ->line('Password - '.$this->password)
                    ->action('Click here to login to your account', url($url))
                    ->line('If you did not create an account, no further action is required.')
                    ->line('Please look for your Welcome to Focused.com email to help explain your next steps.');
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
