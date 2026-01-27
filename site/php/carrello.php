<?php
// Avvio sessione solo se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";

// carico template HTML
$paginaHTML = file_get_contents( __DIR__ .'/../html/carrello.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere il template carrello.html");
}

$db = new DBAccess();

// gestione aggiunta articolo al carrello
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ID'])) {
    
    $connessione = $db->openDBConnection();
    if ($connessione) {
        $idProdotto = $_POST['ID'];
        $itemDB = $db->getItemDetail($idProdotto);
        $db->closeDBConnection();

        if ($itemDB) {
            $tipoItem = strtolower($itemDB['tipo']);
            
            // sanificazione input
            $quantitaInput = isset($_POST['quantita']) ? (int)$_POST['quantita'] : 1;
            if ($quantitaInput < 1) $quantitaInput = 1;

            $porzioneScelta = isset($_POST['porzione']) ? (int)$_POST['porzione'] : 0; 
            $testoTarga = isset($_POST['testoTarga']) ? trim($_POST['testoTarga']) : "";

            // calcolo del prezzo per unità
            $prezzoBaseItem = (float)$itemDB['prezzo'];
            $prezzoPerUnita = $prezzoBaseItem; 
            
            if ($tipoItem === 'torta' && $porzioneScelta > 0) {
                $prezzoPerUnita = $prezzoBaseItem * $porzioneScelta;
            }

            $nuovoElemento = array(
                'id' => $itemDB['id'],
                'nome' => $itemDB['nome'],
                'tipo' => $tipoItem,
                'prezzo_unitario_calcolato' => $prezzoPerUnita, 
                'quantita' => $quantitaInput,
                'porzione' => $porzioneScelta,
                'targa' => $testoTarga
            );

            if (!isset($_SESSION['carrello'])) {
                $_SESSION['carrello'] = array();
            }

            // uniamo i prodotti identici
            $trovato = false;
            foreach ($_SESSION['carrello'] as &$itemCarrello) {
                if ($itemCarrello['id'] == $nuovoElemento['id'] &&
                    $itemCarrello['porzione'] == $nuovoElemento['porzione'] &&
                    $itemCarrello['targa'] === $nuovoElemento['targa']) {
                    
                    $itemCarrello['quantita'] += $quantitaInput;
                    $trovato = true;
                    break;
                }
            }
            unset($itemCarrello); 

            if (!$trovato) {
                $_SESSION['carrello'][] = $nuovoElemento;
            }
        }
    }
    // pattern PRG per evitare reinvio form al refresh
    header("Location: carrello");
    exit;
}

// rimoizone articolo dal carrello
if (isset($_GET['action']) && $_GET['action'] == 'rimuovi' && isset($_GET['index'])) {
    $index = (int)$_GET['index'];
    if (isset($_SESSION['carrello'][$index])) {
        unset($_SESSION['carrello'][$index]);
        $_SESSION['carrello'] = array_values($_SESSION['carrello']);
    }
    header("Location: carrello");
    exit;
}

// generazione contenuto carrello
$contenutoGenerato = "";

if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0) {
    
    $righeProdotti = "";
    $totaleCarrello = 0;

    foreach ($_SESSION['carrello'] as $index => $item) {
        
        $prezzoUnitario = isset($item['prezzo_unitario_calcolato']) ? $item['prezzo_unitario_calcolato'] : 0;
        $prezzoTotaleRiga = $prezzoUnitario * $item['quantita'];
        $totaleCarrello += $prezzoTotaleRiga;

        $dettagliExtra = "";
        $infoParts = [];
        if ($item['tipo'] === 'torta') {
            if ($item['porzione'] > 0) {
                $infoParts[] = "Formato: " . $item['porzione'] . " persone";
            }
            if (!empty($item['targa'])) {
                $infoParts[] = "Targa: <em>" . htmlspecialchars($item['targa']) . "</em>";
            }
        }
        $numDettagli = count($infoParts);

        if ($numDettagli === 0) {
            // CASO 0: Nessun dettaglio
            $dettagliExtra = "<span aria-hidden='true'>-</span><span class='sr-only'>Nessun dettaglio extra</span>";
        
        } elseif ($numDettagli === 1) {
            // CASO 1: Un solo dettaglio
            $dettagliExtra = "<span class='dettaglio-singolo'>" . $infoParts[0] . "</span>";
        
        } else {
            // CASO 2: dettagli >1 (Lista non ordinata)
            $dettagliExtra = "<ul class='lista-extra-carrello'>";
            foreach ($infoParts as $info) {
                $dettagliExtra .= "<li>" . $info . "</li>";
            }
            $dettagliExtra .= "</ul>";
        }
        $unitarioFormat = number_format($prezzoUnitario, 2, ',', '.');
        $totaleRigaFormat = number_format($prezzoTotaleRiga, 2, ',', '.');
        $nomeProdotto = htmlspecialchars($item['nome']);

        $righeProdotti .= "<tr>
            <th scope='row' data-label='Prodotto'>$nomeProdotto</th>
            <td data-label='Dettagli'>$dettagliExtra</td>
            <td data-label='Quantità'>" . $item['quantita'] . "</td>
            <td data-label='Prezzo Unit.'>€$unitarioFormat</td>
            <td data-label='Totale'><strong>€$totaleRigaFormat</strong></td>
            <td data-label='Azioni'>
                <a href='carrello&action=rimuovi&index=$index' class='link-rimuovi' aria-label='Rimuovi $nomeProdotto dal carrello'>Rimuovi</a>
            </td>
        </tr>";
    }

    $totaleGeneraleFormat = "€" . number_format($totaleCarrello, 2, ',', '.');

    // logica di login
    $pulsanteProcedi = "";
    if (isset($_SESSION['ruolo'])) {
        $pulsanteProcedi = "
        <div class='box-checkout'>
            <a href='conferma-ordine' class='pulsanteGenerico'>Procedi con l'ordine &rarr;</a>
        </div>";
    } else {
        $pulsanteProcedi = "
        <div class='login-alert'>
            <p>Per concludere l'ordine è necessario accedere.</p>
            <a href='login' class='pulsanteGenerico'>Accedi o Registrati</a>
        </div>";
    }

    $contenutoGenerato = "
    <div id='contenitoreTabella'>
        <table class='tabella-carrello'>
            <caption>Riepilogo prodotti nel tuo carrello</caption>
            <thead>
                <tr>
                    <th scope='col'>Prodotto</th>
                    <th scope='col'>Info Extra</th>
                    <th scope='col'>Qtà</th>
                    <th scope='col'>Prezzo Unit.</th>
                    <th scope='col'>Totale</th>
                    <th scope='col'><span class='sr-only'>Azioni</span></th>
                </tr>
            </thead>
            <tbody>
                $righeProdotti
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='4' class='totaleLabel'>Totale Complessivo:</td>
                    <td class='totaleValore'>$totaleGeneraleFormat</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class='azioniCarrello'>
            <a href='torte' class='link-indietro'>&larr; Continua acquisti</a>
            $pulsanteProcedi
        </div>
    </div>";

} else {
    // Caso Carrello Vuoto
    $contenutoGenerato = "
    <div class='carrello-vuoto'>
        <p>Il tuo carrello è vuoto.</p>
        <div class='bottoni-vuoto'>
            <a href='torte' class='pulsanteGenerico'>Le nostre Torte</a>
            <a href='pasticcini' class='pulsanteGenerico'>I nostri Pasticcini</a>
        </div>
    </div>";
}

$paginaHTML = str_replace("[ContenutoCarrello]", $contenutoGenerato, $paginaHTML);
echo $paginaHTML;
?>