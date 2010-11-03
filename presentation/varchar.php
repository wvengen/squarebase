<?php
  function special_nameparts($tablename) {
    return '('.implode('|', array(_('name'), _('title'), _('description'), _('acronym'), _('abbr'), _('abbreviation'), _('value'), $tablename)).')';
  }

  function probability_varchar($field) {
    return 0.1;
  }

  function in_desc_varchar($field) { return preg_match('@'.special_nameparts($field['table_name']).'@i', $field['column_name']) ? ($field['fieldnr'] < 5 ? 1 : 0.9) : ($field['fieldnr'] < 5 ? 0.3 : 0.2); }
  function in_list_varchar($field) { return preg_match('@'.special_nameparts($field['table_name']).'@i', $field['column_name']) ? ($field['fieldnr'] < 5 ? 1 : 0.9) : ($field['fieldnr'] < 5 ? 0.3 : 0.2); }
  function in_edit_varchar($field) { return 1; }

  function is_sortable_varchar() { return true; }
  function is_quickaddable_varchar() { return true; }

  function formfield_varchar($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_varchar($field) {
    $value = get_parameter($_POST, "field:$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_varchar($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_varchar() {
    return
      ".varchar.edit { width: 20em; }\n".
      ".varchar.list { width: 10em; }\n";
  }
?>
