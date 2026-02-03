<?php
// 1. AVVIO SESSIONE SUBITO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SE L'UTENTE È GIÀ LOGGATO
if (isset($_SESSION['ruolo'])) {
    header("Location: area-personale");
    exit;
}

error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

// TOKEN CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['csrf_token'];

$paginaHTML = file_get_contents( __DIR__ .'/../html/registrazione.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

// DICHIARAZIONE VARIABILI
$email = '';
$nome = '';
$cognome = '';
$telefono = '';
$password = '';

$erroreEmail = '';
$erroreNome = '';
$erroreCognome = '';
$erroreTelefono = '';
$errorePassword = '';
$ArrayErroriPassword = [];
$messaggioConferma = ''; 
$messaggioErrore = '';       

// funzione per pulire l'input
function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    return $value;
}

// formatta il testo rendendo maiuscole solo le iniziali
function formattaNomeCognome($value){
    return ucwords(strtolower($value));                
}

if(isset($_POST['submit'])){
    
    // 1. VERIFICA CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $messaggioErrore = '<p class="errore" role="alert">Errore sessione scaduta. Ricarica la pagina.</p>';
    } else {
        // recupero parametri e pulizia input
        $nome = pulisciInput($_POST['nome']);
        $cognome = pulisciInput($_POST['cognome']);
        $email = pulisciInput($_POST['email']);
        
        $telefonoRaw = str_replace(' ', '', $_POST['telefono']);
        $telefono = pulisciInput($telefonoRaw);
        
        $password = trim($_POST['password']);

        // controllo nome
        if (strlen($nome) === 0) {
            $erroreNome = '<p class="errore" role="alert">Inserire il nome</p>';
        } else if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $nome)) {
            $erroreNome = '<p class="errore" role="alert">Il nome può contenere solo lettere e spazi</p>';
        }

        // controllo cognome
        if (strlen($cognome) === 0) {
            $erroreCognome = '<p class="errore" role="alert">Inserire il cognome</p>';
        } else if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $cognome)) {
            $erroreCognome = '<p class="errore" role="alert">Il cognome può contenere solo lettere e spazi</p>';
        }

        // controllo email
        if (strlen($email) === 0) {
            $erroreEmail = '<p class="errore" role="alert">Inserire una email</p>';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erroreEmail = '<p class="errore" role="alert">Formato email non valido</p>';
        }

        // controllo telefono
        if (strlen($telefono) === 0) {
            $erroreTelefono = '<p class="errore" role="alert">Inserire il numero di telefono</p>';
        } elseif (!preg_match("/^[0-9]{10}$/", $telefono)) {
            $erroreTelefono = '<p class="errore" role="alert">Il telefono deve essere composto da 10 cifre senza spazi.</p>';
        }

        // controllo password
        if (strlen($password) < 8) {
            $ArrayErroriPassword[] = '<li>La password deve essere lunga almeno 8 caratteri.</li>';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $ArrayErroriPassword[] = '<li>La password deve contenere almeno una lettera maiuscola.</li>';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $ArrayErroriPassword[] = '<li>La password deve contenere almeno una lettera minuscola.</li>';
        }
        if (!preg_match('/\d/', $password)) {
            $ArrayErroriPassword[] = '<li>La password deve contenere almeno un numero.</li>';
        }
        if (!preg_match('/[\W_]/', $password)) {
            $ArrayErroriPassword[] = '<li> La password deve contenere almeno un simbolo.</li>';
        }

        if(!empty($ArrayErroriPassword)){
            $errorePassword .= '<div class="errore" role="alert"><ul>';
            foreach($ArrayErroriPassword as $error){
                $errorePassword .= $error;
            }
            $errorePassword .= '</ul></div>';
        }

        // inserimento valori nel database
        if (empty($erroreNome) && empty($erroreCognome) && empty($erroreEmail) && empty($erroreTelefono) && empty($errorePassword)){
            
            $nome = formattaNomeCognome($nome);
            $cognome = formattaNomeCognome($cognome);

            $db = new DBAccess();
            $connessione = $db->openDBConnection();
            
            if(!$connessione){  
                http_response_code(500);
                include __DIR__ . '/500.php';
                exit;
            } else {
                if($db->emailExists($email)){        
                    $erroreEmail = '<div class="errore" role="alert"><p>L\'indirizzo email è già registrato.</p>
                                    <p>Riprova con una email differente o effettua il login.</p></div>';
                } else {
                    $success = $db->insertNewPersona($email, $nome, $cognome, $telefono, $password);
                    $db->closeDBConnection();
                    
                    if(!$success){  
                        $messaggioErrore = '<p class="errore" role="alert">Errore durante la registrazione. Riprova più tardi.</p>';
                    } else {
                        session_regenerate_id(true); 
                        
                        $_SESSION['email'] = $email;
                        $_SESSION['nome'] = $nome;
                        $_SESSION['cognome'] = $cognome;
                        $_SESSION['ruolo'] = 'user'; 

                        header("Location: area-personale");
                        exit;
                    }
                }
            }
        }
    }
}

// Sostituzioni nel template
$paginaHTML = str_replace('[valoreNome]', htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreCognome]', htmlspecialchars($cognome, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreEmail]', htmlspecialchars($email, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valoreTelefono]', htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[valorePassword]', '', $paginaHTML); 

$paginaHTML = str_replace('[messaggioErroreNome]', $erroreNome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreCognome]', $erroreCognome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreEmail]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreTelefono]', $erroreTelefono, $paginaHTML);
$paginaHTML = str_replace('[messaggioErrorePassword]', $errorePassword, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML);
$paginaHTML = str_replace('[messaggioConferma]', $messaggioConferma, $paginaHTML); 

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