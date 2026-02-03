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
        $messaggioErrore = "<p class='errore' role='alert'>ID ordine non valido.</p>";
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

    $listaDettagliOrdine .= '<h3 class="titolo-ordine">Ordine creato da:</h3>
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

    if(empty($torteOrdinate) && empty($pasticciniOrdinati)){
        $listaDolciOrdinati .= '<p>Ordine vuoto</p>';
    } else {
        $listaDolciOrdinati .= '<table>
                                    <caption><h3>Elenco dei Prodotti</h3></caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">Nome</th>
                                            <th scope="col">Dettagli</th>
                                            <th scope="col">Quantità</th>
                                            <th scope="col">Prezzo <abbr title="Euro">(€)</abbr></th>
                                        </tr>
                                    </thead>
                                <tbody>';
        if(!empty($torteOrdinate)){     //se è stata ordinata almeno una torta
            foreach ($torteOrdinate as $torta){
    
                $listaDolciOrdinati .= '<tr>
                                            <th scope="row" data-label="Nome">' . htmlspecialchars($torta['nome']) . '</th>
                                            <td data-label="Dettagli">
                                                <ul>
                                                    <li>Porzioni: ' . htmlspecialchars($torta['porzioni']) . '</li>
                                                    <li>Targa: ' . htmlspecialchars($torta['targa'] ?: 'Nessuna') . '</li>
                                                </ul>
                                            </td>
                                            <td data-label="Quantità">' . htmlspecialchars($torta['numero_torte']) . '</td>
                                            <td data-label="Prezzo">€' . number_format($torta['prezzo_totale'], 2, ',', '.') . '</td>
                                        </tr>';
            }
        }
        if(!empty($pasticciniOrdinati)){     //se è stata ordinata almeno una torta
            foreach ($pasticciniOrdinati as $pasticcino){
                $listaDolciOrdinati .= '<tr>
                                            <th scope="row" data-label="Nome">' . htmlspecialchars($pasticcino['nome']) . '</th>
                                            <td data-label="Dettagli">-</td>
                                            <td data-label="Quantità">' . htmlspecialchars($pasticcino['quantita']) . '</td>
                                            <td data-label="Prezzo">€' . number_format($pasticcino['prezzo_totale'], 2, ',', '.') . '</td>
                                        </tr>';
            }
        }
        $listaDolciOrdinati .= '</tbody></table>';
    }
}

//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML);
$paginaHTML = str_replace('[listaDettagliOrdine]', $listaDettagliOrdine, $paginaHTML); 
$paginaHTML = str_replace('[listaDolciOrdinati]', $listaDolciOrdinati, $paginaHTML); 
echo $paginaHTML;
?>