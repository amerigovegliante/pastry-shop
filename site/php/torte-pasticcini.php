<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/torte-pasticcini.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}
$db = new DBAccess();
$connessione = $db->openDBConnection(); 

//VARIABILI
$listaItem = "";
$tipo = "";
$tipoTitolo = "";
$titolo = "";
$Breadcrum= "";
$NessunaDisponibilità = "";
$suffisso = "";

if ($page === 'torte'){
    $NessunaDisponibilità="nessuna torta disponibile";
	$tipo="torta";
    $tipoTitolo="torte";
    $suffisso="<small> / porzione</small>";
    $titolo="Torte";
	$Breadcrum="<a href=\"torte\">Torte</a>";
}else if ($page === 'pasticcini'){
	$tipo="pasticcino";
    $tipoTitolo="pasticcini";
	$NessunaDisponibilità="nessun pasticcino disponibile";
    $titolo="Pasticcini";
    $Breadcrum="<a href=\"pasticcini\">Pasticcini</a>";
}
// leggo i dati delle torte
if($connessione && empty($listaItem)){
    $Items = $db->getListOfActiveItems($tipo);
    $db->closeDBConnection();
    
    if (!empty($Items)){
        $listaItem .= '<ul id="grigliaTorte" class="contenuto">';
        foreach($Items as $Item){
            // Immagini e Alt Text (con fallback)
            $imgSrc = !empty($Item['immagine']) ? "site/img/" . $Item['immagine'] : "site/img/placeholder.jpeg";
            $altText = !empty($Item['testo_alternativo']) ? $Item['testo_alternativo'] : "Foto del dolce " . $Item['nome'];
            
            // Creazione Card con Escape Output Sicuro
            $listaItem .="<li> <article class=\"cardTorta\">     
                <a href=\"dettagli?ID=".urlencode($Item['id'])."\"> 
                   <img src=\"" . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . "\" 
                        alt=\"" . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . "\">
                    <div class=\"infoTorta\"> 
                        <h3>".htmlspecialchars($Item['nome'], ENT_QUOTES, 'UTF-8')."</h3> 
                        <span class=\"prezzoItem\">€".number_format($Item['prezzo'], 2, ',', '.'). $suffisso."</span> 
                    </div>
                </a>
             </article> </li>";
        }
        $listaItem .= '</ul>';
    } else {
        $listaItem ="<p class='contenuto'>Al momento non abbiamo $NessunaDisponibilità, però se volete chiamarci siamo più che felici di ascoltare la vostra richiesta</p>";
    }
} else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

// SOSTITUZIONE SEGNAPOSTI
$paginaHTML = str_replace("[grigliaItems]", $listaItem, $paginaHTML);
$paginaHTML = str_replace("[breadcrum]", $Breadcrum, $paginaHTML);
$paginaHTML = str_replace("[titolo]", $titolo, $paginaHTML);
$paginaHTML = str_replace("[tipo]", $tipoTitolo, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>