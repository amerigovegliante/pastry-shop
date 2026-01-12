<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

// controlli preliminari
if (!isset($_SESSION['ruolo'])) {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['carrello']) || count($_SESSION['carrello']) === 0) {
    header("Location: carrello.php");
    exit;
}

$db = new DBAccess();
$aperta = $db->openDBConnection();
$connessione = $db->getConn();

if (!$aperta || !$connessione) {
    die("Errore critico: Impossibile connettersi al database.");
}

// recupero dati utente
$emailUtente = $_SESSION['email'];
$queryUtente = "SELECT nome, cognome, telefono FROM persona WHERE email = ?";
$stmtU = mysqli_prepare($connessione, $queryUtente);
mysqli_stmt_bind_param($stmtU, "s", $emailUtente);
mysqli_stmt_execute($stmtU);
$resU = mysqli_stmt_get_result($stmtU);
$datiUtente = mysqli_fetch_assoc($resU);
mysqli_stmt_close($stmtU);

if (!$datiUtente) {
    die("Errore: Utente non trovato nel database.");
}

// preparazione Dati Ordine
$totaleOrdine = 0;
foreach ($_SESSION['carrello'] as $item) {
    $totaleOrdine += ($item['prezzo_unitario'] * $item['quantita']);
}

$dataOrdinazione = date('Y-m-d H:i:s');
if (isset($_POST['dataRitiro']) && !empty($_POST['dataRitiro'])) {
    $dataRitiro = $_POST['dataRitiro'] . " 10:00:00"; 
} else {
    $dataRitiro = date('Y-m-d H:i:s', strtotime('+2 days'));
}
$numeroOrdine = rand(1000, 9999);
$stato = 1; // In attesa

// inserimento in tabella ORDINE
$queryOrdine = "INSERT INTO ordine (ritiro, ordinazione, numero, persona, nome, cognome, telefono, stato, totale) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($connessione, $queryOrdine);
if (!$stmt) {
    die("Errore preparazione query ordine: " . mysqli_error($connessione));
}

mysqli_stmt_bind_param($stmt, "ssissssid", 
    $dataRitiro, $dataOrdinazione, $numeroOrdine, $emailUtente, 
    $datiUtente['nome'], $datiUtente['cognome'], $datiUtente['telefono'], 
    $stato, $totaleOrdine
);

if (mysqli_stmt_execute($stmt)) {
    $idOrdine = mysqli_insert_id($connessione);
    mysqli_stmt_close($stmt);

    // inserimento Prodotti
    foreach ($_SESSION['carrello'] as $item) {
        
        if ($item['tipo'] === 'Torta') {
            
            $queryTorta = "INSERT INTO ordine_torta (torta, ordine, porzioni, targa) VALUES (?, ?, ?, ?)";
            $stmtT = mysqli_prepare($connessione, $queryTorta);
            
            $porzioniTotaliDB = (int)$item['porzione'] * (int)$item['quantita'];
            
            $targa = $item['targa'];
            
            mysqli_stmt_bind_param($stmtT, "iiis", $item['id'], $idOrdine, $porzioniTotaliDB, $targa);
            mysqli_stmt_execute($stmtT);
            mysqli_stmt_close($stmtT);

        } else {
            $queryPast = "INSERT INTO ordine_pasticcino (pasticcino, ordine, quantita) VALUES (?, ?, ?)";
            $stmtP = mysqli_prepare($connessione, $queryPast);
            
            mysqli_stmt_bind_param($stmtP, "iii", $item['id'], $idOrdine, $item['quantita']);
            mysqli_stmt_execute($stmtP);
            mysqli_stmt_close($stmtP);
        }
    }

    // in caso di successo
    unset($_SESSION['carrello']);
    $db->closeDBConnection();

    ?>
    <!DOCTYPE html>
    <html lang="it">
    <head>
        <meta charset="utf-8">
        <title>Ordine Confermato</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../css/style.css">
        <link rel="stylesheet" href="../css/mini.css" media="screen and (max-width:800px)">
    </head>
    <body>
        <header>
            <div class="contenuto">
                <div id="logo">
                    <h1>Pasticceria Padovana</h1>
                    <p class="sirivennela-regular">Grazie per il tuo acquisto</p>
                </div>
            </div>
        </header>
        <main>
            <section class="contenuto" style="text-align: center; padding: 3em;">
                <h2 class="successo" style="font-size: 2em; margin-bottom: 0.5em;">Ordine Confermato!</h2>
                <div style="background: white; padding: 2em; border-radius: 10px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 1.2em;">Il tuo numero d'ordine è: <strong style="color:#8b5437;">#<?php echo $numeroOrdine; ?></strong></p>
                    <p>Totale pagato: <strong>€<?php echo number_format($totaleOrdine, 2, ',', '.'); ?></strong></p>
                    <p>Data ritiro prevista: <strong><?php echo date('d/m/Y', strtotime($dataRitiro)); ?></strong></p>
                    <hr style="margin: 1.5em 0; border: 0; border-top: 1px solid #ddd;">
                    <p>Riceverai una email di conferma a breve.</p>
                </div>
                
                <div style="margin-top: 2em;">
                    <a href="../../index.html" class="pulsanteGenerico">Torna alla Home</a>
                </div>
            </section>
        </main>
        <footer>
            <div class="contenuto"><p>&copy; 2025 Pasticceria Padovana</p></div>
        </footer>
    </body>
    </html>
    <?php
    exit;

} else {
    die("Errore critico durante il salvataggio dell'ordine: " . mysqli_error($connessione));
}
?>