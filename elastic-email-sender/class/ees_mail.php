<?php

class eemail
{
    static $options = array(),
    $conflict = false;

    public static function on_load($pluginpath)
    {
        self::$options = get_option('ee_options');

        if (self::is_configured() === false) {
            return;
        }

        require_once($pluginpath . '/api/ElasticEmailClient.php');
        \ElasticEmailClient\ApiClient::SetApiKey(self::getOption('ee_apikey'));

        add_filter('pre_wp_mail', array(__CLASS__, 'pre_wp_mail_handler'), 10, 2);
    }

    public static function pre_wp_mail_handler($null, $atts)
    {
        if ($null !== null) {
            return $null;
        }

        if (self::is_configured() === false) {
            return null;
        }

        extract($atts);

        try {
            $rs = self::send($to, $subject, $message, $headers, $attachments, $ee_channel = null);

            if ($rs !== true) {
                return self::wp_mail_native($to, $subject, $message, $headers, $attachments, $rs);
            }

            return $rs;
        } catch (Exception $e) {
            return self::wp_mail_native($to, $subject, $message, $headers, $attachments, $e->getMessage());
        }
    }

    static function send($to, $subject, $message, $headers, $attachments, $ee_channel = null)
    {
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail is a WordPress core filter
        $atts = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));

        if (isset($atts['to'])) {
            $to = $atts['to'];
        }
        if (isset($atts['subject'])) {
            $subject = $atts['subject'];
        }
        if (isset($atts['message'])) {
            $message = $atts['message'];
        }
        if (isset($atts['headers'])) {
            $headers = $atts['headers'];
        }
        if (isset($atts['attachments'])) {
            $attachments = $atts['attachments'];
        }
        if (!is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments || []));
        }
        $cc = $bcc = array();
        if (empty($headers)) {
            $headers = array();
        } else {
            if (!is_array($headers)) {
                $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempheaders = $headers;
            }
            $headers = array();
            $j = 1;
            if (!empty($tempheaders)) {
                foreach ((array) $tempheaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (false !== stripos($header, 'boundary=')) {
                            $parts = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
                        }
                        continue;
                    }
                    list($name, $content) = explode(':', trim($header), 2);
                    $name = trim($name);
                    $content = trim($content);
                    switch (strtolower($name)) {
                        case 'from':
                            list($from_email, $from_name) = self::getEmailAndName($content);
                            break;
                        case 'content-type':
                            if (strpos($content, ';') !== false) {
                                list($type, $charset_content) = explode(';', $content);
                                $content_type = trim($type);
                                if (false !== stripos($charset_content, 'charset=')) {
                                    $charset = trim(str_replace(array('charset=', '"'), '', $charset_content));
                                } elseif (false !== stripos($charset_content, 'boundary=')) {
                                    $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset_content));
                                    $charset = '';
                                }
                            } elseif ('' !== trim($content)) {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array) $cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array) $bcc, explode(',', $content));
                            break;
                        case 'reply-to':
                            list($reply_to, $reply_to_name) = self::getEmailAndName($content);
                            break;
                        default:
                            //custom headers
                            $headers[('header' . $j++)] = sprintf('%1$s: %2$s', trim($name), trim($content));
                            break;
                    }
                }
            }
        }

        if (empty(get_option('ee_config_from_name'))) {
            if (!isset($from_name)) {
                $from_name = 'Wordpress';
            }
        } else {
            $from_name = get_option('ee_config_from_name');
        }

        if (empty(get_option('ee_config_from_email'))) {
            if (!isset($from_email)) {
                $from_email = 'wordpress@' . self::getDefaultDomain();
                update_option('ee_from_email', $from_email);
            }
        } else {
            $from_email = get_option('ee_config_from_email');
            update_option('ee_from_email', $from_email);
        }

        if (!isset($content_type)) {
            $content_type = 'text/plain';
        }

        $lostpassword = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
        if (isset($lostpassword)) {
            if ($lostpassword === 'lostpassword') {
                $content_type = 'text/html';
            }
        }
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_content_type is a WordPress core filter
        $content_type = apply_filters('wp_mail_content_type', $content_type);

        if (!isset($charset)) {
            $charset = get_bloginfo('charset');
        }
        if (!is_array($to)) {
            $to = array_merge(explode(',', $to));
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_from is a WordPress core filter
        $from_email = apply_filters('wp_mail_from', $from_email);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_from_name is a WordPress core filter
        $from_name = apply_filters('wp_mail_from_name', $from_name);
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_charset is a WordPress core filter
        $charset = apply_filters('wp_mail_charset', $charset);

        $Email = new \ElasticEmailClient\Email();

        if (!isset($reply_to)) {
            $reply_to = '';
        }
        if (!isset($reply_to_name)) {
            $reply_to_name = '';
        }

        $ee_channel_name = empty(get_option('ee_channel_name')) ? 'Elastic Email Sender' : get_option('ee_channel_name');
        $ee_channel = ($ee_channel === null) ? $ee_channel_name : 'Elastic Email - Send Test';

        $emailType = (get_option('ee_send-email-type') === 'transactional') ? true : false;

        $searchword = '';
        $matches = [];
        foreach ($headers as $k => $v) {
            if (preg_match("/\b$searchword\b/i", $v)) {
                $matches[$k] = $v;
            }
        }

        $fname = $from_name;
        $femail = $from_email;

        if (filter_var($from_name, FILTER_VALIDATE_EMAIL) && is_string($from_email) && $from_email !== "" && !filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
            $fname = $from_email;
            $femail = $from_name;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_content_type is a WordPress core filter
        $content_type = apply_filters('wp_mail_content_type', 'text/html');
        switch (get_option('ee_mimetype')) {
            case 'texthtml':
                $bodyHtml = $message;
                $bodyText = $message;
                break;
            case 'plaintext':
                // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wp_mail_content_type is a WordPress core filter
                $content_type = apply_filters('wp_mail_content_type', 'text/plain');
                $bodyHtml = null;
                $bodyText = $message;
                break;
            case 'auto':
                $bodyHtml = $message;
                $bodyText = $message;
                break;
            default:
                $bodyHtml = $message;
                $bodyText = $message;
        }

        $emailsend = $Email->Send(
            $subject, // 01 $subject
            $femail, // 02 $from
            $fname, // 03 $fromName
            null, // 04 $sender
            null, // 05 $senderName
            null, // 06 $msgFrom
            null, // 07 $msgFromName
            $reply_to, // 08 $replyTo
            $reply_to_name, // 09 $replyToName
            array(), // 10 array $to
            $to, // 11 array $msgTo
            $cc, // 12 array $msgCC
            $bcc, // 13 array $msgBcc
            array(), // 14 array $lists
            array(), // 15 array $segments
            null, // 16 $mergeSourceFilename
            $ee_channel, // 17 $channel
            $bodyHtml, // 18 $bodyHtml
            $bodyText, // 19 $bodyText
            $charset, // 20 $charset
            null, // 21 $charsetBodyHtml
            null, // 22 $charsetBodyText
            ApiTypes\EncodingType::None, // 23 $encodingType
            null, // 24 $template
            $attachments, // 25 array $attachmentFiles
            $headers, // 26 array $headers
            null, // 27 $postBack
            array(), // 28 array $merge
            null, // 29 $timeOffSetMinutes
            null, // 30 $poolName
            $emailType // 31 $isTransactional
        );

        if (isset($emailsend)) {
            if ($emailsend == TRUE) {
                return true;
            } else {
                return false;
            }
        }

    }

    static function wp_mail_native($to, $subject, $message, $headers, $attachments, $error)
    {
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Error logging is intentional for debugging email sending failures
        error_log("eemail::wp_mail_native: $to ($subject) Error: $error");
        
        // Remove our filter temporarily to use native wp_mail
        remove_filter('pre_wp_mail', array(__CLASS__, 'pre_wp_mail_handler'), 10);
        
        // Use native WordPress wp_mail function
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Re-add our filter
        add_filter('pre_wp_mail', array(__CLASS__, 'pre_wp_mail_handler'), 10, 2);
        
        return $result;
    }


    static function is_configured()
    {
        return (self::getOption('ee_enable') === 'yes' && self::getOption('ee_apikey'));
    }

    static function getOption($name, $default = false)
    {
        if (isset(self::$options[$name])) {
            return self::$options[$name];
        }
        return $default;
    }

    static function getEmailAndName($content)
    {
        $address = array('', '');
        $bracket_pos = strpos($content, '<');
        if ($bracket_pos !== false) {
            // Text before the bracketed email is the "From" name.
            if ($bracket_pos > 0) {
                $address[1] = substr($content, 0, $bracket_pos - 1);
                $address[1] = str_replace('"', '', $address[1]);
                $address[1] = trim($address[1]);
            }

            $address[0] = substr($content, $bracket_pos + 1);
            $address[0] = str_replace('>', '', $address[0]);
            $address[0] = trim($address[0]);
            // Avoid setting an empty $email.
        } elseif ('' !== trim($content)) {
            $address[0] = trim($content);
        }
        return $address;
    }

    /* If we don't have an email from the input headers default to wordpress@$sitename
     * Some hosts will block outgoing mail from this address if it doesn't exist but
     * there's no easy alternative. Defaulting to admin_email might appear to be another
     * option but some hosts may refuse to relay mail from an unknown domain. See
     * https://core.trac.wordpress.org/ticket/5007.
     */

    static function getDefaultDomain()
    {
        // Get the site domain and get rid of www.
        $ees_server_name = isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : '';
        $sitename = strtolower($ees_server_name);
        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }
        return $sitename;
    }

}
