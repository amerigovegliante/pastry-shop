<?php
// Avvio sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect home se non c'è esito
if (!isset($_SESSION['risultato_esito'])) {
    header("Location: home");
    exit;
}

$dati = $_SESSION['risultato_esito'];

// Carichiamo il template
$paginaHTML = file_get_contents( __DIR__ .'/../html/esito.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$titoloPagina = "";
$classeEsito = "";
$contenutoEsito = "";

if ($dati['successo'] === true) {
    // --- CASO POSITIVO ---
    $titoloPagina = "Ordine Confermato!";
    $classeEsito = "titolo-successo";
    
    $totaleFormat = "€" . number_format($dati['totale'], 2, ',', '.');
    $dataRitiroFormat = date('d/m/Y H:i', strtotime($dati['data_ritiro'])); 

    $contenutoEsito = "
        <div class='box-img-esito'>
            <img src='site/img/ordineInviato.gif' alt='Conferma ordine inviato' class='img-esito-gif'>
        </div>
        
        <p class='messaggio-intro'>Grazie! Il tuo ordine è stato registrato correttamente.</p>
        
        <dl class='dati-riepilogo'>
            <div class='riga-dati'>
                <dt>Numero Ordine</dt>
                <dd>#{$dati['id_ordine']}</dd>
            </div>
            
            <div class='riga-dati'>
                <dt>Intestatario</dt>
                <dd>" . htmlspecialchars($dati['nome_cognome']) . "</dd>
            </div>
            
            <div class='riga-dati'>
                <dt>Ritiro previsto</dt>
                <dd>{$dataRitiroFormat}</dd>
            </div>
            
            <div class='riga-dati'>
                <dt>Totale da pagare</dt>
                <dd class='prezzo-evidenza'>{$totaleFormat}</dd>
            </div>
        </dl>
        
        <p class='nota-finale'>Ti aspettiamo in negozio per il ritiro. Ricorda di portare il numero dell'ordine.</p>
    ";

} else {
    // --- CASO NEGATIVO ---
    $titoloPagina = "Errore Ordine";
    $classeEsito = "titolo-errore";

    $contenutoEsito = "
        <p class='messaggio-errore'>Ops! Non siamo riusciti a completare il tuo ordine a causa di un problema tecnico.</p>
        <p>Ti consigliamo di riprovare tra qualche minuto.</p>
        <div class='azioni-errore-interne'>
            <a href='carrello'>Torna al Carrello</a>
        </div>
    ";
}

// Sostituzione segnaposto nel template
$paginaHTML = str_replace("[titoloPagina]", $titoloPagina, $paginaHTML);
$paginaHTML = str_replace("[classeEsito]", $classeEsito, $paginaHTML);
$paginaHTML = str_replace("[contenutoEsito]", $contenutoEsito, $paginaHTML);

// Pulizia sessione
unset($_SESSION['risultato_esito']);

echo $paginaHTML;
?>