<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();
        
        return Inertia::render("clients/Index", ["clients" => $clients]);
    }

    public function show(Client $client){
        return Inertia::render("clients/Show", ["client" => $client]);
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
            'identification' => 'nullable|string|max:50|unique:clients,identification',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
        ], $messages);

        return Client::create($validated);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identification' => 'nullable|string|max:50|unique:clients,identification,' . $client->id,
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string|max:255',
        ]);

        $client->update($validated);
        return $client;
    }

    public function destroy(Client $client)
    {
        dd($client);
        $client->delete();
        return response()->noContent();
    }

    public function search(String $search){
        $clients = Client::where('name', 'like', '%' . $search . '%')
            ->orWhere('identification', 'like', '%' . $search . '%')
            ->orWhere('phone', 'like', '%' . $search . '%')
            ->orWhere('email', 'like', '%' . $search . '%')
            // ->orWhere('address', 'like', '%' . $search . '%')
            ->get();
        return $clients;
    }
}
