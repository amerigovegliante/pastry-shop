<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if(session_status() === PHP_SESSION_NONE){
    session_start();                    
}

$paginaHTML = file_get_contents( __DIR__ .'/../html/home.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}
//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>