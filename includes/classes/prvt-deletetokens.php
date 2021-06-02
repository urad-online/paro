<?php
/**
 * Deletes token from DB.
 *
 * Deletes one token or all tokens of a project from DB. For used tokens deletes
 * votes as well.
 *
 * @package paro
 * @since 0.1.0
 */
class PrVt_DeleteTokens extends PrVt_FormParams
{
    protected $token_value   = "";
    protected $token_status  = "all";
    protected $single_delete = false;
    private   $values_delimiter = ",";

    /**
    * Id of project the token is assigned to.Set from input parameters.
    * @since 0.1.0
    * @var array $project_id
    */
    private $project_id = 0;

    public function __construct( $params = null)
    {
        parent::__construct( $params);
        global $wpdb;
        $this->db = $wpdb;
    }
    /**
    * Set class properties.
    *
    * @param array $params Array of input fields with values from form
    * @since 0.1.0
    */
    protected function set_messages()
    {
        $this->messages = array(
              "notfound"    => __("Token nebyl nalezen", PRVT_DOMAIN),
              "error_delete" => __("Chyba při mazání záznamu s tokenem. ", PRVT_DOMAIN),
              "empty_params" => __("Neúplné vstupní parametry.", PRVT_DOMAIN),
              "ok"           => __("Token(y) byl(y) smazány.", PRVT_DOMAIN),
        );
    }

    /**
    * Reads expected input values from an array.
    *
    * Reads values from array set in constructor. If expected value is not set, the default value is used.
    *
    * @since 0.1.0
    */
    protected function set_params()
    {
      $project_id = intval($this->getValueFromParams( INPUTS_FORM_DELETE['project_id']));
      if (!empty($project_id) && ($project_id >0)) {
        $this->project_id = $project_id;
      }

      $status = $this->getValueFromParams( INPUTS_FORM_DELETE['token_status']);
      if ( !empty($status) ) {
        $this->token_status = $status;
      }

      $token = $this->getValueFromParams( INPUTS_FORM_DELETE['token']);
      if ( !empty($token) ) {
        $this->token_value = explode ( $this->values_delimiter , $token);
        $this->single_delete = true;
      }

    }

    /**
    * Main function for both single token deletion or deletion of tokens of a project.
    *
    * @since 0.2.0
    *
    * @return bool Result.
    */
    public function delete_tokens()
    {
      if ($this->single_delete) {
        $result = $this->delete_tokens_by_title();
      } elseif ($this->project_id > 0) {
        $result = $this->delete_tokens_by_project();
      } else {
        $this->set_result_error('empty_params');
        $result = false;
      }
      return $result;
    }

    /**
    * Delete token(s) defined by they value. Values are taken from an array.
    *
    * @since 0.2.0
    *
    * @return bool Result.
    */
    private function delete_tokens_by_title()
    {
      $result = true;
      foreach ($this->token_value as $token) {
        if (! empty( $token)) {
          $post_id = $this->get_token_post( $token);
          if ( $post_id && ! is_bool( $post_id)) {
            if (! $this->delete_token_by_postid( $post_id)) {
              $result = false;
            }
          }
        }
      }
      return $result;
    }

    /**
    * Delete a token including its votes.
    *
    * @since 0.2.0
    *
    * @param string $post_id ID of the token post.
    *
    * @return bool Result.
    */
    private function delete_token_by_postid( $post_id)
    {
      $result = true;
      $this->delete_single_token_votes( $post_id);
      if (! $this->delete_single_token_post( $post_id)) {
        $result = false;
      }
      return $result;
    }

    /**
    * Returns the post record.
    *
    * Looks for posts by token value and returns the wp post.
    *
    * @since 0.2.0
    *
    * @param string $token Token value.
    * @return int|bool Returns post or false.
    */
    private function get_token_post( $token )
    {
      $query_arg = array(
              'post_type' => PRVT_POST_TYPE,
              'name' => $token,
              'posts_per_page' => -1,
            );
      $post_list = get_posts( $query_arg );

      $output = false;
      if ( count($post_list) > 0) {
          foreach ($post_list as $post ) {
            if ( $post->post_title === $token) {
              $output = $post->ID;
              break;
            }
          }
      }

      if (! $output ) {
          $this->set_result_error('notfound');
      }

      return $output;
    }

    /**
    * Returns result.
    *
    * Validates mandatory attributes and inserts new post.
    *
    * @since 0.2.0
    *
    * @return string  Error message.
    */
    public function get_status_msg()
    {
        return $this->result['message'];
    }

    /**
    * Delete all votes of a single token.
    *
    * @since 0.2.0
    *
    * @param string $post_id ID of the token post.
    *
    * @return bool Result.
    */
    private function delete_single_token_votes( $post_id)
    {
      $sql_comm = $this->db->prepare( 'DELETE FROM '.$this->db->prefix . PRVT_VOTE_TABLE_NAME
          . ' WHERE token_id = %d ',
              $post_id
              );

      $result = $this->db->query( $sql_comm );
      if ($result ) {
          return true;
      } else {
        if ( empty($this->db->last_error)) {
          return true;
        } else {
          $this->result = array(
            'result'  => 'error',
            'message' => $this->db->last_error,
          );
          return false;
        }
      }
    }

    /**
    * Delete single token post record by post_id.
    *
    * @since 0.2.0
    *
    * @param string $post_id.
    *
    * @return bool Result.
    */
    private function delete_single_token_post( $post_id)
    {
      $result = wp_delete_post( $post_id, true );
      return true;
    }

    /**
    * Mass delete of token for specific project.
    *
    * @since 0.2.0
    *
    * @return bool Result.
    */
    public function delete_tokens_by_project( )
    {
      $query_arg = array(
              'post_type'   => PRVT_POST_TYPE,
              'post_parent' => $this->project_id,
              'posts_per_page' => -1,
            );
      if ( $this->token_status !== "all" ) {
        $query_arg[ 'meta_query'] = $this->build_metaquery($this->token_status);
      }

      $token_list = get_posts( $query_arg );
      $result = true;
      if ( count($token_list) > 0) {
          foreach ($token_list as $token_post ) {
            $single_result = $this->delete_token_by_postid( $token_post->ID );
            if ( ! $single_result ) {
              $result = false;
            }
          }
      }
      if ($result) {
        $this->set_result_ok();
        return true;
      } else {
        $this->set_result_error( 'error_delete');
        return false;
      }
    }

    /**
    * Returns array with results.
    *
    * @since 0.2.0
    *
    * @return array Array of result info.
    */
    public function get_result()
    {
      return $this->result;
    }

    /**
    * Returns array with meta query.
    *
    * @since 0.2.0
    *
    * @param string $token_status Status from form.
    *
    * @return array Array of meta query parameters.
    */
    private function build_metaquery( $token_status)
    {
      switch ($token_status) {
        case 'active':
          $curr_time = current_time('timestamp', false);
          $meta_query_args = array(
            'relation' => 'AND',
            array(
              'key'     => 'zbyva_hlasu',
              'value'   => 0,
              'compare' => '>'
            ),
            array(
              'key'     => 'platnost_od',
              'value'   => $curr_time,
              'compare' => '<'
            ),
            array(
              'key'     => 'platnost_do',
              'value'   => $curr_time,
              'compare' => '>'
            ),
          );
          break;

        case 'expired':
          $curr_time = current_time('timestamp', false);
          $meta_query_args = array(
            'relation' => 'AND',
            array(
              'key'     => 'zbyva_hlasu',
              'value'   => 0,
              'compare' => '>'
            ),
            array(
              'key'     => 'platnost_do',
              'value'   => $curr_time,
              'compare' => '<='
            ),
          );
          break;

        case 'used':
            $meta_query_args = array(
              'relation' => 'AND',
              array(
                'key'     => 'zbyva_hlasu',
                'value'   => 0,
                'compare' => '='
              ),
            );
          break;

        default:
          $meta_query_args = array();
          break;
      }
      return $meta_query_args;
    }
}
