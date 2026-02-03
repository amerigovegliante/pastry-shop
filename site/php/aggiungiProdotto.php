<?php
//avvio sessione solo se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// 1. SICUREZZA: Controllo che l'utente sia loggato E sia ADMIN
/*if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login");
    exit;
}*/

// 2. SICUREZZA: Generazione CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$paginaHTML = file_get_contents( __DIR__ .'/../html/aggiungiProdotto.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;

}

// DICHIARAZIONE VARIABILI
$tipo = '';
$nome = '';
$descrizione = '';
$prezzo = '';
$immagine = ''; 
$testoAlternativo = '';
$allergeni = [];    //array per gli allergeni selezionati
$selTorta = '';
$selPasticcino = '';

$erroreTipo = '';
$erroreNome = '';
$erroreDescrizione = '';
$errorePrezzo = '';
$erroreImmagine = '';
$erroreTestoAlternativo = '';

$messaggioErrore = '';       //per errore generico o di connessione con il DB
$messaggioConferma = '';     //inserimento dei dati avvenuto con successo

$tabellaItems = '';

function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    return $value;
}

//funzione per inserire gli allergeni
function inserisciAllergeni($db, $itemId, $allergeni) {
    if (!empty($allergeni) && !empty($itemId) && is_array($allergeni)) {
        foreach ($allergeni as $a) {
            $success = $db->insertNewItemAllergico($itemId, htmlspecialchars($a));
            if (!$success) return false;
        }
    }
    return true;
}

//GESTIONE AGGIUNTA PRODOTTO
if(isset($_POST['submit'])){

    // 3. SICUREZZA: Verifica CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $messaggioErrore = '<p class="errore" role="alert">Errore di sicurezza: Token non valido. Riprova.</p>';
    } else {
        // --- VALIDAZIONE CAMPI ---
        // Tipo
        if(isset($_POST['tipo']) && ($_POST['tipo'] === 'torta' || $_POST['tipo'] === 'pasticcino')){
            $tipo = $_POST['tipo'];
            $selTorta = ($tipo === 'torta') ? 'selected' : '';
            $selPasticcino = ($tipo === 'pasticcino') ? 'selected' : '';
        } else {
            $erroreTipo = '<p class="errore" role="alert">Seleziona un tipo valido</p>';
        }
        
        // Nome
        $nome = pulisciInput($_POST['nome']);
        if(strlen($nome) === 0){
            $erroreNome = '<p class="errore" role="alert">Inserire il nome</p>';
        } else if (strlen($nome) > 30){
            $erroreNome = '<p class="errore" role="alert">Il nome non può superare 30 caratteri</p>';
        }

        // Descrizione
        $descrizione = pulisciInput($_POST['descrizione']);
        if(strlen($descrizione) === 0){
            $erroreDescrizione = '<p class="errore" role="alert">Inserire la descrizione</p>';
        } else if (strlen($descrizione) > 255){
            $erroreDescrizione = '<p class="errore" role="alert">La descrizione non può superare 255 caratteri</p>';
        }

        // Prezzo
        $prezzo = $_POST['prezzo'];
        if(empty($prezzo)){
            $errorePrezzo = '<p class="errore" role="alert">Inserisci il prezzo</p>';
        } else if(!is_numeric($prezzo) || $prezzo < 0 || $prezzo > 99999999.99){
            $errorePrezzo = '<p class="errore" role="alert">Inserisci un prezzo valido</p>';
        }

        //immagine
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {

            $fileTmpPath = $_FILES['immagine']['tmp_name'];     //percorso temporaneo dove PHP mette il file appena caricato
            $fileName = $_FILES['immagine']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $fileSize = $_FILES['immagine']['size'];            //dimensione in byte del file caricato

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 1 * 1024 * 1024; //dimensione massima per le immagini caricate: 1 MB

            //controllo formato file
            if (!in_array($fileExtension, $allowedExtensions)) {
                $erroreImmagine = '<p class="errore" role="alert">Formato immagine non consentito. Usa JPG, PNG o WEBP.</p>';
            } else 
            //controllo dimensione file
            if ($fileSize > $maxSize) {
                $erroreImmagine = '<p class="errore" role="alert">L\'immagine non può superare ' . $maxSize/(1024*1024) . ' MB di dimensione.</p>';
            } else {
                $uploadDir = 'site/img/'; //cartella dove salvare le immagini
        
                if (!is_dir($uploadDir)) {  //se la cartella non esiste la crea
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = $nome . '.' . $fileExtension; //nomina il file in maniera univoca
                $destPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {  //sposta il file nella cartella definitiva
                    // salva solo il path relativo
                    $immagine = $newFileName;
                } else {
                    $erroreImmagine = '<p class="errore" role="alert">Errore durante il caricamento dell\'immagine.</p>';
                }
            }
        } else {
            $erroreImmagine = '<p class="errore" role="alert">Inserire l\'immagine</p>';
        }
   
        //testo alternativo
        $testoAlternativo = pulisciInput($_POST['testoAlternativo']);
        if(strlen($testoAlternativo) === 0){
            $erroreTestoAlternativo = '<p class="errore" role="alert">Inserire il testo alternativo all\'immagine</p>';
        } else if (strlen($testoAlternativo) > 255){
            $erroreTestoAlternativo = '<p class="errore" role="alert">Il testo alternativo non può superare 255 caratteri</p>';
        }

        //allergeni
        $allergeni = isset($_POST['allergeni']) && is_array($_POST['allergeni']) ? $_POST['allergeni'] : [];
        $allergeni = array_map('htmlspecialchars', $allergeni);
    }

    //INSERIMENTO VALORI NEL DATABASE solo se non ci sono errori in nessun campo
    if(empty($erroreTipo) && empty($erroreNome) && empty($erroreDescrizione) && empty($errorePrezzo) && empty($erroreImmagine) && empty($erroreTestoAlternativo)){

        $db = new DBAccess();
        $connessione = $db->openDBConnection();
        
        if(!$connessione){  //errore di connessione al database
            http_response_code(500);
            include __DIR__ . '/500.php';
            exit;
        } else {
            $lastItemId = $db->insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo);    //inserimento e recupero id ultimo item aggiunto  

            //INSERIMENTO DEGLI ALLERGENI (solo se l'item è stato inserito correttamente)
            if($lastItemId){  
                $successAllergeni = inserisciAllergeni($db, $lastItemId, $allergeni);
                $db->closeDBConnection();
                $messaggioConferma = '<div class="successo" role="status"><p>Prodotto inserito con successo!</p></div>';
                // Dopo inserimento riuscito, svuota i campi del form
                $tipo = '';
                $nome = '';
                $descrizione = '';
                $prezzo = '';
                $immagine = ''; 
                $testoAlternativo = '';
                $allergeni = [];
                $selTorta = '';
                $selPasticcino = '';
                } else {
                    $messaggioErrore = '<p class="errore" role="alert">Errore durante la scrittura nel database</p>';
            }
        }
    }
}

//GESTIONE CAMBIO STATO PRODOTTO (l'item passerà da attivo a inattivo e viceversa)
if (isset($_POST['cambiaStato']) && !empty($_POST['id_cambioStato'])){
    //controllo corrispondenza token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $messaggioErrore = '<p class="errore" role="alert">Errore di sicurezza: Token non valido. Riprova.</p>';
    } else {
        $idCambio = intval($_POST['id_cambioStato']);  //intval rimuove tutto ciò che non è numero, restituendo un valore intero pulito.
        $db = new DBAccess();
        $connessione = $db->openDBConnection();

        if ($connessione) {
            $result = $db->changeItemStateById($idCambio);
            $db->closeDBConnection();

            if ($result === 1){
                $messaggioConferma = '<div class="sr-only" role="status"><p>Prodotto attivato con successo!</p></div>';
            } elseif($result === 0) {
                 $messaggioConferma = '<div class="sr-only" role="status"><p>Prodotto disattivato con successo!</p></div>';
            } else {
                $messaggioErrore = '<p class="errore" role="alert">Errore durante la modifica dello stato del prodotto.</p>';
            }
        } else {
            $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database durante la modifica.</p>';
        }
    }
}

//TABELLA PRODOTTI: Viene stampata una tabella con tutti i prodotti salvati nel DB fino a quel momento
$db = new DBAccess();
$connessione = $db->openDBConnection();
if(!$connessione){  
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
} else {
    $items = $db->getAllItems();
    $db->closeDBConnection();
    if(empty($items)){
        $tabellaItems = '<p class="errore" role="alert">Non sono stati trovati prodotti nel database</p>';
    } else {    //se ho recuperato almeno un prodotto dal DB
        $tabellaItems = '<p id="descr" class="sr-only">Tabella contenente la lista di dolci registrati. Ogni riga descrive un dolce con numero 
                        identificativo, tipologia, nome, descrizione, prezzo e stato. L\'ultima colonna consente di vedere lo stato di un prodotto e di cambiarlo.
                        Solo i prodotti con stato attivo sono visionabili dalla clientela.</p>
                        <table aria-describedby="descr">
                            <caption>Tabella dei prodotti disponibili</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Numero</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Descrizione</th>
                                    <th scope="col">Prezzo (€)</th>
                                    <th scope="col">Stato</th>
                                </tr>
                            </thead>
                            <tbody>';
        foreach ($items as $item){
            $statoTesto = $item['attivo'] ? '<span aria-hidden="true">✔</span> Disponibile' : '<span aria-hidden="true">✖</span> Non Disponibile';

            $tabellaItems .= '<tr>' .
            '<th scope="row" data-label="ID">' . htmlspecialchars($item['id']) . '</th>' .
            '<td data-label="Tipo">' . htmlspecialchars($item['tipo']) . '</td>' .
            '<td data-label="Nome">' . htmlspecialchars($item['nome']) . '</td>' .
            '<td data-label="Descrizione">' . htmlspecialchars($item['descrizione']) . '</td>' .
            '<td data-label="Prezzo">
                <span aria-hidden="true">€</span>
                <span class="sr-only">euro</span>' .
                number_format($item['prezzo'], 2, ',', '.') . 
            '</td>' .
            '<td data-label="Stato">
                <form method="post" onsubmit="return confirm(\'Vuoi davvero cambiare lo stato di questo prodotto?\');">
                    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">
                    <input type="hidden" name="id_cambioStato" value="' . htmlspecialchars($item['id']) . '">
                    <button type="submit" name="cambiaStato" aria-label="Cambia stato ' . htmlspecialchars($item['nome']) . '">'
                        . $statoTesto .
                    '</button>
                </form>
            </td>' .
            '</tr>';
        }
        $tabellaItems .= '</tbody> </table>';
    }
}
// SOSTITUZIONI NEL TEMPLATE HTML
$paginaHTML = str_replace('[csrf_token]', $token, $paginaHTML);

$paginaHTML = str_replace('[valoreNome]', htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreDescrizione]', htmlspecialchars($descrizione, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valorePrezzo]', htmlspecialchars($prezzo, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreTestoAlternativo]', htmlspecialchars($testoAlternativo, ENT_QUOTES, 'UTF-8'), $paginaHTML);

$paginaHTML = str_replace('[checkedGlutine]', in_array('Glutine', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedUova]', in_array('Uova', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedLatte]', in_array('Latte', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedFrutta]', in_array('Frutta a guscio', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedArachidi]', in_array('Arachidi', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedSoia]', in_array('Soia', $allergeni) ? 'checked' : '', $paginaHTML);
$paginaHTML = str_replace('[checkedSesamo]', in_array('Sesamo', $allergeni) ? 'checked' : '', $paginaHTML);

$selTorta = ($tipo === 'torta') ? 'selected' : '';
$selPasticcino = ($tipo === 'pasticcino') ? 'selected' : '';
$paginaHTML = str_replace('[selTorta]', $selTorta, $paginaHTML);
$paginaHTML = str_replace('[selPasticcino]', $selPasticcino, $paginaHTML);

// Sostituzioni Messaggi Errore
$paginaHTML = str_replace('[messaggioErroreTipo]', $erroreTipo, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreNome]', $erroreNome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDescrizione]', $erroreDescrizione, $paginaHTML);
$paginaHTML = str_replace('[messaggioErrorePrezzo]', $errorePrezzo, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreImmagine]', $erroreImmagine, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreTestoAlternativo]', $erroreTestoAlternativo, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $messaggioConferma, $paginaHTML); 

$paginaHTML = str_replace('[tabellaProdotti]', $tabellaItems, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>