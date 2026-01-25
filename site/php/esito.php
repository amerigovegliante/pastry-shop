<?php
session_start();

// se l'utente prova ad aprire questa pagina direttamente senza passare dal checkout, lo rispediamo alla home
if (!isset($_SESSION['risultato_esito'])) {
    header("Location: ../../index.html");
    exit;
}

$dati = $_SESSION['risultato_esito'];

// in caso di errore:
if ($dati['successo'] === false) {
    $paginaHTML = file_get_contents( __DIR__ .'/../html/esito_negativo.html');
    unset($_SESSION['risultato_esito']);
    
    echo $paginaHTML;
    exit;
}

// nel caso l'ordine sia andato a buon fine:
if ($dati['successo'] === true) {
    $paginaHTML = file_get_contents('../html/esito_positivo.html');

    // Formattazione dati per la vista
    $totaleFormat = "€" . number_format($dati['totale'], 2, ',', '.');
    $dataRitiroFormat = date('d/m/Y', strtotime($dati['data_ritiro']));
    
    // Sostituzione Placeholder
    $paginaHTML = str_replace("[numeroOrdine]", $dati['numero_ordine'], $paginaHTML);
    $paginaHTML = str_replace("[nomeCognome]", htmlspecialchars($dati['nome_cognome']), $paginaHTML);
    $paginaHTML = str_replace("[totale]", $totaleFormat, $paginaHTML);
    $paginaHTML = str_replace("[dataRitiro]", $dataRitiroFormat, $paginaHTML);
    $paginaHTML = str_replace("[ListaProdotti]", $dati['lista_prodotti'], $paginaHTML);

    // puliamo la sessione cosí se l'utente ricarica la pagina verrà reindirizzato in homee, questo impedisce di vedere la conferma all'infinito o di ri-ordinare per sbaglio.
    unset($_SESSION['risultato_esito']);
    echo $paginaHTML;
    exit;
}
?>