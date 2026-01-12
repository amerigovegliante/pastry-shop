<?php

class DBAccess{
    // Parametri per la connessione al database
	private const HOST_DB = "localhost";
	private const DATABASE_NAME = "";
	private const USERNAME = "";
	private const PASSWORD = "";

    private $connection;    //variabile di connessione

    public function openDBConnection(){ //funzione di apertura connessione
        
        mysqli_report(MYSQLI_REPORT_ERROR);
        
        $this->connection = mysqli_connect(     //tentativo di connessione
            DBAccess::HOST_DB, 
            DBAccess::USERNAME,
            DBAccess::PASSWORD,
            DBAccess::DATABASE_NAME
        );

        if(mysqli_connect_errno())	//Controlla se la connessione è fallita
			return false;
		else 
			return true;
    }

    public function closeDBConnection() {	//funzione di chiusura connessione
		mysqli_close($this->connection);
	}
/* 
-------------------------------------------------------------------------------------------------------------------------------------------------
FUNZIONI PER LEGGERE DATI
-------------------------------------------------------------------------------------------------------------------------------------------------
*/
    //restituisce tutte le torte o i pasticcini a seconda del parametro tipo passato sia "Torta" o "Pasticcino"
    public function getListOfItems($tipo){
        //uso un placeholder per evitare sql injection, faccio si che il valore dopo il = sia sempre tratto come una stringa
        $querySelect="SELECT id, nome, prezzo, icona FROM item WHERE tipo=?";
        $stmt=mysqli_prepare($this->connection,$querySelect);
        mysqli_stmt_bind_param($stmt, "s", $tipo); //binding del parametro: $tipo vine trattato sempre come stringa (s)
        mysqli_stmt_execute($stmt); //esecuzione della query già compilata
        $queryResult=mysqli_stmt_get_result($stmt);
        /*creo un array associativo: 
        $itemsArray = [
            ['id'=>1, 'nome'=>'Torta Sacher', 'prezzo'=>22.50],
            ['id'=>2, 'nome'=>'Torta al Cioccolato', 'prezzo'=>18.00]
        ];*/
        $itemsArray = array();
        if (mysqli_num_rows($queryResult)>0){
            while ($row = mysqli_fetch_assoc($queryResult)){ //mysqli_fetch_assoc($queryResult) restituisce una riga del risultato e la converte in un array associativo usando i nomi delle colonne come chiavi
                if($row['icona'] == null){
                    $row['icona'] = "../img/placeholder.jpeg"; //immagine di default se non presente
                }
                array_push($itemsArray,$row);
            }
            return $itemsArray;
        }else{
            return null;
        }
	}

     public function getItemDetail($ID){
        //uso un placeholder per evitare sql injection, faccio si che il valore dopo il = sia sempre tratto come una stringa
        $querySelect="SELECT id, nome, prezzo, descrizione, immagine, tipo FROM item WHERE id=?";
        $stmt=mysqli_prepare($this->connection,$querySelect);
        mysqli_stmt_bind_param($stmt, "i", $ID); //binding del parametro: $ID vine trattato sempre come intero (i)
        mysqli_stmt_execute($stmt); //esecuzione della query già compilata
        $queryResult=mysqli_stmt_get_result($stmt);
        /*creo un array associativo: 
        $itemDetails = [
            'id'=>1, 
            'nome'=>'Torta Sacher', 
            'prezzo'=>22.50,
            'descrizione'=>'Descrizione della torta Sacher',
            'immagine'=>'../img/sacher.jpg'
        ];*/
        if (mysqli_num_rows($queryResult)>0){
            $itemDetails = mysqli_fetch_assoc($queryResult); //mysqli_fetch_assoc($queryResult) restituisce una riga del risultato e la converte in un array associativo usando i nomi delle colonne come chiavi
            if($itemDetails['immagine'] == null){
                $itemDetails['immagine'] = "../img/placeholder.jpeg"; //immagine di default se non presente
            }
            $querySelect="SELECT allergene FROM item_allergico WHERE item=$ID";
            $queryResult=mysqli_query($this->connection,$querySelect); 
            $listaAllergeni = array();
            if (mysqli_num_rows($queryResult)>0){
                while ($row = mysqli_fetch_assoc($queryResult)){ //mysqli_fetch_assoc($queryResult) restituisce una riga del risultato e la converte in un array associativo usando i nomi delle colonne come chiavi
                    array_push($listaAllergeni,$row['allergene']); //aggiungo solo il nome dell'allergene all'array listaAllergeni
                }
            }else{
                $listaAllergeni= null;
            }
            $itemDetails['allergeni'] = $listaAllergeni; //aggiungo l'array degli allergeni all'array itemDetails
            return $itemDetails;
        }else{
            return null;
        }
    }
    //ritiro è un datetime
    public function getOrdini(){
        $querySelect="SELECT id, ritiro, nome, cognome, telefono, annotazioni, stato, totale FROM ordine WHERE ritiro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ORDER BY ritiro ASC, stato ASC";
        //DATE_SUB(data, INTERVAL valore unità)= sottrae un intervallo di tempo a una data o data/ora
        $queryResult = mysqli_query($this->connection, $querySelect);
        if (mysqli_num_rows($queryResult)>0){
            $Ritirati=array();
            $NonRitirati=array();
            $Progresso=[1=>'in attesa', 2=>'in preparazione', 3=>'completato', 4=>'ritirato'];
            while ($row = mysqli_fetch_assoc($queryResult)){ //mysqli_fetch_assoc($queryResult) restituisce una riga del risultato e la converte in un array associativo usando i nomi delle colonne come chiavi
                // trasformo i NULL in stringhe vuote
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $row[$key] = '';
                    }
                }
                $row['progresso']=$Progresso[$row['stato']];
                if ($row['stato']==4){
                    array_push($Ritirati,$row);
                }else{
                    array_push($NonRitirati,$row);
                }
            }
            return array_merge($NonRitirati, $Ritirati);
        }else{
            return null;
        }
    }



    //restituisce TRUE se la email è gia presente nel database, FALSE se non c'è la email nel database
    public function emailExists($email){
        $querySelect = "SELECT email FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        $queryResult = mysqli_stmt_get_result($stmt);

        //controllo se la query ha restituito almeno una riga
        $exists = mysqli_num_rows($queryResult) > 0;

        //pulizia memoria
        mysqli_free_result($queryResult);
        mysqli_stmt_close($stmt);

        return $exists;
    }

    //restituisce il nome relativo alla email, FALSE se non c'è la email nel database
    public function getNome($email){
        $querySelect = "SELECT nome FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        mysqli_stmt_bind_result($stmt, $nome);

        //controlla se la query ha trovato l'utente
        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            return $nome;                   // restituisce il nome
        } else {
            mysqli_stmt_close($stmt);
            return false;                   // email non trovata
        }
    }

    //restituisce il cognome relativo alla email, FALSE se non c'è la email nel database
    public function getCognome($email){
        $querySelect = "SELECT cognome FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt); 
        mysqli_stmt_bind_result($stmt, $cognome);

        //controlla se la query ha trovato l'utente
        if (mysqli_stmt_fetch($stmt)) {
            mysqli_stmt_close($stmt);
            return $cognome;                // restituisce il cognome
        } else {
            mysqli_stmt_close($stmt);
            return false;                   // email non trovata
        }
    }

    //data una email, restituisce l'hash della password salvato nel database. Restituisce FALSE se non c'è la email nel database
    public function getHash($email){
        $querySelect = "SELECT password FROM persona WHERE email = ?";
        $stmt = mysqli_prepare($this->connection, $querySelect);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $password_hash);
        
        //controllo se la query ha trovato l'utente
        if(mysqli_stmt_fetch($stmt)){
            mysqli_stmt_close($stmt);
            return $password_hash;      //restituisce l'hash corrispondente alla email
        } else{
            mysqli_stmt_close($stmt);
            return false;               //email non trovata, restituisce FALSE   
        }
    }
    
    //restituisce TRUE se esiste una tupla con email e password che corrispondono, FALSE altrimenti
    public function correctLogin($email, $password){
    $hash = $this->getHash($email);     //ottengo l'hash della password dal database
    if($hash === false || $hash === null){  // controllo sia per false che null
        return false;
    }
    
    if (password_verify($password, $hash)){     //confronta la password inserita con l'hash salvato
        return true;  // login corretto 
    } else{
        return false; // password errata
    }
}
/* 
-------------------------------------------------------------------------------------------------------------------------------------------------
FUNZIONI PER SCRIVERE DATI
-------------------------------------------------------------------------------------------------------------------------------------------------
*/
    //inserisce i dati di un nuovo utente semplice (user)
    //restituisce l'oggetto mysqli_result se la query è andata a buon fine, altrimrnti FALSE
	public function insertNewPersona($email, $nome, $cognome, $password){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);    // salvo l'hash della password

		$queryInsert = "INSERT INTO persona(email, nome, cognome, ruolo, password)
		VALUES (?, ?, ?, 'user', ?)";	

        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "ssss", $email, $nome, $cognome, $password_hash);
        $success = mysqli_stmt_execute($stmt); 

        //pulizia memoria
        mysqli_stmt_close($stmt);

        return $success;   
	}

    //inserisce una nuova riga nella tabella "domanda_contattaci" del DB
    //restituisce l'oggetto mysqli_result se la query è andata a buon fine, altrimrnti FALSE
    public function insertNewDomanda($email, $domanda){
        $queryInsert = "INSERT INTO domanda_contattaci(email, domanda) VALUES (?, ?)";

        $stmt = mysqli_prepare($this->connection, $queryInsert);
        mysqli_stmt_bind_param($stmt, "ss", $email, $domanda);
        $success = mysqli_stmt_execute($stmt); 

        //pulizia memoria
        mysqli_stmt_close($stmt);

        return $success; 
    }
}
