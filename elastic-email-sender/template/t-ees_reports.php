<?php
defined('EE_ADMIN_5120420526') or die('No direct access allowed.');

wp_enqueue_script('eesender-jquery');
wp_enqueue_script('eesender-chart-script');
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
        <?php if (get_option('ees-connecting-status') === 'disconnected') {
            include 't-ees_connecterror.php';
        } else { ?>
            <div class="ee-header">
                <div class="ee-pagetitle">
                    <h1><?php esc_html_e('Reports', 'elasticemailsender') ?></h1>
                </div>
            </div>

            <?php
            if ((empty($error)) === true) {
                if (isset($_POST['daterange'])) {
                    // Verify nonce
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- wp_verify_nonce() validates the nonce directly, sanitization is not needed
                    if (!isset($_POST['ees_reports_nonce']) || !wp_verify_nonce(wp_unslash($_POST['ees_reports_nonce']), 'ees_reports')) {
                        wp_die(esc_html__('Security check failed.', 'elasticemailsender'));
                    }

                    // Check user capabilities
                    if (!current_user_can('manage_options')) {
                        wp_die(esc_html__('You do not have permission to perform this action.', 'elasticemailsender'));
                    }

                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                    $ees_daterange = sanitize_text_field(wp_unslash($_POST['daterange']));
                    update_option('daterangeselect', $ees_daterange);
                }
                ?>

                <div class="ee-select-form-box">
                    <form name="form" id="daterange" method="post">
                        <?php wp_nonce_field('ees_reports', 'ees_reports_nonce'); ?>
                        <?php esc_html_e('Date range:', 'elasticemailsender') ?>
                        <select id="daterange-select" name="daterange" onchange="this.form.submit()">
                            <option value="last-7d" <?php if (get_option('daterangeselect') == 'last-7d') echo 'selected' ?>><?php esc_html_e('Last 7 days', 'elasticemailsender') ?></option>
                            <option value="last-14d" <?php if (get_option('daterangeselect') == 'last-14d') echo 'selected' ?>><?php esc_html_e('Last 14 days', 'elasticemailsender') ?></option>
                            <option value="last-30d" <?php if (get_option('daterangeselect') == 'last-30d') echo 'selected' ?>><?php esc_html_e('Last 30 days', 'elasticemailsender') ?></option>
                            <option value="last-3m" <?php if (get_option('daterangeselect') == 'last-3m') echo 'selected' ?>><?php esc_html_e('Last 3 months', 'elasticemailsender') ?></option>
                            <option value="last-6m" <?php if (get_option('daterangeselect') == 'last-6m') echo 'selected' ?>><?php esc_html_e('Last 6 months', 'elasticemailsender') ?></option>
                            <option value="last-1y" <?php if (get_option('daterangeselect') == 'last-1y') echo 'selected' ?>><?php esc_html_e('Last year', 'elasticemailsender') ?></option>
                        </select>
                    </form>
                </div>

                <div class="ee-reports-container">

                    <?php
                    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                    $ees_chartHide = false;
                    if ((empty($total) === true || $total === 0)):
                        echo '
                            <div class="empty-chart">
                                <img src="' . esc_url(plugins_url('/src/img/template-empty.svg', dirname(__FILE__))) . '" >
                                <p class="ee-p">' . esc_html__("No data to display. Send campaign to see results.", "elasticemailsender") . '</p>
                            </div>';
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variable is local to this template file
                        $ees_chartHide = true;
                    endif;
                    ?>
                    <div class="ee-reports-list" style="display: <?php echo esc_attr($ees_chartHide ? 'none' : 'block'); ?>">
                        <div id="canvas-holder" style="width:80%;">
                            <canvas id="chart-area"/>
                        </div>

                        <script>

                            var chartColors = {
                                color1: '#c6f6d5',
                                color2: '#feebc8',
                                color3: '#bee3f8',
                                color4: '#e9d8fd',
                                color5: '#fdd5cb'
                            };
                            var chartColorsBorder = {colorBorder1: '#F1F1F1'};
                            var config = {
                                type: 'doughnut',
                                data: {
                                    labels: ["<?php echo esc_js(__('Delivered', 'elasticemailsender')); ?>", "<?php echo esc_js(__('Opened', 'elasticemailsender')); ?>", "<?php echo esc_js(__('Clicked', 'elasticemailsender')); ?>", "<?php echo esc_js(__('Unsubscribed', 'elasticemailsender')); ?>", "<?php echo esc_js(__('Bounced', 'elasticemailsender')); ?>"],
                                    datasets: [{
                                        label: '# of Votes',
                                        data: [
                                            <?php if (is_numeric($delivered)): echo absint($delivered); else:echo 100000;endif;?>,
                                            <?php if (is_numeric($opened)): echo absint($opened); else:echo 85000;endif;?>,
                                            <?php if (is_numeric($clicked)): echo absint($clicked); else:echo 95000;endif;?>,
                                            <?php if (is_numeric($unsubscribed)): echo absint($unsubscribed); else:echo 4000;endif;?>,
                                            <?php if (is_numeric($bounced)): echo absint($bounced); else:echo 4000;endif;?>],
                                        backgroundColor: [
                                            chartColors.color1,
                                            chartColors.color2,
                                            chartColors.color3,
                                            chartColors.color4,
                                            chartColors.color5
                                        ],
                                        borderColor: [
                                            chartColorsBorder.colorBorder1,
                                            chartColorsBorder.colorBorder1,
                                            chartColorsBorder.colorBorder1,
                                            chartColorsBorder.colorBorder1,
                                            chartColorsBorder.colorBorder1
                                        ],
                                        borderWidth: 1.5
                                    }]
                                },
                                options: {responsive: true}
                            };
                            window.onload = function () {
                                var ctx = document.getElementById("chart-area").getContext("2d");
                                window.myPie = new Chart(ctx, config);
                            };
                        </script>
                    </div>
                </div>

            <?php }} ?>
    </div>

    <?php
    include 't-ees_marketing.php';
    ?>

</div>
</div>
