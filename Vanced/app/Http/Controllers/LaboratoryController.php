<?php
namespace App\Http\Controllers;

use App\Models\Laboratory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LaboratoryController extends Controller
{
    public function index()
    {
        return Inertia::render('laboratories/Index', [
            'laboratories' => Laboratory::orderBy('name')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:laboratories,name,' . ($laboratory->id ?? 'NULL'),
            'ruc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'representative_name' => 'nullable|string|max:255',
            'representative_phone' => 'nullable|string|max:20',
        ]);

        $data = $request->only([
            'name', 'ruc', 'address', 'phone', 'representative_name', 'representative_phone'
        ]);
        Laboratory::create($data);
        return redirect()->back()->with('success', 'Laboratorio creado');
    }

    public function update(Request $request, Laboratory $laboratory)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:laboratories,name,' . ($laboratory->id ?? 'NULL'),
            'ruc' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'representative_name' => 'nullable|string|max:255',
            'representative_phone' => 'nullable|string|max:20',
        ]);

        $data = $request->only([
            'name', 'ruc', 'address', 'phone', 'representative_name', 'representative_phone'
        ]);
        $laboratory->update($data);
        return redirect()->back()->with('success', 'Laboratorio actualizado');
    }

    public function destroy(Laboratory $laboratory)
    {
        $laboratory->delete();
        return redirect()->back()->with('success', 'Laboratorio eliminado');
    }
}
