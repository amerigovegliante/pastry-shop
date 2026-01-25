<?php
// importiamo il file di configurazione che contiene le costanti per la connessione (DB_HOST, DB_USER, ecc..)
require_once( __DIR__ .'/../../db_config.php');

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
                DB_NAME,
                DB_PORT
            );
            return true;
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }

    public function closeDBConnection() {
        // chiude la connessione solo se è stata effettivamente aperta
        if($this->connection) {
            mysqli_close($this->connection);
        }
    }

    //restituisce la variabile di connessione al DB (necessaria per le transazioni e altre operazioni manuali)
    public function getConn() {
        return $this->connection;
    }
    
    // recupera la lista di prodotti filtrata per categoria (torta o pasticcino)
    public function getListOfItems($tipo){
        $querySelect = "SELECT id, nome, prezzo, descrizione, immagine, testo_alternativo FROM item WHERE tipo=?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $tipo); 
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);

        $itemsArray = array();
        if (mysqli_num_rows($queryResult) > 0){
            while ($row = mysqli_fetch_assoc($queryResult)){ 
                array_push($itemsArray, $row);
            }
            return $itemsArray;
        } else {
            return null;
        }
    }

    // recupera il dettaglio completo di un singolo prodotto, inclusi gli allergeni
    public function getItemDetail($ID){
        $querySelect = "SELECT id, nome, prezzo, descrizione, immagine, tipo FROM item WHERE id=?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "i", $ID); 
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($queryResult) > 0){
            $itemDetails = mysqli_fetch_assoc($queryResult);

            // query allergeni
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
            
            $itemDetails['allergeni'] = $listaAllergeni; 
            return $itemDetails;
        } else {
            return null;
        }
    }

    // recupera gli ordini recenti per il pannello admin
    public function getOrdini(){
        $querySelect = "SELECT id, ritiro, nome, cognome, telefono, annotazioni, stato, totale 
                        FROM ordine 
                        WHERE ritiro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                        ORDER BY ritiro ASC, stato ASC";
        
        $queryResult = mysqli_query($this->connection, $querySelect);
        
        if (mysqli_num_rows($queryResult) > 0){
            $Ritirati = array();
            $NonRitirati = array();
            $Progresso = [1=>'in attesa', 2=>'in preparazione', 3=>'completato', 4=>'ritirato'];
            
            while ($row = mysqli_fetch_assoc($queryResult)){
                $row['progresso'] = isset($Progresso[$row['stato']]) ? $Progresso[$row['stato']] : 'sconosciuto';
                if ($row['stato'] == 4){
                    array_push($Ritirati, $row);
                } else {
                    array_push($NonRitirati, $row);
                }
            }
            return array_merge($NonRitirati, $Ritirati);
        } else {
            return null;
        }
    }

    // Recupera TUTTI i dati di un utente
    public function getPersona($email){
        $query = "SELECT email, nome, cognome, telefono, ruolo FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($res);
    }

    // Recupera ordini di un singolo utente (Storico)
    public function getOrdiniUtente($email){
        $query = "SELECT id, ritiro, ordinazione, stato, totale, numero 
                  FROM ordine 
                  WHERE persona = ? 
                  ORDER BY ordinazione DESC";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        
        $ordini = array();
        while($row = mysqli_fetch_assoc($res)){
            array_push($ordini, $row);
        }
        return $ordini;
    }

    // Utility User/Login
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

    public function correctLogin($email, $password){
        $hash = $this->getHash($email);
        if($hash === false || $hash === null){ return false; }
        if (password_verify($password, $hash)){ return true; } else { return false; }
    }

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

    public function insertNewPersona($email, $nome, $cognome, $telefono, $password){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $queryInsert = "INSERT INTO persona(email, nome, cognome, telefono, ruolo, password) VALUES (?, ?, ?, ?, 'user', ?)";   
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sssss", $email, $nome, $cognome, $telefono, $password_hash);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);   
        return $success;
    }

    public function insertNewDomanda($email, $domanda){
        $queryInsert = "INSERT INTO domanda_contattaci(email, domanda) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "ss", $email, $domanda);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    public function insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo){
        $queryInsert = "INSERT INTO item(tipo, nome, descrizione, prezzo, immagine, testo_alternativo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sssdss", $tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo); 
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    // Aggiorna i dati dell'utente (Nome, Cognome, Telefono)
    public function updatePersona($vecchiaEmail, $nuovoNome, $nuovoCognome, $nuovoTelefono){
        $query = "UPDATE persona SET nome=?, cognome=?, telefono=? WHERE email=?";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $nuovoNome, $nuovoCognome, $nuovoTelefono, $vecchiaEmail);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    // Elimina utente
    public function deletePersona($email) {
        // Verifico se la connessione è aperta, altrimenti provo ad aprirla
        if (!$this->connection) {
            if (!$this->openDBConnection()) {
                return false;
            }
        }
        
        $connessione = $this->connection; 
        
        // 1. Controllo se ci sono ordini ATTIVI (Stato 1, 2, 3)
        $queryCheck = "SELECT id FROM ordine WHERE persona = ? AND stato IN (1, 2, 3)";
        $stmtCheck = mysqli_prepare($connessione, $queryCheck);
        
        if (!$stmtCheck) {
            return false;
        }

        mysqli_stmt_bind_param($stmtCheck, "s", $email);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_store_result($stmtCheck);

        // Se ci sono ordini attivi, NON cancello e ritorno false
        if(mysqli_stmt_num_rows($stmtCheck) > 0){
            mysqli_stmt_close($stmtCheck);
            return false; 
        }
        mysqli_stmt_close($stmtCheck);

        $queryDelete = "DELETE FROM persona WHERE email = ?";
        $stmtDelete = mysqli_prepare($connessione, $queryDelete);
        
        if (!$stmtDelete) {
            return false;
        }

        mysqli_stmt_bind_param($stmtDelete, "s", $email);
        $result = mysqli_stmt_execute($stmtDelete);
        
        mysqli_stmt_close($stmtDelete);
        
        return $result;
    }

     public function  AggiornaStati($statiModificati) {
        // Verifico se la connessione è aperta, altrimenti provo ad aprirla
        if (!$this->connection) {
            if (!$this->openDBConnection()) {
                return false;
            }
        }
        
        $connessione = $this->connection; 

        foreach($statiModificati as $idOrdine => $nuovoStato){
            $query = "UPDATE ordine SET stato=? WHERE id=?";
            $stmt = mysqli_prepare($connessione, $query);
            mysqli_stmt_bind_param($stmt, "ii", $nuovoStato, $idOrdine);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

}  
?>