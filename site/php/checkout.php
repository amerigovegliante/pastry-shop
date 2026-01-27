<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "dbConnection.php";

// --- CONTROLLI DI SICUREZZA ---
if (!isset($_SESSION['ruolo']) || !isset($_SESSION['carrello']) || count($_SESSION['carrello']) === 0 || $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: carrello");
    exit;
}
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Errore di sicurezza. Torna al carrello.");
}
unset($_SESSION['csrf_token']); 

// --- CONNESSIONE ---
$db = new DBAccess();
$aperta = $db->openDBConnection();
$connessione = $db->getConn();

if (!$aperta || !$connessione) {
    $_SESSION['risultato_esito'] = ['successo' => false];
    header("Location: esito-ordine");
    exit;
}

try {
    $connessione->begin_transaction();

    // Recupero Dati
    $emailUtente = $_SESSION['email'];
    $nomeOrdine = htmlspecialchars($_POST['nome']);
    $cognomeOrdine = htmlspecialchars($_POST['cognome']);
    $telefonoOrdine = htmlspecialchars($_POST['telefono']);
    $annotazioni = isset($_POST['annotazioni']) ? htmlspecialchars($_POST['annotazioni']) : null;
    $dataOrdinazione = date('Y-m-d H:i:s');
    
    // Gestione Data/Ora Ritiro
    if (!empty($_POST['dataRitiro']) && !empty($_POST['oraRitiro'])) {
        $dataRitiro = $_POST['dataRitiro'] . " " . $_POST['oraRitiro'] . ":00";
    } else {
        $dataRitiro = date('Y-m-d H:i:s', strtotime('+2 days 10:00:00'));
    }
    
    // Totale
    $totaleOrdine = 0;
    foreach ($_SESSION['carrello'] as $item) {
        $totaleOrdine += ($item['prezzo_unitario_calcolato'] * $item['quantita']);
    }

    $stato = 1; // 1 = in attesa

    $queryOrdine = "INSERT INTO ordine (ritiro, ordinazione, persona, nome, cognome, telefono, annotazioni, stato, totale) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($connessione, $queryOrdine);
    if (!$stmt) throw new Exception("Errore prepare Ordine");

    mysqli_stmt_bind_param($stmt, "sssssssid", 
        $dataRitiro, $dataOrdinazione, $emailUtente, 
        $nomeOrdine, $cognomeOrdine, $telefonoOrdine, $annotazioni,
        $stato, $totaleOrdine
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Errore execute Ordine");
    }
    
    $idOrdine = mysqli_insert_id($connessione);
    mysqli_stmt_close($stmt);


    foreach ($_SESSION['carrello'] as $item) {
        $tipoItem = strtolower($item['tipo']);
        
        if ($tipoItem === 'torta') {
            $queryTorta = "INSERT INTO ordine_torta (torta, ordine, porzioni, targa, numero_torte) VALUES (?, ?, ?, ?, ?)";
            $stmtT = mysqli_prepare($connessione, $queryTorta);
            $idItem = (int)$item['id'];
            $porzioni = (int)$item['porzione'];
            $targa = $item['targa'] ?? "";
            $numeroTorte = (int)$item['quantita'];
            mysqli_stmt_bind_param($stmtT, "iiisi", $idItem, $idOrdine, $porzioni, $targa, $numeroTorte);
            mysqli_stmt_execute($stmtT);
            mysqli_stmt_close($stmtT);
        } else {
            $queryPast = "INSERT INTO ordine_pasticcino (pasticcino, ordine, quantita) VALUES (?, ?, ?)";
            $stmtP = mysqli_prepare($connessione, $queryPast);
            $idItem = (int)$item['id'];
            $quantita = (int)$item['quantita'];
            mysqli_stmt_bind_param($stmtP, "iii", $idItem, $idOrdine, $quantita);
            mysqli_stmt_execute($stmtP);
            mysqli_stmt_close($stmtP);
        }
    }

    $connessione->commit();

    // dati per esito.php
    $_SESSION['risultato_esito'] = [
        'successo' => true,
        'id_ordine' => $idOrdine,      
        'nome_cognome' => "$nomeOrdine $cognomeOrdine",
        'totale' => $totaleOrdine,
        'data_ritiro' => $dataRitiro
    ];

    unset($_SESSION['carrello']);
    $db->closeDBConnection();
    header("Location: esito-ordine");
    exit;

} catch (Exception $e) {
    $connessione->rollback();
    $db->closeDBConnection();
    $_SESSION['risultato_esito'] = ['successo' => false];
    header("Location: esito-ordine");
    exit;
}
?>