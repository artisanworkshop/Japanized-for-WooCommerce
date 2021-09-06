<?php
/**
 * Framework Name: Artisan Workshop FrameWork for WooCommerce
 * Framework Version : 2.0.12
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 *
 * @category JP4WC_Framework
 * @author Artisan Workshop
 */

namespace ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12;

use WP_Error;if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('\\ArtisanWorkshop\\WooCommerce\\PluginFramework\\v2_0_12\\JP4WC_Plugin')):
    class JP4WC_Plugin {
        /**
         * Text to be translated by the framework
         *
         * @var array
         */
        public $text_array;

        /**
         * allowed html tag setting.
         *
         * @var array
         */
        public $allowed_html = array(
            'a' => array( 'href' => array (), 'target' => array(), ),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
        );

        /**
         * Constructor for the config class.
         */
        public function __construct() {
            $this->text_array = require( 'config-jp4wc-framework.php' );
            foreach ($this->text_array as $key => $value){
                $this->text_array[$key] = wp_kses($value, $this->allowed_html);
            }
        }

        /**
         * create checkbox input form to compliant Setting API.
         *
         * @param array setting args
         * @param string description
         * @param string option key
         */
        public function jp4wc_setting_input_checkbox($args, $description, $option_key){
            $options = get_option( $option_key );
            ?>
            <label for="<?php echo esc_attr( $args['label_for'] ); ?>">
            <input type="checkbox" id="woocommerce_input_<?php echo esc_attr($args['slug']);?>" name="wc_sbp_options[<?php echo esc_attr($args['slug']);?>]" value="1" <?php if(isset($options[ $args['slug'] ]))checked( $options[ $args['slug'] ], 1 ); ?>>
            <?php if(isset($description))echo esc_attr($description);
            echo '</label>';
        }
        /**
         * create input text form to compliant Setting API.
         *
         * @param array args
         * @param string description
         * @param string option key
         * @param string default value
         */
        public function jp4wc_setting_input_text($args, $description, $option_key, $default_value = null){
            $options = get_option( $option_key );
            ?>
            <label for="<?php echo esc_attr( $args['label_for'] ); ?>">
                <input type="text" id="woocommerce_input_<?php echo esc_attr($args['slug']);?>" name="wc_sbp_options[<?php echo esc_attr($args['slug']);?>]"  size="<?php echo esc_attr( $args['text_num'] ); ?>" value="<?php echo isset( $options[ $args['slug'] ] ) ? esc_html($options[ $args['slug'] ]) : esc_html( $default_value ); ?>" ><br />
                <?php if(isset($description))echo esc_attr($description); ?>
            </label>
            <?php
        }

        /**
         * create option setting.
         *
         * @param string slug
         * @param string plugin prefix
         * @param string array_name
         * @param string ENCRYPT password
         *
         * @return array
         */
        public function jp4wc_option_setting($slug, $prefix, $array_name = null, $password = null){
            $jp4wc_options_setting = null;
            $jp4wc_meta_name = $prefix.$slug;
            if(get_option($jp4wc_meta_name)){
                if(isset($password)){
                    $jp4wc_options_setting = self::decrypt( get_option($jp4wc_meta_name), $password );
                }else{
                    $jp4wc_options_setting = get_option( $jp4wc_meta_name );
                }
            }elseif(get_option($array_name)){
                $setting = get_option($array_name);
                if(isset($password)) {
                    $jp4wc_options_setting = self::decrypt( $setting[$slug], $password );
                }else{
                    $jp4wc_options_setting = $setting[$slug];
                }
            }
            return $jp4wc_options_setting;
        }

        /**
         * crypt AES 256
         *
         * @param string data
         * @param string password
         * @return mixed encrypted data
         */
        public function encrypt( $set_data, $password ) {
            // Set a random salt
            $salt = openssl_random_pseudo_bytes(16);
            $salted = '';
            $dx = '';
            // Salt the key(32) and iv(16) = 48
            while (strlen($salted) < 48) {
                $dx = hash('sha256', $dx.$password.$salt, true);
                $salted .= $dx;
            }
            $key = substr($salted, 0, 32);
            $iv  = substr($salted, 32,16);
            $encrypted_data = openssl_encrypt($set_data, 'AES-256-CBC', $key, 0, $iv);
            return base64_encode($salt . $encrypted_data);
        }

        /**
         * Complex: AES 256
         * @param string Encrypted BASE64 character string
         * @param string password
         * @return string
         */
        public function decrypt($set_data, $password) {
            $data = base64_decode($set_data);
            $salt = substr($data, 0, 16);
            $ct = substr($data, 16);
            $rounds = 3; // depends on key length
            $data00 = $password.$salt;
            $hash = array();
            $hash[0] = hash('sha256', $data00, true);
            $result = $hash[0];
            for ($i = 1; $i < $rounds; $i++) {
                $hash[$i] = hash('sha256', $hash[$i - 1].$data00, true);
                $result .= $hash[$i];
            }
            $key = substr($result, 0, 32);
            $iv  = substr($result, 32,16);
            return openssl_decrypt($ct, 'AES-256-CBC', $key, 0, $iv);
        }


        /**
         * create checkbox input form.
         *
         * @param string slug
         * @param string description
         * @param string prefix
         * @param string array name
         */
        public function jp4wc_input_checkbox($slug, $description, $prefix, $array_name = null){
            ?>
            <?php if(isset($description))echo '<label for="woocommerce_input_'.esc_attr($slug).'">';
            $jp4wc_options_setting = $this->jp4wc_option_setting($slug, $prefix, $array_name);
            ?>
            <input type="checkbox" id="woocommerce_input_<?php echo esc_attr($slug);?>" name="<?php echo esc_attr($slug);?>" value="1" <?php checked( $jp4wc_options_setting, 1 ); ?>>
            <?php if(isset($description))echo wp_kses($description, $this->allowed_html);
            if(isset($description))echo '</label>';
        }
        /**
         * create input select form.
         *
         * @param string slug
         * @param string description
         * @param array  select options
         * @param string prefix
         * @param string array name
         */
        public function jp4wc_input_select($slug, $description, $select_options, $prefix, $array_name = null){
            ?>
            <label for="woocommerce_input_<?php echo esc_attr($slug);?>">
                <?php
                $jp4wc_options_setting = $this->jp4wc_option_setting($slug, $prefix, $array_name);
                echo '<select id="woocommerce_input_'. esc_attr($slug) .'" name="'. esc_attr($slug) .'">';
                foreach($select_options as $key => $value){
                    $checked = '';
                    if($jp4wc_options_setting == $key){
                        $checked = ' selected="selected"';
                    }
                    echo '<option value="'.esc_attr($key).'"'.$checked.'>'.esc_attr($value).'</option>';
                }
                echo '</select><br />';
                echo wp_kses($description, $this->allowed_html); ?>
                </select>
            </label>
            <?php
        }
        /**
         * create input text form.
         *
         * @param string slug
         * @param string description
         * @param number num
         * @param string default value
         * @param string prefix
         * @param string array name
         * @param string password
         */
        public function jp4wc_input_text($slug, $description, $num, $default_value, $prefix, $array_name = null, $password = null){
            $jp4wc_options_setting = $this->jp4wc_option_setting($slug, $prefix, $array_name, $password);
            if($jp4wc_options_setting)$default_value = $jp4wc_options_setting;
            ?>
            <label for="woocommerce_input_<?php echo esc_attr($slug);?>">
                <input type="text" id="woocommerce_input_<?php echo esc_attr($slug);?>" name="<?php echo esc_attr($slug);?>"  size="<?php echo esc_attr($num);?>" value="<?php echo esc_attr($default_value); ?>" ><br />
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
            <?php
        }
        /**
         * create input textarea form.
         *
         * @param string slug
         * @param string description
         * @param string default value
         * @param string prefix
         * @param array input size array
         * @param string array name
         */
        public function jp4wc_input_textarea($slug, $description, $default_value, $prefix, $size_array = array( 'cols' => 55, 'rows' => 4), $array_name = null){
            $jp4wc_options_setting = $this->jp4wc_option_setting($slug, $prefix, $array_name);
            $size_text ='';
            foreach( $size_array as $key => $value ){
                $size_text .= $key.'="'.$value.'" ';
            }
            if($jp4wc_options_setting)$default_value = $jp4wc_options_setting;
            ?>
            <label for="woocommerce_input_<?php echo esc_attr($slug);?>">
                <textarea id="woocommerce_input_<?php echo esc_attr($slug);?>" name="<?php echo esc_attr($slug);?>" <?php echo esc_attr($size_text); ?>><?php echo esc_attr($default_value); ?></textarea><br />
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
            <?php
        }
        /**
         * create input number form.
         *
         * @param string slug
         * @param string description
         * @param string default value
         * @param string prefix
         * @param string array name
         */
        public function jp4wc_input_number($slug, $description, $default_value, $prefix, $array_name = null){
            $jp4wc_options_setting = $this->jp4wc_option_setting($slug, $prefix, $array_name);
            if($jp4wc_options_setting)$default_value = $jp4wc_options_setting;
            ?>
            <label for="woocommerce_input_<?php echo esc_attr($slug);?>">
                <input type="number" id="woocommerce_input_<?php echo esc_attr($slug);?>" name="<?php echo esc_attr($slug);?>" value="<?php echo esc_attr($default_value); ?>" ><br />
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
            <?php
        }
        /**
         * create input time.
         *
         * @param string slug
         * @param string description
         * @param string default_value
         * @param string prefix
         */
        public function jp4wc_input_time($slug, $description, $default_value, $prefix){
            ?>
            <label for="woocommerce_input_<?php echo esc_attr($slug);?>">
                <?php
                $meta_name = $prefix.$slug;
                if(get_option($meta_name)){
                    $options_setting = get_option($meta_name) ;
                }else{
                    $options_setting = $default_value;
                }
                ?>
                <input type="time" id="woocommerce_input_<?php echo esc_attr($slug);?>" name="<?php echo esc_attr($slug);?>" value="<?php echo esc_attr($options_setting); ?>" ><br />
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
        <?php }

        /**
         * create date time.
         *
         * @param array start_date
         * @param array end_date
         * @param string description
         * @param string prefix
         */
        public function jp4wc_input_date_term($start_date, $end_date, $description, $prefix){
            ?>
            <label for="woocommerce_input_date_term">
                <?php
                $start_meta_name = $prefix.$start_date['name'];
                $start_date_value = '';
                if(get_option($start_meta_name)){
                    $start_date_value = get_option($start_meta_name) ;
                }
                $end_date_value = '';
                $end_meta_name = $prefix.$end_date['name'];
                if(get_option($end_meta_name)){
                    $end_date_value = get_option($end_meta_name) ;
                }
                ?>
                <?php echo esc_attr($start_date['label']); ?><input id="<?php echo esc_attr( $start_date['id'] );?>" name="<?php echo esc_attr( $start_date['name'] );?>" type="date" value="<?php echo esc_attr($start_date_value); ?>" > - <?php echo esc_attr($end_date['label']); ?><input id="<?php echo esc_attr($end_date['id']);?>" name="<?php echo esc_attr($end_date['name']);?>" type="date" value="<?php echo esc_attr($end_date_value); ?>" ><br />
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
        <?php }

        /**
         * create input text form.
         *
         * @param string slug
         * @param string description
         */
        public function jp4wc_display_message( $slug, $description ){
            ?>
            <label for="woocommerce_show_<?php echo esc_attr( $slug );?>">
                <?php echo wp_kses($description, $this->allowed_html); ?>
            </label>
            <?php
        }

        /**
         * create description for check pattern.
         *
         * @param string title
         * @return string description
         */
        public function jp4wc_description_check_pattern($title){
            $description = sprintf( $this->text_array['description_check_pattern'], $title);
            return wp_kses($description, $this->allowed_html);
        }

        /**
         * create description for payment pattern.
         *
         * @param string title
         * @return string $description
         */
        public function jp4wc_description_payment_pattern($title){
            $description = sprintf( $this->text_array['description_payment_pattern'], $title);
            return wp_kses($description, $this->allowed_html);
        }

        /**
         * create description for input pattern.
         *
         * @param string title
         * @return string description
         */
        public function jp4wc_description_input_pattern($title){
            $description = sprintf( $this->text_array['description_input_pattern'], $title);
            return wp_kses($description, $this->allowed_html);
        }

        /**
         * create description for input pattern.
         *
         * @param string title
         * @return string description
         */
        public function jp4wc_description_select_pattern($title){
            $description = sprintf( $this->text_array['description_select_pattern'], $title);
            return wp_kses($description, $this->allowed_html);
        }

        /**
         * save option.
         *
         * @param array add_methods
         * @param string plugin prefix
         * @param string ENCRYPT password
         * @return mixed
         */
        public function jp4wc_save_methods( $add_methods, $prefix, $password = null ) {
            foreach($add_methods as $add_method){
                if(isset($_POST[$add_method]) && $_POST[$add_method]){
                    if(isset($password)){
                        $post_add_method = sanitize_text_field($_POST[$add_method]);
                        update_option( $prefix.$add_method, self::encrypt( $post_add_method, $password ));
                    }else{
                        $post_add_method = sanitize_text_field($_POST[$add_method]);
                        update_option( $prefix.$add_method, $post_add_method);
                    }
                }else{
                    update_option( $prefix.$add_method, '');
                }
            }
        }

        /**
         * Sidebar Support notice html
         *
         * @param string support form url
         */
        public function jp4wc_support_notice( $support_form_url ){?>
            <h4 class="inner"><?php echo $this->text_array['support_notice_01'];?></h4>
            <p class="inner"><?php echo sprintf($this->text_array['support_notice_02'],esc_url( $support_form_url ).'?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=top-support');?></p>
            <p class="inner"><?php echo sprintf($this->text_array['support_notice_03'],'https://wc.artws.info/product-category/setting-support/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=setting-support','https://wc.artws.info/product-category/maintenance-support/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=maintenance-support');?></p>
            <?php
        }

        /**
         * Sidebar Pro version notice html
         *
         * @param string jp4wc_pro_url
         */
        public function jp4wc_pro_notice( $jp4wc_pro_url ){?>
            <h4 class="inner"><?php echo $this->text_array['pro_notice_01'];?></h4>
            <p class="inner"><?php echo sprintf($this->text_array['pro_notice_02'],esc_url( $jp4wc_pro_url ).'?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=top-pro');?></p>
            <p class="inner"><?php echo $this->text_array['pro_notice_03'];?></p>
            <?php
        }

        /**
         * Sidebar Update notice html
         */
        public function jp4wc_update_notice(){?>
            <h4 class="inner"><?php echo $this->text_array['update_notice_01'];?></h4>
            <p class="inner"><?php echo sprintf($this->text_array['update_notice_02'],'https://wc.artws.info/shop/maintenance-support/woocommerce-professional-support-subscription/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=maintenance-support');?>
            </p>
            <?php
        }

        /**
         * Sidebar Community information html
         */
        public function jp4wc_community_info(){?>
            <h4 class="inner"><?php echo $this->text_array['community_info_01'];?></h4>
            <p class="inner"><?php echo sprintf( $this->text_array['community_info_02'],'http://meetup.com/ja-JP/Tokyo-WooCommerce-Meetup/?');?><br />
                <?php echo sprintf( $this->text_array['community_info_03'],'http://meetup.com/ja-JP/Kansai-WooCommerce-Meetup/');?><br />
                <?php echo $this->text_array['community_info_04'];?>
            </p>
            <?php
        }

        /**
         * Sidebar Footer Author information html
         *
         * @param string plugin url
         */
        public function jp4wc_author_info( $plugin_url ){?>
            <p class="jp4wc-link inner"><?php echo $this->text_array['author_info_01'];?> <a href="https://wc.artws.info/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=created-by" target="_blank" title="Artisan Workshop"><img src="<?php echo esc_url($plugin_url);?>assets/images/woo-logo.png" title="Artsain Workshop" alt="Artsain Workshop" class="jp4wc-logo" /></a><br />
                <a href="https://docs.artws.info/?utm_source=jp4wc-settings&utm_medium=link&utm_campaign=created-by" target="_blank"><?php echo $this->text_array['author_info_02'];?></a>
            </p>
            <?php
        }

        /**
         * This function is similar to the function in the Settings API, only the output HTML is changed.
         * Print out the settings fields for a particular settings section
         *
         * @global $wp_settings_fields Storage array of settings fields and their pages/sections
         *
         * @since 1.2
         *
         * @param string Slug title of the admin page who's settings fields you want to show.
         */
        public function do_settings_sections( $page ) {
            global $wp_settings_sections, $wp_settings_fields;

            if ( ! isset( $wp_settings_sections[$page] ) )
                return;

            foreach ( (array) $wp_settings_sections[$page] as $section ) {
                echo '<div id="" class="stuffbox postbox ' . esc_attr( $section['id'] ) . '">';
                if ( $section['title'] )
                    echo '<h3 class="hndle"><span>' . esc_attr( $section['title'] ) . '</span></h3>'."\n";

                if ( $section['callback'] )
                    call_user_func( $section['callback'], $section );

                if ( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[$page] ) || !isset( $wp_settings_fields[$page][$section['id']] ) )
                    continue;
                echo '<div class="inside"><table class="form-table">';
                do_settings_fields( $page, $section['id'] );
                echo '</table></div>';
                echo '</div>';
            }
        }

        /**
         * create debug log as each plugin.
         *
         * @param string message
         * @param boolean as flag
         * @param string slug
         * @param string start time
         * @param string end time
         *
         * @return mixed $log
         */
        public function jp4wc_debug_log( $message, $flag, $slug, $start_time = null, $end_time = null){
            if (apply_filters('wc_jp4wc_logging', true, $message )) {
                $logger = wc_get_logger();
                if ( $flag != 'yes') {
                    return;
                }
                if ( ! is_null( $start_time ) && is_numeric($end_time) && is_numeric($start_time)) {
                    $formatted_start_time = date_i18n( get_option( 'date_format' ) . ' g:ia', $start_time );
                    $end_time             = is_null( $end_time ) ? current_time( 'timestamp' ) : $end_time;
                    $formatted_end_time   = date_i18n( get_option( 'date_format' ) . ' g:ia', $end_time );
                    $elapsed_time         = round( abs( $end_time - $start_time ) / 60, 2 );

                    $log_entry  = "\n" . '===='.$slug.' Framework Version: ' . $this->text_array['framework_version'] . '====' . "\n";
                    $log_entry .= '====Start Log ' . $formatted_start_time . '====' . "\n" . $message . "\n";
                    $log_entry .= '====End Log ' . $formatted_end_time . ' (' . $elapsed_time . ')====' . "\n\n";
                } else {
                    $log_entry  = "\n" . '===='.$slug.' Framework Version: ' . $this->text_array['framework_version'] . '====' . "\n";
                    $log_entry .= '====Start Log====' . "\n" . $message . "\n" . '====End Log====' . "\n\n";
                }
                $logger->debug( $log_entry, array( 'source' => $slug ) );
            }
        }

        /**
         * create debug log as each plugin.
         *
         * @param array message array
         * @return string message
         */
        public function jp4wc_array_to_message( $array ){
            if(is_array($array)){
                $message = '';
                foreach($array as $key => $value){
                    if(is_array($value)){
                        $message .= $key . ' : '. "\n";
                        foreach ($value as $key1 => $value1){
                            if(is_array($value1)){
                                $message .= '  '.$key1 . ' : ' . "\n";
                                foreach ($value1 as $key2 => $value2){
                                    $value2 = mb_convert_encoding($value2,"UTF-8", "auto" );
                                    $message .= '    '.$key2 . ' : ' . $value2. "\n";
                                }
                            }else{
                                $value1 = mb_convert_encoding($value1,"UTF-8", "auto" );
                                $message .= '  '.$key1 . ' : ' . $value1. "\n";
                            }
                        }
                    }else{
                        $value = mb_convert_encoding($value,"UTF-8", "auto" );
                        $message .= $key . ' : ' . $value. "\n";
                    }
                }
                return $message;
            }else{
                return null;
            }
        }

        /**
         * create debug log as each plugin.
         *
         * @param string url_encode
         * @return array array
         */
        public function jp4wc_url_to_array( $url_encode ){
            $array = array();
            if(!empty($url_encode)){
                $first_array = explode("&", $url_encode);
                foreach ($first_array as $data){
                    $set_array = explode("=", $data);
                    $array[$set_array[0]] = $set_array[1];
                }
            }
            return $array;
        }

        /**
         * Finds an Order ID based on an order key.
         *
         * @param string (transaction_id) An order key has generated by.
         * @return int The ID of an order, or 0 if the order could not be found
         */
        public function get_order_id_by_transaction_id( $transaction_id ) {
            global $wpdb;
            return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_transaction_id' AND meta_value = %s", $transaction_id ) );
        }

        /**
         * Function to determine whether it is a smartphone.
         *
         * @return boolean
         */
        public function isSmartPhone() {
            $ua = isset($_SERVER['HTTP_USER_AGENT'])? $_SERVER['HTTP_USER_AGENT'] : '';
            if (stripos($ua, 'iphone') !== false || // iphone
                stripos($ua, 'ipod') !== false || // ipod
                (stripos($ua, 'android') !== false && stripos($ua, 'mobile') !== false) || // android
                (stripos($ua, 'windows') !== false && stripos($ua, 'mobile') !== false) || // windows phone
                (stripos($ua, 'firefox') !== false && stripos($ua, 'mobile') !== false) || // firefox phone
                (stripos($ua, 'bb10') !== false && stripos($ua, 'mobile') !== false) || // blackberry 10
                (stripos($ua, 'blackberry') !== false) // blackberry
            ) {
                $isSmartPhone = true;
            } else {
                $isSmartPhone = false;
            }

            return $isSmartPhone;
        }

        /**
         * Function that determines whether all text is Hiragana
         *
         * @param string text
         * @return boolean
         */
        public function isHiragana($text) {
            if (mb_ereg('^[ぁ-ん]+$', $text)) {
                $isHiragana = true;
            } else {
                $isHiragana = false;
            }

            return $isHiragana;
        }

        /**
         * Function that determines whether all text is Katakana
         *
         * @param string text
         * @return boolean
         */
        public function isKatakana($text) {
            if (mb_ereg('^[ァ-ヶー]+$', $text)) {
                $isKatakana = true;
            } else {
                $isKatakana = false;
            }

            return $isKatakana;
        }

        /**
         * Add a message.
         * @param string text
         * @param object message object
         */
        public function add_message( $text, $object ) {
            $object->messages[] = $text;
        }

        /**
         * Add an error.
         * @param string text
         * @param object message object
         */
        public function add_error( $text, $object ) {
            $object->errors[] = $text;
        }

        /**
         * Output messages + errors.
         * @param object object
         */
        public function show_messages($object) {
            if ( sizeof( $object->errors ) > 0 ) {
                foreach ( $object->errors as $error ) {
                    echo '<div id="message" class="error inline"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
                }
            } elseif ( sizeof( $object->messages ) > 0 ) {
                foreach ( $object->messages as $message ) {
                    echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
                }
            }
        }

        /**
         * Display numbers (price) according to WooCommerce settings.
         *
         * @param float number
         * @param string round or round_up or round_down
         * @return false|float|WP_Error WP_Error and number
         */
        public function jp4wc_price_round_cal($num, $round = 'round'){
            $num_decimals = get_option('woocommerce_price_num_decimals');
            if($round  == 'round'){
                return round($num, $num_decimals);
            }elseif( $round  == 'round_up' ){
                return ceil($num, $num_decimals);
            }elseif( $round  == 'round_down' ){
                return floor($num, $num_decimals);
            }else{
                return new WP_Error('round_type_error', 'Round Type Error');
            }
        }
    }
endif;