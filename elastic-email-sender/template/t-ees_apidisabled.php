<div class="connection-error-container">
    <img src="<?php echo esc_url(plugins_url('/src/img/connect_apikey.png', dirname(__FILE__))) ?>">
    <p class="ee-p"><?php _e('Sending via Elastic Email API is disabled.', 'elastic-email-sender') ?></p>
    <p class="user-info">
        <?php _e('You are currently sending through the basic Wordpress settings', 'elastic-email-sender') ?> <code>WP_MAIL()</code>.
        <?php _e('This screen is only available for sending via Elastic Email API. ', 'elastic-email-sender') ?>
        <?php _e('You can change it ', 'elastic-email-sender') ?> <a href="
        <?php echo get_admin_url() . 'admin.php?page=elasticemail-settings'; ?>"> <?php _e('here', 'elastic-email-sender') ?></a> <?php _e('(option: Select mailer)', 'elastic-email-sender') ?>.
    </p>
</div>
