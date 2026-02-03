<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "dbConnection.php";

$paginaHTML = file_get_contents( __DIR__ .'/../html/dettagli.html');
if ($paginaHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
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
    http_response_code(404);
    include __DIR__ . '/404.php';
    $db->closeDBConnection();
    exit;
}

if($connessione){
    $Item = $db->getItemDetail($ID);
    $db->closeDBConnection(); 
    
    if ($Item != null){
        $nome = htmlspecialchars($Item['nome'], ENT_QUOTES, 'UTF-8');
        
        // GESTIONE ALLERGENI
        $allergeniArray = $Item['allergeni']; 
        if(!empty($allergeniArray)){
            $listaAllergeni = "<ul class=\"listaAllergeni\">Allergeni:"; 
            foreach($allergeniArray as $allergene){
                $listaAllergeni .= "<li>".htmlspecialchars($allergene, ENT_QUOTES, 'UTF-8')."</li>";
            }
            $listaAllergeni .= "</ul>";
        } else {
            $listaAllergeni = ""; 
        }

        // PREZZI E FORMATTAZIONE
        $tipoItem = strtolower($Item['tipo']);

        $prezzoFormatted = number_format($Item['prezzo'], 2, ',', '.');
        $etichettaUnit = ($tipoItem === 'torta') ? "/ porzione" : "";
        
        // Etichetta specifica per l'input dentro il box quantità
        $labelQuantita = ($tipoItem === 'torta') ? "Numero Torte" : "Numero Pezzi";

        // IMMAGINE E ALT TEXT
        $imgSrc = !empty($Item['immagine']) ? "site/img/" . $Item['immagine'] : "site/img/placeholder.jpeg";
        // Usa testo alternativo DB se c'è, altrimenti fallback
        $altText = !empty($Item['testo_alternativo']) ? $Item['testo_alternativo'] : "Foto del dolce " . $Item['nome'];

        // SEZIONE DETTAGLI VISIVI
        $Itemdetails .= "<div class=\"product-info-section\">
                      <section class=\"infoItem\">
                          <h2>".htmlspecialchars($Item['nome'], ENT_QUOTES, 'UTF-8')."</h2> 
                          <data value=\"" . $Item['prezzo'] . "\" class=\"prezzoItem\">€" . $prezzoFormatted . " <small>" . $etichettaUnit . "</small></data> 
                          <p>".htmlspecialchars($Item['descrizione'], ENT_QUOTES, 'UTF-8')."</p>
                          " . $listaAllergeni . "
                      </section>
                      <figure>
                         <img src=\"" . htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') . "\" alt=\"" . htmlspecialchars($altText, ENT_QUOTES, 'UTF-8') . "\" class=\"cornice\">
                      </figure>
                      </div>";
        
        $redirectTo = ($tipoItem === 'torta') ? 'torte' : 'pasticcini';
        // SEZIONE FORM DI ACQUISTO
        $formAcquisto .= "
        <form method=\"post\" action=\"carrello\">
            <input type=\"hidden\" name=\"ID\" value=\"".htmlspecialchars($Item['id'], ENT_QUOTES, 'UTF-8')."\">
            <input type=\"hidden\" name=\"redirect_to\" value=\"$redirectTo\">
        ";
        
        // --- LOGICA TORTE ---
        if($tipoItem === 'torta'){
            $tipoBreadcrumb = "<a href=\"torte\">Torte</a>";
            
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
            $tipoBreadcrumb = "<a href=\"pasticcini\">Pasticcini</a>";
        }

        // --- BOX QUANTITÀ
        $maxQuantita = '';
        if($tipoItem === 'torta'){
            $maxQuantita = 10;
        } elseif($tipoItem === 'pasticcino'){
            $maxQuantita = 50;
        }             

        $formAcquisto .= "<fieldset class=\"boxQuantita\">
                            <legend>Quantità</legend>
                            <div class=\"campoQuantita\">
                                <label for=\"quantita\">" . $labelQuantita . "</label>
                                <div class=\"qty-container\">
                                <button type=\"button\" class=\"generic-button btn-qty-meno\" aria-label=\"Diminuisci quantità\">-</button>
                                <input type=\"number\" id=\"quantita\" min=\"1\" max=\"$maxQuantita\" value=\"1\" name=\"quantita\" required class=\"qty-number\">
                                <button type=\"button\" class=\"generic-button btn-qty-piu\" aria-label=\"Aumenta quantità\">+</button>
                                </div>
                                <small>(max $maxQuantita)</small>
                            </div>
                          </fieldset>";

        $formAcquisto .= "<button type=\"submit\" aria-label=\"Aggiungi ".htmlspecialchars($Item['nome'], ENT_QUOTES, 'UTF-8')." al carrello\" class=\"generic-button\">Aggiungi al carrello</button>
                        </form>
                        </section>";
    } else {
        $Itemdetails = "<p class='errore'>Prodotto non trovato.</p>"; 
        $nome = "Errore";
    }
} else {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
}

$paginaHTML = str_replace("[DettagliItem]", $Itemdetails, $paginaHTML);
$paginaHTML = str_replace("[tipoBreadcrumb]", $tipoBreadcrumb, $paginaHTML);
$paginaHTML = str_replace("[Item]", $nome, $paginaHTML);
$paginaHTML = str_replace("[formAcquisto]", $formAcquisto, $paginaHTML);

//Header
$headerHTML = '';
ob_start();
include __DIR__ . '/header.php';
$headerHTML = ob_get_clean();
$paginaHTML = str_replace('[header]', $headerHTML, $paginaHTML);

echo $paginaHTML;
?>