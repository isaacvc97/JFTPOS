<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\BranchInvitation;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        
        return Inertia::render("users/Index", ["users" => $users]);
    }

    public function store(Request $request)
    {
        $messages = [
            'name.required' => 'El nombre attribute obligatorio',
            'identification.unique' => ':input ya fue registrado.',
            'email.email' => 'Correo invalido, compruebe.',
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identification' => 'nullable|string|max:50|unique:users,identification',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
        ], $messages);

        $user = User::create($validated);

        $invitacion = BranchInvitation::where('email', $user->email)->where('estado', 'pendiente')->first();
        if ($invitacion) {
            $user->update([
                'branch_id' => $invitacion->branch_id,
                'role' => 'vendedor'
            ]);
            $invitacion->update(['estado' => 'aceptada']);
        }


        // Auth::login($user);

        return $user;
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identification' => 'nullable|string|max:50|unique:users,identification,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $user->update($validated);
        return $user;
    }

    public function destroy(User $user)
    {
        dd($user);
        $user->delete();
        return response()->noContent();
    }
}
