<?php
  // list of auto-detected currencies
  // see also: http://en.wikipedia.org/wiki/List_of_circulating_currencies
  global $currencies;
  $currencies = array(
    _('price')=> NULL,
    _('currency')=> NULL,
    _('cost')=> NULL,
    _('bucks')=> '$',
    _('dollar')=> '$',
    _('euro')=> '&euro;'
  );

  function probability_currency($field) {
    global $currencies;
    if (!probability_int($field) && !probability_float($field))
      return 0;
    if (preg_match('@('.join('|',array_keys($currencies)).')@', $field['Field']))
      return 0.5;
    return 0.2;
  }

  function typename_currency($field) {
    return $field['Field'];
  }

  function in_desc_currency($field) { return false; }
  function in_list_currency($field) { return false; }
  function in_edit_currency($field) { return true; }

  function is_sortable_currency() { return true; }

  function formfield_currency($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>join(' ', array_clean(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_currency($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_currency($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_currency() {
    return ".currency { text-align: right; }\n";
  }
?>
