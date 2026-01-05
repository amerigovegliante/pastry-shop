<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/registrazione.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere registrazione.html");
}

//DICHIARAZIONE VARIABILI
$nome ='';
$cognome ='';
$email ='';
$password ='';
$erroreNome ='';
$erroreCognome ='';
$erroreEmail ='';
$errorePassword ='';
$messaggioConferma = '';

//funzione per pulire l'input del form per evitare errori o iniezione di codice sql malevola
function pulisciInput($value){
 	$value = trim($value);				
  	$value = strip_tags($value); 		
	$value = htmlentities($value);		
  	return $value;
}

//fa diventare maiuscole solo le iniziali
function formattaNomeCognome($value){
    return ucwords(strtolower($value));                
}

if(isset($_POST['submit'])){
    
    //pulizia input
    $nome = pulisciInput($_POST['nome']);
    $cognome = pulisciInput($_POST['cognome']);
    $email = pulisciInput($_POST['email']);
    $password = pulisciInput($_POST['password']);

    //controllo nome
    if (strlen($nome) === 0) {					//se il nome risulta vuoto
		$erroreNome = '<p class="errore">Inserire il nome</p>';
    } else if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $nome)) {
    $erroreNome = '<p class="errore">Il nome può contenere solo lettere e spazi</p>';
    }

    //controllo cognome
    if (strlen($cognome) === 0) {
        $erroreCognome = '<p class="errore">Inserire il cognome</p>';
    } else if (!preg_match("/^[a-zA-ZÀ-ÿ\s]+$/u", $cognome)) {
    $erroreCognome = '<p class="errore">Il cognome può contenere solo lettere e spazi</p>';
    }

    //controllo email
    if (strlen($email) === 0) {
        $erroreEmail = '<p class="errore">Inserire una email</p>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroreEmail = '<p class="errore">Formato email non valido</p>';
    }

    //controllo password
    if (strlen($password) < 8) {
        $errorePassword = '<p class="errore">La password deve avere almeno 8 caratteri</p>';
    }

    //INSERIMENTO VALORI NEL DATABASE (solo se tutti i campi sono corretti)
    if (empty($erroreNome) && empty($erroreCognome) && empty($erroreEmail) && empty($errorePassword)) {
        formattaNomeCognome($nome);
        formattaNomeCognome($cognome);

        $db = new DBAccess();
        $connessione = $db->openDBConnection(); //tento la connessione

        $messaggioConferma = '<p class="successo">Registrazione completata con successo!</p>';
        $nome = $cognome = $email = $password = '';     //i campi vengono puliti
    }
}
//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti dall'utente, evitando frustrazione
$paginaHTML = str_replace('[valoreNome]', $nome, $paginaHTML);
$paginaHTML = str_replace('[valoreCognome]', $cognome, $paginaHTML);
$paginaHTML = str_replace('[valoreEmail]', $email, $paginaHTML);
$paginaHTML = str_replace('[valorePassword]', $password, $paginaHTML);

$paginaHTML = str_replace('[messaggioErroreNome]', $erroreNome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreCognome]', $erroreCognome, $paginaHTML);
$paginaHTML = str_replace('[messaggioErroreEmail]', $erroreEmail, $paginaHTML);
$paginaHTML = str_replace('[messaggioErrorePassword]', $errorePassword, $paginaHTML);

$paginaHTML = str_replace('[messaggioConferma]', $messaggioConferma, $paginaHTML); 

echo $paginaHTML;
?>
