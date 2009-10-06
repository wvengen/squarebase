<?php
  function probability_date($field) {
    return preg_match('@^date\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_date($field) { return 0; }
  function in_list_date($field) { return 0; }
  function in_edit_date($field) { return 1; }

  function is_sortable_date() { return true; }

  function formfield_date($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return 
      html('input', array('type'=>'text', 'class'=>join_clean(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'value'=>date2local($value), 'readonly'=>$readonly ? 'readonly' : null)).
      ($extra ? html('span', array('class'=>'help'), find_datetime_format('%x')) : '');
  }

  function formattedsql_date($fieldname) {
    return 'DATE_FORMAT('.$fieldname.', \''.find_datetime_format('%x', 'mysql').'\')';
  }

  function formvalue_date($field) {
    $value = parameter('get', "field:$field[fieldname]");
    return $value == "" ? null : local2date($value);
  }

  function list_date($metabasename, $databasename, $field, $value) {
    return date2local($value);
  }

  function date2local($value) {
    return change_datetime_format($value, '%Y-%m-%d', '%x');
  }
  
  function local2date($value) {
    return change_datetime_format($value, '%x', '%Y-%m-%d');
  }
  
  function css_date() {
    return
      ".date.edit { width: 20em; }\n".
      ".date.list { width: 6em; }\n";
  }
?>
