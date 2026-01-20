<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

// AVVIO SESSIONE E CONTROLLO ACCESSO (IMPORTANTE!)
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

// Se non è loggato o non è admin, via da qui.
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

require_once "dbConnection.php";

$paginaHTML = file_get_contents('../html/ordiniAdmin.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere ordiniAdmin.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection();

$tabella="";  


if($connessione){
    $Ordini = $db->getOrdini();
    $db->closeDBConnection();
    
    if ($Ordini != null){ 
        $tabella .= "<p id=\"descr\" class=\"visually-hidden\">Tabella che elenca tutte le ordinazioni ancora da ritirare ordinate in base a data e ora di ritiro. 
                    Ogni riga descrive un'ordinazione con numero identificativo dell'ordine, data di ritiro, nominativo e telefono del cliente,
                     costo totale, eventuali annotazioni e stato dell'ordine. Lo stato dell'ordine può essere: in attesa, in preparazione, completato o ritirato</p>
        <table class=\"contenuto\" aria-describedby=\"descr\">    
            <caption>Elenco degli ordini ancora da ritirare</caption>
            <thead>
                <tr>
                    <th scope=\"col\" abbr=\"ID\">ID Ordine</th>
                    <th scope=\"col\" abbr=\"Ritiro\">Data di ritiro</th>                
                    <th scope=\"col\" abbr=\"Nome\">Nominativo</th>
                    <th scope=\"col\" abbr=\"Tel\">Telefono</th>
                    <th scope=\"col\" abbr=\"Tot\">Totale</th>
                    <th scope=\"col\" abbr=\"Note\">Annotazioni</th>
                    <th scope=\"col\">Stato</th>
                </tr>
            </thead>
            <tbody>";
        foreach($Ordini as $Ordine){
            $idSelect = "select_" . $Ordine['id']; 
            $idRow = "row_" . $Ordine['id'];
            
            $tabella .="<tr id=\"$idRow\">
                <th scope=\"row\">".htmlspecialchars($Ordine['id'])."</th>
                <td data-title=\"Ritiro\">".date("d/m H:i", strtotime($Ordine['ritiro']))."</td>
                
                <td data-title=\"Nominativo\">".htmlspecialchars($Ordine['nome'] ?? '')." ".htmlspecialchars($Ordine['cognome'] ?? '')."</td>
                
                <td data-title=\"Telefono\"><a href=\"tel:+".htmlspecialchars($Ordine['telefono'] ?? '')."\">".htmlspecialchars($Ordine['telefono'] ?? '')."</a></td>
                
                <td data-title=\"Totale\">€".number_format($Ordine['totale'], 2)."</td>
                
                <td data-title=\"Annotazioni\">".htmlspecialchars($Ordine['annotazioni'] ?? '')."</td>
                
                <td data-title=\"Stato\">
                    <div class=\"stato-ordine\">
                        <button type=\"button\" class=\"btn-stato prev\" data-target=\"$idSelect\" aria-label=\"Stato precedente\">◀</button>

                        <label class=\"visually-hidden\" for=\"$idSelect\">Stato ordine</label>
                        
                        <select id=\"$idSelect\" name=\"stato\" data-id=\"".$Ordine['id']."\" class=\"select-stato\">
                            <option value=\"1\" " . ($Ordine['stato'] == 1 ? 'selected' : '') . ">In attesa</option>
                            <option value=\"2\" " . ($Ordine['stato'] == 2 ? 'selected' : '') . ">In preparazione</option>
                            <option value=\"3\" " . ($Ordine['stato'] == 3 ? 'selected' : '') . ">Completato</option>
                            <option value=\"4\" " . ($Ordine['stato'] == 4 ? 'selected' : '') . ">Ritirato</option>
                        </select>

                        <button type=\"button\" class=\"btn-stato next\" data-target=\"$idSelect\" aria-label=\"Stato successivo\">▶</button>
                    </div>
                </td>
            </tr>";
        }
        $tabella .= "</tbody></table>";
    } else {
        $tabella ="<p class='contenuto'> Nessun ordine in attesa </p>"; 
    }
} else {
    $tabella = "<p class='errore'>I sistemi sono momentaneamente fuori servizio, ci scusiamo per il disagio</p>";
}

$paginaHTML = str_replace("[TabellaOrdini]", $tabella, $paginaHTML);
echo $paginaHTML;
?>