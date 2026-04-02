<?php
// Script para descargar y configurar el certificado SSL de PHP en XAMPP

echo "Descargando cacert.pem...\n";

// Intentar descargar el cert sin verificar SSL (bootstrap)
$context = stream_context_create([
    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    'http' => ['timeout' => 30]
]);

$cert = @file_get_contents('https://curl.se/ca/cacert.pem', false, $context);

if (!$cert) {
    // Alternativa: copiar desde Windows cert store
    echo "No se pudo descargar. Intentando alternativa...\n";
    // Usar curl para descargarlo
    $cert = shell_exec('curl -sk https://curl.se/ca/cacert.pem');
}

if ($cert && strlen($cert) > 10000) {
    $certPath = 'C:/xampp/php/cacert.pem';
    file_put_contents($certPath, $cert);
    echo "✅ cacert.pem guardado en: {$certPath} (" . strlen($cert) . " bytes)\n";

    // Leer php.ini actual
    $iniFile = 'C:/xampp/php/php.ini';
    $ini = file_get_contents($iniFile);

    // Configurar curl.cainfo
    if (strpos($ini, 'curl.cainfo') !== false) {
        $ini = preg_replace('/;?\s*curl\.cainfo\s*=.*/m', "curl.cainfo=\"{$certPath}\"", $ini);
    } else {
        $ini .= "\ncurl.cainfo=\"{$certPath}\"\n";
    }

    // Configurar openssl.cafile
    if (strpos($ini, 'openssl.cafile') !== false) {
        $ini = preg_replace('/;?\s*openssl\.cafile\s*=.*/m', "openssl.cafile=\"{$certPath}\"", $ini);
    } else {
        $ini .= "\nopenssl.cafile=\"{$certPath}\"\n";
    }

    file_put_contents($iniFile, $ini);
    echo "✅ php.ini actualizado con el certificado.\n";
    echo "Reinicia Laragon/XAMPP para aplicar los cambios.\n";
} else {
    echo "❌ No se pudo obtener el certificado.\n";
    echo "Descarga manualmente desde: https://curl.se/ca/cacert.pem\n";
    echo "Y guárdalo en: C:/xampp/php/cacert.pem\n";
}
