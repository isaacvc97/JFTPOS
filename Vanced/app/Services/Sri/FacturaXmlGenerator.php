<?php

namespace App\Services\Sri;

use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FacturaXmlGenerator
{


    public function generarClaveAcceso($fecha, $tipoComprobante, $ruc, $ambiente, $establecimiento, $puntoEmision, $secuencial, $codigoNumerico, $tipoEmision)
    {
        $clave = $fecha . $tipoComprobante . $ruc . $ambiente .
                $establecimiento . $puntoEmision . $secuencial .
                $codigoNumerico . $tipoEmision;

        $suma = 0;
        $factor = 2;

        for ($i = strlen($clave) - 1; $i >= 0; $i--) {
            $suma += $factor * intval($clave[$i]);
            $factor = $factor == 7 ? 2 : $factor + 1;
        }

        $modulo11 = 11 - ($suma % 11);
        if ($modulo11 == 11) $modulo11 = 0;
        elseif ($modulo11 == 10) $modulo11 = 1;

        return $clave . $modulo11;
    }


    public function generarClaveAcceso1(
        string $fechaEmision, // formato ddmmaaaa
        string $tipoComprobante,
        string $ruc,
        string $ambiente,
        string $establecimiento,
        string $puntoEmision,
        string $secuencial, // 9 dígitos
        string $codigoNumerico,
        string $tipoEmision
    ): string {
        $serie = $establecimiento . $puntoEmision;

        $clave = $fechaEmision
            . $tipoComprobante
            . $ruc
            . $ambiente
            . $serie
            . $secuencial
            . $codigoNumerico
            . $tipoEmision;

        // Calcular dígito verificador
        $digitoVerificador = $this->modulo11($clave);
        return $clave . $digitoVerificador;
    }

    private function modulo11(string $clave): int {
        $baseMultiplicador = [2,3,4,5,6,7];
        $suma = 0;
        $factorIndex = 0;

        // De derecha a izquierda
        for ($i = strlen($clave) - 1; $i >= 0; $i--) {
            $suma += ((int)$clave[$i]) * $baseMultiplicador[$factorIndex];
            $factorIndex = ($factorIndex + 1) % count($baseMultiplicador);
        }

        $modulo = $suma % 11;
        $verificador = 11 - $modulo;

        if ($verificador == 10) return 1;
        if ($verificador == 11) return 0;
        return $verificador;
    }

    private function generarClaveAcceso0(
        string $fechaEmision, // ddmmaaaa
        string $tipoComprobante, // 01 = factura
        string $ruc,
        string $ambiente, // 1 = pruebas, 2 = producción
        string $establecimiento, // 001
        string $puntoEmision, // 001
        string $secuencial, // 000000123
        string $codigoNumerico, // 8 dígitos
        string $tipoEmision // 1 = normal
    ): string {
        $clave = $fechaEmision
            . $tipoComprobante
            . $ruc
            . $ambiente
            . $establecimiento
            . $puntoEmision
            . $secuencial
            . $codigoNumerico
            . $tipoEmision;

        return $clave . $this->calcularDigitoVerificador($clave);
    }

    private function calcularDigitoVerificador(string $clave): string
    {
        $base = [2, 3, 4, 5, 6, 7];
        $suma = 0;
        $claveInvertida = strrev($clave);

        for ($i = 0; $i < strlen($claveInvertida); $i++) {
            $suma += ((int)$claveInvertida[$i]) * $base[$i % count($base)];
        }

        $modulo = 11 - ($suma % 11);
        if ($modulo == 11) return '0';
        if ($modulo == 10) return '1';
        return (string)$modulo;
    }

    public $claveAcceso = null;

    public function generar(array $venta): array
    {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        $factura = $xml->createElement('factura');
        $factura->setAttribute('id', 'comprobante');
        $factura->setAttribute('version', '2.0.1');

        // infoTributaria
        $factura->appendChild($this->infoTributaria($xml, $venta));

        // infoFactura
        $factura->appendChild($this->infoFactura($xml, $venta));

        // detalles
        $factura->appendChild($this->detalles($xml, $venta));

        $xml->appendChild($factura);

        // Guarda archivo
        $nombreArchivo = 'factura_' . $venta['id'] . '.xml';
        $ruta = storage_path("app/xml/{$nombreArchivo}");

        // dd($xml->saveXML());
        $xmlString = $xml->saveXML();
        file_put_contents(storage_path("app/xml/{$nombreArchivo}"), $xmlString);

        // Storage::disk('local')->put("xml/{$nombreArchivo}", $xml->saveXML());
        return [
            'path' => $ruta,
            'clave_acceso' => $this->claveAcceso,
        ];

    }

    private function infoTributaria($xml, $venta)
    {
        $fechaEmision = Carbon::parse($venta['created_at'])->format('dmY');
        $tipoComprobante = '01'; // Factura
        $ruc = '0913401352001'; // RUC real
        $ambiente = '1'; // Producción
        $establecimiento = '001';
        $puntoEmision = '001';
        $secuencial = str_pad($venta['id'], 9, '0', STR_PAD_LEFT);
        $codigoNumerico = '12345678'; // puedes generar aleatorio si quieres
        $tipoEmision = '1'; // Emisión normal

        $this->claveAcceso = $this->generarClaveAcceso(
            $fechaEmision,
            $tipoComprobante,
            $ruc,
            $ambiente,
            $establecimiento,
            $puntoEmision,
            $secuencial,
            $codigoNumerico,
            $tipoEmision
        );

        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('ambiente', $ambiente));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', $tipoEmision));
        $infoTributaria->appendChild($xml->createElement('razonSocial', 'PIÑA ORTIZ FRANCISCO JAVIER'));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', 'IMPORTADORA DISTRIBUIDORA COMERCIAL PIÑA'));
        $infoTributaria->appendChild($xml->createElement('ruc', $ruc));
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $this->claveAcceso));
        $infoTributaria->appendChild($xml->createElement('codDoc', $tipoComprobante));
        $infoTributaria->appendChild($xml->createElement('estab', $establecimiento));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', $puntoEmision));
        $infoTributaria->appendChild($xml->createElement('secuencial', $secuencial));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'HUANCAVILCA 500 Y CORONEL'));
        $infoTributaria->appendChild($xml->createElement('agenteRetencion', '1'));

        return $infoTributaria;
    }


    private function infoTributaria0($xml, $venta)
    {

        // dd( Carbon::parse($venta['created_at'])->format('dmY'));

        $fechaEmision = Carbon::parse($venta['created_at'])->format('dmY');
        $tipoComprobante = '01'; // Factura
        $ruc = '0913401352001'; // tu RUC
        $ambiente = '1'; // pruebas
        $establecimiento = '001';
        $puntoEmision = '001';
        $secuencial = str_pad($venta['id'], 9, '0', STR_PAD_LEFT);
        $codigoNumerico = '12345678'; // Puedes hacerlo aleatorio si deseas
        $tipoEmision = '1'; // normal

        $this->claveAcceso = $this->generarClaveAcceso(
            $fechaEmision,
            $tipoComprobante,
            $ruc,
            $ambiente,
            $establecimiento,
            $puntoEmision,
            $secuencial,
            $codigoNumerico,
            $tipoEmision
        );

        $infoTributaria = $xml->createElement('infoTributaria');
        $infoTributaria->appendChild($xml->createElement('claveAcceso', $this->claveAcceso));
        $infoTributaria->appendChild($xml->createElement('ambiente', $ambiente));
        $infoTributaria->appendChild($xml->createElement('tipoEmision', $tipoEmision));
        $infoTributaria->appendChild($xml->createElement('razonSocial', 'Tu Negocio S.A.'));
        $infoTributaria->appendChild($xml->createElement('nombreComercial', 'Tu Farmacia'));
        $infoTributaria->appendChild($xml->createElement('ruc', $ruc));
        $infoTributaria->appendChild($xml->createElement('codDoc', $tipoComprobante));
        $infoTributaria->appendChild($xml->createElement('estab', $establecimiento));
        $infoTributaria->appendChild($xml->createElement('ptoEmi', $puntoEmision));
        $infoTributaria->appendChild($xml->createElement('secuencial', $secuencial));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'Guayaquil'));

        /* $info = $xml->createElement('infoTributaria');
        $info->appendChild($xml->createElement('ambiente', '1'));
        $info->appendChild($xml->createElement('tipoEmision', '1'));
        $info->appendChild($xml->createElement('razonSocial', 'FARMACIA ISAAC'));
        $info->appendChild($xml->createElement('nombreComercial', 'FARMACIA ISAAC'));
        $info->appendChild($xml->createElement('ruc', '1790017884001'));
        $info->appendChild($xml->createElement('claveAcceso', '0000000000000000000000000000000000000000000000'));
        $info->appendChild($xml->createElement('codDoc', '01'));
        $info->appendChild($xml->createElement('estab', '001'));
        $info->appendChild($xml->createElement('ptoEmi', '001'));
        $info->appendChild($xml->createElement('secuencial', str_pad($venta['id'], 9, '0', STR_PAD_LEFT)));
        $info->appendChild($xml->createElement('dirMatriz', 'Av. Siempre Viva 123')); */

        
        return $infoTributaria;
    }

    private function infoFactura($xml, $venta)
    {
        $info = $xml->createElement('infoFactura');
        $info->appendChild($xml->createElement('fechaEmision', Carbon::parse($venta['created_at'])->format('d/m/Y')));
        $info->appendChild($xml->createElement('dirEstablecimiento', 'Av. Farmacia 123'));
        $info->appendChild($xml->createElement('obligadoContabilidad', 'SI'));
        $info->appendChild($xml->createElement('tipoIdentificacionComprador', '05'));
        $info->appendChild($xml->createElement('razonSocialComprador', $venta['client']['name']));
        $info->appendChild($xml->createElement('identificacionComprador', $venta['client']['identification']));
        $info->appendChild($xml->createElement('totalSinImpuestos', $venta['total']));
        $info->appendChild($xml->createElement('totalDescuento', '0.00'));

        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $impuesto = $xml->createElement('totalImpuesto');
        $impuesto->appendChild($xml->createElement('codigo', '2'));
        $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2'));
        $impuesto->appendChild($xml->createElement('baseImponible', $venta['total']));
        $impuesto->appendChild($xml->createElement('valor', number_format($venta['total'] * 0.12, 2, '.', '')));
        $totalConImpuestos->appendChild($impuesto);
        $info->appendChild($totalConImpuestos);

        $info->appendChild($xml->createElement('propina', '0.00'));
        $info->appendChild($xml->createElement('importeTotal', number_format($venta['total'] * 1.12, 2, '.', '')));
        $info->appendChild($xml->createElement('moneda', 'DOLAR'));

        return $info;
    }

    private function detalles($xml, $venta)
    {
        $detalles = $xml->createElement('detalles');

        foreach ($venta['items'] as $item) {
            $detalle = $xml->createElement('detalle');
            $detalle->appendChild($xml->createElement('codigoPrincipal', 'MED' . $item['id']));
            $detalle->appendChild($xml->createElement('descripcion', $item['formatted']));
            $detalle->appendChild($xml->createElement('cantidad', $item['quantity']));
            $detalle->appendChild($xml->createElement('precioUnitario', $item['price']));
            $detalle->appendChild($xml->createElement('descuento', '0.00'));
            $detalle->appendChild($xml->createElement('precioTotalSinImpuesto', $item['subtotal']));

            $impuestos = $xml->createElement('impuestos');
            $impuesto = $xml->createElement('impuesto');
            $impuesto->appendChild($xml->createElement('codigo', '2'));
            $impuesto->appendChild($xml->createElement('codigoPorcentaje', '2'));
            $impuesto->appendChild($xml->createElement('tarifa', '15'));
            $impuesto->appendChild($xml->createElement('baseImponible', $item['subtotal']));
            $impuesto->appendChild($xml->createElement('valor', number_format($item['subtotal'] * 0.12, 2, '.', '')));

            $impuestos->appendChild($impuesto);
            $detalle->appendChild($impuestos);
            $detalles->appendChild($detalle);
        }

        return $detalles;
    }
}
