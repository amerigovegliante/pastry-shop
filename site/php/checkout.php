<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";
// controlli di sicurezza iniziali ----------------------------------------------------

// se non loggato -> Login
if (!isset($_SESSION['ruolo'])) {
    header("Location: login.php");
    exit;
}

// se carrello vuoto o inesistente -> Carrello
if (!isset($_SESSION['carrello']) || count($_SESSION['carrello']) === 0) {
    header("Location: carrello.php");
    exit;
}

// se non è una richiesta POST -> Carrello
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: carrello.php");
    exit;
}

// verifica Token CSRF (Anti-Forgery)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Errore di sicurezza: Richiesta non valida. Torna al carrello.");
}
// consumiamo il token (opzionale, ma buona pratica per azioni one-time)
unset($_SESSION['csrf_token']); 



// connessione al DB ----------------------------------------------------------------------
$db = new DBAccess();
$aperta = $db->openDBConnection();
$connessione = $db->getConn();

// Se il DB non risponde, impostiamo l'errore in sessione e andiamo alla pagina di esito
if (!$aperta || !$connessione) {
    $_SESSION['risultato_esito'] = ['successo' => false];
    header("Location: esito.php");
    exit;
}

// elaborazione ordine con transazione ---------------------------------------------------
try {
    // tutto quello che succede da qui in poi è "in sospeso" fino al commit()
    $connessione->begin_transaction();

    // recupero e Pulizia Dati Input
    $emailUtente = $_SESSION['email'];
    $nomeOrdine = htmlspecialchars($_POST['nome']);
    $cognomeOrdine = htmlspecialchars($_POST['cognome']);
    $telefonoOrdine = htmlspecialchars($_POST['telefono']);
    $annotazioni = isset($_POST['annotazioni']) ? htmlspecialchars($_POST['annotazioni']) : null;

    $dataOrdinazione = date('Y-m-d H:i:s');
    
    // gestione data ritiro
    if (isset($_POST['dataRitiro']) && !empty($_POST['dataRitiro'])) {
        $dataRitiro = $_POST['dataRitiro'] . " 10:00:00";
    } else {
        $dataRitiro = date('Y-m-d H:i:s', strtotime('+2 days 10:00:00'));
    }
    
    // calcolo totale ordine
    $totaleOrdine = 0;
    foreach ($_SESSION['carrello'] as $item) {
        $totaleOrdine += ($item['prezzo_unitario_calcolato'] * $item['quantita']);
    }

    // Generazione numero ordine casuale (dite che vada bene? non è sicuro al 100% ma è semplice)
    $numeroOrdine = rand(10000, 99999);
    $stato = 1; // 1 = in attesa

    // inserimento ordine
    $queryOrdine = "INSERT INTO ordine (ritiro, ordinazione, numero, persona, nome, cognome, telefono, annotazioni, stato, totale) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($connessione, $queryOrdine);
    if (!$stmt) throw new Exception("Errore prepare Ordine");

    mysqli_stmt_bind_param($stmt, "ssisssssid", 
        $dataRitiro, $dataOrdinazione, $numeroOrdine, $emailUtente, 
        $nomeOrdine, $cognomeOrdine, $telefonoOrdine, $annotazioni,
        $stato, $totaleOrdine
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Errore execute Ordine");
    }
    
    // recupero l'ID autogenerato dell'ordine appena creato
    $idOrdine = mysqli_insert_id($connessione);
    mysqli_stmt_close($stmt);

    $riepilogoProdotti = '
    <table class="tabella-riepilogo">
        <caption>Dettaglio dei prodotti ordinati</caption>
        <thead>
            <tr>
                <th scope="col">Prodotto</th>
                <th scope="col">Dettagli</th>
                <th scope="col">Quantità</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($_SESSION['carrello'] as $item) {

    $tipoItem = strtolower($item['tipo']);

    $nomeProd = htmlspecialchars($item['nome']);
    $qty = (int)$item['quantita'];

    // costruzione dettagli extra
    $dettagliExtra = '<ul class="dettagli-prodotto">';

    if ($tipoItem === 'torta') {
        $porzioni = (int)$item['porzione'];
        $dettagliExtra .= "<li>Formato: $porzioni persone</li>";

        if (!empty($item['targa'])) {
            $targa = htmlspecialchars($item['targa']);
            $dettagliExtra .= "<li>Targa: $targa</li>";
        }
    } else {
        $dettagliExtra .= "<li>-</li>";
    }

    $dettagliExtra .= '</ul>';

    $riepilogoProdotti .= "
        <tr>
            <th scope=\"row\">$nomeProd</th>
            <td>$dettagliExtra</td>
            <td class=\"cella-numerica\">$qty</td>
        </tr>";

    if ($tipoItem === 'torta') {
        $queryTorta = "INSERT INTO ordine_torta (torta, ordine, porzioni, targa, numero_torte) 
                       VALUES (?, ?, ?, ?, ?)";
        $stmtT = mysqli_prepare($connessione, $queryTorta);

        $idItem = (int)$item['id'];
        $targa = $item['targa'] ?? "";
        $numeroTorte = (int)$item['quantita'];

        mysqli_stmt_bind_param(
            $stmtT,
            "iiisi",
            $idItem,
            $idOrdine,
            $porzioni,
            $targa,
            $numeroTorte
        );

        mysqli_stmt_execute($stmtT);
        mysqli_stmt_close($stmtT);

    } else {
        $queryPast = "INSERT INTO ordine_pasticcino (pasticcino, ordine, quantita) 
                      VALUES (?, ?, ?)";
        $stmtP = mysqli_prepare($connessione, $queryPast);

        $idItem = (int)$item['id'];
        $quantita = (int)$item['quantita'];

        mysqli_stmt_bind_param($stmtP, "iii", $idItem, $idOrdine, $quantita);
        mysqli_stmt_execute($stmtP);
        mysqli_stmt_close($stmtP);
    }
}

    $riepilogoProdotti .= "
            </tbody>
        </table>";

    // commit finale. confermiamo le modifiche al database. Se non arriviamo qui, scatta il catch.
    $connessione->commit();

    // Salviamo i dati necessari per la pagina di ringraziamento in sessione
    $_SESSION['risultato_esito'] = [
        'successo' => true,
        'numero_ordine' => $numeroOrdine,
        'nome_cognome' => "$nomeOrdine $cognomeOrdine",
        'totale' => $totaleOrdine,
        'data_ritiro' => $dataRitiro,
        'lista_prodotti' => $riepilogoProdotti
    ];

    unset($_SESSION['carrello']);
    $db->closeDBConnection();
    header("Location: esito.php");
    exit;

} catch (Exception $e) {
    // Qualcosa è andato storto: annulliamo tutte le modifiche al DB
    $connessione->rollback();
    $db->closeDBConnection();
    $_SESSION['risultato_esito'] = ['successo' => false];
    header("Location: esito.php");
    exit;
}
?>