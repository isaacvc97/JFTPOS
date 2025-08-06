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
            'branch_id' => auth()->user()->branch_id,
            'email' => $request->email,
            'token' => $token,
            'enviado_por' => auth()->id()
        ]);

        Notification::route('mail', $request->email)
            ->notify(new BranchInvitationNotification($token));

        return back();
    }

    public function accept($token) {
        $inv = BranchInvitation::where('token', $token)->firstOrFail();

        if (!auth()->check()) {
            session(['invitation_token' => $token]);
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Si ya tiene una sucursal, eliminarla
        if ($user->branch_id) {
            $user->branch()->delete();
        }

        $inv->estado = 'aceptada';
        $inv->save();

        // $user->update([
        //     'branch_id' => $inv->branch_id,
        //     'role' => 'vendedor'
        // ]);

        $user->branch_id = $inv->branch_id;
        $user->role = 'vendedor';
        $user->save();

        return redirect('/dashboard');
    }

    public function accept0($token) {
        $inv = BranchInvitation::where('token', $token)->firstOrFail();

        if (!auth()->check()) {
            session(['invitation_token' => $token]);
            return redirect()->route('login');
        }

        $inv->estado = 'aceptada';
        $inv->save();

        auth()->user()->update([
            'branch_id' => $inv->branch_id,
            'role' => 'vendedor'
        ]);

        return redirect('/dashboard');
    }

    public function reject($token) {
        $inv = BranchInvitation::where('token', $token)->firstOrFail();
        if (auth()->check() && auth()->user()->email === $inv->email) {
            $inv->update(['estado' => 'rechazada']);
        }
        return back();
    }

}