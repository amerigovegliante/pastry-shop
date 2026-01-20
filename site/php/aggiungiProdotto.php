<?php
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// 1. SICUREZZA: Controllo che l'utente sia loggato E sia ADMIN
if (!isset($_SESSION['ruolo']) || $_SESSION['ruolo'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// 2. SICUREZZA: Generazione CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$paginaHTML = file_get_contents('../html/aggiungiProdotto.html');
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

$messaggioErrore = '';       
$messaggioConferma = '';     

function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    $value = htmlentities($value);      
    return $value;
}

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

        // Immagine
        if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['immagine']['tmp_name'];     
            $fileName = $_FILES['immagine']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($fileExtension, $allowedExtensions)) {
                $erroreImmagine = '<p class="errore" role="alert">Formato immagine non consentito. Usa JPG, PNG o WEBP.</p>';
            } else {
                $uploadDir = '../img/uploads/'; 
        
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $newFileName = uniqid('img_', true) . '.' . $fileExtension;
                $destPath = $uploadDir . $newFileName; // Dove salvo fisicamente il file (../img/uploads/...)
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // FIX: Nel DB salvo solo 'uploads/nomefile.jpg'
                    // Perché in dettagli.php facciamo "../img/" . $immagineDB
                    $immagineDB = 'uploads/' . $newFileName; 
                } else {
                    $erroreImmagine = '<p class="errore" role="alert">Errore durante il caricamento dell\'immagine.</p>';
                }
            }
        } else {
            $erroreImmagine = '<p class="errore" role="alert">Inserire l\'immagine</p>';
        }
    
        // Testo alternativo
        $testoAlternativo = pulisciInput($_POST['testoAlternativo']);
        if(strlen($testoAlternativo) === 0){
            $erroreTestoAlternativo = '<p class="errore" role="alert">Inserire il testo alternativo</p>';
        }

        // INSERIMENTO DB
        if(empty($erroreTipo) && empty($erroreNome) && empty($erroreDescrizione) && empty($errorePrezzo) && empty($erroreImmagine) && empty($erroreTestoAlternativo)){

            $db = new DBAccess();
            $connessione = $db->openDBConnection();
            
            if(!$connessione){  
                $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
            } else {
                // Attenzione: insertNewItem accetta (tipo, nome, descrizione, prezzo, immagine, testo_alternativo)
                $success = $db->insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagineDB, $testoAlternativo);
                $db->closeDBConnection();

                if(!$success){  
                    $messaggioErrore = '<p class="errore" role="alert">Errore durante la scrittura nel database.</p>';
                } else {
                    $messaggioConferma = '<div class="successo" role="status"><p>Prodotto inserito con successo!</p></div>';
                    // Reset campi form dopo successo
                    $tipo = ''; $nome = ''; $descrizione = ''; $prezzo = ''; $testoAlternativo = '';
                }
            }
        }
    }
}

// INIEZIONE DATI NEL TEMPLATE

// 1. Token CSRF
$inputCsrf = '<input type="hidden" name="csrf_token" value="' . $token . '">';
$paginaHTML = str_replace('[csrfToken]', $inputCsrf, $paginaHTML);

// 2. Valori Form (Retention)
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

echo $paginaHTML;
?>