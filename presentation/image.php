<?php
  function probability_image($field) {
    return preg_match('@^(tiny||medium|long)blob\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_image($field) { return 0; }
  function in_list_image($field) { return 0; }
  function in_edit_image($field) { return 1; }

  function is_sortable_image() { return false; }
  function is_quickaddable_image() { return false; }

  callable_function('ajax_image', array('metabasename', 'databasename', 'tablename', 'fieldname', 'value', 'presentationname', 'uniquefieldname', 'uniquevalue', 'nullallowed', 'defaultvalue', 'readonly', 'extra', 'newname'));

  function ajax_image($metabasename, $databasename, $tablename, $fieldname, $value, $presentationname, $uniquefieldname, $uniquevalue, $nullallowed, $defaultvalue, $readonly, $extra, $newname = null) {
    $field = array('tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'fieldname'=>$fieldname);
    return
      html('div', array('class'=>'ajax', 'id'=>http_build_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'ajax_image', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname, 'value'=>$value ? 1 : 0, 'presentationname'=>$presentationname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'nullallowed'=>$nullallowed, 'defaultvalue'=>$defaultvalue ? $defaultvalue : '', 'readonly'=>$readonly, 'extra'=>$extra ? 1 : 0, 'newname'=>$newname ? $newname : ''))),
        $readonly
        ? list_image($metabasename, $databasename, $field, $value)
        : html($extra ? 'fieldset' : 'div', array('class'=>array($presentationname, $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty')),
            html('ul', array('class'=>'minimal'),
              html('li', array(),
                array(
                  $newname
                  ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio-$fieldname", 'id'=>"radio-new-$fieldname", 'value'=>'new', 'checked'=>'checked')).
                    html('label', array('for'=>"radio-new-$fieldname"),
                      html('span', array('class'=>array('filesource', 'filesourcenew')), _('new')).
                      html('img', array('src'=>http_build_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'new_image', 'newname'=>$newname)), 'alt'=>_('new image'), 'class'=>'listimage')).
                      html('input', array('type'=>'hidden', 'name'=>"field-new-$fieldname", 'value'=>$newname))
                    )
                  : null,

                  !$newname && $value
                  ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio-$fieldname", 'id'=>"radio-original-$fieldname", 'value'=>'original', 'checked'=>$newname ? null : 'checked')).
                    html('label', array('for'=>"radio-original-$fieldname"),
                      html('span', array('class'=>array('filesource', 'filesourceoriginal')), _('original')).
                      list_image($metabasename, $databasename, $field, $value)
                    )
                  : null,

                  $value || $newname
                  ? html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio-$fieldname", 'id'=>"radio-delete-$fieldname", 'value'=>'delete', 'checked'=>$value ? null : 'checked')).
                    html('label', array('for'=>"radio-delete-$fieldname", 'class'=>array('filesource', 'filesourcedelete')), _('delete'))
                  : html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio-$fieldname", 'id'=>"radio-none-$fieldname", 'value'=>'none', 'checked'=>$value ? null : 'checked')).
                    html('label', array('for'=>"radio-none-$fieldname", 'class'=>array('filesource', 'filesourcenone')), _('none')),

                  html('input', array('type'=>'radio', 'class'=>'radio', 'name'=>"radio-$fieldname", 'id'=>"radio-upload-$fieldname", 'value'=>'upload')).
                  html('label', array('for'=>"radio-upload-$fieldname"),
                    ($value || $newname
                    ? html('span', array('class'=>array('filesource', 'filesourcereplace')), _('replace'))
                    : html('span', array('class'=>array('filesource', 'filesourceupload')), _('upload'))
                    ).
                    html('input', array('type'=>'file', 'class'=>array($value || $newname ? 'filereplace' : 'fileupload', $presentationname, $readonly ? 'readonly' : null, $nullallowed || $defaultvalue != '' ? null : 'notempty'), 'id'=>"field-$fieldname", 'name'=>"field-$fieldname", 'readonly'=>$readonly ? 'readonly' : null))
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
    $choice = get_post("radio-$field[fieldname]", null);
    switch ($choice) {
    case 'original':
      return query1field('SELECT `<fieldname>` FROM `<databasename>`.`<tablename>` WHERE `<uniquefieldname>` = "<uniquevalue>"', array('fieldname'=>$field['fieldname'], 'databasename'=>$field['databasename'], 'tablename'=>$field['tablename'], 'uniquefieldname'=>$field['uniquefieldname'], 'uniquevalue'=>$field['uniquevalue']));
    case 'none':
    case 'delete':
      return null;
    case 'new':
      $newname = directory_part(get_post("field-new-$field[fieldname]"));
      $file = file_name(array('upload', $newname));
      $image = file_get_contents($file);
      unlink($file);
      return $image;
    case 'upload':
    case 'replace':
      $warning = check_image();
      if ($warning) {
        add_log('warning', sprintf(_('no image stored because %s'), $warning));
        return null;
      }
      $file = get_parameter('FILES', $_FILES, "field-$field[fieldname]");
      return $file['tmp_name'] ? file_get_contents($file['tmp_name']) : null;
    }
    return null;
  }

  function list_image($metabasename, $databasename, $field, $value) {
    return $value ? html('img', array('src'=>http_build_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'get_image', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$field['tablename'], 'uniquefieldname'=>$field['uniquefieldname'], 'uniquevalue'=>$field['uniquevalue'], 'fieldname'=>$field['fieldname'], 'forcereload'=>time())), 'alt'=>_('uploaded image'), 'class'=>'listimage')) : '';
  }
  
  callable_function('get_image', array());

  function get_image() {
    $metabasename    = get_get('metabasename');
    $databasename    = get_get('databasename');
    $tablename       = get_get('tablename');
    $uniquefieldname = get_get('uniquefieldname');
    $uniquevalue     = get_get('uniquevalue');
    $fieldname       = get_get('fieldname');

    $image = query1field('SELECT `<fieldname>` FROM `<databasename>`.`<tablename>` WHERE `<uniquefieldname>` = "<uniquevalue>"', array('fieldname'=>$fieldname, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
    http_response('Content-type: image/jpeg', $image);
  }

  callable_function('new_image', array());

  function new_image() {
    $newname = directory_part(get_get('newname'));

    $image = file_get_contents(file_name(array('upload', $newname)));
    http_response('Content-type: image/jpeg', $image);
  }

  function check_image() {
    if (count($_FILES) != 1)
      return sprintf(_('not 1 file uploaded but %d'), count($_FILES));
    $names = array_keys($_FILES);
    $file = $_FILES[$names[0]];
    if ($file['error'] != UPLOAD_ERR_OK)
      return file_upload_error_message($file['error']);
    if ($file['type'] != 'image/jpeg')
      return sprintf(_('invalid mime type: %s'), $file['type']);
    if (!preg_match('@^\w+\.\w+$@i', $file['name']))
      return sprintf(_('invalid characters in file name: %s'), $file['name']);
    if (!preg_match('@\.jpe?g$@i', $file['name']))
      return sprintf(_('invalid extension: %s'), $file['name']);
    return null;
  }

  function process_image() {
    $error = check_image();
    if ($error)
      return $error;
    $names = array_keys($_FILES);
    $file = $_FILES[$names[0]];
    $newname = strftime('%Y_%m_%d_%H_%M_%S').'_'.directory_part($file['name']);
    if (!move_uploaded_file($file['tmp_name'], file_name(array('upload', $newname))))
      return sprintf(_('uploaded file cannot be moved: %s'), $file['name']);
    set_get('ajax', preg_replace('@\bnewname=[^&]*@', "newname=$newname", get_get('ajax')));
  }

  callable_function('upload_image', array());

  function upload_image() {
    $warning = process_image();
    if ($warning)
      add_log('warning', $warning);
    call_function(get_get('ajax'));
  }

  function css_image() {
    return
      ".image.edit ul li { vertical-align: top; }\n".
      ".image .filesource { display: inline-block; width: 4em; }\n".
      ".image.list li { display: inline; }\n".
      ".list img.listimage { max-height: 1em; }\n";
  }

  function jquery_enhance_form_image() {
    return
      "find('.image.edit').\n".
      "  css('border', 0).\n".
      "  find('ul li').\n".
      "    css('display', 'inline').\n".
      "  end().\n".
      "  find(':radio, .filesource, .filereplace').\n".
      "    hide().\n".
      "  end().\n".
      "  find('.filesourcedelete').\n".
      "    each(\n".
      "      function() {\n".
      "        $(this).\n".
      "        replaceWith('<span class=\"clickable clickabledelete\">delete</span>');\n".
      "      }\n".
      "    ).\n".
      "  end().\n".
      "  find('.clickabledelete').\n".
      "    click(\n".
      "      function() {\n".
      "        $(this).\n".
      "        closest('.image.edit').\n".
      "          find(':radio[value=\"delete\"]').\n".
      "            attr('checked', true).\n".
      "          end().\n".
      "          find('.listimage').\n".
      "            hide().\n".
      "          end().\n".
      "          find('.clickabledelete').\n".
      "            hide().\n".
      "          end().\n".
      "          find('.filesourcereplace').\n".
      "            parent().\n".
      "              find('.image').\n".
      "                show().\n".
      "              end().\n".
      "            end().\n".
      "          end().\n".
      "        end();\n".
      "      }\n".
      "    ).\n".
      "  end().\n".
      "  find('.clickablereplace, .clickableupload').\n".
      "    click(\n".
      "      function() {\n".
      "        $(this).\n".
      "        closest('.image.edit').\n".
      "          find('input.image').\n".
      "            show().\n".
      "            focus().\n".
      "          end().\n".
      "        end();\n".
      "      }\n".
      "    ).\n".
      "  end().\n".
      "end().\n";
  }

  function jquery_ajaxify_image() {
    return
      "getScripts(['jquery/uploadfile.js'], '.image',\n".
      "  function() {\n".
      "    $(this).\n".
      "    find(':input[type=file]').\n".
      "      change(\n".
      "        function() {\n".
      "          var inputfile = $(this);\n".
      "          $(this).\n".
      "          uploadFile(\n".
      "            '".http_build_url(array('action'=>'call_function', 'presentationname'=>'image', 'functionname'=>'upload_image'))."&ajax=' + escape($(this).closest('.ajax').attr('id')),\n".
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
      "              ajaxify();\n".
      "            }\n".
      "          );\n".
      "        }\n".
      "      ).\n".
      "    end();\n".
      "  }\n".
      ").\n";
  }
?>
