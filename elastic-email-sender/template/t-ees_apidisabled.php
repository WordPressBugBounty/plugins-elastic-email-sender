<div class="connection-error-container">
    <img src="<?php echo esc_url(plugins_url('/src/img/connect_apikey.png', dirname(__FILE__))) ?>">
    <p class="ee-p"><?php esc_html_e('Sending via Elastic Email API is disabled.', 'elasticemailsender') ?></p>
    <p class="user-info">
        <?php esc_html_e('You are currently sending through the basic Wordpress settings', 'elasticemailsender') ?> <code>WP_MAIL()</code>.
        <?php esc_html_e('This screen is only available for sending via Elastic Email API. ', 'elasticemailsender') ?>
        <?php esc_html_e('You can change it ', 'elasticemailsender') ?> <a href="
        <?php echo esc_url(get_admin_url() . 'admin.php?page=elasticemail-settings'); ?>"> <?php esc_html_e('here', 'elasticemailsender') ?></a> <?php esc_html_e('(option: Select mailer)', 'elasticemailsender') ?>.
    </p>
</div>
