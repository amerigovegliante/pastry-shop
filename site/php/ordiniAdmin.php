<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/ordiniAdmin.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere ordiniAdmin.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection(); //tento la connessione

//VARIABILI
$tabella="";  


if($connessione){
    $Ordini=array();
	$Ordini = $db->getOrdini();
	$db->closeDBConnection();
	if ($Ordini!=null){ 
        $tabella .= "<p id=\"descr\" class=\"visually-hidden\">Tabella che elenca tutte le ordinazioni ancora da ritirare ordinate in base a data e ora di ritiro. 
                    Ogni riga descrive un'ordinazione con numero identificativo dell'ordine, data e ora di ritiro, nominativo e telefono del cliente,
                     costo totale, eventuali annotazioni e stato dell'ordine. Lo stato dell'ordine può essere: in attesa, in preparazione, completato o ritirato</p>
        <table class=\"contenuto\" aria-describedby=\"descr\">    
            <caption>Elenco degli ordini ancora da ritirare</caption>
            <thead>
                <tr>
                    <th scope=\"col\" abbr=\"ID\">ID Ordine</th>
                    <th scope=\"col\" abbr=\"Ritiro\">Data e ora ritiro</th>                
                    <th scope=\"col\" abbr=\"Nome\">Nominativo</th>
                    <th scope=\"col\" abbr=\"Tel\">Telefono</th>
                    <th scope=\"col\" abbr=\"Tot\">Totale</th>
                    <th scope=\"col\" abbr=\"Note\">Annotazioni</th>
                    <th scope=\"col\">Stato</th>
                </tr>
            </thead>
            <tbody>";
        foreach($Ordini as $Ordine){
            $idSelect = "stato-ordine-" . (int)$Ordine['id']; //creo un id unico
            $tabella .="<tr>
                <th scope=\"row\">".htmlspecialchars($Ordine['id'])."</th>
                <td data-title=\"Ritiro\"><time datetime=\"".htmlspecialchars($Ordine['ritiro'])."\">".htmlspecialchars($Ordine['ritiro'])."</time></td>
                <td data-title=\"Nominativo\">".htmlspecialchars($Ordine['nome'])." ".htmlspecialchars($Ordine['cognome'])."</td>
                <td data-title=\"Telefono\"><a href=\"tel:+".htmlspecialchars($Ordine['telefono'])."\">".htmlspecialchars($Ordine['telefono'])."</a></td>
                <td data-title=\"Totale\"><data value=\"".number_format($Ordine['totale'])."\">€".number_format($Ordine['totale'])."</data></td>
                <td data-title=\"Annotazioni\">".htmlspecialchars($Ordine['annotazioni'])."</td>
                <td data-title=\"Stato\">
                <div class=\"stato-ordine\">
                    <button type=\"button\" class=\"prev\" aria-label=\"Stato precedente\">
                    ◀
                    </button>

                    <label class=\"visually-hidden\" for=\"$idSelect\">
                    Stato ordine
                    </label>

                    <select id=\"$idSelect\" name=\"stato\" aria-label=\"Stato ordine\">
                        <option value=\"1\" " . ($Ordine['stato'] == 1 ? 'selected' : '') . ">In attesa</option>
                        <option value=\"2\" " . ($Ordine['stato'] == 2 ? 'selected' : '') . ">In preparazione</option>
                        <option value=\"3\" " . ($Ordine['stato'] == 3 ? 'selected' : '') . ">Completato</option>
                        <option value=\"4\" " . ($Ordine['stato'] == 4 ? 'selected' : '') . ">Ritirato</option>
                    </select>

                    <button type=\"button\" class=\"next\" aria-label=\"Stato successivo\">
                    ▶
                    </button>
                </div>
                <span class=\"progresso-stato\">".number_format($Ordine['stato'])."/4</span>
                </td>
            </tr>";
        }
        $tabella .= "</tbody></table>";
	}else{
		$tabella ="<p> Nessun ordine in attesa </p>"; 
	}
} else {
	$tabella = "<p>I sistemi sono momentaneamente fuori servizio, ci scusiamo per il disagio</p>";
}

//RIMPIAZZO I SEGNAPOSTO [...]
$paginaHTML = str_replace("[TabellaOrdini]", $tabella, $paginaHTML);
echo $paginaHTML;
?>