<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/** Charge le template single + assets CSS/JS. */
class Lottery_Template {
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    public function init(): void {
        add_filter('single_template', [ $this, 'single_template' ]);
        add_action('wp_enqueue_scripts', [ $this, 'enqueue' ]);
    }

    public function single_template(string $template): string {
        if ( is_singular('winshirt_lottery') ) {
            $tpl = WINSHIRT_DIR . 'templates/single-winshirt_lottery.php';
            if ( file_exists($tpl) ) return $tpl;
        }
        return $template;
    }

    public function enqueue(): void {
        // Enqueue sur single loterie et sur toute page où apparaissent nos shortcodes
        $needs = is_singular('winshirt_lottery');
        if ( ! $needs ) {
            global $post;
            if ( $post && is_a($post,'WP_Post') ) {
                $c = $post->post_content;
                $needs = ( has_shortcode($c,'winshirt_lotteries') || has_shortcode($c,'winshirt_lottery_card') || has_shortcode($c,'winshirt_lottery_form') );
            }
        }
        if ( $needs ) {
            wp_enqueue_style('winshirt-lottery', WINSHIRT_URL.'assets/css/lottery.css', [], WINSHIRT_VERSION);
            wp_enqueue_script('winshirt-lottery', WINSHIRT_URL.'assets/js/lottery.js', ['jquery'], WINSHIRT_VERSION, true);
        }
    }
}
