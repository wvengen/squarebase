<?php
  include('functions.php');

  $action = parameter('get', 'action', 'login');

  if ($action == 'style')
    augment_file('style.css', 'text/css');
  if ($action == 'script')
    augment_file('script.js', 'text/javascript');

  addtolist('logs', 'action', $action.' '.array_show(parameter('get')));

  session_set_cookie_params(7 * 24 * 60 * 60);
  session_save_path('session');
  session_start();

  set_best_locale(
    preg_replace(
      array('@\.[a-z][a-z0-9\-]*@', '@_([a-z]+)@ie'       ),
      array(''                    , '"-".strtolower("$1")'),
      join_clean(',',
        parameter('get', 'metabasename') && mysql_num_rows(query('meta', 'SHOW DATABASES LIKE "<metabasename>"', array('metabasename'=>parameter('get', 'metabasename')))) && mysql_num_rows(query('meta', 'SHOW TABLES FROM `<metabasename>` LIKE "languages"', array('metabasename'=>parameter('get', 'metabasename')))) ? query1field('meta', 'SELECT languagename FROM `<metabasename>`.languages', array('metabasename'=>parameter('get', 'metabasename'))).';q=4.0' : null,
        parameter('get', 'language')     ? parameter('get', 'language').';q=3.0' : null,
        parameter('session', 'language') ? parameter('session', 'language').';q=2.0' : null,
        parameter('server', 'HTTP_ACCEPT_LANGUAGE'),
        'en;q=0.0'
      )
    ),
    join_clean(',',
      parameter('server', 'HTTP_ACCEPT_CHARSET'),
      '*;q=0.0'
    )
  );

  bindtextdomain('messages', './locale');
  textdomain('messages');

  $ajaxy = parameter('get', 'ajaxy');
  if (!is_null($ajaxy))
    $_SESSION['ajaxy'] = $ajaxy == 'on';

  /********************************************************************************************/

  if ($action == 'login') {
    $username = parameter('get', 'username', 'root');
    $host     = parameter('get', 'host', 'localhost');
    $password = parameter('get', 'password');

    page($action, null,
      form(
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array('class'=>'small'), html('label', array('for'=>'username'), _('username'))).html('td', array(), html('input', array('type'=>'text',     'id'=>'username', 'name'=>'username', 'value'=>$username))),
              html('td', array('class'=>'small'), html('label', array('for'=>'host'    ), _('host'    ))).html('td', array(), html('input', array('type'=>'text',     'id'=>'host',     'name'=>'host',     'value'=>$host))),
              html('td', array('class'=>'small'), html('label', array('for'=>'password'), _('password'))).html('td', array(), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password', 'value'=>$password))),
              html('td', array('class'=>'small'), html('label', array('for'=>'language'), _('language'))).html('td', array(), select_locale()),
              html('td', array('class'=>'small'), '').                                                    html('td', array(), html('input', array('type'=>'submit',                     'name'=>'action',   'value'=>'connect', 'class'=>join_clean(' ', 'button', 'mainsubmit'))))
            )
          )
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'connect') {
    $username = parameter('get', 'username');
    $host     = parameter('get', 'host');
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
    $metabases = query('root', 'SHOW DATABASES WHERE `Database` != "information_schema"');
    $rows = array(html('th', array(), array(_('database'), _('metabase'), '')));
    $links = array();
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['Database'];
      $databasenames = databasenames($metabasename);
      foreach ($databasenames as $databasename) {
        $link = array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename);
        $links[] = $link;
        $rows[] =
          html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
            html('td', array(),
              internalreference($link, $databasename)
            ).
            html('td', array('class'=>'small'),
              array(
                has_grant($metabasename, 'DROP') ? internalreference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename) : null,
                has_grant($databasename, 'DROP') ? internalreference(array('action'=>'drop_database', 'databasename'=>$metabasename), 'drop') : null
              )
            )
          );
      }
    }

    if (count($links) == 0 && has_grant('?', 'CREATE'))
      internalredirect(array('action'=>'new_metabase_from_database'));

    if (count($links) == 1 && !has_grant('?', 'CREATE'))
      internalredirect($links[0]);

    page($action, null,
      (has_grant('?', 'CREATE') ? internalreference(array('action'=>'new_metabase_from_database'), _('new metabase from database')) : '').
      html('table', array(), join($rows))
    );
  }

  /********************************************************************************************/

  if ($action == 'new_metabase_from_database') {
    $rows = array(html('th', array(), array(_('database'), _('tables'), '')));
    $databases = query('root', 'SHOW DATABASES WHERE `Database` != "information_schema"');
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['Database'];
      $dblist = array();
      $dbs = databasenames($databasename);
      if ($dbs) {
        foreach ($dbs as $db)
          $dblist[] = internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$db, 'metabasename'=>$databasename), $db);
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $dblist));
      }
      else {
        $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
        if ($tables) {
          $tablelist = array();
          while ($table = mysql_fetch_assoc($tables)) {
            $tablelist[] = $table["Tables_in_$databasename"];
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
              internalreference(array('action'=>'drop_database', 'databasename'=>$databasename), 'drop')
            )
          )
        );
    }
    page($action, null,
      form(
        html('table', array(), join($rows))
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
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop_database_really', 'class'=>'button')).
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

    $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
    if ($tables) {
      $tablelist = array();
      while ($table = mysql_fetch_assoc($tables)) {
        $tablelist[] = $table["Tables_in_$databasename"];
      }
      $tables = join(', ', $tablelist);
    }

    page($action, path(null, $databasename),
      form(
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array('class'=>'small'), html('label', array('for'=>'databasename'), _('databasename'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly'))),
              html('td', array('class'=>'small'), html('label', array(), _('tables'))).html('td', array(),
                html('input', array('type'=>'text', 'name'=>'tables', 'value'=>$tables, 'readonly'=>'readonly', 'class'=>'readonly')).
                html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename))
              ),
              html('td', array('class'=>'small'), html('label', array('for'=>'language'), _('language'))).html('td', array(), select_locale())
            )
          )
        ).
        html('p', array(),
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'form_metabase_for_database', 'class'=>'button'))
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

    if (!$metabasename) {
      $mbnames = array();
      $metabases = query('root', 'SHOW DATABASES WHERE `Database` != "information_schema"');
      while ($metabase = mysql_fetch_assoc($metabases)) {
        $mbname = $metabase['Database'];
        if ($mbname != 'mysql') {
          $dbs = databasenames($mbname);
          foreach ($dbs as $db)
            if ($db == $databasename)
              $mbnames[] = $mbname;
        }
      }
    }

    $presentationnames = get_presentationnames();

    $fields = $alltables = $primarykeyfieldname = $tableswithoutsinglevaluedprimarykey = array();
    $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table["Tables_in_$databasename"];

      $alltables[] = $tablename;

      $allprimarykeyfieldnames = array();
      $fields[$tablename] = query('data', 'SHOW COLUMNS FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$tablename));
      while ($field = mysql_fetch_assoc($fields[$tablename])) {
        $fieldname = $field['Field'];
        if ($field['Key'] == 'PRI')
          $allprimarykeyfieldnames[] = $field['Field'];

        $typeinfo = $field['Type'];
        list($typeinfo, $type          ) = preg_delete('@^(\w+) *@',     $typeinfo);
        list($typeinfo, $typelength    ) = preg_delete('@^\((\d+)\) *@', $typeinfo);
      }
      if (count($allprimarykeyfieldnames) == 1)
        $primarykeyfieldname[$tablename] = $allprimarykeyfieldnames[0];
      else
        $tableswithoutsinglevaluedprimarykey[] = $tablename;
    }

    $header =
      html('tr', array(),
        html('th', array(),
          array(
            _('table'), _('list'), _('field'), _('title'), _('type'), _('len'), _('unsg'), _('fill'), _('null'), _('auto'), _('more'), _('presentation'), _('key'), _('desc'), _('list'), _('edit')
          )
        )
      );

    $totalstructure = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];
      $plural = $tablename;
      $singular = singularize_noun($plural);

      $max_in_desc = $max_in_list = $max_in_edit = 0;
      $fieldextra = array();
      $fieldnr = 0;
      for (mysql_data_reset($fields[$tablename]); $field = mysql_fetch_assoc($fields[$tablename]); ) {
        $fieldname = $field['Field'];
        $fieldextra[$fieldname] = array();

        $augmentedfield =
          array_merge(
            $field,
            array(
              'Database'=>$databasename,
              'Table'=>$tablename,
              'Alltables'=>$alltables,
              'Primarykeyfieldname'=>$primarykeyfieldname,
              'FieldNr'=>$fieldnr++,
              'NumFields'=>mysql_num_rows($fields[$tablename])
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

        $fieldextra[$fieldname]['presentationnames'] = array_keys($probabilities);
        $fieldextra[$fieldname]['presentationname'] = $bestpresentationname;
        $fieldextra[$fieldname]['linkedtable'] = $bestpresentationname == 'lookup' ? linkedtable_lookup($tablename, $fieldname) : null;

        $fieldextra[$fieldname]['in_desc'] = call_user_func("in_desc_$bestpresentationname", $augmentedfield);
        $fieldextra[$fieldname]['in_list'] = call_user_func("in_list_$bestpresentationname", $augmentedfield);
        $fieldextra[$fieldname]['in_edit'] = call_user_func("in_edit_$bestpresentationname", $augmentedfield);

        $max_in_desc = max($max_in_desc, $fieldextra[$fieldname]['in_desc']);
        $max_in_list = max($max_in_list, $fieldextra[$fieldname]['in_list']);
        $max_in_edit = max($max_in_edit, $fieldextra[$fieldname]['in_edit']);
      }

      $tablestructure = array();
      for (mysql_data_reset($fields[$tablename]); $field = mysql_fetch_assoc($fields[$tablename]); ) {
        $fieldname = $field['Field'];

        $originals = $metabasename ? query('meta', 'SELECT mt.intablelist, title, type, typelength, typeunsigned, typezerofill, presentationname, nullallowed, autoincrement, indesc, inlist, inedit, mt2.tablename AS foreigntablename FROM `<metabasename>`.tables AS mt LEFT JOIN `<metabasename>`.fields AS mf ON mf.tableid = mt.tableid LEFT JOIN `<metabasename>`.presentations mr ON mr.presentationid = mf.presentationid LEFT JOIN `<metabasename>`.tables AS mt2 ON mf.foreigntableid = mt2.tableid WHERE mt.tablename = "<tablename>" AND fieldname = "<fieldname>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname)) : null;
        if ($originals) {
          $original = mysql_fetch_assoc($originals);
          $title            = $original['title'];
          $type             = $original['type'];
          $typelength       = $original['typelength'];
          $typeunsigned     = $original['typeunsigned'];
          $typezerofill     = $original['typezerofill'];
          $intablelist      = $original['intablelist'];
          $presentationname = $original['presentationname'];
          $nullallowed      = $original['nullallowed'];
          $autoincrement    = $original['autoincrement'];
          $linkedtable      = $original['foreigntablename'];
          $indesc           = $original['indesc'];
          $inlist           = $original['inlist'];
          $inedit           = $original['inedit'];

          $typeinfo = '';
          $numeric = $type == 'int';
        }
        else {
          $title = preg_replace(
            array('@(?<=[a-z])([A-Z]+)@e', '@id$@i', "@^(.*?)\b( *(?:$singular|$plural) *)\b(.*?)$@ie"       , '@(?<=[\w ])id$@i', '@ {2,}@', '@(^ +| +$)@'),
            array('strtolower(" $1")'    , ' id'   ,'"$1" && "$3" ? "$1 $3" : ("$1" || "$3" ? "$1$3" : "$0")', ''                , ' '      , ''           ),
            $fieldname
          );
          $intablelist   = TRUE;
          $typeinfo = $field['Type'];
          list($typeinfo, $type          ) = preg_delete('@^(\w+) *@',         $typeinfo);
          list($typeinfo, $typelength    ) = preg_delete('@^\((\d+)\) *@',     $typeinfo);
          list($typeinfo, $typemd        ) = preg_delete('@^\((\d+,\d+)\) *@', $typeinfo); //ignored non-standard syntax: "(M,D)" means than values can be stored with up to M digits in total, of which D digits may be after the decimal point
          list($typeinfo, $typeunsigned  ) = preg_delete('@(unsigned) *@',     $typeinfo);
          list($typeinfo, $typezerofill  ) = preg_delete('@(zerofill) *@',     $typeinfo);

          $numeric = $type == 'int';

          $nullallowed = $field['Null'] == 'YES';

          $extrainfo = $field['Extra'];
          list($extrainfo, $autoincrement) = preg_delete('@(auto_increment) *@', $extrainfo);

          $presentationname = $fieldextra[$fieldname]['presentationname'];
          $linkedtable = $fieldextra[$fieldname]['linkedtable'];

          $indesc = $fieldextra[$fieldname]['in_desc'] == $max_in_desc;
          $inlist = $fieldextra[$fieldname]['in_list'] == $max_in_list;
          $inedit = $fieldextra[$fieldname]['in_edit'] == $max_in_edit;
        }

        $tableoptions = array();
        $tableoptions[] = html('option', array('value'=>'', 'selected'=>!$linkedtable ? 'selected' : null), '').$tableoptions;
        foreach ($alltables as $onetable)
          $tableoptions[] = html('option', array('value'=>$onetable, 'selected'=>$onetable == $linkedtable ? 'selected' : null), $onetable);

        $presentationnamespositive = $presentationnameszero = array();
        foreach ($fieldextra[$fieldname]['presentationnames'] as $onepresentationname)
          if ($probabilities[$onepresentationname])
            $presentationnamespositive[$onepresentationname] = $probabilities[$onepresentationname];
          else
            $presentationnameszero[] = $onepresentationname;
        arsort($presentationnamespositive);
        sort($presentationnameszero);

        $positiveoptions = array();
        foreach ($presentationnamespositive as $onepresentationname=>$probability)
          $positiveoptions[] = html('option', array('value'=>$onepresentationname, 'selected'=>$onepresentationname == $presentationname ? 'selected' : null), $onepresentationname);
        $zerooptions = array();
        foreach ($presentationnameszero as $onepresentationname)
          $zerooptions[] = html('option', array('value'=>$onepresentationname, 'selected'=>$onepresentationname == $presentationname ? 'selected' : null), $onepresentationname);
        $presentationnameoptions = html('optgroup', array(), join($positiveoptions)).html('optgroup', array('label'=>'------------------------'), join($zerooptions));

        $issimpletype = preg_match('@^(tinyint|smallint|mediumint|int|integer|bigint|char|varchar|date|datetime)$@', $type);
        $tablestructure[] =
          html('tr', array('class'=>'list'),
            ($tablestructure
            ? ''
            : html('td', array('class'=>join_clean(' ', 'top', 'nolist'), 'rowspan'=>mysql_num_rows($fields[$tablename])),
                $tablename.
                html('ol', array('class'=>'pluralsingular'),
                  html('li', array(),
                    array(
                      html('label', array('for'=>"$tablename:singular"), '1').html('input', array('type'=>'text', 'name'=>"$tablename:singular", 'id'=>"$tablename:singular", 'value'=>$singular)),
                      html('label', array('for'=>"$tablename:plural"), '2').html('input', array('type'=>'text', 'name'=>"$tablename:plural", 'id'=>"$tablename:plural", 'value'=>$plural))
                    )
                  )
                )
              ).
              html('td', array('class'=>join_clean(' ', 'top', 'nolist'), 'rowspan'=>mysql_num_rows($fields[$tablename])),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:intablelist", 'checked'=>$intablelist ? 'checked' : null))
              )
            ).
            html('td', array(), $fieldname.($originals ? '*' : '')).
            html('td', array(), html('input', array('type'=>'text', 'class'=>'title', 'name'=>"$tablename:$fieldname:title", 'value'=>$title))).
            html('td', array(),
              html('select', array('name'=>"$tablename:$fieldname:type"),
                html('option', array('value'=>'int'     , 'selected'=>$type == 'int'      ? 'selected' : null), 'int'     ).
                html('option', array('value'=>'varchar' , 'selected'=>$type == 'varchar'  ? 'selected' : null), 'varchar' ).
                html('option', array('value'=>'datetime', 'selected'=>$type == 'datetime' ? 'selected' : null), 'datetime').
                ($type != 'int' && $type != 'varchar' && $type != 'datetime' ? html('option', array('value'=>$type, 'selected'=>'selected'), $type) : '')
              )
            ).
            html('td', array(), html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:typelength", 'value'=>$typelength))).
            html('td', array('class'=>'center'), $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typeunsigned", 'checked'=>$typeunsigned ? 'checked' : null)) : '').
            html('td', array('class'=>'center'), $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typezerofill", 'checked'=>$typezerofill ? 'checked' : null)) : '').
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:nullallowed", 'checked'=>$nullallowed ? 'checked' : null))).
            html('td', array('class'=>'center'), $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:autoincrement", 'checked'=>$autoincrement ? 'checked' : null)) : '').
            html('td', array(), join_clean(' ', $typeinfo, $extrainfo)).
            html('td', array(), html('select', array('name'=>"$tablename:$fieldname:presentationname", 'class'=>'presentationname'), $presentationnameoptions)).
            html('td', array(),
              ($fieldname == $primarykeyfieldname[$tablename]
              ? _('primary').html('input', array('type'=>'hidden', 'name'=>"$tablename:primary", 'value'=>$fieldname))
              : ($issimpletype
                ? html('select', array('name'=>"$tablename:$fieldname:foreigntablename", 'class'=>'foreigntablename'),
                    join($tableoptions)
                  )
                : ''
                )
              )
            ).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:indesc", 'checked'=>$indesc ? 'checked' : null))).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inlist", 'checked'=>$inlist ? 'checked' : null))).
            html('td', array('class'=>'center'), html('input', array('type'=>'checkbox', 'class'=>'checkboxedit insome', 'name'=>"$tablename:$fieldname:inedit", 'checked'=>$inedit ? 'checked' : null)))
          );
      }
      $totalstructure[] = $header.join($tablestructure);
    }

    page($action, path(null, $databasename),
      $tableswithoutsinglevaluedprimarykey
      ? html('p', array(),
          html('span', array('class'=>'error'), sprintf(_('no single valued primary key for table(s) %s'), join(', ', $tableswithoutsinglevaluedprimarykey)))
        )
      : form(
          html('table', array(),
            html('tr', array(),
              array(
                html('td', array('class'=>'small'), html('label', array('for'=>'metabasename'), _('metabasename'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$metabasename ? $metabasename : (count($mbnames) == 1 ? $mbnames[0] : "${databasename}_metabase"), 'class'=>'notempty'))),
                html('td', array('class'=>'small'), html('label', array('for'=>'databasename'), _('databasename'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'databasename', 'value'=>$databasename, 'readonly'=>'readonly', 'class'=>'readonly'))),
                html('td', array('class'=>'small'), html('label', array('for'=>'language'), _('language'))).html('td', array(), html('input', array('type'=>'text', 'name'=>'language', 'value'=>$language, 'readonly'=>'readonly', 'class'=>'readonly')))
              )
            )
          ).
          html('table', array(),
            join($totalstructure)
          ).
          ($metabasename ? "* = from $metabasename" : '').
          html('p', array(),
            html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'extract_structure_from_database_to_metabase', 'class'=>'button'))
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
        'typelength       INT UNSIGNED         ,'.
        'typeunsigned     BOOLEAN      NOT NULL,'.
        'typezerofill     BOOLEAN      NOT NULL,'.
        'autoincrement    BOOLEAN      NOT NULL,'.
        'nullallowed      BOOLEAN      NOT NULL,'.
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

    $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
    $tableids = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table["Tables_in_$databasename"];
      $tableids[$tablename] = insertorupdate($metabasename, 'tables', array('tablename'=>$tablename, 'singular'=>parameter('get', "$tablename:singular"), 'plural'=>parameter('get', "$tablename:plural"), 'intablelist'=>parameter('get', "$tablename:intablelist") == 'on'));
    }

    $errors = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];
      $tableid = $tableids[$tablename];

      $descs = $sorts = $lists = $edits = 0;
      $fields = query('data', 'SHOW COLUMNS FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$tablename));
      while ($field = mysql_fetch_assoc($fields)) {
        $fieldname = $field['Field'];

        $foreigntablename = parameter('get', "$tablename:$fieldname:foreigntablename");

        $indesc = parameter('get', "$tablename:$fieldname:indesc") ? 1 : 0;
        $inlist = parameter('get', "$tablename:$fieldname:inlist") ? 1 : 0;
        $inedit = parameter('get', "$tablename:$fieldname:inedit") ? 1 : 0;

        $fieldid = insertorupdate($metabasename, 'fields', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'title'=>parameter('get', "$tablename:$fieldname:title"), 'type'=>parameter('get', "$tablename:$fieldname:type"), 'typelength'=>parameter('get', "$tablename:$fieldname:typelength"), 'typeunsigned'=>parameter('get', "$tablename:$fieldname:typeunsigned") ? 1 : 0, 'typezerofill'=>parameter('get', "$tablename:$fieldname:typezerofill") ? 1 : 0, 'presentationid'=>$presentationids[parameter('get', "$tablename:$fieldname:presentationname")], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'autoincrement'=>parameter('get', "$tablename:$fieldname:autoincrement") ? 1 : 0, 'nullallowed'=>parameter('get', "$tablename:$fieldname:nullallowed") ? 1 : 0, 'indesc'=>$indesc, 'inlist'=>$inlist, 'inedit'=>$inedit));

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
    if ($errors)
      error(join(', ', $errors));

    internalredirect(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'form_metabase_to_database') {
    $rows = array();
    $metabases = query('root', 'SHOW DATABASES WHERE `Database` != "information_schema"');
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['Database'];
      $databasenames = databasenames($metabasename);
      if ($databasenames)
        $rows[] = html('td', array(), internalreference(array('action'=>'form_database_for_metabase', 'metabasename'=>$metabasename), $metabasename));
    }
    page($action, null,
      html('table', array(), html('tr', array(), $rows))
    );
  }

  /********************************************************************************************/

  if ($action == 'form_database_for_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $rows = array();
    $databasenames = databasenames($metabasename);
    if ($databasenames) {
      foreach ($databasenames as $databasename)
        $rows[] =
          html('td', array(),
            internalreference(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $databasename)
          );
      $rows[] =
        html('td', array(),
          html('input', array('type'=>'text', 'name'=>'databasename')).
          html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'update_database_from_metabase', 'class'=>join_clean(' ', 'button', 'mainsubmit')))
        );
    }
    page($action, path($metabasename),
      form(
        html('table', array(), html('tr', array(), $rows))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'update_database_from_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');

    if (has_grant($metabasename, 'ALL')) {
      query('meta', 'INSERT IGNORE INTO `<metabasename>`.databases SET databasename = "<databasename>"', array('metabasename'=>$metabasename, 'databasename'=>$databasename));

      query('data', 'CREATE DATABASE IF NOT EXISTS `<databasename>`', array('databasename'=>$databasename));

      $tables = query('meta', 'SELECT * FROM `<metabasename>`.tables mt LEFT JOIN `<metabasename>`.fields mf ON mf.fieldid = mt.uniquefieldid', array('metabasename'=>$metabasename));
      while ($table = mysql_fetch_assoc($tables)) {
        if (!$table['fieldname'])
          error(sprintf(_('table %s has no single valued primary key'), $table['tablename']));

        $totaltype = totaltype($table);
        query('data', 'CREATE TABLE IF NOT EXISTS `<databasename>`.`<tablename>` (<fieldname> <totaltype>)', array('databasename'=>$databasename, 'tablename'=>$table['tablename'], 'fieldname'=>$table['fieldname'], 'totaltype'=>$totaltype));

        $associatedoldfields = array();
        $oldfields = query('data', 'SHOW COLUMNS FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$table['tablename']));
        while ($oldfield = mysql_fetch_assoc($oldfields))
          $associatedoldfields[$oldfield['Field']] = $oldfield;

        $associatedoldindices = array();
        $oldindices = query('data', 'SHOW INDEX FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$table['tablename']));
        while ($oldindex = mysql_fetch_assoc($oldindices))
          if ($oldindex['Seq_in_index'] == 1)
            $associatedoldindices[$oldindex['Column_name']] = $oldindex;

        $fields = query('meta', 'SELECT mt.tablename, mf.fieldid, mf.fieldname, mf.foreigntableid, mt.uniquefieldid, mf.type, mf.typelength, mf.typeunsigned, mf.typezerofill, mf.nullallowed FROM `<metabasename>`.fields mf LEFT JOIN `<metabasename>`.tables mt ON mt.tableid = mf.tableid WHERE mf.tableid = <tableid>', array('metabasename'=>$metabasename, 'tableid'=>$table['tableid']));
        while ($field = mysql_fetch_assoc($fields)) {
          if ($field['uniquefieldid'] != $field['fieldid']) {
            $oldfield = $associatedoldfields[$field['fieldname']];
            $oldtype = $oldfield['Type'].($oldfield['Null'] == 'YES' ? '' : ' not null');
            $newtype = totaltype($field);
            if ($oldfield) {
              if (strcasecmp($oldtype, $newtype))
                query('data', 'ALTER TABLE `<databasename>`.`<tablename>` MODIFY COLUMN <fieldname> <newtype> #warning WAS <oldtype>', array('databasename'=>$databasename, 'tablename'=>$field['tablename'], 'fieldname'=>$field['fieldname'], 'newtype'=>$newtype, 'oldtype'=>$oldtype));
            }
            else
              query('data', 'ALTER TABLE `<databasename>`.`<tablename>` ADD COLUMN (<fieldname> <newtype>) #warning WAS <oldtype>', array('databasename'=>$databasename, 'tablename'=>$field['tablename'], 'fieldname'=>$field['fieldname'], 'newtype'=>$newtype, 'oldtype'=>$oldtype));
            if ($field['foreigntableid'] && !$associatedoldindices[$field['fieldname']])
              query('data', 'ALTER TABLE `<databasename>`.`<tablename>` ADD INDEX (<fieldname>) #warning WAS non-existent', array('databasename'=>$databasename, 'tablename'=>$field['tablename'], 'fieldname'=>$field['fieldname']));
          }
        }
      }
    }

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'show_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tables = query('meta', 'SELECT * FROM `<metabasename>`.tables LEFT JOIN `<metabasename>`.fields ON tables.uniquefieldid = fields.fieldid WHERE intablelist = TRUE ORDER BY tablename', array('metabasename'=>$metabasename));
    $rows = array(html('th', array(), 'table'));
    while ($table = mysql_fetch_assoc($tables)) {
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$table['tablename'], 'uniquefieldname'=>$table['fieldname']), $table['tablename'])
          )
        );
    }
    page($action, path($metabasename, $databasename),
      html('div', array('class'=>'ajax'),
        html('table', array(), join($rows))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'show_table') {
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $offset          = parameter('get', 'offset', 0);
    $orderfieldname  = parameter('get', 'orderfieldname');
    $orderasc        = parameter('get', 'orderasc', 'on') == 'on';

    page($action, path($metabasename, $databasename, $tablename, $uniquefieldname),
      list_table($metabasename, $databasename, $tablename, 0, $offset, $uniquefieldname, $orderfieldname, $orderasc, null, null, null, TRUE)
    );
  }

  /********************************************************************************************/

  if ($action == 'new_record' || $action == 'edit_record' || $action == 'show_record') {
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $uniquevalue     = parameter('get', 'uniquevalue');
    $back            = parameter('get', 'back');

    $fields = fieldsforpurpose($metabasename, $tablename, 'inedit');

    $row = array();
    if (!is_null($uniquevalue))
      $row = query1('data', 'SELECT * FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

    get_presentationnames();

    $lines = array(
      html('td', array('class'=>'header', 'colspan'=>2), query1field('meta', 'SELECT singular FROM `<metabasename>`.tables WHERE tablename = "<tablename>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename)))
    );
    for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
      $field['uniquefieldname'] = $uniquefieldname;
      $field['uniquevalue'] = $uniquevalue;
      $fixedvalue = $value = parameter('get', "field:$field[fieldname]");
      if (!$value && $row)
        $value = $row[$field['fieldname']];
      $cell = call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, $field, $value, $action == 'show_record' || $fixedvalue);
      $lines[] =
        html('td', array('class'=>'description'), html('label', array('for'=>"field:$field[fieldname]"), $field['title'])).
        html('td', array(), $cell);
    }

    $lines[] =
      html('td', array('class'=>'description'), '&rarr;').
      html('td', array(),
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$action == 'show_record' ? 'delete_record' : ($uniquevalue ? 'update_record' : 'add_record'), 'class'=>join_clean(' ', 'mainsubmit', 'button'))).
        (!$uniquevalue ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>join_clean(' ', 'minorsubmit', 'button'))) : '').
        internalreference($back ? $back : parameter('server', 'HTTP_REFERER'), 'cancel', array('class'=>'cancel')).
        ($action == 'edit_record' ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'delete_record', 'class'=>join_clean(' ', 'mainsubmit', 'button', 'delete'))) : '')
      );

    if (!is_null($uniquevalue)) {
      $referringfields = query('meta', 'SELECT mt.tablename, mf.fieldname AS fieldname, mf.title AS title, mfu.fieldname AS uniquefieldname FROM `<metabasename>`.fields mf LEFT JOIN `<metabasename>`.tables mtf ON mtf.tableid = mf.foreigntableid LEFT JOIN `<metabasename>`.tables mt ON mt.tableid = mf.tableid LEFT JOIN `<metabasename>`.fields mfu ON mt.uniquefieldid = mfu.fieldid WHERE mtf.tablename = "<tablename>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename));
      while ($referringfield = mysql_fetch_assoc($referringfields)) {
        $lines[] =
          html('td', array('class'=>'description'), $referringfield['tablename'].html('div', array('class'=>'referrer'), sprintf(_('via %s'), $referringfield['title']))).
          html('td', array(), list_table($metabasename, $databasename, $referringfield['tablename'], 0, 0, $referringfield['uniquefieldname'], null, TRUE, $referringfield['fieldname'], $uniquevalue, $tablename, $action != 'show_record'));
      }
    }

    page($action, path($metabasename, $databasename, $tablename, $tableid, $uniquefieldname, $uniquevalue),
      form(
        html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'tablename', 'value'=>$tablename)).
        html('input', array('type'=>'hidden', 'name'=>'uniquefieldname', 'value'=>$uniquefieldname)).
        html('input', array('type'=>'hidden', 'name'=>'uniquevalue', 'value'=>$uniquevalue)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
        html('table', array('class'=>'tableedit'), html('tr', array(), $lines))
      )
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
    $metabasename    = parameter('get', 'metabasename');
    $databasename    = parameter('get', 'databasename');
    $tablename       = parameter('get', 'tablename');
    $uniquefieldname = parameter('get', 'uniquefieldname');
    $uniquevalue     = parameter('get', 'uniquevalue');
    $back            = parameter('get', 'back');

    get_presentationnames();

    $fieldnamesandvalues = array();
    $fields = fieldsforpurpose($metabasename, $tablename, 'inedit');
    while ($field = mysql_fetch_assoc($fields)) {
      $fieldnamesandvalues[$field['fieldname']] = call_user_func("formvalue_$field[presentationname]", $field);
    }

    $uniquevalue = insertorupdate($databasename, $tablename, $fieldnamesandvalues, $uniquefieldname, $uniquevalue);
    if ($action == 'add_record_and_edit')
      internalredirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue, 'back'=>$back));

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

  if ($action == 'phpinfo') {
    phpinfo();
    exit;
  }

  /********************************************************************************************/

  error('unknown action '.$action);
?>
