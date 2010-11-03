<?php
  function probability_float($field) {
    // probability is lower than int to get it precedence with zero digits
    return preg_match('@^(real|double|float|decimal|numeric)\b@', $field['column_type']) ? 0.3 : 0;
  }

  function in_desc_float($field) { return 0; }
  function in_list_float($field) { return 0; }
  function in_edit_float($field) { return 1; }

  function is_sortable_float() { return true; }
  function is_quickaddable_float() { return true; }

  function formfield_float($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_float($field) {
    $value = get_parameter($_POST, "field:$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_float($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_float() {
    return
      ".float { text-align: right; }\n";
  }
?>
