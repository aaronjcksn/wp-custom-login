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
        add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
        add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
        add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 105, 3 );
        add_shortcode( 'praxis-register-form', array( $this, 'render_register_form' ) );
        add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
        add_action( 'login_form_register', array( $this, 'do_register_user') );


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
            'member-register' => array(
                'title' => __( 'Register', 'praxis-login' ),
                'content'   => '[praxis-register-form]'
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

        // Errors

        $errors = array();
        if ( isset( $_REQUEST['login'] ) ) {
            $error_codes = explode( ',', $_REQUEST['login'] );

            foreach ( $error_codes as $code ) {
                $errors []= $this->get_error_message( $code );
            }
        }
        $attributes['errors'] = $errors;

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
         ob_end_clean();

         return $html;
     }

     // Redirect

     /**
     *
     * Redirects the user to the custom login page instead of wp-login
     *
     */

     function redirect_to_custom_login() {
         if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
             $redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;

             if ( is_user_logged_in() ) {
                 $this->redirect_logged_in_user( $redirect_to );
                 exit;
             }

             // Everything else is redirected to the login page
             $login_url = home_url( 'member-login' );
             if ( ! empty( $redirect_to ) ) {
                 $login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
             }

             wp_redirect( $login_url );
             exit;
         }
     }

     /**
     *
     * Redirects the user after successful login
     *
     */

     public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
         $redirect_url = home_url();

         if ( ! isset( $user-> ID ) ) {
             return $redirect_url;
         }

         if ( user_can( $user, 'manage_options' ) ) {
             // Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
             if ( $requested_redirect_to == '' ) {
                 $redirect_url = admin_url();
             } else {
                 $redirect_url = $requested_redirect_to;
             }
         } else {
             // Non-admin users always go to their account page after login
             $redirect_url = home_url ( 'member-account' );
         }

         return wp_validate_redirect( $redirect_url, home_url() );
     }

    // Error Messages

    /**
    *
    * Redirect the user after authentication if there were any errors.
    *
    */

    function maybe_redirect_at_authenticate( $user, $username, $password ) {
        if ( $_SERVER['REQUEST_METHOD'] === 'POST') {
            if ( is_wp_error( $user ) ) {
                $error_codes = join(',', $user->get_error_codes() );

                $login_url = home_url( 'member-login' );
                $login_url = add_query_arg( 'login', $error_codes, $login_url );

                wp_redirect( $login_url );
                exit;
            }
        }

        return $user;
    }

    private function get_error_message( $error_code ) {
        switch ( $error_code ) {
            case 'empty_username':
                return __( 'You have an email address, correct?', 'praxis-login');
                break;
            case 'empty_password':
                return __( 'You need to enter a password to login.', 'praxis-login');
                break;
            case 'invalid_username':
                return __( 'Sorry, none of our users have that email address.', 'praxis-login');
                break;
            case 'incorrect_password':
                $err = __( "The password you entered is in correct. <a href='%s'>Did you forget your password</a>?", 'praxis-login' );
                return sprintf( $err, wp_lostpassword_url() );
                break;
            default:
                break;
        }

        return __( 'An unknown error occurred. Please try again later.', 'praxis-login' );
    }

    // Registration Form

    /**
    *
    * A shortcode for rendering the new user registration form.
    *
    */

    public function render_register_form( $attributes, $content = null ) {
        // Parse shortcode attributes
        $default_attributes = array ( 'show_title' => false );
        $attributes = shortcode_atts( $default_attributes, $attributes );

        if ( is_user_logged_in() ) {
            return __( 'You are already signed in.', 'praxis-login' );
        } elseif ( ! get_option( 'users_can_register' ) ) {
            return __( 'Registering new users is currently not allowed.', 'praxis-login' );
        } else {
            return $this->get_template_html( 'register_form', $attributes );
        }
    }

    /**
    *
    * Redirect users to the custom registration page
    *
    */

    public function redirect_to_custom_register() {
        if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
            if ( is_user_logged_in() ) {
                $this->redirect_logged_in_user();
            } else {
                wp_redirect ( home_url('member-register') );
            }
            exit;
        }
    }

    // Form Vaildation

    /**
    *
    * Vaildates and completes the new user signup process
    *
    */

    private function register_user( $email, $first_name, $last_name ) {
        $errors = new WP_Error();

        // Email address is used as both username and email.
        if ( ! is_email($email ) ) {
            $errors->add( 'email', $this->get_error_message( 'email' ) );
            return $errors;
        }

        if ( username_exists( $email ) || email_exists( $email ) ) {
            $errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
            return $errors;
        }

        // Generate password to force subscriber to check email
        $password = wp_generate_password( 12, false );

        $user_data = array(
            'user_login'    => $email,
            'user_email'    => $email,
            'user_pass'     => $password,
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'nickname'      => $first_name,
        );

        $user_id = wp_insert_user( $user_data );
        wp_new_user_notification( $user_id, $password );

        return $user_id;
    }

    public function do_register_user() {
        if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $redirect_url = home_url( 'member-register' );

            if ( ! get_option( 'users_can_register') ) {
                // Registration closed, display error
                $redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
            } else {
                $email = $_POST['email'];
                $first_name = sanitize_text_field( $_POST['first_name'] );
                $last_name = sanitize_text_field( $_POST['last_name'] );

                $result = $this->register_user( $email, $first_name, $last_name );

                if ( is_wp_error( $result ) ) {
                    // Parse errors into a string and append as parameter to redirect
                    $errors = join( ',', $result->get_error_codes() );
                    $redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
                } else {
                    // Success, redirect to login.
                    $redirect_url = home_url( 'memeber-login' );
                    $redirect_url = add_query_arg( 'registered', $email, $redirect_url );
                }
            }

            wp_redirect( $redirect_url );
            exit;
        }
    }

}

// Init the plugin
$personalize_login_pages_plugin = new Praxis_Login_WP();

// Create the custom pages at plugin activation
register_activation_hook(__FILE__, array('Praxis_Login_WP', 'plugin_activated' ) );
