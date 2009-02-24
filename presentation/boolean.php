<?php
  function probability_boolean($field) {
    if (preg_match('/^(bit|bool|boolean)\b/', $field['Type']))
      return 0.5;
    $distinct = query1('data', "SELECT COUNT($field[Field]) AS numberofrows, SUM(IF($field[Field] = 0, 1, 0)) AS numberofzeros, SUM(IF($field[Field] != 0, 1, 0)) AS numberofnonzeros, COUNT(DISTINCT($field[Field])) AS numberofdistinctvalues FROM `$field[Database]`.$field[Table]");
    return $distinct['numberofrows'] > 10 && $distinct['numberofdistinctvalues'] == 2 && $distinct['numberofzeros'] > 1 && $distinct['numberofnonzeros'] > 1 ? 0.5 : 0;
  }

  function typename_boolean($field) {
    return $field['Field'];
  }

  function in_desc_boolean() { return 0; }
  function in_sort_boolean() { return 0; }
  function in_list_boolean() { return 0; }
  function in_edit_boolean() { return 1; }

  function formfield_boolean($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'checkbox', 'class'=>$field['presentation'], 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null, 'checked'=>$value ? 'checked' : null));
  }

  function formvalue_boolean($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }

  function cell_boolean($metabasename, $databasename, $field, $value) {
    return formfield_boolean($metabasename, $databasename, $field, $value, true);
  }
  
  function css_boolean() {
    return ".boolean { margin: 0; }\n";
  }
?>
