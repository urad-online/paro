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
    protected $parent_id    = 0;
    protected $token_status  = "all";
    protected $single_delete = false;
    private   $values_delimiter = ",";

    /**
    * Error messages. Are set in costructor because of calling translation function.
    * @since 0.1.0
    * @var array $messages
    */
    protected $messages = array();
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
        $this->parent_id = $project_id;
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

    public function delete_tokens()
    {
      if ($this->single_delete) {
        $result = $this->delete_single_token();
      } elseif ($this->parent_id > 0) {
        $result = $this->delete_project_tokens();
      } else {
        $this->set_result_error('empty_params');
        $result = false;
      }
      return $result;
    }
    /**
    * Sets post_type attributes into class property.
    *
    * @since 0.1.0
    */
    private function delete_single_token()
    {
      $result = true;
      foreach ($this->token_value as $token) {
        if (! empty( $token)) {
          $post_id = $this->get_token_post( $token);
          if ( $post_id && ! is_bool( $post_id)) {
            $this->delete_single_token_votes( $post_id);
            if (! $this->delete_single_token_post( $post_id)) {
              $result = false;
            };
          }
        }
      }
    }

    /**
    * Returns the post record.
    *
    * Looks for posts by token value and returns the wp post.
    *
    * @since 0.1.0
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
    * @since 0.1.0
    *
    * @return string  Error message.
    */
    public function get_status_msg()
    {
        return $this->result['message'];
    }

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

    private function delete_single_token_post( $post_id)
    {
      $result = wp_delete_post( $post_id, false );
      return true;
    }
    private function delete_project_tokens()
    {
      $this->result = array(
        'result'  => 'error',
        'message' => "Dosud není implementováno",
      );
      return false;
    }
    /**
    * Reads tokens from DB.
    *
    * Reads data for parameters project, status and timestamp of token validity.
    * The date from is not implemented yet.
    * @since 0.1.0
    *
    * @param int $parent_id
    * @param string $status
    * @param datetime $from_date
    * @return array  List of tokens - post ID + token value.
    */
    public function get_project_tokens( $parent_id = 0, $status = "all", $from_date = "" )
    {
        $status = strtolower( $status);

        switch ($status) {
          case 'active':
              $meta_query[] = array(
                  'relation' => 'AND',
                   array(
                     'key' => 'zbyva_hlasu',
                     'value' => 0 ,
                     'compare' => '>',
                     'type' => 'NUMERIC'
                   ),
                   array(
                    'key'     => 'platnost_do',
                    'value'   => date("Y-m-d H:i:s") ,
                    'compare' => '>',
                    'type' => 'DATETIME'
                  ),
                );
            break;
          case 'expired' :
              $meta_query[] = array(
                  'relation' => 'AND',
                   array(
                     'key' => 'zbyva_hlasu',
                     'value' => 0 ,
                     'compare' => '>',
                     'type' => 'NUMERIC'
                   ),
                   array(
                    'key'     => 'platnost_do',
                    'value'   => date("Y-m-d H:i:s") ,
                    'compare' => '<=',
                    'type' => 'DATETIME'
                  ),
                );
            break;
          case 'voted' :
              $meta_query[] = array(
                  'relation' => 'AND',
                   array(
                     'key' => 'zbyva_hlasu',
                     'value' => 0 ,
                     'compare' => '0',
                     'type' => 'NUMERIC'
                   ),
                );
            break;
          case 'all' :
          default:
            $meta_query = array(array());
            break;
        }
        $query_arg = array(
                'post_type' => PRVT_POST_TYPE,
                'post_status' => array('publish'),
                'posts_per_page' => -1,
                'post_parent' => $parent_id,
                'meta_query'  => $meta_query,
              );

        $post_list = new WP_Query( $query_arg );

    // The Loop
    $tokens = array();
    if ( $post_list->have_posts() ) :
        while ( $post_list->have_posts() ) :
          $post_list->the_post();
          array_push( $tokens, array('ID' => get_the_ID(), 'token' => get_the_title()));
        endwhile;
    endif;

    // Reset Post Data
    wp_reset_postdata();
    return $tokens;
    }
    /**
    * Returns array with results.
    *
    * @since 0.1.0
    *
    * @return array Array of result info.
    */
    public function get_result()
    {
      return $this->result;
    }
}
