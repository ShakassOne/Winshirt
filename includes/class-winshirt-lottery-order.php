<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Paiement WooCommerce → création de tickets individuels.
 * - Utilise \WinShirt\Tickets pour écrire en BDD (numéros uniques)
 * - Met à jour la méta _ws_lottery_count avec la somme SQL (compat front & widgets)
 * - Alimente le checkout & l'email avec la plage attribuée
 */
class Lottery_Order {

    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    public function init(): void {
        if ( ! class_exists('WooCommerce') ) return;

        add_filter('woocommerce_add_cart_item_data', [ $this, 'attach_lottery_to_cart_item' ], 10, 3);
        add_action('woocommerce_checkout_create_order_line_item', [ $this, 'add_line_item_meta' ], 10, 4);

        add_action('woocommerce_payment_complete',        [ $this, 'credit_lotteries_from_order' ]);
        add_action('woocommerce_order_status_processing', [ $this, 'credit_lotteries_from_order' ]);
        add_action('woocommerce_order_status_completed',  [ $this, 'credit_lotteries_from_order' ]);

        add_action('woocommerce_order_item_meta_end', [ $this, 'display_item_lottery_meta' ], 10, 4);
        add_action('woocommerce_email_order_meta',    [ $this, 'email_lottery_summary' ], 10, 3);
    }

    /* --------- Attacher info loterie/tickets sur l'item panier --------- */
    public function attach_lottery_to_cart_item($cart_item_data, $product_id, $variation_id) {
        $pid  = $variation_id ?: $product_id;
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

    /* ---------------------- Copie meta sur ligne commande ---------------------- */
    public function add_line_item_meta($item, $cart_item_key, $values, $order) {
        if ( isset($values['winshirt_lottery']) ) {
            $meta = $values['winshirt_lottery'];
            $item->add_meta_data( __('Loterie','winshirt'), $meta['title'], true );
            $item->add_meta_data( __('Tickets','winshirt'), (int)$meta['tickets'], true );
            $item->add_meta_data( '_ws_lottery_id', (int)$meta['lottery_id'], true );
        }
    }

    /* ---------------------------- Créditer les tickets ------------------------- */
    public function credit_lotteries_from_order( $order_id ): void {
        $order = wc_get_order($order_id);
        if ( ! $order ) return;
        if ( $order->get_meta('_ws_lotteries_credited') ) return;

        // Total de tickets par loterie dans la commande
        $by_lottery = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $lid = (int) $item->get_meta('_ws_lottery_id', true);
            $tix = (int) $item->get_meta('Tickets', true);
            if ( $lid > 0 && $tix > 0 ) {
                $qty = max(1,$item->get_quantity());
                $by_lottery[$lid] = ($by_lottery[$lid] ?? 0) + $tix * $qty;
            }
        }
        if ( empty($by_lottery) ) return;

        $tickets = Tickets::instance();
        $customer_name  = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
        $customer_email = $order->get_billing_email();

        $summary = [];
        foreach ($by_lottery as $lottery_id => $count) {

            // Dédup simple : si une ligne "order" existe déjà pour cette commande/loterie → skip (pas de doubles)
            // On reste simple : si re-crédit, l'admin peut manuellement supprimer dans la table.
            $range = $tickets->create_tickets( (int)$lottery_id, (int)$count, [
                'order_id'       => (int)$order_id,
                'order_item_id'  => 0,
                'customer_name'  => $customer_name,
                'customer_email' => $customer_email,
            ]);

            // Mise à jour méta compteur depuis la base pour compatibilité (cards/shortcodes)
            $sum = $tickets->count_tickets( (int)$lottery_id );
            update_post_meta( (int)$lottery_id, '_ws_lottery_count', (int)$sum );

            $summary[] = [
                'lottery_id' => (int)$lottery_id,
                'title'      => get_the_title( (int)$lottery_id ),
                'tickets'    => (int)$count,
                'range_from' => (int)$range['from'],
                'range_to'   => (int)$range['to'],
            ];
        }

        $order->update_meta_data('_ws_lotteries_credited', 1);
        $order->update_meta_data('_ws_lotteries_summary', $summary);
        $order->save();
    }

    /* ------------------------ Affichages checkout/compte ----------------------- */
    public function display_item_lottery_meta( $item_id, $item, $order, $plain_text ) {
        $lid = (int) $item->get_meta('_ws_lottery_id', true);
        $tix = (int) $item->get_meta('Tickets', true);
        if ( $lid > 0 && $tix > 0 ) {
            $title = get_the_title($lid) ?: ('#'.$lid);
            echo '<div style="margin-top:4px;font-size:90%"><em>'.
                 sprintf( esc_html__('%1$s — %2$d %3$s','winshirt'),
                          esc_html($title),
                          (int)$tix,
                          _n('ticket','tickets',$tix,'winshirt')
                 ).
                 '</em></div>';
        }
    }

    /* ------------------------------- Email Woo ------------------------------- */
    public function email_lottery_summary( $order, $sent_to_admin, $plain_text ) {
        $summary = $order->get_meta('_ws_lotteries_summary');
        if ( empty($summary) || ! is_array($summary) ) return;

        if ( $plain_text ) {
            echo "\n".__('Loteries liées à votre commande :','winshirt')."\n";
            foreach ($summary as $row) {
                $range = ($row['range_from'] === $row['range_to'])
                    ? ('#'.str_pad($row['range_from'], 6, '0', STR_PAD_LEFT))
                    : ('#'.str_pad($row['range_from'], 6, '0', STR_PAD_LEFT).' - #'.str_pad($row['range_to'], 6, '0', STR_PAD_LEFT));
                echo '- '.$row['title'].' : '.$row['tickets'].' '._n('ticket','tickets',$row['tickets'],'winshirt').' ('.$range.")\n";
            }
            return;
        }

        echo '<div style="margin:12px 0;padding:10px;border:1px solid #e5e7eb;border-radius:8px">';
        echo '<strong>'.esc_html__('Loteries liées à votre commande :','winshirt').'</strong>';
        echo '<ul style="margin:6px 0 0 18px">';
        foreach ($summary as $row) {
            $range = ($row['range_from'] === $row['range_to'])
                ? ('#'.str_pad($row['range_from'],6,'0',STR_PAD_LEFT))
                : ('#'.str_pad($row['range_from'],6,'0',STR_PAD_LEFT).' - #'.str_pad($row['range_to'],6,'0',STR_PAD_LEFT));
            printf(
                '<li>%s : <b>%d</b> %s — %s</li>',
                esc_html($row['title']),
                (int)$row['tickets'],
                esc_html(_n('ticket','tickets',$row['tickets'],'winshirt')),
                esc_html($range)
            );
        }
        echo '</ul></div>';
    }

    /* ------------------------------- Helpers --------------------------------- */
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
        return [ 'enabled'=>(bool)$enabled, 'lottery_id'=>(int)$lottery, 'tickets'=>(int)$tickets ];
    }
}
