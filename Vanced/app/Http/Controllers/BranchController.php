<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller {
    public function index() {

        $branches = Branch::with('users')->where('owner_id', auth()->id())->get();
        
        return Inertia::render('branches/Branchmanager', ['branches' => $branches]);
    }

    public function show($id) {
        $branch = Branch::with(['users', 'invitacionesPendientes'])
            ->where('id', $id)
            ->where('owner_id', auth()->id())
            ->firstOrFail();

        return Inertia::render('branches/Show', [
            'branch' => $branch
        ]);
    }

    public function store(Request $request) {
        $branch = Branch::create([...$request->all(), 'owner_id' => auth()->id()]);
        $branch->users()->attach(auth()->id(), ['role' => 'administrador']);
        return $branch;
    }

    public function cambiarRol(Request $request, $id) {
        DB::table('branch_user')
            ->where('branch_id', $id)
            ->where('user_id', $request->user_id)
            ->update(['role' => $request->role]);

        return response()->json(['ok' => true]);
    }

    public function quitarUsuario($branchId, $userId) {
        DB::table('branch_user')
            ->where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['ok' => true]);
    }
    
}

