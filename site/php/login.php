<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

//AVVIO SESSIONE
if (session_status() === PHP_SESSION_NONE){
    session_start();                    
}
/*  DA DECCOMMENTARE
//se l'utente è già loggato, reindirizzalo subito all’area personale
if (isset($_SESSION['email'])) {
    header("Location: areaPersonale.php");
    exit;
}
*/   
require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/login.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere login.html");
}

//DICHIARAZIONE VARIABILI
$email ='';
$password ='';
$erroreEmail ='';       //se mancante o con formato errato
$errorePassword ='';    //se mancante
$erroreLogin ='';       //per errore di login nel nome utente e/o password
$erroreDB ='';          //per errore di connessione con il DB

//funzione per pulire l'input del form per evitare errori o iniezione di codice sql malevola
function pulisciInput($value){
 	$value = trim($value);				
  	$value = strip_tags($value); 		
	$value = htmlentities($value);		
  	return $value;
}

if(isset($_POST['submit'])){
    
    //recupero parametri e pulizia input
    $email = pulisciInput($_POST['email']);
    $password = pulisciInput($_POST['password']);

    //controllo email
    if (strlen($email) === 0){
        $erroreEmail = '<p class="errore">Inserire l\'email</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erroreEmail = '<p class="errore">Formato email non valido</p>';
    }

    //controllo password
    if (strlen($password) === 0){
        $errorePassword = '<p class="errore">Inserire la password</p>';
    }

    //CONTROLLO CORRISPONDENZA EMAIL E PASSWORD
    if (empty($erroreEmail) && empty($errorePassword)){
        $db = new DBAccess();
        $connessione = $db->openDBConnection(); //tento la connessione
        if(!$connessione){  
            $erroreDB = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
        } else {
            if(!$db->correctLogin($email, $password)){        //verifica se si puo fare il login
                $erroreLogin = '<div class="errore"><p>Accesso non riuscito</p>
                                <p>Controlla email e password</p></div>';
                $db->closeDBConnection();
            } else {
                //LOGIN VALIDO: popolo i campi della sessione
                    $_SESSION['email'] = $email;
                    $_SESSION['nome'] = $db->getNome($email) ?: '';
                    $_SESSION['cognome'] = $db->getCognome($email) ?: '';
                    $db->closeDBConnection();

                    header("Location: areaPersonale.php");    //reindirizzamento all'area personale
                    exit;
            }
        }
    }
}

//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti dall'utente, evitando frustrazione
$paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
$paginaHTML = str_replace('[valorePassword]', $password, $paginaHTML);
$paginaHTML = str_replace('[messaggioEmailMancante]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioPasswordMancante]', $errorePassword, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreLogin]', $erroreLogin, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreDB]', $erroreDB, $paginaHTML); 

echo $paginaHTML;
?>
