<?php
/**
 * Saves/reads tokens into/from DB.
 *
 * Sets token atrributes (project, expiration datetime, # of votes) a stores
 * token into post_type defined in PRVT_POST_TYPE. Post status is "publish".
 *
 * @package paro
 * @since 0.1.0
 */
class PrVt_TokenPersist
{
    /**
    * Default value of expiration hours.
    * @since 0.1.0
    * @var int PR_TOKEN_EXP_HRS
    */
    const PR_TOKEN_EXP_HRS = 7*24;
    /**
    * Default value of number of votes for each token.
    * @since 0.1.0
    * @var int VOTES_COUNT
    */
    const VOTES_COUNT      = 3;
    /**
    * Error messages. Are set in costructor because of calling translation function.
    * @since 0.1.0
    * @var array $messages
    */
    private $messages = array();
    /**
    * Id of project the token is assigned to.Set from input parameters.
    * @since 0.1.0
    * @var array $parent_id
    */
    private $parent_id = 0;
    /**
    * Variable to store token post.
    * @since 0.1.0
    * @var array $post_data
    */
    private $post_data =  array();

    /**
     * Set class properties.
     *
     * @param array $params Array of input fields with values from form
     * @since 0.1.0
     */
    public function __construct( $params = "")
    {
        $this->params = $params;
        $this->set_params();

        if ( $this->parent_id>0 ) {
          $this->set_post_attrib();
        }
        $this->messages = array(
              "no_parent"    => __("Není zadáno ID PR projektu.", PRVT_DOMAIN),
              "error_insert" => __("Chyba při ukládání záznamu s tokenem. ", PRVT_DOMAIN),
              "empty_token"  => __("Prázdný token.", PRVT_DOMAIN),
              "ok"           => __("Záznam byl uložen.", PRVT_DOMAIN),
        );
    }

    /**
    * Reads expected input values from an array.
    *
    * Reads values from array set in constructor. If expected value is not set, the default value is used.
    *
    * @since 0.1.0
    */
    private function set_params()
    {
      $project_id = intval($this->getValueFromParams( INPUTS_FORM_GENERATE['project_id']));
      if (!empty($project_id) && ($project_id >0)) {
        $this->parent_id = $project_id;
      }

      $expiration_hrs = intval($this->getValueFromParams( INPUTS_FORM_GENERATE['exp_hrs']));
      if ($expiration_hrs > 0) {
        $this->expiration_hrs = $expiration_hrs;
      } else {
        $this->expiration_hrs = self::PR_TOKEN_EXP_HRS;
      }

      $vote_count = intval($this->getValueFromParams( INPUTS_FORM_GENERATE['votes_count']));
      if ($vote_count > 0) {
        $this->votes_count = $vote_count;
      } else {
        $this->votes_count = self::VOTES_COUNT;
      }
    }

    /**
    * Reads one value from array.
    *
    * Check if the required values is set. If not return empty string.
    *
    * @since 0.1.0
    *
    * @param string $param_name Name of the key in the array.
    */
    public function getValueFromParams( $param_name = "")
    {
      if ((! empty($param_name)) && isset( $this->params[ $param_name]) && (!empty($this->params[ $param_name])) ) {
          return $this->params[ $param_name];
      } else {
          return "";
      }
    }

    /**
    * Sets post_type attributes into class property.
    *
    * @since 0.1.0
    */
    private function set_post_attrib()
    {
      	$this->post_data = array(
      		'post_title' => "",
      		'post_content' => "",
      		'post_type'   => PRVT_POST_TYPE,
      		'post_status' => 'publish',
      		'post_parent' => $this->parent_id,
      	);

    }

    /**
    * Inserts one token into a post.
    *
    * Validates mandatory attributes and inserts new post.
    *
    * @since 0.1.0
    *
    * @param string $token Token value.
    * @return int|bool Returns post ID or false.
    */
    public function insert_token( $token )
    {
      $this->result = array(
        'result'  => "ok",
        'message' => $this->messages[ "ok"],
      );
        if (! $this->parent_id > 0) {
          $this->result = array(
            'result'  => "error",
            'message' => $this->messages[ "no_parent"],
          );
          return false;
        }

        if ( empty( $token) ) {
          $this->result = array(
            'result'  => "error",
            'message' => $this->messages[ "empty_token"],
          );
          return false;
        }

        $issue_time      =  current_time( 'timestamp', 0 );
        $expiration_time = $issue_time + 60*60*intval($this->expiration_hrs);

        $post_data = $this->post_data;
        $post_data['post_title'] = $token;
        $post_data['post_name']  = $token;
        $post_data['meta_input'] = array(
          'pr-projekt'  => $this->parent_id,
          'platnost_od' => date( 'Y-m-d H:i:s', $issue_time),
          'platnost_do' => date( 'Y-m-d H:i:s', $expiration_time),
          'zbyva_hlasu' => $this->votes_count,
        );

        $post_id = wp_insert_post( $post_data, true);

        if (is_wp_error($post_id)) {
          $this->result = array(
            'result'  => "error",
            'message' => $this->messages['error_insert'].$post_id->get_error_messages(),
          );
          return false;
        } else {
          return $post_id;
        }

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
}
