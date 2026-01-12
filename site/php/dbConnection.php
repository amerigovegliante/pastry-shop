<?php
// importiamo il file di configurazione che contiene le costanti per la connessione (DB_HOST, DB_USER, ecc..)
require_once('../../db_config.php');

class DBAccess{
    
    private $connection;

    public function openDBConnection(){
        // configurazione per la segnalazione degli errori di mysqli, utile per il debug
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // tentativo di apertura della connessione usando le costanti definite in db_config.php
            $this->connection = mysqli_connect(
                DB_HOST, 
                DB_USER,
                DB_PASSWORD,
                DB_NAME
            );
            return true;
        } catch (mysqli_sql_exception $e) {
            // in caso di errore di connessione, restituiamo false per gestirlo nel frontend
            return false;
        }
    }

    public function closeDBConnection() {
        // chiude la connessione solo se è stata effettivamente aperta
        if($this->connection) {
            mysqli_close($this->connection);
        }
    }

/* -------------------------------------------------------------------------------------------------------------------------------------------------
FUNZIONI PER LEGGERE DATI
-------------------------------------------------------------------------------------------------------------------------------------------------
*/

    
    // recupera la lista di prodotti filtrata per categoria (torta o pasticcino)
    public function getListOfItems($tipo){
        // prepariamo la query selezionando tutti i campi necessari per la visualizzazione in lista
        // nota: prendiamo anche descrizione e immagine per avere flessibilità nel frontend
        $querySelect = "SELECT id, nome, prezzo, icona, descrizione, immagine FROM item WHERE tipo=?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $tipo); 
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);

        $itemsArray = array();
        if (mysqli_num_rows($queryResult) > 0){
            while ($row = mysqli_fetch_assoc($queryResult)){ 
                // se non c'è un'icona specifica, ne assegniamo una di default
                if($row['icona'] == null){
                    $row['icona'] = "../img/placeholder.jpeg"; 
                }
                if($row['immagine'] == null){
                    $row['immagine'] = "../img/placeholder.jpeg";
                }
                array_push($itemsArray, $row);
            }
            return $itemsArray;
        } else {
            return null;
        }
    }

    // recupera il dettaglio completo di un singolo prodotto, inclusi gli allergeni
    public function getItemDetail($ID){
        // prima query: recuperiamo le informazioni base del prodotto
        $querySelect = "SELECT id, nome, prezzo, descrizione, immagine, tipo FROM item WHERE id=?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "i", $ID); 
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($queryResult) > 0){
            $itemDetails = mysqli_fetch_assoc($queryResult);
            
            // verifica presenza immagine
            if($itemDetails['immagine'] == null){
                $itemDetails['immagine'] = "../img/placeholder.jpeg"; 
            }

            // seconda query: recuperiamo la lista degli allergeni associati a questo specifico prodotto
            // usiamo un prepared statement anche qui per sicurezza
            $queryAllergeni = "SELECT allergene FROM item_allergico WHERE item=?";
            $stmtAllergeni = mysqli_prepare($this->connection, $queryAllergeni);
            mysqli_stmt_bind_param($stmtAllergeni, "i", $ID);
            mysqli_stmt_execute($stmtAllergeni);
            $resultAllergeni = mysqli_stmt_get_result($stmtAllergeni);

            $listaAllergeni = array();
            if (mysqli_num_rows($resultAllergeni) > 0){
                while ($row = mysqli_fetch_assoc($resultAllergeni)){
                    array_push($listaAllergeni, $row['allergene']);
                }
            } else {
                $listaAllergeni = null;
            }
            
            // uniamo gli allergeni ai dettagli del prodotto
            $itemDetails['allergeni'] = $listaAllergeni; 
            return $itemDetails;
        } else {
            return null;
        }
    }

    // recupera gli ordini recenti (ultima settimana) e futuri per il pannello admin
    public function getOrdini(){
        // selezioniamo tutti i campi utili per identificare l'ordine e il cliente
        // la query filtra per data di ritiro partendo da 7 giorni fa in poi
        $querySelect = "SELECT id, ritiro, nome, cognome, telefono, annotazioni, stato, totale 
                        FROM ordine 
                        WHERE ritiro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                        ORDER BY ritiro ASC, stato ASC";
        
        $queryResult = mysqli_query($this->connection, $querySelect);
        
        if (mysqli_num_rows($queryResult) > 0){
            $Ritirati = array();
            $NonRitirati = array();
            // mappa per convertire lo stato numerico in stringa leggibile
            $Progresso = [1=>'in attesa', 2=>'in preparazione', 3=>'completato', 4=>'ritirato'];
            
            while ($row = mysqli_fetch_assoc($queryResult)){
                // assegnazione della stringa di stato, con fallback se il codice non esiste
                $row['progresso'] = isset($Progresso[$row['stato']]) ? $Progresso[$row['stato']] : 'sconosciuto';
                
                // divisione degli ordini tra già conclusi (ritirati) e ancora attivi
                if ($row['stato'] == 4){
                    array_push($Ritirati, $row);
                } else {
                    array_push($NonRitirati, $row);
                }
            }
            // restituisce prima quelli attivi, poi quelli ritirati
            return array_merge($NonRitirati, $Ritirati);
        } else {
            return null;
        }
    }

    // verifica se un'email è già registrata nel sistema
    public function emailExists($email){
        $querySelect = "SELECT email FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        $queryResult = mysqli_stmt_get_result($stmt);

        $exists = mysqli_num_rows($queryResult) > 0;

        mysqli_stmt_close($stmt);
        return $exists;
    }

    // recupera il nome dell'utente data l'email
    public function getNome($email){
        $querySelect = "SELECT nome FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        mysqli_stmt_bind_result($stmt, $nome);

        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            return $nome;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    // recupera il cognome dell'utente data l'email
    public function getCognome($email){
        $querySelect = "SELECT cognome FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        mysqli_stmt_bind_result($stmt, $cognome);

        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            return $cognome;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    // recupera l'hash della password per la verifica del login
    public function getHash($email){
        $querySelect = "SELECT password FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $password_hash);
        
        if(mysqli_stmt_fetch($stmt)){
            mysqli_stmt_close($stmt);
            return $password_hash;
        } else{
            mysqli_stmt_close($stmt);
            return false;  
        }
    }
    
    // verifica la corrispondenza tra email e password inserita
    public function correctLogin($email, $password){
        $hash = $this->getHash($email);
        
        if($hash === false || $hash === null){
            return false;
        }
        
        // usiamo password_verify per confrontare la password in chiaro con l'hash crittografato nel db
        if (password_verify($password, $hash)){
            return true;
        } else {
            return false;
        }
    }

    //restituisce il ruolo (admin o user) relativo alla email, FALSE se non trovato
    public function getRuolo($email){
        $querySelect = "SELECT ruolo FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        mysqli_stmt_bind_result($stmt, $ruolo);

        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            return $ruolo;
        } else {
            mysqli_stmt_close($stmt);
            return false;
        }
    }

/* -------------------------------------------------------------------------------------------------------------------------------------------------
FUNZIONI PER SCRIVERE DATI
-------------------------------------------------------------------------------------------------------------------------------------------------
*/

// registra un nuovo utente nel database con ruolo 'user'
    public function insertNewPersona($email, $nome, $cognome, $telefono, $password){
        // crittografia della password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $queryInsert = "INSERT INTO persona(email, nome, cognome, telefono, ruolo, password) VALUES (?, ?, ?, ?, 'user', ?)";   

        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sssss", $email, $nome, $cognome, $telefono, $password_hash);
        
        try {
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $success;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getConn() {
        return $this->connection;
    }
}
?>