<?php
/**
 * Creates table(s) on plugin activation.
 *
 * If they doesn't exist the specific plugin DB tables are created.
 *
 * @package paro
 * @subpackage prvt_create_tables
 * @since 0.1.0
*/

/**
 * Creates table for storing of votes.
 *
 * Shall be called only at plugin activation. If table exists, it is not re-created.
 *
 * @since 0.1.0
 *
 * @see -
 *
 * @param -
 */
function prvt_activation_create_tables( )
{
    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_name_reg = $wpdb->prefix . PRVT_VOTE_TABLE_NAME;
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name_reg'") != $table_name_reg) {
         //table not in database. Create new table
        $charset_collate = $wpdb->get_charset_collate();

        $command = "CREATE TABLE IF NOT EXISTS $table_name_reg (
            `id` INT NOT NULL AUTO_INCREMENT,
            `project_id` INT NOT NULL,
            `proposal_id` INT NOT NULL,
            `token_id` INT NOT NULL,
            `vote_plus` SMALLINT,
            `vote_minus` SMALLINT,
            `cct_created` DATETIME NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE INDEX `id_UNIQUE` (`id` ASC),
            INDEX `project` (`project_id` ASC, `proposal_id`),
            INDEX `proposal` (`proposal_id` ASC, `token_id` ASC),
            INDEX `token` (`token_id` ASC, `proposal_id` ASC))
            $charset_collate;";

        $result = dbDelta( $command );
    }
}
