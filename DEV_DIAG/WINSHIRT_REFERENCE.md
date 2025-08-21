# WinShirt – Référence & Diag Express

## A. Structure cible du plugin

winshirt/
├─ winshirt.php (bootstrap)
├─ assets/
│  ├─ css/winshirt-lottery.css
│  ├─ js/winshirt-lottery.js
│  └─ img/placeholder.jpg
└─ includes/
   ├─ class-winshirt-lottery.php (CPT + cœur loterie)
   ├─ class-winshirt-lottery-template.php (shortcodes + cartes + slider)
   ├─ class-winshirt-lottery-display.php (optionnel : réglages d’affichage)
   ├─ class-winshirt-lottery-product-link.php (Woo produits ↔ loteries)
   ├─ class-winshirt-tickets.php (table SQL + API tickets)
   └─ class-winshirt-lottery-order.php (commandes Woo → tickets)

> **Tolérance attendue** : le bootstrap doit charger *ce qui existe* et ignorer *ce qui manque*, sans fatal.

---

## B. Shortcodes (layout moderne)

### 1) Liste de loteries
[winshirt_lotteries
status="active|upcoming|finished|all"
featured="0|1"
limit="12"
layout="grid|slider|diagonal"
columns="1|2|3|4"
gap="24"
show_timer="1|0"
show_count="1|0"
autoplay="0|4000|6000"
speed="600"
loop="0|1"
]

- **grid** : grille responsive (colonnes = `columns`).
- **slider** : carrousel classique (flèches + dots).
- **diagonal** : effet style CodePen (cartes inclinées sur les côtés, carte active droite).
- **loop** : `0` = **ne boucle pas** (arrêt en fin, comme demandé).
- **mobile** diagonal : hauteur ~ **75vh**, 1 carte visible.

### 2) Carte unique
[winshirt_lottery_card id="3400" show_timer="1" show_count="1"]

---

## C. Procédure “Clean Reboot” (sans fatal)

1. **Mettre le bootstrap quarantaine** dans `winshirt.php` (fourni).  
2. Vider le cache éventuel (WP Fastest Cache).  
3. **Réactiver l’extension** : le site doit être OK (plugin neutre).  
4. Ouvrir `https://tonsite.tld/?winshirt_diag=1` (connecté admin) pour voir :
   - versions PHP / WP / Woo,
   - fichiers **trouvés / manquants**,
   - classes détectées.

---

## D. Diagnostic ciblé (quand tu remettras le “vrai” bootstrap)

Le plus fréquent :
- **Méthode CPT différente** : `register_cpt()` (instance) vs `register_post_type()` (statique).
- **Fichiers manquants / casse** dans `includes/`.
- **PHP** trop ancien pour certains type-hints.

**Approche** :
1. Revenir à un `winshirt.php` complet **tolérant**, qui fait :
   - `require_once` silencieux,
   - `if (class_exists(...)) ...->init()` *seulement si la classe existe*,
   - fallback d’enregistrement CPT (voir E).
2. Si ça repète : commenter **1 inclusion à la fois** pour isoler :
   - `class-winshirt-tickets.php` → `class-winshirt-lottery-order.php` → `class-winshirt-lottery-display.php` → `class-winshirt-lottery-product-link.php`.
3. Lire `wp-content/debug.log` (activer `WP_DEBUG_LOG` si besoin).

---

## E. Bootstrap complet recommandé (tolérant)

Points clés pour ton futur `winshirt.php` :
- `winshirt_require()` qui n’émet pas de fatal si le fichier n’existe pas.
- i18n + flush sur activation.
- init conditionnel des classes.

Exemple minimal (pseudo) :
```php
winshirt_require('includes/class-winshirt-lottery.php');
winshirt_require('includes/class-winshirt-lottery-template.php');
winshirt_require('includes/class-winshirt-tickets.php');
winshirt_require('includes/class-winshirt-lottery-order.php');
winshirt_require('includes/class-winshirt-lottery-display.php');
winshirt_require('includes/class-winshirt-lottery-product-link.php');

register_activation_hook(__FILE__, function(){
  if (class_exists('\\WinShirt\\Lottery')) {
    if (method_exists('\\WinShirt\\Lottery','register_cpt')) {
      \WinShirt\Lottery::instance()->register_cpt();
    } elseif (method_exists('\\WinShirt\\Lottery','register_post_type')) {
      \WinShirt\Lottery::register_post_type();
    }
  }
  if (class_exists('\\WinShirt\\Tickets') && method_exists('\\WinShirt\\Tickets','install')) {
    \WinShirt\Tickets::instance()->install();
  }
  flush_rewrite_rules();
});

add_action('plugins_loaded', function(){
  if (class_exists('\\WinShirt\\Lottery'))                 \WinShirt\Lottery::instance()->init();
  if (class_exists('\\WinShirt\\Lottery_Template'))        \WinShirt\Lottery_Template::instance()->init();
  if (class_exists('\\WinShirt\\Tickets'))                 \WinShirt\Tickets::instance()->init();
  if (class_exists('\\WinShirt\\Lottery_Order'))           \WinShirt\Lottery_Order::instance()->init();
  if (class_exists('\\WinShirt\\Lottery_Display'))         \WinShirt\Lottery_Display::instance()->init();
  if (class_exists('\\WinShirt\\Lottery_Product_Link'))    \WinShirt\Lottery_Product_Link::instance()->init();
});
