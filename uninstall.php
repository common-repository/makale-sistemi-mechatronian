<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}


if ( !is_user_logged_in() )
	wp_die( 'Silmek İçin giriş yapınız.' );

if ( !current_user_can( 'install_plugins' ) )
	wp_die( 'Bunu yapmak için gerekli izniniz yok.' );


// Enter our plugin uninstall script below
?>