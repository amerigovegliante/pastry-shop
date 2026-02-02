<?php
// Avvio sessione solo se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// Controllo accesso
/*if (!isset($_SESSION['ruolo'])) {
    header("Location: login");
    exit;
}*/

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$db = new DBAccess();
$connessione = $db->openDBConnection();
$messaggioSistema = "";

if (isset($_SESSION['msg_flash'])) {
    $messaggioSistema = $_SESSION['msg_flash'];
    unset($_SESSION['msg_flash']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // verifica token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        // Errore sicurezza -> Salvo in sessione e ricarico
        $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Errore di sicurezza: Token non valido. Ricarica la pagina.</p>";
        header("Location: area-personale");
        exit;
    } 
    else {
        // Logica Modifica Dati
        if ($_POST['action'] === 'modificaDati') {
            $nuovoNome = htmlspecialchars(strip_tags(trim($_POST['nome'])));
            $nuovoCognome = htmlspecialchars(strip_tags(trim($_POST['cognome'])));
            $nuovoTelefono = htmlspecialchars(strip_tags(trim($_POST['telefono'])));
            
            if(empty($nuovoNome) || empty($nuovoCognome) || empty($nuovoTelefono)){
                $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Tutti i campi sono obbligatori.</p>";
            } else {
                if($connessione){
                    $esito = $db->updatePersona($_SESSION['email'], $nuovoNome, $nuovoCognome, $nuovoTelefono);
                    if($esito){
                        $_SESSION['nome'] = $nuovoNome;
                        $_SESSION['cognome'] = $nuovoCognome;
                        $_SESSION['msg_flash'] = "<p class='successo' role='alert'>Dati aggiornati con successo!</p>";
                    } else {
                        $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Errore durante l'aggiornamento dati.</p>";
                    }
                }
            }
            // per evitare di rimandare il modulo al refresh al refresh
            header("Location: area-personale");
            exit;
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
                    $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Non puoi eliminare l'account perché hai ordini in corso.</p>";
                    header("Location: area-personale");
                    exit;
                }
            }else{
                http_response_code(500);
                include __DIR__ . '/500.php';
                exit;
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
        <p id=\"descr\" class=\"visually-hidden\">Tabella organizzata per colonne che mostra lo storico degli ordini effettuati dall'utente.
        Ogni riga descrive un'ordine con numero identificativo, data di ritiro, stato, prezzo totale, azioni.</p>
        <table aria-describedby='descr'>
                <caption>Storico degli ordini effettuati</caption>
                <thead>
                    <tr>
                        <th scope='col'>Numero</th>
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
                <th scope='row' data-label='Numero'>{$o['id']}</th> 
                <td data-label='Data'>$dataIta</td>
                <td data-label='Stato'><span class='stato-tag s-{$o['stato']}'>$statoTesto</span></td>
                <td data-label='Totale'>€$totaleFmt</td>
                <td data-label='Azioni'>
                     <a href=\"dettaglio-ordine?id=".urlencode($o['id'])."\" class='generic-button' aria-label='Vedi dettagli ordine numero {$o['id']}'>Dettagli</a>
                </td> 
            </tr>";
        }
        $tabellaOrdiniHTML .= "</tbody></table>";
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
                Gestione Ordini Clienti
            </a><br>
            <a href="aggiungi-prodotto" class="bottone-admin">
                Aggiungi Nuovo Prodotto
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