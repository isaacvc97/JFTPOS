<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Laboratory;
use Inertia\Inertia;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('accounts/Index', [
            'accounts' => Account::with(['client', 'laboratory', 'payments'])->get(),
            'clients' => Client::get(),
            'providers' => Laboratory::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $request['client_id'] == 'por_cobrar' ? $request->client_id : null;
        $request['laboratory_id'] == 'por_pagar' ? $request->laboratory_id : null;
        
         Account::create($request->validate([
            'name' => 'required|string',
            'client_id' => 'nullable|exists:clients,id',
            'laboratory_id' => 'nullable|exists:laboratories,id',
            'amount' => 'required|numeric',
            'type' => 'required|in:por_pagar,por_cobrar',
            'status' => 'required|in:pendiente,pagado,vencido',
            'contact_id' => 'nullable|exists:contacts,id',
        ]));

        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Account $account)
    {
        $account->update($request->validate([
            'name' => 'required|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:por_pagar,por_cobrar',
            'status' => 'required|in:pendiente,pagado,vencido',
            'contact_id' => 'nullable|exists:contacts,id',
        ]));

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function storePayment(Request $request, Account $account)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'method' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $account->payments()->create($data);
        $account->recalculateStatus();
        $account->save();

        return back()->with('success', 'Pago registrado');
    }
}
