<?php
class Fluent_Support_Dashboard_Public
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles' ));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
    }

    public function enqueue_styles()
    {
        wp_enqueue_style('support-public-style', FlUENT_SUPPORT_PUBLIC . '/css/fluent-support-dashboard-public.css', array(), time(), 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('support-public-js', FlUENT_SUPPORT_PUBLIC . '/js/fluent-support-dashboard-public.js', array('jquery'), time(), true);
        wp_localize_script('support-public-js', 'auth_credentials', array(
            "user_name" => get_option('fluent_support_wp_username'),
		    "user_pass" => get_option('fluent_support_app_pass')
        ));
    }
}
new Fluent_Support_Dashboard_Public();
