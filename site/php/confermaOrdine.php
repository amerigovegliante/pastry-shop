<?php
// Avvio sessione solo se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "dbConnection.php";

// controllo accesso
if (!isset($_SESSION['ruolo'])) {
    header("Location: login");
    exit;
}
if (!isset($_SESSION['carrello']) || count($_SESSION['carrello']) === 0) {
    header("Location: carrello");
    exit;
}

// generiamo il token CSRF per il form di conferma ordine
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$paginaHTML = file_get_contents( __DIR__ .'/../html/confermaOrdine.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$db = new DBAccess();
$db->openDBConnection(); 
$connessione = $db->getConn(); 

$nomePre = "";
$cognomePre = "";
$telefonoPre = "";

if ($connessione) {
    $emailUtente = $_SESSION['email'];
    $queryUtente = "SELECT nome, cognome, telefono FROM persona WHERE email = ?";
    $stmt = mysqli_prepare($connessione, $queryUtente);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $emailUtente);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $nomePre = $row['nome'];
            $cognomePre = $row['cognome'];
            $telefonoPre = $row['telefono'];
        }
        mysqli_stmt_close($stmt);
    }
    $db->closeDBConnection();
}else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$totaleOrdine = 0;
foreach ($_SESSION['carrello'] as $item) {
    $prezzoItem = isset($item['prezzo_unitario_calcolato']) ? $item['prezzo_unitario_calcolato'] : 0;
    $totaleOrdine += ($prezzoItem * $item['quantita']);
}

$totaleFormat = number_format($totaleOrdine, 2, ',', '.');
$minDate = date('Y-m-d', strtotime('+2 day'));

// Sostituzione Segnaposto
$paginaHTML = str_replace("[valoreNome]", htmlspecialchars($nomePre), $paginaHTML);
$paginaHTML = str_replace("[valoreCognome]", htmlspecialchars($cognomePre), $paginaHTML);
$paginaHTML = str_replace("[valoreTelefono]", htmlspecialchars($telefonoPre), $paginaHTML);
$paginaHTML = str_replace("[minDate]", $minDate, $paginaHTML);
$paginaHTML = str_replace("[totale]", $totaleFormat, $paginaHTML);

$inputToken = "<input type='hidden' name='csrf_token' value='$token'>";
$paginaHTML = str_replace('[csrfToken]', $inputToken, $paginaHTML);

echo $paginaHTML;
?>