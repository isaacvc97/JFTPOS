<?php

namespace App\Services\Sri;

use Exception;
use DOMDocument;
use SoapClient;
use ZipArchive;
use Carbon\Carbon;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

class ComprobanteService
{
    protected string $wsdlRecepcion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    protected string $wsdlAutorizacion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';


    public function digitoVerificadorModulo11(string $clave48): string {
        $pesos = [2,3,4,5,6,7];
        $suma = 0;
        $long = strlen($clave48);
        for ($i = 0; $i < $long; $i++) {
            $digito = intval($clave48[$long - $i - 1]);
            $suma += $digito * $pesos[$i % count($pesos)];
        }
        $res = 11 - ($suma % 11);
        if ($res == 11) return '0';
        if ($res == 10) return '1';
        return (string)$res;
    }

    public function generarClaveAcceso($sale): string
    {

        $fechaEmision = Carbon::parse($sale['created_at'])->format('dmY');
        $tipoComprobante = '01'; // Factura
        $ruc = '0913401352001'; // RUC real
        $ambiente = '1'; // Producción
        $establecimiento = '001';
        $puntoEmision = '001';
        $secuencial = str_pad($sale['id'], 9, '0', STR_PAD_LEFT);
        $codigoNumerico = '12345678'; // puedes generar aleatorio si quieres
        $tipoEmision = '1'; // Emisión normal

        $base = $fechaEmision . $tipoComprobante . $ruc . $ambiente . $establecimiento . $puntoEmision . $secuencial . $codigoNumerico . $tipoEmision;

        /* $suma = 0;
        $factor = 2;
        for ($i = strlen($base) - 1; $i >= 0; $i--) {
            $suma += $factor * intval($base[$i]);
            $factor = $factor == 7 ? 2 : $factor + 1;
        }

        $modulo11 = 11 - ($suma % 11);
        $verificador = $modulo11 == 11 ? 0 : ($modulo11 == 10 ? 1 : $modulo11); */

        $digito = $this->digitoVerificadorModulo11($base);
        return (string) $base . $digito;
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

    public function firmarXml(string $xmlPath, string $p12Path, string $password, string $claveAcceso): string
    {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->load($xmlPath);

        $p12Content = file_get_contents($p12Path);
        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new Exception('No se pudo leer el archivo .p12. Verifica la contraseña.');
        }

        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $objDSig->addReference(
            $xml,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($certs['pkey'], false);

        $objDSig->sign($objKey);
        $objDSig->add509Cert($certs['cert'], true, false, ['subjectName' => true]);
        $objDSig->appendSignature($xml->documentElement);

        // $signedPath = str_replace('.xml', '_firmado.xml', $xmlPath);
        // Storage::disk('local')->put('xml/' . basename($signedPath), $xml->saveXML());

        // $xml->encoding = 'UTF-8';
        $signedPath = storage_path("app/private/xml/{$claveAcceso}.xml");
        file_put_contents($signedPath, $xml->saveXML(), LOCK_EX);
        // Storage::disk('local')->put("xml/{$claveAcceso}.xml", $xml->saveXML());

        return $signedPath;

        // return storage_path('app/private/xml/' . basename($signedPath));
    }

    public function crearZip3(string $rutaXmlFirmado, string $claveAcceso): string
    {
        $zipPath = storage_path("app/private/xml/{$claveAcceso}.zip");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception("No se pudo crear el archivo ZIP.");
        }

        $zip->addFile($rutaXmlFirmado, "{$claveAcceso}.xml");
        $zip->close();

        return $zipPath;
    }

    public function crearZip(string $rutaXmlFirmado, string $claveAcceso): string
    {
        $zipPath = storage_path("app/private/xml/{$claveAcceso}.zip");
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("No se pudo crear el archivo ZIP.");
        }

        // Asegura que el nombre dentro del zip sea solo el XML (sin path)
        $zip->addFile($rutaXmlFirmado, "{$claveAcceso}.xml");
        $zip->close();

        return $zipPath;
    }

    public function crearZip0(string $xmlFirmadoPath): string
    {
        $claveAcceso = pathinfo($xmlFirmadoPath, PATHINFO_FILENAME);
        $zipPath = dirname($xmlFirmadoPath) . "/$claveAcceso.zip";

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new Exception('No se pudo crear el ZIP del comprobante.');
        }

        $zip->addFile($xmlFirmadoPath, "$claveAcceso.xml");
        $zip->close();

        return $zipPath;
    }

    public function enviarComprobante(string $rutaZip, string $claveAcceso)
    {

        // Comprobar que el ZIP existe
        /* if (!file_exists($rutaZip)) {
            throw new Exception("El archivo ZIP no existe: $rutaZip");
        }

        // Comprobar que el ZIP contiene el XML
        $zipCheck = new ZipArchive();
        if ($zipCheck->open($rutaZip) === TRUE) {
            if ($zipCheck->numFiles === 0) {
                throw new Exception("El ZIP está vacío.");
            }

            $xmlFound = false;
            for ($i = 0; $i < $zipCheck->numFiles; $i++) {
                $filename = $zipCheck->getNameIndex($i);
                if (str_ends_with($filename, '.xml')) {
                    $xmlFound = true;
                    break;
                }
            }
            if (!$xmlFound) {
                throw new Exception("El ZIP no contiene un archivo .xml válido.");
            }

            $zipCheck->close();
        } else {
            throw new Exception("No se pudo abrir el ZIP para validación.");
        }

        // Leer y codificar
        $xmlZipContent = file_get_contents($rutaZip);
        if (!$xmlZipContent) {
            throw new Exception("No se pudo leer el contenido del ZIP.");
        }

        $xmlBase64 = base64_encode($xmlZipContent);
        if (!$xmlBase64) {
            throw new Exception("Error al codificar el ZIP en base64.");
        }

        \Log::info('ZIP Base64 Preview', [
            'base64Size' => strlen($xmlBase64),
            'firstBytes' => substr($xmlBase64, 0, 100),
        ]); */

        try {
            $contenido = file_get_contents($rutaZip);
            $base64 = base64_encode($contenido);

            $client = new SoapClient($this->wsdlRecepcion, [
                'trace' => 1,
                'exceptions' => true,
            ]);

            $params = array(
                'xml' => $contenido, // XML del comprobante sin comprimir
                'claveAcceso' => $claveAcceso // Clave de acceso del comprobante
            );

            $response = $client->validarComprobante($params);


            \Log::info('Comprobante ZIP', [
                'exists' => file_exists($rutaZip),
                'size_kb' => filesize($rutaZip) / 1024,
                'content' => base64_encode(file_get_contents($rutaZip)),
            ]);
            \Log::info('SRI SOAP Request', ['request' => $client->__getLastRequest()]);
            \Log::info('SRI SOAP Response', ['response' => $client->__getLastResponse()]);

            dd($response);
            // ✅ Log del request y response
            \Log::info("SRI ENVÍO ZIP", [
                'clave' => $claveAcceso,
                'request' => $client->__getLastRequest(),
                'response' => $client->__getLastResponse(),
                'respuesta' => $response,
                'estado' => $response->RespuestaRecepcionComprobante->estado ?? 'DESCONOCIDO'
            ]);

            return $response;
        } catch (\SoapFault $e) {
            \Log::error("ERROR envío SRI", [
                'clave' => $claveAcceso,
                'message' => $e->getMessage(),
                'request' => $client->__getLastRequest(),
                'response' => $client->__getLastResponse(),
            ]);
            return null;
        }
    }

    public function enviarComprobante0(string $zipPath): bool
    {
        $zipContent = file_get_contents($zipPath);
        $base64Zip = base64_encode($zipContent);

        $client = new SoapClient($this->wsdlRecepcion, ['trace' => true]);
        $response = $client->validarComprobante(['comprobante' => $base64Zip]);

        if ($response->RespuestaRecepcionComprobante->estado !== 'RECIBIDA') {
            $msg = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes ?? 'Sin mensajes';
            throw new Exception("No recibido: " . json_encode($msg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        return true;
    }

    public function consultarAutorizacion(string $claveAcceso): string|null
    {
        $client = new SoapClient($this->wsdlAutorizacion, ['trace' => true]);
        sleep(4);

        $response = $client->autorizacionComprobante(['claveAccesoComprobante' => $claveAcceso]);
        $respuesta = $response->RespuestaAutorizacionComprobante ?? null;

        if ((int)($respuesta->numeroComprobantes ?? 0) === 0) {
            return null;
        }

        $autorizacion = $respuesta->autorizaciones->autorizacion ?? null;

        if ($autorizacion->estado === 'AUTORIZADO') {
            return $autorizacion->comprobante->__toString();
        }

        return null;
    }
}
