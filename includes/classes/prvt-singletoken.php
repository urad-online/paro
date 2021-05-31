<?php
/**
 * Validates one token.
 *
 * Reads token post from database and checks the token status.
 *
 * @package paro
 * @since 0.1.0
 */
class PrVt_SingleToken
{
    protected $token_value = "";
    protected $token_post = null;
    /**
     * Set class properties.
     *
     * @param array $params Array of input fields with values from form
     * @since 0.1.0
    */
    public function __construct( $params = null)
    {
        $this->params = $params;
        $this->set_params();
        $this->messages = array(
              "notfound"    => __("Token nebyl nalezen", PRVT_DOMAIN),
              "expired"     => __("Platnost tokenu již expirovala", PRVT_DOMAIN),
              "alreadyused" => __("Token byl již použit pro hlasování", PRVT_DOMAIN),
              "uknown"      => __("Neznámá chyba", PRVT_DOMAIN),
              "ok"          => __("Token je platný", PRVT_DOMAIN),
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
      $token_value = $this->getValueFromParams( INPUTS_FORM_CHECKTOKEN['token']);
      if (!empty($token_value) ) {
        $this->token_value = $token_value;
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
    protected function getValueFromParams( $param_name = "")
    {
      if ((! empty($param_name)) && isset( $this->params[ $param_name]) && (!empty($this->params[ $param_name])) ) {
          return $this->params[ $param_name];
      } else {
          return "";
      }
    }

    /**
    * Checks token value.
    *
    * Checks if token exists and is active.
    *
    * @since 0.1.0
    *
    * @return bool|mixed If token is active returns array otherwise false.
    */
    public function checkToken( )
    {

        $this->getTokenPost();
        if ( $this->token_post && $this->is_active() ) {
          return $this->result;
        } else {
          return false;
        }

    }

    /**
    * Read data from post.
    *
    * Read the post and its meta fields.
    *
    * @since 0.1.0
    *
    */
    protected function getTokenPost()
    {
      $query_arg = array(
              'post_type' => PRVT_POST_TYPE,
              'title' => $this->token_value,
              'posts_per_page' => -1,
            );
      $post_list = new WP_Query( $query_arg );
      // $post = get_page_by_title( $this->token_value);
      if ($post_list &&  $post_list->have_posts()) {
          global $post;
          $post_list->the_post();
          if ($post->post_title === $this->token_value) {
              $post_meta = get_post_meta( $post->ID );
              if ($post_meta && is_array($post_meta) && count($post_meta)>0) {
                $this->token_post =  json_decode(json_encode($post), true);
                $this->token_post['meta_data'] = $post_meta;
              }
          }
      }
      if (! $this->token_post) {
          $this->set_result_error('notfound');
      }
      wp_reset_postdata();
    }

    /**
    * Checks if the token is active.
    *
    * Evaluates datetime value of tokne expiration, # of remaining votes.
    * Token is active when is not expired and has > 0 remaining votes.
    *
    * @since 0.1.0
    *
    * @return bool Token is active true/false.
    */
    protected function is_active()
    {
        // $curr_timestr      =  date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ));
        $curr_time      =  current_time( 'timestamp', 0 );
        $platnost_od    = intval($this->token_post['meta_data']['platnost_od'][0]);
        $platnost_do    = intval( $this->token_post['meta_data']['platnost_do'][0]);
        $hlasu          = intval($this->token_post['meta_data']['zbyva_hlasu'][0]);

        $result= false;
        if (($hlasu >0) && ($platnost_od <= $curr_time) && ($platnost_do > $curr_time)) {
              $this->set_result_ok();
              $result = true;
        } elseif ( ($hlasu >0) && ($platnost_do <= $curr_time)) {
              $this->set_result_error( 'expired');
        } elseif ($hlasu == 0) {
           $this->set_result_error( 'alreadyused');
        } else {
          $this->set_result_error('uknown');
        }
        return $result;
    }
    /**
    * Sets array with returned values.
    *
    * Sets result OK, project id, token valus and message.
    *
    * @since 0.1.0
    *
    */
    protected function set_result_ok()
    {
        $this->result =  array(
          'result'  => 'OK',
          'project_id' => $this->token_post['post_parent'],
          'token'   => $this->token_value,
          'message' => $this->messages['ok']
        );
    }
    /**
    * Sets array with returned values for error result.
    *
    * Sets result error, empty project id, token valus and message.
    *
    * @since 0.1.0
    *
    */
    protected function set_result_error( $msg_type)
    {
        $this->result =  array(
          'result'  => 'error',
          'project_id' => "",
          'token'   => $this->token_value,
          'message' => $this->messages[ $msg_type],
        );
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
