<?php 

namespace App\Services\Sri;

use Exception;
use SoapClient;
use ZipArchive;

class SriSoapService
{
    protected string $wsdlRecepcion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
    protected string $wsdlAutorizacion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';


    public function sendXML(string $rutaZip, string $claveAcceso)
    {
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





    
    public function enviarXml(string $rutaXmlFirmado): string
{
    // 1. Extrae clave de acceso desde el XML firmado
    $xml = simplexml_load_file($rutaXmlFirmado);
    $claveAcceso = (string) $xml->infoTributaria->claveAcceso;

    // 2. Genera el nombre correcto del ZIP
    $nombreXml = $claveAcceso . '.xml';
    $nombreZip = $claveAcceso . '.zip';
    $rutaZip = dirname($rutaXmlFirmado) . '/' . $nombreZip;

    // 3. Copia el XML con el nombre correcto temporalmente
    $rutaXmlRenombrado = dirname($rutaXmlFirmado) . '/' . $nombreXml;
    copy($rutaXmlFirmado, $rutaXmlRenombrado);

    // 4. Crear el ZIP
    chmod($rutaXmlFirmado, 0644);
    $zip = new ZipArchive();
    if ($zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new Exception('No se pudo crear el archivo ZIP.');
    }

    $zip->addFile($rutaXmlRenombrado, $nombreXml);
    $zip->close();

    chmod($rutaZip, 0644);
    // 5. Leer y codificar en base64
    $xmlZipContent = file_get_contents($rutaZip);
    $xmlBase64 = base64_encode($xmlZipContent);

    // 6. Enviar a SRI
    $client = new SoapClient($this->wsdlRecepcion, [
        'trace' => 1,
        'exceptions' => true,
    ]);

    try {
        $response = $client->validarComprobante(['comprobante' => $xmlBase64]);
        dd($response);
    } catch (SoapFault $e) {
        dd(
            $client->__getLastRequestHeaders(),
            $client->__getLastRequest(),
            $client->__getLastResponse()
        );
        throw new Exception("Error SOAP al validar comprobante: " . $e->getMessage());
    }

    // 7. Verifica estado
    $estado = $response->RespuestaRecepcionComprobante->estado ?? 'ERROR';
    if ($estado !== 'RECIBIDA') {
        $mensajes = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes ?? null;
        throw new Exception("El SRI no recibió el comprobante. Estado: $estado\n" . json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    sleep(4); // espera para consultar autorización
    return $this->consultarAutorizacion(file_get_contents($rutaXmlFirmado));
}


    public function enviarXml2(string $rutaXmlFirmado): string
    {
        $xmlContent = file_get_contents($rutaXmlFirmado);
        $xmlBase64 = base64_encode($xmlContent);

        $client = new SoapClient($this->wsdlRecepcion, [
            'trace' => 1,
            'exceptions' => true,
        ]);

        try {
            $response = $client->validarComprobante(['comprobante' => $xmlBase64]);
            dd($response);

            echo "Estado: " . $response->RespuestaRecepcionComprobante->estado . "\n";
            $mensajes = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes ?? null;

            if ($mensajes) {
                echo "Errores:\n";
                foreach ($mensajes->mensaje as $msg) {
                    echo "- " . $msg->mensaje . ": " . $msg->informacionAdicional . "\n";
                }
            }
        } catch (SoapFault $e) {
            dd([
                'REQUEST' => $client->__getLastRequest(),
                'RESPONSE' => $client->__getLastResponse(),
                'RESULT' => $e
            ]);
        }

        $estado = $response->RespuestaRecepcionComprobante->estado ?? 'ERROR';
        if ($estado !== 'RECIBIDA') {
            $mensajes = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes ?? null;
            throw new Exception("El SRI no recibió el comprobante. Estado: $estado\n" . json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        sleep(4); // espera para que el SRI procese

        return $this->consultarAutorizacion($xmlContent);
    }


    public function enviarXml1(string $rutaXmlFirmado): string
    {
        // $rutaZip = tempnam(sys_get_temp_dir(), 'xml_') . '.zip';
        $rutaZip = dirname($rutaXmlFirmado) . '/' . pathinfo($rutaXmlFirmado, PATHINFO_FILENAME) . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($rutaZip, ZipArchive::CREATE) !== true) {
            throw new Exception('No se pudo crear el archivo ZIP.');
        }

        $nombreArchivoXml = basename($rutaXmlFirmado);
        $zip->addFile($rutaXmlFirmado, $nombreArchivoXml);
        $zip->close();

        $xmlZipContent = file_get_contents($rutaZip);
        $xmlBase64 = base64_encode($xmlZipContent);

        // $client = new SoapClient($this->wsdlRecepcion, ['trace' => true]);
        // dd($client->__getLastRequest(), $client->__getLastResponse());

        $client = new SoapClient($this->wsdlRecepcion, [
            'trace' => 1,
            'exceptions' => true,
        ]);

        try {
            $response = $client->validarComprobante(['comprobante' => $xmlBase64]);
        } catch (SoapFault $e) {
            dd($e->getMessage(), $client->__getLastRequest(), $client->__getLastResponse());
        }

        $response = $client->validarComprobante([ 'comprobante' => $xmlBase64  ]);

        $estado = $response->RespuestaRecepcionComprobante->estado ?? 'ERROR';
        if ($estado !== 'RECIBIDA') {
            // Extrae errores si existen
            $mensajes = $response->RespuestaRecepcionComprobante->comprobantes->comprobante->mensajes ?? null;
            throw new Exception("El SRI no recibió el comprobante. Estado: $estado\n" . json_encode($mensajes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        sleep(4); // espera 3 segundos antes de consultar
        return $this->consultarAutorizacion(file_get_contents($rutaXmlFirmado));

    }

    public function enviarXml0(string $rutaXmlFirmado): string
    {
        $xmlContent = file_get_contents($rutaXmlFirmado);
        $xmlBase64 = base64_encode($xmlContent);

        $client = new SoapClient($this->wsdlRecepcion);
        $params = [
            'comprobante' => $xmlBase64,
        ];

        $response = $client->validarComprobante($params);

        $estado = $response->RespuestaRecepcionComprobante->estado ?? 'ERROR';
        if ($estado !== 'RECIBIDA') {
            throw new Exception("El SRI no recibió el comprobante. Estado: $estado");
        }

        return $this->consultarAutorizacion($xmlContent);
    }

    public function consultarAutorizacion(string $xmlContent): string
    {
       $xml = simplexml_load_string($xmlContent);
        $claveAcceso = (string) $xml->infoTributaria->claveAcceso;

        $client = new SoapClient($this->wsdlAutorizacion);

        $intentos = 0;
        $maxIntentos = 3;

        while ($intentos < $maxIntentos) {
            $params = ['claveAccesoComprobante' => $claveAcceso];
            $response = $client->autorizacionComprobante($params);

            $respuesta = $response->RespuestaAutorizacionComprobante ?? null;
            if ((int)($respuesta->numeroComprobantes ?? 0) > 0) {
                break;
            }

            $intentos++;
            sleep(2); // espera entre intentos
        }

        dd($response);
        if ((int)($respuesta->numeroComprobantes ?? 0) === 0) {
            throw new Exception("El SRI no devolvió autorizaciones. La clave fue consultada correctamente pero aún no ha sido autorizada.");
        }

        if (!$respuesta) {
            throw new Exception("Respuesta inválida del SRI (sin nodo principal).");
        }

        $autorizaciones = $respuesta->autorizaciones ?? null;
        if (!$autorizaciones) {
            throw new Exception("No se encontró el nodo 'autorizaciones'. Estado posible: ".$respuesta->estado ?? 'desconocido');
        }

        $autorizacionesList = is_array($autorizaciones->autorizacion)
            ? $autorizaciones->autorizacion
            : [$autorizaciones->autorizacion];

        foreach ($autorizacionesList as $autorizacion) {
            $estado = (string) ($autorizacion->estado ?? 'SIN_ESTADO');

            if ($estado === 'AUTORIZADO') {
                return $autorizacion->comprobante->__toString();
            }

            if ($estado === 'NO AUTORIZADO') {
                $mensaje = $autorizacion->mensajes->mensaje ?? null;
                throw new Exception("Factura no autorizada: " . json_encode($mensaje, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        throw new Exception("Ningún comprobante autorizado. Verifica estado.");
    }
}
