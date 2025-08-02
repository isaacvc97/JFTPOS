<?php

use App\Models\Medicine;
use App\Models\MedicineForm;

use Illuminate\Http\Request;
use App\Models\MedicineDosage;
use Illuminate\Support\Facades\DB;
use App\Models\MedicinePresentation;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CartSalesController;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/medicine-forms', function(){
    $forms = MedicineForm::all();
    return response()->json($forms);
});

Route::get('/medicine-presentations', function(){
    $presentations = MedicinePresentation::all();
    return response()->json($presentations);
});

Route::post('/medicine-store', function (Request $request) {
    // return ['status' => $request->all()];
    // dd($request);
        try {
        $data = $request->validate([
            'name' => 'required|string',
            'generic_name' => 'nullable|string',
            'description' => 'nullable|string',
            'dosages' => 'required|array',
            'dosages.*.concentration' => 'required|string',
            'dosages.*.form.name' => 'required|string',
            'dosages.*.presentations' => 'required|array',
            'dosages.*.presentations.*.unit_type' => 'required|string',
            'dosages.*.presentations.*.quantity' => 'required|numeric',
            'dosages.*.presentations.*.price' => 'required|numeric',
            'dosages.*.presentations.*.stock' => 'required|numeric',
        ]);

        DB::beginTransaction();

        $medicine = Medicine::create([
            'name' => $data['name'],
            'generic_name' => $data['generic_name'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        foreach ($data['dosages'] as $dosageData) {
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
                    'price' => $presData['price'],
                    'stock' => $presData['stock'],
                    'barcode' => $presData['barcode'] ?? null,
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'ok' => true,
            'message' => 'Medicamento registrado correctamente.',
            'data' => $medicine->load('dosages.form', 'dosages.presentations'),
        ], 201);

    } catch (ValidationException $e) {
        return response()->json([
            'ok' => false,
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'ok' => false,
            'errors' => 'Error al guardar: ' . $e->getMessage(),
        ], 500);
    }
});


Route::get('/medications/search', [\App\Http\Controllers\Api\MedicationSearchController::class, 'index']);


route::get('/medicines/search', function (Request $request)
{
    // return ['query' => $request];

    // if ($request->has('presentations')) {
    //     $query = $request->presentations;
    //     $ps = MedicinePresentation::select('unit_type')->where('unit_type', 'like', "%{$query}%")->get();
    //     return response()->json($ps);
    // }

    /* if($request->has('laboratories')){
        $query = $request->laboratories;
        return Lab::select('name')->where('name', 'like', "%{$query}%")->get();
    } */

    $search = $request->input('search');

    $query = Medicine::query()->with(['dosages.form', 'dosages.presentations']);

    if ($search) {
        $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhere('generic_name', 'like', '%' . $search . '%')
            ->select('id', 'name', 'generic_name', 'created_at') // Puedes ajustar campos
            ->withCount(['dosages as total_dosages']);

        }
            return response()->json($query->get());
});

route::get('/medicines/{nombre}/stock', function ($nombre)
{
    $medicine = Medicine::with(['dosages.presentations'])
        ->where('name', 'like', "%$nombre%")
        ->orWhere('generic_name', 'like', "%$nombre%")
        ->first();

    if (!$medicine) {
        return response()->json(['ok' => false, 'message' => 'Medicamento no encontrado'], 404);
    }

    // Recolectar todos los stocks de sus presentaciones
    $stocks = [];

    foreach ($medicine->dosages as $dosage) {
        foreach ($dosage->presentations as $presentation) {
            $stocks[] = [
                'concentration' => $dosage->concentration,
                'form' => $dosage->form->name ?? null,
                'quantity' => $presentation->quantity,
                'unit_type' => $presentation->unit_type,
                'stock' => $presentation->stock,
            ];
        }
    }

    return response()->json([
        'medicine' => $medicine->name,
        'stocks' => $stocks,
    ]);
});

route::get('/medicines/{nombre}/get', function ($nombre)
{
    $medicine = Medicine::with(['dosages.form', 'dosages.presentations'])
        ->where('name', 'like', "%$nombre%")
        ->orWhere('generic_name', 'like', "%$nombre%")
        ->first();

    if (!$medicine) {
        return response()->json(['error' => true, 'message' => 'Medicamento no encontrado'], 404);
    }

    return response()->json($medicine);
});


Route::middleware(['auth:web'])->get('/check-auth', fn() => Auth::user());