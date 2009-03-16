<?php
  include('functions.php');

  session_set_cookie_params(7 * 24 * 60 * 60);
  session_save_path('session');
  session_start();

  best_locale();

  bindtextdomain('messages', './locale');
  textdomain('messages');

  $action = parameter('get', 'action', 'login');
  addtolist('logs', 'action', $action.' '.array_show(parameter('get')));

  /********************************************************************************************/

  if ($action == 'login') {
    page($action, null,
      form(
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array('class'=>'small'), html('label', array('for'=>'username'), _('username'))).html('td', array(), html('input', array('type'=>'text',     'id'=>'username', 'name'=>'username', 'value'=>'root'))),
              html('td', array('class'=>'small'), html('label', array('for'=>'host'    ), _('host'    ))).html('td', array(), html('input', array('type'=>'text',     'id'=>'host',     'name'=>'host',     'value'=>'localhost'))),
              html('td', array('class'=>'small'), html('label', array('for'=>'password'), _('password'))).html('td', array(), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password'))),
              html('td', array('class'=>'small'), html('label', array('for'=>'language'), _('language'))).html('td', array(), html('select', array('id'=>'language', 'name'=>'language'), html('option', array(), 'en_US').html('option', array(), 'nl_NL'))),
              html('td', array('class'=>'small'), '&nbsp;').                                              html('td', array(), html('input', array('type'=>'submit',                     'name'=>'action',   'value'=>'connect', 'class'=>'button mainsubmit')))
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
    $metabases = query('root', 'SHOW DATABASES');
    $rows = array(html('th', array(), array(_('metabase'), _('database'))));
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['Database'];
      $databasenames = databasenames($metabasename);
      if ($databasenames) {
        $databaselist = array();
        while ($databasename = mysql_fetch_assoc($databasenames))
          $databaselist[] = internalreference(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename['value']), $databasename['value']);
        $rows[] =
          html('tr', array('class'=>join(' ', array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list'))),
            html('td', array('class'=>'small'),
              internalreference(array('action'=>'form_database_for_metabase', 'metabasename'=>$metabasename), $metabasename)
            ).
            html('td', array(),
              html('ul', array('class'=>'compact'), html('li', array(), $databaselist))
            )
          );
      }
    }
    page($action, null,
      internalreference(array('action'=>'new_metabase_from_database'), _('new metabase from database')).
      html('table', array(), join($rows))
    );
  }

  /********************************************************************************************/

  if ($action == 'new_metabase_from_database') {
    $rows = array(html('th', array(), array(_('database'), _('tables'), '&nbsp;')));
    $databases = query('root', 'SHOW DATABASES');
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['Database'];
      $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
      $dblist = array();
      $dbs = databasenames($databasename);
      if ($dbs) {
        while ($db = mysql_fetch_assoc($dbs))
          $dblist[] = internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$db['value'], 'metabasename'=>$databasename), $db['value']);
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $dblist));
      }
      elseif ($tables) {
        $tablelist = array();
        while ($table = mysql_fetch_assoc($tables)) {
          $tablelist[] = $table["Tables_in_$databasename"];
        }
        $fulllist = null;
        if (count($tablelist) > 5) {
          $fulllist = join(' ', array_slice($tablelist, 4));
          array_splice($tablelist, 4);
        }
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $tablelist).($fulllist ? html('li', array('title'=>$fulllist), '&hellip') : ''));
      }
      $rows[] =
        html('tr', array('class'=>join(' ', array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list'))),
          html('td', array(),
            array(
              internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$databasename), $databasename),
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
    page($action, path('&hellip;', $databasename),
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

  if ($action == 'form_metabase_for_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');

    if (!$metabasename) {
      $mbnames = array();
      $metabases = query('root', 'SHOW DATABASES');
      while ($metabase = mysql_fetch_assoc($metabases)) {
        $mbname = $metabase['Database'];
        if ($mbname != 'mysql') {
          $dbs = databasenames($mbname);
          if ($dbs)
            while ($db = mysql_fetch_assoc($dbs))
              if ($db['value'] == $databasename)
                $mbnames[] = $mbname;
        }
      }
    }

    $presentations = get_presentations();

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
        list($typeinfo, $type          ) = preg_delete('/^(\w+) */',     $typeinfo);
        list($typeinfo, $typelength    ) = preg_delete('/^\((\d+)\) */', $typeinfo);
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
            _('table'), _('list'), _('field'), _('type'), _('len'), _('unsg'), _('fill'), _('null'), _('auto'), _('more'), _('typename'), _('presentation'), _('key'), _('desc'), _('list'), _('edit')
          )
        )
      );

    $totalstructure = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];

      $tablestructure = array();
      $desc = $sort = $list = $edit = $fieldnr = 0;
      for (mysql_data_reset($fields[$tablename]); $field = mysql_fetch_assoc($fields[$tablename]); ) {
        $fieldname = $field['Field'];

        $originals = $metabasename ? query('meta', 'SELECT mt.intablelist, typename, type, typelength, typeunsigned, typezerofill, presentation, nullallowed, autoincrement, indesc, inlist, inedit, mt2.tablename AS foreigntablename FROM `<metabasename>`.metatable AS mt LEFT JOIN `<metabasename>`.metafield AS mf ON mf.tableid = mt.tableid LEFT JOIN `<metabasename>`.metatype AS my ON my.typeid = mf.typeid LEFT JOIN `<metabasename>`.metapresentation mr ON mr.presentationid = my.presentationid LEFT JOIN `<metabasename>`.metatable AS mt2 ON mf.foreigntableid = mt2.tableid WHERE mt.tablename = \'<tablename>\' AND fieldname = \'<fieldname>\'', array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname)) : null;
        if ($originals) {
          $original = mysql_fetch_assoc($originals);
          $type          = $original['type'];
          $typelength    = $original['typelength'];
          $typeunsigned  = $original['typeunsigned'];
          $typezerofill  = $original['typezerofill'];
          $intablelist   = $original['intablelist'];
          $typename      = $original['typename'];
          $presentation  = $original['presentation'];
          $nullallowed   = $original['nullallowed'];
          $autoincrement = $original['autoincrement'];
          $linkedtable   = $original['foreigntablename'];
          $indesc        = $original['indesc'];
          $inlist        = $original['inlist'];
          $inedit        = $original['inedit'];

          $typeinfo = '';
          $numeric = $type == 'int';
        }
        else {
          $intablelist   = TRUE;
          $typeinfo = $field['Type'];
          list($typeinfo, $type          ) = preg_delete('/^(\w+) */',         $typeinfo);
          list($typeinfo, $typelength    ) = preg_delete('/^\((\d+)\) */',     $typeinfo);
          list($typeinfo, $typemd        ) = preg_delete('/^\((\d+,\d+)\) */', $typeinfo); //ignored non-standard syntax: "(M,D)" means than values can be stored with up to M digits in total, of which D digits may be after the decimal point
          list($typeinfo, $typeunsigned  ) = preg_delete('/(unsigned) */',     $typeinfo);
          list($typeinfo, $typezerofill  ) = preg_delete('/(zerofill) */',     $typeinfo);

          $numeric = $type == 'int';

          $nullallowed = $field['Null'] == 'YES';

          $extrainfo = $field['Extra'];
          list($extrainfo, $autoincrement) = preg_delete('/(auto_increment) */', $extrainfo);

          $augmentedfield = 
            array_merge(
              $field, 
              array(
                'Database'=>$databasename, 
                'Table'=>$tablename, 
                'Linkedtable'=>$linkedtable,
                'Alltables'=>$alltables,
                'Primarykeyfieldname'=>$primarykeyfieldname,
                'FieldNr'=>$fieldnr++,
                'NumFields'=>mysql_num_rows($fields[$tablename])
              )
            );
          $bestpresentation = null;
          $bestprobability = 0;
          foreach ($presentations as $onepresentation) {
            $probability = $probabilities[$onepresentation] = call_user_func("probability_$onepresentation", $augmentedfield);
            if ($probability > $bestprobability) {
              $bestpresentation = $onepresentation;
              $bestprobability = $probability;
            }
          }
          $presentations = array_keys($probabilities);
          $presentation = $bestpresentation;
          $linkedtable = $presentation == 'lookup' ? linkedtable_lookup($tablename, $fieldname) : null;
          $typename = call_user_func("typename_$presentation", $augmentedfield);

          $indesc = call_user_func("in_desc_$presentation", $augmentedfield);
          $inlist = call_user_func("in_list_$presentation", $augmentedfield);
          $inedit = call_user_func("in_edit_$presentation", $augmentedfield);
        }

        $tableoptions = array();
        $tableoptions[] = html('option', array('value'=>'', 'selected'=>!$linkedtable ? 'selected' : null), '').$tableoptions;
        foreach ($alltables as $onetable)
          $tableoptions[] = html('option', array('value'=>$onetable, 'selected'=>$onetable == $linkedtable ? 'selected' : null), $onetable);

        $presentationspositive = $presentationszero = array();
        foreach ($presentations as $onepresentation)
          if ($probabilities[$onepresentation])
            $presentationspositive[$onepresentation] = $probabilities[$onepresentation];
          else
            $presentationszero[] = $onepresentation;
        arsort($presentationspositive);
        sort($presentationszero);

        $positiveoptions = array();
        foreach ($presentationspositive as $onepresentation=>$probability)
          $positiveoptions[] = html('option', array('value'=>$onepresentation, 'selected'=>$onepresentation == $presentation ? 'selected' : null), $onepresentation);
        $zerooptions = array();
        foreach ($presentationszero as $onepresentation)
          $zerooptions[] = html('option', array('value'=>$onepresentation, 'selected'=>$onepresentation == $presentation ? 'selected' : null), $onepresentation);
        $presentationoptions = html('optgroup', array(), join($positiveoptions)).html('optgroup', array('label'=>'------------------------'), join($zerooptions));

        $tablestructure[] =
          html('tr', array(),
            ($tablestructure 
            ? '' 
            : html('td', array('class'=>'rowgroup top', 'rowspan'=>mysql_num_rows($fields[$tablename])), $tablename).
              html('td', array('class'=>'rowgroup top', 'rowspan'=>mysql_num_rows($fields[$tablename])), 
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:intablelist", 'checked'=>$intablelist ? 'checked' : null))
              )
            ).
            html('td', array('class'=>'rowgroup'),
              array(
                $fieldname.($originals ? '*' : ''),
                html('select', array('name'=>"$tablename:$fieldname:type"),
                  html('option', array('value'=>'int'     , 'selected'=>$type == 'int'      ? 'selected' : null), 'int'     ).
                  html('option', array('value'=>'varchar' , 'selected'=>$type == 'varchar'  ? 'selected' : null), 'varchar' ).
                  html('option', array('value'=>'datetime', 'selected'=>$type == 'datetime' ? 'selected' : null), 'datetime').
                  ($type != 'int' && $type != 'varchar' && $type != 'datetime' ? html('option', array('value'=>$type, 'selected'=>'selected'), $type) : '')
                ),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:typelength", 'value'=>$typelength)),
                $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typeunsigned", 'checked'=>$typeunsigned ? 'checked' : null)) : '&nbsp;',
                $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typezerofill", 'checked'=>$typezerofill ? 'checked' : null)) : '&nbsp;',
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:nullallowed", 'checked'=>$nullallowed ? 'checked' : null)),
                $numeric ? html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:autoincrement", 'checked'=>$autoincrement ? 'checked' : null)) : '&nbsp;',
                join(' ', array($typeinfo, $extrainfo)),
                html('input', array('type'=>'text', 'class'=>'typename', 'name'=>"$tablename:$fieldname:typename", 'value'=>$typename)),
                html('select', array('name'=>"$tablename:$fieldname:presentation", 'class'=>'presentation'), $presentationoptions),
                ($fieldname == $primarykeyfieldname[$tablename]
                ? html('input', array('type'=>'hidden', 'name'=>"$tablename:primary", 'value'=>$fieldname))
                : ($type == 'int'
                  ? html('select', array('name'=>"$tablename:$fieldname:foreigntablename"),
                      join($tableoptions)
                    )
                  : '&nbsp;'
                  )
                ),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:indesc", 'checked'=>$indesc ? 'checked' : null)),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:inlist", 'checked'=>$inlist ? 'checked' : null)),
                html('input', array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:inedit", 'checked'=>$inedit ? 'checked' : null))
              )
            )
          );
      }
      $totalstructure[] = $header.join($tablestructure);
    }

    page($action, path('&hellip;', $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('p', array(),
          _('metabase').' '.html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$metabasename ? $metabasename : (count($mbnames) == 1 ? $mbnames[0] : ''), 'class'=>'notempty'))
        ).
        html('table', array(),
          join($totalstructure)
        ).
        ($metabasename ? "* = from $metabasename" : '').
        html('p', array(),
          $tableswithoutsinglevaluedprimarykey
          ? html('span', array('class'=>'error'), sprintf(_('no single valued primary key for table(s) %s'), join(', ', $tableswithoutsinglevaluedprimarykey)))
          : html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'extract_structure_from_database_to_metabase', 'class'=>'button'))
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

    query('meta', 'CREATE TABLE `<metabasename>`.metaconstant ('.
          '  constantid      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  constantname    VARCHAR(100) NOT NULL,'.
          '  UNIQUE KEY (constantname)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    query('meta', 'CREATE TABLE `<metabasename>`.metavalue ('.
          '  valueid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  constantid      INT UNSIGNED NOT NULL,'.
          '  value           VARCHAR(100) NOT NULL,'.
          '  UNIQUE KEY (constantid, value)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    query('meta', 'CREATE TABLE `<metabasename>`.metatable ('.
          '  tableid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  tablename       VARCHAR(100) NOT NULL,'.
          '  uniquefieldid   INT UNSIGNED NOT NULL,'.
          '  intablelist     BOOLEAN NOT NULL,'.
          '  UNIQUE KEY (tablename)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    query('meta', 'CREATE TABLE `<metabasename>`.metafield ('.
          '  fieldid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  tableid         INT UNSIGNED NOT NULL,'.
          '  fieldname       VARCHAR(100) NOT NULL,'.
          '  autoincrement   BOOLEAN NOT NULL,'.
          '  typeid          INT UNSIGNED NOT NULL,'.
          '  nullallowed     BOOLEAN NOT NULL,'.
          '  indesc          BOOLEAN NOT NULL,'.
          '  inlist          BOOLEAN NOT NULL,'.
          '  inedit          BOOLEAN NOT NULL,'.
          '  foreigntableid  INT UNSIGNED         ,'.
          '  UNIQUE KEY (tableid, fieldname)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    query('meta', 'CREATE TABLE `<metabasename>`.metatype ('.
          '  typeid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  typename        VARCHAR(100) NOT NULL,'.
          '  type            VARCHAR(100) NOT NULL,'.
          '  typelength      INT UNSIGNED         ,'.
          '  typeunsigned    INT UNSIGNED         ,'.
          '  typezerofill    INT UNSIGNED         ,'.
          '  presentationid  INT UNSIGNED         ,'.
          '  UNIQUE KEY (typename)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    query('meta', 'CREATE TABLE `<metabasename>`.metapresentation ('.
          '  presentationid  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
          '  presentation    VARCHAR(100) NOT NULL,'.
          '  UNIQUE KEY (presentation)'.
          ')',
          array('metabasename'=>$metabasename)
    );

    $constantid = insertorupdate($metabasename, 'metaconstant', array('constantname'=>'database'), 'constantid');

    insertorupdate($metabasename, 'metavalue', array('constantid'=>$constantid, 'value'=>$databasename));

    $presentations = get_presentations();
    $presentationids = array();
    foreach ($presentations as $presentation)
      $presentationids[$presentation] = insertorupdate($metabasename, 'metapresentation', array('presentation'=>$presentation));

    $tables = query('data', 'SHOW TABLES FROM `<databasename>`', array('databasename'=>$databasename));
    $tableids = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table["Tables_in_$databasename"];
      $tableids[$tablename] = insertorupdate($metabasename, 'metatable', array('tablename'=>$tablename, 'intablelist'=>parameter('get', "$tablename:intablelist") == 'on'), 'tableid');
    }

    $errors = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];
      $tableid = $tableids[$tablename];

      $descs = $sorts = $lists = $edits = 0;
      $fields = query('data', 'SHOW COLUMNS FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$tablename));
      while ($field = mysql_fetch_assoc($fields)) {
        $fieldname = $field['Field'];

        $typename = parameter('get', "$tablename:$fieldname:typename");
        if (!$typeids[$typename]) {
          $typeids[$typename] = insertorupdate($metabasename, 'metatype', array('typename'=>$typename, 'type'=>parameter('get', "$tablename:$fieldname:type"), 'typelength'=>parameter('get', "$tablename:$fieldname:typelength"), 'typeunsigned'=>parameter('get', "$tablename:$fieldname:typeunsigned") ? 1 : 0, 'typezerofill'=>parameter('get', "$tablename:$fieldname:typezerofill") ? 1 : 0, 'presentationid'=>$presentationids[parameter('get', "$tablename:$fieldname:presentation")]), 'typeid');
        }

        $foreigntablename = parameter('get', "$tablename:$fieldname:foreigntablename");

        $indesc = parameter('get', "$tablename:$fieldname:indesc") ? 1 : 0;
        $inlist = parameter('get', "$tablename:$fieldname:inlist") ? 1 : 0;
        $inedit = parameter('get', "$tablename:$fieldname:inedit") ? 1 : 0;

        $fieldid = insertorupdate($metabasename, 'metafield', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'typeid'=>$typeids[$typename], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'autoincrement'=>parameter('get', "$tablename:$fieldname:autoincrement") ? 1 : 0, 'nullallowed'=>parameter('get', "$tablename:$fieldname:nullallowed") ? 1 : 0, 'indesc'=>$indesc, 'inlist'=>$inlist, 'inedit'=>$inedit), 'fieldid');

        $indescs += $indesc;
        $inlists += $inlist;
        $inedits += $inedit;

        if (parameter('get', "$tablename:primary") == $fieldname)
          query('meta', 'UPDATE `<metabasename>`.metatable SET uniquefieldid = <fieldid> WHERE tableid = <tableid>', array('metabasename'=>$metabasename, 'fieldid'=>$fieldid, 'tableid'=>$tableid));
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

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'form_metabase_to_database') {
    $rows = array();
    $metabases = query('root', 'SHOW DATABASES');
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
      while ($databasename = mysql_fetch_assoc($databasenames))
        $rows[] =
          html('td', array(),
            internalreference(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename['value']), $databasename['value'])
          );
      $rows[] =
        html('td', array(),
          html('input', array('type'=>'text', 'name'=>'databasename')).
          html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'update_database_from_metabase', 'class'=>'button mainsubmit'))
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

    query('meta', 'INSERT IGNORE INTO `<metabasename>`.metavalue (constantid, value) SELECT constantid, "<databasename>" FROM `<metabasename>`.metaconstant WHERE constantname = \'<database>\'', array('metabasename'=>$metabasename, 'databasename'=>$databasename));

    query('data', 'CREATE DATABASE IF NOT EXISTS `<databasename>`', array('databasename'=>$databasename));

    $metatables = query('meta', 'SELECT * FROM `<metabasename>`.metatable mt LEFT JOIN `<metabasename>`.metafield mf ON mf.fieldid = mt.uniquefieldid LEFT JOIN `<metabasename>`.metatype my ON mf.typeid = my.typeid', array('metabasename'=>$metabasename));
    while ($metatable = mysql_fetch_assoc($metatables)) {
      $totaltype = totaltype($metatable);
      query('data', 'CREATE TABLE IF NOT EXISTS `<databasename>`.`<tablename>` (<fieldname> <totaltype>)', array('databasename'=>$databasename, 'tablename'=>$metatable['tablename'], 'fieldname'=>$metatable['fieldname'], 'totaltype'=>$totaltype));

      $associatedoldfields = array();
      $oldfields = query('data', 'SHOW COLUMNS FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$metatable['tablename']));
      while ($oldfield = mysql_fetch_assoc($oldfields))
        $associatedoldfields[$oldfield['Field']] = $oldfield;

      $associatedoldindices = array();
      $oldindices = query('data', 'SHOW INDEX FROM `<databasename>`.`<tablename>`', array('databasename'=>$databasename, 'tablename'=>$metatable['tablename']));
      while ($oldindex = mysql_fetch_assoc($oldindices))
        if ($oldindex['Seq_in_index'] == 1)
          $associatedoldindices[$oldindex['Column_name']] = $oldindex;

      $metafields = query('meta', 'SELECT mt.tablename, mf.fieldid, mf.fieldname, mf.foreigntableid, mt.uniquefieldid, my.type, my.typelength, my.typeunsigned, my.typezerofill, mf.nullallowed FROM `<metabasename>`.metafield mf LEFT JOIN `<metabasename>`.metatable mt ON mt.tableid = mf.tableid LEFT JOIN `<metabasename>`.metatype my ON mf.typeid = my.typeid WHERE mf.tableid = <tableid>', array('metabasename'=>$metabasename, 'tableid'=>$metatable['tableid']));
      while ($metafield = mysql_fetch_assoc($metafields)) {
        if ($metafield['uniquefieldid'] != $metafield['fieldid']) {
          $oldfield = $associatedoldfields[$metafield['fieldname']];
          $oldtype = $oldfield['Type'].($oldfield['Null'] == 'YES' ? '' : ' not null');
          $newtype = totaltype($metafield);
          if ($oldfield) {
            if (strcasecmp($oldtype, $newtype))
              query('data', 'ALTER TABLE `<databasename>`.`<tablename>` MODIFY COLUMN <fieldname> <newtype> /* WAS <oldtype> */', array('databasename'=>$databasename, 'tablename'=>$metafield['tablename'], 'fieldname'=>$metafield['fieldname'], 'newtype'=>$newtype, 'oldtype'=>$oldtype));
          }
          else
            query('data', 'ALTER TABLE `<databasename>`.`<tablename>` ADD COLUMN (<fieldname> <newtype>) /* WAS <oldtype> */', array('databasename'=>$databasename, 'tablename'=>$metafield['tablename'], 'fieldname'=>$metafield['fieldname'], 'newtype'=>$newtype, 'oldtype'=>$oldtype));
          if ($metafield['foreigntableid'] && !$associatedoldindices[$metafield['fieldname']])
            query('data', 'ALTER TABLE `<databasename>`.`<tablename>` ADD INDEX (<fieldname>) /* WAS non-existent */', array('databasename'=>$databasename, 'tablename'=>$metafield['tablename'], 'fieldname'=>$metafield['fieldname']));
        }
      }
    }

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'show_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tables = query('meta', 'SELECT * FROM `<metabasename>`.metatable LEFT JOIN `<metabasename>`.metafield ON metatable.uniquefieldid = metafield.fieldid WHERE intablelist = TRUE ORDER BY tablename', array('metabasename'=>$metabasename));
    $rows = array(html('th', array(), 'table'));
    while ($table = mysql_fetch_assoc($tables)) {
      $rows[] =
        html('tr', array('class'=>join(' ', array(count($rows) % 2 ? 'rowodd' : 'roweven', 'list'))),
          html('td', array(),
            internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$table['tablename'], 'uniquefieldname'=>$table['fieldname']), $table['tablename'])
          )
        );
    }
    page($action, path($metabasename, $databasename),
      html('div', array('class'=>'ajax', 'id'=>''),
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

    page($action, path($metabasename, $databasename, $tablename, $uniquefieldname),
      list_table($metabasename, $databasename, $tablename, 0, $offset, $uniquefieldname, $orderfieldname, null, null, null, TRUE, TRUE)
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
      $row = query1('data', 'SELECT * FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = \'<uniquevalue>\'', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

    get_presentations();

    $line = array();
    for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
      $field['uniquefieldname'] = $uniquefieldname;
      $field['uniquevalue'] = $uniquevalue;
      $fixedvalue = $value = parameter('get', "field:$field[fieldname]");
      if (!$value && $row)
        $value = $row[$field['fieldname']];
      $cell = call_user_func("formfield_$field[presentation]", $metabasename, $databasename, $field, $value, $action == 'show_record' || $fixedvalue);
      $lines[] =
        html('td', array('class'=>'description'), html('label', array('for'=>"field:$field[fieldname]"), preg_replace('/(?<=\w)id$/i', '', $field['fieldname']))).
        html('td', array(), $cell);
    }

    $lines[] =
      html('td', array('class'=>'description'), '&rarr;').
      html('td', array(), 
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$action == 'show_record' ? 'delete_record' : ($uniquevalue ? 'update_record' : 'add_record'), 'class'=>'mainsubmit button')).
        (!$uniquevalue ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit button')) : '').
        internalreference($back ? $back : parameter('server', 'HTTP_REFERER'), 'cancel', array('class'=>'cancel')).
        ($action == 'show_record' ? '' : html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'delete_record', 'class'=>'mainsubmit button delete')))
      );

    if (!is_null($uniquevalue)) {
      $referringfields = query('meta', 'SELECT mt.tablename, mf.fieldname AS fieldname, mfu.fieldname AS uniquefieldname FROM `<metabasename>`.metafield mf LEFT JOIN `<metabasename>`.metatable mtf ON mtf.tableid = mf.foreigntableid LEFT JOIN `<metabasename>`.metatable mt ON mt.tableid = mf.tableid LEFT JOIN `<metabasename>`.metafield mfu ON mt.uniquefieldid = mfu.fieldid WHERE mtf.tablename = "<tablename>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename));
      while ($referringfield = mysql_fetch_assoc($referringfields)) {
        $lines[] =
          html('td', array('class'=>'description'), $referringfield['tablename']).
          html('td', array(), list_table($metabasename, $databasename, $referringfield['tablename'], 0, 0, $referringfield['uniquefieldname'], null, $referringfield['fieldname'], $uniquevalue, $tablename, $action != 'show_record', TRUE));
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

    query('data', 'DELETE FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = \'<uniquevalue>\'', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

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

    get_presentations();

    $fieldnamesandvalues = array();
    $fields = fieldsforpurpose($metabasename, $tablename, 'inedit');
    while ($field = mysql_fetch_assoc($fields)) {
      $fieldnamesandvalues[$field['fieldname']] = call_user_func("formvalue_$field[presentation]", $field);
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

    $image = query1field('data', 'SELECT <fieldname> FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = \'<uniquevalue>\'', array('fieldname'=>$fieldname, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));

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
