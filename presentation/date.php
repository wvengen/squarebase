<?php
  function probability_date($field) {
    return preg_match('@^date\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_date($field) { return 0; }
  function in_list_date($field) { return 0; }
  function in_edit_date($field) { return 1; }

  function is_sortable_date() { return true; }

  function formfield_date($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return 
      html('input', array('type'=>'text', 'class'=>join_clean(' ', $field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'value'=>date2local($value), 'readonly'=>$readonly ? 'readonly' : null)).
      ($extra ? html('span', array('class'=>'help'), find_datetime_format('%x', 'text')) : '');
  }

  function formattedsql_date($fieldname) {
    return 'DATE_FORMAT('.$fieldname.', \''.find_datetime_format('%x', 'mysql').'\')';
  }

  function formvalue_date($field) {
    $value = parameter('get', "field:$field[fieldname]");
    return $value == "" ? null : local2date($value);
  }

  function list_date($metabasename, $databasename, $field, $value) {
    return date2local($value);
  }

  function date2local($value) {
    return change_datetime_format($value, '%Y-%m-%d', '%x');
  }
  
  function local2date($value) {
    return change_datetime_format($value, '%x', '%Y-%m-%d');
  }
  
  function css_date() {
    return
      ".date.edit { width: 20em; }\n".
      ".date.list { width: 6em; }\n".
      ".ui-datepicker-div { border: 0; }\n".
      ".ui-datepicker-trigger { margin-left: 0.5em; }\n".
      ".ui-datepicker { background-color: #eee; width: 17em; }\n".
      ".ui-datepicker.edit { margin: -1.5em 0 0 22.5em; }\n".
      ".ui-datepicker .ui-datepicker-header { position: relative; padding: 0.2em 0; }\n".
      ".ui-datepicker .ui-datepicker-prev, .ui-datepicker .ui-datepicker-next { position: absolute; top: 0.2em; width: 1.8em; height: 1.8em; }\n".
      ".ui-datepicker .ui-datepicker-prev { left: 0.5em; }\n".
      ".ui-datepicker .ui-datepicker-next { right: 0; }\n".
      ".ui-datepicker .ui-datepicker-prev span, .ui-datepicker .ui-datepicker-next span { display: block; position: absolute; top: 50%; margin-top: -0.8em;  }\n".
      ".ui-datepicker .ui-datepicker-title { margin: 0 2.3em; line-height: 1.8em; text-align: center; }\n".
      ".ui-datepicker .ui-datepicker-title select { float: left; font-size: 1em; margin: 0.1em 0; }\n".
      ".ui-datepicker select.ui-datepicker-month-year { width: 100%; }\n".
      ".ui-datepicker select.ui-datepicker-month, \n".
      ".ui-datepicker select.ui-datepicker-year { width: 50%; }\n".
      ".ui-datepicker .ui-datepicker-title select.ui-datepicker-year { float: right; }\n".
      ".ui-datepicker table { border: 0; width: 100%; font-size: 0.9em; border-collapse: collapse; margin: 0 0 0.4em; }\n".
      ".ui-datepicker th { background-color: #eee; padding: 0.7em 0.3em; text-align: center; font-weight: bold; border: 0;  }\n".
      ".ui-datepicker .ui-state-active { background-color: #ccc; }\n".
      ".ui-datepicker td { border: 0; padding: 0.1em; }\n".
      ".ui-datepicker td span, .ui-datepicker td a { display: block; padding: 0.2em; text-align: right; text-decoration: none; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane { background-image: none; margin: 0.7em 0 0 0; padding: 0 0.2em; border-left: 0; border-right: 0; border-bottom: 0; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane button { float: right; margin: 0.5em 0.2em 0.4em; cursor: pointer; padding: 0.2em 0.6em 0.3em 0.6em; width: auto; overflow: visible; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane button.ui-datepicker-current { float: left; }\n";
  }

  function jquery_enhance_form_date() {
    return
      "find('.date').\n".
      "  datepicker({ changeMonth: true, changeYear: true, duration: '', gotoCurrent: true, prevText: '<', nextText: '>', showOn: 'button', beforeShow: function(input) { $('.ui-datepicker').removeClass('edit list').addClass($(input).hasClass('edit') ? 'edit' : 'list').addClass('box'); }, dateFormat: '".find_datetime_format('%x', 'jquery')."' }).\n".
      "end().\n";
  }
?>
