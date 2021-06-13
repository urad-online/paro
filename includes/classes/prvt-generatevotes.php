<?php
/**
 * Saves votes to the table.
 *
 * Genereat random votes for perfomance testing.
 *
 * @package paro
 * @since 0.2.1
 */
class PrVt_GenerateVotes extends PrVt_FormParams
{
    protected $project_id  = "";
    protected $nr_plus  = 3;
    protected $nr_minus  = 3;
    protected $nr_tokens = 1;
    protected $token_list = array();
    protected $proposals = array();
    protected $input_forms_generate = array(
      'project_id' => 'projectId',
      'nr_tokens' => 'countToken',
      'nr_plus'   => 'countPlus',
      'nr_minus'  => 'countMinus',
    );
    protected $proposals_post_type = "pr-navrhy";
    protected $proposals_meta_key  = "relation_d15c7aa91423515c18fe4c60455a6022";
    protected $proposals_count     = 0;

    public function __construct( $params = null)
    {
        parent::__construct( $params);
        $this->messages["missingProjectId"] = __("Chybějící nebo neplatné ID projektu", PRVT_DOMAIN);
        $this->messages["noFreeToken"]      = __("Není žádný aktivní token", PRVT_DOMAIN);
        $this->messages["notEnoughProposals"] = __("Počet návrhů je menší než počet hlasů", PRVT_DOMAIN);
        $this->messages["nothingCreated"] = __("Nebyl uložen žádný hlas", PRVT_DOMAIN);
    }

    public function generateVotes()
    {
        $result = false;
        if ( $this->getTokens() && $this->getProposals()) {
            $result = $this->generate_in_loop();
        }
        return $result;
    }
    protected function set_params()
    {
      $par_value = $this->getValueFromParams( $this->input_forms_generate['project_id']);
      if (!empty($par_value) ) {
        $this->project_id = $par_value;
      }

      $par_value = $this->getValueFromParams( $this->input_forms_generate['nr_tokens']);
      if (!empty($par_value) ) {
        $this->nr_tokens = $par_value;
      }

      $par_value = $this->getValueFromParams( $this->input_forms_generate['nr_plus']);
      if (!empty($par_value) ) {
        $this->nr_plus = $par_value;
      }

      $par_value = $this->getValueFromParams( $this->input_forms_generate['nr_minus']);
      if (!empty($par_value) ) {
        $this->nr_minus = $par_value;
      }

    }

    /**
    * Reads active tokens for a project_id.
    *
    * @since 0.2.1
    *
    * @return bool|mixed If token is active returns array otherwise false.
    */
    protected function getTokens( )
    {
      $curr_time = current_time('timestamp', false);

      $query_arg = array(
              'post_type'   => PRVT_POST_TYPE,
              'post_parent' => $this->project_id,
              'posts_per_page' => -1,
              'meta_query'     => array(
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
        ));

        $post_list = get_posts( $query_arg );
        $count = 0;

        if ( count($post_list) > 0) {
            foreach ($post_list as $token_post ) {
              array_push( $this->token_list, $token_post->post_title );
              $count += 1;
              if ( $count >= $this->nr_tokens ) {
                break;
              }
            }
        }

        if ($count > 0) {
          return true;
        } else {
          $this->set_result_error('noFreeToken');
          return false;

        }

    }
    /**
    * Reads active tokens for a project_id.
    *
    * @since 0.2.1
    *
    * @return bool|mixed If token is active returns array otherwise false.
    */
    protected function getProposals( )
    {

      $query_arg = array(
              'post_type'   => $this->proposals_post_type,
              'post_status' => 'publish',
              'posts_per_page' => -1,
              'meta_query'     => array(
                  'relation' => 'AND',
                  array(
                    'key'     => $this->proposals_meta_key,
                    'value'   => $this->project_id,
                    'compare' => '='
                  ),
        ));

        // $post_list =  new WP_Query( $query_arg );
        $post_list = get_posts( $query_arg );

        if ( count($post_list) > 0) {
            foreach ($post_list as $post ) {
              array_push( $this->proposals, $post->ID );
            }
        }

        $count = count($this->proposals);
        if ($count > 0 && ($count >= ($this->nr_plus + $this->nr_minus)))  {
          $this->proposals_count = $count;
          return true;
        } else {
          $this->set_result_error('notEnoughProposals');
          return false;
        }

    }

    /**
    * Generates and saves votes.
    *
    * Calls method for token validation if this is successfull saves votes.
    *
    * @since 0.2.1
    *
    * @return bool
    */
    protected function generate_in_loop()
    {
      $count = 0;
      foreach ($this->token_list as $token) {

        $votes_plus = $this->generate_votes($this->nr_plus);

        $votes_minus = $this->generate_votes($this->nr_minus, $votes_plus);

        $data = array(
          INPUTS_FORM_VOTE['token']        => $token,
          INPUTS_FORM_VOTE['project_id']   => $this->project_id,
          INPUTS_FORM_VOTE['votes_plus']   => implode(",", $votes_plus),
          INPUTS_FORM_VOTE['votes_minus']  => implode(",", $votes_minus),
          INPUTS_FORM_VOTE['voting_start'] => current_time('timestamp', false),
          INPUTS_FORM_VOTE['voting_end']   => current_time('timestamp', false),
          );

          if (count($votes_plus)>0 || count($votes_minus)>0) {

            $save1vote = new PrVt_SingleTokenVotes( $data);

            $result = $save1vote->save_votes();
            $result = true;
            if ($result) {
              $count += 1;
            }

            unset($save1vote);
          }
      }
      if ($count > 0) {
        return true;
      } else {
        $this->set_result_error('nothingCreated');
        return false;
      }

    }

    private function generate_votes( $count_to_generate = 1,$already_picked = array())
    {
      $votes = array();
      $new = null;
      for ($i=0 ; $i < $count_to_generate ; $i++ ) {
        $new = $this->random_prop_id(array_merge($already_picked, $votes));
        if (isset( $new)) {
          array_push($votes, $new);
        }
        $new = null;
      }
      return $votes;
    }
    protected function random_prop_id( $already_picked = array())
    {
      $new = $this->proposals[ array_rand( $this->proposals, 1) ];
      if (! is_bool(array_search( $new, $already_picked, true)) && ( count( $already_picked) < $this->proposals_count )) {
        $continue =  true;
        do {
          $new = $this->proposals[ array_rand( $this->proposals, 1) ];
          if ( is_bool(array_search( $new, $already_picked, true))) {
            $continue = false;
          }
        } while ($continue);
      } elseif (! is_bool(array_search( $new, $already_picked, true))) {
        $new = null;
      }

      return $new;
    }
}
