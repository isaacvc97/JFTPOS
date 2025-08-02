<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Client;
use App\Models\CartSale;
use App\Models\CartSaleItem;
use Illuminate\Http\Request;

class CartSalesController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $sessionId = session()->getId();

        $cartSaleId = request('cart_sale_id', false);

        $cart = [];
        $carts = [];

        if($cartSaleId){
            $cart = CartSale::with(['items.presentation.dosage.form', 'items.presentation.dosage.medicine'])
                ->where('cart_sales.id', $cartSaleId)
                ->orderBy('created_at', 'asc')
                ->get();
        }else{
            $cart = CartSale::with(['client', 'items.presentation.dosage.form', 'items.presentation.dosage.medicine'])
            ->orderBy('created_at', 'asc')
                ->firstOrCreate([
                        'user_id' => $user?->id,
                        // 'session_id' => $sessionId,
                        'finished' => false,
                    ]);
    
    
            $carts = CartSale::where('user_id', $user?->id)
                ->where('finished', false)
                ->orderByDesc('created_at')
                ->get();
        }
        // return response()->json($cart);
        return Inertia::render('sales/Create', ["currentCart" => $cart, 'carts' => $carts]);

    }

    public function actives()
    {
        $user = auth()->user();

        $carts = CartSale::where('user_id', $user?->id)
            ->where('finished', false)
            ->orderByDesc('created_at')
            ->get();
        return response()->json($carts);
    }


    public function allActives()
    {
        return CartSale::where('finished', false)
            ->orderByDesc('created_at')
            ->get();
    }


    public function current()
    {
        $user = auth()->user();
        $sessionId = session()->getId();

        $cart = CartSale::with('items.presentation.dosage.medication')
            ->firstOrCreate([
                'user_id' => $user?->id,
                'session_id' => $sessionId,
                'finished' => false,
            ]);

        return response()->json($cart);
    }

    // app/Http/Controllers/CartSaleController.php

    public function items($id)
    {
        $cart = CartSale::with('items.presentation.medicine')->findOrFail($id);

        return response()->json($cart->items);
    }
    // public function items($id)
    // {
    //     $cart = CartSale::with('items.presentation.dosage.medication')->findOrFail($id);
    //     dd($cart);
    //     return response()->json($cart->items);
    // }

    /**
     * Display a listing of the resource.
     */
    public function index0()
    {
        $user = auth()->user();
        $sessionId = session()->getId();

        $cart = CartSale::with('items.presentation.dosage.form', 'items.presentation.dosage.medicine')
            ->firstOrCreate([
                'user_id' => $user?->id,
                // 'session_id' => $sessionId,
                'finished' => false,
            ]);
        // dd($cart);
        $items = $cart->items->map(function ($item) {
            $cantidad = $item->cantidad;
            $presentation = $item->presentation;
            $dosage = $presentation->dosage;
            $med = $dosage->medicine;

            // Buscar todas las presentaciones de la misma dosificación
            $otrasPresentaciones = $dosage->presentations->map(function ($p) {
                return [
                    'id' => $p->id,
                    'label' => "{$p->unit_type} x {$p->quantity} - \${$p->price}",
                    'data' => [
                        'id' => $p->id,
                        'unit_type' => $p->unit_type,
                        'quantity' => $p->quantity,
                        'price' => $p->price,
                        'stock' => $p->stock,
                        'cost' => $p->cost,
                        'iva' => $p->iva,
                    ]
                ];
            });

            return [
                'medicine_id' => $med->id,
                'medicine_name' => $med->name,
                'generic_name' => $med->generic_name,
                'dosage_id' => $dosage->id,
                'concentration' => $dosage->concentration,
                'form_id' => $dosage->form->id,
                'form_name' => $dosage->form->name,
                'presentation_id' => $presentation->id,
                'presentations' => $otrasPresentaciones,
                'unit_type' => $presentation->unit_type,
                'quantity' => $item->quantity,
                'price' => (float) $presentation->price,
                'stock' => $presentation->stock,
                'cost' => $presentation->cost,
                'cantidad' => $cantidad,
            ];
        });

        $cart = [
            'id' => $cart->id,
            'created_at' => $cart->created_at,
            'items' => $items
        ];
        
            // dd($cart);
        $carts = CartSale::select("id", "created_at")
                ->where("user_id", $user?->id)
                ->where("session_id", $sessionId)
                ->where("finished", false)
                ->get();

        

        return response()->json(["carts" => $carts, "cart" => $cart]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request);
        // $user = auth()->user();
        // $sessionId = session()->getId();

        // $cartSale = CartSale::create([
        //     'user_id' => $user?->id,
        //     'session_id' => $sessionId,
        //     'finished' => false,
        // ]);
        
        // $cart = ["id" => $cartSale->id, "items" => [], "created_at" => $cartSale->created_at];

        // return response()->json($cart);
        
        // $cart = CartSale::firstOrCreate(
        // ['id' => $request['cart_sale_id']],
        // [
        //             'user_id' => auth()->id(),
        //             // 'sessind_id' => session()->getId()
        //         ]
        // );

        // $request['cart_sale_id'] = $cart->id;


        $validated = $request->validate([
            'cart_sale_id' => 'required|exists:cart_sales,id',
            'medicine_id' => 'required|exists:medicines,id',
            'presentation_id' => 'required|exists:medicine_presentations,id',
            'cantidad' => 'required|integer|min:1',
            'descuento' => 'nullable|min:0',
        ]);

        $presentation = \DB::table('medicine_presentations as mp')
                ->join('medicine_dosages as md', 'mp.medicine_dosage_id', '=', 'md.id')
                ->where('mp.id', $validated['presentation_id'])
                // ->where('md.medicine_id', $validated['medicine_id'])
                ->select('mp.*', 'md.*')
                ->firstOrFail();

                // dd($presentation);
            if (!$presentation) {
                abort(404, 'Presentación no válida para este medicamento.');
            }


        $subtotal = ($presentation->price * $presentation->quantity) * (1 - ($validated['descuento'] ?? 0) / 100);

        $item_id = CartSaleItem::insertGetId([
            'cart_sale_id' => $validated['cart_sale_id'],
            'medicine_presentation_id' => $validated['presentation_id'],
            'cantidad' => $validated['cantidad'],
            'precio' => $presentation->price,
            // 'descuento' => $validated['descuento'],
            'subtotal' => $subtotal,
        ]);

        return response()->json(['message' => 'Añadido correctamente.', "id" => $item_id], 200);
        // return redirect()->back()->with('message', [
        //     'type' => 'success',
        //     'message' => 'Añadido correctamente.',
        // ]); 
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cart = CartSale::with('items.presentation.dosage.form', 'items.presentation.dosage.medicine')
            ->findOrFail($id);
        // dd($cart);
        $items = $cart->items->map(function ($item) {
            $presentation = $item->presentation;
            $dosage = $presentation->dosage;
            $med = $dosage->medicine;

            // Buscar todas las presentaciones de la misma dosificación
            $otrasPresentaciones = $dosage->presentations->map(function ($p) {
                return [
                    'id' => $p->id,
                    'label' => "{$p->unit_type} x {$p->quantity} - \${$p->price}",
                    'data' => [
                        'id' => $p->id,
                        'unit_type' => $p->unit_type,
                        'quantity' => $p->quantity,
                        'price' => $p->price,
                        'stock' => $p->stock,
                        'cost' => $p->cost,
                        'iva' => $p->iva,
                    ]
                ];
            });

            return [
                'medicine_id' => $med->id,
                'medicine_name' => $med->name,
                'generic_name' => $med->generic_name,
                'dosage_id' => $dosage->id,
                'concentration' => $dosage->concentration,
                'form_id' => $dosage->form->id,
                'form_name' => $dosage->form->name,
                'presentation_id' => $presentation->id,
                'presentations' => $otrasPresentaciones,
                'unit_type' => $presentation->unit_type,
                'quantity' => $item->quantity,
                'price' => (float) $presentation->price,
                'stock' => $presentation->stock,
                'cost' => $presentation->cost,
            ];
        });

        return response()->json([
            'id' => $cart->id,
            'created_at' => $cart->created_at,
            'items' => $items
        ]);
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        $cart = CartSale::firstOrCreate(
        ['id' => $request['cart_sale_id']],
        [
                    'user_id' => auth()->id(),
                    'sessind_id' => session()->getId()
                ]
        );

        $request['cart_sale_id'] = $cart->id;


        $validated = $request->validate([
            'presentation_id' => 'required|exists:medicine_presentations,id',
            'cantidad' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'cart_sale_id' => 'required|exists:cart_sales,id'
        ]);
        // dd($validated);

        $subtotal = ($validated['precio'] * $validated['cantidad']) * (1 - ($validated['descuento'] ?? 0) / 100);

        $item = CartSaleItem::create([
            'cart_sale_id' => $validated['cart_sale_id'],
            'medicine_presentation_id' => $validated['presentation_id'],
            'cantidad' => $validated['cantidad'],
            'precio' => $validated['precio'],
            'descuento' => $validated['descuento'] ?? 0,
            'subtotal' => $subtotal
        ]);

        return response()->json($item, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CartSaleItem $cartItem, Request $request)
    {
        
        // dd($cart);


        $validated = $request->validate([
            'cart_sale_id' => 'required|exists:cart_sales,id',
            'presentation_id' => 'required|exists:medicine_presentations,id',
            'cantidad' => 'required|integer|min:1',
            // 'precio' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
        ]);

        $presentation = \DB::table('medicine_presentations as mp')
                ->join('medicine_dosages as md', 'mp.medicine_dosage_id', '=', 'md.id')
                ->where('mp.id', $validated['presentation_id'])
                // ->where('md.medicine_id', $validated['medicine_id'])
                ->select('mp.*', 'md.*')
                ->firstOrFail();

                // dd($presentation);
            if (!$presentation) {
                abort(404, 'Presentación no válida para este medicamento.');
            }


        $subtotal = ($presentation->price * $presentation->quantity) * (1 - ($validated['descuento'] ?? 0) / 100);

        // $cart = CartSaleItem::find($validated['cart_sale_id']);

        $cartItem->cantidad = $validated['cantidad'];

        $cartItem->medicine_presentation_id = $validated['presentation_id'];

        $cartItem->subtotal = $subtotal;

        $cartItem->descuento = $validated['descuento'] ?? 0;

        $cartItem->save();

        return response()->json(['message'=> 'Se modifico carrito.', 'item' => $cartItem], 200);
        // return response()->json($cart, 201);
    }

    /**
     * Remove the specified resource from storage.
     */

    public function remove(CartSaleItem $item)
    {
 
        // dd($item);

        $item->delete();

        return response()->json("Eliminado correctamente.", 200);
    }
    public function destroy(CartSale $cart)
    {
        // $flight = CartSale::find($id);
 
        // dd($cart);

        $cart->delete();

        return redirect('sales/create');
        return redirect()->back()->with('message', [
            'type' => 'error',
            'message' => 'Se elimino carrito.'
        ]);
    }

    public function client(CartSale $cart, Request $request)
    {
        // dd($cart);

        $cart->client_id = $request->client_id;
        $cart->save();

         return back()->with('message',[
            'type' => 'info',
            'message' => 'Se establecio cliente.',
        ]);
    }


    public function addItem(Request $request)
    {

       $cart = CartSale::firstOrCreate(
        ['id' => $request['cart_sale_id']],
        [
                    'user_id' => auth()->id(),
                    'sessind_id' => session()->getId()
                ]
        );

        $request['cart_sale_id'] = $cart->id;


        $validated = $request->validate([
            'presentation_id' => 'required|exists:medicine_presentations,id',
            'cantidad' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0|max:100',
            'cart_sale_id' => 'required|exists:cart_sales,id'
        ]);
        // dd($validated);

        $subtotal = ($validated['precio'] * $validated['cantidad']) * (1 - ($validated['descuento'] ?? 0) / 100);

        $item = CartSaleItem::create([
            'cart_sale_id' => $validated['cart_sale_id'],
            'medicine_presentation_id' => $validated['presentation_id'],
            'cantidad' => $validated['cantidad'],
            'precio' => $validated['precio'],
            'descuento' => $validated['descuento'] ?? 0,
            'subtotal' => $subtotal
        ]);

        return response()->json($item, 201);
    }
}
