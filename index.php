<?php
  /*
    Copyright 2009,2010 Frans Reijnhoudt

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

  include('functions.php');

  ini_set('session.use_only_cookies', true);
  session_set_cookie_params(7 * 24 * 60 * 60);
  session_save_path('session');
  session_start();

  umask(0177); // => maximum = rw-.---.---

  set_preference('scripty', 1);
  set_preference('ajaxy', 1);
  set_preference('logsy', 0);

  error_reporting(php_sapi_name() == 'cli' || parameter('cookie', 'logsy') ? E_ALL : 0);

  if (parameter('cookie', 'logsy')) {
    if (parameter('get') && parameter('post'))
      error(_('both get and post parameters'));
    $parametersource = parameter('post') ? 'post' : 'get';
    add_log($parametersource, $parametersource.': '.html('div', array('class'=>'arrayshow'), array_show(parameter($parametersource))));
//  add_log('cookie', 'cookie: '.html('div', array('class'=>'arrayshow'), array_show(parameter('cookie'))));
  }

  $languagename = !parameter('get', 'language') && parameter('get', 'metabasename') ? query1field('meta', 'SELECT languagename FROM `<metabasename>`.languages', array('metabasename'=>parameter('get', 'metabasename'))) : null;

  set_best_locale(
    preg_replace(
      array('@\.[a-z][a-z0-9\-]*@', '@_([a-z]+)@ie'       ),
      array(''                    , '"-".strtolower("$1")'),
      join_non_null(',',
        preg_match('/^([^\.]+)/', parameter('get', 'language'),    $matches) ? $matches[1].';q=4.0' : null,
        preg_match('/^([^\.]+)/', $languagename,                   $matches) ? $matches[1].';q=3.0' : null,
        preg_match('/^([^\.]+)/', parameter('cookie', 'language'), $matches) ? $matches[1].';q=2.0' : null,
        parameter('server', 'HTTP_ACCEPT_LANGUAGE'),
        'en;q=0.0'
      )
    ),
    join_non_null(',',
      preg_match('/\.(.*?)$/', parameter('get', 'language'),    $matches) ? $matches[1].';q=4.0' : null,
      preg_match('/\.(.*?)$/', $languagename,                   $matches) ? $matches[1].';q=3.0' : null,
      preg_match('/\.(.*?)$/', parameter('cookie', 'language'), $matches) ? $matches[1].';q=2.0' : null,
      parameter('server', 'HTTP_ACCEPT_CHARSET'),
      '*;q=0.0'
    )
  );

  bindtextdomain('messages', './locale');
  textdomain('messages');

  $action = first_non_null(parameter('get_or_post', 'action'), 'login');

  /********************************************************************************************/

  if ($action == 'style')
    augment_file('style', 'css', 'text/css');

  /********************************************************************************************/

  if ($action == 'script')
    augment_file('script', 'js', 'text/javascript');

  /********************************************************************************************/

  if ($action == 'login') {
    $usernameandhost = parameter('get', 'usernameandhost');
    $next = parameter('get', 'next');

    if (parameter('session', 'username') && parameter('session', 'host') && $usernameandhost == parameter('get', 'username').'@'.parameter('get', 'host'))
      internal_redirect(first_non_null(http_parse_query($next), array('action'=>'index')));

    if (is_null($usernameandhost) && parameter('session', 'username'))
      internal_redirect(array('action'=>'index'));

    $password = parameter('get', 'password');
    if (!$usernameandhost) {
      $radios = array();
      $lastusernamesandhosts = parameter('cookie', 'lastusernamesandhosts');
      if ($lastusernamesandhosts) {
        foreach (explode(',', $lastusernamesandhosts) as $thisusernameandhost)
          $radios[] =
            html('input', array('type'=>'radio', 'class'=>join_non_null(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'id'=>"lastusernameandhost:$thisusernameandhost", 'value'=>$thisusernameandhost, 'checked'=>$radios ? null : 'checked')).
            html('label', array('for'=>"lastusernameandhost:$thisusernameandhost"), $thisusernameandhost).
            internal_reference(array('action'=>'forget_username_and_host', 'usernameandhost'=>$thisusernameandhost), 'forget', array('class'=>'forget'));
      }
      if (!$radios)
        $usernameandhost = 'root@localhost';
    }

    page($action, null,
      form(
        ($next ? html('input', array('type'=>'hidden', 'name'=>'next', 'value'=>$next)) : '').
        html('table', array('class'=>'box'),
          inputrow(_('user').'@'._('host'),
            isset($radios)
            ? html('ul', array('class'=>join_non_null(' ', 'minimal', 'lastusernamesandhosts')),
                html('li', array(),
                  array_merge(
                    $radios,
                    array(
                      html('input', array('type'=>'radio', 'class'=>join_non_null(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'value'=>'')).
                      html('input', array('type'=>'text', 'class'=>join_non_null(' ', 'afterradio', 'skipfirstfocus'), 'id'=>'usernameandhost', 'name'=>'usernameandhost', 'value'=>$usernameandhost))
                    )
                  )
                )
              )
            : html('input', array('type'=>'text', 'class'=>'skipfirstfocus', 'id'=>'usernameandhost', 'name'=>'usernameandhost', 'value'=>$usernameandhost)),
            _('The username@host from the underlying MySql database.')
          ).
          inputrow(_('password'), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password', 'value'=>$password)), _('The password for username@host from the underlying MySql database.')).
          inputrow(_('language'), select_locale(), _('The default language for displaying translations, dates, numbers, etc.')).
          inputrow(null,  html('input', array('type'=>'submit', 'name'=>'action',   'value'=>'connect', 'class'=>'mainsubmit')))
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'forget_username_and_host') {
    $usernameandhost = parameter('get', 'usernameandhost');

    forget($usernameandhost);
    internal_redirect(array('action'=>'login'));
  }

  /********************************************************************************************/

  if ($action == 'connect') {
    $usernameandhost = parameter('get', 'lastusernameandhost');
    if (!$usernameandhost)
      $usernameandhost = parameter('get', 'usernameandhost');
    if (preg_match('@^([^\@]+)\@([^\@]+)$@', $usernameandhost, $match)) {
      $username = $match[1];
      $host     = $match[2];
    }
    elseif ($usernameandhost) {
      $username = $usernameandhost;
      $host     = 'localhost';
    }
    else
      internal_redirect(array('action'=>'login', 'error'=>_('no username@host given')));
    $password = parameter('get', 'password');
    $language = parameter('get', 'language');

    login($username, $host, $password, $language);

    $next = parameter('get', 'next');
    internal_redirect(first_non_null(http_parse_query($next), array('action'=>'index')));
  }

  /********************************************************************************************/

  if ($action == 'logout') {
    logout();
  }

  /********************************************************************************************/

  if ($action == 'index') {
    $metabases = all_databases();
    $rows = array(html('th', array('class'=>'filler'), _('database')).html('th', array(), _('metabase')).html('th', array(), ''));
    $links = array();
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['schema_name'];
      $databasenames = databasenames($metabasename);
      foreach ($databasenames as $databasename) {
        $link = array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename);
        $links[] = $link;
        $rows[] =
          html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
            html('td', array('class'=>'filler'),
              internal_reference($link, $databasename)
            ).
            html('td', array(),
              has_grant('DROP', $metabasename)
              ? array(
                  internal_reference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename),
                  internal_reference(array('action'=>'drop_database', 'databasename'=>$metabasename), 'drop', array('class'=>'drop'))
                )
              : array('', '')
            )
          );
      }
    }

    $can_create = has_grant('CREATE', '?');

    if (count($links) == 0 && $can_create)
      internal_redirect(array('action'=>'new_metabase_from_database'));

    if (count($links) == 1 && !$can_create)
      internal_redirect($links[0]);

    page($action, null,
      html('table', array('class'=>'box'), join($rows)).
      ($can_create ? internal_reference(array('action'=>'new_metabase_from_database'), _('new metabase from database')) : '')
    );
  }

  /********************************************************************************************/

  if ($action == 'new_metabase_from_database') {
    $rows = array(html('th', array('class'=>'filler'), _('database')).html('th', array(), _('tables')).html('th', array(), ''));
    $databases = all_databases();
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['schema_name'];
      $dblist = array();
      $dbs = databasenames($databasename);
      if ($dbs) {
        foreach ($dbs as $db)
          $dblist[] = internal_reference(array('action'=>'form_metabase_for_database', 'databasename'=>$db, 'metabasename'=>$databasename), $db);
        $dblist[] = internal_reference(array('action'=>'form_database_for_metabase', 'metabasename'=>$databasename), _('(add database)'));
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $dblist));
      }
      else {
        $tables = query('top', 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "<databasename>"', array('databasename'=>$databasename));
        if ($tables) {
          $tablelist = array();
          while ($table = mysql_fetch_assoc($tables)) {
            $tablelist[] = $table['table_name'];
          }
          if (count($tablelist) > 5) {
            $notshown = join(' ', array_slice($tablelist, 4));
            array_splice($tablelist, 4, count($tablelist), html('span', array('title'=>$notshown), '&hellip;'));
          }
          $contents = html('ul', array('class'=>'compact'), html('li', array(), $tablelist));
        }
      }
      $rows[] =
        html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array('class'=>'filler'), internal_reference(array('action'=>'language_for_database', 'databasename'=>$databasename), $databasename)).
          html('td', array(), $contents).
          html('td', array(), has_grant('DROP', $databasename) ? internal_reference(array('action'=>'drop_database', 'databasename'=>$databasename), 'drop', array('class'=>'drop')) : '')
        );
    }
    page($action, null,
      form(
        html('table', array('class'=>'box'), join($rows))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'drop_database') {
    $databasename = parameter('get', 'databasename');
    page($action, breadcrumbs(null, $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>parameter('server', 'HTTP_REFERER'))).
        html('p', array(), sprintf(_('Drop database %s?'), html('strong', array(), $databasename))).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop_database_really', 'class'=>'mainsubmit')).
        internal_reference(http_parse_query(parameter('server', 'HTTP_REFERER')), 'cancel', array('class'=>'cancel')),
        'post'
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'drop_database_really') {
    $databasename = parameter('post', 'databasename');
    query('root', 'DROP DATABASE `<databasename>`', array('databasename'=>$databasename));
    back();
  }

  /********************************************************************************************/

  if ($action == 'language_for_database') {
    $databasename = parameter('get', 'databasename');

    $tables = query('top', 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "<databasename>"', array('databasename'=>$databasename));
    if ($tables) {
      $tablelist = array();
      while ($table = mysql_fetch_assoc($tables)) {
        $tablelist[] = $table['table_name'];
      }
      $tables = join(', ', $tablelist);
    }

    page($action, breadcrumbs(null, $databasename),
      form(
        html('table', array('class'=>'box'),
          inputrow(_('database'), html('input', array('type'=>'text', 'id'=>'databasename', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly')), _('The name of the database to build a metabase for.')).
          inputrow(_('tables'), html('input', array('type'=>'text', 'id'=>'tables', 'name'=>'tables', 'value'=>$tables, 'title'=>$tables, 'readonly'=>'readonly', 'class'=>'readonly')), _('The names of the tables in this database.')).
          inputrow(_('language'), select_locale(), _('The language for displaying dates, numbers, etc in this database.'))
        ).
        html('p', array(),
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'form_metabase_for_database', 'class'=>'mainsubmit'))
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'form_metabase_for_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $language =
      $metabasename
      ? query1field('meta', 'SELECT languagename FROM `<metabasename>`.languages', array('metabasename'=>parameter('get', 'metabasename')))
      : parameter('get', 'language');

    // pass 1: store query results and find the primary key field name
    $infos = $alltablenames = $tableswithoutsinglevaluedprimarykey = array();
    $tables = query('top',
      'SELECT tb.table_name, vw.table_name AS view_name, is_updatable, view_definition '.
      'FROM INFORMATION_SCHEMA.TABLES tb '.
      'LEFT JOIN INFORMATION_SCHEMA.VIEWS vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
      'WHERE tb.table_schema = "<databasename>" '.
      'ORDER BY vw.table_name, tb.table_name', // base tables first, views last, so the primary key of a view can be set to that of the underlying base table for simple views
      array('databasename'=>$databasename)
    );
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      $tableinfo = array('table_name'=>$tablename, 'fields'=>array(), 'is_view'=>!is_null($table['view_name']));

      $allprimarykeyfieldnames = array();
      $fields = query('top',
        'SELECT c.table_schema, c.table_name, c.column_name, column_key, column_type, is_nullable, column_default, referenced_table_name '.
        'FROM INFORMATION_SCHEMA.COLUMNS c '.
        'LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON kcu.table_schema = c.table_schema AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name AND referenced_table_schema = c.table_schema '.
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
        else
          $tableswithoutsinglevaluedprimarykey[] = $tablename;
      }
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
          $infos[$tablename]['fields'][$index]['original'] = query01('meta', 'SELECT tbl.singular, tbl.plural, tbl.intablelist, title, presentationname, nullallowed, indesc, inlist, inedit, ftbl.tablename AS foreigntablename FROM `<metabasename>`.tables AS tbl LEFT JOIN `<metabasename>`.fields AS fld ON fld.tableid = tbl.tableid LEFT JOIN `<metabasename>`.presentations pst ON pst.presentationid = fld.presentationid LEFT JOIN `<metabasename>`.tables AS ftbl ON fld.foreigntableid = ftbl.tableid WHERE tbl.tablename = "<tablename>" AND fieldname = "<fieldname>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname));
        $infos[$tablename]['fields'][$index]['linkedtable'] = isset($infos[$tablename]['fields'][$index]['original']) ? $infos[$tablename]['fields'][$index]['original']['foreigntablename'] : @call_user_func("linkedtable_$bestpresentationname", $tablename, $fieldname);
      }
    }

    // pass 3: produce output for tables and fields (needs $max_in_****)
    $alternative_views = array();
    if ($metabasename) {
      $views = query('meta', 'SELECT * FROM `<metabasename>`.views', array('metabasename'=>$metabasename));
      while ($view = mysql_fetch_assoc($views))
        $alternative_views[$view['viewname']] = 1;
    }

    $rowsfields = array();
    foreach ($infos as $tablename=>$table) {
      $rowsfields[] =
        html('tr', array(),
          column_header(_('table'), 'The name of the table').
          column_header(_('singular').' / '._('plural'), 'The singular form and the plural form of the table name.').
          column_header(_('top'), 'Whether this table will be visible on the toplevel of "show database".').
          column_header(_('desc'), 'Whether this field will be included in the short description of a record.').
          column_header(_('list'), 'Whether this field will be included in the list of records.').
          column_header(_('edit'), 'Whether this field will be included in the form to edit a record.').
          column_header(_('title').' + '._('field'), 'The readable title / original field name.').
          column_header(_('presentation').' + '._('type'), 'The presentation that will be used to show and edit this field / the original type.', true)
        );

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
          $title            = preg_replace(
                                array('@_@', '@(?<=[a-z])([A-Z]+)@e', "@^(.*?)\b( *(?:$singular|$plural) *)\b(.*?)$@ie"       , '@(?<=[\w ])'._('id').'$@i', '@ {2,}@', '@(^ +| +$)@'),
                                array(' '  , 'strtolower(" $1")'    , '"$1" && "$3" ? "$1 $3" : ("$1" || "$3" ? "$1$3" : "$0")', ''                        , ' '      , ''           ),
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
          html('tr', array('class'=>join_non_null(' ', ($field['fieldnr'] + 1) % 2 ? 'rowodd' : 'roweven', 'list', "table-$tablename")),
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
                      html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:viewfortable", 'id'=>"$tablename:viewfortable", 'checked'=>!$metabasename || isset($alternative_views[$tablename]) ? 'checked' : null)).
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
              html('td', array('class'=>join_non_null(' ', 'top', 'pluralsingular'), 'rowspan'=>count($table['fields'])),
                html('div', array(),
                  array(
                    html('input', array('type'=>'text', 'name'=>"$tablename:singular", 'value'=>$singular)),
                    html('input', array('type'=>'text', 'name'=>"$tablename:plural", 'value'=>$plural))
                  )
                )
              ).
              html('td', array('class'=>join_non_null(' ', 'top', 'center'), 'rowspan'=>count($table['fields'])),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:intablelist", 'checked'=>$intablelist ? 'checked' : null))
              )
            : ''
            ).
            html('td', array('class'=>'center'),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:indesc", 'checked'=>$indesc ? 'checked' : null))
            ).
            html('td', array('class'=>join_non_null(' ', 'center', $inlistforquickadd ? 'inlistforquickadd' : null)),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inlist", 'checked'=>$inlist ? 'checked' : null))
            ).
            html('td', array('class'=>'center'),
              html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inedit", 'checked'=>$inedit ? 'checked' : null))
            ).
            html('td', array('title'=>$fieldname),
              html('input', array('type'=>'text', 'class'=>'title', 'name'=>"$tablename:$fieldname:title", 'value'=>$title))
            ).
            html('td', array('class'=>'filler', 'title'=>join_non_null(' ', $field['column_type'], $nullallowed ? null : 'not null', $fieldname == $table['primarykeyfieldname'] ? 'auto_increment' : null)),
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
          $metabase_options[] = html('option', array(), $database);
        $metabase_input = html('select', array('name'=>'metabasename'), join($metabase_options));
      }
      // prevent reading the language for a non-existent metabase in the next action
      $metabase_input .= html('input', array('type'=>'hidden', 'name'=>'language', 'value'=>parameter('cookie', 'language')));
    }

    page($action, breadcrumbs($metabasename, $databasename),
      $tableswithoutsinglevaluedprimarykey
      ? html('p', array(),
          html('span', array('class'=>'error'), sprintf(_('no single valued primary key for table(s) %s'), join(', ', $tableswithoutsinglevaluedprimarykey)))
        )
      : form(
          html('table', array('class'=>'box'),
            inputrow(_('metabase'), $metabase_input, _('The name of the metabase that will be build for this database.')).
            inputrow(_('database'), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly')), _('The name of this database.')).
            inputrow(_('language'), html('input', array('type'=>'text', 'name'=>'language', 'value'=>$language, 'readonly'=>'readonly', 'class'=>'readonly')), _('The language for displaying dates, numbers, etc in this database.'))
          ).
          html('table', array('class'=>'box'),
            join($rowsfields)
          ).
          html('p', array(),
            html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'extract_structure_from_database_to_metabase', 'class'=>'mainsubmit'))
          ),
          'post'
        )
    );
  }

  /********************************************************************************************/

  if ($action == 'extract_structure_from_database_to_metabase') {
    $databasename = parameter('post', 'databasename');
    $metabasename = parameter('post', 'metabasename');
    if (!$metabasename)
      error(_('no name given for the metabase'));

    query('meta', 'DROP DATABASE IF EXISTS `<metabasename>`', array('metabasename'=>$metabasename));

    query('meta', 'CREATE DATABASE IF NOT EXISTS `<metabasename>`', array('metabasename'=>$metabasename));

    query('meta',
      'CREATE TABLE `<metabasename>`.languages ('.
        'languageid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'languagename     VARCHAR(100) NOT NULL,'.
        'UNIQUE KEY (languagename)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    query('meta',
      'CREATE TABLE `<metabasename>`.databases ('.
        'databaseid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'databasename     VARCHAR(100) NOT NULL,'.
        'UNIQUE KEY (databasename)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    query('meta',
      'CREATE TABLE `<metabasename>`.tables ('.
        'tableid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'tablename        VARCHAR(100) NOT NULL,'.
        'singular         VARCHAR(100) NOT NULL,'.
        'plural           VARCHAR(100) NOT NULL,'.
        'uniquefieldid    INT UNSIGNED NOT NULL REFERENCES `fields` (fieldid),'.
        'intablelist      BOOLEAN      NOT NULL,'.
        'UNIQUE KEY (tablename),'.
        'INDEX (uniquefieldid)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    query('meta',
      'CREATE TABLE `<metabasename>`.fields ('.
        'fieldid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'tableid          INT UNSIGNED NOT NULL REFERENCES `tables` (tableid),'.
        'fieldname        VARCHAR(100) NOT NULL,'.
        'title            VARCHAR(100) NOT NULL,'.
        'type             VARCHAR(100) NOT NULL,'.
        'nullallowed      BOOLEAN      NOT NULL,'.
        'defaultvalue     VARCHAR(100)         ,'.
        'presentationid   INT UNSIGNED NOT NULL REFERENCES `presentations` (presentationid),'.
        'indesc           BOOLEAN      NOT NULL,'.
        'inlist           BOOLEAN      NOT NULL,'.
        'inedit           BOOLEAN      NOT NULL,'.
        'foreigntableid   INT UNSIGNED          REFERENCES `tables` (tableid),'.
        'UNIQUE KEY (tableid, fieldname),'.
        'INDEX (foreigntableid),'.
        'INDEX (presentationid)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    query('meta',
      'CREATE TABLE `<metabasename>`.views ('.
        'viewid           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'viewname         VARCHAR(100) NOT NULL,'.
        'tableid          INT UNSIGNED NOT NULL REFERENCES `tables` (tableid),'.
        'UNIQUE KEY (viewname),'.
        'INDEX (tableid)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    query('meta',
      'CREATE TABLE `<metabasename>`.presentations ('.
        'presentationid   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
        'presentationname VARCHAR(100) NOT NULL,'.
        'UNIQUE KEY (presentationname)'.
      ')',
      array('metabasename'=>$metabasename)
    );

    insert_or_update($metabasename, 'languages', array('languagename'=>parameter('post', 'language')));

    insert_or_update($metabasename, 'databases', array('databasename'=>$databasename));

    $presentationnames = get_presentationnames();
    $presentationids = array();
    foreach ($presentationnames as $presentationname)
      $presentationids[$presentationname] = insert_or_update($metabasename, 'presentations', array('presentationname'=>$presentationname));

    $tables = query('top',
      'SELECT tb.table_name, vw.table_name AS view_name, is_updatable, view_definition '.
      'FROM INFORMATION_SCHEMA.TABLES tb '.
      'LEFT JOIN INFORMATION_SCHEMA.VIEWS vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
      'WHERE tb.table_schema = "<databasename>" '.
      'ORDER BY vw.table_name, tb.table_name', // base tables first, views last, so the table id of a view can be set to that of the underlying base table for alternative views
      array('databasename'=>$databasename)
    );
    $tableids = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      if (parameter('post', "$tablename:viewfortable") && parameter('post', "$tablename:possibleviewfortable"))
        insert_or_update($metabasename, 'views', array('viewname'=>$tablename, 'tableid'=>$tableids[parameter('post', "$tablename:possibleviewfortable")]));
      else
        $tableids[$tablename] = insert_or_update($metabasename, 'tables', array('tablename'=>$tablename, 'singular'=>parameter('post', "$tablename:singular"), 'plural'=>parameter('post', "$tablename:plural"), 'intablelist'=>parameter('post', "$tablename:intablelist") == 'on'));
    }

    $errors = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table['table_name'];
      if (!parameter('post', "$tablename:viewfortable")) {
        $tableid = $tableids[$tablename];

        $indescs = $inlists = $inedits = 0;
        $fields = query('top',
          'SELECT c.table_schema, c.table_name, c.column_name, column_key, column_type, is_nullable, column_default, referenced_table_name '.
          'FROM INFORMATION_SCHEMA.COLUMNS c '.
          'LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON kcu.table_schema = c.table_schema AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name AND referenced_table_schema = c.table_schema '.
          'WHERE c.table_schema = "<databasename>" AND c.table_name = "<tablename>"',
          array('databasename'=>$databasename, 'tablename'=>$tablename)
        );
        while ($field = mysql_fetch_assoc($fields)) {
          $fieldname = $field['column_name'];

          $foreigntablename = parameter('post', "$tablename:$fieldname:foreigntablename");

          $indesc = parameter('post', "$tablename:$fieldname:indesc") ? true : false;
          $inlist = parameter('post', "$tablename:$fieldname:inlist") ? true : false;
          $inedit = parameter('post', "$tablename:$fieldname:inedit") ? true : false;

          $fieldid = insert_or_update($metabasename, 'fields', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'title'=>parameter('post', "$tablename:$fieldname:title"), 'type'=>$field['column_type'], 'presentationid'=>$presentationids[parameter('post', "$tablename:$fieldname:presentationname")], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'nullallowed'=>$field['is_nullable'] == 'YES' ? true : false, 'defaultvalue'=>$field['column_default'], 'indesc'=>$indesc, 'inlist'=>$inlist, 'inedit'=>$inedit));

          $indescs += $indesc;
          $inlists += $inlist;
          $inedits += $inedit;

          if (parameter('post', "$tablename:primary") == $fieldname)
            query('meta', 'UPDATE `<metabasename>`.tables SET uniquefieldid = <fieldid> WHERE tableid = <tableid>', array('metabasename'=>$metabasename, 'fieldid'=>$fieldid, 'tableid'=>$tableid));
        }
        if (!$indescs)
          $errors[] = sprintf(_('no fields to desc for %s'), $tablename);
        if (!$inlists)
          $errors[] = sprintf(_('no fields to list for %s'), $tablename);
        if (!$inedits)
          $errors[] = sprintf(_('no fields to edit for %s'), $tablename);
      }
    }

    if ($errors)
      error(join(', ', $errors));

    internal_redirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'form_database_for_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $rows = array(html('th', array(), _('database')).html('th', array('class'=>'filler'), ''));
    $databasenames = databasenames($metabasename);

    $databases = all_databases();
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['schema_name'];
      $rows[] =
        html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            array(
              internal_reference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $databasename),
              internal_reference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), in_array($databasename, $databasenames) ? 'update' : 'add')
            )
          )
        );
    }
    $rows[] =
      html('tr', array('class'=>count($rows) % 2 ? 'rowodd' : 'roweven'),
        html('td', array(),
          array(
            html('input', array('type'=>'text', 'name'=>'databasename')),
            html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
            html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'attach_database_to_metabase', 'class'=>'mainsubmit'))
          )
        )
      );
    page($action, breadcrumbs($metabasename),
      form(
        html('table', array('class'=>'box'), html('tr', array(), $rows))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'attach_database_to_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');

    query('meta', 'INSERT IGNORE INTO `<metabasename>`.databases SET databasename = "<databasename>"', array('metabasename'=>$metabasename, 'databasename'=>$databasename));

    internal_redirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'show_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tables = query('meta', 'SELECT * FROM `<metabasename>`.tables LEFT JOIN `<metabasename>`.fields ON tables.uniquefieldid = fields.fieldid WHERE intablelist = true ORDER BY tablename', array('metabasename'=>$metabasename));
    $rows = array(html('th', array('class'=>'filler'), _('table')));
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['tablename'];
      if (has_grant('SELECT', $databasename, table_or_view($metabasename, $databasename, $tablename), '?'))
        $rows[] =
          html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
            html('td', array(),
              internal_reference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$table['singular'], 'uniquefieldname'=>$table['fieldname']), $table['plural'])
            )
          );
    }
    page($action, breadcrumbs($metabasename, $databasename),
      html('div', array('class'=>'ajax'),
        html('table', array('class'=>'box'), join($rows))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'show_table') {
    $metabasename      = parameter('get', 'metabasename');
    $databasename      = parameter('get', 'databasename');
    $tablename         = parameter('get', 'tablename');
    $tablenamesingular = parameter('get', 'tablenamesingular');
    $uniquefieldname   = parameter('get', 'uniquefieldname');
    $limit             = parameter('get', 'limit');
    $offset            = first_non_null(parameter('get', 'offset'), 0);
    $orderfieldname    = parameter('get', 'orderfieldname');
    $orderasc          = first_non_null(parameter('get', 'orderasc'), 'on') == 'on';

    page($action, breadcrumbs($metabasename, $databasename, $tablename, $uniquefieldname),
      list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, null, $orderfieldname, $orderasc, null, null, null, true)
    );
  }

  /********************************************************************************************/

  if ($action == 'new_record' || $action == 'edit_record' || $action == 'show_record') {
    $metabasename            = parameter('get', 'metabasename');
    $databasename            = parameter('get', 'databasename');
    $tablename               = parameter('get', 'tablename');
    $tablenamesingular       = parameter('get', 'tablenamesingular');
    $uniquefieldname         = parameter('get', 'uniquefieldname');
    $uniquevalue             = parameter('get', 'uniquevalue');
    $referencedfromfieldname = parameter('get', 'referencedfromfieldname');
    $back                    = parameter('get', 'back');

    page($action, breadcrumbs($metabasename, $databasename, $tablename, $uniquefieldname, $uniquevalue),
      edit_record($action == 'new_record' ? 'INSERT' : ($action == 'edit_record' ? 'UPDATE' : 'SELECT'), $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue, $referencedfromfieldname, $back ? $back : parameter('server', 'HTTP_REFERER'))
    );
  }

  /********************************************************************************************/

  if ($action == 'delete_record') {
    $metabasename    = parameter('post', 'metabasename');
    $databasename    = parameter('post', 'databasename');
    $tablename       = parameter('post', 'tablename');
    $uniquefieldname = parameter('post', 'uniquefieldname');
    $uniquevalue     = parameter('post', 'uniquevalue');

    query('data', 'DELETE FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

    back();
  }

  /********************************************************************************************/

  if ($action == 'update_record' || $action == 'add_record' || $action == 'add_record_and_edit') {
    $metabasename            = parameter('post', 'metabasename');
    $databasename            = parameter('post', 'databasename');
    $tablename               = parameter('post', 'tablename');
    $tablenamesingular       = parameter('post', 'tablenamesingular');
    $uniquefieldname         = parameter('post', 'uniquefieldname');
    $uniquevalue             = parameter('post', 'uniquevalue');
    $referencedfromfieldname = parameter('post', 'referencedfromfieldname');
    $back                    = parameter('post', 'back');

    $viewname = table_or_view($metabasename, $databasename, $tablename);

    get_presentationnames();

    $fieldnamesandvalues = array();
    $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname, $action == 'update_record' ? 'UPDATE' : 'INSERT');
    while ($field = mysql_fetch_assoc($fields)) {
      if ($field['inedit']) {
        $value = call_user_func("formvalue_$field[presentationname]", array_merge($field, array('databasename'=>$databasename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)));
        if ($action == 'update_record' || !is_null($value))
          $fieldnamesandvalues[$field['fieldname']] = $value;
      }
    }

    $uniquevalue = insert_or_update($databasename, $viewname, $fieldnamesandvalues, $uniquefieldname, $uniquevalue);

    $ajax = parameter('post', 'ajax');
    if ($action == 'add_record' || $action == 'add_record_and_edit') {
      if ($ajax)
        parameter('post', 'ajax', preg_replace('@\bvalue=\d+\b@', '', parameter('post', 'ajax'))."&value=$uniquevalue");
      elseif ($referencedfromfieldname)
        parameter('post', 'back', preg_replace('@\bback=@', "field:$referencedfromfieldname=$uniquevalue&back=", parameter('post', 'back')));
    }
    if ($action == 'add_record_and_edit') {
      if ($ajax)
        parameter('post', 'ajax', "$ajax&uniquevalue=$uniquevalue");
      else
        internal_redirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'back'=>$back));
    }

    back();
  }

  /********************************************************************************************/

  if ($action == 'call_function') {
    call_function(parameter('server', 'REQUEST_URI'));
  }

  /********************************************************************************************/

  if ($action == 'explain_query') {
    $query = parameter('get', 'query');

    $explanations = query('top', 'EXPLAIN EXTENDED '.$query);
    query('top', 'SHOW WARNINGS');

    $headings = array();
    for ($i = 0; $i < mysql_num_fields($explanations); $i++) {
      $meta = mysql_fetch_field($explanations, $i);
      $headings[] = $meta->name;
    }

    $rows = array(html('tr', array(), html('th', array(), $headings)));
    while ($explanation = mysql_fetch_assoc($explanations)) {
      $cells = array();
      foreach (array_keys($explanation) as $key) {
        $cells[] = is_null($explanation[$key]) ? '-' : $explanation[$key];
      }
      $rows[] = html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')), html('td', array(), $cells));
    }

    page($action, null,
      html('p', array(), $query).
      html('table', array('class'=>'box'),
        join($rows)
      ).
      html('p', array(), external_reference('http://dev.mysql.com/doc/refman/5.0/en/using-explain.html', 'MySQL 5.0 Reference Manual :: 7.2.1 Optimizing Queries with EXPLAIN'))
    );
  }

  /********************************************************************************************/

  error('unknown action '.$action);
?>
