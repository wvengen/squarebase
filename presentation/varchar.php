<?php
  function special_nameparts($tablename) {
    return '('.join_clean('|', _('name'), _('title'), _('description'), _('acronym'), _('abbr'), _('abbreviation'), _('value'), $tablename).')';
  }

  function probability_varchar($field) {
    return 0.1;
  }

  function in_desc_varchar($field) { return preg_match('@'.special_nameparts($field['Table']).'@i', $field['Field']) ? ($field['FieldNr'] < 5 ? 1 : 0.9) : 0.2; }
  function in_list_varchar($field) { return preg_match('@'.special_nameparts($field['Table']).'@i', $field['Field']) ? ($field['FieldNr'] < 5 ? 1 : 0.9) : 0.2; }
  function in_edit_varchar($field) { return 1; }

  function is_sortable_varchar() { return true; }

  function formfield_varchar($metabasename, $databasename, $field, $value, $readonly) {
    return html('input', array('type'=>'text', 'class'=>join_clean(' ', $field['presentationname'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null));
  }

  function formvalue_varchar($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_varchar($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_varchar() {
    return ".varchar { width: 20em; }\n";
  }
?>
