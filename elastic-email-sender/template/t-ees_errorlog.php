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
        <p><strong><?php esc_html_e('Settings saved.', 'elasticemailsender') ?></strong></p>
    </div>
<?php endif; ?>

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
                <h1><?php esc_html_e('Error logs', 'elasticemailsender') ?></h1>
                </div>
            </div>

            <div class="ee-log-container">
           
            <?php
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function is local to this template file
            function ees_show_clean_button() {
                echo '<div class="ee-clean-log-box">
                <span class="ee-button-clean-log" id="eeCleanErrorLog">' . esc_html__("Clean log", "elasticemailsender") . '</span>
                </div>';
            } ?>

            <?php
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Function is local to this template file
            function ees_show_logs() {
                global $wpdb;
                $table = $wpdb->prefix . 'elasticemail_log';
                $table = esc_sql($table);
                // phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
                // Table name is escaped with esc_sql(). wpdb->prepare() cannot be used for table names. Direct query is necessary for error log display.
                $sql = "SELECT * FROM {$table}";
                $results = $wpdb->get_results($sql);
                // phpcs:enable
                
                if(sizeof($results) >= 1) {
                    ees_show_clean_button();
                    foreach( $results as $result ) {
                       echo '<div class="ee-single-log"><div>' . esc_html($result->date) . ' => ' . esc_html($result->error) . '</div></div>';
                    }
                } else {
                    echo '<div class="ee-single-log__empty">
                    <div>' . esc_html__('Cool! You don\'t have any error logs.', 'elasticemailsender') . '</div></div>';
                }
            } 

            ees_show_logs(); 
            ?>

            </div>

        <?php }
        } else {
            include 't-ees_apidisabled.php';
        }?>

    </div>

    <?php
    include 't-ees_marketing.php';
    ?>

</div>

</div>
      