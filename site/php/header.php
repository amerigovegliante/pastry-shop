<?php
error_reporting(E_ALL); 
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE){
    session_start();
}

$currentPage = $page ?? 'home';

// Calcolo numero prodotti carrello per il badge
$numeroCarrello = 0;
if (isset($_SESSION['carrello']) && is_array($_SESSION['carrello'])) {
    foreach ($_SESSION['carrello'] as $item) {
        $numeroCarrello += $item['quantita'] ?? 0;
    }
}

$headerHTML = file_get_contents(__DIR__.'/../html/header.html');
if ($headerHTML === false) {
    http_response_code(500);
    include __DIR__ . '/500.php';
    exit;
};

$menu = [
    ['href' => 'home', 'label' => '<span lang="en">Home</span>'],
    ['href' => 'chi-siamo', 'label' => 'Chi siamo'],
    ['href' => 'torte', 'label' => 'Torte'],
    ['href' => 'pasticcini', 'label' => 'Pasticcini'],
    ['href' => 'contattaci', 'label' => 'Contattaci'],
];

$menuPrincipale = '';
foreach ($menu as $m) {
    $isActive = ($currentPage === $m['href']);
    $menuPrincipale .= '<li '.($isActive ? 'id="currentlink" aria-current="page"' : '').'>';
    if ($isActive) {
        $menuPrincipale .= $m['label'];
    } else {
        $menuPrincipale .= '<a href="'.htmlspecialchars($m['href']).'">'.$m['label'].'</a>';
    }
    $menuPrincipale .= '</li>';
}
// Determiniamo label e link per Area Personale in base al login
$isLogged = isset($_SESSION['ruolo']) && in_array($_SESSION['ruolo'], ['user','admin']);
$hrefPersonal = $isLogged ? 'area-personale' : 'login';
$labelPersonalSr = $isLogged ? 'Area personale' : 'Accedi all\'area personale';

$menuPrincipale .= '
    <li class="menu-icons-mobile">
        <div class="mobile-actions">
            
            <a href="'.$hrefPersonal.'" class="icon-btn" aria-label="'.$labelPersonalSr.'">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span class="sr-only">'.$labelPersonalSr.'</span> 
            </a>

            <a href="carrello" class="icon-btn" aria-label="Vai al carrello">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                '. ($numeroCarrello > 0 ? '<span class="cart-badge" aria-hidden="true">'.$numeroCarrello.'</span>' : '') .'
                <span class="sr-only">Carrello</span> 
            </a>
        </div>
    </li>';
// ---------------------------------------------------------------


$badgeCarrello = '';
if ($numeroCarrello > 0) {
    $badgeCarrello = '<span class="cart-badge" aria-label="Prodotti nel carrello">'.$numeroCarrello.'</span>';
}

$icone = '';

// Icona Carrello Desktop
$iconaCarrelloSVG = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" /></svg>';

if ($currentPage === 'carrello') {
    $icone .= '<div class="icon-btn" aria-label="Carrello">'.$iconaCarrelloSVG.$badgeCarrello.'<span class="sr-only">Carrello</span></div>';
} else {
    $icone .= '<a href="carrello" class="icon-btn" aria-label="Carrello">'.$iconaCarrelloSVG.$badgeCarrello.'<span class="sr-only">Carrello</span></a>';
}

// Icona Utente Desktop
$iconaUtenteSVG = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>';

if (isset($_SESSION['ruolo']) && in_array($_SESSION['ruolo'], ['user','admin'])) {
    $hrefUtente  = 'area-personale';
    $labelUtente = 'Area personale';
} else {
    $hrefUtente  = 'login';
    $labelUtente = 'Accedi';
}

if ($currentPage === $hrefUtente) {
    $icone .= '<div class="icon-btn" aria-label="'.$labelUtente.'">'.$iconaUtenteSVG.'<span class="sr-only">'.$labelUtente.'</span></div>';
} else {
    $icone .= '<a href="'.$hrefUtente.'" class="icon-btn" aria-label="'.$labelUtente.'">'.$iconaUtenteSVG.'<span class="sr-only">'.$labelUtente.'</span></a>';
}

$headerHTML = str_replace('[menuPrincipale]', $menuPrincipale, $headerHTML);
$headerHTML = str_replace('[icone]', $icone, $headerHTML);

echo $headerHTML;
?>