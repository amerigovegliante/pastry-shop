<?php
define('DB_HOST',     getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER',     getenv('MYSQLUSER') ?: 'gromanat');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: 'eefee6eiMah3ohZi');
define('DB_NAME',     getenv('MYSQLDATABASE') ?: 'gromanat');
define('DB_PORT',     getenv('MYSQLPORT') ?: '30022');
?>