<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";

//Se non già avviata avvio la sessione
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
$torteOrdinate = [];
$pasticciniOrdinati = [];
$listaDolciOrdinati = '';

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

    if(!$connessione){  //se avviene errore di connessione al database
        http_response_code(500);
        include __DIR__ . '/500.php';
        exit;
        } else {

            if (!$db->ordineEsiste($id_ordine)) {   //se l'ordine non esiste nel database 
                http_response_code(404);
                include __DIR__ . '/404.php';
                $db->closeDBConnection();
                exit;
            } else {
                if ($ruolo === 'admin') {
                    $ordine = $db->getOrdineById($id_ordine);                       //admin puo vedere gli ordini di tutti
                } else {
                    $ordine = $db->getOrdineByIdAndEmail($id_ordine, $email);       //un utente user può vedere SOLO i suoi ordini
                }
                if (!$ordine) {     //l'ordine esiste ma non si hanno i permessi per visualizzarlo. es: user che prova ad accedere ad un ordine non effettuato da lui
                    http_response_code(404);
                    include __DIR__ . '/404.php';
                    $db->closeDBConnection();
                    exit;
                } else {    //TUTTO OK: recupero dettagli di torte e pasticcini ordinati
                    $torteOrdinate = $db->getOrdiniTortaById($id_ordine);
                    $pasticciniOrdinati = $db->getOrdiniPasticcinoById($id_ordine);
                    $db->closeDBConnection();
                }
            }
        }
    

    //COSTRUZIONE HTML DETTAGLI ORDINE
    if(empty($ordine)){
        $listaDettagliOrdine = '<p class="errore" role="alert">L\'ordine non contiene prodotti</p>';
    } else {    
    $statoTesto = isset($stati[$ordine['stato']]) ? $stati[$ordine['stato']] : 'Sconosciuto';   //se non trova uno stato corrispondente gli da "Sconosciuto"

    $listaDettagliOrdine  = '<h2>Dettagli ordine <span aria-label="Numero">#</span>' . htmlspecialchars($ordine['id']) . '</h2>
                            <div class="dettagli-ordine">
                                <dl>
                                    <dt><strong>Stato:</strong></dt>
                                    <dd>'. htmlspecialchars($statoTesto) . '</dd>
                                    <dt><strong>Data di ordinazione:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['ordinazione']) . '</dd>
                                    <dt><strong>Data di ritiro:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['ritiro']) . '</dd>
                                    <dt><strong>Annotazioni:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['annotazioni'] ?? 'nessuna annotazione inserita') . '</dd>
                                    <dt><strong>Totale (€):</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['totale']) . '</dd>
                                </dl>
                            </div>';

    $listaDettagliOrdine .= '<h3>Ordine creato da:</h3>
                            <div class="dettagli-ordine">
                                <dl>
                                    <dt><strong>Email:</strong></dt>
                                    <dd>' . htmlspecialchars($ordine['persona']) . '</dd>
                                    <dt><strong>Nome:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['nome']) . '</dd>
                                    <dt><strong>Cognome:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['cognome']) . '</dd>
                                    <dt><strong>Telefono:</strong></dt>
                                    <dd>'. htmlspecialchars($ordine['telefono']) . '</dd>
                                </dl>
                            </div>';

    $listaDolciOrdinati =  '<h3>Dolci ordinati:</h3>';
    if(empty($torteOrdinate) && empty($pasticciniOrdinati)){
        $listaDolciOrdinati .= '<p>Ordine vuoto</p>';
    } else {
        $listaDolciOrdinati .= '<ul>';
        if(!empty($torteOrdinate)){     //se è stata ordinata almeno una torta
            foreach ($torteOrdinate as $torta){
                $imgSrc = !empty($torta['immagine']) ? "site/img/" . $torta['immagine'] : "site/img/placeholder.jpeg";
                $altText = !empty($torta['testo_alternativo']) ? $torta['testo_alternativo'] : "Immagine non disponibile";
                $listaDolciOrdinati .= '<li class="dolce-ordinato">
                                            <h4>' . $torta['nome'] . '</h4>
                                            <div class="contenitore-dolce">
                                                <div class="immagine-dolce">
                                                    <img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($altText) .'"/>
                                                </div>
                                                <div class="dettagli-dolce">
                                                    <dl>
                                                        <dt><strong>Quantità:</strong></dt>
                                                        <dd>'. htmlspecialchars($torta['numero_torte']) . '</dd>
                                                        <dt><strong>Porzioni per torta:</strong></dt>
                                                        <dd>'. htmlspecialchars($torta['porzioni']) . '</dd>';
                $targaTorta = htmlspecialchars($torta['targa']);    //se vuota non mostro la vote della targa
                if(!empty($targaTorta)){
                    $listaDolciOrdinati .= '<dt><strong>Scritta sulla targa:</strong></dt>
                    <dd>'. $targaTorta . '</dd>';
                }
                $listaDolciOrdinati .= '</dl>
                                    </div>
                                </div>
                            </li>';
            }
        }
        if(!empty($pasticciniOrdinati)){     //se è stata ordinata almeno una torta
            foreach ($pasticciniOrdinati as $pasticcino){
                $imgSrc = !empty($pasticcino['immagine']) ? "site/img/" . $pasticcino['immagine'] : "site/img/placeholder.jpeg";
                $altText = !empty($pasticcino['testo_alternativo']) ? $pasticcino['testo_alternativo'] : "Immagine non disponibile";
                $listaDolciOrdinati .= '<li class="dolce-ordinato">
                                            <h4>' . htmlspecialchars($pasticcino['nome']) . '</h4>
                                            <div class="contenitore-dolce">
                                                <div class="immagine-dolce">
                                                    <img src="' . htmlspecialchars($imgSrc) . '" alt="' . htmlspecialchars($altText) . '"/>
                                                </div>
                                                <div class="dettagli-dolce">
                                                    <dl>
                                                        <dt><strong>Quantità:</strong></dt>
                                                        <dd>' . htmlspecialchars($pasticcino['quantita']) . '</dd>
                                                    </dl>
                                                </div>
                                            </div>
                                        </li>';
            }
        }
        $listaDolciOrdinati .= '</ul>';
    }
}

//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML);
$paginaHTML = str_replace('[listaDettagliOrdine]', $listaDettagliOrdine, $paginaHTML); 
$paginaHTML = str_replace('[listaDolciOrdinati]', $listaDolciOrdinati, $paginaHTML); 
echo $paginaHTML;
?>