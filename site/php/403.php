<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

http_response_code(403);

$file = __DIR__ . '/../html/403.html';
readfile($file);

?>