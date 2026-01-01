<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaTorteHTML = file_get_contents('../html/torte.html');
if ($paginaTorteHTML === false) {
    die("Errore: impossibile leggere torte.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection(); //tento la connessione

//VARIABILI
$torte = "";
$stringaTorteHTML = "";

// leggo i dati delle torte
if($connessione){
    $torte = $db->getListOfItems("Torta");
    $db->closeConnection();    //chiudo la connessione
    if(!empty($torte)){
        $stringaTorteHTML .= '<div id="grigliaTorte" class="contenuto">';
        foreach($torte as $torta){  //creazione card per ogni torta
            $stringaTorteHTML .= '<a href="paginaDettaglioTorta.html" class="cardTorta">'
            . '<img src="' . $torta['immagine'] . '" alt="">'
            . '<div class="infoTorta">'
            . '<h2>' . $torta['nome'] . '</h2>'
            . '<p>' . $torta['descrizione'] . '</p>'
            . '<span class="prezzoTorta">' . $torta['prezzo'] . '</span>'
            . '</div>' . '</a>';
        }
        $stringaTorteHTML .= '</div>';
    } else{
    $stringaTorteHTML .= '<p>La sezione torte è momentaneamente non disponibile.</p>';
    }    
} else {
    $stringaTorteHTML .= '<p>I sistemi sono momentaneamente fuori servizio, ci scusiamo per il disagio.</p>';
}
//RIMPIAZZO I SEGNAPOSTO [...]
$paginaTorteHTML = str_replace("[grigliaTorte]", $stringaTorteHTML, $paginaTorteHTML);

echo $paginaTorteHTML;
?>