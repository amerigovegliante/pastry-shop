<?php
// Avvio sessione se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// Controllo accesso
if (!isset($_SESSION['ruolo'])) {
    header("Location: login");
    exit;
}

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$db = new DBAccess();
$connessione = $db->openDBConnection();
$messaggioSistema = "";

// gestione azioni post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $messaggioSistema = "<p class='errore' role='alert'>Errore di sicurezza: Token non valido. Ricarica la pagina.</p>";
    } 
    else {
        // Logica Modifica Dati
        if ($_POST['action'] === 'modificaDati') {
            $nuovoNome = htmlspecialchars(strip_tags(trim($_POST['nome'])));
            $nuovoCognome = htmlspecialchars(strip_tags(trim($_POST['cognome'])));
            $nuovoTelefono = htmlspecialchars(strip_tags(trim($_POST['telefono'])));
            
            if(empty($nuovoNome) || empty($nuovoCognome) || empty($nuovoTelefono)){
                $messaggioSistema = "<p class='errore' role='alert'>Tutti i campi sono obbligatori.</p>";
            } else {
                if($connessione){
                    $esito = $db->updatePersona($_SESSION['email'], $nuovoNome, $nuovoCognome, $nuovoTelefono);
                    if($esito){
                        $messaggioSistema = "<p class='successo' role='alert'>Dati aggiornati con successo!</p>";
                        $_SESSION['nome'] = $nuovoNome;
                        $_SESSION['cognome'] = $nuovoCognome;
                    } else {
                        $messaggioSistema = "<p class='errore' role='alert'>Errore durante l'aggiornamento dati.</p>";
                    }
                }
            }
        } 
        // Logica Elimina Account
        else if ($_POST['action'] === 'eliminaAccount') {
            if($connessione){
                $esito = $db->deletePersona($_SESSION['email']);
                if($esito){
                    session_destroy();
                    header("Location: home"); 
                    exit;
                } else {
                    $messaggioSistema = "<p class='errore' role='alert'>Non puoi eliminare l'account perché hai ordini in corso.</p>";
                }
            }
        }
    }
}

// caricamento template
$paginaHTML = file_get_contents( __DIR__ .'/../html/areaPersonale.html');
if ($paginaHTML === false) die("Errore template");

// Recupero Dati
$datiUtente = null;
if($connessione){
    $datiUtente = $db->getPersona($_SESSION['email']);
}
if(!$datiUtente){
    $datiUtente = ['nome'=>$_SESSION['nome'], 'cognome'=>$_SESSION['cognome'], 'telefono'=>'', 'email'=>$_SESSION['email']];
}

// generazione tabella ordini
$tabellaOrdiniHTML = "<p>Nessun ordine effettuato.</p>";

if($connessione){
    $ordini = $db->getOrdiniUtente($_SESSION['email']);
    
    if(!empty($ordini)){
        $tabellaOrdiniHTML = "
        <div class='table-container' tabindex='0'> <table class='tabella-ordini'>
                <caption>Storico degli ordini effettuati con stato e totale</caption>
                <thead>
                    <tr>
                        <th scope='col'>N. Ordine</th>
                        <th scope='col'>Data</th>
                        <th scope='col'>Stato</th>
                        <th scope='col'>Totale</th>
                        <th scope='col'><span class='sr-only'>Azioni</span></th>
                    </tr>
                </thead>
                <tbody>";
        
        $StatiOrdine = [1=>'In attesa', 2=>'In preparazione', 3=>'Pronto', 4=>'Ritirato'];
        
        foreach($ordini as $o){
            $dataIta = date("d/m/Y", strtotime($o['ordinazione']));
            $statoTesto = isset($StatiOrdine[$o['stato']]) ? $StatiOrdine[$o['stato']] : 'Sconosciuto';
            $totaleFmt = number_format($o['totale'], 2, ',', '.');
            
            $tabellaOrdiniHTML .= "
            <tr>
                <th scope='row' data-label='Numero'>#{$o['numero']}</th>
                <td data-label='Data'>$dataIta</td>
                <td data-label='Stato'><span class='stato-tag s-{$o['stato']}'>$statoTesto</span></td>
                <td data-label='Totale'>€$totaleFmt</td>
                <td data-label='Azioni'>
                     <a href='dettaglioOrdine.php?id={$o['id']}' class='link-dettaglio' aria-label='Vedi dettagli ordine numero {$o['numero']}'>Dettagli</a>
                </td>
            </tr>";
        }
        $tabellaOrdiniHTML .= "</tbody></table></div>";
    }
}

// Admin
$sezioneAdmin = "";
if (isset($_SESSION['ruolo']) && $_SESSION['ruolo'] === 'admin') {
    $sezioneAdmin = '
    <section class="pannello-admin" aria-labelledby="titolo-admin">
        <div class="admin-header">
            <h3 id="titolo-admin">Pannello Amministrazione</h3>
            <p>Accesso riservato allo staff</p>
        </div>
        <div class="admin-azioni">
            <a href="ordini-amministratore" class="bottone-admin">
                <span class="icona-btn">📋</span> Gestione Ordini Clienti
            </a>
            <a href="aggiungi-prodotto" class="bottone-admin">
                <span class="icona-btn">🍰</span> Aggiungi Nuovo Prodotto
            </a>
        </div>
    </section>';
}

$db->closeDBConnection();

// Sostituzioni
$paginaHTML = str_replace('[NomeUtente]', htmlspecialchars($datiUtente['nome']), $paginaHTML);
$paginaHTML = str_replace('[MessaggiSistema]', $messaggioSistema, $paginaHTML);
$paginaHTML = str_replace('[SezioneAdmin]', $sezioneAdmin, $paginaHTML);
$paginaHTML = str_replace('[valoreNome]', htmlspecialchars($datiUtente['nome']), $paginaHTML);
$paginaHTML = str_replace('[valoreCognome]', htmlspecialchars($datiUtente['cognome']), $paginaHTML);
$paginaHTML = str_replace('[valoreTelefono]', htmlspecialchars($datiUtente['telefono']), $paginaHTML);
$paginaHTML = str_replace('[valoreEmail]', htmlspecialchars($datiUtente['email']), $paginaHTML);
$paginaHTML = str_replace('[TabellaOrdini]', $tabellaOrdiniHTML, $paginaHTML);

// iniezione token CSRF
$csrfField = "<input type='hidden' name='csrf_token' value='$token'>";
$paginaHTML = str_replace('[csrfToken]', $csrfField, $paginaHTML);

echo $paginaHTML;
?>