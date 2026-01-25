<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

http_response_code(404);

/*$paginaHTML = file_get_contents('/site/html/pag404.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere pag404.html");
}*/

$file = __DIR__ . '/../html/404.html';
readfile($file);
/*echo file_get_contents('../html/404.html');*/

?>