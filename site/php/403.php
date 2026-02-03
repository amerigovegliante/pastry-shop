<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

http_response_code(403);

if (!isset($messaggioErrore)) {
    $messaggioErrore = "Probabilmente hai sbagliato percorso, ma è tutto sotto controllo.";
}

$file = __DIR__ . '/../html/403.html';
if (!file_exists($file)) die("Errore critico: Template 403 mancante.");

$paginaHTML = file_get_contents($file);

$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$uriCorrente = $_SERVER['REQUEST_URI'];

$paginaHTML = str_replace('[BASE_URL]', $baseUrl, $paginaHTML);
$paginaHTML = str_replace('[URI_CORRENTE]', htmlspecialchars($uriCorrente), $paginaHTML);

$paginaHTML = str_replace('[messaggioErrore]', htmlspecialchars($messaggioErrore), $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>