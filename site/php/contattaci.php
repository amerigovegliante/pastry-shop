<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if(session_status() === PHP_SESSION_NONE){
    session_start();                    
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

require_once __DIR__ . "/dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/contattaci.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$email ='';
$domanda ='';
$erroreEmail ='';
$erroreDomanda ='';       
$erroreDB ='';          
$confermaInvio ='';     
$ip ='';                
$limite = 3;            

if (isset($_SESSION['success_msg'])) {
    $confermaInvio = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); 
}

if(isset($_SESSION['email'])){      
    $email = $_SESSION['email'];
}

if(isset($_POST['submit'])){

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erroreDB = '<p class="errore" role="alert">Errore di sicurezza. Ricarica la pagina.</p>';
    } else {
        $email = trim($_POST['email'] ?? '');
        $domanda = trim($_POST['domanda'] ?? '');

        if (empty($email)) {
            $erroreEmail = '<p class="errore" role="alert">Inserire una email</p>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erroreEmail = '<p class="errore" role="alert">Formato email non valido</p>';
        }

        if (empty($domanda)) {
            $erroreDomanda = '<p class="errore" role="alert">Inserire almeno una domanda</p>';
        }
        
        $ip = $_SERVER['REMOTE_ADDR']; 
        
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
                    $_SESSION['success_msg'] = '<p class="successo" role="alert">Domanda inviata con successo!</p>';
                    $db->closeDBConnection();
                    
                    header("Location: " . BASE_URL . "contattaci");
                    exit;
                }
            }
            $db->closeDBConnection();
        }
    }
}

$paginaHTML = str_replace('[valoreEmail]', htmlspecialchars($email, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreDomanda]', htmlspecialchars($domanda, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreEmail]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDomanda]', $erroreDomanda, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $confermaInvio, $paginaHTML); 

$inputToken = '<input type="hidden" name="csrf_token" value="' . $token . '">';
$paginaHTML = str_replace('[csrfToken]', $inputToken, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>