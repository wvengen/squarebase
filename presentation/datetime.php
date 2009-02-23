<?php
  function probability_datetime($field) {
    return preg_match('/^datetime\b/', $field['Type']) ? 0.5 : 0;
  }

  function typename_datetime($field) {
    return 'datetime';
  }

  function in_desc_datetime() { return 0; }
  function in_sort_datetime() { return 0; }
  function in_list_datetime() { return 0; }
  function in_edit_datetime() { return 1; }

  function formfield_datetime($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'value'=>swap_datetime($value), 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_datetime($field) {
    return swap_datetime(parameter('get', "field:$field[fieldname]"));
  }

  function cell_datetime($metabasename, $databasename, $field, $value) {
    return swap_datetime($value);
  }

  function swap_datetime($value) {
    if (!preg_match('/^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)$/', $value, $matches))
      return $value;
    return "$matches[3]-$matches[2]-$matches[1] $matches[4]:$matches[5]:$matches[6]";
  }
  
  function css_datetime() {
    return '';
  }
?>
