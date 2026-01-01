<?php

class DBAccess{
    // Parametri per la connessione al database
	private const HOST_DB = "localhost";
	private const DATABASE_NAME = "gdelucch";
	private const USERNAME = "gdelucch";
	private const PASSWORD = "pheexei3AiVu4toh";

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

    public function closeConnection() {	//funzione di chiusura connessione
		mysqli_close($this->connection);
	}

    //restituisce tutte le torte o i pasticcini a seconda del parametro tipo passato sia "Torta" o "Pasticcino"
    public function getListOfItems($tipo){
        $query = "SELECT id, nome, descrizione, prezzo, immagine FROM item WHERE tipo='$tipo' ORDER BY ID ASC";
        $queryResult = mysqli_query($this->connection, $query) 
            or die("Errore in dbConnection: " . mysqli_error($this->connection));	
        
        if(mysqli_num_rows($queryResult) != 0){		
			$result = array();						//array per i risultati
			while($row = mysqli_fetch_assoc($queryResult)){	//restituisce ogni riga come array associativo
				array_push($result, $row);			
			} 										
			$queryResult->free();					//libero memoria
			return $result;
		}
		else return [];
    }
}