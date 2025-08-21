<?php
if ( ! defined('ABSPATH') ) exit;
if ( ! current_user_can('manage_options') ) wp_die('Nope');

header('Content-Type: text/plain; charset=utf-8');

echo "WinShirt Diagnostic\n";
echo "===================\n\n";
echo "Site: " . home_url('/') . "\n";
echo "PHP : " . PHP_VERSION . "\n";
echo "WP  : " . get_bloginfo('version') . "\n";

if (defined('WC_VERSION')) {
    echo "Woo : " . WC_VERSION . "\n";
} else {
    echo "Woo : (non détecté)\n";
}

$base = dirname(__DIR__, 1);
$files = [
  'includes/class-winshirt-lottery.php',
  'includes/class-winshirt-lottery-template.php',
  'includes/class-winshirt-lottery-display.php',
  'includes/class-winshirt-lottery-product-link.php',
  'includes/class-winshirt-tickets.php',
  'includes/class-winshirt-lottery-order.php',
  'assets/css/winshirt-lottery.css',
  'assets/js/winshirt-lottery.js',
];

echo "\nFichiers attendus :\n";
foreach ($files as $rel) {
    $full = $base . '/' . $rel;
    echo sprintf(" - %-48s : %s\n", $rel, file_exists($full) ? 'OK' : 'MANQUANT');
}

echo "\nClasses détectées :\n";
$classes = [
  '\\WinShirt\\Lottery',
  '\\WinShirt\\Lottery_Template',
  '\\WinShirt\\Lottery_Display',
  '\\WinShirt\\Lottery_Product_Link',
  '\\WinShirt\\Tickets',
  '\\WinShirt\\Lottery_Order',
];
foreach ($classes as $c) {
    echo sprintf(" - %-36s : %s\n", $c, class_exists($c) ? 'OK' : '—');
}

echo "\nConseil : si un fichier est MANQUANT, laisse le bootstrap tolérant ignorer ce module;\n"
   . "puis réintègre-le plus tard. Si page blanche, garde le mode quarantaine et passe au bootstrap complet module par module.\n";

exit;
