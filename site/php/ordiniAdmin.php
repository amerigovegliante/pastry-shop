<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

// AVVIO SESSIONE E CONTROLLO ACCESSO (IMPORTANTE!)
if (session_status() === PHP_SESSION_NONE){
    session_start();
}

require_once "dbConnection.php";

// 2. GESTIONE CSRF TOKEN
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

// 3. ELABORAZIONE FORM (Modifica Stati)
$statiModificati = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stato']) && is_array($_POST['stato'])) {
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ordini-amministratore");
        exit;
    }

    foreach($_POST['stato'] as $idOrdine => $nuovoStato){
        $idOrdine = intval($idOrdine);
        $nuovoStato = intval($nuovoStato);

        $statiModificati[$idOrdine] = $nuovoStato;
    }


if (!empty($statiModificati)) {
    $db = new DBAccess();
    $connessione = $db->openDBConnection();

        if($connessione){
            $db->AggiornaStati($statiModificati);
            $db->closeDBConnection();
            
            header("Location: ordini-amministratore");
            exit;
        } else {
            http_response_code(500);
            include __DIR__ . '/500.php';
            exit;
        }
    }
}

// 4. CARICAMENTO TEMPLATE E DATI
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
        $tabella .= "
        <section class=\"contenuto orders-content\">
        <p id=\"descr\" class=\"sr-only\">Tabella organizzata per colonne che mostra tutti gli ordini recenti. Ogni riga contiene numero, data, cliente, totale, note e un menu per modificare lo stato.</p>
        
        <form method=\"post\" action=\"ordini-amministratore\">
        <input type=\"hidden\" name=\"csrf_token\" value=\"$token\">
        
        <table aria-describedby=\"descr\">    
            <caption>
                <div class=\"caption-bar\">
                    <span class=\"caption-text\">Ordini dal ".date("d/m/Y", strtotime("-7 days"))."</span>
                    <button type=\"submit\" class=\"generic-button aggiorna-tabella\">Aggiorna stati</button>
                </div>
            </caption>
            
            <thead>
                <tr>
                    <th scope=\"col\">Numero</th>
                    <th scope=\"col\">Data di ritiro</th>                
                    <th scope=\"col\">Nominativo</th>
                    <th scope=\"col\">Telefono</th>
                    <th scope=\"col\">Totale</th>
                    <th scope=\"col\">Annotazioni</th>
                    <th scope=\"col\">Stato</th>
                    <th scope=\"col\"><span class=\"sr-only\">Azioni</span></th>
                </tr>
            </thead>
            <tbody>";
        
        foreach($Ordini as $Ordine){
            // Sanitizzazione output
            $idSicuro = htmlspecialchars($Ordine['id'], ENT_QUOTES, 'UTF-8');
            $nomeSicuro = htmlspecialchars($Ordine['nome'] ?? '', ENT_QUOTES, 'UTF-8');
            $cognomeSicuro = htmlspecialchars($Ordine['cognome'] ?? '', ENT_QUOTES, 'UTF-8');
            $telefonoSicuro = htmlspecialchars($Ordine['telefono'] ?? '', ENT_QUOTES, 'UTF-8');
            $noteSicure = htmlspecialchars($Ordine['annotazioni'] ?? '', ENT_QUOTES, 'UTF-8');
            
            $idSelect = "select_" . $idSicuro; 
            $idRow = "row_" . $idSicuro;
            
            $tabella .="<tr id=\"$idRow\">
                <th scope=\"row\">$idSicuro</th>
                <td data-label=\"Ritiro\">".date("d/m H:i", strtotime($Ordine['ritiro']))."</td>
                
                <td data-label=\"Nominativo\">$nomeSicuro $cognomeSicuro</td>
                
                <td data-label=\"Telefono\"><a href=\"tel:+$telefonoSicuro\">$telefonoSicuro</a></td>
                
                <td data-label=\"Totale\">€".number_format($Ordine['totale'], 2, ',', '.')."</td>
                
                <td data-label=\"Annotazioni\">$noteSicure</td>
                
                <td data-label=\"Stato\">
                    <div class=\"stato-ordine\">
                        <label class=\"sr-only\" for=\"$idSelect\">Stato ordine $idSicuro</label>
                        
                        <select id=\"$idSelect\" name=\"stato[$idSicuro]\" class=\"select-stato\">
                            <option value=\"1\" " . ($Ordine['stato'] == 1 ? 'selected' : '') . ">In attesa</option>
                            <option value=\"2\" " . ($Ordine['stato'] == 2 ? 'selected' : '') . ">In preparazione</option>
                            <option value=\"3\" " . ($Ordine['stato'] == 3 ? 'selected' : '') . ">Completato</option>
                            <option value=\"4\" " . ($Ordine['stato'] == 4 ? 'selected' : '') . ">Ritirato</option>
                        </select>
                        <span class=\"sr-only\">Attuale: " . ($Ordine['stato'] == 1 ? 'In attesa' : ($Ordine['stato'] == 2 ? 'In preparazione' : ($Ordine['stato'] == 3 ? 'Completato' : 'Ritirato'))) . "</span>
                    </div>
                </td>
                <td data-label=\"Azioni\">
                    <a href=\"dettaglio-ordine?id=$idSicuro\" class=\"generic-button\" aria-label=\"Dettagli ordine $idSicuro\">Dettagli</a>
                </td>
            </tr>";
        }
        $tabella .= "</tbody></table></form></section>";
    } else {
        $tabella ="<p class='contenuto'> Nessun ordine in attesa </p>"; 
    }
} else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$paginaHTML = str_replace("[TabellaOrdini]", $tabella, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>