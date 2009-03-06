<?php
  function probability_int($field) {
    // ordinary integers
    if (preg_match('/^(tinyint|smallint|mediumint|int|integer|bigint)\b/', $field['Type']))
      return 0.4;
    // real numbers without decimals
    if (preg_match('/^(real|double|float|decimal|numeric)\s*\(\s*\d+\s*,\s*0\s*\)/', $field['Type']))
      return 0.4;
    return 0;
  }

  function typename_int($field) {
    return strtolower($field['Field']);
  }

  function in_desc_int($field) { return 0; }
  function in_sort_int($field) { return 0; }
  function in_list_int($field) { return 0; }
  function in_edit_int($field) { return 1; }

  function formfield_int($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>join(' ', cleanlist(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null));
  }

  function formvalue_int($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function cell_int($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_int() {
    $width = $field[''];
    return ".int { text-align: right; width: 3em; }\n";
  }
?>
