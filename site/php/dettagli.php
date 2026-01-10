<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);

require_once "dbConnection.php";
use DBAccess;

$paginaHTML = file_get_contents('../html/dettagli.html');
if ($paginaHTML === false) {
    die("Errore: impossibile leggere dettagli.html");
}

$db = new DBAccess();
$connessione = $db->openDBConnection(); //tento la connessione

//VARIABILI
//script per item
$tipoBreadcrumb="";
$nome="";
$ID="";
$listaAllergeni= "";
$Itemdetails= "";
$formAcquisto="";   




if(isset($_GET['ID'])){
    $ID=$_GET['ID'];
} else {
    die("Errore: ID non specificato.");
}
// leggo i dati della torta con ID specificato
if($connessione){
    $Item=array();
	$Item = $db->getItemDetail($ID);
	$db->closeConnection();
	if ($Item!=null){ //DA DECIDERE: se inserire alt su immagine, cambiare il prezzo in base alla porzione e quantità selezionata
        $nome=htmlspecialchars($Item['nome']);
        $allergeniArray=array();
        $allergeniArray=$Item['allergeni']; //prendo l'array degli allergeni
        if(!empty($allergeniArray)){
            foreach($allergeniArray as $allergene){
                $listaAllergeni.="<li>".htmlspecialchars($allergene)."</li>";
            }
        }
        $listaAllergeni.="</ul>";
		$Itemdetails .= "<figure>
                         <img src=\"" . $Item['immagine'] . "\" alt=\"\" class=\"cornice\">
                         <figcaption>".htmlspecialchars($Item['nome'])."</figcaption>
                      </figure>
                      <section class=\"infoItem\">
                          <h2>".htmlspecialchars($Item['nome'])."</h2> 
                          <data value=\"" . $Item['prezzo'] . "\" class=\"prezzoTorta\">€".number_format($Item['prezzo'], 2, ',', '.')."</data> 
                          <p>".htmlspecialchars($Item['descrizione'])."</p>
                          <ul class=\"listaAllergeni\"> Allergeni: " . $listaAllergeni . "</ul>
                      </section>";
        $formAcquisto.="<section class=\"acquistoItem\">
                <form method=\"post\" action=\"carrello.php\">
                    <fieldset>
                    <legend>Acquisto ".htmlspecialchars($Item['nome'])."</legend>
                    <input type=\"hidden\" name=\"ID\" value=\"".htmlspecialchars($Item['id'])."\"> <!--serve per passare l'id dell'item al carrello-->
                    <div>
                        <label for=\"quantita\">Quantità</label>
                        <input type=\"number\" id=\"quantita\" min=\"1\" value=\"1\" name=\"quantita\">
                    </div>";
                    
        if($Item['tipo']==='Torta'){
            $tipoBreadcrumb="<a href=\"../../torte-pasticcini.php?tipo=torte\">Le nostre torte</a>";
            $formAcquisto.="<label for=\"porzione\"> Porzione: </label>
                    <p>Le grammature sono indicative e possono variare in base alla decorazione OPPURE Peso indicativo calcolato su circa 150 g a persona</p>
                    <div class=\"porzioni\">
                        <input type=\"radio\" id=\"2P\" name=\"porzione\" value=\"300\" >
                        <label for=\"2P\">2 persone (300 <abbr title=\"grammi\">gr</abbr>)</label>
                        <input type=\"radio\" id=\"4P\" name=\"porzione\" value=\"600\">
                        <label for=\"4P\">4 persone (600 <abbr title=\"grammi\">gr</abbr>)</label>
                        <input type=\"radio\" id=\"6P\" name=\"porzione\" value=\"900\">
                        <label for=\"6P\">6 persone (900 <abbr title=\"grammi\">gr</abbr>)</label>   
                        <input type=\"radio\" id=\"8P\" name=\"porzione\" value=\"1200\">
                        <label for=\"8P\">8 persone (1.2<abbr title=\"chilogrammi\">kg</abbr>)</label>
                        <input type=\"radio\" id=\"10P\" name=\"porzione\" value=\"1500\">
                        <label for=\"10P\">10 persone (1.5<abbr title=\"chilogrammi\">kg</abbr>)</label>
                        <input type=\"radio\" id=\"12P\" name=\"porzione\" value=\"1800\">
                        <label for=\"12P\">12 persone (1.8<abbr title=\"chilogrammi\">kg</abbr>)</label>
                    </div> 
                    <label>Personalizzazione (opzionale):</label>
                    <div>
                        <label for=\"Targa\">
                            <input type=\"checkbox\" id=\"chkTarga\">
                            Aggiungi una targa
                        </label>
                        <div id=\"campoTarga\">  <!--la nascondo con javascript se il checkbox non è selezionato-->
                            <label for=\"testoTarga\">
                                Testo sulla targa (max 20 caratteri)
                            </label>
                            <textarea id=\"testoTarga\" name=\"testoTarga\" maxlength=\"20\" rows=\"1\" placeholder=\"Es. Buon Compleanno Anna\"></textarea>
                        </div>
                        <label for=\"Foto\">
                            <input type=\"checkbox\" id=\"chkFoto\">
                            Aggiungi una foto
                        </label>
                        <div id=\"campoFoto\"> <!--la nascondo con javascript se il checkbox non è selezionato-->
                            <label for=\"uploadFoto\">
                                Carica la foto da stampare (formati accettati: .jpg, .png)
                            </label>
                            <input type=\"file\" id=\"uploadFoto\" accept=\".jpg, .jpeg, .png\">
                        </div>
                    </div>";
                    
                    
            
        } else if($Item['tipo']==='Pasticcino'){
            $tipoBreadcrumb="<a href=\"../../torte-pasticcini.php?tipo=pasticcini\">I nostri pasticcini</a>";
        }
        $formAcquisto.="<button type=\"submit\" aria-label=\"Aggiungi ".htmlspecialchars($Item['nome'])." al carrello\">Aggiungi al carrello</button>
                        </fieldset>
                        </form>
                        </section>";
	}else{
		$Itemdetails ="<p>I dettagli del prodotto". $Item['nome']."non sono al momento disponibili</p>"; 
	}
} else {
	$Itemdetails = "<p>I sistemi sono momentaneamente fuori servizio, ci scusiamo per il disagio</p>";
}

//RIMPIAZZO I SEGNAPOSTO [...]
$paginaHTML = str_replace("[DettagliItem]", $Itemdetails, $paginaHTML);
$paginaHTML = str_replace("[tipoBreadcrumb]", $tipoBreadcrumb, $paginaHTML);
$paginaHTML = str_replace("[Item]", $nome, $paginaHTML);
$paginaHTML = str_replace("[formAcquisto]", $formAcquisto, $paginaHTML);
echo $paginaHTML;
?>