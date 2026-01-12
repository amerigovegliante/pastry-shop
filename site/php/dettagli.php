<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/dettagli.html');
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

        // definizione etichetta prezzo
        $prezzoFormatted = number_format($Item['prezzo'], 2, ',', '.');
        $etichettaUnit = ($Item['tipo'] === 'Torta') ? "/ porzione" : "cad.";

        $Itemdetails .= "<figure>
                         <img src=\"" . htmlspecialchars($Item['immagine']) . "\" alt=\"\" class=\"cornice\">
                         <figcaption>".htmlspecialchars($Item['nome'])."</figcaption>
                      </figure>
                      <section class=\"infoItem\">
                          <h2>".htmlspecialchars($Item['nome'])."</h2> 
                          <data value=\"" . $Item['prezzo'] . "\" class=\"prezzoItem\">€" . $prezzoFormatted . " <small>" . $etichettaUnit . "</small></data> 
                          <p>".htmlspecialchars($Item['descrizione'])."</p>
                          " . $listaAllergeni . "
                      </section>";

        $formAcquisto .= "<section class=\"acquistoItem\">
                <form method=\"post\" action=\"carrello.php\">
                    <fieldset>
                    <legend>Acquisto ".htmlspecialchars($Item['nome'])."</legend>
                    <input type=\"hidden\" name=\"ID\" value=\"".htmlspecialchars($Item['id'])."\">
                    <div>
                        <label for=\"quantita\">Quantità (n. prodotti)</label>
                        <input type=\"number\" id=\"quantita\" min=\"1\" value=\"1\" name=\"quantita\">
                    </div>";
                    
        if($Item['tipo'] === 'Torta'){
            $tipoBreadcrumb = "<a href=\"torte-pasticcini.php?tipo=torte\">Le nostre torte</a>";
            
            $formAcquisto .= "<label for=\"porzione\"> Grandezza Torta: </label>
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
                    
                    <fieldset class=\"personalizzazione\">
                        <legend>Personalizzazione (opzionale):</legend>
                        <div>
                            <label for=\"chkTarga\">
                                <input type=\"checkbox\" id=\"chkTarga\" name=\"chkTarga\">
                                Aggiungi una targa
                            </label>
                            <div id=\"campoTarga\"> 
                                <label for=\"testoTarga\">Testo sulla targa (max 20 caratteri)</label>
                                <textarea id=\"testoTarga\" name=\"testoTarga\" maxlength=\"20\" rows=\"1\" placeholder=\"Es. Buon Compleanno\"></textarea>
                            </div>
                            </div>
                    </fieldset>";
            
        } else if($Item['tipo'] === 'Pasticcino'){
            $tipoBreadcrumb = "<a href=\"torte-pasticcini.php?tipo=pasticcini\">I nostri pasticcini</a>";
        }

        $formAcquisto .= "<button type=\"submit\" aria-label=\"Aggiungi ".htmlspecialchars($Item['nome'])." al carrello\">Aggiungi al carrello</button>
                        </fieldset>
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