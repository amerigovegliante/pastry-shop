<?php
$paginaHTML = file_get_contents('../html/esito_form.html');
if ($paginaHTML === false) {
    die("Errore template");
}

// variabile che conterrà tutto l'HTML del messaggio
$htmlEsito = "";

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Accesso diretto non consentito
    $htmlEsito = "<h2 class='errore'>Accesso non valido</h2>
                  <p>Si prega di utilizzare il modulo contatti.</p>";
} else {
    // recupero dati
    $email = isset($_POST['identificativo']) ? trim($_POST['identificativo']) : '';
    $messaggio = isset($_POST['domande']) ? trim($_POST['domande']) : '';

    // validazione
    if (empty($email) || empty($messaggio) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $htmlEsito = "<h2 class='errore'>Dati mancanti</h2>
                      <p>L'email non è valida o il messaggio è vuoto. <a href='../html/contattaci.html'>Riprova</a>.</p>";
    } else {
        // salvataggio
        $dataOra = date('d/m/Y H:i:s');
        $logEntry = "[$dataOra] - Da: $email\nMessaggio: $messaggio\n---------------------------------------------------\n";
        $fileLog = '../../messaggi_ricevuti.txt';

        if (file_put_contents($fileLog, $logEntry, FILE_APPEND | LOCK_EX)) {
            // SUCCESSO
            $htmlEsito = "<h2 class='successo'>Messaggio inviato!</h2>
                          <p>Grazie <strong>" . htmlspecialchars($email) . "</strong>, abbiamo ricevuto la tua richiesta.</p>";
        } else {
            // ERRORE
            $htmlEsito = "<h2 class='errore'>Errore di sistema</h2>
                          <p>Impossibile salvare il messaggio. Ti preghiamo di chiamarci.</p>";
        }
    }
}

$paginaHTML = str_replace("[Esito]", $htmlEsito, $paginaHTML);

echo $paginaHTML;
?>