<?php
defined('EE_ADMIN_5120420526') or die('No direct access allowed.');

wp_enqueue_style('eesender-bootstrap-grid');
wp_enqueue_style('eesender-css');
wp_enqueue_script('eesender-jquery');
wp_enqueue_script('eesender-send-test');

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- settings-updated is added by WordPress Settings API after saving settings
if (isset($_GET['settings-updated'])):
    ?>
    <div id="message" class="updated">
        <p><strong>
                <?php esc_html_e('Settings saved.', 'elasticemailsender') ?>
            </strong></p>
    </div>
<?php endif;
?>

<div class="eewp-evmab-frvvr">

    <div class="eewp-container">
        <div class="col-12 col-md-12 col-lg-7">
            <?php
            if (get_option('ee_options')["ee_enable"] === 'yes') {

                if (get_option('ees-connecting-status') === 'disconnected') {
                    include 't-ees_connecterror.php';
                } else { ?>
                    <div class="ee-header">
                        <div class="ee-pagetitle">
                            <h1>
                                <?php esc_html_e('Send test', 'elasticemailsender') ?>
                            </h1>
                        </div>
                    </div>

                    <div class="ee-send-test-container">
                        <p class="ee-p test-description">
                            <?php esc_html_e('Sending this testing email will provide you with the necessary information about the ability to send emails from your account as well as email and contact status. The email provided by you will be added to your All Contacts list, then the testing message will be sent to this contact. Be aware that if you are charged by the number of emails sent, sending these testing messages will have an impact on your credits.', 'elasticemailsender') ?>
                        </p>
                        <form action="" method="post">
                            <?php wp_nonce_field('ees_send_test', 'ees_send_test_nonce'); ?>
                            <div class="form-box">
                                <input type="hidden" name="eeSendTest" value="sendtest">
                                <div class="form-group">
                                    <label>
                                        <?php esc_html_e('Email to', 'elasticemailsender') ?>
                                    </label>
                                    <input type="email" name="to" id="to"
                                        placeholder="<?php esc_attr_e('Email to', 'elasticemailsender') ?>">
                                </div>
                                <span class="valid hide" id="invalid_email"></span>
                                <div class="form-group">
                                    <label>
                                        <?php esc_html_e('Test message', 'elasticemailsender') ?>
                                    </label>
                                    <textarea name="message" id="message" rows="5" cols="40"
                                        placeholder="<?php esc_attr_e('Test message', 'elasticemailsender') ?>"></textarea>
                                </div>
                                <span class="valid hide" id="invalid_message"></span>
                                <input class="ee-button-test" type="submit" id="sendTest"
                                    value="<?php esc_attr_e('Send test', 'elasticemailsender') ?>">
                            </div>
                        </form>

                        <div class="">
                            <?php
                            if (isset($_POST["eeSendTest"]) && $_POST["eeSendTest"] === "sendtest") {
                                // Verify nonce
                                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce() validates the nonce directly, sanitization is not needed
                                if (!isset($_POST['ees_send_test_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ees_send_test_nonce']), 'ees_send_test')) {
                                    wp_die(esc_html__('Security check failed.', 'elasticemailsender'));
                                }

                                // Check user capabilities
                                if (!current_user_can('manage_options')) {
                                    wp_die(esc_html__('You do not have permission to perform this action.', 'elasticemailsender'));
                                }

                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this template file
                                $ees_to = isset($_POST['to']) ? sanitize_email(wp_unslash($_POST['to'])) : '';
                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this template file
                                $ees_message = isset($_POST['message']) ? preg_replace('/\\\\(["\'])/', '$1', sanitize_textarea_field(wp_unslash($_POST['message']))) : '';
                                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this template file
                                $ees_subject = 'Elastic Email Sender send test';

                                try {
                                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are local to this template file
                                    $ees_send = eemail::send($ees_to, $ees_subject, $ees_message, null, null, true);

                                    if ($ees_send) {
                                        echo '<p class="ee-info-box send-status-success">Success</p>';
                                    } else {
                                        echo '<p class="ee-info-box send-status-failed">Error</p>';
                                    }

                                } catch (Exception $e) {
                                    echo "Error (MIQU0dq30JXAm7MSyegZDpMyg): " . esc_html($e->getMessage());
                                }
                            }
                            ?>
                        </div>
                    </div>

                <?php }
            } else {
                include 't-ees_apidisabled.php';
            } ?>

        </div>

        <?php
        include 't-ees_marketing.php';
        ?>

    </div>

</div>