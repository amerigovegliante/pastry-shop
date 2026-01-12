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
$email ='';
$nome ='';
$cognome ='';
$password ='';
$erroreEmail ='';
$erroreNome ='';
$erroreCognome ='';
$ArrayErroriPassword =[];
$errorePassword ='';
$messaggioErrore ='';       //per errore di connessione con il DB
$messaggioConferma = '';    //inserimento dei dati avvenuto con successo

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
    
    //recupero parametri e pulizia input
    $nome = pulisciInput($_POST['nome']);
    $cognome = pulisciInput($_POST['cognome']);
    $email = pulisciInput($_POST['email']);

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
        $errorePassword .= '<div class="errore"><ul>';
        foreach($ArrayErroriPassword as $error){
            $errorePassword .= $error;
        }
        $errorePassword .= '</ul></div>';
    }

    //INSERIMENTO VALORI NEL DATABASE (solo se tutti i campi sono corretti)
    if (empty($erroreNome) && empty($erroreCognome) && empty($erroreEmail) && empty($errorePassword)){
        $nome = formattaNomeCognome($nome);
        $cognome = formattaNomeCognome($cognome);

        $db = new DBAccess();
        $connessione = $db->openDBConnection(); //tento la connessione
        if(!$connessione){  
            $messaggioErrore = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
        } else {
            if($db->emailExists($email)){        //se la email è gia registrata nel database
                $erroreEmail = '<div class="errore"><p>L\'indirizzo email è già stato usato.</p>
                                <p>Riprova con una email differente.</p></div>';
            } else {
                $success = $db->insertNewPersona($email, $nome, $cognome, $password);
                $db->closeDBConnection();   //chiudo la connessione
                if(!$success){  
                    $messaggioErrore = '<p class="errore">Siamo spiacenti, si è verificato un problema di connessione. Riprova più tardi.</p>';
                } else {
                   //AVVIO SESSIONE
                    session_start();                    
                    $_SESSION['email'] = $email;
                    $_SESSION['nome'] = $nome;
                    $_SESSION['cognome'] = $cognome;

                    $messaggioConferma = '<div class="successo">
                        <p>Registrazione completata con successo!</p>
                        <p><a href="areaPersonale.php">Vai alla tua area personale</a></p>
                        </div>';
                }
            }
        }
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

$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML); 
$paginaHTML = str_replace('[messaggioConferma]', $messaggioConferma, $paginaHTML); 

echo $paginaHTML;
?>
