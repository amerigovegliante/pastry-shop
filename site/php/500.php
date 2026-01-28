<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

http_response_code(500);

$file = __DIR__ . '/../html/500.html';
if (!file_exists($file)) die("Errore critico: Template 500 mancante.");
$paginaHTML = file_get_contents($file);
$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$paginaHTML = str_replace('[BASE_URL]', $baseUrl, $paginaHTML);
echo $paginaHTML;

?>