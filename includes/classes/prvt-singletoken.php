<?php
/**
 * @package PARO hlas
 * @version 0.1.0
 */
/*
* Class checks token's validity
*/
class PrVt_SingleToken
{
    protected $token_value = "";
    protected $token_post = null;

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

    protected function set_params()
    {
      $token_value = $this->getValueFromParams( INPUTS_FORM_CHECKTOKEN['token']);
      if (!empty($token_value) ) {
        $this->token_value = $token_value;
      }
    }

    protected function getValueFromParams( $param_name = "")
    {
      if ((! empty($param_name)) && isset( $this->params[ $param_name]) && (!empty($this->params[ $param_name])) ) {
          return $this->params[ $param_name];
      } else {
          return "";
      }
    }
    public function checkToken( )
    {

        $this->getTokenPost();
        if ( $this->token_post && $this->is_active() ) {
          return $this->result;
        } else {
          return false;
        }

    }

    protected function getTokenPost()
    {
      $query_arg = array(
              'post_type' => PRVT_POST_TYPE,
              'post_title' => $this->token_value,
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

    protected function is_active()
    {
        $curr_time      =  date( 'Y-m-d H:i:s', current_time( 'timestamp', 0 ));
        $platnost_od    = $this->token_post['meta_data']['platnost_od'][0];
        $platnost_do    = $this->token_post['meta_data']['platnost_do'][0];
        $hlasu          = $this->token_post['meta_data']['zbyva_hlasu'][0];

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
    protected function set_result_ok()
    {
        $this->result =  array(
          'result'  => 'OK',
          'project_id' => $this->token_post['post_parent'],
          'token'   => $this->token_value,
          'message' => $this->messages['ok']
        );
    }
    protected function set_result_error( $msg_type)
    {
        $this->result =  array(
          'result'  => 'error',
          'project_id' => "",
          'token'   => $this->token_value,
          'message' => $this->messages[ $msg_type],
        );
    }
    public function get_result()
    {
      return $this->result;
    }
}
