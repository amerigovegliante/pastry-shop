<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/torte-pasticcini.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere torte-pasticcini.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection(); //tento la connessione

//VARIABILI
//script per item
$listaItem= "";
$tipo="";
$titolo="";
$ID="";
$LinkPagina="";
$NessunaDisponibilità="";


if (isset($_GET['tipo']) && $_GET['tipo']==='torte'){
	$NessunaDisponibilità="nessuna torta disponibile";
	$tipo="Torta";
    $titolo="Le nostre torte";
	$LinkPagina="<li id='currentLink'>Le nostre torte</li><li><a href=\"../php/torte-pasticcini.php?tipo=pasticcini\">I nostri pasticcini</a></li>";
}else if (isset($_GET['tipo']) && $_GET['tipo']==='pasticcini'){
	$tipo="Pasticcino";
	$NessunaDisponibilità="nessun pasticcino disponibile";
    $titolo="I nostri pasticcini";
	$LinkPagina="<li><a href=\"../php/torte-pasticcini.php?tipo=torte\">Le nostre torte</a></li><li id='currentLink'>I nostri pasticcini</li>";
}else if (isset($_GET['tipo']) && $_GET['tipo']!=='torte' && $_GET['tipo']!=='pasticcini'){
    $LinkPagina="<li><a href=\"../php/torte-pasticcini.php?tipo=pasticcini\">I nostri pasticcini</a></li><li><a href=\"../php/torte-pasticcini.php?tipo=torte\">Le nostre torte</a></li>";
	$listaItem ="<p>Non hai specificato se vuoi vedere le torte o i pasticcini.<p>"
}

// leggo i dati delle torte
if($connessione){
    $Items=array();
	$Items = $db->getListOfItems($tipo);
	$db->closeConnection();
	if (!empty($Items)){
		$listaItem .= '<ul id="grigliaTorte" class="contenuto">';
		foreach($Items as $Item){
			$listaItem .="<li> <article class=\"cardTorta\"> 
                <a href=\"dettagli.php?ID=".urlencode($Item['id'])."\" aria-label=\"Vedi i dettagli: \"".htmlspecialchars($Item['nome'])."> 
                <img src=\"" . $Item['icona'] . "\" alt=\"\">
                <div class=\"infoTorta\"> 
                    <h2>".htmlspecialchars($Item['nome'])."</h2> <!-- htmlspecialchars per renderlo più sicuro: rende i caratteri pericolosi come ><  & in formato HTML &lt; &gt; ect.-->
                    <span class=\"prezzoTorta\">€".number_format($Item['prezzo'], 2, ',', '.')."</span> <!--number_format(numero, decimali, separatore_decimali, separatore_migliaia)-->
                    <!-- bottone VEDI DETTAGLI: finto in realtà tutta la card è il link VA BENE???, con aria-hidden lo nascondo agli screen readers essendo che ha funziona solamente decorativa, aria-label su tag a-->
                    <span class=\"pulsanteGenerico\" aria-hidden=\"true\">Vedi Dettagli</span>
                    <button class=\"pulsanteGenerico\" aria-label=\"Aggiungi ".htmlspecialchars($Item['nome'])." al carrello\">Aggiungi al Carrello</button>
                </div>
                </a>
             </article> </li>";
		}
        $listaItem .= '</ul>';
	}else{
		$listaItem ="<p>Al momento non abbiamo $NessunaDisponibilità, però se volete chiamarci siamo più che felici di ascoltare la vostra richiesta</p>"; //DA CAMBIARE
        //OPPURE $listaItem ="<p>La sezione $tipo è momentaneamente non disponibile.</p>";
    }
} else {
	$listaItem = "<p>I sistemi sono momentaneamente fuori servizio, ci scusiamo per il disagio.</p>";
}
//RIMPIAZZO I SEGNAPOSTO [...]
$paginaHTML = str_replace("[grigliaItems]", $listaItem, $paginaHTML);
$paginaHTML = str_replace("[LinkPagina]", $LinkPagina, $paginaHTML);
$paginaHTML = str_replace("[titolo]", $titolo, $paginaHTML);
echo $paginaHTML;
?>