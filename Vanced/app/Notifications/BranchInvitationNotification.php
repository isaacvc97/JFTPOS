<?php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class BranchInvitationNotification extends Notification /* implements ShouldQueue */ {
    public function __construct(public string $token) {}

    public function via($notifiable) {
        return ['mail', 'database'];
    }

    public function toMail($notifiable) {
        return (new MailMessage)
            ->subject('Invitación a sucursal')
            ->line('Has sido invitado a una sucursal.')
            ->action('Aceptar invitación', url('/invitations/accept/' . $this->token));
    }

    public function toDatabase($notifiable) {
        return [
            'tipo' => 'invitacion',
            'mensaje' => 'Has sido invitado a una sucursal.',
            'token' => $this->token
        ];
    }
}
