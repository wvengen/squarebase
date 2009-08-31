<?php
  function probability_text($field) {
    return preg_match('@^(tiny||medium|long)text\b@', $field['Type']) ? 0.5 : 0;
  }

  function in_desc_text($field) { return 0; }
  function in_list_text($field) { return 0; }
  function in_edit_text($field) { return 1; }

  function is_sortable_text() { return true; }

  function formfield_text($metabasename, $databasename, $field, $value, $readonly) {
    return html('textarea', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join_clean(' ', $field['presentationname'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), preg_replace('@<(.*?)>@', '&lt;$1&gt;', $value));
  }

  function formvalue_text($field) {
    $value = parameter('get', "field:$field[fieldname]");
    return $value == "" ? null : $value;
  }

  function list_text($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_text() {
    return ".text { width: 20em; height: 10em; white-space: pre-wrap; }\n";
  }
?>
