<?php
  function probability_enum($field) {
    if (preg_match('@^(enum|set)\b@', $field['column_type']))
      return 0.9;
    $distinct = query1('SELECT COUNT(`<fieldname>`) AS numberofrows, COUNT(DISTINCT(`<fieldname>`)) AS numberofdistinctvalues FROM `<databasename>`.`<tablename>`', array('databasename'=>$field['table_schema'], 'tablename'=>$field['table_name'], 'fieldname'=>$field['column_name']));
    return !$distinct['numberofrows'] ? 0 : 0.4 * (1 - $distinct['numberofdistinctvalues'] / $distinct['numberofrows']);
  }

  function in_desc_enum($field) { return 0; }
  function in_list_enum($field) { return 0; }
  function in_edit_enum($field) { return 1; }

  function is_sortable_enum() { return true; }
  function is_quickaddable_enum() { return true; }

  function formfield_enum($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    $options = array();
    if ($readonly)
      $options[] = html('option', array('value'=>$value, 'selected'=>'selected'), htmlentities($value));
    else {
      $enums = explode("','", preg_match1("@(?:enum|set)\('(.+?)'\)@", $field['type']));
      $oneselected = false;
      foreach ($enums as $enum) {
        $selected = $value == $enum;
        $oneselected = $oneselected || $selected;
        $options[] = html('option', array_merge(array('value'=>$enum), $selected ? array('selected'=>'selected') : array()), $enum);
      }
      if ($field['nullallowed'])
        array_unshift($options, html('option', array_merge(array('value'=>''), $oneselected ? array() : array('selected'=>'selected')), ''));
      if (!$oneselected && $value)
        array_unshift($options, html('option', array_merge(array('value'=>$value), array('selected'=>'selected')), $value));
    }
    return html('select', array('name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'class'=>join_non_null_with_blank($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'readonly'=>$readonly ? 'readonly' : null), join($options));
  }

  function formvalue_enum($field) {
    return get_parameter($_POST, "field:$field[fieldname]", null);
  }

  function list_enum($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_enum() {
    return ".enum.edit { width: 20.5em; }\n";
  }
?>
