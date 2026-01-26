<?php
// Avvio sessione solo se non già avviata
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// 1. SICUREZZA: Controllo che l'utente sia loggato E sia ADMIN
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login");
    exit;
}

// 2. SICUREZZA: Generazione CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$paginaHTML = file_get_contents( __DIR__ .'/../html/aggiungiProdotto.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere il file template html/aggiungiProdotto.html");
}

// DICHIARAZIONE VARIABILI
$tipo = '';
$nome = '';
$descrizione = '';
$prezzo = '';
$immagineDB = ''; // Variabile per il database
$testoAlternativo = '';

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
    $value = htmlentities($value);      
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

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $erroreImmagine = '<p class="errore" role="alert">Formato immagine non consentito. Usa JPG, PNG o WEBP.</p>';
            } else {
                $uploadDir = '../img/'; //cartella dove salvare le immagini
        
                if (!is_dir($uploadDir)) {  //sela cartella non esiste la crea
                    mkdir($uploadDir, 0755, true);
                }
                $newFileName = uniqid('img_', true) . '.' . $fileExtension; //nomina il file in maniera univoca
                $destPath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {  //sposta il file nella cartella definitiva
                    // salva solo il path relativo
                    $immagine = '../img/' . $newFileName;
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
    }

    //INSERIMENTO VALORI NEL DATABASE solo se non ci sono errori in nessun campo
    if(empty($erroreTipo) && empty($erroreNome) && empty($erroreDescrizione) && empty($errorePrezzo) && empty($erroreImmagine) && empty($erroreTestoAlternativo)){

        $db = new DBAccess();
        $connessione = $db->openDBConnection();
        
        if(!$connessione){  
            $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
        } else {
            $lastItemId = $db->insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo);    //inserimento e recupero id ultimo item aggiunto  

            if(!$lastItemId){  
                $messaggioErrore = '<p class="errore" role="alert">Errore durante la scrittura nel database</p>';
            } else {
                $messaggioConferma = '<div class="successo" role="status">
                        <p>Prodotto inserito con successo!</p>
                        </div>';
            }

            //Inserimento allergeni
            $allergeni = $_POST['allergeni'];     //recupero array degli allergeni selezionati
            $successAllergeni = inserisciAllergeni($db, $lastItemId, $allergeni);
            $db->closeDBConnection();
            if(!$successAllergeni){
                $messaggioErrore = '<p class="errore" role="alert">Errore durante la scrittura degli allergeni</p>';
                $messaggioConferma = '';
            }
        }
    }
}

//GESTIONE ELIMINAZIONE PRODOTTO (l'item verrà segnato come inattivo e non cancellato dal database)
if (isset($_POST['delete']) && !empty($_POST['delete_id'])){
    $idDaCancellare = intval($_POST['delete_id']);  //intval rimuove tutto ciò che non è numero, restituendo un valore intero pulito.
    $db = new DBAccess();
    $connessione = $db->openDBConnection();

    if ($connessione) {
        $success = $db->deactivateItemById($idDaCancellare);
        $db->closeDBConnection();

        if ($success === true){
            $messaggioConferma = '<div class="successo" role="status"><p>Prodotto eliminato con successo!</p></div>';
        } else {
            $messaggioErrore = '<p class="errore" role="alert">Errore durante l\'eliminazione del prodotto.</p>';
        }
    } else {
        $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database durante l\'eliminazione.</p>';
    }
}

//TABELLA PRODOTTI: Viene stampata una tabella con tutti i prodotti salvati nel DB fino a quel momento
$db = new DBAccess();
$connessione = $db->openDBConnection();
if(!$connessione){  
    $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
} else {
    $items = $db->getActiveItems();
    $db->closeDBConnection();
    if(empty($items)){
        $tabellaItems = '<p class="errore" role="alert">Non sono stati trovati prodotti nel database</p>';
    } else {    //se ho recuperato almeno un prodotto dal DB
        $tabellaItems = '<p id="descr" class="sr-only">Tabella contenente la lista di dolci registrati. Ogni riga descrive un dolce con numero 
                        identificativo, tipologia, nome, descrizione e prezzo. L\'ultima colonna consente di eliminare il dolce corrispondente dal database.</p>
                        <table aria-describedby="descr">
                            <caption>Tabella dei prodotti disponibili</caption>
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Tipo</th>
                                    <th scope="col">Nome</th>
                                    <th scope="col">Descrizione</th>
                                    <th scope="col">Prezzo (€)</th>
                                    <th scope="col">Elimina</th>
                                </tr>
                            </thead>
                            <tbody>';
        foreach ($items as $item){
            $tabellaItems .= '<tr>' .
            '<td>' . htmlspecialchars($item['id']) . '</td>' .
            '<td>' . htmlspecialchars($item['tipo']) . '</td>' .
            '<td>' . htmlspecialchars($item['nome']) . '</td>' .
            '<td>' . htmlspecialchars($item['descrizione']) . '</td>' .
            '<td>' . number_format($item['prezzo'], 2, ',', '.') . '</td>' .
            '<td>
                <form method="post" onsubmit="return confirm(\'Vuoi davvero eliminare questo prodotto?\');">
                    <input type="hidden" name="delete_id" value="' . htmlspecialchars($item['id']) . '">
                    <button type="submit" name="delete" class="pulsanteCancella" aria-label="Elimina prodotto ' . htmlspecialchars($item['nome']) . '">
                        &#x1F5D1;
                    </button>
                </form>
            </td>' .
            '</tr>';
        }
        $tabellaItems .= '</tbody> </table>';
    }
}
//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[valoreNome]', $nome, $paginaHTML);
$paginaHTML = str_replace('[valoreDescrizione]', $descrizione, $paginaHTML);
$paginaHTML = str_replace('[valorePrezzo]', $prezzo, $paginaHTML);
$paginaHTML = str_replace('[valoreTestoAlternativo]', $testoAlternativo, $paginaHTML);

// 3. Gestione Select "Tipo" (Per mantenerla selezionata in caso di errore)
$selTorta = ($tipo === 'torta') ? 'selected' : '';
$selPasticcino = ($tipo === 'pasticcino') ? 'selected' : '';
$paginaHTML = str_replace('[selTorta]', $selTorta, $paginaHTML);
$paginaHTML = str_replace('[selPasticcino]', $selPasticcino, $paginaHTML);

// 4. Messaggi Errore
$paginaHTML = str_replace('[messaggioErroreTipo]', $erroreTipo, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreNome]', $erroreNome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDescrizione]', $erroreDescrizione, $paginaHTML);
$paginaHTML = str_replace('[messaggioErrorePrezzo]', $errorePrezzo, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreImmagine]', $erroreImmagine, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreTestoAlternativo]', $erroreTestoAlternativo, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $messaggioConferma, $paginaHTML); 

$paginaHTML = str_replace('[tabellaProdotti]', $tabellaItems, $paginaHTML);
echo $paginaHTML;
?>