<?php
/**
 * @package PARO hlas
 * @version 0.1.0
 */
/*
* Class extends token's validity check by checking project_id and saves votes into database
*/
class PrVt_SingleTokenVotes extends PrVt_SingleToken
{
    protected $project_id  = "";
    protected $votes_plus  = null;
    protected $votes_minus = null;

    public function __construct( $params = null)
    {
        parent::__construct( $params);
        $this->messages["saveVotesFailed"] = __("Chyba při ukládání hlasů", PRVT_DOMAIN);
        global $wpdb;
        $this->db = $wpdb;
    }

    protected function set_params()
    {
      $token_value = $this->getValueFromParams( INPUTS_FORM_VOTE['token']);
      if (!empty($token_value) ) {
        $this->token_value = $token_value;
      }

      $project_id = $this->getValueFromParams( INPUTS_FORM_VOTE['project_id']);
      if (!empty($project_id) ) {
        $this->project_id = $project_id;
      }

      $this->votes_plus  =  explode ( "," , $this->getValueFromParams( INPUTS_FORM_VOTE['votes_plus']));
      $this->votes_minus =  explode ( "," , $this->getValueFromParams( INPUTS_FORM_VOTE['votes_minus']));

      $this->voting_start = $this->getValueFromParams( INPUTS_FORM_VOTE['voting_start']);
      $this->voting_end   = $this->getValueFromParams( INPUTS_FORM_VOTE['voting_end']);
    }

    public function checkToken( )
    {

        $this->getTokenPost();
        if ( $this->token_post && $this->is_active() ) {
            if (empty($this->project_id) || $this->project_id != $this->token_post['meta_data']['pr-projekt'][0]) {
              $this->set_result_error('notfound');
              return false;
            }
          return $this->result;
        } else {
          return false;
        }

    }

    public function save_votes()
    {
        $result = false;
        if ($this->checkToken()) {
          if ($this->save_plus_minus_votes()) {
            $this->set_token_used();
            $result = true;
          } else {
            $this->set_result_error('saveVotesFailed');
          }
        }
        return $result;
    }
    private function set_token_used()
    {
        update_post_meta($this->token_post['ID'], "zbyva_hlasu", 0);
        update_post_meta($this->token_post['ID'], "hlasovani_zacatek", $this->voting_start);
        update_post_meta($this->token_post['ID'], "hlasovani_konec",   $this->voting_end);
    }
    private function save_plus_minus_votes()
    {
        foreach ($this->votes_plus as $vote) {
          $this->save_one_vote( $vote, 1,0);
        }
        foreach ($this->votes_minus as $vote) {
          $this->save_one_vote( $vote, 0,1);
        }
        return true;
    }

    public function save_one_vote( $proposal_id, $vote_plus = 0, $vote_minus = 0)
    {
        $insert_time = date( 'Y-m-d H:i:s',current_time( 'timestamp', 0 ));

        $sql_comm = $this->db->prepare( 'INSERT INTO '.$this->db->prefix . PRVT_VOTE_TABLE_NAME
            . ' (project_id, proposal_id, token_id, vote_plus, vote_minus, created_time)
            VALUES ( %d, %d, %d, %d, %d, %s)',
                intval( $this->project_id ),
                intval( $proposal_id ),
                $this->token_post['ID'],
                $vote_plus ,
                $vote_minus,
                $insert_time
                );

        $result = $this->db->query( $sql_comm );
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}
