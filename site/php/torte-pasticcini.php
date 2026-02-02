<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/torte-pasticcini.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}
$db = new DBAccess();
$connessione = $db->openDBConnection(); //tento la connessione

//VARIABILI
//script per item
$listaItem= "";
$tipo="";
$tipoTitolo="";
$titolo="";
$LinkPagina="";
$NessunaDisponibilità="";
$suffisso="";
/*
if (isset($_GET['tipo']) && $_GET['tipo']==='torte'){
	$NessunaDisponibilità="nessuna torta disponibile";
	$tipo="torta";
    $titolo="Le nostre torte";
	$LinkPagina="<li id='currentLink'>Le nostre torte</li><li><a href=\"pasticcini\">I nostri pasticcini</a></li>";
}else if (isset($_GET['tipo']) && $_GET['tipo']==='pasticcini'){
	$tipo="pasticcino";
	$NessunaDisponibilità="nessun pasticcino disponibile";
    $titolo="I nostri pasticcini";
    $LinkPagina="<li><a href=\"torte\">Le nostre torte</a></li><li id='currentLink'>I nostri pasticcini</li>";
}else if (isset($_GET['tipo']) && $_GET['tipo']!=='pasticcini' && $_GET['tipo']!=='torte'){ //oppure si potrebbe andare su pag404
    $titolo="Le nostre scelte";
    $listaItem="Scegli tra la nostra selezione di <a href=\"../php/torte-pasticcini.php?tipo=torte\">torte</a> o <a href=\"../php/torte-pasticcini.php?tipo=pasticcini\">pasticcini</a>";
    $LinkPagina="<li><a href=\"torte\">Le nostre torte</a></li><li><a href=\"pasticcini\">I nostri pasticcini</a></li>";
}*/
if ($page === 'torte'){
    $NessunaDisponibilità="nessuna torta disponibile";
	$tipo="torta";
    $tipoTitolo="torte";
    $suffisso="<small>\ porzione</small>";
    $titolo="Torte";
	$LinkPagina="<li id='currentLink'>Torte</li><li><a href=\"pasticcini\">Pasticcini</a></li>";
}else if ($page === 'pasticcini'){
	$tipo="pasticcino";
    $tipoTitolo="pasticcini";
	$NessunaDisponibilità="nessun pasticcino disponibile";
    $titolo="Pasticcini";
    $LinkPagina="<li><a href=\"torte\">Torte</a></li><li id='currentLink'>Pasticcini</li>";
}
// leggo i dati delle torte
if($connessione && empty($listaItem)){
    $Items = $db->getListOfActiveItems($tipo);
    $db->closeDBConnection();
    
    if (!empty($Items)){
        $listaItem .= '<ul id="grigliaTorte" class="contenuto">';
        foreach($Items as $Item){
            $imgSrc = !empty($Item['immagine']) ? "site/img/" . $Item['immagine'] : "site/img/placeholder.jpeg";
            $altText = !empty($Item['testo_alternativo']) ? $Item['testo_alternativo'] : "Immagine non disponibile";
            $listaItem .="<li> <article class=\"cardTorta\">     
                <a href=\"dettagli?ID=".urlencode($Item['id'])."\"> 
                   <img src=\"" . htmlspecialchars($imgSrc)
                   . "\" alt=\"" . htmlspecialchars($altText) . "\">
                    <div class=\"infoTorta\"> 
                        <h3>".htmlspecialchars($Item['nome'])."</h3> 
                        <span class=\"prezzoItem\">€".number_format($Item['prezzo'], 2, ',', '.'). $suffisso."</span> 
                    </div>
                </a>
             </article> </li>";
        }
        $listaItem .= '</ul>';
    }else{
        $listaItem ="<p class='contenuto'>Al momento non abbiamo $NessunaDisponibilità, però se volete chiamarci siamo più che felici di ascoltare la vostra richiesta</p>";
    }
} else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

//RIMPIAZZO I SEGNAPOSTO [...]
$paginaHTML = str_replace("[grigliaItems]", $listaItem, $paginaHTML);
$paginaHTML = str_replace("[LinkPagina]", $LinkPagina, $paginaHTML);
$paginaHTML = str_replace("[titolo]", $titolo, $paginaHTML);
$paginaHTML = str_replace("[tipo]", $tipoTitolo, $paginaHTML);
echo $paginaHTML;
?>