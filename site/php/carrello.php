<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/carrello.html');
if ($paginaHTML === false){
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
} 

$db = new DBAccess();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ID']) && !isset($_POST['action'])) {
    $connessione = $db->openDBConnection();
    if ($connessione) {
        $idProdotto = $_POST['ID'];
        $itemDB = $db->getItemDetail($idProdotto);
        $db->closeDBConnection();

        if ($itemDB) {
            $tipoItem = strtolower($itemDB['tipo']);
            $quantitaInput = isset($_POST['quantita']) ? (int)$_POST['quantita'] : 1;
            if ($quantitaInput < 1) $quantitaInput = 1;
            $porzioneScelta = isset($_POST['porzione']) ? (int)$_POST['porzione'] : 0; 
            $testoTarga = isset($_POST['testoTarga']) ? trim(strip_tags($_POST['testoTarga'])) : "";
            
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

            if (!isset($_SESSION['carrello'])) $_SESSION['carrello'] = array();

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
            
            header("Location: carrello");
            exit;
        }else{
            http_response_code(404);
            include __DIR__ . '/404.php';
            $db->closeDBConnection();
            exit;
        }
    }else{
        //header("Location: carrello");
        http_response_code(500);
        include __DIR__ . '/500.php';
        exit;
    }
}

// AGGIORNAMENTO QUANTITÀ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['index'])) {
    
    $index = (int)$_POST['index'];
    
    if (isset($_SESSION['carrello']) && isset($_SESSION['carrello'][$index])) {
        
        if ($_POST['action'] == 'rimuovi') {
            unset($_SESSION['carrello'][$index]);
            $_SESSION['carrello'] = array_values($_SESSION['carrello']); 
        }
        
        elseif ($_POST['action'] == 'piu') {
            $_SESSION['carrello'][$index]['quantita']++;
        }
        
        elseif ($_POST['action'] == 'meno') {
            if ($_SESSION['carrello'][$index]['quantita'] > 1) {
                $_SESSION['carrello'][$index]['quantita']--;
            }
        }
    }
    // Ricarica la pagina per vedere le modifiche
    header("Location: carrello");
    exit;
}

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
            if ($item['porzione'] > 0) $infoParts[] = "Formato: " . $item['porzione'] . " persone";
            if (!empty($item['targa'])) $infoParts[] = "Targa: <em>" . htmlspecialchars($item['targa'], ENT_QUOTES, 'UTF-8') . "</em>";
        }
        $numDettagli = count($infoParts);
        if ($numDettagli === 0) $dettagliExtra = "<span aria-hidden='true'>-</span><span class='sr-only'>Nessun dettaglio extra</span>";
        elseif ($numDettagli === 1) $dettagliExtra = "<span class='dettaglio-singolo'>" . $infoParts[0] . "</span>";
        else {
            $dettagliExtra = "<ul class='lista-extra-carrello'>";
            foreach ($infoParts as $info) $dettagliExtra .= "<li>" . $info . "</li>";
            $dettagliExtra .= "</ul>";
        }

        $unitarioFormat = number_format($prezzoUnitario, 2, ',', '.');
        $totaleRigaFormat = number_format($prezzoTotaleRiga, 2, ',', '.');
        $nomeProdotto = htmlspecialchars($item['nome'], ENT_QUOTES, 'UTF-8');

        // attributo disabled per il bottone meno se qtà è 1
        $disabledMeno = ($item['quantita'] <= 1) ? 'disabled' : '';

        // Form per il tasto MENO
        $formMeno = "
        <form action='carrello' method='POST' class='form-qty'>
            <input type='hidden' name='action' value='meno'>
            <input type='hidden' name='index' value='$index'>
            <button type='submit' class='btn-qty' $disabledMeno aria-label='Diminuisci quantità di $nomeProdotto' title='Diminuisci'>-</button>
        </form>";

        // Form per il tasto PIU
        $formPiu = "
        <form action='carrello' method='POST' class='form-qty'>
            <input type='hidden' name='action' value='piu'>
            <input type='hidden' name='index' value='$index'>
            <button type='submit' class='btn-qty' aria-label='Aumenta quantità di $nomeProdotto' title='Aumenta'>+</button>
        </form>";

        // form per il tasto rimuovi
        $formRimuovi = "
        <form action='carrello' method='POST'>
            <input type='hidden' name='action' value='rimuovi'>
            <input type='hidden' name='index' value='$index'>
            <button type='submit' aria-label='Rimuovi $nomeProdotto dal carrello'>Rimuovi</button>
        </form>";

        $righeProdotti .= "<tr>
            <th scope='row' data-label='Prodotto'>$nomeProdotto</th>
            <td data-label='Info Extra'>$dettagliExtra</td>
            <td data-label='Quantità'>
                <div class='qty-container'>
                    $formMeno
                    <span class='qty-numero'>" . $item['quantita'] . "</span>
                    $formPiu
                </div>
            </td>
            <td data-label='Prezzo Unit.' class='cella-numerica'>€$unitarioFormat</td>
            <td data-label='Totale' class='cella-numerica'><strong>€$totaleRigaFormat</strong></td>
            <td data-label='Azioni'>
                $formRimuovi
            </td>
        </tr>";
    }

    $totaleGeneraleFormat = "€" . number_format($totaleCarrello, 2, ',', '.');

    // Controllo Login
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
    <p id=\"descrizioneCarrello\" class=\"visually-hidden\">Tabella organizzata per colonne che elenca i prodotti all'interno del carrello. 
                    Ogni riga descrive un prodotto con nome, informazioni aggiuntive sull'ordine, quantità, prezzo unitario e totale parziale. 
                    Alla fine della tabella è mostrato il totale complessivo dell'ordine. </p>
    <div id='contenitoreTabella'>
        <table aria-describedby='descrizioneCarrello'>
            <caption>Riepilogo prodotti nel carrello</caption>
            <thead>
                <tr>
                    <th scope='col'>Prodotto</th>
                    <th scope='col' abbr='Informazioni'>Informazioni Aggiuntive</th>
                    <th scope='col'>Quantità</th>
                    <th scope='col' abbr='Prezzo'>Prezzo Unitario</th>
                    <th scope='col'>Totale</th>
                    <th scope='col'><span class='sr-only'>Azioni</span></th>
                </tr>
            </thead>
            <tbody>
                $righeProdotti
            </tbody>
            <tfoot>
                <tr>
                    <th scope='row' colspan='4' class='totaleLabel'>Totale Complessivo:</th>
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
        <p class='messaggio-vuoto'>Il tuo carrello è vuoto.</p>
        <div class='bottoni-vuoto'>
            <a href='torte' class='pulsanteGenerico'>Le nostre Torte</a>
            <a href='pasticcini' class='pulsanteGenerico'>I nostri Pasticcini</a>
        </div>
    </div>";
}

$paginaHTML = str_replace("[ContenutoCarrello]", $contenutoGenerato, $paginaHTML);
echo $paginaHTML;
?>