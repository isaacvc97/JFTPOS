<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashController extends Controller
{
    public function index(){

        $cash = [];
        
        $exist = DB::table('cash_registers')
            ->insertGetId(['user_id' => auth()->id()]);

        // dd($exist);
        if($exist) $cash = $exist;

        return Inertia::render('cashregisters/Index', ['data', $cash]);
    }

    public function store(Request $request){
        $request->validate([
            'initial_amount' => 'nullable|numeric',
            'user' => 'nullable|exists:users,id',
            'note' => 'nullable|string',
        ]);

        if(!isset($request['user'])) dd(auth()->id());

        $data = DB::table('cash_registers')->insertGetId(
            [
                'initial_amount' => $request->cash || 0,
                'user_id' => $request->user || auth()->id(),
                'note' => $request->note || "",
            ]);

        return response()->json(['data' => $data]);
    }
}
