<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchInvitationNotification extends Notification /* implements ShouldQueue */ {
    public function __construct(public string $token) {}

    public function via($notifiable) {
        return ['mail', 'database'];
    }

    public function toMail($notifiable) {
        return (new MailMessage)
            ->subject('Invitación a sucursal')
            ->line('Has sido invitado a una sucursal.')
            ->action('Aceptar invitación', url('/invitaciones/aceptar/' . $this->token));
    }

    public function toDatabase($notifiable) {
        return [
            'tipo' => 'invitacion',
            'mensaje' => 'Has recibido una invitación a una sucursal.',
            'token' => $this->token,
            'url' => url('/invitaciones/aceptar/' . $this->token)
        ];
    }
}

// class BranchInvitationNotification0 extends Notification
// {
//     use Queueable;

//     /**
//      * Create a new notification instance.
//      */
//     public function __construct()
//     {
//         //
//     }

//     /**
//      * Get the notification's delivery channels.
//      *
//      * @return array<int, string>
//      */
//     public function via(object $notifiable): array
//     {
//         return ['mail'];
//     }

//     /**
//      * Get the mail representation of the notification.
//      */
//     public function toMail(object $notifiable): MailMessage
//     {
//         return (new MailMessage)
//             ->line('The introduction to the notification.')
//             ->action('Notification Action', url('/'))
//             ->line('Thank you for using our application!');
//     }

//     /**
//      * Get the array representation of the notification.
//      *
//      * @return array<string, mixed>
//      */
//     public function toArray(object $notifiable): array
//     {
//         return [
//             //
//         ];
//     }
// }
