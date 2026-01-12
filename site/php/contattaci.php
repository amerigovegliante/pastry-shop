<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/contattaci.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere contattaci.html");
}

//DICHIARAZIONE VARIABILI
$email ='';
$domanda ='';
$erroreEmail ='';
$erroreDomanda ='';       
$erroreDB ='';               //errore in accesso o inserimento dati nel database
$confermaInvio ='';    //inserimento dei dati avvenuto con successo

//Se è stato fatto il login, recupera la mail dai dati della sessione
if(session_status() === PHP_SESSION_NONE){
    session_start();                    
}
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
if(isset($email)) {
    $paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
}

//funzione per pulire l'input del form per evitare errori o iniezione di codice sql malevola
function pulisciInput($value){
 	$value = trim($value);				
  	$value = strip_tags($value); 		
	$value = htmlentities($value);		
  	return $value;
}

if(isset($_POST['submit'])){
    
    //recupero dati per le variabili (puliti)
    $email = pulisciInput($_POST['email']);
    $domanda = pulisciInput($_POST['domanda']);

    //controllo email
    if (strlen($email) === 0) {
        $erroreEmail = '<p class="errore">Inserire una email</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroreEmail = '<p class="errore">Formato email non valido</p>';
    }

    //controllo domanda
    if (strlen($domanda) === 0) {
        $erroreDomanda = '<p class="errore">Inserire almeno una domanda</p>';
    }

    //INSERIMENTO VALORI NEL DATABASE (solo se tutti i campi sono corretti)
    if (empty($erroreEmail) && empty($erroreDomanda)){
        $db = new DBAccess();
        $connessione = $db->openDBConnection(); //tento la connessione
        if(!$connessione){  
            $erroreDB = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
        } else {
            $success = $db->insertNewDomanda($email, $domanda);
            $db->closeDBConnection();   //chiudo la connessione
            if(!$success){  
                $erroreDB = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
            } else {
                $confermaInvio = '<p class="successo">Domanda inviata con successo!</p>';
            }
        }
    }
}

//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti dall'utente, evitando frustrazione
$paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
$paginaHTML = str_replace('[valoreDomanda]', $domanda, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreEmail]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDomanda]', $erroreDomanda, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $confermaInvio, $paginaHTML); 
echo $paginaHTML;
?>
