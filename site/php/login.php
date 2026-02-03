<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// AVVIO SESSIONE
if (session_status() === PHP_SESSION_NONE){
    session_start();                    
}

if (isset($_SESSION['ruolo'])) {
    header("Location: area-personale");
    exit;
}

// TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];
  
require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/login.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

// DICHIARAZIONE VARIABILI
$email ='';
$password ='';
$erroreEmail ='';       
$errorePassword ='';    
$erroreLogin ='';       
$erroreDB ='';          
$utentiSpeciali = ["admin", "user"];   

// FUNZIONE PULIZIA INPUT
function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    return $value;
}

if(isset($_POST['submit'])){
    
    // VERIFICA CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erroreLogin = '<div class="errore" role="alert"><p>Errore di sicurezza (Token scaduto). Ricarica la pagina.</p></div>';
    } else {
        $email = pulisciInput($_POST['email']);
        $password = trim($_POST['password']);

        // Controllo email
        if (strlen($email) === 0){
            $erroreEmail = '<p class="errore" role="alert">Inserire l\'email</p>';
        } else if (!in_array($email, $utentiSpeciali) && !filter_var($email, FILTER_VALIDATE_EMAIL)){ 
            $erroreEmail = '<p class="errore" role="alert">Formato email non valido</p>';
        }

        // Controllo password
        if (strlen($password) === 0){
            $errorePassword = '<p class="errore" role="alert">Inserire la password</p>';
        }

        if (empty($erroreEmail) && empty($errorePassword)){
            $db = new DBAccess();
            $connessione = $db->openDBConnection(); 
            if(!$connessione){  
                http_response_code(500); 
                include __DIR__ . '/500.php';
                exit;
            } else {
                if(!$db->correctLogin($email, $password)){        
                    $erroreLogin = '<div class="errore" role="alert"><p>Accesso non riuscito</p>
                                    <p>Controlla email e password</p></div>';
                    $db->closeDBConnection();
                } else {
                    // LOGIN VALIDO
                    session_regenerate_id(true);

                    $_SESSION['email'] = $email;
                    $_SESSION['nome'] = $db->getNome($email);
                    $_SESSION['cognome'] = $db->getCognome($email);
                    $_SESSION['ruolo'] = $db->getRuolo($email); 

                    $db->closeDBConnection();

                    header("Location: area-personale");
                    exit;
                }
            }
        }
    }
}

// ESCAPE OUTPUT
$paginaHTML = str_replace('[valoreEmail]', htmlspecialchars($email, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valorePassword]', '', $paginaHTML); 

$paginaHTML = str_replace('[messaggioEmailMancante]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioPasswordMancante]', $errorePassword, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreLogin]', $erroreLogin, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 

// Iniezione CSRF
$csrfField = "<input type='hidden' name='csrf_token' value='$token'>";
$paginaHTML = str_replace('[csrfToken]', $csrfField, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>