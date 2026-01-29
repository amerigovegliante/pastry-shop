<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

http_response_code(404);

$file = __DIR__ . '/../html/404.html';
if (!file_exists($file)) { die("Errore critico: Template 404 mancante.");}
$paginaHTML = file_get_contents($file);
$baseUrl = defined('BASE_URL') ? BASE_URL : '/';
$paginaHTML = str_replace('[BASE_URL]', $baseUrl, $paginaHTML);
echo $paginaHTML;

?>