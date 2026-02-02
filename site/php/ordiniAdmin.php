<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

// AVVIO SESSIONE E CONTROLLO ACCESSO (IMPORTANTE!)
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once "dbConnection.php";

$statiModificati = []; // array di ordine con stato modificato

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stato']) && is_array($_POST['stato'])) {
    foreach($_POST['stato'] as $idOrdine => $nuovoStato){
        $idOrdine = intval($idOrdine);
        $nuovoStato = intval($nuovoStato);

        // inseriamo/modifichiamo l’array: ultima selezione sovrascrive le precedenti
        $statiModificati[$idOrdine] = $nuovoStato;
    }
}

if (!empty($statiModificati)) {
    $db = new DBAccess();
    $connessione = $db->openDBConnection();

    if($connessione){
        $Ordini = $db->AggiornaStati($statiModificati);
        $db->closeDBConnection();

        // opzionale: svuota l'array per evitare doppie esecuzioni in refresh
        $statiModificati = [];
        
        // Redirect per evitare ri-invio POST al refresh
        header("Location: ordini-amministratore");
        exit;
    }else{
        http_response_code(500);
        include __DIR__ . '/500.php';
        exit;
    }
    
}

// Se non è loggato o non è admin, via da qui.
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login");
    exit;
}

//require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/ordiniAdmin.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$db = new DBAccess();
$connessione = $db->openDBConnection();

$tabella="";  


if($connessione){
    $Ordini = $db->getOrdini();
    $db->closeDBConnection();
    
    if ($Ordini != null){ 
        $tabella .= "<p id=\"descr\" class=\"sr-only\">Tabella organizzata per colonne che mostra tutti gli ordini la cui data di ritiro risale al massimo a sette giorni fa.
                    Ogni riga descrive un'ordinazione con numero identificativo dell'ordine, data di ritiro, nominativo e telefono del cliente,
                     costo totale, eventuali annotazioni e stato di avanzamento dell'ordine. Lo stato dell'ordine può essere: in attesa, in preparazione, completato o ritirato</p>
        <form method=\"post\" action=\"ordini-amministratore\">
        
        <table class=\"contenuto\" aria-describedby=\"descr\">    
            <caption>
                <div class=\"caption-bar\">
                <span class=\"caption-text\">Ordini dal ".date("d/m/Y", strtotime("-7 days"))."</span>
                <button type=\"submit\" class=\"generic-button aggiorna-tabella\">Aggiorna tabella</button>
                </div>
            </caption>
            
            <thead>
                <tr>
                    <th scope=\"col\">Numero</th>
                    <th scope=\"col\" abbr=\"Ritiro\">Data di ritiro</th>                
                    <th scope=\"col\">Nominativo</th>
                    <th scope=\"col\">Telefono</th>
                    <th scope=\"col\">Totale</th>
                    <th scope=\"col\">Annotazioni</th>
                    <th scope=\"col\">Stato</th>
                    <th scope=\"col\">Dettagli</th>
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
                        <label class=\"visually-hidden\" for=\"$idSelect\">Stato ordine</label>
                        
                        <select id=\"$idSelect\" name=\"stato[".$Ordine['id']."]\" data-id=\"".$Ordine['id']."\" class=\"select-stato\">
                            <option value=\"1\" " . ($Ordine['stato'] == 1 ? 'selected' : '') . ">In attesa</option>
                            <option value=\"2\" " . ($Ordine['stato'] == 2 ? 'selected' : '') . ">In preparazione</option>
                            <option value=\"3\" " . ($Ordine['stato'] == 3 ? 'selected' : '') . ">Completato</option>
                            <option value=\"4\" " . ($Ordine['stato'] == 4 ? 'selected' : '') . ">Ritirato</option>
                        </select>
                    </div>
                </td>
                <td><a href=\"dettaglio-ordine?id=".urlencode($Ordine['id'])."\" class=\"pulsanteGenerico\">Dettagli</a></td>
            </tr>";
        }
        $tabella .= "</tbody></table></form>";
    } else {
        $tabella ="<p class='contenuto'> Nessun ordine in attesa </p>"; 
    }
} else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$paginaHTML = str_replace("[TabellaOrdini]", $tabella, $paginaHTML);
echo $paginaHTML;
?>