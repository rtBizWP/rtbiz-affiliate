<?php

/*
 *     Plugin Name:  rtAffiliate
 *     Plugin URI:   http://rtcamp.com
 *     Description:  rtAffiliate
 *     Version:      3.0.7.3
 *     Author:       rtCamp
 *     Author URI:   http://rtcamp.com
 *     Contributer:  $trik3r<faishal.saiyed@rtcamp.com>,Joshua Abenazer, Santosh Kamble
 */

/**
 *  RT_AFFILIATE_PATH
 *  Gives plugin server Absolute path
 */
if (!defined('RT_AFFILIATE_PATH')) {
    define('RT_AFFILIATE_PATH', plugin_dir_path(__FILE__));
}

/**
 * RT_AFFILIATE_URL
 * Give plugin url
 */
if (!defined('RT_AFFILIATE_URL')) {
    define('RT_AFFILIATE_URL', plugin_dir_url(__FILE__));
}

/**
 * Auto Loader Function
 *
 * Autoloads classes on instantiation. Used by spl_autoload_register.
 *
 * @param string $class_name The name of the class to autoload
 */
function rt_affiliate_autoloader ( $class_name ) {
        $rtlibpath = array (
            'app/admin/' . $class_name . '.php' ,
            'app/main/' . $class_name . '.php' ,
            'app/helper/' . $class_name . '.php' ,
        ) ;
        foreach ( $rtlibpath as $path ) {
                $path = RT_AFFILIATE_PATH . $path ;
                if ( file_exists ( $path ) ) {
                        include $path ;
                        break ;
                }
        }
}

//include_once 'app/lib/wp-helpers.php';

/**
 * Register the autoloader function into spl_autoload
 */
spl_autoload_register ( 'rt_affiliate_autoloader' ) ;

/**
 * Instantiate the rtAffiliate class.
 */
global $rt_affiliate ;
$rt_affiliate = new rtAffiliate() ;
