<?php
/*
* Plugin Name: Paro
* Plugin URI: https://urad.online
* Description: Participativní rozpočet - hlasovaci tokeny
* Author: Miloslav Stastny
* Version: 0.2.0
* Author URI: https://urad.online
* Text Domain: prvt-token
* Domain Path: /languages
*/

define( 'PRVT_PATH',     dirname(__FILE__));
define( 'PRVT_PATH_INC', PRVT_PATH . "/includes");
define( 'PRVT_DOMAIN',   "paro-voting");
define( 'PRVT_POST_TYPE',  "pr-respondenti");
// define( 'PRVT_VOTING_PAGE', "pr-projec-votes");
// define( 'PRVT_VOTING_PAGE', "registrace-form");
define( 'PRVT_VOTING_PAGE', "hlasovani-paro");
define( 'PRVT_VOTE_TABLE_NAME', 'jet_cct_pr_hlasy');
// define( 'PRVT_VOTE_TABLE_NAME', 'paro_votes');
define( 'INPUTS_FORM_GENERATE', array(
  'token_length' => 'tokenLength',
  'token_count'  => 'tokenCount',
  'project_id'   => 'prProjectId',
  'exp_hrs'      => 'prExpirationHrs',
  'votes_count'  => 'prTokenVotes',
));
define( 'INPUTS_FORM_CHECKTOKEN', array(
  'token'        => 'token',
));
define( 'INPUTS_FORM_VOTE', array(
  'token'        => 'token',
  'project_id'   => 'projekt',
  'votes_plus'   => 'plusa',
  'votes_minus'  => 'minusa',
  'voting_start' => 'hlasovani_konec',
  'voting_end'   => 'hlasovani_zacatek',
));
define( 'INPUTS_FORM_DELETE', array(
  'token'        => 'token',
  'project_id'   => 'projekt',
  'token_status' => 'status',
));

register_activation_hook( __FILE__, 'prvt_activation' );
pr_ms_register_plugin_actions();

function pr_ms_register_plugin_actions()
{
    add_action( 'init',           'prvt_on_init' );
    add_action( 'plugins_loaded', 'prvt_plugin_loaded');

}

function prvt_plugin_loaded()
{
    require_once PRVT_PATH_INC .'/prvt_load.php';
}


function prvt_activation()
{
    require_once PRVT_PATH_INC. '/prvt_create_tables.php';
    prvt_activation_create_tables();
}
