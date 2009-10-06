<?php
  function probability_boolean($field) {
    if (preg_match('@^(bit|bool|boolean|tinyint)\b@', $field['column_type']))
      return 0.5;
    $distinct = query1('data', "SELECT COUNT(`<fieldname>`) AS numberofrows, SUM(IF(`<fieldname>` = 0, 1, 0)) AS numberofzeros, SUM(IF(`<fieldname>` != 0, 1, 0)) AS numberofnonzeros, COUNT(DISTINCT(`<fieldname>`)) AS numberofdistinctvalues FROM `<databasename>`.`<tablename>`", array('databasename'=>$field['table_schema'], 'tablename'=>$field['table_name'], 'fieldname'=>$field['column_name']));
    return $distinct['numberofrows'] > 10 && $distinct['numberofdistinctvalues'] == 2 && $distinct['numberofzeros'] > 1 && $distinct['numberofnonzeros'] > 1 ? 0.5 : 0;
  }

  function in_desc_boolean($field) { return 0; }
  function in_list_boolean($field) { return 0; }
  function in_edit_boolean($field) { return 1; }

  function is_sortable_boolean() { return true; }

  function formfield_boolean($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'checkbox', 'class'=>join_clean(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'readonly'=>$readonly ? 'readonly' : null, 'checked'=>$value ? 'checked' : null));
  }

  function formvalue_boolean($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }

  function list_boolean($metabasename, $databasename, $field, $value) {
    return formfield_boolean($metabasename, $databasename, $field, $value, true, true, false);
  }
  
  function css_boolean() {
    return "th.boolean, td.boolean { text-align: center; }\n";
  }
?>
