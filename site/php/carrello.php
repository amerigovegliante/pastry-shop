<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

// carico il template HTML
$paginaHTML = file_get_contents('../html/carrello.html');
if ($paginaHTML === false) {
    die("Errore template carrello");
}

$db = new DBAccess();

// aggiunta prodotto al carrello
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ID'])) {
    
    $connessione = $db->openDBConnection();
    if ($connessione) {
        $idProdotto = $_POST['ID'];
        // recuperiamo i dati: per ora le torte il prezzo DB è inteso "a porzione"
        $itemDB = $db->getItemDetail($idProdotto);
        $db->closeDBConnection();

        if ($itemDB) {
            
            $quantita = isset($_POST['quantita']) ? (int)$_POST['quantita'] : 1;
            $porzioneScelta = isset($_POST['porzione']) ? (int)$_POST['porzione'] : null;
            
            // calcolo prezzo unitario dell'oggetto intero
            if ($itemDB['tipo'] === 'Torta' && $porzioneScelta > 0) {
                $prezzoOggetto = $itemDB['prezzo'] * $porzioneScelta;
            } else {
                $prezzoOggetto = $itemDB['prezzo'];
            }

            // creo l'elemento
            $nuovoElemento = array(
                'id' => $itemDB['id'],
                'nome' => $itemDB['nome'],
                'prezzo_unitario' => $prezzoOggetto,
                'quantita' => $quantita,
                'tipo' => $itemDB['tipo'],
                'porzione' => $porzioneScelta,
                'targa' => (isset($_POST['chkTarga']) && !empty($_POST['testoTarga'])) ? $_POST['testoTarga'] : null
            );

            if (!isset($_SESSION['carrello'])) {
                $_SESSION['carrello'] = array();
            }
            $_SESSION['carrello'][] = $nuovoElemento;
        }
    }
    header("Location: carrello.php");
    exit;
}

// rimozione prodotto dal carrello
if (isset($_GET['action']) && $_GET['action'] == 'rimuovi' && isset($_GET['index'])) {
    $index = (int)$_GET['index'];
    if (isset($_SESSION['carrello'][$index])) {
        unset($_SESSION['carrello'][$index]);
        $_SESSION['carrello'] = array_values($_SESSION['carrello']);
    }
    header("Location: carrello.php");
    exit;
}

// costruzione tabella
$contenutoGenerato = "";

if (isset($_SESSION['carrello']) && count($_SESSION['carrello']) > 0) {
    
    // righe
    $righeProdotti = "";
    $totaleCarrello = 0;

    foreach ($_SESSION['carrello'] as $index => $item) {
        $prezzoTotaleItem = $item['prezzo_unitario'] * $item['quantita'];
        $totaleCarrello += $prezzoTotaleItem;

        $dettagliExtra = "";
        if ($item['tipo'] === 'Torta') {
            if ($item['porzione']) {
                $dettagliExtra .= "<small>Torta per: {$item['porzione']} persone</small><br>";
            }
            if ($item['targa']) {
                $dettagliExtra .= "<small>Targa: " . htmlspecialchars($item['targa']) . "</small><br>";
            }
        }

        $righeProdotti .= "<tr>
            <td data-label='Prodotto'><strong>" . htmlspecialchars($item['nome']) . "</strong></td>
            <td data-label='Dettagli'>" . ($dettagliExtra ? $dettagliExtra : "-") . "</td>
            <td data-label='Quantità'>" . $item['quantita'] . "</td>
            <td data-label='Prezzo'>€" . number_format($item['prezzo_unitario'], 2, ',', '.') . "</td>
            <td data-label='Totale'><strong>€" . number_format($prezzoTotaleItem, 2, ',', '.') . "</strong></td>
            <td data-label='Azioni'>
                <a href='carrello.php?action=rimuovi&index=$index' class='link-rimuovi'>Rimuovi</a>
            </td>
        </tr>";
    }

    $totaleFormat = "€" . number_format($totaleCarrello, 2, ',', '.');

    // checkout con data se loggato
    $formCheckout = "";
    if (isset($_SESSION['ruolo'])) {
        $minDate = date('Y-m-d', strtotime('+2 day'));
        $formCheckout = "
        <form action='checkout.php' method='POST' class='form-checkout'>
            <div class='box-data'>
                <label for='dataRitiro'>Data di Ritiro:</label>
                <input type='date' id='dataRitiro' name='dataRitiro' min='$minDate' required>
            </div>
            <button type='submit' class='pulsanteGenerico'>Conferma e Ordina</button>
        </form>";
    } else {
        $formCheckout = "<a href='login.php' class='pulsanteGenerico'>Accedi per ordinare</a>";
    }

    // assemblaggio Finale
    $contenutoGenerato = "
    <div id='contenitoreTabella'>
        <table summary='Riepilogo prodotti nel carrello'>
            <thead>
                <tr>
                    <th scope='col'>Prodotto</th>
                    <th scope='col'>Info Extra</th>
                    <th scope='col'>Qtà</th>
                    <th scope='col'>Prezzo</th>
                    <th scope='col'>Totale</th>
                    <th scope='col'></th>
                </tr>
            </thead>
            <tbody>
                $righeProdotti
            </tbody>
            <tfoot>
                <tr>
                    <td colspan='4' class='totaleLabel'>Totale Ordine:</td>
                    <td class='totaleValore'>$totaleFormat</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class='azioniCarrello'>
            <a href='torte-pasticcini.php?tipo=torte' class='pulsanteSecondario'>← Continua acquisti</a>
            $formCheckout
        </div>
    </div>";

} else {
    // CARRELLO VUOTO
    $contenutoGenerato = "
    <div class='carrello-vuoto'>
        <p>Il tuo carrello è vuoto.</p>
        <a href='torte-pasticcini.php?tipo=torte' class='pulsanteGenerico'>Inizia lo shopping</a>
    </div>";
}

$paginaHTML = str_replace("[ContenutoCarrello]", $contenutoGenerato, $paginaHTML);
echo $paginaHTML;
?>