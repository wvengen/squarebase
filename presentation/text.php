<?php
  function probability_text($field) {
    return preg_match('/^(tiny||medium|long)text\b/', $field['Type']) ? 0.5 : 0;
  }

  function typename_text($field) {
    return $field['Field'];
  }

  function in_desc_text() { return 0; }
  function in_sort_text() { return 0; }
  function in_list_text() { return 0; }
  function in_edit_text() { return 1; }

  function formfield_text($metabasename, $databasename, $field, $value, $readonly) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'class'=>$field['presentation'], 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null), preg_replace('/<(.*?)>/', '&lt;$1&gt;', $value));
  }

  function formvalue_text($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_text($metabasename, $databasename, $field, $value) {
    return $value;
  }
?>
