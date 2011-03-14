<?php
  /*
    Copyright 2009-2011 Frans Reijnhoudt

    This file is part of Squarebase.

    Squarebase is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Squarebase is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
  */

  include('inflection.php');

  $metabasename = get_get('metabasename', null);
  $databasename = get_get('databasename');
  $language =
    $metabasename
    ? query1field('SELECT languagename FROM `<metabasename>`.languages', array('metabasename'=>$metabasename))
    : get_get('language');

  // pass 1: store query results and find the primary key field name
  $infos = $alltablenames = array();
  $tables = query(
    'SELECT tb.table_name, vw.table_name AS view_name, is_updatable, view_definition '.
    'FROM information_schema.tables tb '.
    'LEFT JOIN information_schema.views vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
    'WHERE tb.table_schema = "<databasename>" '.
    'ORDER BY vw.table_name, tb.table_name', // base tables first, views last, so the primary key of a view can be set to that of the underlying base table for simple views
    array('databasename'=>$databasename)
  );
  while ($table = mysql_fetch_assoc($tables)) {
    $tablename = $table['table_name'];
    $tableinfo = array('table_name'=>$tablename, 'fields'=>array(), 'is_view'=>!is_null($table['view_name']));

    $allprimarykeyfieldnames = array();
    $fields = query(
      'SELECT c.table_schema, c.table_name, c.column_name, column_key, column_type, is_nullable, column_default, referenced_table_name '.
      'FROM information_schema.columns c '.
      'LEFT JOIN information_schema.key_column_usage kcu ON kcu.table_schema = c.table_schema AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name AND referenced_table_schema = c.table_schema '.
      'WHERE c.table_schema = "<databasename>" AND c.table_name = "<tablename>"',
      array('databasename'=>$databasename, 'tablename'=>$tablename)
    );
    $fieldnr = 0;
    while ($field = mysql_fetch_assoc($fields)) {
      $fieldname = $field['column_name'];
      $field['fieldnr'] = $fieldnr++;
      $tableinfo['fields'][] = $field;
      if ($field['column_key'] == 'PRI')
        $allprimarykeyfieldnames[] = $field['column_name'];
    }
    $tableinfo['primarykeyfieldname'] = null;
    if ($tableinfo['is_view']) {
      if ($table['is_updatable'] == 'YES') {
        $tableinfo['possible_view_for_table'] = preg_match1('@ from `.*?`\.`(.*?)`@', $table['view_definition']);
        $tableinfo['primarykeyfieldname'] = $tableinfo['possible_view_for_table'] ? $infos[$tableinfo['possible_view_for_table']]['primarykeyfieldname'] : null;
      }
    }
    else {
      if (count($allprimarykeyfieldnames) == 1)
        $tableinfo['primarykeyfieldname'] = $allprimarykeyfieldnames[0];
    }
    if ($tableinfo['primarykeyfieldname'])
      $alltablenames[$tablename] = $tableinfo['primarykeyfieldname'];
    $infos[$tablename] = $tableinfo;
  }
  ksort($infos);

  // pass 2: find presentation and in_desc, in_list and in_edit (needs $alltablenames and $infos)
  $presentationnames = get_presentationnames();
  foreach ($infos as $tablename=>$table) {
    $max_in_desc = $max_in_list = $max_in_edit = 0;
    foreach ($table['fields'] as $index=>$field) {
      $fieldname = $field['column_name'];

      $augmentedfield =
        array_merge(
          $field,
          array(
            'alltablenames'=>$alltablenames,
            'primarykeyfieldname'=>$table['primarykeyfieldname'],
            'numfields'=>count($table['fields'])
          )
        );
      $bestpresentationname = null;
      $bestprobability = 0;
      foreach ($presentationnames as $onepresentationname) {
        $probability = $probabilities[$onepresentationname] = call_user_func("probability_$onepresentationname", $augmentedfield);
        if ($probability > $bestprobability) {
          $bestpresentationname = $onepresentationname;
          $bestprobability = $probability;
        }
      }

      $infos[$tablename]['fields'][$index]['presentationprobabilities'] = $probabilities;
      $infos[$tablename]['fields'][$index]['presentationname'] = $bestpresentationname;

      $infos[$tablename]['fields'][$index]['in_desc'] = call_user_func("in_desc_$bestpresentationname", $augmentedfield);
      $infos[$tablename]['fields'][$index]['in_list'] = call_user_func("in_list_$bestpresentationname", $augmentedfield);
      $infos[$tablename]['fields'][$index]['in_edit'] = call_user_func("in_edit_$bestpresentationname", $augmentedfield);

      $max_in_desc = max($max_in_desc, $infos[$tablename]['fields'][$index]['in_desc']);
      $max_in_list = max($max_in_list, $infos[$tablename]['fields'][$index]['in_list']);
      $max_in_edit = max($max_in_edit, $infos[$tablename]['fields'][$index]['in_edit']);

      if ($metabasename)
        $infos[$tablename]['fields'][$index]['original'] = query01('SELECT tbl.singular, tbl.plural, tbl.intablelist, title, presentationname, nullallowed, indesc, inlist, inedit, ftbl.tablename AS foreigntablename FROM `<metabasename>`.tables AS tbl LEFT JOIN `<metabasename>`.fields AS fld ON fld.tableid = tbl.tableid LEFT JOIN `<metabasename>`.presentations pst ON pst.presentationid = fld.presentationid LEFT JOIN `<metabasename>`.tables AS ftbl ON fld.foreigntableid = ftbl.tableid WHERE tbl.tablename = "<tablename>" AND fieldname = "<fieldname>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname));
      $infos[$tablename]['fields'][$index]['linkedtable'] = isset($infos[$tablename]['fields'][$index]['original']) ? $infos[$tablename]['fields'][$index]['original']['foreigntablename'] : @call_user_func("linkedtable_$bestpresentationname", $tablename, $fieldname);
    }
  }

  // pass 3: produce output for tables and fields (needs $max_in_****)
  $alternative_views = array();
  if ($metabasename) {
    $views = query('SELECT * FROM `<metabasename>`.views', array('metabasename'=>$metabasename));
    while ($view = mysql_fetch_assoc($views))
      $alternative_views[$view['viewname']] = 1;
  }

  $rowsfields = array();
  foreach ($infos as $tablename=>$table) {
    $rowsfields[] =
      html('tr', array(),
        column_header(_('table'), _('The name of the table')).
        column_header(_('include'), _('Whether this table will be included in the metabase.')).
        column_header(_('singular').' / '._('plural'), _('The singular / plural form of the table name.')).
        column_header(_('top'), _('Whether this table will be visible on the toplevel of "show database".')).
        column_header(_('desc'), _('Whether this field will be included in the short description of a record.')).
        column_header(_('list'), _('Whether this field will be included in the list of records.')).
        column_header(_('edit'), _('Whether this field will be included in the form to edit a record.')).
        column_header(_('title').' + '._('field'), _('The readable title').' + '._('The original field name.')).
        column_header(_('presentation').' + '._('type'), _('The presentation that will be used to show and edit this field').' + '._('The original type.'), true)
      );

    if (!$table['primarykeyfieldname']) {
      $rowsfields[] =
        html('tr', array('class'=>array(($field['fieldnr'] + 1) % 2 ? 'rowodd' : 'roweven', 'list', "table-$tablename")),
          html('td', array('class'=>'top'),
            html('div', array('class'=>'tablename'),
              $tablename
            )
          ).
          html('td', array('class'=>array('top', 'center')),
            html('input', array('type'=>'checkbox', 'class'=>array('checkboxedit', 'readonly', 'include'), 'readonly'=>'readonly', 'name'=>"$tablename:include"))
          ).
          html('td', array('class'=>'top', 'colspan'=>2),
            ''
          ).
          html('td', array('class'=>'reason', 'colspan'=>5),
            _('no single valued primary key')
          )
        );
    }
    else {
      foreach ($table['fields'] as $field) {
        $fieldname = $field['column_name'];

        $inlistforquickadd = $field['column_name'] != $table['primarykeyfieldname'] && $field['is_nullable'] == 'NO' && !$field['column_default'];
        if (isset($field['original'])) {
          $plural           = $field['original']['plural'];
          $singular         = $field['original']['singular'];
          $title            = $field['original']['title'];
          $intablelist      = $field['original']['intablelist'];
          $nullallowed      = $field['original']['nullallowed'];
          $presentationname = $field['original']['presentationname'];
          $indesc           = $field['original']['indesc'];
          $inlist           = $field['original']['inlist'];
          $inedit           = $field['original']['inedit'];
        }
        else {
          $plural           = $tablename;
          $singular         = singularize_noun($plural);
          $title            = preg_replace_all(
                                array(
                                  '@_@'=>' ',
                                  '@(?<=[a-z])([A-Z]+)@e'=>'strtolower(" $1")',
                                  "@^(.*?)\b( *(?:$singular|$plural) *)\b(.*?)$@ie"=>'"$1" && "$3" ? "$1 $3" : ("$1" || "$3" ? "$1$3" : "$0")',
                                  '@(?<=[\w ])'._('id').'$@i'=>'',
                                  '@ {2,}@'=>' ',
                                  '@(^ +| +$)@'=>''
                                ),
                                $fieldname
                              );
          $intablelist      = true;
          $nullallowed      = $field['is_nullable'] == 'YES';
          $presentationname = $field['presentationname'];
          $indesc           = $field['in_desc'] == $max_in_desc;
          $inlist           = $field['in_list'] == $max_in_list || $inlistforquickadd;
          $inedit           = $field['in_edit'] == $max_in_edit;
        }

        $tableoptions = array(html('option', array('value'=>'', 'selected'=>!$field['linkedtable'] ? 'selected' : null), ''));
        $alternativeoptions = array(html('option', array('value'=>'', 'selected'=>!$metabasename || isset($alternative_views[$tablename]) ? 'selected' : null), ''));
        foreach ($alltablenames as $onetablename=>$oneprimarykeyfieldname) {
          $tableoptions[] = html('option', array('value'=>$onetablename, 'selected'=>$onetablename == $field['linkedtable'] ? 'selected' : null), $onetablename);
          $alternativeoptions[] = html('option', array('value'=>$onetablename, 'selected'=>isset($alternative_views[$tablename]) && $onetablename == $alternative_views[$tablename] ? 'selected' : null), $onetablename);
        }

        $mostlikelyoption = null;
        $moreorlesslikelyoptions = $unlikelyoptions = array();
        foreach ($field['presentationprobabilities'] as $onepresentationname=>$probability) {
          $option = html('option', array('value'=>$onepresentationname, 'selected'=>$onepresentationname == $presentationname ? 'selected' : null), $onepresentationname);
          if ($onepresentationname == $presentationname)
            $mostlikelyoption = $option;
          elseif ($probability)
            $moreorlesslikelyoptions["$probability"] = $option;
          else
            $unlikelyoptions[$onepresentationname] = $option;
        }
        krsort($moreorlesslikelyoptions);
        ksort($unlikelyoptions);
        $presentationnameoptions =
          ($mostlikelyoption               ? html('optgroup', array('label'=>_('most likely')), $mostlikelyoption) : '').
          (count($moreorlesslikelyoptions) ? html('optgroup', array('label'=>_('more or less likely')), join(array_values($moreorlesslikelyoptions))) : '').
          (count($unlikelyoptions)         ? html('optgroup', array('label'=>_('unlikely')), join(array_values($unlikelyoptions))) : '');

        $rowsfields[] =
          html('tr', array('class'=>array(($field['fieldnr'] + 1) % 2 ? 'rowodd' : 'roweven', 'list', "table-$tablename")),
            ($field['fieldnr'] == 0
            ? html('td', array('class'=>'top', 'rowspan'=>count($table['fields'])),
                html('div', array('class'=>'tablename'),
                  $tablename.
                  html('input', array('type'=>'hidden', 'name'=>"$tablename:primary", 'value'=>$table['primarykeyfieldname']))
                ).
                ($table['is_view']
                ? html('div', array('class'=>'alternative'),
                    (isset($table['possible_view_for_table'])
                    ? html('input', array('type'=>'hidden', 'name'=>"$tablename:possibleviewfortable", 'value'=>$table['possible_view_for_table'])).
                      html('input', array('type'=>'checkbox', 'class'=>array('checkboxedit', 'viewfortable'), 'name'=>"$tablename:viewfortable", 'id'=>"$tablename:viewfortable", 'checked'=>!$metabasename || isset($alternative_views[$tablename]) ? 'checked' : null)).
                      html('label', array('for'=>"$tablename:viewfortable"), sprintf(_('alternative for %s'), $table['possible_view_for_table']))
                    : html('input', array('type'=>'hidden', 'name'=>"$tablename:viewfortable", 'value'=>'on')).
                      html('label', array('for'=>"$tablename:possibleviewfortable"), _('alternative for')).
                      html('select', array('name'=>"$tablename:possibleviewfortable", 'id'=>"$tablename:possibleviewfortable"),
                        join($alternativeoptions)
                      )
                    )
                  )
                : ''
                )
              ).
              html('td', array('class'=>array('top', 'center'), 'rowspan'=>count($table['fields'])),
                html('input', array('type'=>'checkbox', 'class'=>array('checkboxedit', 'include'), 'name'=>"$tablename:include", 'checked'=>!$metabasename || isset($field['original']) ? 'checked' : null))
              ).
              html('td', array('class'=>array('top', 'pluralsingular'), 'rowspan'=>count($table['fields'])),
                html('div', array(),
                  array(
                    html('input', array('type'=>'text', 'name'=>"$tablename:singular", 'value'=>$singular)),
                    html('input', array('type'=>'text', 'name'=>"$tablename:plural", 'value'=>$plural))
                  )
                )
              ).
              html('td', array('class'=>array('top', 'center', 'intablelist'), 'rowspan'=>count($table['fields'])),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:intablelist", 'checked'=>$intablelist ? 'checked' : null))
              )
            : ''
            ).
            html('td', array('class'=>array('row', 'center')),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:indesc", 'checked'=>$indesc ? 'checked' : null))
            ).
            html('td', array('class'=>array('row', 'center', $inlistforquickadd ? 'inlistforquickadd' : null)),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inlist", 'checked'=>$inlist ? 'checked' : null))
            ).
            html('td', array('class'=>array('row', 'center')),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inedit", 'checked'=>$inedit ? 'checked' : null))
            ).
            html('td', array('class'=>'row', 'title'=>$fieldname),
              html('input', array('type'=>'text', 'class'=>'title', 'name'=>"$tablename:$fieldname:title", 'value'=>$title))
            ).
            html('td', array('class'=>array('row', 'filler'), 'title'=>$field['column_type'].($nullallowed ? '' : ' '.'not null').($fieldname == $table['primarykeyfieldname'] ? ' '.'auto_increment' : '')),
              html('select', array('name'=>"$tablename:$fieldname:presentationname", 'class'=>'presentationname'), $presentationnameoptions).
              ($fieldname != $table['primarykeyfieldname'] && preg_match('@^(tiny|small|medium|big)?int(eger)?\b@', $field['column_type'])
              ? html('select', array('name'=>"$tablename:$fieldname:foreigntablename", 'class'=>'foreigntablename'),
                  join($tableoptions)
                )
              : ''
              ).
              html('input', array('type'=>'hidden', 'name'=>"$tablename:$fieldname:nullallowed", 'value'=>$nullallowed ? 'on' : ''))
            )
          );
      }
    }
  }

  if ($metabasename)
    $metabase_input = html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$metabasename, 'readonly'=>'readonly', 'class'=>'readonly'));
  else {
    $databases = array_diff(databases_with_grant('CREATE'), array($databasename));
    if (count($databases) == 1) {
      $database = array_shift($databases);
      if ($database == '*')
        $metabase_input = html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$databasename.'_metabase', 'class'=>'notempty'));
      else
        $metabase_input = html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$database, 'readonly'=>'readonly', 'class'=>'readonly'));
    }
    else {
      $metabase_options = array();
      foreach ($databases as $database)
        $metabase_options[html('option', array(), $database)] =
          (preg_match("@^${database}_metabase$@i", $databasename)
          ? 200
          : (preg_match("@^$database|$database$@i", $databasename)
            ? 100 + strlen($database)
            : strlen($database) - levenshtein($database, $databasename)
            )
          );
      arsort($metabase_options);
      $metabase_input = html('select', array('name'=>'metabasename'), join(array_keys($metabase_options)));
    }
    // prevent reading the language for a non-existent metabase in the next action
    $metabase_input .= html('input', array('type'=>'hidden', 'name'=>'language', 'value'=>get_cookie('language')));
  }

  page('form metabase for database', breadcrumbs($metabasename, $databasename),
    form(
      html('table', array('class'=>'box'),
        inputrow(_('metabase'), $metabase_input, _('The name of the metabase that will be build for this database.')).
        inputrow(_('database'), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly')), _('The name of this database.')).
        inputrow(_('language'), html('input', array('type'=>'text', 'name'=>'language', 'value'=>$language, 'readonly'=>'readonly', 'class'=>'readonly')), _('The language for displaying dates, numbers, etc in this database.'))
      ).
      html('table', array('class'=>array('box', 'formmetabasefordatabase')),
        join($rowsfields)
      ).
      html('p', array(),
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'extract_structure_from_database_to_metabase', 'class'=>'submit'))
      ),
      array('method'=>'post')
    )
  );
?>
