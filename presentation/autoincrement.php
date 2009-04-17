<?php
  function probability_autoincrement($field) {
    return preg_match('@(auto_increment)@', $field['Extra']) ? 0.7 : 0;
  }

  function typename_autoincrement($field) {
    return 'autoincrement';
  }

  function in_desc_autoincrement($field) { return false; }
  function in_list_autoincrement($field) { return false; }
  function in_edit_autoincrement($field) { return true; }

  function is_sortable_autoincrement() { return false; }

  function formfield_autoincrement($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>join_clean(' ', $field['presentationname'], 'readonly'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>'readonly'));
  }

  function formvalue_autoincrement($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_autoincrement($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_autoincrement() {
    return ".autoincrement { width: 20em; }\n";
  }
?>
