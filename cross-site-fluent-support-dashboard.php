<?php
/**
 * Plugin Name: Appsero My Account to Freshdesk Support
 * Plugin URI:  https://wppool.dev
 * Description: This plugin will create freshdesk ticket right from appsero my account page
 * Version:     1.0
 * Author:      WPPOOL
 * Author URI:  https://wppool.dev
 * Text Domain: appsero-freshdesk
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
//namespace Appsero\Helper\WooCommerce;

final class WP_To_Freshdesk_Support{

	const version = '1.0.0';
	public $appsero_freshdesk_admin = '';
	public $appsero_freshdesk_public = '';

    public function __construct(){
        $this->load_dependencies();
		$this->define_constants();
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'plugin_page_settings_link'));
    }
    public function plugin_page_settings_link($links)
    {
        $links[] = '<a href="' .
            admin_url('options-general.php?page=freshdesk-settings') . '">' . __('Settings', 'appsero-freshdesk') . '</a>';
        return $links;
    }

    private function load_dependencies(){
        require_once plugin_dir_path(__FILE__) . 'admin/class-admin-freshdesk-appsero.php';
        require_once plugin_dir_path(__FILE__) . 'public/class-public-freshdesk-appsero.php';
		$this->appsero_freshdesk_admin = new Freshdesk_Appsero_Admin();
		$this->appsero_freshdesk_public = new Freshdesk_Appsero_Public();
   }

   public function define_constants(){
		define('FRSHDESK_APPSERO_VERSION', self::version);
		define('FRSHDESK_APPSERO_FILE', __FILE__);
		define('FRSHDESK_APPSERO_PATH', __DIR__);
		define('FRSHDESK_APPSERO_URL', plugins_url('', FRSHDESK_APPSERO_FILE));
		define('FRSHDESK_APPSERO_ASSETS', FRSHDESK_APPSERO_URL . '/assets');
		define('FRSHDESK_APPSERO_ADMIN', FRSHDESK_APPSERO_URL . '/admin');
		define('FRSHDESK_APPSERO_PUBLIC', FRSHDESK_APPSERO_URL . '/public');
		define('FRSHDESK_APPSERO_ROOT_PATH', plugin_dir_path(__FILE__));
    }

}

new WP_To_Freshdesk_Support();