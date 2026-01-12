<?php
session_start();

if (!isset($_SESSION['ruolo'])) {
    header("Location: login.php");
    exit;
}

$paginaHTML = file_get_contents('../html/areaPersonale.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere areaPersonale.html");
}

// recupero dati sessione
$nome = isset($_SESSION['nome']) ? $_SESSION['nome'] : 'Utente';
$cognome = isset($_SESSION['cognome']) ? $_SESSION['cognome'] : '';

// gestione pulsante Admin
$pulsanteAdmin = "";
if ($_SESSION['ruolo'] === 'admin') {
    // se è admin, creo un elemento della lista con il link
    $pulsanteAdmin = '<li><a href="ordiniAdmin.php" class="pulsanteGenerico">Gestione Ordini (Admin)</a></li>';
}

// sostituzioni
$paginaHTML = str_replace('[NomeUtente]', htmlspecialchars($nome), $paginaHTML);
$paginaHTML = str_replace('[CognomeUtente]', htmlspecialchars($cognome), $paginaHTML);
$paginaHTML = str_replace('[PulsanteAdmin]', $pulsanteAdmin, $paginaHTML);

echo $paginaHTML;
?>