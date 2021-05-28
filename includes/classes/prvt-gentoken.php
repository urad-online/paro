<?php
/**
 * Generates tokens.
 *
 * Generate tokens with chars [a-z][A-Z][0-9]. The length and number of tokens is parametr.
 * Parameter shall sent to construct in assoc. array. If not defined, default values are used.
 *
 * @package paro
 * @since 0.1.0
 */
class PrVt_GenToken
{
    /**
    * List of all allowed chars.
    * @since 0.1.0
    * @var string $used_chars Chars that are used in token value. One char is selected by random index.
    */
    private $used_chars     = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    /**
    * Lenght of the string with allowed chars. Value is set in constructor.
    * @since 0.1.0
    * @var int $used_chars_len
    */
    private $used_chars_len = 0;
    /**
    * Default value of token length.
    * @since 0.1.0
    * @var int $token_length
    */
    private $token_length = 10;
    /**
    * Default value of number of generated tokens.
    * @since 0.1.0
    * @var int $token_count
    */
    private $token_count  = 1;

    public function __construct( $params = null)
    {
        $this->params = $params;
        $this->set_params();
        $this->used_chars_len = strlen( $this->used_chars);

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
      $token_length = intval($this->getValueFromParams( INPUTS_FORM_GENERATE['token_length']));
      if (!empty($token_length) && ($token_length >0)) {
        $this->token_length = $token_length;
      }
      $token_count = intval($this->getValueFromParams( INPUTS_FORM_GENERATE['token_count']));
      if (!empty($token_count) && ($token_count >0)) {
        $this->token_count = $token_count;
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
    * Generates random integer number within min and max value.
    *
    * Check if the required values is set. If not return empty string.
    *
    * @since 0.1.0
    *
    * @param int $min Lower interval limit.
    * @param int $max upper inteval limit.
    *
    * @return int random integer.
    */
    protected function get_random_index($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    /**
    * Generates tokens.
    *
    * For one token creates the value char by char. Calls the function for random index and
    * selects a char from the set of allowed values by the index.
    * Repeats that for required number of tokens
    *
    * @since 0.1.0
    *
    * @return array Set of strings with token's values.
    */
    public function getToken()
    {
        $token_array = array();
        for ($j=0; $j < $this->token_count; $j++) {
          $token = "";

          for ($i=0; $i < $this->token_length; $i++) {
            $token .= $this->used_chars[ $this->get_random_index(0, $this->used_chars_len-1)];
          }
          array_push( $token_array, $token);
        }

        return $token_array;
    }

}
