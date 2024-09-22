<?php
defined('EE_ADMIN_5120420526') or die('No direct access allowed.');

wp_enqueue_style('eesender-bootstrap-grid');
wp_enqueue_style('eesender-css');
wp_enqueue_script('eesender-jquery');
wp_enqueue_script('eesender-send-test');

if (isset($_GET['settings-updated'])):
    ?>
    <div id="message" class="updated">
        <p><strong>
                <?php _e('Settings saved.', 'elastic-email-sender') ?>
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
                                <?php _e('Send test', 'elastic-email-sender') ?>
                            </h1>
                        </div>
                    </div>

                    <div class="ee-send-test-container">
                        <p class="ee-p test-description">
                            <?php _e('Sending this testing email will provide you with the necessary information about the ability to send emails from your account as well as email and contact status. The email provided by you will be added to your All Contacts list, then the testing message will be sent to this contact. Be aware that if you are charged by the number of emails sent, sending these testing messages will have an impact on your credits.', 'elstic-email-sender') ?>
                        </p>
                        <?php
                        $protocol = !isset($_SERVER['HTTPS']) ? 'http://' : 'https://';
                        $url = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
                        ?>
                        <form action="<?= $url ?>" method="post">
                            <div class="form-box">
                                <input type="hidden" name="eeSendTest" value="sendtest">
                                <div class="form-group">
                                    <label>
                                        <?php _e('Email to', 'elastic-email-sender') ?>
                                    </label>
                                    <input type="email" name="to" id="to"
                                        placeholder="<?php _e('Email to', 'elastic-email-sender') ?>">
                                </div>
                                <span class="valid hide" id="invalid_email"></span>
                                <div class="form-group">
                                    <label>
                                        <?php _e('Test message', 'elastic-email-sender') ?>
                                    </label>
                                    <textarea name="message" id="message" rows="5" cols="40"
                                        placeholder="<?php _e('Test message', 'elastic-email-sender') ?>"></textarea>
                                </div>
                                <span class="valid hide" id="invalid_message"></span>
                                <input class="ee-button-test" type="submit" id="sendTest"
                                    value="<?php _e('Send test', 'elastic-email-sender') ?>">
                            </div>
                        </form>

                        <div class="">
                            <?php
                            if (isset($_POST["eeSendTest"]) && $_POST["eeSendTest"] === "sendtest") {
                                $to = $_POST['to'];
                                $message = preg_replace('/\\\\(["\'])/', '$1', $_POST['message']);
                                $subject = 'Elastic Email Sender send test';

                                try {
                                    $ee_eemail = new eemail();
                                    $send = eemail::send($to, $subject, $message, null, null, true);

                                    if ($send) {
                                        echo '<p class="ee-info-box send-status-success">Success</p>';
                                    } else {
                                        echo '<p class="ee-info-box send-status-failed">Error</p>';
                                    }

                                } catch (Exception $e) {
                                    echo "Error (MIQU0dq30JXAm7MSyegZDpMyg): " . $e->getMessage();
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