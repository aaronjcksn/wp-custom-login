<?php
/**
*
* Plugin Name:      Custom Login WP
* Description:      A simple and minamal plugin that replaces the WordPress Login
* Version:          1.0.0
* Author:           Aaron Jackson
* License:          GPL-2.0+
* Text Domain:      custom-login-wp
*
*/

class Custom_Login_WP {

    /**
    * Initializes the plugin
    *
    * To keep the initialization fast, only add filter and action
    * hooks in the constructor.
    */

    public function __construct() {

    }

    // Plugin activation hook.
    public static function plugin_activated() {
        // These are the pages that are created once the plugin is activated
        $page_definitions = array(
            'member-login' => array(
                'title' => __( 'Sign In', 'custom-login' ),
                'content' => '[custom-login-form]'
            ),
            'member-account' => array(
                'title' => __( 'Your Account', 'custom-login' ),
                'content' => '[account-info]'
            ),
        );

        foreach ( $page_definitions as $slug => $page ) {
            // This checks to see if the page exist or not
            $query = new WP_Query ( 'pagename=' . $slug );
            if ( ! $query -> have_posts() ) {
                // Add the page using the data above
                wp_insert_post (
                    array(
                        'post_content'      => $page['content'],
                        'post_name'         => $slug,
                        'post_title'        => $page['title'],
                        'post_status'       => 'publish',
                        'post_type'         => 'page',
                        'ping_status'       => 'closed',
                        'comment_status'    => 'closed'
                    )
                );
            }
        }
    }
}

// Init the plugin
$personalize_login_pages_plugin = new Custom_Login_WP();

// Create the custom pages at plugin activation
register_activation_hook(__FILE__, array('Custom_Login_WP', 'plugin_activated' ) );
