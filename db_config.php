<?php
define('DB_HOST',     getenv('MYSQLHOST') ?: 'localhost');
define('DB_USER',     getenv('MYSQLUSER') ?: 'tuo_uer');
define('DB_PASSWORD', getenv('MYSQLPASSWORD') ?: 'tua_password');
define('DB_NAME',     getenv('MYSQLDATABASE') ?: 'tuo_user');
define('DB_PORT',     getenv('MYSQLPORT') ?: '3306');
?>