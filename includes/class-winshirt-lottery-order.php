<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Gestion des ventes WooCommerce → Loteries :
 * - Ajoute les métas loterie/tickets sur chaque ligne
 * - Met à jour le compteur + liste des participants de chaque loterie (1 participant par commande/lotterie)
 * - Affiche loterie/tickets + rang participant dans checkout, "Mon compte" et emails.
 */
class Lottery_Order {

    /** Singleton */
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    /** Démarre les hooks */
    public function init(): void {
        if ( ! class_exists('WooCommerce') ) return;

        // Pendant l'ajout au panier: calcule et stocke info loterie (servira à créer la commande)
        add_filter('woocommerce_add_cart_item_data', [ $this, 'attach_lottery_to_cart_item' ], 10, 3);

        // Lors de la création des lignes de commande : copie les métas
        add_action('woocommerce_checkout_create_order_line_item', [ $this, 'add_line_item_meta' ], 10, 4);

        // Quand la commande est payée (payment_complete) ou passe en processing/completed → on crédite les participants
        add_action('woocommerce_payment_complete', [ $this, 'credit_lotteries_from_order' ]);
        add_action('woocommerce_order_status_processing', [ $this, 'credit_lotteries_from_order' ]);
        add_action('woocommerce_order_status_completed', [ $this, 'credit_lotteries_from_order' ]);

        // Affichage checkout / mon compte
        add_action('woocommerce_order_item_meta_end', [ $this, 'display_item_lottery_meta' ], 10, 4);

        // Email client & admin
        add_action('woocommerce_email_order_meta', [ $this, 'email_lottery_summary' ], 10, 3);
    }

    /* ---------------------------------------------------------------------
     * PANIER : rattache loterie et tickets à chaque item
     * -------------------------------------------------------------------*/
    public function attach_lottery_to_cart_item($cart_item_data, $product_id, $variation_id) {
        $pid = $variation_id ?: $product_id;
        $info = $this->resolve_lottery_for_product( $pid );
        if ( $info['enabled'] && $info['lottery_id'] > 0 && $info['tickets'] > 0 ) {
            $cart_item_data['winshirt_lottery'] = [
                'lottery_id' => (int)$info['lottery_id'],
                'tickets'    => (int)$info['tickets'],
                'title'      => get_the_title( (int)$info['lottery_id'] ),
            ];
        }
        return $cart_item_data;
    }

    /* ---------------------------------------------------------------------
     * CRÉATION DE COMMANDE : copie les infos dans la ligne
     * -------------------------------------------------------------------*/
    public function add_line_item_meta($item, $cart_item_key, $values, $order) {
        if ( isset($values['winshirt_lottery']) ) {
            $meta = $values['winshirt_lottery'];
            $item->add_meta_data( __('Loterie','winshirt'), $meta['title'], true );
            $item->add_meta_data( __('Tickets','winshirt'), (int)$meta['tickets'], true );
            $item->add_meta_data( '_ws_lottery_id', (int)$meta['lottery_id'], true ); // interne
        }
    }

    /* ---------------------------------------------------------------------
     * POST PAIEMENT : crédite participants pour chaque loterie
     * -------------------------------------------------------------------*/
    public function credit_lotteries_from_order( $order_id ): void {
        $order = wc_get_order($order_id);
        if ( ! $order ) return;

        // Évite multiples crédits
        if ( $order->get_meta('_ws_lotteries_credited') ) return;

        $by_lottery = []; // [lottery_id => ['tickets'=>X]]
        foreach ( $order->get_items() as $item ) {
            $lid = (int) $item->get_meta('_ws_lottery_id', true);
            $tix = (int) $item->get_meta('Tickets', true);
            if ( $lid > 0 && $tix > 0 ) {
                if ( ! isset($by_lottery[$lid]) ) $by_lottery[$lid] = 0;
                $by_lottery[$lid] += $tix * max(1, $item->get_quantity());
            }
        }
        if ( empty($by_lottery) ) return;

        $customer_name  = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
        $customer_email = $order->get_billing_email();
        $ip             = $order->get_customer_ip_address();

        $summary = []; // pour réutiliser en email (rang par loterie)
        foreach ( $by_lottery as $lottery_id => $tickets_total ) {
            // Récup list + count
            $rows  = (array) get_post_meta($lottery_id,'_ws_lottery_participants',true);
            $count = (int) get_post_meta($lottery_id,'_ws_lottery_count',true);

            // Dédup: si déjà une entrée pour cette commande et cette loterie → on ne double pas
            $already = false;
            foreach ($rows as $r) {
                if ( (int)($r['order_id'] ?? 0) === (int)$order_id ) { $already = true; break; }
            }
            if ( $already ) {
                // Construit tout de même la ligne pour l’e-mail (rang existant)
                $summary[] = [
                    'lottery_id' => $lottery_id,
                    'title'      => get_the_title($lottery_id),
                    'tickets'    => (int)$tickets_total,
                    'ordinal'    => $count, // pas exact si d’autres ont été ajoutés entre-temps, mais on évite double incrément
                ];
                continue;
            }

            // Ajoute 1 PARTICIPANT (par commande), et stocke les tickets gagnés
            $rows[] = [
                'date'     => date_i18n('Y-m-d H:i:s'),
                'name'     => $customer_name,
                'email'    => $customer_email,
                'order'    => $order->get_order_number(),
                'order_id' => (int)$order_id,
                'tickets'  => (int)$tickets_total,
                'ip'       => (string)$ip,
                'source'   => 'order',
            ];
            $count = count($rows); // rang = nouveau total

            update_post_meta($lottery_id,'_ws_lottery_participants',$rows);
            update_post_meta($lottery_id,'_ws_lottery_count',$count);

            $summary[] = [
                'lottery_id' => $lottery_id,
                'title'      => get_the_title($lottery_id),
                'tickets'    => (int)$tickets_total,
                'ordinal'    => (int)$count,
            ];
        }

        // Marque la commande comme créditée + sauvegarde un résumé
        $order->update_meta_data('_ws_lotteries_credited', 1);
        $order->update_meta_data('_ws_lotteries_summary', $summary);
        $order->save();
    }

    /* ---------------------------------------------------------------------
     * AFFICHAGE checkout / mon compte : sous chaque item, on rappelle ticket(s)
     * -------------------------------------------------------------------*/
    public function display_item_lottery_meta( $item_id, $item, $order, $plain_text ) {
        $lid = (int) $item->get_meta('_ws_lottery_id', true);
        $tix = (int) $item->get_meta('Tickets', true);
        if ( $lid > 0 && $tix > 0 ) {
            $title = get_the_title($lid) ?: ('#'.$lid);
            echo '<div style="margin-top:4px;font-size:90%"><em>'.
                 sprintf( esc_html__('Loterie : %1$s — %2$d %3$s','winshirt'),
                          esc_html($title),
                          (int)$tix,
                          _n('ticket','tickets',$tix,'winshirt')
                 ).
                 '</em></div>';
        }
    }

    /* ---------------------------------------------------------------------
     * EMAIL WooCommerce : bloc récap loteries (titre + tickets + rang)
     * -------------------------------------------------------------------*/
    public function email_lottery_summary( $order, $sent_to_admin, $plain_text ) {
        $summary = $order->get_meta('_ws_lotteries_summary');
        if ( empty($summary) || ! is_array($summary) ) return;

        if ( $plain_text ) {
            echo "\n".__('Loteries liées à votre commande :','winshirt')."\n";
            foreach ($summary as $row) {
                echo '- '.$row['title'].' : '.$row['tickets'].' '._n('ticket','tickets',$row['tickets'],'winshirt').' — '.
                     sprintf(__('Participant n°%d','winshirt'), (int)$row['ordinal'])."\n";
            }
            return;
        }

        echo '<div style="margin:12px 0;padding:10px;border:1px solid #e5e7eb;border-radius:8px">';
        echo '<strong>'.esc_html__('Loteries liées à votre commande :','winshirt').'</strong>';
        echo '<ul style="margin:6px 0 0 18px">';
        foreach ($summary as $row) {
            printf(
                '<li>%s : <b>%d</b> %s — %s</li>',
                esc_html($row['title']),
                (int)$row['tickets'],
                esc_html(_n('ticket','tickets',$row['tickets'],'winshirt')),
                esc_html( sprintf(__('Participant n°%d','winshirt'), (int)$row['ordinal']) )
            );
        }
        echo '</ul></div>';
    }

    /* ---------------------------------------------------------------------
     * Résolution de la loterie d’un produit (copie logique Product_Link)
     * -------------------------------------------------------------------*/
    private function resolve_lottery_for_product( int $product_id ): array {
        $enabled = get_post_meta($product_id,'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($product_id,'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($product_id,'_ws_ticket_count',true);

        $parent_id = wp_get_post_parent_id($product_id);
        if ( ( ! $enabled || $lottery<=0 || $tickets<=0 ) && $parent_id ) {
            $enabled = get_post_meta($parent_id,'_ws_enable_lottery',true)==='yes';
            $lottery = (int) get_post_meta($parent_id,'_ws_lottery_id',true);
            $tickets = (int) get_post_meta($parent_id,'_ws_ticket_count',true);
        }

        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) {
            $terms = get_the_terms($product_id,'product_cat') ?: [];
            foreach($terms as $t){
                $cat_lot = (int) get_term_meta($t->term_id,'_ws_lottery_cat_id',true);
                $cat_tix = (int) get_term_meta($t->term_id,'_ws_lottery_cat_tickets',true);
                if ($cat_lot>0 && $cat_tix>0){ $enabled=true; $lottery=$cat_lot; $tickets=$cat_tix; break; }
            }
        }

        return [
            'enabled'    => (bool)$enabled,
            'lottery_id' => (int)$lottery,
            'tickets'    => (int)$tickets,
        ];
    }
}
