<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

//AVVIO SESSIONE
if (session_status() === PHP_SESSION_NONE){
    session_start();                    
}

if (isset($_SESSION['ruolo'])) {
    header("Location: areaPersonale.php");
    exit;
}
  
require_once "dbConnection.php";

$paginaHTML = file_get_contents('../html/login.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere login.html");
}

//DICHIARAZIONE VARIABILI
$email ='';
$password ='';
$erroreEmail ='';       
$errorePassword ='';    
$erroreLogin ='';       
$erroreDB ='';          
$utentiSpeciali = ["admin", "user"];   

//funzione per pulire l'input del form per evitare errori o iniezione di codice sql malevola
function pulisciInput($value){
    $value = trim($value);              
    $value = strip_tags($value);        
    $value = htmlentities($value);      
    return $value;
}

if(isset($_POST['submit'])){
    
    $email = pulisciInput($_POST['email']);
    $password = trim($_POST['password']);

    //controllo email
    if (strlen($email) === 0){
        $erroreEmail = '<p class="errore" role="alert">Inserire l\'email</p>';
    } else if (!in_array($email, $utentiSpeciali) && !filter_var($email, FILTER_VALIDATE_EMAIL)){  //fa passare "admin" e "user" come mail
        $erroreEmail = '<p class="errore" role="alert">Formato email non valido</p>';
    }

    //controllo password
    if (strlen($password) === 0){
        $errorePassword = '<p class="errore" role="alert">Inserire la password</p>';
    }

    if (empty($erroreEmail) && empty($errorePassword)){
        $db = new DBAccess();
        $connessione = $db->openDBConnection(); 
        if(!$connessione){  
            $erroreDB = '<p class="errore" role="alert">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
        } else {
            if(!$db->correctLogin($email, $password)){        
                $erroreLogin = '<div class="errore" role="alert"><p>Accesso non riuscito</p>
                                <p>Controlla email e password</p></div>';
                $db->closeDBConnection();
            } else {
                //LOGIN VALIDO
                    session_regenerate_id(true);

                    $_SESSION['email'] = $email;
                    $_SESSION['nome'] = $db->getNome($email) ?: '';
                    $_SESSION['cognome'] = $db->getCognome($email) ?: '';
                    
                    $ruolo = $db->getRuolo($email);
                    $_SESSION['ruolo'] = $ruolo ?: 'user'; //se $ruolo ha un valore vero/non vuoto usa $ruolo; altrimenti usa 'user'

                    $db->closeDBConnection();

                    header("Location: areaPersonale.php");
                    exit;
            }
        }
    }
}

$paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
$paginaHTML = str_replace('[valorePassword]', '', $paginaHTML); 

$paginaHTML = str_replace('[messaggioEmailMancante]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioPasswordMancante]', $errorePassword, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreLogin]', $erroreLogin, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 

echo $paginaHTML;
?>