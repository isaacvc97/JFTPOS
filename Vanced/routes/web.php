<?php

use App\Http\Controllers\InvitationController;
use Inertia\Inertia;
use App\Models\MedicineForm;
use App\Models\MedicinePresentation;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use App\Http\Controllers\CashController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\ClientController;

use App\Http\Controllers\AccountController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\CartSalesController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\CartPurchaseController;
use App\Http\Controllers\DatabaseBackupController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard/Index');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware(['auth','verified',])->group(function () {
    
    Route::inertia('/assistant', 'assistant/Assistant');
    Route::inertia('/whatsapp', 'whatsapp/Index');

    /* Route::post('/medicine-store', function (Request $req) {
        return ['status' => 'ok'];
    }); */

    // ROUTES AUTENTICATED

    Route::prefix('medicines')->name('medicines.')->controller(MedicineController::class)->group(function () {

        route::get('/', 'index')
            /* ->name('medicines') */;

        route::get('/forms', 'forms');

        route::put('/', 'storeFull')
            ->name("store");

        route::post('/update/{medicine}', 'update')
            ->name("update");

        route::get('/search', 'search')
            ->name('search');

        route::get('/search-flat/{query}', 'flatSearch')
            ->name('flat');

        route::get('/search-sale/{query}', 'searchSale')
            ->name('sale');

        // Route::get('/{id}/dosages', 'dosages');
        Route::get('/presentations/{id}', 'presentations');
        Route::get('/laboratories', 'laboratories');

    });


    Route::get('/search-web', function()
    {

        $route = request('estructura', null);

        if(!$route) return;

        $response = Http::timeout(120)->get('http://puppeteer:8002/'.$route.'/search', [
                    'query' => request('query')
                ]);
        // dd(request('query'));

        return response()->json($response->json());
    })->name('web-search');

    Route::get('/search-web/details', function()
    {
        $linkCodificado = request('url');

        if (!$linkCodificado) {
            return redirect()->back()->with('message','Debe enviar un link');
        }

        // Decodificar el link
        $link = urldecode($linkCodificado);

        // Validar que sea un link de Fybeca
        // if (!Str::startsWith($link, 'https://www.fybeca.com')) {
        //     return redirect()->back()->with('message','Link no válido');
        // }
        
        $response = Http::timeout(120)->get('http://puppeteer:8002/details', [
            'link' => $link
        ]);

        return response()->json($response->json());
    })->name('web-details');

    /* Route::get('/laboratories', [LaboratoryController::class, 'index']); */

     Route::resource('laboratories', LaboratoryController::class)->only(['index', 'store', 'update', 'destroy']);


    Route::prefix('purchases')->name('purchases.')->controller(PurchaseController::class)->group(function () {

        Route::get('/', 'index')->name(name: 'index');

        Route::get('/create', 'create')->name('create');

        Route::post('/', 'store')->name('store');

        Route::get('/{sale}', 'show')->name('show');

        Route::prefix('cart')->name('cart.')->controller(CartPurchaseController::class)->group(function () {
            
            Route::get('/', 'index')->name('index');               // Mostrar carrito actual
            Route::post('/', 'store')->name('store');              // Agregar al carrito
            Route::get('/{id}', 'show')->name('show');             // Ver producto en carrito
            Route::put('/{id}', 'update')->name('update');         // Actualizar cantidad o descuento
            Route::delete('/{id}', 'destroy')->name('destroy');    // Eliminar del carrito
            
            /* Route::get('/actives', 'actives')
                ->name('.cart.actives'); */

            /* Route::get('/current', 'current'); */
        });
        
        /* route::put('/store', 'store')
            ->name('store'); */
    });

    /* route::get('/purchases/create', [PurchaseController::class, 'create'])
        ->name('purchases.create'); */


    Route::prefix('sales')->name('sales.')->controller(SaleController::class)->group(function () {

        Route::get('/', 'index')->name(name: 'index');

        Route::get('/create', 'create')->name('create');

        Route::post('/', [SaleController::class, 'store'])->name('store');

        Route::get('/history/{sale}', [SaleController::class, 'show'])->name('show');


        Route::post('/sri/{id}', [SaleController::class, 'declareSale'])->name('sri');
        Route::post('/sri/{access_key}/check', [SaleController::class, 'getAuthorization'])->name('authorization');


        Route::prefix('/cart')->name('cart.')->controller(CartSalesController::class)->group(function () {
            
            Route::get('/', 'index')->name('index');                   // Mostrar carrito actual

            Route::post('/', 'store')->name('store');                  // Agregar al carrito
            // Route::get('/show/{id}', 'show')->name('show');                            // Ver producto en carrito
            Route::put('/update/{cartItem}', 'update')->name('update');    // Actualizar cantidad o descuento

            Route::delete('/remove/{item}', 'remove')->name('remove'); // Eliminar del carrito

            Route::delete('/delete/{cart}', 'destroy')->name('destroy');// Destruir carrito


            Route::patch('/store/{cart}', 'client')->name('client');    // Destruir carrito
            
            /* Route::get('/actives', 'actives')
                ->name('.cart.actives'); */

            /* Route::get('/current', 'current'); */
        });
        
        route::put('/store', 'store')
            ->name('.store');
    });

    /* route::get('/sales/kardex', 'kardex')
        ->name('sales.create'); */

    Route::resource('accounts', AccountController::class)->except('show');
    Route::post('/accounts/{account}/payments', [AccountController::class, 'storePayment']);

    // Route::get('accounts', function(){
    //     return Inertia::render('accounts/Index');
    // });

    Route::prefix('cashregisters')->name('cash.')->controller(CashController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{register}', 'update')->name('update');
        Route::delete('/{register}', 'delete')->name('delete');

        Route::prefix('/movements')->name('movements')->group(function(){
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::delete('/{movements}', 'delete')->name('delete');
            Route::delete('/{movements}', 'delete')->name('delete');
        });
    });

    Route::prefix('branches')->name('branch.')->controller(BranchController::class)->group(function () {
        
        Route::get('/', 'index');
        Route::get('/{id}', 'show')->name('show');
        Route::post('/', 'store');
        
        Route::post('/{id}/roles', 'cambiarRol');
        Route::delete('/{branchId}/users/{userId}', 'quitarUsuario');
    });

    
    Route::prefix('invitations')->name('invitations.')->controller(InvitationController::class)->group(function () {
        
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/aceptar/{token}', 'accept');
        Route::post('/rechazar/{token}', 'rechazar');
        Route::delete('/{id}', function ($id) {
                dd(auth()->user()->notifications()->where('id', $id)->delete());
                return response()->json(['ok' => true]);
            });
        // Route::delete('/invitations/{id}', [InvitationController::class, 'destroy']);
        
    });

    Route::name('clients.')->prefix('clients')->controller(ClientController::class)->group(function () {
        Route::get('/', 'index')
            /* ->name('clients') */;

        Route::get('/{client}', 'show');

        Route::post('/', 'store')
            /* ->name('clients') */;

        Route::put('/{client}', 'update')
            /* ->name('clients') */;

        Route::delete('/{client}', 'destroy')
            /* ->name('.delete') */;

        Route::get('/search/{query}', 'search')
            ->name('search');
    });
    
    Route::name('users')->prefix('users')->controller(UserController::class)->group(function () {
        Route::get('/', 'index')
            /* ->name('users') */;

        Route::put('/', 'store')
            /* ->name('users') */;

        Route::post('/{user}', 'update')
            /* ->name('users') */;

        Route::delete('/{user}', 'destroy')
            /* ->name('.delete') */;
    });

    
    Route::name('settings')->prefix('settings/backup')->controller(DatabaseBackupController::class)->group(function () {

        Route::get('/', 'index')
            ->name('.backup');

        Route::post('/', 'generate');

        Route::post('/import', 'import');

        Route::get('/tables', 'tables');  
         
        Route::get('/list', 'listBackups');

        Route::get('/download/{filename}', 'downloadBackup');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
