<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller {

    public function index() {
        $user = auth()->user();
        $branch = $user->branch()->with(['users', 'invitaciones'])->first();
        // dd($branch);

        return Inertia::render('branches/Branchmanager', [
            'branch' => $branch,
            'users' => $branch?->users,
            'invitaciones' => $branch?->invitaciones,
            'role' => $user->role,
        ]);
    }

    public function store(Request $request) {
        $branch = Branch::create([
            'nombre' => $request->nombre,
            'ruc' => $request->ruc,
            'telefono' => $request->telefono,
            'direccion' => $request->direccion,
            'owner_id' => auth()->id()
        ]);

        $user = auth()->user();

        $user->branch_id = $branch->id;
        $user->role = 'administrador';
        $user->save();

        // auth()->user()->update([
        //     'branch_id' => $branch->id,
        //     'role' => 'administrador'
        // ]);

        return redirect()->back();
        // return redirect()->route('dashboard');
    }


    public function create(){
        return Inertia::render('Branch/Create');
    }

    public function update(Branch $branch, Request $request) {
        $branch->update($request->only(['nombre', 'ruc', 'telefono', 'direccion']));
        return notify('success', 'Sucursal actualizada');
    }



    public function cambiarRol(User $user, Request $request)
    {
        $request->validate([
            'role' => 'required|in:administrador,vendedor',
        ]);

        if (auth()->user()->id === $user->id) {
            return back()->with('error', 'No puedes cambiar tu propio rol');
        }

        if (auth()->user()->branch_id !== $user->branch_id) {
            return back()->with('error', 'El usuario no pertenece a tu sucursal');
        }

        $user->role = $request->role;
        $user->save();

        return notify('success', 'Rol actualizado correctamente');
        // return back()->with('success', 'Rol actualizado correctamente');
    }

    public function quitar(Request $request, User $user)
    {
        if (auth()->user()->id === $user->id) {
            return back()->with('error', 'No puedes quitarte a ti mismo');
        }

        if (auth()->user()->branch_id !== $user->branch_id) {
            return back()->with('error', 'El usuario no pertenece a tu sucursal');
        }

        $user->branch_id = null;
        $user->role = null;
        $user->save();

        return back()->with('success', 'Usuario removido de la sucursal');
    }

    
}

