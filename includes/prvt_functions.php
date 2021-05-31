<?php
/**
 * Core plugins funtions
 *
 * Mostly functions called by defined filters.
 *
 *
 * @package paro
 * @subpackage prvt_functions
 * @since 0.1.0
 */

 /**
  * Generates voting tokens and saves them to DB.
  *
  * Is called by filter. Tokens are generate in 1st step, then in 2ns step saved to DB by 1 token.
  * Depends on defined post_type  pr-respondenti
  *
  * @since 0.1.0
  *
  * @see prvt_on_init.
  *
  * @param bool $result value set by do_filter.
  * @param array $data Associative array with all input values from a form.
  * @param string $form_id ID of the form that calls the function. Not used.
  * @param object $spec_notification Used for returning specific error message on case of false result.
  * @return bool result.
  */
function prvt_generateTokens ($result = "", $data = "", $form_id = "", $spec_notif = "" )
{
  $get_tokens = new PrVt_GenToken( $data);
  $new_tokens = $get_tokens->getToken();
  $count_generated = count($new_tokens);

  $save_token = new PrVt_TokenPersist( $data );
  $count_saved = 0;
  $error_msgs =  array();

  foreach ($new_tokens as $token) {
    $post_id = $save_token->insert_token($token);
    if ( $post_id ) {
        $count_saved += 1;
    } else {
      array_push( $error_msgs, $save_token->get_status_msg());
    }

  }

  if ($count_saved > 0 && ($count_saved == $count_generated)) {
    return true;
  } else {
    $spec_notif->set_specific_status( "Uloženo pouze ". $count_saved . " z " . $count_generated. " vygenerovaných tokenů" );
    return false;
  }
}

/**
 * Reads token from DB.
 *
 * Inputs are project_is and optionaly status. Params are defined due to assumption
 * that funtion will be called from jetEngine form. Mandatory is only $data
 * Depends on defined post_type  pr-respondenti
 *
 * @since 0.1.0
 *
 * @see -
 *
 * @param bool $result value set by do_filter.
 * @param array $data Associative array with all input values from a form.
 * @param string $form_id ID of the form that calls the function. Not used.
 * @param object $spec_notification Used for returning specific error message on case of false result.
 * @return bool result.
 */
function prvt_getTokens($result = "", $data = "", $form_id = "", $spec_notif = "" )
{

  $tokens = new PrVt_TokenPersist( $data);
  $token_status = $tokens->getValueFromParams( 'token_status' );
  if (empty( $token_status)) {
    $token_status = 'all';
  }
  $result = $tokens->get_project_tokens( $token_status);
  return $result;
}

/**
 * Checks validity of a token.
 *
 * Required input is only token value. The called class finds the token,
 * checks if it this valid and returns result. If the token is valid, the page
 * is redirected to defined page with paramters project_id and token in URL.
 *
 * @since 0.1.0
 *
 * @see -
 *
 * @param bool $result value set by do_filter.
 * @param array $data Associative array with all input values from a form.
 * @param string $form_id ID of the form that calls the function. Not used.
 * @param object $spec_notification Used for returning specific error message on case of false result.
 * @return bool result.
 */
function prvt_checkToken($result = "", $data = "", $form_id = "", $spec_notif = "" )
{

  $token = new PrVt_SingleToken( $data);
  $result = $token->checkToken();

  // because Pavel wants to redirect always regardless the validation result
    $new_url = prvt_set_url_to_voting_page( $token->get_result() );
  wp_redirect( $new_url ) ;
  exit;

  // the rest is skipped
  if ($result) {
    $new_url = prvt_set_url_to_voting_page( $token->get_result());
    wp_redirect( $new_url ) ;
    exit;
  } else {
    $result = $token->get_result();
    $spec_notif->set_specific_status( $result['message']);
    return false;
  }
}
/**
 * Creates new URL with parameters.
 *
 * The path to new page is defined as plugin CONST. Added parameters are
 * project_id and token values.
 *
 * @since 0.1.0
 *
 * @see -
 *
 * @param array $data Associative array with all input values from a form.
 * @return string new URL.
 */

function prvt_set_url_to_voting_page( $data)
{
  $stranka = get_page_by_path( PRVT_VOTING_PAGE );
  $url = get_page_link( $stranka->ID) . "?projekt=". $data['project_id'] . "&token=" . $data['token'];
  return $url;
}

/**
 * Saves votes from a form to database.
 *
 * Is called by filter. In case of false result sets specific error message to be shown.
 *
 * @since 0.1.0
 *
 * @see -
 *
 * @param bool $result value set by do_filter.
 * @param array $data Associative array with all input values from a form.
 * @param string $form_id ID of the form that calls the function. Not used.
 * @param object $spec_notification Used for returning specific error message on case of false result.
 * @return bool result.
 */

function prvt_saveVotes($result = "", $data = "", $form_id = "", $spec_notif = "" )
{
  $token = new PrVt_SingleTokenVotes( $data);
  $result = $token->save_votes();
  if ($result) {
    return true;
  } else {
    $result = $token->get_result();
    $spec_notif->set_specific_status( $result['message']);
    return false;
  }
}

function PrVt_TokenDelete($result = "", $data = "", $form_id = "", $spec_notif = "" )
{
  $token_to_delete = new PrVt_DeleteTokens( $data);
  $result = $token_to_delete->delete_tokens();
  if ($result) {
    return true;
  } else {
    $result = $token_to_delete->get_result();
    // $spec_notif->set_specific_status( $result['message']);
    return false;
  }
}

// PrVt_TokenDelete( true, array( 'projekt' => 543,), 545, "" );
