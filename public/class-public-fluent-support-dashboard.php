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
    }
}
new Fluent_Support_Dashboard_Public();
