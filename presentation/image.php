<?php
  function probability_image($field) {
    return preg_match('/^(tiny||medium|long)blob\b/', $field['Type']) ? 0.5 : 0;
  }

  function typename_image($field) {
    return 'image';
  }

  function in_desc_image($field) { return 0; }
  function in_sort_image($field) { return 0; }
  function in_list_image($field) { return 0; }
  function in_edit_image($field) { return 1; }

  function formfield_image($metabasename, $databasename, $field, $value, $readonly) {
    return
      $readonly
      ? list_image($metabasename, $databasename, $field, $value)
      : html('fieldset', array('class'=>join(' ', cleanlist(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty')))),
          html('ul', array(),
            html('li', array(),
              array(
                $value ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$field[fieldname]", 'id'=>"radio:original:$field[fieldname]", 'value'=>'original', 'checked'=>'checked'), html('input', array('type'=>'hidden', 'name'=>"original:$field[fieldname]", 'value'=>base64_encode($value))).html('label', array('for'=>"radio:original:$field[fieldname]"), list_image($metabasename, $databasename, $field, $value))) : null,
                html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$field[fieldname]", 'id'=>"radio:none:$field[fieldname]", 'value'=>'none', 'checked'=>$value ? null : 'checked'), html('label', array('for'=>"radio:none:$field[fieldname]"), 'none')),
                html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$field[fieldname]", 'id'=>"radio:new:$field[fieldname]", 'value'=>'new'), html('label', array('for'=>"radio:new:$field[fieldname]"), html('input', array('type'=>'file', 'class'=>join(' ', cleanlist(array($field['presentation'], $readonly ? 'readonly' : null, $field['nullallowed'] ? null : 'notempty'))), 'name'=>"field:$field[fieldname]", 'readonly'=>$readonly ? 'readonly' : null, 'disabled'=>$readonly ? 'disabled' : null))))
              )
            )
          )
        );
  }

  function formvalue_image($field) {
    $choice = parameter('get', "radio:$field[fieldname]");
    switch ($choice) {
    case 'original':
      return base64_decode(parameter('get', "original:$field[fieldname]"));
    case 'none':
      return null;
    case 'new':
      $file = parameter('files', "field:$field[fieldname]");
      return $file['tmp_name'] ? file_get_contents($file['tmp_name']) : null;
    }
    return null;
  }

  function list_image($metabasename, $databasename, $field, $value) {
    return $value ? html('img', array('src'=>internalurl(array('action'=>'get_image', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$field['tableid'], 'uniquevalue'=>$field['uniquevalue'], 'fieldname'=>$field['fieldname'])))) : '';
  }
  
  function css_image() {
    return
      ".image ul { margin: 0; padding: 0; }\n".
      ".image li { list-style-type: none; }\n".
      ".image .radio { margin-right: 0.3em; }\n".
      ".image img { width: 2em; height: 2em; }\n";
  }
?>
