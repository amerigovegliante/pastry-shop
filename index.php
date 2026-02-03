<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configFile = __DIR__ . '/db_config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($scriptPath, '/\\') . '/';

// Definisco la costante solo se serve ad altri file PHP inclusi
define('BASE_URL', $baseUrl);

//  Pagina richiesta
//$page = $_GET['page'] ?? 'home';
//--------- da cancellare per la consegna (de-commenta la riga sopra) --------------
if (isset($_GET['page'])) {
    $page = $_GET['page'];
}
else {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (strpos($requestUri, $scriptPath) === 0) {
        $page = substr($requestUri, strlen($scriptPath));
    } else {
        $page = $requestUri;
    }
    $page = trim($page, '/');
}
if (empty($page)) {
    $page = 'home';
}
// ---------------------------------------------------------------------------

// Protezione input
if (!preg_match('/^[a-z0-9-]+$/i', $page)) {
    http_response_code(404);
    include __DIR__ . '/site/php/404.php';
    exit;
}

$whitelist = [
    'home'              => ['file' => 'site/php/home.php', 'permesso' => 'public'],
    'chi-siamo'         => ['file' => 'site/php/story.php', 'permesso' => 'public'],
    'contattaci'        => ['file' => 'site/php/contattaci.php', 'permesso' => 'public'],
    'torte'             => ['file' => 'site/php/torte-pasticcini.php', 'permesso' => 'public'],
    'pasticcini'        => ['file' => 'site/php/torte-pasticcini.php', 'permesso' => 'public'],
    'login'             => ['file' => 'site/php/login.php', 'permesso' => 'public'],
    'registrazione'     => ['file' => 'site/php/registrazione.php', 'permesso' => 'public'],
    'dettagli'          => ['file' => 'site/php/dettagli.php', 'permesso' => 'public'],
    'carrello'          => ['file' => 'site/php/carrello.php', 'permesso' => 'public'],
    
    'area-personale'    => ['file' => 'site/php/areaPersonale.php', 'permesso' => 'user_admin'],
    'checkout'          => ['file' => 'site/php/checkout.php', 'permesso' => 'user_admin'],
    'dettaglio-ordine'  => ['file' => 'site/php/dettagliOrdine.php', 'permesso' => 'user_admin'],
    'logout'            => ['file' => 'site/php/logout.php', 'permesso' => 'user_admin'],
    'conferma-ordine'   => ['file' => 'site/php/confermaOrdine.php', 'permesso' => 'user_admin'],
    'esito-ordine'      => ['file' => 'site/php/esito.php', 'permesso' => 'user_admin'],

    'ordini-amministratore' => ['file' => 'site/php/ordiniAdmin.php', 'permesso' => 'admin'],
    'aggiungi-prodotto'     => ['file' => 'site/php/aggiungiProdotto.php', 'permesso' => 'admin']
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
} elseif ($info['permesso'] === 'user' && $ruoloUtente === 'user') {
    $autorizzato = true;
}

// Gestione mancata autorizzazione, da dedcidere se togliere la pagina 403 e quindi togliere questo blocco
if (!$autorizzato) {
    if (!isset($_SESSION['ruolo'])) {
        header("Location: " . $baseUrl . "login");
        exit;
    } 
    else {
        include __DIR__ . '/site/php/403.php';
        exit;
    }
}

// Caricamento pagina
$file = __DIR__ . '/' . $info['file'];

if (!file_exists($file)) {
    http_response_code(500);
    include __DIR__ . '/site/php/500.php';
    exit;
}

ob_start();

if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    include $file;
} else {
    echo file_get_contents($file);
}

$contenutoPagina = ob_get_clean();

$contenutoPagina = str_replace('[BASE_URL]', $baseUrl, $contenutoPagina);

// per le ancore #main, #header, ecc. recupero l'indirizzo esatto della pagina corrente (es. /sito/chi-siamo)
$uriCorrente = $_SERVER['REQUEST_URI'];
$contenutoPagina = str_replace('[URI_CORRENTE]', htmlspecialchars($uriCorrente, ENT_QUOTES, 'UTF-8'), $contenutoPagina);

echo $contenutoPagina;
?>