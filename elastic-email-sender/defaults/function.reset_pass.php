<?php

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function already has ees_ prefix
function ees_custom_password_reset($message, $key, $user_login, $user_data)
{
    $message = esc_html__('Someone has requested a password reset for the following account:', 'elasticemailsender') . "<br><br>\r\n\r\n";
    /* translators: %s: site name */
    $message .= sprintf(esc_html__('Site Name: %s', 'elasticemailsender'), esc_html(get_bloginfo())) . "<br>\r\n\r\n";
    /* translators: %s: user email address */
    $message .= sprintf(esc_html__('Email Address: %s', 'elasticemailsender'), esc_html($user_data->user_email)) . "<br>\r\n\r\n";

    /* translators: %s: user login */
    $message .= sprintf(esc_html__('Username: %s', 'elasticemailsender'), esc_html($user_login)) . "<br><br>\r\n\r\n";
    $message .= esc_html__('If this was a mistake, just ignore this email and nothing will happen.', 'elasticemailsender') . "<br><br>\r\n\r\n";
    $message .= esc_html__('To reset your password, visit the following address:', 'elasticemailsender') . "\r\n\r\n";
    $message .= '<a href="' . esc_url(network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login')) . "\">" . esc_html__('Reset your Password', 'elasticemailsender') . "</a>\r\n";

    return $message;
}

add_filter("retrieve_password_message", "ees_custom_password_reset", 99, 4);