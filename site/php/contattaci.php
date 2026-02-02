<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once __DIR__ . "/dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/contattaci.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

//DICHIARAZIONE VARIABILI
$email ='';
$domanda ='';
$erroreEmail ='';
$erroreDomanda ='';       
$erroreDB ='';          //errore in accesso o inserimento dati nel database
$confermaInvio ='';     //inserimento dei dati avvenuto con successo
$ip ='';                //ip utente 
$limite = 3;            //numero di domande che si possono inviare nello stesso giorno dallo stesso ip
// Controllo se c'è un messaggio di successo in sessione (Pattern PRG)
if (isset($_SESSION['success_msg'])) {
    $confermaInvio = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); // Lo rimuovo per non mostrarlo di nuovo al prossimo reload
}

//Se è stato fatto il login, recupera la mail dai dati della sessione
if(session_status() === PHP_SESSION_NONE){
    session_start();                    
}
if(isset($_SESSION['email'])){      
    $email = $_SESSION['email'];
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
    
    //recupero IP utente 
    $ip = $_SERVER['REMOTE_ADDR']; 
    
    //INSERIMENTO VALORI NEL DATABASE (solo se tutti i campi sono corretti)
    if (empty($erroreEmail) && empty($erroreDomanda)){
        $db = new DBAccess();
        $connessione = $db->openDBConnection(); //tento la connessione
        if(!$connessione){  
            $erroreDB = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
        } else if(($db->numDomandeIP($ip)) >= $limite || ($db->numDomandeEmail($email) >= $limite)){
                $erroreDB = '<p class="errore">Ha già inviato il numero massimo di domande per oggi: ' . $limite . '</p>';
        } else {
            $success = $db->insertNewDomanda($email, $domanda, $ip);
            if(!$success){  
                $erroreDB = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
            } else {
                $_SESSION['success_msg'] = '<p class="successo">Domanda inviata con successo!</p>';
                $db->closeDBConnection();
                
                // reindirizzo alla pagina stessa (BASE_URL è definita in index.php)
                header("Location: " . BASE_URL . "contattaci");
                exit;
            }
        }
        $db->closeDBConnection();   //chiudo la connessione nel caso di errore
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
