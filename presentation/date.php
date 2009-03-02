<?php
  function probability_date($field) {
    return preg_match('/^date\b/', $field['Type']) ? 0.5 : 0;
  }

  function typename_date($field) {
    return 'date';
  }

  function in_desc_date() { return 0; }
  function in_sort_date() { return 0; }
  function in_list_date() { return 0; }
  function in_edit_date() { return 1; }

  function formfield_date($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'].' '.($readonly ? 'readonly' : ''), 'name'=>"field:$field[fieldname]", 'value'=>swap_date($value), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_date($field) {
    return swap_date(parameter('get', "field:$field[fieldname]"));
  }

  function cell_date($metabasename, $databasename, $field, $value) {
    return swap_date($value);
  }

  function swap_date($value) {
    if (!preg_match('/^(\d+)-(\d+)-(\d+)$/', $value, $matches))
      return $value;
    return "$matches[3]-$matches[2]-$matches[1]";
  }
  
  function css_date() {
    return '';
  }
?>
