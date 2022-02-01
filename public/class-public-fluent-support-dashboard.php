<?php
class Freshdesk_Appsero_Public
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles' ));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts' ));
    }

    public function enqueue_styles()
    {
        wp_enqueue_style('support-public-style', FRSHDESK_APPSERO_PUBLIC . '/css/freshdesk-support-public.css', array(), time(), 'all');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('support-public-js', FRSHDESK_APPSERO_PUBLIC . '/js/freshdesk-support-public.js', array('jquery'), time(), true);
    }
}
new Freshdesk_Appsero_Public();
