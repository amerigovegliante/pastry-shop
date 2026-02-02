<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// 1. Session start deve essere la PRIMA cosa logica
if(session_status() === PHP_SESSION_NONE){
    session_start();                    
}

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
$erroreDB ='';          
$confermaInvio ='';     
$ip ='';                
$limite = 3;            

// 2. Controllo messaggi Flash (ORA FUNZIONA perché la sessione è avviata)
if (isset($_SESSION['success_msg'])) {
    $confermaInvio = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); 
}

// Recupero mail utente loggato
if(isset($_SESSION['email'])){      
    $email = $_SESSION['email'];
}

// Funzione pulizia input
function pulisciInput($value){
 	$value = trim($value);				
  	$value = strip_tags($value); 		
	$value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');		
  	return $value;
}

if(isset($_POST['submit'])){
    
    //recupero dati (puliti)
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $domanda = pulisciInput($_POST['domanda']);

    // Validazione Email
    if (empty($email)) {
        $erroreEmail = '<p class="errore" role="alert">Inserire una email</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroreEmail = '<p class="errore" role="alert">Formato email non valido</p>';
    }

    // Validazione Domanda
    if (empty($domanda)) {
        $erroreDomanda = '<p class="errore" role="alert">Inserire almeno una domanda</p>';
    }
    
    $ip = $_SERVER['REMOTE_ADDR']; 
    
    // DB Actions
    if (empty($erroreEmail) && empty($erroreDomanda)){
        $db = new DBAccess();
        $connessione = $db->openDBConnection();
        
        if(!$connessione){  
            $erroreDB = '<p class="errore" role="alert">Problema di connessione. Riprova più tardi.</p>';
        } else if(($db->numDomandeIP($ip)) >= $limite || ($db->numDomandeEmail($email) >= $limite)){
            $erroreDB = '<p class="errore" role="alert">Limite messaggi giornalieri raggiunto (' . $limite . ').</p>';
        } else {
            $success = $db->insertNewDomanda($email, $domanda, $ip);
            if(!$success){  
                $erroreDB = '<p class="errore" role="alert">Errore invio messaggio. Riprova più tardi.</p>';
            } else {
                // Successo: Salvataggio in sessione
                $_SESSION['success_msg'] = '<p class="successo" role="alert">Domanda inviata con successo!</p>';
                $db->closeDBConnection();
                
                // Redirect PRG
                header("Location: " . BASE_URL . "contattaci");
                exit;
            }
        }
        $db->closeDBConnection();
    }
}

// Sostituzione placeholder
$paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
$paginaHTML = str_replace('[valoreDomanda]', $domanda, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreEmail]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDomanda]', $erroreDomanda, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $confermaInvio, $paginaHTML); 

echo $paginaHTML;
?>