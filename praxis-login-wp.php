<?php
/**
*
* Plugin Name:      Praxis WP
* Description:      A simple and minamal plugin that replaces the WordPress Login
* Version:          1.0.0
* Author:           Aaron Jackson
* License:          GPL-2.0+
* Text Domain:      praxis-wp
*
*/

class Praxis_Login_WP {

    /**
    * Initializes the plugin
    *
    * To keep the initialization fast, only add filter and action
    * hooks in the constructor.
    */

    public function __construct() {
        add_shortcode( 'praxis-login-form', array( $this, 'render_login_form' ) );

    }

    // Plugin activation hook.
    public static function plugin_activated() {
        // These are the pages that are created once the plugin is activated
        $page_definitions = array(
            'member-login' => array(
                'title' => __( 'Sign In', 'praxis-login' ),
                'content' => '[praxis-login-form]'
            ),
            'member-account' => array(
                'title' => __( 'Your Account', 'praxis-login' ),
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

    // Form Rendering Shortcodes

    /**
    *
    * A shortcode for rendering the login form.
    *
    * @param array $attribute Shortcode attributes.
    * @param string $content The text content for shortcode.
    *
    * @return string The shortcode output
    */

    public function render_login_form( $attributes, $content = null ) {
        // Parse shortcode attributes
        $default_attributes = array( 'show_title' => false );
        $attributes = shortcode_atts( $default_attributes, $attributes );
        $show_title = $attributes['show_title'];

        if (is_user_logged_in() ) {
            return __( 'You are already signed in.', 'praxis-login');
        }

        // Pass the redirect parameter to the WordPress login functionality: by default,
        // don't specify a redirect, but if a valid redirect URL has been passed as
        // request parameter, use it.
        $attributes['redirect'] = '';
        if ( isset($_REQUEST['redirect_to'] ) ) {
            $attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
        }

        // Render the login form using an external template
        return $this->get_template_html( 'login_form', $attributes );
    }

    /**
    * Renders the contents of the given template to a string and returns it.
     *
     * @param string $template_name The name of the template to render (without .php)
     * @param array  $attributes    The PHP variables for the template
     *
     * @return string               The contents of the template.
     */

     private function get_template_html( $template_name, $attributes = null ) {
         if ( ! $attributes ) {
             $attributes = array();
         }

         ob_start();

         do_action( 'praxis_login_before_' . $template_name );
         require( 'templates/' . $template_name . '.php');
         do_action( 'praxis_login_after_' . $template_name );

         $html = ob_get_contents();
         $ob_end_clean();

         return $html;
     }
}

// Init the plugin
$personalize_login_pages_plugin = new Praxis_Login_WP();

// Create the custom pages at plugin activation
register_activation_hook(__FILE__, array('Praxis_Login_WP', 'plugin_activated' ) );
