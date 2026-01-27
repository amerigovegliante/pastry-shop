<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/dettagliOrdine.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere il file template html/dettagliOrdine.html");
}

//DICHIARAZIONE VARIABILI
$id_ordine = null;
$listaDettagliOrdine = '';
$messaggioErrore = '';       //per errore generico o di connessione con il DB
$stati = [                   // Mappa stati ordine
    1 => 'In attesa',
    2 => 'In preparazione',
    3 => 'Completato',
    4 => 'Ritirato'
];

// prendi id dall'url GET oppure POST
if (isset($_GET['id'])) {
    $id_ordine = intval($_GET['id']);
} elseif (isset($_POST['id'])) {
    $id_ordine = intval($_POST['id']);
}

    if (!$id_ordine) {
        http_response_code(400);
        $messaggioErrore = "<p class='errore'>ID ordine non valido.</p>";
        exit;
    }

    $email = $_SESSION['email'];
    $ruolo = $_SESSION['ruolo'];

    $db = new DBAccess();
    $connessione = $db->openDBConnection();
    if(!$connessione){  
        http_response_code(500);
        include __DIR__ . '/500.php';
        $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
        exit;
    } else {
        if (!$db->ordineEsiste($id_ordine)) {
            http_response_code(404);
            $messaggioErrore = "<p class='errore'>Ordine non trovato.</p>";
        }else{
            if ($ruolo === 'admin') {
                $ordine = $db->getOrdineById($id_ordine);
            } else {
                // l'utente può vedere SOLO i suoi ordini
                $ordine = $db->getOrdineByIdAndEmail($id_ordine, $email);
            }

            if (!$ordine) {
                http_response_code(403);
                include __DIR__ . '/403.php';
                die('Accesso non autorizzato');
            }  
        }
    
        $db->closeDBConnection();
        if(empty($ordine)){
            $listaDettagliOrdine = '<p class="errore" role="alert">L\'ordine risulta vuoto</p>';
        } else {    
        $statoTesto = isset($stati[$ordine['stato']]) ? $stati[$ordine['stato']] : 'Sconosciuto';   //se non trova uno stato corrispondente gli da "Sconosciuto"

        $listaDettagliOrdine  = '<h2>Dettagli ordine <span aria-label="Numero">#</span>' . htmlspecialchars($ordine['id']) . '</h2>';
        $listaDettagliOrdine .= '<ul>';
        $listaDettagliOrdine .= '<li><strong>Stato:</strong> ' . htmlspecialchars($statoTesto) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Data di ordinazione:</strong> ' . htmlspecialchars($ordine['ordinazione']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Data di ritiro:</strong> ' . htmlspecialchars($ordine['ritiro']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Annotazioni:</strong> ' . htmlspecialchars($ordine['annotazioni'] ?? '')  . '</li>';
        $listaDettagliOrdine .= '<li><strong>Totale (€):</strong> ' . htmlspecialchars($ordine['totale']) . '</li>';
        $listaDettagliOrdine .= '</ul>';

        $listaDettagliOrdine .= '<h3>Ordine creato da:</h3>';
        $listaDettagliOrdine .= '<ul>';
        $listaDettagliOrdine .= '<li><strong>Email:</strong> ' . htmlspecialchars($ordine['persona']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Nome:</strong> ' . htmlspecialchars($ordine['nome']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Cognome:</strong> ' . htmlspecialchars($ordine['cognome']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Telefono:</strong> ' . htmlspecialchars($ordine['telefono']) . '</li>';
        $listaDettagliOrdine .= '</ul>';
        }
    }

//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML);
$paginaHTML = str_replace('[listaDettagliOrdine]', $listaDettagliOrdine, $paginaHTML); 
echo $paginaHTML;
?>