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
            // FIX PUNTO 3: Tolto htmlspecialchars, tenuto solo trim e strip_tags per il DB
            $nuovoNome = trim(strip_tags($_POST['nome']));
            $nuovoCognome = trim(strip_tags($_POST['cognome']));
            $nuovoTelefono = trim(strip_tags($_POST['telefono'])); // Spazi già tolti dal browser type=tel, ma trim aiuta
            
            // Validazione Campi Obbligatori
            if(empty($nuovoNome) || empty($nuovoCognome) || empty($nuovoTelefono)){
                $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Tutti i campi (Nome, Cognome, Telefono) sono obbligatori.</p>";
            } 
            // Validazione Telefono
            elseif (!ctype_digit($nuovoTelefono) || strlen($nuovoTelefono) < 9 || strlen($nuovoTelefono) > 15) {
                $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Il telefono non è valido (usare solo numeri).</p>";
            } 
            else {
                // LOGICA PASSWORD (FIX PUNTO 2)
                $errorePassword = "";
                $aggiornaPassword = false;
                $nuovaPass = "";

                if (!empty($_POST['nuova_password'])) {
                    $nuovaPass = trim($_POST['nuova_password']);
                    
                    // Controlli Regex (Coerenza con registrazione.php)
                    if (strlen($nuovaPass) < 8) {
                        $errorePassword = 'La password deve essere lunga almeno 8 caratteri.';
                    } elseif (!preg_match('/[A-Z]/', $nuovaPass)) {
                        $errorePassword = 'La password deve contenere almeno una lettera maiuscola.';
                    } elseif (!preg_match('/[a-z]/', $nuovaPass)) {
                        $errorePassword = 'La password deve contenere almeno una lettera minuscola.';
                    } elseif (!preg_match('/\d/', $nuovaPass)) {
                        $errorePassword = 'La password deve contenere almeno un numero.';
                    } elseif (!preg_match('/[\W_]/', $nuovaPass)) {
                        $errorePassword = 'La password deve contenere almeno un simbolo.';
                    }
                    
                    if (empty($errorePassword)) {
                        $aggiornaPassword = true;
                    }
                }

                if (!empty($errorePassword)) {
                    $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Errore Password: $errorePassword</p>";
                } else {
                    // Procediamo con l'update
                    if($connessione){
                        $esitoDati = $db->updatePersona($_SESSION['email'], $nuovoNome, $nuovoCognome, $nuovoTelefono);
                        $esitoPass = true; // Default true se non la cambiamo

                        if ($aggiornaPassword) {
                            $esitoPass = $db->updatePassword($_SESSION['email'], $nuovaPass);
                        }

                        if($esitoDati && $esitoPass){
                            $_SESSION['nome'] = $nuovoNome;
                            $_SESSION['cognome'] = $nuovoCognome;
                            $_SESSION['msg_flash'] = "<p class='successo' role='alert'>Profilo aggiornato con successo!</p>";
                        } else {
                            $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Errore server durante l'aggiornamento.</p>";
                        }
                    } else {
                        $_SESSION['msg_flash'] = "<p class='errore' role='alert'>Errore di connessione al database.</p>";
                    }
                }
            }
            // Refresh
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
                    $_SESSION['popup_errore'] = "Impossibile eliminare l'account, hai degli ordini in corso che devono ancora essere ritirati o completati.";
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
            // Sicurezza: Escape dei dati in output
            $idSicuro = htmlspecialchars($o['id'], ENT_QUOTES, 'UTF-8');
            $statoKey = (int)$o['stato']; // Cast a intero per sicurezza
            
            $dataIta = date("d/m/Y", strtotime($o['ordinazione']));
            $statoTesto = isset($StatiOrdine[$statoKey]) ? $StatiOrdine[$statoKey] : 'Sconosciuto';
            $totaleFmt = number_format($o['totale'], 2, ',', '.');
            
            $tabellaOrdiniHTML .= "
            <tr>
                <th scope='row' data-label='Numero'>$idSicuro</th> 
                <td data-label='Data'>$dataIta</td>
                <td data-label='Stato'><span class='stato-tag s-$statoKey'>$statoTesto</span></td>
                <td data-label='Totale'>€$totaleFmt</td>
                <td data-label='Azioni'>
                     <a href=\"dettaglio-ordine?id=$idSicuro\" class='generic-button' aria-label='Vedi dettagli ordine numero $idSicuro'>Dettagli</a>
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
            <a href="ordini-amministratore" class="generic-button">
                Gestione Ordini Clienti
            </a><br>
            <a href="aggiungi-prodotto" class="generic-button">
                Gestione Prodotti
            </a>
        </div>
    </section>';
}

$db->closeDBConnection();

$paginaHTML = str_replace('[NomeUtente]', htmlspecialchars($datiUtente['nome'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
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

// Controllo se c'è un popup da mostrare
if (isset($_SESSION['popup_errore'])) {
    $msgPopupSafe = json_encode($_SESSION['popup_errore']);
    $scriptPopup = "<script>alert($msgPopupSafe);</script></body>";
    
    $paginaHTML = str_replace('</body>', $scriptPopup, $paginaHTML);
    unset($_SESSION['popup_errore']);
}

echo $paginaHTML;
?>