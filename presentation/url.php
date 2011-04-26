<?php
  function probability_url($field) {
    if (!preg_match('@^(var)?char\b@', $field['column_type']))
      return 0;
    $texts = query('SELECT `<fieldname>` FROM `<databasename>`.`<tablename>` WHERE `<fieldname>` IS NOT NULL LIMIT 10', array('fieldname'=>$field['column_name'], 'tablename'=>$field['table_name'], 'databasename'=>$field['table_schema']));
    if (mysql_num_rows($texts) == 0)
      return 0;
    while ($text = mysql_fetch_assoc($texts)) {
      if (!preg_match('@^https?://@', $text[$field['column_name']]))
        return 0;
    }
    return 0.6;
  }

  function in_desc_url($field) { return 0; }
  function in_list_url($field) { return 0; }
  function in_edit_url($field) { return 1; }

  function is_sortable_url() { return false; }
  function is_quickaddable_url() { return true; }

  function formfield_url($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return
      html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field-$field[fieldname]", 'id'=>"field-$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null)).
      ($extra && $value ? external_reference($value, 'link') : '');
  }

  function formvalue_url($field) {
    $value = get_post("field-$field[fieldname]", null);
    return $value == '' ? null : $value;
  }

  function list_url($metabasename, $databasename, $field, $value) {
    return htmlentities($value);
  }
  
  function css_url() {
    return ".url.edit { width: 20em; }\n";
  }
?>
