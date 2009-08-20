<?php
  function probability_url($field) {
    if (!preg_match('@^(var)?char\b@', $field['Type']))
      return 0;
    $texts = query('data', "SELECT $field[Field] FROM `$field[Database]`.$field[Table] WHERE $field[Field] IS NOT NULL LIMIT 10");
    if (mysql_num_rows($texts) == 0)
      return 0;
    while ($text = mysql_fetch_assoc($texts)) {
      if (!preg_match('@^https?://@', $text[$field['Field']]))
        return 0;
    }
    return 0.6;
  }

  function in_desc_url($field) { return 0; }
  function in_list_url($field) { return 0; }
  function in_edit_url($field) { return 1; }

  function is_sortable_url() { return false; }

  function formfield_url($metabasename, $databasename, $field, $value, $readonly) {
    return
      html('input', array('type'=>'text', 'class'=>join_clean(' ', $field['presentationname'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'id'=>"field:$field[fieldname]", 'value'=>$value, 'readonly'=>$readonly ? 'readonly' : null)).
      ($value ? externalreference($value, 'link') : '');
  }

  function formvalue_url($field) {
    return parameter('get', "field:$field[fieldname]");
  }

  function list_url($metabasename, $databasename, $field, $value) {
    return $value;
  }
  
  function css_url() {
    return ".url { width: 20em; }\n";
  }
?>
