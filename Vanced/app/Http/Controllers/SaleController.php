<?php

namespace App\Http\Controllers;

use App\Models\Client;
use SoapClient;
use DOMDocument;
use Carbon\Carbon;
use App\Models\Sale;
use Inertia\Inertia;
use App\Enums\SaleType;
use App\Models\Account;
use App\Models\CartSale;
use App\Models\SaleItem;
use App\Enums\SaleStatus;
use App\Models\CartSaleItem;
use Illuminate\Http\Request;
use App\Enums\SalePaymentType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\Sri\SriSoapService;
use Illuminate\Support\Facades\Auth;
use App\Services\Sri\XmlSignerService;
use Illuminate\Support\Facades\Storage;
use App\Services\Sri\FacturaXmlGenerator;


class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with(['client', 'user', 'items'])
            ->when(request('client', false), fn($query, $client) => $query->where('client_id', $client))
            ->when(request('state', false), fn($query, $state) => $query->where('status', $state))
            ->when(request('rangeDate', false), fn($query, $state) => $query->whereBetween('created_at', $state))
            ->when(request('product', false), fn($query, $product) => 
                $query->whereHas('items', fn($q) => $q->where('medicine_presentation_id', $product))
            )->orderBy('created_at', 'DESC')
            ->get();
        
        $clients = Client::all();
        
        return Inertia::render('sales/Index', [
            'sales' => $sales,
            'clients' => $clients,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $sessionId = session()->getId();
        $selectedCartId = request('cart_sale_id', false);
        $clientId = request('client_id', null);

        // Si se envió un ID, intenta cargar ese carrito
        if ($selectedCartId) {
            $cart = CartSale::with([
                'client',
                'items.presentation.dosage.form',
                'items.presentation.dosage.medicine',
                'items.presentation.dosage.presentations',
            ])->where('user_id', $user?->id)
            ->where('id', $selectedCartId)
            ->where('finished', false)
            ->first();
        }

        // Si no existe o no se envió, buscar el último carrito activo o crear uno nuevo
        if (!isset($cart)) {
            $cart = CartSale::with([
                'client',
                'items.presentation.dosage.form',
                'items.presentation.dosage.medicine',
                'items.presentation.dosage.presentations',
            ])->firstOrCreate(
                [
                    'user_id' => $user?->id,
                    'finished' => false,
                    // 'client_id' => $clientId
                ]
            );
        }

        // Mapear items
        $items = $cart->items->map(fn($item) => $this->mapCartItem($item));

        // dd($cart);
        return Inertia::render('sales/Create', [
            'currentCart' => [
                'id' => $cart->id,
                'client' => $cart->client,
                'payment_type' => $cart?->payment_type ?? 'Efectivo',
                'sale_type' => $cart?->sale_type ?? 'Contado',
                'created_at' => $cart->created_at,
                'items' => $items,
                'note' => $cart->note,
            ],
            'carts' => CartSale::select('id', 'created_at')
                ->where('user_id', $user?->id)
                ->where('finished', false)
                ->orderByDesc('created_at')
                // ->limit(20)
                ->get(),
        ]);
    }

    public function show($id){
       /*  dd(['id' => $sale->id,
                'client' => $sale->load('client'),
                'paymentType' => $sale->paymentType,
                'saleType' => $sale->saleType,
                'created_at' => $sale->created_at,
                'items' => $sale->items->map(fn($item) => $this->mapItem($item)),
                ]); */


       $sale = Sale::with([
            'client',
            'items.presentation.dosage.form',
            'items.presentation.dosage.medicine',
        ])->findOrFail($id);

        return response()->json($this->mapSale($sale));
    }

    public function mapSale(Sale $sale): array
    {
        // Asegúrate de que estas relaciones estén precargadas para evitar N+1
        $sale->loadMissing(['client', 'items.presentation.dosage.medicine', 'items.presentation.dosage.form']);

        return [
            'id' => $sale->id,
            'access_key' => $sale->acces,
            'client_data' => $sale->client,
            'client' => $sale->client?->name,
            'cedula' => $sale->client?->identification,
            'email' => 'cliente@correo.com',
            'seller' => $sale->user,
            'payment_type' => $sale->payment_type,
            'sale_type' => $sale->sale_type,
            'items' => $sale->items->map(fn ($item) => $this->mapItem($item)),
            'total' => $sale->total,
            'pago' => $sale->pago,
            'cambio' => $sale->cambio,
            'status' => $sale->status,
            'authorized' => $sale->authorized,
            'created_at' => $sale->created_at,
        ];
    }

    public function mapItem($item): array
    {
        $p = $item->presentation;
        $d = $p?->dosage;
        $m = $d?->medicine;
        $f = $d?->form;

        return [
            'id' => $item->id,
            'presentation_id' => $p?->id,
            'medicine_name' => $m?->name,
            'generic_name' => $m?->generic_name,
            'form_name' => $f?->name,
            'concentration' => $d?->concentration,
            'unit_type' => $p?->unit_type,
            'quantity_unit' => $p?->quantity,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'subtotal' => $item->subtotal,
            'formatted' => $m && $d && $f && $p
                ? "{$m->name} ({$m->generic_name}) - {$d->concentration} - {$f->name} - {$p->unit_type} x {$p->quantity}"
                : null,
            'formatted_name' => $m && $d && $f 
                ? "{$m->name} {$d->concentration} {$f->name}"
                : null,
            'formatted_presentation' => $p
                ? "{$p->unit_type} x {$p->quantity}"
                : null,
        ];
    }

    private function mapCartItem(CartSaleItem $item): array
    {
        $presentation = $item->presentation;
        $dosage = $presentation->dosage;
        $medicine = $dosage->medicine;

        return [
            'id' => $item->id,
            'medicine_id' => $medicine->id,
            // 'name' => $medicine->name,
            'name' => $medicine->name .' '.$dosage->form->name.' '.$dosage->concentration,
            'generic_name' => $medicine->generic_name,
            'dosage_id' => $dosage->id,
            'concentration' => $dosage->concentration,
            'form_id' => $dosage->form->id,
            'form_name' => $dosage->form->name,
            'presentation_id' => $item->medicine_presentation_id,
            'presentations' => $dosage->presentations->map(fn($p) => [
                'id' => $p->id,
                'label' => "{$p->unit_type} x {$p->quantity} - \${$p->price}",
                'value' => $p->id,
                'unit_type' => $p->unit_type,
                'quantity' => $p->quantity,
                'price' => $p->price,
                'stock' => $p->stock,
                'cost' => $p->cost,
                'iva' => $p->iva,
            ]),
            'unit_type' => $presentation->unit_type,
            'quantity' => $item->cantidad,
            'price' => (float) $presentation->price,
            'stock' => $presentation->stock,
            'cost' => $presentation->cost,
            'cantidad' => $item->cantidad,
            'discount' => $item->descuento,
        ];
    }
    public function store0(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'pago' => 'required|numeric|min:0',
            'cambio' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            /* 'items' => 'required|array|min:1',
            'items.*.presentation_id' => 'required|exists:medicine_presentations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0', */
        ]);

        return DB::transaction(function () use ($request) {
            $sale = Sale::create([
                'client_id' => $request->client_id,
                'user_id' => Auth::id(),
                'total' => $request->total,
                'pago' => $request->pago,
                'cambio' => $request->cambio,
            ]);

            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'presentation_id' => $item['presentation_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ]);
            }

            return response()->json(['message' => 'Venta registrada correctamente.', 'id' => $sale->id], 201);
        });
    }

    public $saleId = null;


    public function store(Request $request)
    {
        // dd(SaleType::cases());
        $request->validate([
            'cart_sale_id' => 'required|exists:cart_sales,id',
            'client_id' => 'nullable|exists:clients,id',
            'sale_type' => ['required', Rule::enum(SaleType::class)],
            'payment_type' => ['required', Rule::enum(SalePaymentType::class)],
            'discount' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'pago' => 'required|numeric|min:0',
            'cambio' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);


        DB::transaction(function () use ($request) {
            $cart = CartSale::with([
                // 'items.presentation.dosage.form',
                // 'items.presentation.dosage.medicine',
                // 'items.presentation.dosage.presentations',
                'client'
            ])->findOrFail($request->cart_sale_id);

            if ($cart->finished) return response()->json(['message' => 'Este carrito ya ha sido procesado.'], 400);
            
            // CONVIERTE EL INPUT DE sale_type EN UN ENUM
            $saleType = SaleType::tryFrom($request->sale_type);
            // VALIDA EL ENUM
            $status = $saleType === SaleType::CONTADO
                ? SaleStatus::PROCESADA
                : SaleStatus::PENDIENTE;
            
            // dd($status);

            $sale = Sale::create([
                'client_id' => $request->client_id ?? 1,
                'user_id' => Auth::id(),
                'total' => $request->total,
                'pago' => $request->pago,
                'cambio' => $request->cambio,
                'sale_type' => $request->sale_type,
                'payment_type' => $request->payment_type,
                'discount' => $request->discount ?? 0,
                'status' => $status->value,
                'note' => $request->note,
            ]);
            
            foreach ($cart->items as $item) {
                // dd($item);
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'medicine_presentation_id' => $item->medicine_presentation_id,
                    'quantity' => $item->cantidad,
                    'price' => $item->precio,
                    'discount' => $item->descuento ?? 0,
                    'subtotal' => $item->subtotal,
                ]);

                // Opcional: reducir stock de la presentación
                $item->presentation->decrement('stock', $item->cantidad);
            }

            // Marcar el carrito como finalizado
            $cart->update(['finished' => true]);

            if ($saleType === SaleType::CREDITO) {
                Account::create([
                    'type' => 'por_cobrar',
                    'status' => 'pendiente',
                    'amount' => $sale->total,
                    'client_id' => $request->client_id,
                    'sale_id' => $sale->id,
                    'due_date' => now()->addDays(30),
                ]);
            }

            // if ($purchase->payment_type === 'credito') {
            // Account::create([
            //     'type' => 'por_pagar',
            //     'status' => 'pendiente',
            //     'amount' => $purchase->total,
            //     'laboratory_id' => $purchase->laboratory_id,
            //     'purchase_id' => $purchase->id,
            //     'due_date' => now()->addDays(30),
            // ]);
            // }
            // $cart->delete();

            // $current_sale = Sale::with([
            //                         'client',
            //                         'items.presentation.dosage.form',
            //                         'items.presentation.dosage.medicine',
            //                     ])->findOrFail($sale->id);

            // $this->saleId = $sale->id;
            // dd($current_sale);
            // GENERAR EL XML PARA SRI
            // $venta = Sale::with('client', 'items')->findOrFail($sale->id)->toArray();



            // 1.- GENERA EL XML
            // $data = app(FacturaXmlGenerator::class)->generar($this->mapSale($current_sale));
            // $rutaXml = $data['path'];

            
            // return response()->download($rutaXml);

            // return $this->create();
            /* return response()->json([
                'message' => 'Venta registrada correctamente.',
                'id' => $sale->id
            ], 201); */
        });
        
        // if(!$this->saleId){ response()->json('No se encontro venta.',  500); }

        
        // 2 .- FIRMA (.P12) EL XML 
        // return $this->firmarFactura($this->saleId);

    }

    public function declareSale($id)
    {

        $sale = Sale::with('client',
                        'items.presentation.dosage.form',
                        'items.presentation.dosage.medicine'
                        )->findOrFail($id);

        $saleFormatted = $this->mapSale($sale);


        $sri = new \App\Services\Sri\ComprobanteService();

        /* $clave = $sri->generarClaveAcceso(
            $fechaEmision,
            $tipoComprobante,
            $ruc,
            $ambiente,
            $establecimiento,
            $puntoEmision,
            $secuencial,
            $codigoNumerico,
            $tipoEmision
        ); */
        $clave = $sri->generarClaveAcceso($saleFormatted);

        $sale->access_key = $clave;
        $sale->save();

        $rutaXml= $sri->generarFacturaXml($saleFormatted, $clave);
 
        $rutaP12 = storage_path("app/firmas/0913401352.p12");
        $claveFirma = 'LINA091@'; // tu clave real
        $rutaFirmado = $sri->firmarXml($rutaXml, $rutaP12, $claveFirma, $clave);
        // $rutaZip = $sri->crearZip($rutaFirmado, $clave);

        $sri->enviarComprobante($rutaFirmado, $clave);
        // $respuesta = $sri->consultarAutorizacion($clave);
        // dd($clave);

        // dd($clave, $rutaFirmado, /* $rutaZip, */ $sri, $respuesta);
        // $rutaFirmada = app(XmlSignerService::class)->firmar($rutaXml, $rutaP12, $claveP12);

        // return response()->download($rutaFirmada);
    }

    protected string $wsdlAutorizacion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';


    public function getAuthorization($access_key){
        

        $client = new SoapClient($this->wsdlAutorizacion, ['trace' => true]);
        sleep(4);

        $response = $client->autorizacionComprobante(['claveAccesoComprobante' => $access_key]);
        $respuesta = $response->RespuestaAutorizacionComprobante ?? null;


        dd($response);

        if ((int)($respuesta->numeroComprobantes ?? 0) === 0) {
            return null;
        }

        $autorizacion = $respuesta->autorizaciones->autorizacion ?? null;

        if ($autorizacion->estado === 'AUTORIZADO') {
            return $autorizacion->comprobante->__toString();
        }

        return null;
    }





    // 3.- SUBE LA FACTURA A SRI
    public function enviarFacturaAlSri($id)
    {
        $rutaFirmado = storage_path("app/private/xml/factura_{$id}.xml");

        // dd($rutaFirmado);

        $xmlAutorizado = app(SriSoapService::class)->enviarXml($rutaFirmado);

        Storage::put("xml/factura_{$id}_autorizado.xml", $xmlAutorizado);


        // Guardar en sri_logs
        /* 
            SriLog::create([
                'sale_id' => $venta->id,
                'clave_acceso' => $claveAcceso,
                'estado' => 'NO AUTORIZADO',
                'mensajes' => [
                    'mensaje' => 'La respuesta no contiene autorizaciones.',
                    'clave_acceso' => $claveAcceso,
                ],
                'xml_autorizado' => null,
            ]);

        
            SriLog::create([
            'sale_id' => $venta->id,
            'clave_acceso' => $data['clave_acceso'],
            'estado' => 'AUTORIZADO',
            'mensajes' => null,
            'fecha_autorizacion' => now(),
            'xml_autorizado' => $xmlAutorizado,
        ]); */

        return response()->json(['mensaje' => 'Factura autorizada', 'xml' => $xmlAutorizado]);
    }
    public function destroy(CartSale $cart)
    {
 
        // dd($cart);

        $cart->delete();

        return redirect()->back()->with('message', [
            'type' => 'error',
            'message' => 'Se elimino carrito.'
        ]);
    }

    public function generarFacturaXml(array $venta, string $claveAcceso): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '1.1.0');

        // === INFO TRIBUTARIA ===
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', '1'));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', '1'));
        $infoTributaria->appendChild($xml->createElement('razonSocial', 'EMPRESA DE PRUEBA S.A.'));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', 'PRUEBA SRI'));
        $infoTributaria->appendChild($xml->createElement('ruc', '0913401352001'));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $claveAcceso));
        $infoTributaria->appendChild($xml->createElement('codDoc', '01'));
        $infoTributaria->appendChild($xml->createElement('estab', '001'));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', '001'));
        $infoTributaria->appendChild($xml->createElement('secuencial', str_pad($venta['id'], 9, '0', STR_PAD_LEFT)));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'Dirección Matriz'));
        $factura->appendChild($infoTributaria);

        // === INFO FACTURA ===
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', date('d/m/Y')));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', 'Dirección Establecimiento'));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', 'NO'));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', '05'));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $venta['cliente'] ?? 'CONSUMIDOR FINAL'));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $venta['cedula'] ?? '9999999999999'));
        $infoFactura->appendChild($xml->createElement('direccionComprador', $venta['direccion'] ?? 'SIN DIRECCIÓN'));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($venta['subtotal'] ?? 9.99, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));

        // === TOTAL CON IMPUESTOS ===
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', '2'));
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 12%
        $totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($venta['subtotal'] ?? 9.99, 2, '.', '')));
        $totalImpuesto->appendChild($xml->createElement('valor', number_format($venta['iva'] ?? 0.00, 2, '.', '')));
        $totalConImpuestos->appendChild($totalImpuesto);
        $infoFactura->appendChild($totalConImpuestos);

        $infoFactura->appendChild($xml->createElement('propina', '0.00'));
        $infoFactura->appendChild($xml->createElement('importeTotal', number_format($venta['total'] ?? 9.99, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('moneda', 'DOLAR'));

        // === PAGOS ===
        $pagos = $xml->createElement('pagos');
        $pago = $xml->createElement('pago');
        $pago->appendChild($xml->createElement('formaPago', '01')); // Efectivo
        $pago->appendChild($xml->createElement('total', number_format($venta['total'] ?? 9.99, 2, '.', '')));
        $pago->appendChild($xml->createElement('plazo', '0'));
        $pago->appendChild($xml->createElement('unidadTiempo', 'dias'));
        $pagos->appendChild($pago);
        $infoFactura->appendChild($pagos);

        // Opcional: Contribuyente especial y guía
        if (!empty($venta['contribuyente_especial'])) {
            $infoFactura->appendChild($xml->createElement('contribuyenteEspecial', $venta['contribuyente_especial']));
        }
        if (!empty($venta['guia_remision'])) {
            $infoFactura->appendChild($xml->createElement('guiaRemision', $venta['guia_remision']));
        }

        $factura->appendChild($infoFactura);

        // === DETALLES ===
        $detalles = $xml->createElement('detalles');
        foreach ($venta['items'] as $item) {
            $detalle = $xml->createElement('detalle');
            $detalle->appendChild($xml->createElement('codigoPrincipal', $item['id'] ?? '000000'));
            $detalle->appendChild($xml->createElement('codigoAuxiliar', $item['codigo_aux'] ?? $item['id'] ?? '000000'));
            $detalle->appendChild($xml->createElement('descripcion', $item['formatted'] ?? 'PRODUCTO 1'));
            $detalle->appendChild($xml->createElement('cantidad', number_format($item['cantidad'] ?? 1, 2, '.', '')));
            $detalle->appendChild($xml->createElement('precioUnitario', number_format($item['price'] ?? 0.25, 2, '.', '')));
            $detalle->appendChild($xml->createElement('descuento', '0.00'));
            $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', number_format($item['subtotal'] ?? 0.25, 2, '.', '')));

            $impuestos = $xml->createElement('impuestos');
            $impuesto = $xml->createElement('impuesto');
            $impuesto->appendChild($xml->createElement('codigo', '2'));
            $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2'));
            $impuesto->appendChild($xml->createElement('tarifa', '12.00'));
            $impuesto->appendChild($xml->createElement('baseImponible', number_format($item['subtotal'] ?? 0.25, 2, '.', '')));
            $impuesto->appendChild($xml->createElement('valor', number_format(($item['iva'] ?? ($item['subtotal'] * 0.12)), 2, '.', '')));
            $impuestos->appendChild($impuesto);
            $detalle->appendChild($impuestos);

            $detalles->appendChild($detalle);
        }
        $factura->appendChild($detalles);

        // === INFO ADICIONAL ===
        $infoAdicional = $xml->createElement('infoAdicional');
        if (!empty($venta['email'])) {
            $campo = $xml->createElement('campoAdicional', htmlspecialchars(trim($venta['email'])));
            $campo->setAttribute('nombre', 'Email');
            $infoAdicional->appendChild($campo);
        }
        if (!empty($venta['telefono'])) {
            $campo = $xml->createElement('campoAdicional', htmlspecialchars(trim($venta['telefono'])));
            $campo->setAttribute('nombre', 'Teléfono');
            $infoAdicional->appendChild($campo);
        }
        $factura->appendChild($infoAdicional);

        $xml->appendChild($factura);

        // === GUARDAR XML ===
        $rutaXml = storage_path("app/private/xml/{$claveAcceso}.xml");
        $xml->save($rutaXml);

        return $rutaXml;
    }


    public function generarFacturaXml1(array $venta, string $claveAcceso): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '2.0.1');

        // INFO TRIBUTARIA
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', '1')); // 1 = pruebas
        $infoTributaria->appendChild($xml->createElement('tipoEmision', '1'));
        $infoTributaria->appendChild($xml->createElement('razonSocial', 'EMPRESA DE PRUEBA S.A.'));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', 'PRUEBA SRI'));
        $infoTributaria->appendChild($xml->createElement('ruc', '0913401352001'));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $claveAcceso));
        $infoTributaria->appendChild($xml->createElement('codDoc', '01'));
        $infoTributaria->appendChild($xml->createElement('estab', '001'));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', '001'));
        $infoTributaria->appendChild($xml->createElement('secuencial', str_pad($venta['id'], 9, '0', STR_PAD_LEFT)));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'Dirección Matriz'));
        $factura->appendChild($infoTributaria);

        // INFO FACTURA
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', date('d/m/Y')));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', 'Dirección Establecimiento'));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', 'NO'));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', '05'));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $venta['cliente'] ?? 'CONSUMIDOR FINAL'));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $venta['cedula'] ?? '9999999999999'));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($venta['subtotal'] ?? '9.99', 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));

        // Total impuestos
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', '2'));
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 12%
        $totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($venta['subtotal'] ?? '9.99', 2, '.', '')));
        $totalImpuesto->appendChild($xml->createElement('valor', number_format($venta['iva']?? '9.99', 2, '.', '')));
        $totalConImpuestos->appendChild($totalImpuesto);
        $infoFactura->appendChild($totalConImpuestos);

        $infoFactura->appendChild($xml->createElement('propina', '0.00'));
        $infoFactura->appendChild($xml->createElement('importeTotal', number_format($venta['total']?? '9.99', 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('moneda', 'DOLAR'));

        // Pagos
        $pagos = $xml->createElement('pagos');
        $pago = $xml->createElement('pago');
        $pago->appendChild($xml->createElement('formaPago', '01')); // Efectivo
        $pago->appendChild($xml->createElement('total', number_format($venta['total']?? '9.99', 2, '.', '')));
        $pago->appendChild($xml->createElement('plazo', '0'));
        $pago->appendChild($xml->createElement('unidadTiempo', 'dias'));
        $pagos->appendChild($pago);
        $infoFactura->appendChild($pagos);

        $factura->appendChild($infoFactura);

        // DETALLES
        $detalles = $xml->createElement('detalles');
        foreach ($venta['items'] as $item) {
            $detalle = $xml->createElement('detalle');
            $detalle->appendChild($xml->createElement('codigoPrincipal', $item['id'] ?? '000000'));
            $detalle->appendChild($xml->createElement('descripcion', $item['formatted'] ?? 'PRODUCTO 1'));
            $detalle->appendChild($xml->createElement('cantidad', number_format($item['cantidad'] ?? 1, 2, '.', '')));
            $detalle->appendChild($xml->createElement('precioUnitario', number_format($item['price'] ?? '0.25', 2, '.', '')));
            $detalle->appendChild($xml->createElement('descuento', '0.00'));
            $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', number_format($item['subtotal'] ?? '0.25', 2, '.', '')));

            $impuestos = $xml->createElement('impuestos');
            $impuesto = $xml->createElement('impuesto');
            $impuesto->appendChild($xml->createElement('codigo', '2'));
            $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2'));
            $impuesto->appendChild($xml->createElement('tarifa', '12.00'));
            $impuesto->appendChild($xml->createElement('baseImponible', number_format($item['subtotal'] ?? '0.25', 2, '.', '')));
            $impuesto->appendChild($xml->createElement('valor', number_format($item['subtotal'] ?? '0.25', 2, '.', '')));
            $impuestos->appendChild($impuesto);
            $detalle->appendChild($impuestos);

            $detalles->appendChild($detalle);
        }
        $factura->appendChild($detalles);

        // INFO ADICIONAL
        $infoAdicional = $xml->createElement('infoAdicional');
        // if (!empty($venta['email'])) {
            $campo = $xml->createElement('campoAdicional', htmlspecialchars($venta['email']));
            $campo->setAttribute('nombre', 'Email');
            $infoAdicional->appendChild($campo);
        // }
        $factura->appendChild($infoAdicional);

        $xml->appendChild($factura);

        // Guardar archivo
        $rutaXml = storage_path("app/private/xml/{$claveAcceso}.xml");
        $xml->save($rutaXml);

        return $rutaXml;
    }


    public function generarFacturaXml0(array $venta, $claveAcceso): string
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '2.0.1');

        // INFO TRIBUTARIA
        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', '1')); // 1 = pruebas
        $infoTributaria->appendChild($xml->createElement('tipoEmision', '1')); // 1 = normal
        $infoTributaria->appendChild($xml->createElement('razonSocial', 'EMPRESA DE PRUEBA S.A.'));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', 'PRUEBA SRI'));
        $infoTributaria->appendChild($xml->createElement('ruc', '0913401352001'));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $claveAcceso/* $venta['claveAcceso'] */));
        $infoTributaria->appendChild($xml->createElement('codDoc', '01')); // Factura
        $infoTributaria->appendChild($xml->createElement('estab', '001'));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', '001'));
        $infoTributaria->appendChild($xml->createElement('secuencial', str_pad($venta['id'], 9, '0', STR_PAD_LEFT)));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'Dirección Matriz'));
        // $infoTributaria->appendChild($xml->createElement('agenteRetencion', '1'));

        $factura->appendChild($infoTributaria);

        // INFO FACTURA
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', date('d/m/Y')));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', 'Dirección Establecimiento'));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', 'NO'));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', '05'));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $venta['cliente'] ?? 'CONSUMIDOR FINAL'));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $venta['cedula'] ?? '9999999999999'));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($venta['subtotal'] ?? 1.00, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));

        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', '2')); // IVA
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 12% o 15%
        $totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($venta['subtotal'] ?? 1.00, 2, '.', '')));
        $totalImpuesto->appendChild($xml->createElement('valor', number_format($venta['iva'] ?? 0.15, 2, '.', '')));
        $totalConImpuestos->appendChild($totalImpuesto);

        $infoFactura->appendChild($totalConImpuestos);
        $infoFactura->appendChild($xml->createElement('propina', '0.00'));
        $infoFactura->appendChild($xml->createElement('importeTotal', number_format($venta['total'] ?? 1.15, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('moneda', 'DOLAR'));

        $factura->appendChild($infoFactura);

        // DETALLES
        $detalles = $xml->createElement('detalles');
        $detalle = $xml->createElement('detalle');
        $detalle->appendChild($xml->createElement('codigoPrincipal', 'PROD001'));
        $detalle->appendChild($xml->createElement('descripcion', 'Producto de prueba'));
        $detalle->appendChild($xml->createElement('cantidad', '1'));
        $detalle->appendChild($xml->createElement('precioUnitario', '1.00'));
        $detalle->appendChild($xml->createElement('descuento', '0.00'));
        $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', '1.00'));

        $impuestos = $xml->createElement('impuestos');
        $impuesto = $xml->createElement('impuesto');
        $impuesto->appendChild($xml->createElement('codigo', '2'));
        $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2'));
        $impuesto->appendChild($xml->createElement('tarifa', '15.00'));
        $impuesto->appendChild($xml->createElement('baseImponible', '1.00'));
        $impuesto->appendChild($xml->createElement('valor', '0.15'));
        $impuestos->appendChild($impuesto);
        $detalle->appendChild($impuestos);
        $detalles->appendChild($detalle);

        $factura->appendChild($detalles);

        $xml->appendChild($factura);

        // Guardar
        // $claveAcceso = $venta['claveAcceso'];
        $rutaXml = storage_path("app/private/xml/{$claveAcceso}.xml");
        $xml->save($rutaXml);

        return $rutaXml;
    }
}
