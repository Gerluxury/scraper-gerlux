<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

function get_remote_html($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function parse_mobile_de($html) {
    $data = [];
    if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $m)) {
        $data["imagen"] = $m[1];
    }
    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/', $html, $m)) {
        $titulo = strip_tags($m[1]);
        $partes = explode(" ", $titulo, 3);
        $data["marca"] = $partes[0] ?? '';
        $data["modelo"] = $partes[1] ?? '';
    }
    if (preg_match('/Erstzulassung.*?<div[^>]*>(\d{4})<\/div>/s', $html, $m)) {
        $data["ano"] = $m[1];
    }
    if (preg_match('/Leistung.*?<div[^>]*>(\d+)[^<]*kW<\/div>/s', $html, $m)) {
        $data["potencia"] = round($m[1] * 1.36);
    }
    if (preg_match('/Kraftstoff.*?<div[^>]*>([^<]+)<\/div>/s', $html, $m)) {
        $data["combustible"] = trim($m[1]);
    }
    if (preg_match('/"priceCurrency":"EUR","price":"(\d+)"/', $html, $m)) {
        $data["precio"] = $m[1];
    }
    return $data;
}

function parse_autoscout24($html) {
    $data = [];
    if (preg_match('/"image":"([^"]+)"/', $html, $m)) {
        $data["imagen"] = str_replace('\\u002F', '/', $m[1]);
    }
    if (preg_match('/"make":"([^"]+)"/', $html, $m)) {
        $data["marca"] = $m[1];
    }
    if (preg_match('/"model":"([^"]+)"/', $html, $m)) {
        $data["modelo"] = $m[1];
    }
    if (preg_match('/"firstRegistrationYear":(\d{4})/', $html, $m)) {
        $data["ano"] = $m[1];
    }
    if (preg_match('/"fuelType":"([^"]+)"/', $html, $m)) {
        $data["combustible"] = ucfirst(strtolower($m[1]));
    }
    if (preg_match('/"horsePower":(\d+)/', $html, $m)) {
        $data["potencia"] = $m[1];
    }
    if (preg_match('/"price":\{"amount":(\d+)/', $html, $m)) {
        $data["precio"] = $m[1];
    }
    return $data;
}

$url = $_GET["url"] ?? "";
if (!$url) {
    echo json_encode(["error" => "URL vacía"]);
    exit;
}

$html = get_remote_html($url);
if (!$html) {
    echo json_encode(["error" => "No se pudo acceder al contenido"]);
    exit;
}

$data = [];

if (strpos($url, "mobile.de") !== false) {
    $data = parse_mobile_de($html);
} elseif (strpos($url, "autoscout24") !== false) {
    $data = parse_autoscout24($html);
}

echo json_encode($data);
?>
