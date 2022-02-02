<?php
/**
 * Plugin Name: Cross Site Fluent Support Dashboard
 * Plugin URI:  https://wppool.dev
 * Description: This plugin will create Fluent Support ticket right from appsero my account page
 * Version:     1.0
 * Author:      WPPOOL
 * Author URI:  https://wppool.dev
 * Text Domain: fluent-integration
 * Domain Path: /languages/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
//namespace Appsero\Helper\WooCommerce;

final class WP_To_Fluent_Support_Dashboard{

	const version = '1.0.0';
	public $fluent_support_admin = '';
	public $fluent_support_public = '';

    public function __construct(){
        $this->load_dependencies();
		$this->define_constants();
        add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($this, 'plugin_page_settings_link'));
    }
    public function plugin_page_settings_link($links)
    {
        $links[] = '<a href="' .
            admin_url('options-general.php?page=fluent-support-settings') . '">' . __('Settings', 'fluent-support') . '</a>';
        return $links;
    }

    private function load_dependencies(){
        require_once plugin_dir_path(__FILE__) . 'admin/class-admin-fluent-support-dashboard.php';
        require_once plugin_dir_path(__FILE__) . 'public/class-public-fluent-support-dashboard.php';
		$this->fluent_support_admin = new Fluent_Support_Dashboard_Admin();
		$this->fluent_support_public = new Fluent_Support_Dashboard_Public();
   }

   public function define_constants(){
		define('FlUENT_SUPPORT_VERSION', self::version);
		define('FlUENT_SUPPORT_FILE', __FILE__);
		define('FlUENT_SUPPORT_PATH', __DIR__);
		define('FlUENT_SUPPORT_URL', plugins_url('', FlUENT_SUPPORT_FILE));
		define('FlUENT_SUPPORT_ASSETS', FlUENT_SUPPORT_URL . '/assets');
		define('FlUENT_SUPPORT_ADMIN', FlUENT_SUPPORT_URL . '/admin');
		define('FlUENT_SUPPORT_PUBLIC', FlUENT_SUPPORT_URL . '/public');
		define('FlUENT_SUPPORT_ROOT_PATH', plugin_dir_path(__FILE__));
    }

}

new WP_To_Fluent_Support_Dashboard();