<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Gestion des tickets en base SQL.
 * - Table: {prefix}ws_lottery_tickets
 * - Numérotation séquentielle par loterie (ticket_number INT UNSIGNED AUTO_INCREMENT-like par loterie)
 * - API minimaliste: create_tickets(), count_tickets(), max_ticket()
 * - Export CSV par loterie
 */
class Tickets {
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    /** Nom qualifié de la table SQL */
    public function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'ws_lottery_tickets';
    }

    /** Création / migration de table */
    public function install(): void {
        global $wpdb;
        $table = $this->table();
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lottery_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            order_item_id BIGINT UNSIGNED NULL,
            customer_name VARCHAR(190) NULL,
            customer_email VARCHAR(190) NULL,
            ticket_number BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY lot (lottery_id),
            KEY ord (order_id),
            KEY tnum (ticket_number),
            UNIQUE KEY uniq_lot_ticket (lottery_id, ticket_number)
        ) {$charset};";
        dbDelta($sql);
    }

    /** Bootstrap: hooks d'export */
    public function init(): void {
        add_action('admin_post_ws_export_tickets', [ $this, 'handle_export' ]);
    }

    /**
     * Crée N tickets pour une loterie et retourne [from, to, count]
     * @param int   $lottery_id
     * @param int   $count
     * @param array $context ['order_id','order_item_id','customer_name','customer_email']
     * @return array{from:int,to:int,count:int}
     */
    public function create_tickets(int $lottery_id, int $count, array $context = []): array {
        if ($lottery_id<=0 || $count<=0) return ['from'=>0,'to'=>0,'count'=>0];
        global $wpdb;
        $table = $this->table();

        // Détermine le prochain numéro (max + 1)
        $max = (int) $this->max_ticket($lottery_id);
        $from = $max + 1;
        $to   = $max + $count;

        // Prépare le bulk insert
        $now = current_time('mysql');
        $base = [
            'lottery_id'     => $lottery_id,
            'order_id'       => (int)($context['order_id'] ?? 0),
            'order_item_id'  => (int)($context['order_item_id'] ?? 0),
            'customer_name'  => sanitize_text_field($context['customer_name'] ?? ''),
            'customer_email' => sanitize_email($context['customer_email'] ?? ''),
            'created_at'     => $now,
        ];

        $place = "(%d,%d,%d,%d,%s,%s,%d,%s)";
        $values = [];
        $args   = [];
        for ($i=$from; $i<=$to; $i++) {
            $values[] = $place;
            $args[] = $base['lottery_id'];
            $args[] = $base['order_id'];
            $args[] = $base['order_item_id'];
            $args[] = 0; // id auto, placeholder aligné au pattern mais pas utilisé
            $args[] = $base['customer_name'];
            $args[] = $base['customer_email'];
            $args[] = $i; // ticket_number
            $args[] = $base['created_at'];
        }
        // Remplacement du champ 'id' par NULL proprement
        $sql = "INSERT INTO {$table} (lottery_id,order_id,order_item_id,id,customer_name,customer_email,ticket_number,created_at) VALUES ".implode(',', $values);
        // Remplace le 4e %d (id) par NULL pour chaque ligne
        $sql = preg_replace('/,0,customer_name/',' ,NULL,customer_name', $sql);

        $wpdb->query( $wpdb->prepare( $sql, $args ) );

        return ['from'=>$from,'to'=>$to,'count'=>$count];
    }

    /** Nombre total de tickets pour une loterie (rapide) */
    public function count_tickets(int $lottery_id): int {
        global $wpdb; $table=$this->table();
        return (int) $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE lottery_id=%d", $lottery_id) );
    }

    /** Numéro max courant pour une loterie */
    public function max_ticket(int $lottery_id): int {
        global $wpdb; $table=$this->table();
        return (int) $wpdb->get_var( $wpdb->prepare("SELECT MAX(ticket_number) FROM {$table} WHERE lottery_id=%d", $lottery_id) );
    }

    /** Export CSV admin ?action=ws_export_tickets&lottery_id=ID&_wpnonce=... */
    public function handle_export(): void {
        if ( ! current_user_can('export') ) wp_die('forbidden','', ['response'=>403]);
        $lottery_id = (int)($_GET['lottery_id'] ?? 0);
        check_admin_referer('ws_export_tickets_'.$lottery_id);

        global $wpdb; $table=$this->table();
        $rows = $wpdb->get_results( $wpdb->prepare("SELECT ticket_number, created_at, order_id, customer_name, customer_email FROM {$table} WHERE lottery_id=%d ORDER BY ticket_number ASC", $lottery_id), ARRAY_A );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="tickets-lottery-'.$lottery_id.'.csv"');
        $out = fopen('php://output','w');
        fputcsv($out, ['ticket_number','created_at','order_id','customer_name','customer_email']);
        foreach($rows as $r){ fputcsv($out,$r); }
        fclose($out); exit;
    }
}
