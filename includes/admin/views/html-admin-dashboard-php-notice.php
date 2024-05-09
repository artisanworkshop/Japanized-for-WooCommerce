<?php
/**
 * Admin View: Dashboard - PHP notice
 *
 * @package JP4WC\Admin
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$php_ver = phpversion();
$message = __( 'Technology is constantly evolving, and web development especially requires updates to languages like PHP.', 'woocommerce-for-japan' ).'<br />';
if(strpos($php_ver,'7.4.') !== false){
    $message .= sprintf( __( 'The PHP version of this site is %1$s, and support has already ended in <strong>%2$s</strong>.', 'woocommerce-for-japan' ), $php_ver, __( 'November 2022', 'woocommerce-for-japan') );
}elseif(strpos($php_ver,'8.0.') !== false){
    $message .= sprintf( __( 'The PHP version of this site is %1$s, and support has already ended in %2$s.', 'woocommerce-for-japan' ), $php_ver, __( 'November 2023', 'woocommerce-for-japan') );
}else{
    $message .= sprintf( __( 'The PHP version of this site is %s, and support has already ended.', 'woocommerce-for-japan' ), $php_ver );
}
$button_message = __( 'Please see here for the detail', 'woocommerce-for-japan' );
?>
<div class="dashboard-widget-php-notice">
    <div class="description">
        <?php echo $message; ?>
        <div>
            <a href="<?php echo $php_notice_link; ?>" class="button button-primary" target="_blank"><?php echo $button_message; ?></a>
        </div>
    </div>
    <div class="clear"></div>
</div>
