<?php
  function special_nameparts() {
    return '('.join_clean('|', _('name'), _('title'), _('description'), _('acronym'), _('abbr'), _('abbreviation'), _('value')).')';
  }

  function probability_varchar($field) {
    return 0.1;
  }

  function in_desc_varchar($field) { return $field['FieldNr'] < 5 && preg_match('@'.special_nameparts().'@i', $field['Field']); }
  function in_list_varchar($field) { return $field['FieldNr'] < 5 && preg_match('@'.special_nameparts().'@i', $field['Field']); }
  function in_edit_varchar($field) { return true; }

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
