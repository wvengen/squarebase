<?php
  function probability_image($field) {
    return preg_match('@^(tiny||medium|long)blob\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_image($field) { return 0; }
  function in_list_image($field) { return 0; }
  function in_edit_image($field) { return 1; }

  function is_sortable_image() { return false; }
  function is_quickaddable_image() { return false; }

  function ajax_image($metabasename, $databasename, $tablename, $fieldname, $value, $presentationname, $uniquefieldname, $uniquevalue, $nullallowed, $defaultvalue, $readonly, $extra, $newname = null) {
    $field = array('tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'fieldname'=>$fieldname);
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'ajax_image', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname, 'value'=>$value ? 1 : 0, 'presentationname'=>$presentationname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'nullallowed'=>$nullallowed, 'defaultvalue'=>$defaultvalue, 'readonly'=>$readonly, 'extra'=>$extra ? 1 : 0, 'newname'=>$newname ? $newname : ''))),
        html('div', array(),
          $readonly
          ? list_image($metabasename, $databasename, $field, $value)
          : html($extra ? 'fieldset' : 'div', array('class'=>join_non_null(' ', $presentationname, $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty')),
              html('ul', array('class'=>'minimal'),
                html('li', array(),
                  array(
                    $value
                    ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$fieldname", 'id'=>"radio:original:$fieldname", 'value'=>'original', 'checked'=>$newname ? null : 'checked')).
                      html('label', array('for'=>"radio:original:$fieldname"),
                        html('span', array('class'=>'filesource'), _('original')).
                        list_image($metabasename, $databasename, $field, $value)
                      )
                    : null,
                    html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$fieldname", 'id'=>"radio:none:$fieldname", 'value'=>'none', 'checked'=>$value ? null : 'checked')).
                    html('label', array('for'=>"radio:none:$fieldname", 'class'=>'filesource'), 'none'),
                    ($newname
                    ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$fieldname", 'id'=>"radio:new:$fieldname", 'value'=>'new', 'checked'=>'checked')).
                      html('label', array('for'=>"radio:new:$fieldname"),
                        html('span', array('class'=>'filesource'), _('new')).
                        html('img', array('src'=>internal_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'new_image', 'newname'=>$newname)), 'alt'=>_('new image'), 'class'=>'listimage')).
                        html('input', array('type'=>'hidden', 'name'=>"field:new:$fieldname", 'value'=>$newname))
                      )
                    : null
                    ),
                    html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio:$fieldname", 'id'=>"radio:upload:$fieldname", 'value'=>'upload')).
                    html('label', array('for'=>"radio:upload:$fieldname"),
                      html('span', array('class'=>'filesource'), _('upload')).
                      html('input', array('type'=>'file', 'class'=>join_non_null(' ', $presentationname, $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty'), 'id'=>"field:$fieldname", 'name'=>"field:$fieldname", 'readonly'=>$readonly ? 'readonly' : null))
                    )
                  )
                )
              )
            )
        )
      );
  }

  function formfield_image($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return ajax_image($metabasename, $databasename, $field['tablename'], $field['fieldname'], $value, $field['presentationname'], $field['uniquefieldname'], $field['uniquevalue'], $field['nullallowed'], $field['defaultvalue'], $readonly, $extra);
  }

  function formvalue_image($field) {
    $choice = parameter('post', "radio:$field[fieldname]");
    switch ($choice) {
    case 'original':
      return query1field('data', 'SELECT <fieldname> FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('fieldname'=>$field['fieldname'], 'databasename'=>$field['databasename'], 'tablename'=>$field['tablename'], 'uniquefieldname'=>$field['uniquefieldname'], 'uniquevalue'=>$field['uniquevalue']));
    case 'none':
      return null;
    case 'new':
      $newname = directorypart(parameter('post', "field:new:$field[fieldname]"));
      $file = "./uploads/$newname";
      $image = file_get_contents($file);
      unlink($file);
      return $image;
    case 'upload':
      $file = parameter('files', "field:$field[fieldname]");
      return $file['tmp_name'] ? file_get_contents($file['tmp_name']) : null;
    }
    return null;
  }

  function list_image($metabasename, $databasename, $field, $value) {
    return $value ? html('img', array('src'=>internal_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'get_image', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$field['tablename'], 'uniquefieldname'=>$field['uniquefieldname'], 'uniquevalue'=>$field['uniquevalue'], 'fieldname'=>$field['fieldname'], 'forcereload'=>time())), 'alt'=>_('uploaded image'), 'class'=>'listimage')) : '';
  }
  
  function get_image() {
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $uniquevalue     = parameter('get', 'uniquevalue');
    $fieldname       = parameter('get', 'fieldname');

    $image = query1field('data', 'SELECT <fieldname> FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('fieldname'=>$fieldname, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
    http_response('Content-type: image/jpeg', $image);
  }

  function new_image() {
    $newname = directorypart(parameter('get', 'newname'));

    $image = file_get_contents("./uploads/$newname");
    http_response('Content-type: image/jpeg', $image);
  }

  function file_upload_error_message($error_code) {
    switch ($error_code) {
    case UPLOAD_ERR_INI_SIZE:
      return _('the uploaded file exceeds the upload_max_filesize directive in php.ini');
    case UPLOAD_ERR_FORM_SIZE:
      return _('the uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
    case UPLOAD_ERR_PARTIAL:
      return _('the uploaded file was only partially uploaded');
    case UPLOAD_ERR_NO_FILE:
      return _('no file was uploaded');
    case UPLOAD_ERR_NO_TMP_DIR:
      return _('missing a temporary folder');
    case UPLOAD_ERR_CANT_WRITE:
      return _('failed to write file to disk');
    case UPLOAD_ERR_EXTENSION:
      return _('file upload stopped by extension');
    default:
      return _('unknown upload error');
    }
  } 

  function upload_image() {
    $ajax = parameter('get', 'ajax');
    if (count($_FILES) == 1) {
      $names = array_keys($_FILES);
      $file = $_FILES[$names[0]];
      if ($file['error'] == UPLOAD_ERR_OK) {
        if ($file['type'] == 'image/jpeg') {
          if (preg_match('@^\w+\.jpe?g$@i', $file['name'])) {
            $newname = strftime('%Y_%m_%d_%H_%M_%S').'_'.directorypart($file['name']);
            if (move_uploaded_file($file['tmp_name'], "./uploads/$newname"))
              $ajax = preg_replace('@\bnewname=[^&]*@', "newname=$newname", $ajax);
          }
          else
            $warning = sprintf(_('invalid file extension: %s'), $file['name']);
        }
        else
          $warning = sprintf(_('invalid mime type: %s'), $file['type']);
      }
      else
        $warning = file_upload_error_message($file['error']);
    }
    else
      $warning = sprintf(_('not 1 file uploaded but %d'), count($_FILES));
    if ($warning)
      add_log('warnings', 'warning', $warning);
    call_function($ajax);
  }

  function css_image() {
    return
      ".image.edit img { max-width: 3em; max-height: 3em; }\n".
      ".image.edit img:hover { max-width: none; max-height: none; }\n".
      ".image .filesource { display: inline-block; width: 4em; }\n".
      ".image.list li { display: inline; }\n".
      ".list img.listimage { max-height: 1em !important; }\n".
      ".list img.listimage:hover { max-height: 4em !important; }\n";
  }

  function jquery_enhance_form_image() {
    return
      "getScripts(['jquery.uploadfile.js'], '.image',\n".
      "  function() {\n".
      "    $(this).\n".
      "    find(':input[type=file]').\n".
      "      change(\n".
      "        function() {\n".
      "          var inputfile = $(this);\n".
      "          $(this).\n".
      "          uploadFile(\n".
      "            'index.php?action=call_function&presentationname=image&functionname=upload_image&ajax=' + escape($(this).closest('.ajax').attr('id')),\n".
      "            function(iframe) {\n".
      "              inputfile.\n".
      "              closest('.ajax').\n".
      "              parent().\n".
      "              empty().\n".
      "              append(\n".
      "                iframe.\n".
      "                contents().\n".
      "                find('#content').\n".
      "                html()\n".
      "              ).\n".
      "              enhance_form();\n".
      "            }\n".
      "          );\n".
      "        }\n".
      "      ).\n".
      "    end();\n".
      "  }\n".
      ").\n";
  }
?>
