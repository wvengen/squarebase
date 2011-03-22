<?php
  function probability_int($field) {
    // ordinary integers
    if (preg_match('@^(tinyint|smallint|mediumint|int|integer|bigint)\b@', $field['column_type']))
      return 0.4;
    // real numbers without decimals
    if (preg_match('@^(real|double|float|decimal|numeric)\s*\(\s*\d+\s*,\s*0\s*\)@', $field['column_type']))
      return 0.4;
    return 0;
  }

  function in_desc_int($field) { return 0; }
  function in_list_int($field) { return 0; }
  function in_edit_int($field) { return 1; }

  function is_sortable_int() { return true; }
  function is_quickaddable_int() { return true; }

  function formfield_int($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field-$field[fieldname]", 'id'=>"field-$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_int($field) {
    $value = get_post("field-$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_int($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_int() {
    return
      ".int { text-align: right; }\n".
      ".int.list, .int.edit { width: 3em; }\n";
  }
?>
