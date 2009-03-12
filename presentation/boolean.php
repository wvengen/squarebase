<?php
  function probability_boolean($field) {
    if (preg_match('/^(bit|bool|boolean)\b/', $field['Type']))
      return 0.5;
    $distinct = query1('data', "SELECT COUNT(`<fieldname>`) AS numberofrows, SUM(IF(`<fieldname>` = 0, 1, 0)) AS numberofzeros, SUM(IF(`<fieldname>` != 0, 1, 0)) AS numberofnonzeros, COUNT(DISTINCT(`<fieldname>`)) AS numberofdistinctvalues FROM `<databasename>`.`<tablename>`", array('databasename'=>$field['Database'], 'tablename'=>$field['Table'], 'fieldname'=>$field['Field']));
    return $distinct['numberofrows'] > 10 && $distinct['numberofdistinctvalues'] == 2 && $distinct['numberofzeros'] > 1 && $distinct['numberofnonzeros'] > 1 ? 0.5 : 0;
  }

  function typename_boolean($field) {
    return strtolower($field['Field']);
  }

  function in_desc_boolean($field) { return false; }
  function in_list_boolean($field) { return false; }
  function in_edit_boolean($field) { return true; }

  function formfield_boolean($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'checkbox', 'class'=>join(' ', array_clean(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null, 'checked'=>$value ? 'checked' : null));
  }

  function formvalue_boolean($field) {
    return parameter('get', "field:$field[fieldname]") ? 1 : 0;
  }

  function list_boolean($metabasename, $databasename, $field, $value) {
    return formfield_boolean($metabasename, $databasename, $field, $value, true);
  }
  
  function css_boolean() {
    return '';
  }
?>
