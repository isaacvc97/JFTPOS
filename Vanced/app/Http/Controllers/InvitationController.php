<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\BranchInvitation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BranchInvitationNotification;

class InvitationController extends Controller {
    public function store(Request $request) {
        $token = Str::uuid();
        BranchInvitation::create([
            'branch_id' => $request->branch_id,
            'email' => $request->email,
            'token' => $token,
            'enviado_por' => auth()->id()
        ]);

        Notification::route('mail', $request->email)->notify(new BranchInvitationNotification($token));

        return response()->json(['status' => 'enviado']);
    }

    public function accept($token) {
        $invitacion = BranchInvitation::where('token', $token)->firstOrFail();
        $invitacion->estado = 'aceptada';
        $invitacion->save();

        auth()->user()->branches()->attach($invitacion->branch_id, ['role' => 'vendedor']);
        return redirect('/dashboard');
    }

    public function rechazar($token) {
        $inv = BranchInvitation::where('token', $token)->firstOrFail();
        $inv->estado = 'rechazada';
        $inv->save();
        return response()->json(['status' => 'rechazada']);
    }

    public function destroy($id) {
        BranchInvitation::where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
// class BranchInvitationNotification extends Notification implements ShouldQueue {
//     public function __construct(public string $token) {}

//     public function via($notifiable) {
//         return ['mail'];
//     }

//     public function toMail($notifiable) {
//         return (new MailMessage)
//             ->subject('Invitación a sucursal')
//             ->line('Has sido invitado a una sucursal.')
//             ->action('Aceptar invitación', url('/invitaciones/aceptar/' . $this->token));
//     }
// }