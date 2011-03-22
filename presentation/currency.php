<?php
  function probability_currency($field) {
    if (!probability_int($field) && !probability_float($field))
      return 0;
    if (preg_match('@('.implode('|', array(_('price'), _('currency'), _('cost'), _('bucks'), _('dollar'), _('euro'))).')@', $field['column_name']))
      return 0.5;
    return 0.2;
  }

  function in_desc_currency($field) { return 0; }
  function in_list_currency($field) { return 0; }
  function in_edit_currency($field) { return 1; }

  function is_sortable_currency() { return true; }
  function is_quickaddable_currency() { return true; }

  function formfield_currency($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field-$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_currency($field) {
    $value = get_post("field-$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_currency($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_currency() {
    return ".currency { text-align: right; }\n";
  }
?>
