<?php
error_reporting(E_ALL); //attiva visualizzazione errori
ini_set('display_errors', 1);


session_start();//CONTROLLA

//genero url base per i file non index
require_once __DIR__ . '/db_config.php';

define('BASE_URL', '/' . DB_USER . '/pastry-shop/');

//  Pagina richiesta
$page = $_GET['page'] ?? 'home';

// Protezione input
if (!preg_match('/^[a-z0-9-]+$/i', $page)) {
    http_response_code(404);
    include __DIR__ . '/site/php/404.php';
    exit;
}


$whitelist = [
    //Accessibili a tutti 
    'home'              => ['file' => 'index.html', 'permesso' => 'public'],
    'chi-siamo'         => ['file' => 'site/html/story.html', 'permesso' => 'public'],
    'contattaci'        => ['file' => 'site/php/contattaci.php', 'permesso' => 'public'],
    'torte'             => ['file' => 'site/php/torte-pasticcini.php', 'permesso' => 'public'],
    'pasticcini'        => ['file' => 'site/php/torte-pasticcini.php', 'permesso' => 'public'],
    'login'             => ['file' => 'site/php/login.php', 'permesso' => 'public'],
    'registrazione'     => ['file' => 'site/php/registrazione.php', 'permesso' => 'public'],
    'dettagli'          => ['file' => 'site/php/dettagli.php', 'permesso' => 'public'],
    'carrello'          => ['file' => 'site/php/carrello.php', 'permesso' => 'public'],
    //Accessibile a user e admin
    'area-personale'    => ['file' => 'site/php/areaPersonale.php', 'permesso' => 'user_admin'],
    'checkout'          => ['file' => 'site/php/checkout.php', 'permesso' => 'user_admin'],
    'dettagli-ordine'   => ['file' => 'site/php/dettagliOrdine.php', 'permesso' => 'user_admin'],
    'logout'            => ['file' => 'site/php/logout.php', 'permesso' => 'user_admin'],
    'conferma-ordine'   => ['file' => 'site/php/conferma_oridine.php', 'permesso' => 'user_admin'],
    'esito-invio-ordine' => ['file' => 'site/php/esito.php', 'permesso' => 'user_admin'],

    //Accessibili ad admin 
    'ordini-amministatore'            => ['file' => 'site/php/ordiniAdmin.php', 'permesso' => 'admin'],
    'aggiungi-prodotto' => ['file' => 'site/php/aggiungiProdotto.php', 'permesso' => 'admin']
];

if (!isset($whitelist[$page])) {
    http_response_code(404);
    include __DIR__ . '/site/php/404.php';
    exit;
}

$info = $whitelist[$page];
$ruoloUtente = $_SESSION['ruolo'] ?? 'public';

// Controllo permessi
$autorizzato = false;

if ($info['permesso'] === 'public') {
        $autorizzato = true;
    } elseif ($info['permesso'] === 'user_admin' && ($ruoloUtente === 'user' || $ruoloUtente === 'admin')) {
        $autorizzato = true;
    } elseif ($info['permesso'] === 'admin' && $ruoloUtente === 'admin') {
        $autorizzato = true;
    }elseif ($info['permesso'] === 'user' && $ruoloUtente === 'user') {
        $autorizzato = true;
    }

if (!$autorizzato) {
    header('Location: index.php?page=login');
    exit;
}

// Caricamento pagina
$file = __DIR__ . '/' . $info['file'];

if (!file_exists($file)) {
    http_response_code(500);
    echo "File non trovato: $file";
    exit;
}

if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
} else {
    echo file_get_contents($file);
}
?>
