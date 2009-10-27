<?php
  include('functions.php');

  $action = first_non_null(parameter('get', 'action'), 'login');

  if ($action == 'style')
    augment_file('style.css', 'text/css');
  if ($action == 'script')
    augment_file('script.js', 'text/javascript');

  ini_set('session.use_only_cookies', true);
  session_set_cookie_params(7 * 24 * 60 * 60);
  session_save_path('session');
  session_start();

  $_SESSION['scripty'] = !is_null(parameter('get', 'scripty')) ? parameter('get', 'scripty') == 'on' : ($_SESSION['timesconnected'] ? $_SESSION['scripty'] : true);
  $_SESSION['ajaxy']   = !is_null(parameter('get', 'ajaxy'  )) ? parameter('get', 'ajaxy'  ) == 'on' : ($_SESSION['timesconnected'] ? $_SESSION['ajaxy'  ] : true);
  $_SESSION['logsy']   = !is_null(parameter('get', 'logsy'  )) ? parameter('get', 'logsy'  ) == 'on' : ($_SESSION['timesconnected'] ? $_SESSION['logsy'  ] : false);

  addtolist('logs', 'get', 'get: '.html('div', array('class'=>'arrayshow'), array_show(parameter('get'))));
  addtolist('logs', 'cookie', 'cookie: '.html('div', array('class'=>'arrayshow'), array_show($_COOKIE)));

  $languagename = !parameter('get', 'language') && parameter('get', 'metabasename') ? query1field('meta', 'SELECT languagename FROM `<metabasename>`.languages', array('metabasename'=>parameter('get', 'metabasename'))) : null;

  set_best_locale(
    preg_replace(
      array('@\.[a-z][a-z0-9\-]*@', '@_([a-z]+)@ie'       ),
      array(''                    , '"-".strtolower("$1")'),
      join_clean(',',
        preg_match('/^([^\.]+)/', parameter('get', 'language'),     $matches) ? $matches[1].';q=4.0' : null,
        preg_match('/^([^\.]+)/', $languagename,                    $matches) ? $matches[1].';q=3.0' : null,
        preg_match('/^([^\.]+)/', parameter('session', 'language'), $matches) ? $matches[1].';q=2.0' : null,
        parameter('server', 'HTTP_ACCEPT_LANGUAGE'),
        'en;q=0.0'
      )
    ),
    join_clean(',',
      preg_match('/\.(.*?)$/', parameter('get', 'language'),     $matches) ? $matches[1].';q=4.0' : null,
      preg_match('/\.(.*?)$/', $languagename,                    $matches) ? $matches[1].';q=3.0' : null,
      preg_match('/\.(.*?)$/', parameter('session', 'language'), $matches) ? $matches[1].';q=2.0' : null,
      parameter('server', 'HTTP_ACCEPT_CHARSET'),
      '*;q=0.0'
    )
  );

  bindtextdomain('messages', './locale');
  textdomain('messages');

  /********************************************************************************************/

  if ($action == 'login') {
    if ($_SESSION['timesconnected'])
      internalredirect(array('action'=>'index'));

    $usernameandhost = parameter('get', 'usernameandhost');
    $password = parameter('get', 'password');
    if (!$usernameandhost) {
      $radios = array();
      $lastusernamesandhosts = $_COOKIE['lastusernamesandhosts'];
      if ($lastusernamesandhosts) {
        foreach (explode(',', $lastusernamesandhosts) as $thisusernameandhost)
          $radios[] = html('input', array('type'=>'radio', 'class'=>join_clean(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'id'=>"lastusernameandhost:$thisusernameandhost", 'value'=>$thisusernameandhost, 'checked'=>$radios ? null : 'checked'), html('label', array('for'=>"lastusernameandhost:$thisusernameandhost"), $thisusernameandhost).internalreference(array('action'=>'forget_username_and_host', 'usernameandhost'=>$thisusernameandhost), 'forget', array('class'=>'forget')));
      }
      if (!$radios)
        $usernameandhost = 'root@localhost';
    }

    $usernameandhost_input = html('input', array('type'=>'text', 'class'=>'skipfirstfocus', 'id'=>'usernameandhost', 'name'=>'usernameandhost', 'value'=>$usernameandhost));

    if ($radios) {
      $usernameandhost_input = 
        html('ul', array('class'=>'minimal'),
          html('li', array(),
            array_merge(
              $radios,
              array(
                html('input', array('type'=>'radio', 'class'=>join_clean(' ', 'radio', 'skipfirstfocus'), 'name'=>'lastusernameandhost', 'value'=>''),
                  $usernameandhost_input
                )
              )
            )
          )
        );
    }

    page($action, null,
      form(
        html('table', array('class'=>'box'),
          html('tr', array(),
            array(
              html('td', array(), html('label', array('for'=>'usernameandhost'), _('user').'@'._('host'))).html('td', array('class'=>'filler'), $usernameandhost_input),
              html('td', array(), html('label', array('for'=>'password'), _('password'))).html('td', array(), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password', 'value'=>$password))),
              html('td', array(), html('label', array('for'=>'language'), _('language'))).html('td', array(), select_locale()),
              html('td', array(), '').                                                    html('td', array(), html('input', array('type'=>'submit', 'name'=>'action',   'value'=>'connect', 'class'=>'mainsubmit')))
            )
          )
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'forget_username_and_host') {
    $usernameandhost = parameter('get', 'usernameandhost');

    forget($usernameandhost);
    internalredirect(array('action'=>'login'));
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
      internalredirect(array('action'=>'login', 'error'=>_('no username@host given')));
    $password = parameter('get', 'password');
    $language = parameter('get', 'language');

    login($username, $host, $password, $language);
    internalredirect(array('action'=>'index'));
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
          html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
            html('td', array(),
              internalreference($link, $databasename)
            ).
            html('td', array(),
              has_grant('DROP', $metabasename)
              ? array(
                  internalreference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename),
                  internalreference(array('action'=>'drop_database', 'databasename'=>$metabasename), 'drop', array('class'=>'drop'))
                )
              : array('', '')
            )
          );
      }
    }

    $can_create = has_grant('CREATE', '?');

    if (count($links) == 0 && $can_create)
      internalredirect(array('action'=>'new_metabase_from_database'));

    if (count($links) == 1 && !$can_create)
      internalredirect($links[0]);

    page($action, null,
      html('table', array('class'=>'box'), join($rows)).
      ($can_create ? internalreference(array('action'=>'new_metabase_from_database'), _('new metabase from database')) : '')
    );
  }

  /********************************************************************************************/

  if ($action == 'new_metabase_from_database') {
    $rows = array(html('th', array(), _('database')).html('th', array(), _('tables')).html('th', array('class'=>'filler'), ''));
    $databases = all_databases();
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['schema_name'];
      $dblist = array();
      $dbs = databasenames($databasename);
      if ($dbs) {
        foreach ($dbs as $db)
          $dblist[] = internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$db, 'metabasename'=>$databasename), $db);
        $dblist[] = internalreference(array('action'=>'form_database_for_metabase', 'metabasename'=>$databasename), _('(add database)'));
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $dblist));
      }
      else {
        $tables = query('top', 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "<databasename>"', array('databasename'=>$databasename));
        if ($tables) {
          $tablelist = array();
          while ($table = mysql_fetch_assoc($tables)) {
            $tablelist[] = $table['table_name'];
          }
          $fulllist = null;
          if (count($tablelist) > 5) {
            $fulllist = join(' ', array_slice($tablelist, 4));
            array_splice($tablelist, 4);
          }
          $contents = html('ul', array('class'=>'compact'), html('li', array(), $tablelist).($fulllist ? html('li', array('title'=>$fulllist), '&hellip;') : ''));
        }
      }
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            array(
              internalreference(array('action'=>'language_for_database', 'databasename'=>$databasename), $databasename),
              $contents,
              has_grant('DROP', $databasename) ? internalreference(array('action'=>'drop_database', 'databasename'=>$databasename), 'drop', array('class'=>'drop')) : ''
            )
          )
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
    page($action, path(null, $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>parameter('server', 'HTTP_REFERER'))).
        html('p', array(), sprintf(_('Drop database %s?'), html('strong', array(), $databasename))).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop_database_really', 'class'=>'mainsubmit')).
        internalreference(parameter('server', 'HTTP_REFERER'), 'cancel', array('class'=>'cancel'))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'drop_database_really') {
    $databasename = parameter('get', 'databasename');
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

    page($action, path(null, $databasename),
      form(
        html('table', array('class'=>'box'),
          html('tr', array(),
            array(
              html('td', array(), html('label', array('for'=>'databasename'), _('databasename'))).html('td', array('class'=>'filler'), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly'))),
              html('td', array(), html('label', array(), _('tables'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'tables', 'value'=>$tables, 'title'=>$tables, 'readonly'=>'readonly', 'class'=>'readonly'))),
              html('td', array(), html('label', array('for'=>'language'), _('language'))).html('td', array(), select_locale())
            )
          )
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
      'SELECT tb.table_name '.
      'FROM INFORMATION_SCHEMA.TABLES tb '.
      'LEFT JOIN INFORMATION_SCHEMA.VIEWS vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
      'WHERE tb.table_schema = "<databasename>" AND vw.view_definition IS NULL',
      array('databasename'=>$databasename)
    );
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      $alltablenames[] = $tablename;
      $tableinfo = array('table_name'=>$tablename, 'fields'=>array());

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
      if (count($allprimarykeyfieldnames) == 1)
        $tableinfo['primarykeyfieldname'] = $allprimarykeyfieldnames[0];
      else
        $tableswithoutsinglevaluedprimarykey[] = $tablename;
      $tableinfo['views'] = array();
      $infos[$tablename] = $tableinfo;
    }

    $views = query('top',
      'SELECT table_name, view_definition, is_updatable '.
      'FROM INFORMATION_SCHEMA.VIEWS '.
      'WHERE table_schema = "<databasename>"',
      array('databasename'=>$databasename)
    );
    while ($view = mysql_fetch_assoc($views)) {
      $viewname = $view['table_name'];
      if ($view['is_updatable'] == 'YES') {
        $fromname = preg_match1('@ from `.*?`\.`(.*?)`@', $view['view_definition']);
        $infos[$fromname]['views'][] = $viewname;
      }
    }

    // pass 2: find presentation and in_desc, in_list and in_edit (needs $alltablenames and $infos)
    $presentationnames = get_presentationnames();
    $referencesin = $referencesout = array();
    foreach ($infos as $tablename=>&$table) {
      $max_in_desc = $max_in_list = $max_in_edit = 0;
      foreach ($table['fields'] as &$field) {
        $fieldname = $field['column_name'];

        $augmentedfield =
          array_merge(
            $field,
            array(
              'alltablenames'=>$alltablenames,
              'primarykeyfieldname'=>$primarykeyfieldname,
              'fieldnr'=>$field['fieldnr'],
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

        $field['presentationprobabilities'] = $probabilities;
        $field['presentationname'] = $bestpresentationname;

        $field['in_desc'] = call_user_func("in_desc_$bestpresentationname", $augmentedfield);
        $field['in_list'] = call_user_func("in_list_$bestpresentationname", $augmentedfield);
        $field['in_edit'] = call_user_func("in_edit_$bestpresentationname", $augmentedfield);

        $max_in_desc = max($max_in_desc, $field['in_desc']);
        $max_in_list = max($max_in_list, $field['in_list']);
        $max_in_edit = max($max_in_edit, $field['in_edit']);

        if ($metabasename)
          $field['original'] = query01('meta', 'SELECT tbl.singular, tbl.plural, tbl.intablelist, title, presentationname, nullallowed, indesc, inlist, inedit, ftbl.tablename AS foreigntablename FROM `<metabasename>`.tables AS tbl LEFT JOIN `<metabasename>`.fields AS fld ON fld.tableid = tbl.tableid LEFT JOIN `<metabasename>`.presentations pst ON pst.presentationid = fld.presentationid LEFT JOIN `<metabasename>`.tables AS ftbl ON fld.foreigntableid = ftbl.tableid WHERE tbl.tablename = "<tablename>" AND fieldname = "<fieldname>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname));
        $field['linkedtable'] = $field['original'] ? $field['original']['foreigntablename'] : @call_user_func("linkedtable_$bestpresentationname", $tablename, $fieldname);
        if ($field['linkedtable']) {
          $referencesout[$tablename]++;
          $referencesin[$field['linkedtable']]++;
        }
      }
    }

    // pass 3: produce output for tables and fields (needs $max_in_**** and $referencesin/-out)
    $rowsfields = array();
    foreach ($infos as $tablename=>&$table) {
      $rowsfields[] =
        html('tr', array(),
          html('th', array(),
            array(
              _('table'), _('top'), _('field'), _('title'), _('type'), _('null'), _('presentation'), _('key'), _('desc'), _('list'), _('edit')
            )
          ).
          html('th', array('class'=>'filler'), '')
        );

      foreach ($table['fields'] as &$field) {
        $fieldname = $field['column_name'];

        $inlistforquickadd = $field['column_key'] != 'PRI' && $field['is_nullable'] == 'NO' && !$field['column_default'];
        if ($field['original']) {
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
                                array('@(?<=[a-z])([A-Z]+)@e', '@'._('id').'$@i', "@^(.*?)\b( *(?:$singular|$plural) *)\b(.*?)$@ie"       , '@(?<=[\w ])id$@i', '@ {2,}@', '@(^ +| +$)@'),
                                array('strtolower(" $1")'    , ' '._('id')      ,'"$1" && "$3" ? "$1 $3" : ("$1" || "$3" ? "$1$3" : "$0")', ''                , ' '      , ''           ),
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
        foreach ($alltablenames as $onetablename)
          $tableoptions[] = html('option', array('value'=>$onetablename, 'selected'=>$onetablename == $field['linkedtable'] ? 'selected' : null), $onetablename);

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
          html('tr', array('class'=>join_clean(' ', ($field['fieldnr'] + 1) % 2 ? 'rowodd' : 'roweven', 'list')),
            ($field['fieldnr'] == 0
            ? html('td', array('class'=>'top', 'rowspan'=>count($table['fields'])),
                $tablename.
                html('ol', array('class'=>'pluralsingular'),
                  html('li', array(),
                    array(
                      html('label', array('for'=>"$tablename:singular"), '1').html('input', array('type'=>'text', 'name'=>"$tablename:singular", 'id'=>"$tablename:singular", 'value'=>$singular)),
                      html('label', array('for'=>"$tablename:plural"), '2').html('input', array('type'=>'text', 'name'=>"$tablename:plural", 'id'=>"$tablename:plural", 'value'=>$plural))
                    )
                  )
                ).
                ($table['views']
                ? _('alternative views').':'.
                  html('ul', array('class'=>'views'),
                    html('li', array(),
                      $table['views']
                    )
                  )
                : ''
                )
              ).
              html('td', array('class'=>join_clean(' ', 'top', 'center'), 'rowspan'=>count($table['fields'])),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:intablelist", 'checked'=>$intablelist ? 'checked' : null)).
                html('div', array('class'=>'countreferences'),
                  html('div', array(), sprintf(_('%d in'), $referencesin[$tablename])).
                  html('div', array(), sprintf(_('%d out'), $referencesout[$tablename]))
                )
              )
            : ''
            ).
            html('td', array(), $fieldname).
            html('td', array(), html('input', array('type'=>'text', 'class'=>'title', 'name'=>"$tablename:$fieldname:title", 'value'=>$title))).
            html('td', array(), $field['column_type']).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'name'=>"$tablename:$fieldname:_nullallowed", 'readonly'=>'readonly', 'checked'=>$nullallowed ? 'checked' : null)).html('input', array('type'=>'hidden', 'name'=>"$tablename:$fieldname:nullallowed", 'value'=>$nullallowed ? 'on' : ''))).
            html('td', array(), html('select', array('name'=>"$tablename:$fieldname:presentationname", 'class'=>'presentationname'), $presentationnameoptions)).
            html('td', array(),
              ($fieldname == $table['primarykeyfieldname']
              ? _('primary').html('input', array('type'=>'hidden', 'name'=>"$tablename:primary", 'value'=>$fieldname))
              : (preg_match('@^(tiny|small|medium|big)?int(eger)?\b@', $field['column_type'])
                ? html('select', array('name'=>"$tablename:$fieldname:foreigntablename", 'class'=>'foreigntablename'),
                    join($tableoptions)
                  )
                : ''
                )
              )
            ).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:indesc", 'checked'=>$indesc ? 'checked' : null))).
            html('td', array('class'=>join_clean(' ', 'center', $inlistforquickadd ? 'inlistforquickadd' : null)), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inlist", 'checked'=>$inlist ? 'checked' : null))).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inedit", 'checked'=>$inedit ? 'checked' : null))).
            html('td', array(), '')
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
      $metabase_input .= html('input', array('type'=>'hidden', 'name'=>'language', 'value'=>parameter('session', 'language')));
    }

    page($action, path(null, $databasename),
      $tableswithoutsinglevaluedprimarykey
      ? html('p', array(),
          html('span', array('class'=>'error'), sprintf(_('no single valued primary key for table(s) %s'), join(', ', $tableswithoutsinglevaluedprimarykey)))
        )
      : form(
          html('table', array('class'=>'box'),
            html('tr', array(),
              array(
                html('td', array(), html('label', array('for'=>'metabasename'), _('metabasename'))).html('td', array('class'=>'filler'), $metabase_input),
                html('td', array(), html('label', array('for'=>'databasename'), _('databasename'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly'))),
                html('td', array(), html('label', array('for'=>'language'), _('language'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'language', 'value'=>$language, 'readonly'=>'readonly', 'class'=>'readonly')))
              )
            )
          ).
          html('table', array('class'=>'box'),
            join($rowsfields)
          ).
          html('p', array(),
            html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'extract_structure_from_database_to_metabase', 'class'=>'mainsubmit'))
          )
        )
    );
  }

  /********************************************************************************************/

  if ($action == 'extract_structure_from_database_to_metabase') {
    $databasename = parameter('get', 'databasename');
    $metabasename = parameter('get', 'metabasename');
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

    insertorupdate($metabasename, 'languages', array('languagename'=>parameter('get', 'language')));

    insertorupdate($metabasename, 'databases', array('databasename'=>$databasename));

    $presentationnames = get_presentationnames();
    $presentationids = array();
    foreach ($presentationnames as $presentationname)
      $presentationids[$presentationname] = insertorupdate($metabasename, 'presentations', array('presentationname'=>$presentationname));

    $tables = query('top',
      'SELECT tb.table_name '.
      'FROM INFORMATION_SCHEMA.TABLES tb '.
      'LEFT JOIN INFORMATION_SCHEMA.VIEWS vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
      'WHERE tb.table_schema = "<databasename>" AND vw.view_definition IS NULL',
      array('databasename'=>$databasename)
    );
    $tableids = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      $tableids[$tablename] = insertorupdate($metabasename, 'tables', array('tablename'=>$tablename, 'singular'=>parameter('get', "$tablename:singular"), 'plural'=>parameter('get', "$tablename:plural"), 'intablelist'=>parameter('get', "$tablename:intablelist") == 'on'));
    }

    $errors = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table['table_name'];
      $tableid = $tableids[$tablename];

      $descs = $sorts = $lists = $edits = 0;
      $fields = query('top',
        'SELECT c.table_schema, c.table_name, c.column_name, column_key, column_type, is_nullable, column_default, referenced_table_name '.
        'FROM INFORMATION_SCHEMA.COLUMNS c '.
        'LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON kcu.table_schema = c.table_schema AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name AND referenced_table_schema = c.table_schema '.
        'WHERE c.table_schema = "<databasename>" AND c.table_name = "<tablename>"',
        array('databasename'=>$databasename, 'tablename'=>$tablename)
      );
      while ($field = mysql_fetch_assoc($fields)) {
        $fieldname = $field['column_name'];

        $foreigntablename = parameter('get', "$tablename:$fieldname:foreigntablename");

        $indesc = parameter('get', "$tablename:$fieldname:indesc") ? true : false;
        $inlist = parameter('get', "$tablename:$fieldname:inlist") ? true : false;
        $inedit = parameter('get', "$tablename:$fieldname:inedit") ? true : false;

        $fieldid = insertorupdate($metabasename, 'fields', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'title'=>parameter('get', "$tablename:$fieldname:title"), 'type'=>$field['column_type'], 'presentationid'=>$presentationids[parameter('get', "$tablename:$fieldname:presentationname")], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'nullallowed'=>$field['is_nullable'] == 'YES' ? true : false, 'defaultvalue'=>$field['column_default'], 'indesc'=>$indesc, 'inlist'=>$inlist, 'inedit'=>$inedit));

        $indescs += $indesc;
        $inlists += $inlist;
        $inedits += $inedit;

        if (parameter('get', "$tablename:primary") == $fieldname)
          query('meta', 'UPDATE `<metabasename>`.tables SET uniquefieldid = <fieldid> WHERE tableid = <tableid>', array('metabasename'=>$metabasename, 'fieldid'=>$fieldid, 'tableid'=>$tableid));
      }
      if (!$indescs)
        $errors[] = sprintf(_('no fields to desc for %s'), $tablename);
      if (!$inlists)
        $errors[] = sprintf(_('no fields to list for %s'), $tablename);
      if (!$inedits)
        $errors[] = sprintf(_('no fields to edit for %s'), $tablename);
    }

    $views = query('top',
      'SELECT table_name, view_definition, is_updatable '.
      'FROM INFORMATION_SCHEMA.VIEWS '.
      'WHERE table_schema = "<databasename>"',
      array('databasename'=>$databasename)
    );
    while ($view = mysql_fetch_assoc($views)) {
      $viewname = $view['table_name'];
      if ($view['is_updatable'] == 'YES') {
        $fromname = preg_match1('@ from `.*?`\.`(.*?)`@', $view['view_definition']);
        insertorupdate($metabasename, 'views', array('viewname'=>$viewname, 'tableid'=>$tableids[$fromname]));
      }
    }

    if ($errors)
      error(join(', ', $errors));

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'form_database_for_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $rows = array(html('th', array(), _('database')).html('th', array('class'=>'filler'), 'database'));
    $databasenames = databasenames($metabasename);

    $databases = all_databases();
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['schema_name'];
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            array(
              internalreference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $databasename),
              internalreference(array('action'=>'attach_database_to_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), in_array($databasename, $databasenames) ? 'update' : 'add')
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
    page($action, path($metabasename),
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

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
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
          html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
            html('td', array(),
              internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$table['singular'], 'uniquefieldname'=>$table['fieldname']), $table['plural'])
            )
          );
    }
    page($action, path($metabasename, $databasename),
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

    page($action, path($metabasename, $databasename, $tablename, $uniquefieldname),
      list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, null, $orderfieldname, $orderasc, null, null, null, true)
    );
  }

  /********************************************************************************************/

  if ($action == 'new_record' || $action == 'edit_record' || $action == 'show_record') {
    $metabasename      = parameter('get', 'metabasename');
    $databasename      = parameter('get', 'databasename');
    $tablename         = parameter('get', 'tablename');
    $tablenamesingular = parameter('get', 'tablenamesingular');
    $uniquefieldname   = parameter('get', 'uniquefieldname');
    $uniquevalue       = parameter('get', 'uniquevalue');
    $back              = parameter('get', 'back');

    page($action, path($metabasename, $databasename, $tablename, $tableid, $uniquefieldname, $uniquevalue),
      edit_record($action == 'new_record' ? 'INSERT' : ($action == 'edit_record' ? 'UPDATE' : 'SELECT'), $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue, $back ? $back : parameter('server', 'HTTP_REFERER'))
    );
  }

  /********************************************************************************************/

  if ($action == 'delete_record') {
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $uniquevalue     = parameter('get', 'uniquevalue');

    query('data', 'DELETE FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

    back();
  }

  /********************************************************************************************/

  if ($action == 'update_record' || $action == 'add_record' || $action == 'add_record_and_edit') {
    $metabasename      = parameter('get', 'metabasename');
    $databasename      = parameter('get', 'databasename');
    $tablename         = parameter('get', 'tablename');
    $tablenamesingular = parameter('get', 'tablenamesingular');
    $uniquefieldname   = parameter('get', 'uniquefieldname');
    $uniquevalue       = parameter('get', 'uniquevalue');
    $back              = parameter('get', 'back');

    $viewname = table_or_view($metabasename, $databasename, $tablename);

    get_presentationnames();

    $fieldnamesandvalues = array();
    $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname, $action == 'update_record' ? 'UPDATE' : 'INSERT');
    while ($field = mysql_fetch_assoc($fields)) {
      if ($field['inedit'])
        $fieldnamesandvalues[$field['fieldname']] = call_user_func("formvalue_$field[presentationname]", $field);
    }

    $uniquevalue = insertorupdate($databasename, $viewname, $fieldnamesandvalues, $uniquefieldname, $uniquevalue);

    if ($action == 'add_record_and_edit') {
      $ajax = parameter('get', 'ajax');
      if ($ajax)
        $_POST['ajax'] = "$ajax&uniquevalue=$uniquevalue";
      else
        internalredirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'back'=>$back));
    }

    back();
  }

  /********************************************************************************************/

  if ($action == 'get_image') {
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $uniquevalue     = parameter('get', 'uniquevalue');
    $fieldname       = parameter('get', 'fieldname');

    $image = query1field('data', 'SELECT <fieldname> FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('fieldname'=>$fieldname, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

//  As of PHP 5.3, Fileinfo will be shipped with the main distribution and enabled by default.
//  $finfo = new finfo(FILEINFO_MIME);
//  $mimedata = finfo_file($finfo, $image);
//  print_r($mimedata);

    header('Content-type: image/jpeg');
    print $image;
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
      $rows[] = html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')), html('td', array(), $cells));
    }

    page($action, null,
      html('p', array(), $query).
      html('table', array('class'=>'box'), 
        join($rows)
      ).
      html('p', array(), externalreference('http://dev.mysql.com/doc/refman/5.0/en/using-explain.html', 'MySQL 5.0 Reference Manual :: 7.2.1 Optimizing Queries with EXPLAIN'))
    );
  }

  /********************************************************************************************/

  if ($action == 'phpinfo') {
    phpinfo();
    exit;
  }

  /********************************************************************************************/

  error('unknown action '.$action);
?>
