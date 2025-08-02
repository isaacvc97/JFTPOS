<?php

namespace App\Services\Sri;

use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Storage;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use RobRichards\XMLSecLibs\XMLSecurityDSig;

class XmlSignerService
{

    public function Signer(string $xmlPath, string $p12Path, string $password, string $access_key): string
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

        $xml->encoding = 'UTF-8';
        $signedPath = storage_path("app/private/xml/{$access_key}.xml");
        file_put_contents($signedPath, $xml->saveXML(), LOCK_EX);
        // Storage::disk('local')->put("xml/{$access_key}.xml", $xml->saveXML());

        return $signedPath;

        // return storage_path('app/private/xml/' . basename($signedPath));
    }
    public function firmar(string $xmlPath, string $p12Path, string $p12Password): string
    {
        // Leer el archivo XML
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->load($xmlPath);

        // Leer el certificado .p12
        $p12Content = file_get_contents($p12Path);
        // dd($p12Path, file_exists($p12Path), filesize($p12Path));

        if (!openssl_pkcs12_read($p12Content, $certs, $p12Password)) {
            throw new Exception('No se pudo leer el archivo .p12. Verifica la contraseña.');
        }

        // Crear la firma digital
        $objDSig = new XMLSecurityDSig();
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $objDSig->addReference(
            $xml,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        // Crear la clave de firma
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($certs['pkey'], false);

        // Firmar el documento
        $objDSig->sign($objKey);
        $objDSig->add509Cert($certs['cert'], true, false, ['subjectName' => true]);

        // Insertar la firma dentro del nodo <factura>
        $objDSig->appendSignature($xml->documentElement);

        // Guardar el archivo firmado
        // $signedPath = str_replace('.xml', '_firmado.xml', $xmlPath);
        $signedPath = str_replace('.xml', '.xml', $xmlPath);
        Storage::disk('local')->put('xml/' . basename($signedPath), $xml->saveXML());

        $savePath = storage_path('app/private/xml/' . basename($signedPath));
        file_put_contents($savePath, $xml->saveXML());
        return $savePath;
        // return storage_path('app/private/xml/' . basename($signedPath));
    }
}
