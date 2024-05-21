<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class NewFlashCoachingStudent extends Notification
{
    use Queueable;

    protected $user;
    protected $coach;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user,$coach)
    {
        $this->user = $user;
        $this->coach = $coach;
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
        $url = url(env('STUDENT_URL').'/login');
        return (new MailMessage)
                    ->subject('New Flash Coaching Program - PAS')
                    ->from(env('EMAIL_FROM'), 'Profit Acceleration Software')
                    ->greeting('Flash Coaching Information.')
                    ->line('You have been invited for a Flash Coaching program by '.$this->coach->first_name .' '.$this->coach->last_name.'.')
                    ->line('Please login here and navigate to "My Lessons" for more details')
                    ->action('Go to "My Lessons"', url($url))
                    ->line('Please reach out to ' .$this->coach->first_name.' on email ('.$this->coach->email.') for further details.');
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
