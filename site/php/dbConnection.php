<?php

class DBAccess{
    
    private $connection;

    public function openDBConnection(){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $host = getenv('MYSQLHOST')     ?: (defined('DB_HOST')     ? DB_HOST     : null);
            $user = getenv('MYSQLUSER')     ?: (defined('DB_USER')     ? DB_USER     : null);
            $pass = getenv('MYSQLPASSWORD') ?: (defined('DB_PASSWORD') ? DB_PASSWORD : null);
            $db   = getenv('MYSQLDATABASE') ?: (defined('DB_NAME')     ? DB_NAME     : null);
            $port = getenv('MYSQLPORT')     ?: (defined('DB_PORT')     ? DB_PORT     : 3306); 
            
            $this->connection = mysqli_connect($host, $user, $pass, $db, (int)$port);
            $this->connection->set_charset("utf8mb4");

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
    
    //restituisce la lista di prodotti ATTIVI filtrata per categoria (torta o pasticcino), altrimenti ritorna lista vuota
    public function getListOfActiveItems($tipo){
        $querySelect = "SELECT id, nome, prezzo, descrizione, immagine, testo_alternativo FROM item WHERE tipo=? AND attivo=TRUE";
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
        } 
    }

   //restituisce TRUE se l'item con l'id passatogli è attivo, FALSE altrimenti
    public function isActive($id_item){
        $querySelect = "SELECT attivo FROM item WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        
        if (!$stmt) {
            return false; // errore nella preparazione
        }

        mysqli_stmt_bind_param($stmt, "i", $id_item);
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);
        
        //se la query non ritorna risultati (id non esiste)
        if (!$queryResult || mysqli_num_rows($queryResult) === 0) {
            mysqli_stmt_close($stmt);
            return false;
        }

        $row = mysqli_fetch_assoc($queryResult);
        mysqli_stmt_close($stmt);
        return (bool)$row['attivo']; // converte 0/1 in booleano
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
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_execute($stmt);
        $queryResult = mysqli_stmt_get_result($stmt);
        
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
            mysqli_stmt_close($stmt);
            return array_merge($NonRitirati, $Ritirati);
        } else {
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    //restituisce tutti i dettagli di un determinato ordine, dato il suo id. FALSE se non presente.
    public function getOrdineById($id){
        $querySelect = "SELECT * FROM ordine WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt); 
        $queryResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($queryResult) > 0) {
            $ordine = mysqli_fetch_assoc($queryResult);
        } else {
            $ordine = false;
        }

        mysqli_stmt_close($stmt);
        return $ordine;
    }
    //restituisce tutti i dettagli di un determinato ordine, dato il suo id e email. FALSE se non presente.
    public function getOrdineByIdAndEmail($id,$email){
        $querySelect = "SELECT * FROM ordine WHERE id = ? AND persona=?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "is", $id, $email);
        mysqli_stmt_execute($stmt); 
        $queryResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($queryResult) > 0) {
            $ordine = mysqli_fetch_assoc($queryResult);
        } else {
            $ordine = false;
        }

        mysqli_stmt_close($stmt);
        return $ordine;
    }

    //dato l'id dell'ordine restituisce tutte le torte con relativi dettagli legati all'ordine, altrimenti ritorna un array vuoto
    public function getOrdiniTortaById($id) {
        $query = "SELECT ot.torta,
            ot.ordine,
            ot.porzioni,
            ot.targa,
            ot.numero_torte,
            i.nome,
            i.immagine,
            i.testo_alternativo
            FROM ordine_torta AS ot
            JOIN item AS i ON ot.torta = i.id
            WHERE ot.ordine = ? ";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $torte = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $torte[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $torte; 
    }


    //dato l'id dell'ordine restituisce tutte i pasticcini con relativi dettagli legati all'ordine, altrimenti ritorna un array vuoto
    public function getOrdiniPasticcinoById($id) {
        $query = "SELECT op.pasticcino,
            op.ordine,
            op.quantita,
            i.nome,
            i.immagine,
            i.testo_alternativo
            FROM ordine_pasticcino AS op
            JOIN item AS i ON op.pasticcino = i.id
            WHERE op.ordine = ? ";
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $pasticcini = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $pasticcini[] = $row;
        }

        mysqli_stmt_close($stmt);
        return $pasticcini;
    }

    //controlla che l'ordine esista
    public function ordineEsiste($id) {
    $sql = "SELECT id FROM ordine WHERE id = ?";
    $stmt = mysqli_prepare($this->connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $esiste = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    return $esiste;
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
        $query = "SELECT id, ritiro, ordinazione, stato, totale 
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

    // verifica se un allergene è già presente nel sistema
    public function allergeneExists($nomeAllergene){
        $querySelect = "SELECT nome FROM allergene WHERE nome = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $nomeAllergene);
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
/*
    //restituisce tutti gli items ordinati per tipo e per nome, FALSE se non ne trova
    public function getAllItems(){
        $querySelect = "SELECT * FROM item ORDER BY tipo, nome";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = array();

        if (mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)){ //salvo riga per riga in un array associativo
                $items[] = $row;
            }
        } else {
            mysqli_stmt_close($stmt);
            return false;   //nessun record trovato
        }

        mysqli_stmt_close($stmt);
        return $items;
    }
*/
     //restituisce tutti gli items ATTIVI ordinati per tipo e per nome, FALSE se non ne trova
    public function getActiveItems(){
        $querySelect = "SELECT * FROM item WHERE attivo = TRUE ORDER BY tipo, nome";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $items = array();

        if (mysqli_num_rows($result) > 0){
            while ($row = mysqli_fetch_assoc($result)){ //salvo riga per riga in un array associativo
                $items[] = $row;
            }
        } else {
            mysqli_stmt_close($stmt);
            return false;   //nessun record trovato
        }

        mysqli_stmt_close($stmt);
        return $items;
    }

    //restituisce il numero di domande fatte da un ip nella data odierna 
    public function numDomandeIP($ip){
        $querySelect = "SELECT COUNT(*) AS num FROM domanda_contattaci WHERE ip_utente = ? AND DATE(data_invio) = CURDATE()";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $ip);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)($row['num'] ?? 0);
    }

    //restituisce il numero di domande fatte da una email nella data odierna 
    public function numDomandeEmail($email){
        $querySelect = "SELECT COUNT(*) AS num FROM domanda_contattaci WHERE email = ? AND DATE(data_invio) = CURDATE()";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return (int)($row['num'] ?? 0);
    }
/* 
-------------------------------------------------------------------------------------------------------------------------------------------------
FUNZIONI PER SCRIVERE DATI
-------------------------------------------------------------------------------------------------------------------------------------------------
*/
    //registra un nuovo utente nel database con ruolo 'user'
    public function insertNewPersona($email, $nome, $cognome, $telefono, $password){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $queryInsert = "INSERT INTO persona(email, nome, cognome, telefono, ruolo, password) VALUES (?, ?, ?, ?, 'user', ?)";   
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sssss", $email, $nome, $cognome, $telefono, $password_hash);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);   
        return $success;
    }

    //inserisce una nuova domanda dell'utente nel DB
    //restituisce l'oggetto mysqli_result se la query è andata a buon fine, altrimrnti FALSE
    public function insertNewDomanda($email, $domanda, $ip){
        $queryInsert = "INSERT INTO domanda_contattaci(email, domanda, ip_utente) VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sss", $email, $domanda, $ip);

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $success;
    }

    //inserisce un nuovo item (prodotto della pasticceria) nel DB
    //restituisce l'id dell'oggeto appena inserito, altrimrnti FALSE
    public function insertNewItem($tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo){
        $queryInsert = "INSERT INTO item(tipo, nome, descrizione, prezzo, immagine, testo_alternativo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "sssdss", $tipo, $nome, $descrizione, $prezzo, $immagine, $testoAlternativo); 

        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if ($success) {
            return mysqli_insert_id($this->connection);
        } else {
            return false;
        }
    }

    //inserisce una nuova riga in item_allergico nel DB
    //restituisce TRUE se la query è andata a buon fine, altrimrnti FALSE
    public function insertNewItemAllergico($idIitem, $allergeneItem){
        if(!$this->allergeneExists($allergeneItem)){
            return false;               //non inserire se l'allergene non esiste nel database
        }
        $queryInsert = "INSERT INTO item_allergico(item, allergene) VALUES (?, ?)";
        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "is", $idIitem, $allergeneItem); 
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
        if (!$this->connection) {
            if (!$this->openDBConnection()) {
                return false;
            }
        }
        
        try {
            // Iniziamo la transazione: blocchiamo lo stato attuale
            $this->connection->begin_transaction();

            // 1. Controllo se ci sono ordini ATTIVI (Stato 1, 2, 3)
            $queryCheck = "SELECT id FROM ordine WHERE persona = ? AND stato IN (1, 2, 3)";
            $stmtCheck = mysqli_prepare($this->connection, $queryCheck);
            
            if (!$stmtCheck) throw new Exception("Errore prepare check");

            mysqli_stmt_bind_param($stmtCheck, "s", $email);
            mysqli_stmt_execute($stmtCheck);
            mysqli_stmt_store_result($stmtCheck);

            // Se ci sono ordini attivi, ANNULLO tutto e ritorno false
            if(mysqli_stmt_num_rows($stmtCheck) > 0){
                mysqli_stmt_close($stmtCheck);
                $this->connection->rollback(); // Annulla
                return false; 
            }
            mysqli_stmt_close($stmtCheck);

            // 2. Procedo con l'eliminazione
            $queryDelete = "DELETE FROM persona WHERE email = ?";
            $stmtDelete = mysqli_prepare($this->connection, $queryDelete);
            
            if (!$stmtDelete) throw new Exception("Errore prepare delete");

            mysqli_stmt_bind_param($stmtDelete, "s", $email);
            $result = mysqli_stmt_execute($stmtDelete);
            mysqli_stmt_close($stmtDelete);
            
            if ($result) {
                $this->connection->commit(); // Confermo le modifiche definitivamente
                return true;
            } else {
                throw new Exception("Errore execute delete");
            }

        } catch (Exception $e) {
            $this->connection->rollback(); // In caso di errore, torno indietro come se nulla fosse successo
            return false;
        }
    }

     public function  AggiornaStati($statiModificati) {
        // Verifico se la connessione è aperta, altrimenti provo ad aprirla
        /*if (!$this->connection) {
            if (!$this->openDBConnection()) {
                return false;
            }
        }
        
        $connessione = $this->connection; */

        foreach($statiModificati as $idOrdine => $nuovoStato){
            $query = "UPDATE ordine SET stato=? WHERE id=?";
            $stmt = mysqli_prepare($connessione, $query);
            mysqli_stmt_bind_param($stmt, "ii", $nuovoStato, $idOrdine);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
/*
    //elimina l'item con l'id passatogli
    //ritorna TRUE se l'eliminazione è andata a buon fine, FALSE se fallisce, "collegato" se c'è un ordine collegato e non può procedere
    public function deleteItemById($idItem){
        // array delle tabelle collegate agli ordini
        $tabelleCollegate = [
            'ordine_torta' => 'torta',
            'ordine_pasticcino' => 'pasticcino'
        ];

        // controlla se l'item è collegato a ordini
        foreach ($tabelleCollegate as $tabella => $colonna){
            $queryCheck = "SELECT COUNT(*) AS num FROM $tabella WHERE $colonna = ?";
            $stmt = mysqli_prepare($this->connection, $queryCheck);
            mysqli_stmt_bind_param($stmt, "i", $idItem);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if ($row['num'] > 0) {
                return "collegato"; // se è collegato non elimina, restituisce un messaggio
            }
        }
        //altrimenti procede con l'eliminazione
        $queryDeleteItem = "DELETE FROM item WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $queryDeleteItem);
        mysqli_stmt_bind_param($stmt, "i", $idItem);
        mysqli_stmt_execute($stmt);
        $righeEliminate = mysqli_stmt_affected_rows($stmt); //conta il numero di righe eliminate
        mysqli_stmt_close($stmt);
        return $righeEliminate > 0; //TRUE se ha eliminato almeno una riga
    }
*/
    //disattiva l'item che ha l'id passatogli
    //ritorna TRUE se la disattivazione è andata a buon fine, FALSE se fallisce
    public function deactivateItemById($idItem){
        $queryUpdateItem = "UPDATE item SET attivo = FALSE WHERE id = ?";
        $stmt = mysqli_prepare($this->connection, $queryUpdateItem);
        mysqli_stmt_bind_param($stmt, "i", $idItem);
        mysqli_stmt_execute($stmt);
        $righeModificate = mysqli_stmt_affected_rows($stmt); //conta il numero di righe eliminate
        mysqli_stmt_close($stmt);
        return $righeModificate > 0; //TRUE se ha eliminato almeno una riga
    }
}  
?>