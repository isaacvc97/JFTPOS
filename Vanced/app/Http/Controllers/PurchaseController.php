<?php

namespace App\Http\Controllers;

use App\Models\CartSaleItem;
use App\Models\Sale;
use Inertia\Inertia;
use App\Models\CartSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('purchases/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente.nombre' => 'nullable|string|max:255',
            'tipo' => 'required|in:contado,credito',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia'
        ]);

        $user = auth()->user();
        $cart = CartSale::where('user_id', $user->id)
            ->where('session_id', session()->getId())
            ->where('finished', false)
            ->with('items.presentation')
            ->firstOrFail();

        if ($cart->items->isEmpty()) {
            return response()->json(['error' => 'El carrito está vacío'], 422);
        }

        DB::beginTransaction();

        try {
            $subtotal = $cart->items->sum(fn ($i) => $i->quantity * $i->price);
            $iva = $subtotal * 0.12;
            $total = $subtotal + $iva;

            $sale = Sale::create([
                'user_id' => $user->id,
                'cliente_nombre' => $request->input('cliente.nombre'),
                'tipo' => $request->input('tipo'),
                'metodo_pago' => $request->input('metodo_pago'),
                'subtotal' => $subtotal,
                'iva' => $iva,
                'total' => $total
            ]);

            foreach ($cart->items as $item) {
                CartSaleItem::create([
                    'sale_id' => $sale->id,
                    'presentation_id' => $item->presentation_id,
                    'quantity' => $item->quantity,
                    'price' => $item->price
                ]);

                // Opcional: reducir stock
                $item->presentation->decrement('stock', $item->quantity);
            }

            $cart->update(['finished' => true]);

            DB::commit();

            return response()->json(['message' => 'Venta realizada con éxito', 'venta_id' => $sale->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar venta'], 500);
        }
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
