<?php
  function probability_date($field) {
    return preg_match('@^date\b@', $field['column_type']) ? 0.5 : 0;
  }

  function in_desc_date($field) { return 0; }
  function in_list_date($field) { return 0; }
  function in_edit_date($field) { return 1; }

  function is_sortable_date() { return true; }
  function is_quickaddable_date() { return true; }

  function formfield_date($metabasename, $databasename, $field, $value, $readonly, $extra = true) {
    return 
      html('input', array('type'=>'text', 'class'=>array($field['presentationname'], $extra ? 'edit' : 'list', $readonly ? 'readonly' : null, $field['nullallowed'] || $field['defaultvalue'] != '' ? null : 'notempty'), 'name'=>"field:$field[fieldname]", 'value'=>date2local($value), 'readonly'=>$readonly ? 'readonly' : null)).
      ($extra ? html('span', array('class'=>'help', 'title'=>sprintf(_('Date format: %s'), find_datetime_format('%x', 'text'))), _('?')) : '');
  }

  function formattedsql_date($fieldname) {
    return 'DATE_FORMAT('.$fieldname.', \''.find_datetime_format('%x', 'mysql').'\')';
  }

  function formvalue_date($field) {
    $value = get_post("field:$field[fieldname]", null);
    return $value == '' ? null : local2date($value);
  }

  function list_date($metabasename, $databasename, $field, $value) {
    return htmlentities(date2local($value));
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
      ".ui-datepicker { background-color: #ffc; width: 17em; }\n".
      ".ui-datepicker.edit { margin: -1.5em 0 0 22.5em; }\n".
      ".ui-datepicker .ui-datepicker-trigger { height: 1.5em; }\n".
      ".ui-datepicker .ui-datepicker-header { position: relative; padding: 0.2em 0; }\n".
      ".ui-datepicker .ui-datepicker-prev, .ui-datepicker .ui-datepicker-next { position: absolute; top: 0.25em; width: 1.8em; height: 1.35em; cursor: pointer; }\n".
      ".ui-datepicker .ui-datepicker-prev { left: 0.5em; }\n".
      ".ui-datepicker .ui-datepicker-next { right: 0.5em; }\n".
      ".ui-datepicker .ui-datepicker-prev span, .ui-datepicker .ui-datepicker-next span { display: block; position: absolute; top: 50%; margin: -0.6em 0.5em 0; }\n".
      ".ui-datepicker .ui-datepicker-title { margin: 0 2.3em; line-height: 1.8em; text-align: center; }\n".
      ".ui-datepicker .ui-datepicker-title select { float: left; font-size: 1em; margin: 0.1em 0; background-color: inherit; }\n".
      ".ui-datepicker select.ui-datepicker-month-year { width: 100%; }\n".
      ".ui-datepicker select.ui-datepicker-month, \n".
      ".ui-datepicker select.ui-datepicker-year { width: 50%; }\n".
      ".ui-datepicker .ui-datepicker-title select.ui-datepicker-year { float: right; }\n".
      ".ui-datepicker table { border: 0; width: auto; font-size: 0.9em; border-collapse: separate; margin: 0 0.1em 0.4em; background-color: inherit; }\n".
      ".ui-datepicker th { color: #999; font-size: x-small; background-color: inherit; padding: 0.3em; text-align: center; border: 0; }\n".
      ".ui-datepicker td { border: 0.1em solid #ccc; padding: 0; }\n".
      ".ui-datepicker td.ui-datepicker-week-col { border-color: #ffc; color: #999; font-size: xx-small; padding-top: 1.1em; text-align: center; }\n".
      ".ui-datepicker td.ui-state-disabled { border: 0; }\n".
      ".ui-datepicker td span, .ui-datepicker td a { display: block; padding: 0.2em; text-align: right; text-decoration: none; }\n".
      ".ui-datepicker a.ui-state-default { text-decoration: underline; border: 0.1em solid #ffc; }\n".
      ".ui-datepicker a.ui-state-default:hover { border-color: #00f; }\n".
      ".ui-datepicker a.ui-state-active { border-color: #f00; }\n".
      ".ui-datepicker a.ui-state-highlight { border-color: #000; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane { background-image: none; margin: 0.7em 0 0 0; padding: 0 0.2em; border-left: 0; border-right: 0; border-bottom: 0; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane button { float: right; margin: 0.5em 0.2em 0.4em; cursor: pointer; padding: 0.2em 0.6em 0.3em 0.6em; width: auto; overflow: visible; }\n".
      ".ui-datepicker .ui-datepicker-buttonpane button.ui-datepicker-current { float: left; }\n";
  }

  function jquery_enhance_form_date() {
    return
      "getScripts(['jquery/ui.core.js', 'jquery/ui.datepicker.js'], '.date:not(.readonly)',\n".
      "  function() {\n".
      "    $(this).\n".
      "    datepicker({ changeMonth: true, changeYear: true, duration: '', gotoCurrent: true, prevText: '&larr;', nextText: '&rarr;', showOn: 'button', beforeShow: function(input, inst) { $(inst.dpDiv).css('border', '0.1em solid #999').removeClass('edit list').addClass($(input).hasClass('edit') ? 'edit' : 'list'); }, dateFormat: '".find_datetime_format('%x', 'jquery')."', dayNamesMin: ['"._('sun')."', '"._('mon')."', '"._('tue')."', '"._('wed')."', '"._('thu')."', '"._('fri')."', '"._('sat')."'], monthNamesShort: ['"._('jan')."', '"._('feb')."', '"._('mar')."', '"._('apr')."', '"._('may')."', '"._('jun')."', '"._('jul')."', '"._('aug')."', '"._('sep')."', '"._('oct')."', '"._('nov')."', '"._('dec')."'], showWeek: true, weekHeader: '', yearRange: '1900:2100' });\n".
      "  }\n".
      ").\n";
  }
?>
