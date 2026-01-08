<?php

class DBAccess{
    // Parametri per la connessione al database
	private const HOST_DB = "localhost";
	private const DATABASE_NAME = "gromanat";
	private const USERNAME = "gromanat";
	private const PASSWORD = "eefee6eiMah3ohZi";

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
        /*$querySelect="SELECT allergene FROM item_allergico WHERE item=$ID";
        $queryResult=mysqli_query($this->connection,$querySelect); 
        $listaAllergeni = array();
        if (mysqli_num_rows($queryResult)>0){
            while ($row = mysqli_fetch_assoc($queryResult)){ //mysqli_fetch_assoc($queryResult) restituisce una riga del risultato e la converte in un array associativo usando i nomi delle colonne come chiavi
                array_push($listaAllergeni,$row['allergene']); //aggiungo solo il nome dell'allergene all'array listaAllergeni
            }
            return $listaAllergeni;
        }else{
            return null;
        }*/
    }
}
