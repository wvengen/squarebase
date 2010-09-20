<?php
  function probability_datetime($field) {
    return preg_match('@^datetime\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_datetime($field) { return 0; }
  function in_list_datetime($field) { return 0; }
  function in_edit_datetime($field) { return 1; }

  function is_sortable_datetime() { return true; }
  function is_quickaddable_datetime() { return true; }

  function formfield_datetime($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return 
      html('input', array('type'=>'text', 'class'=>join_non_null(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>datetime2local($value), 'readonly'=>$readonly ? 'readonly' : null)).
      ($extra ? html('span', array('class'=>'help', 'title'=>sprintf(_('Date/time format: %s'), find_datetime_format('%X', 'text'))), _('?')) : '');
  }

  function formattedsql_datetime($fieldname) {
    return 'DATE_FORMAT('.$fieldname.', \''.find_datetime_format('%X', 'mysql').'\')';
  }

  function formvalue_datetime($field) {
    $value = parameter('post', "field:$field[fieldname]");
    return $value == '' ? null : local2datetime($value);
  }

  function list_datetime($metabasename, $databasename, $field, $value) {
    return htmlentities(datetime2local($value));
  }

  function datetime2local($value) {
    return change_datetime_format($value, '%Y-%m-%d %H:%M:%S', '%x %X');
  }
  
  function local2datetime($value) {
    return change_datetime_format($value, '%x %X', '%Y-%m-%d %H:%M:%S');
  }
  
  function css_datetime() {
    return
      ".datetime.edit { width: 20em; }\n".
      ".datetime.list { width: 6em; }\n";
  }
?>
