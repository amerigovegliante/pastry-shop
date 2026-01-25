<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/dettagli.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere dettagli.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection(); 

// VARIABILI
$tipoBreadcrumb="";
$nome="";
$ID="";
$listaAllergeni= "";
$Itemdetails= "";
$formAcquisto="";   

if(isset($_GET['ID']) && is_numeric($_GET['ID'])){
    $ID = $_GET['ID'];
} else {
    die("Errore: ID prodotto non valido.");
}

if($connessione){
    $Item = $db->getItemDetail($ID);
    $db->closeDBConnection(); 
    
    if ($Item != null){
        $nome = htmlspecialchars($Item['nome']);
        
        // GESTIONE ALLERGENI
        $allergeniArray = $Item['allergeni']; 
        if(!empty($allergeniArray)){
            $listaAllergeni = "<ul class=\"listaAllergeni\">Allergeni:"; 
            foreach($allergeniArray as $allergene){
                $listaAllergeni .= "<li>".htmlspecialchars($allergene)."</li>";
            }
            $listaAllergeni .= "</ul>";
        } else {
            $listaAllergeni = ""; 
        }

        // PREZZI E FORMATTAZIONE
        $tipoItem = strtolower($Item['tipo']);

        $prezzoFormatted = number_format($Item['prezzo'], 2, ',', '.');
        $etichettaUnit = ($tipoItem === 'torta') ? "/ porzione" : "cad.";
        
        // Etichetta specifica per l'input dentro il box quantità
        $labelQuantita = ($tipoItem === 'torta') ? "Numero Torte" : "Numero Pezzi";

        // IMMAGINE
        $imgSrc = ""; 
        if (!empty($Item['immagine'])) {
            $imgSrc = "../img/" . $Item['immagine'];
        } else {
            $imgSrc = "../img/placeholder.jpeg";
        }

        // SEZIONE DETTAGLI VISIVI
        $Itemdetails .= "<figure>
                         <img src=\"" . htmlspecialchars($imgSrc) . "\" alt=\"\" class=\"cornice\">
                         <figcaption>".htmlspecialchars($Item['nome'])."</figcaption>
                      </figure>
                      <section class=\"infoItem\">
                          <h2>".htmlspecialchars($Item['nome'])."</h2> 
                          <data value=\"" . $Item['prezzo'] . "\" class=\"prezzoItem\">€" . $prezzoFormatted . " <small>" . $etichettaUnit . "</small></data> 
                          <p>".htmlspecialchars($Item['descrizione'])."</p>
                          " . $listaAllergeni . "
                      </section>";

        // SEZIONE FORM DI ACQUISTO
        $formAcquisto .= "<section class=\"acquistoItem\">
                <form method=\"post\" action=\"carrello.php\">
                    <input type=\"hidden\" name=\"ID\" value=\"".htmlspecialchars($Item['id'])."\">";
                    
        // --- LOGICA TORTE ---
        if($tipoItem === 'torta'){
            $tipoBreadcrumb = "<a href=\"torte-pasticcini.php?tipo=torte\">Le nostre torte</a>";
            
            // Box 1: Dimensione
            $formAcquisto .= "<fieldset class=\"opzioniTorta\">
                    <legend>Dimensione Torta</legend>
                    <p>Prezzo calcolato a porzione (ca. 150g a persona)</p>
                    <div class=\"porzioni\">
                        <input type=\"radio\" id=\"p6\" name=\"porzione\" value=\"6\" checked>
                        <label for=\"p6\">6 Persone</label>

                        <input type=\"radio\" id=\"p8\" name=\"porzione\" value=\"8\">
                        <label for=\"p8\">8 Persone</label>

                        <input type=\"radio\" id=\"p10\" name=\"porzione\" value=\"10\">
                        <label for=\"p10\">10 Persone</label>   

                        <input type=\"radio\" id=\"p12\" name=\"porzione\" value=\"12\">
                        <label for=\"p12\">12 Persone</label>
                    </div> 
                    </fieldset>";
            
            // Box 2: Personalizzazione
            $formAcquisto .= "<fieldset class=\"personalizzazione\">
                        <legend>Personalizzazione</legend>
                        <div id=\"campoTarga\"> 
                            <label for=\"testoTarga\">Scritta sulla targa (opzionale, max 20 caratteri):</label>
                            <textarea id=\"testoTarga\" name=\"testoTarga\" maxlength=\"20\" rows=\"2\" placeholder=\"Es. Buon Compleanno\"></textarea>
                        </div>
                    </fieldset>";
            
        } else if($tipoItem === 'pasticcino'){
            $tipoBreadcrumb = "<a href=\"torte-pasticcini.php?tipo=pasticcini\">I nostri pasticcini</a>";
        }

        // --- BOX QUANTITÀ
        $formAcquisto .= "<fieldset class=\"boxQuantita\">
                            <legend>Quantità</legend>
                            <div class=\"campoQuantita\">
                                <label for=\"quantita\">" . $labelQuantita . "</label>
                                <input type=\"number\" id=\"quantita\" min=\"1\" value=\"1\" name=\"quantita\" required>
                            </div>
                          </fieldset>";

        $formAcquisto .= "<button type=\"submit\" aria-label=\"Aggiungi ".htmlspecialchars($Item['nome'])." al carrello\">Aggiungi al carrello</button>
                        </form>
                        </section>";
    } else {
        $Itemdetails = "<p class='errore'>Prodotto non trovato.</p>"; 
        $nome = "Errore";
    }
} else {
    $Itemdetails = "<p class='errore'>Errore di connessione al database.</p>";
}

$paginaHTML = str_replace("[DettagliItem]", $Itemdetails, $paginaHTML);
$paginaHTML = str_replace("[tipoBreadcrumb]", $tipoBreadcrumb, $paginaHTML);
$paginaHTML = str_replace("[Item]", $nome, $paginaHTML);
$paginaHTML = str_replace("[formAcquisto]", $formAcquisto, $paginaHTML);

echo $paginaHTML;
?>