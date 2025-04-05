<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

function get_html($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

function parse_mobile_de($html) {
    $data = [];

    if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
        $data["imagen"] = $m[1];
    }

    if (preg_match('/<title>(.*?) in .*? kaufen/i', $html, $m)) {
        $partes = explode(" ", trim($m[1]), 2);
        $data["marca"] = $partes[0] ?? "";
        $data["modelo"] = $partes[1] ?? "";
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
    if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m)) {
        $json = json_decode($m[1], true);
        if ($json) {
            $data["marca"] = $json["brand"] ?? "";
            $data["modelo"] = $json["model"] ?? "";
            $data["ano"] = $json["productionDate"] ?? "";
            $data["precio"] = $json["offers"]["price"] ?? "";
            $data["imagen"] = $json["image"] ?? "";
            if (!empty($json["vehicleEngine"]["power"]["value"])) {
                $data["potencia"] = $json["vehicleEngine"]["power"]["value"];
            }
            if (!empty($json["fuelType"])) {
                $data["combustible"] = $json["fuelType"];
            }
        }
    }
    return $data;
}

$url = $_GET["url"] ?? "";
if (!$url) {
    echo json_encode(["error" => "Falta parámetro ?url"]);
    exit;
}

$html = get_html($url);
if (!$html) {
    echo json_encode(["error" => "No se pudo acceder al contenido"]);
    exit;
}

$data = [];

if (strpos($url, "mobile.de") !== false) {
    $data = parse_mobile_de($html);
} elseif (strpos($url, "autoscout24") !== false) {
    $data = parse_autoscout24($html);
} else {
    $data["error"] = "Fuente no compatible";
}

echo json_encode($data);
?>
