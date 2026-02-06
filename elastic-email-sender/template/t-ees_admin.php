<?php
defined('EE_ADMIN_5120420526') or die('No direct access allowed.');

wp_enqueue_style('eesender-bootstrap-grid');
wp_enqueue_style('eesender-css');

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- settings-updated is added by WordPress Settings API after saving settings
if (isset($_GET['settings-updated'])):
    ?>
    <div id="message" class="updated">
        <p><strong><?php esc_html_e('Settings saved.', 'elasticemailsender') ?></strong></p>
    </div>
<?php endif; ?>

<div class="eewp-evmab-frvvr">
<div class="eewp-container">
    <div class="col-12 col-md-12 col-lg-7">
        <div class="ee-header">
            <div class="ee-pagetitle">
                <h1><?php esc_html_e('General Settings', 'elasticemailsender') ?></h1>
            </div>
        </div>

        <p class="ee-p margin-p-xs">
            <?php
            esc_html_e('Welcome to the Elastic Email WordPress Plugin! From now on, you can send your emails in the 
                    fastest and most reliable way! Just one quick step and you will be ready to rock your 
                    subscribers\' inbox. Fill in the details about the main configuration of 
                    Elastic Email connections.', 'elasticemailsender');
            ?>
        </p>

        <form class="settings-box-form" method="post" action="<?php echo esc_url(admin_url('options.php')); ?>">
            <?php
            settings_fields('ee_option_group');
            do_settings_sections('ee-settings');
            ?>
            <table class="form-table">
                <tbody>
                <tr class="table-slim" valign="top">
                    <?php

                    if (get_option('ees-connecting-status') === 'connecting') {
                        if (empty($error) === true) {
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                            $ees_error_stat = 'ee-success';
                        }
                    }
                    if (get_option('ees-connecting-status') === 'disconnected') {
                        if (empty($error) === false) {
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                            $ees_error_stat = 'ee-error';
                        } else {
                            $error = 'false';
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                            $ees_error_stat = 'ee-error';
                        }
                    }

                    ?>
                    <th scope="row"><?php esc_html_e('Connection Test:', 'elasticemailsender') ?></th>
                    <td> <span class="<?php echo esc_attr($ees_error_stat); ?>">

                        <?php
                        if (get_option('ees-connecting-status') === 'connecting') {
                            if (empty($error) === true) {
                                esc_html_e('Connected', 'elasticemailsender');
                            }
                        }
                        if (get_option('ees-connecting-status') === 'disconnected') {
                            if (empty($error) === false) {
                                esc_html_e('Connection error, check your API key. ', 'elasticemailsender');
                            }
                        }
                        ?>

                        </span></td>
                </tr>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>


        <?php if (empty($error) === false) { ?><?php esc_html_e('Do not have an account yet?', 'elasticemailsender') ?> <a
                href="https://elasticemail.com/account#/create-account" target="_blank"
                title="First 1000 emails for free."><?php esc_html_e('Create your account now', 'elasticemailsender') ?></a>!
            <br/>
            <a href="http://elasticemail.com/transactional-email"
               target="_blank"><?php esc_html_e('Tell me more about it', 'elasticemailsender') ?></a>
        <?php } ?>

    </div>

    <?php
    include 't-ees_marketing.php';
    ?>

</div>
</div>
