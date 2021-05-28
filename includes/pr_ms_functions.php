<?php
//old not used code
define( 'PR_MS_INPUTS', array(
    'records'   => array( "id" => "prMassSaveItems"),
    'post_type' => array( "id" => "prMassSavePostType", "value" => "imc_issues"),
    'post_status'  => array( "id" => "prMassSaveStatus", "value" => "pending"),
    'taxo_name_1'  => array( "id" => "prMassSaveTaxo1Name",  "value" => "imcstatus"),
    'taxo_value_1' => array( "id" => "prMassSaveTaxo1Value", "value" => "novy"),
    'taxo_name_2'  => array( "id" => "prMassSaveTaxo2Name",  "value" => "imccategory"),
    'taxo_value_2' => array( "id" => "prMassSaveTaxo2Value", "value" => "8"), // musi byt term_id, protoze category je hierarchicka
    'taxo_name_3'  => array( "id" => "prMassSaveTaxo3Name",  "value" => "voting-period"),
    'taxo_value_3' => array( "id" => "prMassSaveTaxo3Value", "value" => "hlasovani-2020"),
  ));
define( 'REGEX_DELIMITER', "/[;|\n]/" );  // ";" + "|" + new line

function pr_mass_save_posts( )
{
  if (isset($_POST[PR_MS_INPUTS['records']["id"]]) && ! empty($_POST[PR_MS_INPUTS['records']["id"]])) {
    // the preg_replace is needed for EOL replacement
    $records = get_list( $_POST[PR_MS_INPUTS['records']["id"]]);
    $saved_posts_count = save_posts($records);
    return "Zjištěno záznamů : ". count($records) . " <br />Uloženo záznamů : " . $saved_posts_count;
  } else {
    return "Nenalezena žádná data k uložení";
  }
}

function get_list( $input)
{
    $pom_str = preg_replace( REGEX_DELIMITER,"<EOV>", $input );
    $pom_array = array_map("trim_spaces", explode( "<EOV>", $pom_str));
    return array_filter( $pom_array, "strlen");
}

function trim_spaces( $str)
{
  return trim($str);
}

function save_posts( $titles)
{
  $post_type = get_one_value(PR_MS_INPUTS['post_type']["id"]);
  if (empty($post_type)) {
    return "Chyba - neni definovaný typ záznamu";
  }

  $post_status = get_one_value(PR_MS_INPUTS['post_status']["id"]);
  $taxo1 = get_taxo(1);
  $taxo2 = get_taxo(2);
  $taxo3 = get_taxo(3);
  $tax_input = array();
  $shared_values = array(
    'post_type' => $post_type,
  );

  if (count($taxo1) > 0) {
        $tax_input[$taxo1['taxo_name']] = $taxo1['term_id'];
  }
  if (count($taxo2) > 0) {
        $tax_input[$taxo2['taxo_name']] = $taxo2['term_id'];
  }
  if (count($taxo3) > 0) {
        $tax_input[$taxo3['taxo_name']] = $taxo3['term_id'];
  }

  if (!empty($post_status)) {
    $shared_values['post_status'] = $post_status;
  }
  if (count($tax_input) >0) {
    $shared_values['tax_input'] = $tax_input;
  }

  $count_rec = 0;
  foreach ($titles as $title) {
    $post_data = $shared_values;
    $post_data['post_title'] = $title;
    $post_data['post_name']  = sanitize_title( $title );
    $post_id = wp_insert_post( $post_data, true );
    $post_id = false;
  	if ( $post_id && ( ! is_wp_error($post_id)) ) {
      $count_rec += 1;
    }
  }
  return $count_rec;
}

function get_one_value( $input_name = "")
{
  if (isset($_POST[$input_name]) && ! empty($_POST[$input_name])) {
    return esc_attr(sanitize_text_field($_POST[$input_name]));
  } else {
    return "";
  }
}

function get_taxo( $id)
{
   $taxo_name  = get_one_value(PR_MS_INPUTS['taxo_name_'.$id]["id"]);
   $taxo_value = get_one_value(PR_MS_INPUTS['taxo_value_'.$id]["id"]);
   if (! empty($taxo_name) && ! empty($taxo_value)) {
     return array(
       'taxo_name' => $taxo_name,
       'term_id'   => $taxo_value
     );
   } else {
     return array();
   }
}

function pr_mass_save_form_render(  $atts, $content, $tag)
{
  if ( isset($_POST['submitted'])) {
        $output = "<h3>" . pr_mass_save_posts() . "</h3>";
        return $output;
  } else {
    $short_code_atts = pr_ms_parse_shortcode_atts( $atts);

    $output  = '<form name="save_many_items_form" action="" id="primaryPostForm" method="POST" enctype="multipart/form-data">';
    $output .= '<div class="imc-row"><textarea rows="5" name="'.PR_MS_INPUTS['records']["id"].'" id="'.PR_MS_INPUTS['records']["id"].'"></textarea>';
    $output .= '</div>';
    $output .= '<div class="imc-row"><input type="hidden" name="submitted" id="submitted" value="true" />';
    $output .= '<input id="prMassSaveSubmitBtn" class="imc-button imc-button-primary imc-button-block"
                  type="submit" value="Uložit" /></div>';
    $output .= pr_ms_generate_hiddeninputs( $short_code_atts );
    $output .= '</form>';

    return $output;

  }
}
function pr_ms_parse_shortcode_atts( $input)
{
    $atts = array_change_key_case((array)$input, CASE_LOWER);

    $atts = shortcode_atts([
        'generate_hiddeninputs' => 0,
      ], $atts);
    return $atts;
}
function pr_ms_generate_hiddeninputs( $atts )
{
  if ( isset( $atts['generate_hiddeninputs']) && $atts['generate_hiddeninputs']) {
    $output  = '<div><input type="hidden" id="'.PR_MS_INPUTS['post_type']["id"].'" name="'.PR_MS_INPUTS['post_type']["id"].'"  value="'.PR_MS_INPUTS['post_type']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['post_status']["id"].'"  name="'.PR_MS_INPUTS['post_status']["id"].'"  value="'.PR_MS_INPUTS['post_status']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_name_1']["id"].'"  name="'.PR_MS_INPUTS['taxo_name_1']["id"].'"  value="'.PR_MS_INPUTS['taxo_name_1']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_value_1']["id"].'" name="'.PR_MS_INPUTS['taxo_value_1']["id"].'" value="'.PR_MS_INPUTS['taxo_value_1']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_name_2']["id"].'"  name="'.PR_MS_INPUTS['taxo_name_2']["id"].'"  value="'.PR_MS_INPUTS['taxo_name_2']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_value_2']["id"].'" name="'.PR_MS_INPUTS['taxo_value_2']["id"].'" value="'.PR_MS_INPUTS['taxo_value_2']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_name_3']["id"].'"  name="'.PR_MS_INPUTS['taxo_name_3']["id"].'"  value="'.PR_MS_INPUTS['taxo_name_3']["value"].'" />';
    $output .= '<input type="hidden" id="'.PR_MS_INPUTS['taxo_value_3']["id"].'" name="'.PR_MS_INPUTS['taxo_value_3']["id"].'" value="'.PR_MS_INPUTS['taxo_value_3']["value"].'" />';
    $output .= '</div>';
  } else {
    $output = "";
  }
  return $output;
}
function mojeFunkce ($a = "", $b = "", $c = "", $d = "" )
{
	$get_token = new PrVt_GenToken(12);
  $new_token = $get_token->getToken(2);
  $save_token = new PrVt_TokenPersist(462);
  $count_saved = 0;
  $error_msgs =  array();

  foreach ($new_token as $item) {
    // $result = $save_token->insert_token($item);
    $result = true;
    if ( $result ) {
        $count_saved += 1;
    } else {
      array_push( $error_msgs, $save_token->get_status_msg());
    }

  }
  $cojevdb = $save_token->get_project_tokens( 462, 'active');
	// $d->set_specific_status( "Vse je ok" );
	return true;
}
