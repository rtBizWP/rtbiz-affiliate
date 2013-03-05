<?php

/*
 *     Plugin Name:  RT Affiliate
 *     Plugin URI:   http://rtcamp.com
 *     Description:  RT Affiliate
 *     Version:      2.0
 *     Author:       rtCamp
 *     Author URI:   http://rtcamp.com
 *     Contributer:  Joshua Abenazer, Santosh Kamble
 */

if (!defined('RT_AFFILIATE_PATH')) {
    define('RT_AFFILIATE_PATH', plugin_dir_path(__FILE__));
}

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
function rt_affiliate_autoloader($class_name) {
    $rtlibpath = array(
        'app/admin/' . $class_name . '.php',
        'app/main/' . $class_name . '.php',
    );
    foreach ($rtlibpath as $i => $path) {
        $path = RT_AFFILIATE_PATH . $path;
        if (file_exists($path)) {
            include $path;
            break;
        }
    }
}

/**
 * Register the autoloader function into spl_autoload
 */
spl_autoload_register('rt_affiliate_autoloader');

/**
 * Instantiate the rtAffiliate class.
 */
global $rt_affiliate;
$rt_affiliate = new rtAffiliate();
?>
