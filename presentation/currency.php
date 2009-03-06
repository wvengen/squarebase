<?php

  // list of auto-detected currencies
  // see also: http://en.wikipedia.org/wiki/List_of_circulating_currencies
  global $currencies;
  $currencies = array(
    'price' => NULL,
    'currency' => NULL,
    'cost' => NULL,
    'bucks' => '$',
    'dollar' => '$',
    'euro' => '&euro;'
  );

  function probability_currency($field) {
    global $currencies;
    static $currencyregexp;
    if (!$currencyregexp)
      $currencyregexp = '/('.join('|',array_keys($currencies)).')/';

    if (!probability_int($field) && !probability_float($field))
      return 0;
    if (preg_match($currencyregexp, $field['Field']))
      return 0.5;
    return 0.2;
  }

  function typename_currency($field) {
    return $field['Field'];
  }

  function in_desc_currency($field) { return 0; }
  function in_sort_currency($field) { return 0; }
  function in_list_currency($field) { return 0; }
  function in_edit_currency($field) { return 1; }

  function formfield_currency($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>join(' ', cleanlist(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_currency($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_currency($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_currency() {
    return ".currency { text-align: right; }\n";
  }
?>
