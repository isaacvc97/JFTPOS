<?php

namespace App\Http\Controllers;

use App\Models\CartSale;
use App\Models\CartSaleItem;
use Illuminate\Http\Request;
use App\Models\MedicinePresentation;

class CartPurchaseController extends Controller
{
    public function index()
    {

        $user = auth()->user();
        $cart = CartSale::firstOrCreate([
            'user_id' => $user->id,
            // 'session_id' => session()->getId(),
            'finished' => false
        ]);

        $items = $cart->items()->with('presentation')->get();
        return $items;
    }

    public function store(Request $request)
    {
        $request->validate([
            'presentation_id' => 'required|exists:medicine_presentations,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $user = auth()->user();
        $cart = CartSale::firstOrCreate([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'finished' => false
        ]);

        $presentation = MedicinePresentation::findOrFail($request->presentation_id);
        // dd($presentation);
        $item = $cart->items()->create([
            'medicine_presentation_id' => $presentation->id,
            'cantidad' => $request->quantity,
            'precio' => $presentation->price,
            'subtotal' => $presentation->price * $request->quantity
        ]);

        return response()->json($item->load('presentation'));
    }

    public function update(Request $request, $id)
    {
        $item = CartSaleItem::findOrFail($id);

        $request->validate([
            'quantity' => 'nullable|integer|min:1',
            'presentation_id' => 'nullable|exists:presentations,id'
        ]);

        if ($request->has('quantity')) {
            $item->quantity = $request->quantity;
        }

        if ($request->has('presentation_id')) {
            $presentation = MedicinePresentation::findOrFail($request->presentation_id);
            $item->presentation_id = $presentation->id;
            $item->price = $presentation->price;
        }

        $item->save();
        return response()->json($item->load('presentation'));
    }

    public function destroy($id)
    {
        CartSaleItem::findOrFail($id)->delete();
        return response()->json(['message' => 'Item eliminado']);
    }
}
