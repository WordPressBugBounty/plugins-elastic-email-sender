<?php

define('EE_ADMIN_5120420526', true);

/**
 * Description of eeadmin
 *
 * @author ElasticEmail
 */
class eeadmin5120420526
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $defaultOptions = ['ee_enable' => 'no', 'ee_apikey' => null, 'ee_emailtype' => 'marketing'],
    $options,
    $initAPI = false;
    public $theme_path;

    /**
     * Start up
     */
    public function __construct($pluginpath)
    {
        $this->theme_path = $pluginpath;
        add_action('init', [$this, 'WooCommerce_email']);
        add_action('init', [$this, 'WooCommerce_name']);
        add_action('admin_init', [$this, 'init_options']);
        $this->options = get_option('ee_options', $this->defaultOptions);

        add_action('admin_menu', [$this, 'add_menu']);
    }

    // Added admin menu
    public function add_menu()
    {
        add_action('admin_enqueue_scripts', array($this, 'custom_admin_scripts'));

        add_menu_page(esc_html__('Elastic Email Sender', 'elasticemailsender'), esc_html__('Elastic Email Sender', 'elasticemailsender'), 'manage_options', 'elasticemail-settings', [$this, 'show_settings'], plugins_url('/src/img/icon.png', dirname(__FILE__)));
        add_submenu_page('elasticemail-settings', esc_html__('Settings', 'elasticemailsender'), esc_html__('Settings', 'elasticemailsender'), 'manage_options', 'elasticemail-settings', [$this, 'show_settings']);
        add_submenu_page('elasticemail-settings', esc_html__('Reports', 'elasticemailsender'), esc_html__('Reports', 'elasticemailsender'), 'manage_options', 'elasticemail-report', [$this, 'show_reports']);
        add_submenu_page('elasticemail-settings', esc_html__('Send test', 'elasticemailsender'), esc_html__('Send test', 'elasticemailsender'), 'manage_options', 'elasticemail-send-test', [$this, 'show_sendtest']);
        add_submenu_page('elasticemail-settings', esc_html__('Error log', 'elasticemailsender'), esc_html__('Error log', 'elasticemailsender'), 'manage_options', 'elasticemail-error-log', [$this, 'show_errorlog']);
    }

    public function custom_admin_scripts()
    {
        if (is_admin()) {

            $plugin_path = plugins_url() . '/' . get_option('ees_plugin_dir_name');
            wp_register_script('eesender-jquery', false, array('jquery'), '3.3', true);
            wp_register_script('eesender-chart-script', $plugin_path . '/lib/chart.min.js', '', 2.7, true);
            wp_register_script('eesender-send-test', $plugin_path . '/dist/ees_sendTest.min.js', '', 1.2, true);
            wp_register_style('eesender-bootstrap-grid', $plugin_path . '/lib/bootstrap-grid.min.css', '', 4.1, false);
            wp_register_style('eesender-css', $plugin_path . '/dist/ees_admin.min.css', '', 1.2, false);

            wp_localize_script(
                'eesender-send-test',
                'ees_localize_data',
                [
                    'adminUrl' => esc_url(get_admin_url()),
                    'nonce' => wp_create_nonce('ee_clean_error_log'),
                ]
            );
        }
    }

    // Load Elastic Email settings
    public function show_settings()
    {
        $this->initAPI();
        try {
            $accountAPI = new \ElasticEmailClient\Account();
            $error = null;
            $account = $accountAPI->Load();
            $this->statusToSendEmail();
        } catch (ElasticEmailClient\ApiException $e) {
            $error = $e->getMessage();
            $account = array();
        }

        if (isset($account['data']['email'])) {
            update_option('ee_from_email', $account['data']['email']);
        }

        if (!filter_var(get_option('ee_is_created_channels'), FILTER_VALIDATE_BOOLEAN)) {

            $channelsList = [
                'Elastic Email Sender',
                'Elastic Email - Send Test',
            ];

            foreach ($channelsList as $channelName) {
                $this->addChannel($channelName);
            }

            add_option('ee_is_created_channels', true);
        }

        require_once($this->theme_path . '/template/t-ees_admin.php');
        return;
    }

    public function addChannel($name)
    {
        $this->initAPI();
        try {
            $channelAPI = new \ElasticEmailClient\Channel();
            $channelAPI->Add($name);
        } catch (ElasticEmailClient\ApiException $e) {
            // Channel creation failed, silently continue
        }
    }

    public function statusToSendEmail()
    {
        $this->initAPI();
        try {
            $statusToSendEmailAPI = new \ElasticEmailClient\Account();
            $statusToSendEmail = $statusToSendEmailAPI->GetAccountAbilityToSendEmail();
            if (isset($statusToSendEmail['data'])) {
                update_option('elastic-email-to-send-status', $statusToSendEmail['data']);
            }
        } catch (Exception $ex) {
            // Status check failed, silently continue
        }
        return;
    }

    //Initialization Elastic Email API
    public function initAPI()
    {
        if ($this->initAPI === true) {
            return;
        }

        //Loads Elastic Email Client
        require_once($this->theme_path . '/api/ElasticEmailClient.php');
        if (empty($this->options['ee_apikey']) === false) {
            \ElasticEmailClient\ApiClient::SetApiKey($this->options['ee_apikey']);
        }
        $this->initAPI = true;
    }

    public function show_reports()
    {
        $this->initAPI();

        $allowed_ranges = [
            'last-7d'  => '-7 days',
            'last-14d' => '-14 days',
            'last-30d' => '-30 days',
            'last-3m'  => '-3 months',
            'last-6m'  => '-6 months',
            'last-1y'  => '-1 year',
        ];
        $date_range = get_option('daterangeselect', 'last-7d');
        if (!array_key_exists($date_range, $allowed_ranges)) {
            $date_range = 'last-7d';
        }

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

            $requested_range = sanitize_text_field(wp_unslash($_POST['daterange']));
            if (array_key_exists($requested_range, $allowed_ranges)) {
                $date_range = $requested_range;
                update_option('daterangeselect', $date_range);
            }
        }

        $to = gmdate('c');
        $from = gmdate('c', strtotime($allowed_ranges[$date_range]));

        try {
            $LogAPI = new \ElasticEmailClient\Log();
            $error = null;
            $LogAPI_json = $LogAPI->Summary($from, $to, null, null, null);

            if ($LogAPI_json) {
                $total = $LogAPI_json['data']['logstatussummary']['emailtotal'];
                $delivered = $LogAPI_json['data']['logstatussummary']['delivered'];
                $opened = $LogAPI_json['data']['logstatussummary']['opened'];
                $bounced = $LogAPI_json['data']['logstatussummary']['bounced'];
                $clicked = $LogAPI_json['data']['logstatussummary']['clicked'];
                $unsubscribed = $LogAPI_json['data']['logstatussummary']['unsubscribed'];
            } else {
                $total = 1;
                $delivered = 1;
                $opened = 1;
                $bounced = 1;
                $clicked = 1;
                $unsubscribed = 1;
            }
        } catch (ElasticEmailClient\ApiException $e) {
            $error = $e->getMessage();
        }
        //Loads the settings template
        require_once($this->theme_path . '/template/t-ees_reports.php');
        return;
    }

    public function show_sendtest()
    {
        require_once($this->theme_path . '/template/t-ees_sendtest.php');
        require_once($this->theme_path . '/class/ees_admin.php');
    }

    public function show_errorlog()
    {
        require_once($this->theme_path . '/template/t-ees_errorlog.php');
    }

    //Initialization custom options
    public function init_options()
    {
        register_setting(
            'ee_option_group', //Option group
            'ee_options', //Option name
            [$this, 'valid_options']   //Sanitize callback
        );
        //INIT SECTION
        add_settings_section(
            'setting_section_id',
            null,
            null,
            'ee-settings'
        );

        //INIT FIELD
        add_settings_field(
            'ee_enable',
            esc_html__('Select mailer:', 'elasticemailsender'),
            [$this, 'enable_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_enable'
            ]
        );

        add_settings_field(
            'ee_apikey',
            esc_html__('Elastic Email API Key:', 'elasticemailsender'),
            [$this, 'input_apikey'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_apikey',
                'width' => 280
            ]
        );

        add_settings_field(
            'ee_emailtype',
            esc_html__('Email type:', 'elasticemailsender'),
            [$this, 'emailtype_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_emailtype'
            ]
        );

        add_settings_field(
            'ee_mime_type',
            esc_html__('MIME type:', 'elasticemailsender'),
            [$this, 'mimetype_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_mime_type_input'
            ]
        );

        if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_settings_field(
                'ee_override_wooCommerce',
                esc_html__('Override', 'elasticemailsender'),
                [$this, 'override_wooCommerce_input'],
                'ee-settings',
                'setting_section_id',
                [
                    'input_name' => 'ee_override_wooCommerce',
                    'width' => 280
                ]
            );
        }

        add_settings_field(
            'ee_from_name_config',
            esc_html__('From name (default empty):', 'elasticemailsender'),
            [$this, 'from_name_config_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_from_name_config',
                'width' => 280
            ]
        );

        add_settings_field(
            'ee_from_email_config',
            esc_html__('Email FROM (default empty):', 'elasticemailsender'),
            [$this, 'from_email_config_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_from_email_config',
                'width' => 280
            ]
        );

        add_settings_field(
            'ee_channel_name',
            esc_html__('Channel name:', 'elasticemailsender'),
            [$this, 'channel_name_input'],
            'ee-settings',
            'setting_section_id',
            [
                'input_name' => 'ee_channel_name',
                'width' => 280
            ]
        );

    }

    /**
     * Validation plugin options during their update data
     * @param type $input
     * @return type
     */
    public function valid_options($input)
    {
        // If api key have * then use old api key
        if (strpos($input['ee_apikey'], '*') !== false) {
            $input['ee_apikey'] = $this->options['ee_apikey'];
        } else {
            $input['ee_apikey'] = sanitize_key($input['ee_apikey']);
        }

        if ($input['ee_enable'] !== 'yes') {
            $input['ee_enable'] = 'no';
        }
        return $input;
    }

    /**
     * Get the apikey option and print one of its values
     */
    public function input_apikey($arg)
    {
        $apikey = $this->options[$arg['input_name']];
        update_option('ee-apikey', filter_var($apikey, FILTER_SANITIZE_NUMBER_INT));
        if (empty($apikey) === false) {
            $apikey = '**********' . substr($apikey, strlen($apikey) - 5, strlen($apikey));
        }
        printf('
        <input
            type="text"
            id="title"
            name="ee_options[%s]"
            value="%s"
            style="%s"
        />',
            esc_attr($arg['input_name']),
            esc_attr($apikey),
            (isset($arg['width']) && $arg['width'] > 0) ? 'width:' . absint($arg['width']) . 'px' : ''
        );
    }

    /**
     * Displays the settings mailer
     */
    public function enable_input($arg)
    {
        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $value = 'no';
        } else {
            $value = $this->options[$arg['input_name']];
        }
        echo '<div class="ee-admin-settings-radio-block">
               <div class="ee-admin-settings-radio-item">
                <input
                    type="radio"
                    name="ee_options[' . esc_attr($arg['input_name']) . ']"
                    value="yes"
                    ' . (($value === 'yes') ? 'checked' : '') . '
                />
                <span>' . esc_html__('Send all WordPress emails via Elastic Email API.', 'elasticemailsender') . '</span>
               </div>

               <div class="ee-admin-settings-radio-item">
                <input
                    type="radio"
                    name="ee_options[' . esc_attr($arg['input_name']) . ']"
                    value="no"
                    ' . (($value === 'no') ? 'checked' : '') . '
                />
                <span>' . esc_html__('Use the defaults Wordpress function to send emails.', 'elasticemailsender') . '</span>
              </div>
             </div>';
    }

    /**
     * Displays the settings email type
     */
    public function emailtype_input($arg)
    {
           if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $type = 'marketing';
            update_option('ee_send-email-type', $type);
        } else {
            $type = $this->options[$arg['input_name']];
            update_option('ee_send-email-type', $type);
        }
        echo '
                <div class="ee-admin-settings-radio-inline">
                    <input
                        type="radio"
                        name="ee_options[' . esc_attr($arg['input_name']) . ']"
                        value="marketing"
                        ' . (($type === 'marketing') ? 'checked' : '') . '
                    />
                    <span>' . esc_html__('Marketing', 'elasticemailsender') . '</span>

                    <input
                        type="radio"
                        name="ee_options[' . esc_attr($arg['input_name']) . ']"
                        value="transactional"
                        ' . (($type === 'transactional') ? 'checked' : '') . '
                    />
                    <span>' . esc_html__('Transactional', 'elasticemailsender') . '</span>
                </div>';
    }

    /**
     * Displays the settings MIME Types
     */
    public function mimetype_input($arg)
    {
        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $mimetype = 'auto';
            update_option('ee_mimetype', $mimetype);
        } else {
            $mimetype = $this->options[$arg['input_name']];
            update_option('ee_mimetype', $mimetype);
        }
        echo '
                <div class="ee-admin-settings-radio-inline">
                    <input
                        type="radio"
                        name="ee_options[' . esc_attr($arg['input_name']) . ']"
                        value="auto"
                        ' . (($mimetype === 'auto') ? 'checked' : '') . '
                    />
                    <span>' . esc_html__('Auto (default)', 'elasticemailsender') . '</span>

                    <input
                        type="radio"
                        name="ee_options[' . esc_attr($arg['input_name']) . ']"
                        value="plaintext"
                        ' . (($mimetype === 'plaintext') ? 'checked' : '') . '
                    />
                    <span style="padding-right: 10px">' . esc_html__('plain/text', 'elasticemailsender') . '</span>

                    <input
                    type="radio"
                    name="ee_options[' . esc_attr($arg['input_name']) . ']"
                    value="texthtml"
                    ' . (($mimetype === 'texthtml') ? 'checked' : '') . '
                    />
                    <span>' . esc_html__('text/html', 'elasticemailsender') . '</span>

                </div>';
    }

    /**
     * Displays the settings from name
     */
    public function from_name_config_input($arg)
    {
        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $config_from_name = '';
            update_option('ee_config_from_name', null);
        } else {
            $config_from_name = $this->options[$arg['input_name']];
            update_option('ee_config_from_name', htmlspecialchars($config_from_name));

            /**Adding filter  to override wp_mail_from_name field , if the option is checked */
            if (get_option('ee_config_override_wooCommerce')) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce_name is a WooCommerce plugin hook
                do_action('WooCommerce_name');
            }
        }
        echo '<input
                type="text"
                name="ee_options[' . esc_attr($arg['input_name']) . ']"
                placeholder="' . esc_attr__('From name', 'elasticemailsender') . '"
                value="' . esc_attr($config_from_name) . '"
                style="width:' . absint($arg['width']) . 'px"
              />';
    }

    /**
     * Displays the settings email FROM
     */
    public function from_email_config_input($arg)
    {
        $valid = true;

        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $config_from_email = '';
            update_option('ee_config_from_email', null);

        } else {
            $config_from_email = $this->options[$arg['input_name']];

            if (filter_var($config_from_email, FILTER_VALIDATE_EMAIL)) {
                $valid = true;
                update_option('ee_config_from_email', $config_from_email);
            } else {
                $valid = false;
                $config_from_email = '';
            }

            /**Adding filter  to override wp_mail_from field , if the option is checked */
            if (get_option('ee_config_override_wooCommerce')) {
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce_email is a WooCommerce plugin hook
                do_action('WooCommerce_email');
            }
        }
        echo '<input
                type="text"
                name="ee_options[' . esc_attr($arg['input_name']) . ']"
                placeholder="' . esc_attr__('Email address FROM', 'elasticemailsender') . '"
                value="' . esc_attr($config_from_email) . '"
                style="width:' . absint($arg['width']) . 'px"
              />';

        if (!$valid) {
            esc_html_e(' is not a valid email address.', 'elasticemailsender');
        }
    }

    /**
     * Displays the settings channel name:
     */
    public function channel_name_input($arg)
    {
        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            $channel_name = 'Elastic Email Sender';
            update_option('ee_channel_name', 'Elastic Email Sender');
        } else {
            $channel_name = $this->options[$arg['input_name']];
            update_option('ee_channel_name', htmlspecialchars($channel_name));
        }
        echo '<input
                type="text"
                name="ee_options[' . esc_attr($arg['input_name']) . ']"
                placeholder="' . esc_attr__('Channel name', 'elasticemailsender') . '"
                value="' . esc_attr($channel_name) . '"
                style="width:' . absint($arg['width']) . 'px"
              />';
    }

    /**
     * Display checkbox to  override WooCommerce email 'from' and 'fromName'
     * Display checkbox to  override WooCommerce email 'from' and 'fromName'
     */
    public function override_wooCommerce_input($arg)
    {
        if (!isset($this->options[$arg['input_name']]) || empty($this->options[$arg['input_name']])) {
            update_option('ee_config_override_wooCommerce', 0);
            $override = 0;
        } else {
            update_option('ee_config_override_wooCommerce', 1);
            $override = 1;
        }
        echo '<div class="ee-admin-settings-radio-block">
                <input
                    type="checkbox"
                    name="ee_options[' . esc_attr($arg['input_name']) . ']"
                    value="yes" ' . (($override === 1) ? 'checked' : '') . '
                />
                <span>' . esc_html__('WooCommerce fields "Email from" and " From name"', 'elasticemailsender') . '</span>
              </div>';
    }

    /**function that sets sender email based on the FROM email input , also setting FROM email to send test feature */
    public function set_sender_email()
    {
        $sender = get_option('ee_from_email');
        if (!empty(get_option('ee_config_from_email'))) {
            $sender = get_option('ee_config_from_email');
        }
        return $sender;
    }

    /** function that sets from name based on the form name input , also setting FROM name to send test feature */
    public function set_sender_name()
    {
        $sender = 'Wordpress';
        if (!empty(get_option('ee_config_from_name'))) {
            $sender = get_option('ee_config_from_name');
        }
        return $sender;
    }

    /** function that based on override option and setted FROM email input adds filter for wp_mail_from to override wooCommerce settings */
    public function WooCommerce_email()
    {
        if (get_option('ee_config_override_wooCommerce') && !empty(get_option('ee_config_from_email'))) {
            $wooCommerce_email_original_email = get_option('woocommerce_email_from_address');
            if (!get_option('ee_config_woocommerce_original_email')) {
                add_option('ee_config_woocommerce_original_email', $wooCommerce_email_original_email);
            }

            update_option('woocommerce_email_from_address', $this->set_sender_email());
        } else {
            if (get_option('ee_config_woocommerce_original_email')) {
                update_option('woocommerce_email_from_address', get_option('ee_config_woocommerce_original_email'));
                delete_option('ee_config_woocommerce_original_email');
            }
        }
    }

    /** function that based on override option and setted FROM name input adds filter for wp_mail_from_name to override wooCommerce settings */
    public function WooCommerce_name()
    {
        if (get_option('ee_config_override_wooCommerce') && !empty(get_option('ee_config_from_name'))) {
            $wooCommerce_email_original_name = get_option('woocommerce_email_from_name');
            if (!get_option('ee_config_woocommerce_original_name')) {
                add_option('ee_config_woocommerce_original_name', $wooCommerce_email_original_name);
            }
            update_option('woocommerce_email_from_name', $this->set_sender_name());
        } else {
            if (get_option('ee_config_woocommerce_original_name')) {
                update_option('woocommerce_email_from_name', get_option('ee_config_woocommerce_original_name'));
                delete_option('ee_config_woocommerce_original_name');
            }
        }
    }
}
