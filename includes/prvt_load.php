<?php
/**
 * Loads files , classes
 *
 * Library - loading required files and setting WP filters,actions and  shortcodes
 *
 * @package paro
 * @subpackage prvt_load
 * @since 0.1.0
*/
spl_autoload_register('prvt_autoloader', true);
require_once PRVT_PATH_INC. '/prvt_functions.php';


function prvt_autoloader( $class_name ) {

    /**
     * If the class being requested does not start with our prefix,
     * we know it's not one in our project
     */
    if ( 0 !== strpos( $class_name, 'PrVt_' ) ) {
        return;
    }

    $file_name = str_replace(
        array( 'PrVt_', '_' ),      // Prefix | Underscores
        array( '', '-' ),         // Remove | Replace with hyphens
        strtolower( $class_name ) // lowercase
    );

    // Compile our path from the current location
    $file =  PRVT_PATH_INC . '/classes/'. $file_name .'.php';

    // If a file is found
    if ( file_exists( $file ) ) {
        // Then load it up!
        require( $file );
    }
}

function prvt_on_init()
{
    add_filter('jet-engine-booking/filter/generateTokens', "prvt_generateTokens", 10, 4);
    add_filter('jet-engine-booking/filter/getTokens',   "prvt_getTokens", 10, 4);
    add_filter('jet-engine-booking/filter/checkToken',  "prvt_checkToken", 10, 4);
    add_filter('jet-engine-booking/filter/saveVotes',   "prvt_saveVotes", 10, 4);
    add_filter('jet-engine-booking/filter/deleteToken', "prvt_deleteToken", 10, 4);
}
