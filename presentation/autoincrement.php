<?php
  function probability_autoincrement($field) {
    return preg_match('/(auto_increment)/', $field['Extra']) ? 0.7 : 0;
  }

  function typename_autoincrement($field) {
    return 'autoincrement';
  }

  function in_desc_autoincrement() { return 0; }
  function in_sort_autoincrement() { return 0; }
  function in_list_autoincrement() { return 0; }
  function in_edit_autoincrement() { return 1; }

  function formfield_autoincrement($metabasename, $databasename, $field, $value, $readonly) {
    return html('span', array('class'=>$field['presentation']), $value);
  }

  function formvalue_autoincrement($field) {
    return null;
  }

  function cell_autoincrement($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_autoincrement() {
    return ".autoincrement { color: #666; }\n";
  }
?>
