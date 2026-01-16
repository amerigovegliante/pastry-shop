<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents('../html/aggiungiProdotto.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere il file template html/aggiungiProdotto.html");
}

//DICHIARAZIONE VARIABILI
$tipo = '';
$nome = '';
$descrizione = '';
$prezzo = '';
$immagine = '';
$testoAlternativo = '';

$erroreTipo = '';
$erroreNome = '';
$erroreDescrizione = '';
$errorePrezzo = '';
$erroreImmagine = '';
$erroreTestoAlternativo = '';

$messaggioErrore = '';       //per errore generico o di connessione con il DB
$messaggioConferma = '';     //inserimento dei dati avvenuto con successo

//funzione per pulire l'input del form per evitare iniezioni di codice e XSS
function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    $value = htmlentities($value);      
    return $value;
}

if(isset($_POST['submit'])){
    
    //tipo
    if(isset($_POST['tipo']) && ($_POST['tipo'] === 'torta' || $_POST['tipo'] === 'pasticcino')){
        $tipo = $_POST['tipo'];
    } else {
        $erroreTipo = '<p class="errore" role="alert">Seleziona un tipo valido</p>';
    }
    
    //nome
    $nome = pulisciInput($_POST['nome']);
    if(strlen($nome) === 0){
        $erroreNome = '<p class="errore" role="alert">Inserire il nome</p>';
    } else if (strlen($nome) > 30){
        $erroreNome = '<p class="errore" role="alert">Il nome non può superare 30 caratteri</p>';
    }

    //descrizione
    $descrizione = pulisciInput($_POST['descrizione']);
    if(strlen($descrizione) === 0){
        $erroreDescrizione = '<p class="errore" role="alert">Inserire la descrizione</p>';
    } else if (strlen($descrizione) > 255){
        $erroreDescrizione = '<p class="errore" role="alert">La descrizione non può superare 255 caratteri</p>';
    }

    //prezzo
    $prezzo = $_POST['prezzo'];
    if(empty($prezzo)){
        $errorePrezzo = '<p class="errore" role="alert"> Inserisci il prezzo</p>';
    } else if(!is_numeric($prezzo) || $prezzo < 0 || $prezzo > 99999999.99){
        $errorePrezzo = '<p class="errore" role="alert"> Inserisci un prezzo valido tra 0 e 99999999.99</p>';
    }

   // immagine
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {

        $fileTmpPath = $_FILES['immagine']['tmp_name'];     //percorso temporaneo dove PHP mette il file appena caricato
        $fileName = $_FILES['immagine']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $erroreImmagine = '<p class="errore" role="alert">Formato immagine non consentito. Usa JPG, PNG o WEBP.</p>';
        } else {
            $uploadDir = '../img/uploads/'; //cartella dove salvare le immagini
    
            if (!is_dir($uploadDir)) {  //sela cartella non esiste la crea
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = uniqid('img_', true) . '.' . $fileExtension; //nomina il file in maniera univoca
            $destPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpPath, $destPath)) {  //sposta il file nella cartella definitiva
                // salva solo il path relativo
                $immagine = '../img/uploads/' . $newFileName;
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
    
    //inserimento valori nel database solo se non ci sono errori in nessun campo
    if(empty($erroreTipo) && empty($erroreNome) && empty($erroreDescrizione) && empty($errorePrezzo) && empty($erroreImmagine) && empty($erroreTestoAlternativo)){

        $db = new DBAccess();
        $connessione = $db->openDBConnection();
        
        if(!$connessione){  
            $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
        } else {
            $success = $db->insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo);
            $db->closeDBConnection();

            if(!$success){  
                $messaggioErrore = '<p class="errore" role="alert">Errore durante la scrittura nel database</p>';
            } else {
                $messaggioConferma = '<div class="successo" role="status">
                        <p>Prodotto inserito con successo!</p>
                        </div>';
            }
        }
    }
}
//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[valoreNome]', $nome, $paginaHTML);
$paginaHTML = str_replace('[valoreDescrizione]', $descrizione, $paginaHTML);
$paginaHTML = str_replace('[valorePrezzo]', $prezzo, $paginaHTML);
$paginaHTML = str_replace('[valoreTestoAlternativo]', $testoAlternativo, $paginaHTML);

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