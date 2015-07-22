<div class="login-form-container">
    <?php if ( $attributes['show_title'] ) : ?>
        <h2><?php _e( 'Sign In', 'praxis-login'); ?></h2>
    <?php endif; ?>

    <?php
        wp_login_form(
            array (
                'label_username' => __( 'Email', 'praxis-login' ),
                'label_log_in' => __( 'Sign In', 'praxis-login' ),
                'redirect' => $attributes['redirect'],
            )
        );
     ?>

     <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">
         <?php _e( 'Forgot your password? ', 'praxis-login' ); ?>
     </a>
</div>
