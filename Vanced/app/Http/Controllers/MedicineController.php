<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Medicine;
use App\Models\Laboratory;
use App\Models\MedicineForm;
use Illuminate\Http\Request;
use App\Models\MedicineDosage;
use Illuminate\Support\Facades\DB;
use App\Models\MedicinePresentation;

class MedicineController extends Controller
{
    public function index(Request $request)
    {        
        $search = $request->input('search');
        $page = $request->input('page');

        $query = Medicine::query()->with([/* 'laboratory', */ 'dosages.form', 'dosages.presentations']);

        if ($search) {
            $query ->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('generic_name', 'like', '%' . $search . '%');
            });
        }

        $query
            ->select('id', 'name', 'generic_name', 'laboratory_id', 'description', 'created_at') // Puedes ajustar campos
            ->withCount(['dosages as total_dosages'])
            ->paginate(10, page: $page)
            ->withQueryString();

        return Inertia::render('medicines/Index', ['medicines' => $query->get()]);
    }

    public function storeFull(Request $request)
    {

        $messages = [
            'name.required' => 'El nombre attribute obligatorio',
            'name.unique' => ':input ya existe.',
            'laboratory.unique' => ':input ya existe.',
            'dosages.min' => 'Al menos 1 dosificacion es requerida.',
            'dosages.*.concentration.required' => 'La concentración es obligatoria.',
            'dosages.*.form.name.required' => 'El nombre de la forma es obligatorio.',
            /* 'dosages.[0].presentations.[0].unit_type' => 'Dosificacion - :attribute es requerido.',
            'dosages.[0].presentations.[0].quantity' => 'Dosificacion - :attribute es requerido.',
            'dosages.[0].presentations.[0].price' => 'Dosificacion - :attribute es requerido.',
            'dosages.[0].presentations.[0].stock' => 'Dosificacion - :attribute es requerido.',

            'size' => 'The :attribute must be exactly :size.',
            'between' => 'The :attribute value :input is not between :min - :max.',
            'in' => 'The :attribute must be one of the following types: :values', */
        ];
        $data = $request->validate([
            'name' => 'required|string|unique:medicines,name',

            'generic_name' => 'nullable|string',
            'laboratory' => 'string|unique:laboratory,name',
            'description' => 'nullable|string',

            'dosages' => 'required|array|min:1',
            'dosages.*.concentration' => 'required|string',
            'dosages.*.form.name' => 'required|string',
            'dosages.*.presentations' => 'required|array|min:1',
            'dosages.*.presentations.*.unit_type' => 'required|string',
            'dosages.*.presentations.*.quantity' => 'required|numeric',
            'dosages.*.presentations.*.cost' => 'required|numeric',
            'dosages.*.presentations.*.price' => 'required|numeric',
            'dosages.*.presentations.*.stock' => 'required|numeric',
        ], $messages);

        DB::beginTransaction();

        try {
            $medicine = Medicine::create([
                'name' => $data['name'],
                'generic_name' => $data['generic_name'] ?? null,
                'description' => $data['description'] ?? null,
                'laboratory' => $data['laboratory'] ?? null,
            ]);

            foreach ($data['dosages'] as $dosageData) {
                // Obtener o crear form
                $form = MedicineForm::firstOrCreate(['name' => $dosageData['form']['name']]);

                $dosage = MedicineDosage::create([
                    'medicine_id' => $medicine->id,
                    'medicine_form_id' => $form->id,
                    'concentration' => $dosageData['concentration'],
                ]);

                foreach ($dosageData['presentations'] as $presData) {
                    MedicinePresentation::create([
                        'medicine_dosage_id' => $dosage->id,
                        'unit_type' => $presData['unit_type'],
                        'quantity' => $presData['quantity'],
                        'cost' => $presData['cost'],
                        'price' => $presData['price'],
                        'stock' => $presData['stock'],
                        'barcode' => $presData['barcode'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return redirect()->back()->with('systemMessage', ['message' => 'Medicamento registrado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'laboratory_id' => 'integer|exists:laboratories,id',
            'dosages' => 'nullable|array',
            'dosages.*.id' => 'nullable|integer|exists:medicine_dosages,id',
            'dosages.*.concentration' => 'required|string|max:255',
            'dosages.*.form.name' => 'required|string|max:255',
            'dosages.*.presentations' => 'nullable|array',
            'dosages.*.presentations.*.id' => 'nullable|integer|exists:medicine_presentations,id',
            'dosages.*.presentations.*.unit_type' => 'required|string|max:50',
            'dosages.*.presentations.*.quantity' => 'required|numeric|min:1',
            'dosages.*.presentations.*.cost' => 'required|numeric|min:0',
            'dosages.*.presentations.*.price' => 'required|numeric|min:0',
            'dosages.*.presentations.*.stock' => 'required|numeric',
            'dosages.*.presentations.*.barcode' => 'nullable|string|max:255'
        ]);

        DB::transaction(function () use ($medicine, $validated) {
            // Actualizar medicamento
            $medicine->update([
                'name' => $validated['name'],
                'generic_name' => $validated['generic_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'laboratory_id' => $validated['laboratory_id'] ?? null,
            ]);

            // IDs para control de limpieza
            $existingDosageIds = [];
            $existingPresentationIds = [];

            foreach ($validated['dosages'] ?? [] as $dosageData) {
                $formId = $this->getOrCreateFormId($dosageData['form']['name']);

                // Actualizar o crear dosage
                $dosage = isset($dosageData['id'])
                    ? MedicineDosage::where('id', $dosageData['id'])->where('medicine_id', $medicine->id)->firstOrFail()
                    : $medicine->dosages()->create(['concentration' => $dosageData['concentration'], 'medicine_form_id' => $formId]);

                $dosage->update([
                    'concentration' => $dosageData['concentration'],
                    'medicine_form_id' => $formId,
                ]);

                $existingDosageIds[] = $dosage->id;

                foreach ($dosageData['presentations'] ?? [] as $presentationData) {
                    $presentation = isset($presentationData['id'])
                        ? MedicinePresentation::where('id', $presentationData['id'])->where('medicine_dosage_id', $dosage->id)->first()
                        : $dosage->presentations()->create($presentationData);

                    if ($presentation) {
                        $presentation->update([
                            'unit_type' => $presentationData['unit_type'],
                            'quantity' => $presentationData['quantity'],
                            'cost' => $presentationData['cost'],
                            'price' => $presentationData['price'],
                            'stock' => $presentationData['stock'],
                            'barcode' => $presentationData['barcode'],
                        ]);

                        $existingPresentationIds[] = $presentation->id;
                    }
                }

                // Eliminar presentaciones no incluidas
                $dosage->presentations()->whereNotIn('id', $existingPresentationIds)->delete();
            }

            // Eliminar dosages no incluidos
            $medicine->dosages()->whereNotIn('id', $existingDosageIds)->each(function ($dosage) {
                $dosage->presentations()->delete();
                $dosage->delete();
            });
        });

        return redirect()->back()->with('systemMessage', ['title' => 'Actualizacion', 'message' => 'Medicamento actualizado correctamente', 'type' => 'success']);
    }

    private function getOrCreateFormId($formName)
    {
        return MedicineForm::firstOrCreate(['name' => $formName])->id;
    }

    public function search2(Request $request)
    {
        return DB::table('product_flat_view')
            ->select('medicine_id', 'medicine_name', 'generic_name', 'concentration', 'form_name', DB::raw('SUM(stock) as stock'))
            ->where(function ($q) use ($request) {
                $q->where('medicine_name', 'like', '%' . $request->search . '%')
                  ->orWhere('generic_name', 'like', '%' . $request->search . '%');
            })
            ->groupBy('medicine_id', 'medicine_name', 'generic_name', 'concentration', 'form_name')
            ->limit(15)
            ->get();
    }

    public function presentations0($id)
    {
        return DB::table('product_flat_view')
            ->where('medicine_id', $id)
            ->select('presentation_id', 'unit_type', 'price', 'stock')
            ->get();
    }

    public function search11(Request $request)
    {
        $type = request('type', 'sale');

        
        $search = request('search', '');
        $rows = DB::table('product_flat_view')
            ->where('medicine_name', 'like','%'.$search.'%')
            ->get();
        // dd($rows);
        $medicamentos = collect($rows)->groupBy('medicine_id')->map(function ($items) use($type) {
            $first = $items->first();
            $dosages = collect($items)->groupBy('dosage_id')->map(function ($group) use($type)  {
                $first = $group->first();
                return [
                    'id' => $first->dosage_id,
                    'concentration' => $first->concentration,
                    'form' => [
                        'id' => $first->form_id,
                        'name' => $first->form_name,
                    ],
                    'presentations' => $group->map(fn($p)=> [
                        'id' => $p->presentation_id,
                        'unit_type' => $p->unit_type,
                        'quantity' => $p->quantity,
                        'price' => $type == 'sale' ? $p->price : "0.00",
                        'stock' => $p->stock,
                        'cost' => $type == 'purchase' ? $p->cost : "0.00",
                        // 'iva' => $p->iva,
                    ])->values(),
                ];
            })->values();

            return [
                'id' => $first->medicine_id,
                'name' => $first->medicine_name,
                'generic_name' => $first->generic_name,
                'dosages' => $dosages,
            ];
        })->values();

        return response()->json($medicamentos);
    }

    // /api/medicines/search?query=apr
    public function laboratory(Request $request)
    {
        return Medicine::select('laboratory')->distinct()->pluck('laboratory');
    }


    public function searchInSale(Request $request)
    {
        $search = request('query', '');

        return Medicine::with([
            'dosages.form',
            'dosages.presentations' => function ($q) {
                $q->where('stock', '>', 0);
            }
        ])
        ->where('name', 'like', '%' . $search . '%')
        ->orWhere('generic_name', 'like', '%' . $search . '%')
        ->limit(15)
        ->get();
    }
    public function search(Request $request)
    {
        $search = request('query', '');
        return Medicine::with(['laboratory','dosages.form', 'dosages.presentations'])
                ->where('name', 'like', "%{$search}%")
                ->orWhere('generic_name', 'like', "%{$search}%")
                // ->orWhereRelation('laboratory', 'name', 'like', "%{$search}%")
                ->get();
    }

    public function searchSale(string $query){
        $results = MedicineDosage::with([
                    'medicine:id,name',
                    'form:id,name',
                    'presentations:id,medicine_dosage_id,unit_type,quantity,price,stock'
                ])
                ->whereHas('medicine', fn ($q) => $q->where('name', 'like', '%' . $query . '%'))
                ->orWhere('concentration', 'like', '%' . $query . '%')
                ->take(10)
                ->get();
    
        return $results->map(function ($dosage) {
            return [
                'name' => $dosage->medicine->name .' '.$dosage->form->name.' '.$dosage->concentration,
                'medicine_id' => $dosage->medicine->id,
                'medicine_name' => $dosage->medicine->name,
                'concentration' => $dosage->concentration,
                'form' => $dosage->form->name,
                'presentation_id' => $dosage?->presentations[0]['id'],
                'unit_type' => $dosage?->presentations[0]['unit_type'],
                'quantity' => $dosage?->presentations[0]['quantity'],
                'price' => $dosage?->presentations[0]['price'],
                'presentations' => $dosage->presentations->map(fn ($p) => [
                    'id' => $p->id,
                    'value' => $p->id,
                    'label' => "{$p->unit_type} x {$p->quantity} - \${$p->price}",
                    'unit_type' => $p->unit_type,
                    'quantity' => $p->quantity,
                    'price' => $p->price,
                    'stock' => $p->stock,
                    'cost' => $p->cost,
                    'iva' => $p->iva                    
                ])->values(),
            ];
        });
    }

    public function flatSearch(string $search){
        return DB::table('product_flat_view')
            ->where('medicine_name', 'like', "%{$search}%")
            ->orWhere('generic_name', 'like', "%{$search}%")
            ->get()->map(function($item){
                return [
                    'name' => (string)$item->medicine_name.' '.$item->concentration.' '.$item->form_name.' ('. $item->unit_type.')',
                    'medicine_id' => $item->medicine_id,
                    'medicine_name' => $item->medicine_name,
                    'generic_name' => $item->generic_name,
                    // 'laboratory' => $item->laboratory,
                    'concentration' => $item->concentration,
                    'presentation_id' => $item->presentation_id,
                    'presentation' => $item->unit_type
                    // 'price' => $item->price,
                    // 'stock' => $item->stock,
                    // 'image' => $item->image,
                    ];
            });
    }

    // /api/medicines/{id}/dosages
    public function dosages($id)
    {
        return MedicineDosage::with('form')
            ->where('medicine_id', $id)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'label' => "{$d->form->name} - {$d->concentration}",
            ]);
    }
    // /api/dosages/{id}/presentations
    public function presentations($id)
    {
        $presentations = Medicine::with('dosages.presentations')->find($id);
        return $presentations;
    }

    public function laboratories(){
        return response()->json( Laboratory::all(['id', 'name']));
    }

    public function search0(Request $request)
    {
        if ($request->has('presentations')) {
            $query = $request->presentations;
            $ps = MedicinePresentation::select('unit_type')->where('unit_type', 'like', "%{$query}%")->get();
            return response()->json($ps);
        }

        /* if($request->has('laboratories')){
            $query = $request->laboratories;
            return Lab::select('name')->where('name', 'like', "%{$query}%")->get();
        } */

        $tipo = $request->input('tipo'); // 'venta' o 'inventory'
        $search = $request->input('search');
        $page = $request->input('page');

        $query = Medicine::query()->with(['dosages.form', 'dosages.presentations']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('generic_name', 'like', '%' . $search . '%');
            });
        }

        // Lógica condicional según el tipo
        if ($tipo === 'sale') {
            $result = $query->get()->map(function ($medicine) {
                return [
                    'id' => $medicine->id,
                    'name' => $medicine->name,
                    'generic_name' => $medicine->generic_name,
                    'dosages' => $medicine->dosages->map(function ($dosage) {
                        return [
                            'id' => $dosage->id,
                            'concentration' => $dosage->concentration,
                            'form' => [
                                'id' => $dosage->form->id,
                                'name' => $dosage->form->name,
                            ],
                            'presentations' => $dosage->presentations->map(function ($presentation) {
                                return [
                                    'id' => $presentation->id,
                                    'unit_type' => $presentation->unit_type,
                                    'quantity' => $presentation->quantity,
                                    'price' => $presentation->price,
                                    'stock' => $presentation->stock,
                                    'iva' => $presentation->iva ?? false, // Asume que tienes un campo `iva`
                                ];
                            }),
                        ];
                    }),
                ];
            });

            return response()->json($result);
        }

        if ($tipo === 'inventory') {
            $result = $query
                ->select('id', 'name', 'generic_name', 'created_at') // Puedes ajustar campos
                ->withCount(['dosages as total_dosages'])
                ->paginate(10, page: $page)
                ->withQueryString();

            return response()->json($result);
        }

        // Retorno por defecto si no hay tipo (opcional)
        return response()->json($query->get());
    }
}
