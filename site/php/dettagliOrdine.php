<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents('../html/dettagliOrdine.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere il file template html/dettagliOrdine.html");
}

//DICHIARAZIONE VARIABILI
$id_ordine = '';
$listaDettagliOrdine = '';
$messaggioErrore = '';       //per errore generico o di connessione con il DB
$stati = [                   // Mappa stati ordine
    1 => 'In attesa',
    2 => 'In preparazione',
    3 => 'Completato',
    4 => 'Ritirato'
];

//TABELLA DETTAGLI ORDINE: Viene stampata una tabella con tutti i prodotti salvati nel DB fino a quel momento
if (isset($_POST['id'])) {
    $id_ordine = $_POST['id'];
    
    $db = new DBAccess();
    $connessione = $db->openDBConnection();
    if(!$connessione){  
        $messaggioErrore = '<p class="errore" role="alert">Errore di connessione al database</p>';
    } else {
        $ordine = $db->getOrdineById($id_ordine);       //recupero i dati dell'ordine
        $db->closeDBConnection();
        if(empty($ordine)){
            $listaDettagliOrdine = '<p class="errore" role="alert">L\'ordine risulta vuoto</p>';
        } else {    
        $statoTesto = isset($stati[$ordine['stato']]) ? $stati[$ordine['stato']] : 'Sconosciuto';   //se non trova uno stato corrispondente gli da "Sconosciuto"

        $listaDettagliOrdine  = '<h2>Dettagli ordine <span aria-label="Numero">#</span>' . htmlspecialchars($ordine['id']) . '</h2>';
        $listaDettagliOrdine .= '<ul>';
        $listaDettagliOrdine .= '<li><strong>Stato:</strong> ' . htmlspecialchars($statoTesto) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Quantità:</strong> ' . htmlspecialchars($ordine['numero']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Data di ordinazione:</strong> ' . htmlspecialchars($ordine['ordinazione']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Data di ritiro:</strong> ' . htmlspecialchars($ordine['ritiro']) . '</li>';
        $listaDettagliOrdine .= '<li><strong>Annotazioni:</strong> ' . htmlspecialchars($ordine['annotazioni']) . '</li>';
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
}
//fa si che una volta inviato il form, giusto o sbagliato, vengono ricompilati i campi gia' scritti  dall'utente, evitando frustrazione
$paginaHTML = str_replace('[messaggioErroreDB]', $messaggioErrore, $paginaHTML);
$paginaHTML = str_replace('[listaDettagliOrdine]', $listaDettagliOrdine, $paginaHTML); 
echo $paginaHTML;
?>