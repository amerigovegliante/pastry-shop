<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<p>PHP funziona!</p>";   //per vedere se php sta girando

echo "<p>Stato attivazione mysqlnd: ";
var_dump(function_exists('mysqli_stmt_get_result'));    //per controllare se mysqlnd è attivo
echo "</p>";
?>