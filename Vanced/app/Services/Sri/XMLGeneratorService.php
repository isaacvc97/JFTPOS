<?php

use DOMDocument;

class XMLGeneratorService
{
  public function generarFacturaXml(array $sale/* , string $claveAcceso */): string
    {
        $claveAcceso = $sale['access_key'];

        if(!isset($claveAcceso)) return '';
        
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
        $infoTributaria->appendChild($xml->createElement('secuencial', str_pad($sale['id'], 9, '0', STR_PAD_LEFT)));
        $infoTributaria->appendChild($xml->createElement('dirMatriz', 'Dirección Matriz'));
        $factura->appendChild($infoTributaria);

        // === INFO FACTURA ===
        $infoFactura = $xml->createElement('infoFactura');
        $infoFactura->appendChild($xml->createElement('fechaEmision', date('d/m/Y')));
        $infoFactura->appendChild($xml->createElement('dirEstablecimiento', 'Dirección Establecimiento'));
        $infoFactura->appendChild($xml->createElement('obligadoContabilidad', 'NO'));
        $infoFactura->appendChild($xml->createElement('tipoIdentificacionComprador', '05'));
        $infoFactura->appendChild($xml->createElement('razonSocialComprador', $sale['cliente'] ?? 'CONSUMIDOR FINAL'));
        $infoFactura->appendChild($xml->createElement('identificacionComprador', $sale['cedula'] ?? '9999999999999'));
        $infoFactura->appendChild($xml->createElement('direccionComprador', $sale['direccion'] ?? 'SIN DIRECCIÓN'));
        $infoFactura->appendChild($xml->createElement('totalSinImpuestos', number_format($sale['subtotal'] ?? 9.99, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('totalDescuento', '0.00'));

        // === TOTAL CON IMPUESTOS ===
        $totalConImpuestos = $xml->createElement('totalConImpuestos');
        $totalImpuesto = $xml->createElement('totalImpuesto');
        $totalImpuesto->appendChild($xml->createElement('codigo', '2'));
        $totalImpuesto->appendChild($xml->createElement('codigoPorcentaje', '2')); // 12%
        $totalImpuesto->appendChild($xml->createElement('baseImponible', number_format($sale['subtotal'] ?? 9.99, 2, '.', '')));
        $totalImpuesto->appendChild($xml->createElement('valor', number_format($sale['iva'] ?? 0.00, 2, '.', '')));
        $totalConImpuestos->appendChild($totalImpuesto);
        $infoFactura->appendChild($totalConImpuestos);

        $infoFactura->appendChild($xml->createElement('propina', '0.00'));
        $infoFactura->appendChild($xml->createElement('importeTotal', number_format($sale['total'] ?? 9.99, 2, '.', '')));
        $infoFactura->appendChild($xml->createElement('moneda', 'DOLAR'));

        // === PAGOS ===
        $pagos = $xml->createElement('pagos');
        $pago = $xml->createElement('pago');
        $pago->appendChild($xml->createElement('formaPago', '01')); // Efectivo
        $pago->appendChild($xml->createElement('total', number_format($sale['total'] ?? 9.99, 2, '.', '')));
        $pago->appendChild($xml->createElement('plazo', '0'));
        $pago->appendChild($xml->createElement('unidadTiempo', 'dias'));
        $pagos->appendChild($pago);
        $infoFactura->appendChild($pagos);

        // Opcional: Contribuyente especial y guía
        if (!empty($sale['contribuyente_especial'])) {
            $infoFactura->appendChild($xml->createElement('contribuyenteEspecial', $sale['contribuyente_especial']));
        }
        if (!empty($sale['guia_remision'])) {
            $infoFactura->appendChild($xml->createElement('guiaRemision', $sale['guia_remision']));
        }

        $factura->appendChild($infoFactura);

        // === DETALLES ===
        $detalles = $xml->createElement('detalles');
        foreach ($sale['items'] as $item) {
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
        if (!empty($sale['email'])) {
            $campo = $xml->createElement('campoAdicional', htmlspecialchars(trim($sale['email'])));
            $campo->setAttribute('nombre', 'Email');
            $infoAdicional->appendChild($campo);
        }
        if (!empty($sale['telefono'])) {
            $campo = $xml->createElement('campoAdicional', htmlspecialchars(trim($sale['telefono'])));
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
}