<?php
/**
 * Process parameter from jetEngine form.
 *
 *
 * @package paro
 * @since 0.2.0
 */
class PrVt_FormParams
{
    /**
    * Array with values from jetEngine form.
    * @since 0.2.0
    * @var array $params
    */
    protected $params = array();
    /**
    * Error messages. Are set in costructor because of calling translation function.
    * @since 0.2.0
    * @var array $messages
    */
    protected $messages = array();
    /**
    * Result.
    * @since 0.2.0
    * @var array $messages
    */
    protected $result = array();

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
        $this->set_messages();
    }

    protected function set_messages()
    {
      $this->messages = array(
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
    * @since 0.2.0
    */
    protected function set_params()
    {
      // to be overwritten
    }
    /**
    * Sets error message to the result.
    *
    * @since 0.2.0
    */
    protected function set_result_error( $msg_type)
    {
        $this->result =  array(
          'result'  => 'error',
          'message' => $this->messages[ $msg_type],
        );
    }
    /**
    * Sets OK message to the result.
    *
    * @since 0.2.0
    */
    protected function set_result_ok( )
    {
        $this->result =  array(
          'result'  => 'ok',
          'message' => $this->messages[ 'ok'],
        );
    }

    /**
    * Reads one value from array.
    *
    * Check if the required values is set. If not return empty string.
    *
    * @since 0.2.0
    *
    * @param string $param_name Name of the key in the array.
    *
    * @return string Parametr value.
    */
    public function getValueFromParams( $param_name = "")
    {
      if ((! empty($param_name)) && isset( $this->params[ $param_name]) && (!empty($this->params[ $param_name])) ) {
          return $this->params[ $param_name];
      } else {
          return "";
      }
    }
}
