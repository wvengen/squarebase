<?php
  include('functions.php');

  $privileges = array('select'=>'Select_priv', 'insert'=>'Insert_priv', 'update'=>'Update_priv', 'delete'=>'Delete_priv', 'create'=>'Create_priv', 'drop'=>'Drop_priv', 'alter'=>'Alter_priv', 'grant'=>'Grant_priv');

  session_save_path('session');
  session_start();

  $action = parameter('get', 'action', 'login');
  addtolist('logs', 'action', $action.' '.preg_replace('/^Array/', '', print_r(parameter('get'), true)));

  /********************************************************************************************/

  if ($action == 'login') {
    page($action, null,
      form(
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array(), array(html('label', array('for'=>'username'), 'username'), html('input', array('type'=>'text',     'id'=>'username', 'name'=>'username', 'value'=>'root')))),
              html('td', array(), array(html('label', array('for'=>'host'    ), 'host'    ), html('input', array('type'=>'text',     'id'=>'host',     'name'=>'host',     'value'=>'localhost')))),
              html('td', array(), array(html('label', array('for'=>'password'), 'password'), html('input', array('type'=>'password', 'id'=>'password', 'name'=>'password')))),
              html('td', array(), array('&nbsp;',                                            html('input', array('type'=>'submit',                     'name'=>'action',   'value'=>'connect', 'class'=>'button'))))
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

    login($username, $host, $password);
    internalredirect(array('action'=>'index'));
  }

  /********************************************************************************************/

  if ($action == 'logout') {
    logout();
  }

  /********************************************************************************************/

  if ($action == 'index') {
    $metabases = query('root', 'SHOW DATABASES');
    $rows = array();
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['Database'];
      $databasenames = databasenames($metabasename);
      if ($databasenames) {
        $databaselist = '';
        while ($databasename = mysql_fetch_assoc($databasenames))
          $databaselist .= ($databaselist ? ', ' : '').internalreference(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename['value']), $databasename['value']);
        $rows[] =
          html('td', array(),
            array(
              internalreference(array('action'=>'form_database_for_metabase', 'metabasename'=>$metabasename), $metabasename),
              $databaselist
            )
          );
      }
    }
    page($action, null,
      internalreference(array('action'=>'new_metabase_from_database'), 'new metabase from database').
      html('table', array(),
        html('tr', array(),
          array_concat(
            html('th', array(), array('metabase', 'database')),
            $rows
          )
        )
      ).
      internalreference(array('action'=>'show_users'), 'show users')
    );
  }

  /********************************************************************************************/

  if ($action == 'new_metabase_from_database') {
    $rows = html('tr', array(), html('th', array(), array('database', 'tables', '')));
    $databases = query('root', 'SHOW DATABASES');
    while ($database = mysql_fetch_assoc($databases)) {
      $databasename = $database['Database'];
      $tables = query('data', "SHOW TABLES FROM `$databasename`");
      $dbs = databasenames($databasename);
      $contents = '';
      if ($dbs)
        while ($db = mysql_fetch_assoc($dbs))
          $contents .= ($contents ? ', ' : '').internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$db['value'], 'metabasename'=>$databasename), $db['value']);
      elseif ($tables) {
        $tablelist = array();
        while ($table = mysql_fetch_assoc($tables)) {
          $tablelist[] = $table["Tables_in_$databasename"];
        }
        $fulllist = null;
        if (count($tablelist) > 5) {
          $fulllist = join(' ', array_slice($tablelist, 5));
          array_splice($tablelist, 5);
        }
        $contents = html('ul', array('class'=>'compact'), html('li', array(), $tablelist).($fulllist ? html('li', array('title'=>$fulllist), '&hellip') : ''));
      }
      $userprivs = $dbprivs = '';
      foreach ($privileges as $title=>$privilege) {
        $userprivs .= ($userprivs ? ', ' : '')."user.$privilege AS user_$title";
        $dbprivs   .= ($dbprivs   ? ', ' : '')."db.$privilege AS db_$title";
      }
//    $tableprivs = query('root', "SHOW GRANTS FOR '$sessionparts[1]'@'$sessionparts[2]'");
//    while ($tablepriv = mysql_fetch_assoc($tableprivs)) {
//      print_r($tablepriv);
//      echo html('br');
//    }
//    echo html('hr');
//    $tableprivs = query1('root', "SELECT $userprivs, $dbprivs FROM mysql.user LEFT JOIN mysql.db ON db.Host=user.Host AND db.User=user.User AND db.Db='$databasename' WHERE user.Host='$sessionparts[2]' AND user.User='$sessionparts[1]'");
//    print_r($tableprivs);
//    echo html('hr');
      $rows .=
        html('tr', array(),
          html('td', array(),
            array(
              internalreference(array('action'=>'form_metabase_for_database', 'databasename'=>$databasename), $databasename),
              $contents,
              grant($databasename, 'DROP') ? internalreference(array('action'=>'drop_database', 'databasename'=>$databasename), 'drop') : ''
            )
          )
        );
    }
    page($action, null,
      form(
        html('table', array(), $rows)
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
        html('p', array(),
          'Drop database '.html('strong', array(), $databasename).'?'
        ).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'drop_database_really', 'class'=>'button')).
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'cancel', 'class'=>'button cancel'))
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'drop_database_really') {
    $databasename = parameter('get', 'databasename');
    query('root', "DROP DATABASE $databasename");
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

    $fields = array();
    $alltables = array();
    $primarykeyfieldname = array();
    $tables = query('data', "SHOW TABLES FROM `$databasename`");
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table["Tables_in_$databasename"];

      $alltables[] = $tablename;

      $allprimarykeyfieldnames = array();
      $fields[$tablename] = query('data', "SHOW COLUMNS FROM `$databasename`.$tablename");
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
        $tableswithoutsinglevaluedprimarykey .= ($tableswithoutsinglevaluedprimarykey ? ', ' : '').$tablename;
    }

    $header =
      html('tr', array(),
        html('th', array(),
          array(
            'table', 'field', 'type', 'len', 'unsg', 'fill', 'null', 'auto', 'more', 'typename', 'presentation', 'key', 'desc', 'sort', 'list', 'edit'
          )
        )
      );

    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];

      $tablestructure = '';
      $desc = $sort = $list = $edit = 0;
      $inpurpose = array();
      for (mysql_data_reset($fields[$tablename]); $field = mysql_fetch_assoc($fields[$tablename]); ) {
        $fieldname = $field['Field'];

        $originals = $metabasename ? query('meta', "SELECT typename, type, typelength, typeunsigned, typezerofill, presentation, nullallowed, autoincrement, mt2.tablename AS foreigntablename FROM $metabasename.metatable AS mt LEFT JOIN $metabasename.metafield AS mf ON mf.tableid = mt.tableid LEFT JOIN $metabasename.metatype AS my ON my.typeid = mf.typeid LEFT JOIN $metabasename.metapresentation mr ON mr.presentationid = my.presentationid LEFT JOIN $metabasename.metatable AS mt2 ON mf.foreigntableid = mt2.tableid WHERE mt.tablename = '$tablename' AND fieldname = '$fieldname'") : null;
        if ($originals) {
          $original = mysql_fetch_assoc($originals);
          $type          = $original['type'];
          $typelength    = $original['typelength'];
          $typeunsigned  = $original['typeunsigned'];
          $typezerofill  = $original['typezerofill'];
          $typename      = $original['typename'];
          $presentation  = $original['presentation'];
          $nullallowed   = $original['nullallowed'];
          $autoincrement = $original['autoincrement'];
          $linkedtable   = $original['foreigntablename'];

          $typeinfo = '';
          $numeric = $type == 'int';

          $purposes = query('meta', "SELECT * FROM $metabasename.metatable AS mt LEFT JOIN $metabasename.metafield AS mf ON mf.tableid = mt.tableid LEFT JOIN $metabasename.metaelement AS me ON me.fieldid = mf.fieldid LEFT JOIN $metabasename.metapurpose AS mp ON mp.purposeid = me.purposeid WHERE mt.tablename = '$tablename' AND fieldname = '$fieldname'");
          if ($purposes)
            while ($purpose = mysql_fetch_assoc($purposes))
              $inpurpose[$purpose['purpose']] = $purpose['rank'];
        }
        else {
          $typeinfo = $field['Type'];
          list($typeinfo, $type          ) = preg_delete('/^(\w+) */',     $typeinfo);
          list($typeinfo, $typelength    ) = preg_delete('/^\((\d+)\) */', $typeinfo);
          list($typeinfo, $typeunsigned  ) = preg_delete('/(unsigned) */', $typeinfo);
          list($typeinfo, $typezerofill  ) = preg_delete('/(zerofill) */', $typeinfo);

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
                'Primarykeyfieldname'=>$primarykeyfieldname
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

          $inpurpose['desc'] = call_user_func("in_desc_$presentation") ? ++$desc : '';
          $inpurpose['sort'] = call_user_func("in_sort_$presentation") ? ++$sort : '';
          $inpurpose['list'] = call_user_func("in_list_$presentation") ? ++$list : '';
          $inpurpose['edit'] = call_user_func("in_edit_$presentation") ? ++$edit : '';
        }

        $tableoptions = '';
        foreach ($alltables as $onetable)
          $tableoptions .= html('option', array_merge(array('value'=>$onetable), $onetable == $linkedtable ? array('selected'=>'selected') : array()), $onetable);
        $tableoptions = html('option', array_merge(array('value'=>''), $linkedtable ? array() : array('selected'=>'selected')), '').$tableoptions;

        $presentationspositive = $presentationszero = array();
        foreach ($presentations as $onepresentation)
          if ($probabilities[$onepresentation])
            $presentationspositive[$onepresentation] = $probabilities[$onepresentation];
          else
            $presentationszero[] = $onepresentation;
        arsort($presentationspositive);
        sort($presentationszero);

        $positiveoptions = '';
        foreach ($presentationspositive as $onepresentation=>$probability)
          $positiveoptions .= html('option', array_merge(array('value'=>$onepresentation), $onepresentation == $presentation ? array('selected'=>'selected') : array()), $onepresentation);
        $zerooptions = '';
        foreach ($presentationszero as $onepresentation)
          $zerooptions .= html('option', array_merge(array('value'=>$onepresentation), $onepresentation == $presentation ? array('selected'=>'selected') : array()), $onepresentation);
        $presentationoptions = html('optgroup', array(), $positiveoptions).html('optgroup', array('label'=>'------------------------'), $zerooptions);

        $tablestructure .=
          html('tr', array(),
            ($tablestructure ? '' : html('td', array('class'=>'rowgroup top', 'rowspan'=>mysql_num_rows($fields[$tablename])), $tablename)).
            html('td', array('class'=>'rowgroup'),
              array(
                $fieldname.($originals ? '*' : ''),
                html('select', array('name'=>"$tablename:$fieldname:type"),
                  html('option', array_merge(array('value'=>'int'     ), $type == 'int'      ? array('selected'=>'selected') : array()), 'int'     ).
                  html('option', array_merge(array('value'=>'varchar' ), $type == 'varchar'  ? array('selected'=>'selected') : array()), 'varchar' ).
                  html('option', array_merge(array('value'=>'datetime'), $type == 'datetime' ? array('selected'=>'selected') : array()), 'datetime').
                  ($type != 'int' && $type != 'varchar' && $type != 'datetime' ? html('option', array('value'=>$type, 'selected'=>'selected'), $type) : '')
                ),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:typelength", 'value'=>$typelength)),
                $numeric ? html('input', array_merge(array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typeunsigned"), $typeunsigned ? array('checked'=>'checked') : array())) : '&nbsp;',
                $numeric ? html('input', array_merge(array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:typezerofill"), $typezerofill ? array('checked'=>'checked') : array())) : '&nbsp;',
                html('input', array_merge(array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:nullallowed"), $nullallowed ? array('checked'=>'checked') : array())),
                $numeric ? html('input', array_merge(array('type'=>'checkbox', 'class'=>'checkboxedit', 'name'=>"$tablename:$fieldname:autoincrement"), $autoincrement ? array('checked'=>'checked') : array())) : '&nbsp;',
                join(' ', array($typeinfo, $extrainfo)),
                html('input', array('type'=>'text', 'class'=>'typename', 'name'=>"$tablename:$fieldname:typename", 'value'=>$typename)),
                html('select', array('name'=>"$tablename:$fieldname:presentation", 'class'=>'presentation'), $presentationoptions),
                ($fieldname == $primarykeyfieldname[$tablename]
                ? html('input', array('type'=>'hidden', 'name'=>"$tablename:primary", 'value'=>$fieldname))
                : ($type == 'int'
                  ? html('select', array('name'=>"$tablename:$fieldname:foreigntablename"),
                      $tableoptions
                    )
                  : '&nbsp;'
                  )
                ),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:desc", 'value'=>$inpurpose['desc'])),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:sort", 'value'=>$inpurpose['sort'])),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:list", 'value'=>$inpurpose['list'])),
                html('input', array('type'=>'text', 'class'=>'integer', 'name'=>"$tablename:$fieldname:edit", 'value'=>$inpurpose['edit']))
              )
            )
          );
      }
      $totalstructure .= $header.$tablestructure;
    }

    page($action, path('&hellip;', $databasename),
      form(
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('p', array(),
          'metabasename '.html('input', array('type'=>'text', 'name'=>'metabasename', 'value'=>$metabasename ? $metabasename : (count($mbnames) == 1 ? $mbnames[0] : '')))
        ).
        html('table', array(),
          $totalstructure
        ).
        ($metabasename ? "* = from $metabasename" : '').
        html('p', array(),
          $tableswithoutsinglevaluedprimarykey
          ? html('span', array('class'=>'error'), 'no single valued primary key for table(s) '.$tableswithoutsinglevaluedprimarykey)
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
      error('no name given for the metabase');

    query('meta', "DROP DATABASE IF EXISTS $metabasename");

    query('meta', "CREATE DATABASE IF NOT EXISTS $metabasename");

    query('meta', "CREATE TABLE `$metabasename`.metaconstant (".
          "  constantid      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  constantname    VARCHAR(100) NOT NULL,".
          "  UNIQUE KEY (constantname)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metavalue (".
          "  valueid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  constantid      INT UNSIGNED NOT NULL,".
          "  value           VARCHAR(100) NOT NULL,".
          "  UNIQUE KEY (constantid, value)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metatable (".
          "  tableid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  tablename       VARCHAR(100) NOT NULL,".
          "  uniquefieldid   INT UNSIGNED NOT NULL,".
          "  UNIQUE KEY (tablename)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metafield (".
          "  fieldid         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  tableid         INT UNSIGNED NOT NULL,".
          "  fieldname       VARCHAR(100) NOT NULL,".
          "  autoincrement   INT UNSIGNED NOT NULL,".
          "  typeid          INT UNSIGNED NOT NULL,".
          "  nullallowed     INT UNSIGNED NOT NULL,".
          "  foreigntableid  INT UNSIGNED         ,".
          "  UNIQUE KEY (tableid, fieldname)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metatype (".
          "  typeid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  typename        VARCHAR(100) NOT NULL,".
          "  type            VARCHAR(100) NOT NULL,".
          "  typelength      INT UNSIGNED         ,".
          "  typeunsigned    INT UNSIGNED         ,".
          "  typezerofill    INT UNSIGNED         ,".
          "  presentationid  INT UNSIGNED         ,".
          "  UNIQUE KEY (typename)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metapresentation (".
          "  presentationid  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  presentation    VARCHAR(100) NOT NULL,".
          "  UNIQUE KEY (presentation)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metapurpose (".
          "  purposeid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  purpose         VARCHAR(100) NOT NULL,".
          "  UNIQUE KEY (purpose)".
          ")"
    );

    query('meta', "CREATE TABLE `$metabasename`.metaelement (".
          "  elementid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,".
          "  fieldid         INT UNSIGNED NOT NULL,".
          "  purposeid       INT UNSIGNED NOT NULL,".
          "  rank            INT UNSIGNED NOT NULL,".
          "  UNIQUE KEY (fieldid, purposeid)".
          // UNIQUE KEY (fieldid:tableid, purposeid, rank)
          ")"
    );

    $constantid = insertorupdate($metabasename, 'metaconstant', array('constantname'=>'database'), 'constantid');

    insertorupdate($metabasename, 'metavalue', array('constantid'=>$constantid, 'value'=>$databasename));

    $purposeids = array(
      'desc'=>insertorupdate($metabasename, 'metapurpose', array('purpose'=>'desc'), 'purposeid'),
      'sort'=>insertorupdate($metabasename, 'metapurpose', array('purpose'=>'sort'), 'purposeid'),
      'list'=>insertorupdate($metabasename, 'metapurpose', array('purpose'=>'list'), 'purposeid'),
      'edit'=>insertorupdate($metabasename, 'metapurpose', array('purpose'=>'edit'), 'purposeid')
    );

    $presentations = get_presentations();
    $presentationids = array();
    foreach ($presentations as $presentation)
      $presentationids[$presentation] = insertorupdate($metabasename, 'metapresentation', array('presentation'=>$presentation));

    $tables = query('data', "SHOW TABLES FROM `$databasename`");
    $tableids = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table["Tables_in_$databasename"];
      $tableids[$tablename] = insertorupdate($metabasename, 'metatable', array('tablename'=>$tablename), 'tableid');
    }

    $errors = array();
    for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
      $tablename = $table["Tables_in_$databasename"];
      $tableid = $tableids[$tablename];

      $descs = $sorts = $lists = $edits = 0;
      $fields = query('data', "SHOW COLUMNS FROM `$databasename`.$tablename");
      while ($field = mysql_fetch_assoc($fields)) {
        $fieldname = $field['Field'];

        $typename = parameter('get', "$tablename:$fieldname:typename");
        if (!$typeids[$typename]) {
//        print "$tablename:$fieldname:presentation" . ' - ' . parameter('get', "$tablename:$fieldname:presentation") . html('br');
          $typeids[$typename] = insertorupdate($metabasename, 'metatype', array('typename'=>$typename, 'type'=>parameter('get', "$tablename:$fieldname:type"), 'typelength'=>parameter('get', "$tablename:$fieldname:typelength"), 'typeunsigned'=>parameter('get', "$tablename:$fieldname:typeunsigned") ? 1 : 0, 'typezerofill'=>parameter('get', "$tablename:$fieldname:typezerofill") ? 1 : 0, 'presentationid'=>$presentationids[parameter('get', "$tablename:$fieldname:presentation")]), 'typeid');
        }

        $foreigntablename = parameter('get', "$tablename:$fieldname:foreigntablename");

        $fieldid = insertorupdate($metabasename, 'metafield', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'typeid'=>$typeids[$typename], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'autoincrement'=>parameter('get', "$tablename:$fieldname:autoincrement") ? 1 : 0, 'nullallowed'=>parameter('get', "$tablename:$fieldname:nullallowed") ? 1 : 0), 'fieldid');

        if (parameter('get', "$tablename:primary") == $fieldname)
          query('meta', "UPDATE `$metabasename`.metatable SET uniquefieldid = $fieldid WHERE tableid = $tableid");

        $descs += setelement($metabasename, $tablename, $fieldid, $fieldname, $purposeids, 'desc') ? 1 : 0;
        $sorts += setelement($metabasename, $tablename, $fieldid, $fieldname, $purposeids, 'sort') ? 1 : 0;
        $lists += setelement($metabasename, $tablename, $fieldid, $fieldname, $purposeids, 'list') ? 1 : 0;
        $edits += setelement($metabasename, $tablename, $fieldid, $fieldname, $purposeids, 'edit') ? 1 : 0;
      }
      if (!$descs)
        $errors[] = "no fields to desc for $tablename";
      if (!$sorts)
        $errors[] = "no fields to sort for $tablename";
      if (!$lists)
        $errors[] = "no fields to list for $tablename";
      if (!$edits)
        $errors[] = "no fields to edit for $tablename";
    }
    if ($errors)
      error(join(', ', $errors));

    internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
    page($action, path($metabasename, $databasename),
      internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), 'show_database')
    );
  }

  /********************************************************************************************/

  if ($action == 'form_metabase_to_database') {
    $metabases = query('root', 'SHOW DATABASES');
    while ($metabase = mysql_fetch_assoc($metabases)) {
      $metabasename = $metabase['Database'];
      $databasenames = databasenames($metabasename);
      if ($databasenames)
        $rows .= html('tr', array(), html('td', array(), internalreference(array('action'=>'form_database_for_metabase', 'metabasename'=>$metabasename), $metabasename)));
    }
    page($action, null,
      html('table', array(), $rows)
    );
  }


  /********************************************************************************************/

  if ($action == 'form_database_for_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $databasenames = databasenames($metabasename);
    if ($databasenames) {
      while ($databasename = mysql_fetch_assoc($databasenames))
        $rows .=
          html('tr', array(),
            html('td', array(),
              internalreference(array('action'=>'update_database_from_metabase', 'metabasename'=>$metabasename, 'databasename'=>$databasename['value']), $databasename['value'])
            )
          );
      $rows .=
        html('tr', array(),
          html('td', array(),
            array(
              html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
              html('input', array('type'=>'text', 'name'=>'databasename')).
              html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'update_database_from_metabase', 'class'=>'button'))
            )
          )
        );
    }
    page($action, path($metabasename),
      form(
        html('table', array(), $rows)
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'update_database_from_metabase') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');

    query('meta', "INSERT IGNORE INTO `$metabasename`.metavalue (constantid, value) SELECT constantid, '$databasename' FROM `$metabasename`.metaconstant WHERE constantname = 'database'");

    query('data', "CREATE DATABASE IF NOT EXISTS $databasename");

    $metatables = query('meta', "SELECT * FROM `$metabasename`.metatable mt LEFT JOIN `$metabasename`.metafield mf ON mf.fieldid = mt.uniquefieldid LEFT JOIN `$metabasename`.metatype my ON mf.typeid = my.typeid");
    while ($metatable = mysql_fetch_assoc($metatables)) {
      $totaltype = totaltype($metatable);
      query('data', "CREATE TABLE IF NOT EXISTS `$databasename`.$metatable[tablename] ($metatable[fieldname] $totaltype)");

      $associatedoldfields = array();
      $oldfields = query('data', "SHOW COLUMNS FROM `$databasename`.$metatable[tablename]");
      while ($oldfield = mysql_fetch_assoc($oldfields))
        $associatedoldfields[$oldfield['Field']] = $oldfield;

      $associatedoldindices = array();
      $oldindices = query('data', "SHOW INDEX FROM `$databasename`.$metatable[tablename]");
      while ($oldindex = mysql_fetch_assoc($oldindices))
        if ($oldindex['Seq_in_index'] == 1)
          $associatedoldindices[$oldindex['Column_name']] = $oldindex;

      $metafields = query('meta', "SELECT mt.tablename, mf.fieldid, mf.fieldname, mf.foreigntableid, mt.uniquefieldid, my.type, my.typelength, my.typeunsigned, my.typezerofill, mf.nullallowed FROM `$metabasename`.metafield mf LEFT JOIN `$metabasename`.metatable mt ON mt.tableid = mf.tableid LEFT JOIN `$metabasename`.metatype my ON mf.typeid = my.typeid WHERE mf.tableid = $metatable[tableid]");
      while ($metafield = mysql_fetch_assoc($metafields)) {
        if ($metafield['uniquefieldid'] != $metafield['fieldid']) {
          $oldfield = $associatedoldfields[$metafield['fieldname']];
          $oldtype = $oldfield['Type'].($oldfield['Null'] == 'YES' ? '' : ' not null');
          $newtype = totaltype($metafield);
          if ($oldfield) {
            if (strcasecmp($oldtype, $newtype))
              $rows .= queriesused("ALTER TABLE `$databasename`.$metafield[tablename] CHANGE COLUMN $metafield[fieldname] $metafield[fieldname] $newtype", $oldtype);
          }
          else
            $rows .= queriesused("ALTER TABLE `$databasename`.$metafield[tablename] ADD COLUMN ($metafield[fieldname] $newtype)", $oldtype);
          if ($metafield['foreigntableid'] && !$associatedoldindices[$metafield['fieldname']])
            $rows .= queriesused("ALTER TABLE `$databasename`.$metafield[tablename] ADD INDEX ($metafield[fieldname])", 'non-existent');
        }
      }
    }

    if (!$rows)
      internalredirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));

    page($action, path($metabasename, $databasename),
      html('table', array(), $rows).
      internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), 'show_database')
    );
  }

  /********************************************************************************************/

  if ($action == 'show_database') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tables = query('meta', "SELECT * FROM `$metabasename`.metatable ORDER BY tablename");
    $rows = array();
    while ($table = mysql_fetch_assoc($tables)) {
      $rows[] =
        html('td', array(),
          internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$table['tableid']), $table['tablename'])
        ).
        html('td', array('class'=>'number'),
          query1field('data', "SELECT COUNT(*) AS count FROM `$databasename`.$table[tablename]", 'count')
        );
    }
    page($action, path($metabasename, $databasename),
      html('table', array(),
        html('tr', array(),
          array_concat(
            html('th', array(), array('table', '#rows')),
            $rows
          )
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'show_table') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tableid      = parameter('get', 'tableid');
    $offset       = parameter('get', 'offset', 0);
    $orderfieldid = parameter('get', 'orderfieldid');
    list($tablename, $uniquefieldname) = tableanduniquefieldname($metabasename, $tableid);

    page($action, path($metabasename, $databasename, $tablename, $tableid),
      rows($metabasename, $databasename, $tableid, $tablename, 0, $offset, $uniquefieldname, $orderfieldid)
    );
  }

  /********************************************************************************************/

  if ($action == 'new_record' || $action == 'edit_record' || $action == 'delete_record') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tableid      = parameter('get', 'tableid');
    $uniquevalue  = parameter('get', 'uniquevalue');
    $back         = parameter('get', 'back');
    list($tablename, $uniquefieldname) = tableanduniquefieldname($metabasename, $tableid);

    $fields = fieldsforpurpose($metabasename, $tableid, array('edit'));

    $row = array();
    if (!is_null($uniquevalue))
      $row = query1('data', "SELECT * FROM `$databasename`.$tablename WHERE $uniquefieldname = '$uniquevalue'");

    get_presentations();

    $line = '';
    for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
      $value = parameter('get', "field:$field[fieldname]");
      if (!$value && $row)
        $value = $row[$field['fieldname']];
      $cell = call_user_func("formfield_$field[presentation]", $metabasename, $databasename, $field, $value, $action == 'delete_record');
      $lines .=
        html('tr', array(),
          html('td', array('class'=>'description'), html('label', array('for'=>"field:$field[fieldname]"), preg_replace('/(?<=\w)id$/i', '', $field['fieldname']))).
          html('td', array(), $cell)
        );
    }

    if (!is_null($uniquevalue)) {
      $referringfields = query('meta', "SELECT mt.tableid, tablename, mf.fieldname AS fieldname, mfu.fieldname AS uniquefieldname FROM `$metabasename`.metafield mf LEFT JOIN `$metabasename`.metatable mt ON mt.tableid = mf.tableid LEFT JOIN `$metabasename`.metafield mfu ON mt.uniquefieldid = mfu.fieldid WHERE mf.foreigntableid = $tableid");
      while ($referringfield = mysql_fetch_assoc($referringfields)) {
        $lines .=
          html('tr', array(),
            html('td', array('class'=>'description'), $referringfield['tablename']).
            html('td', array(), rows($metabasename, $databasename, $referringfield['tableid'], $referringfield['tablename'], 0, 0, $referringfield['uniquefieldname'], null, $referringfield['fieldname'], $uniquevalue, $tableid, $action != 'delete_record'))
          );
      }
    }

    page($action, path($metabasename, $databasename, $tablename, $tableid, description($metabasename, $databasename, $tableid, $row)),
      form(
        html('table', array('class'=>'tableedit'), $lines).
        html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'tableid', 'value'=>$tableid)).
        html('input', array('type'=>'hidden', 'name'=>'uniquevalue', 'value'=>$uniquevalue)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
        html('input', array('type'=>'hidden', 'name'=>'newtableid', 'value'=>'')).
        html('p', array(),
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$action == 'delete_record' ? 'delete_record_really' : ($uniquevalue ? 'update_record' : 'add_record'), 'class'=>'mainsubmit button')).
          (!$uniquevalue ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit button')) : '').
          html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'cancel', 'class'=>'button cancel'))
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'delete_record_really') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tableid      = parameter('get', 'tableid');
    $uniquevalue  = parameter('get', 'uniquevalue');
    list($tablename, $uniquefieldname) = tableanduniquefieldname($metabasename, $tableid);

    query('data', "DELETE FROM `$databasename`.$tablename WHERE $uniquefieldname = '$uniquevalue'");

    back();
  }

  /********************************************************************************************/

  if (preg_match('/^new_record_/', $action)) {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $newtableid   = parameter('get', 'newtableid');
    $parameters = parameter('get');
    $parameters['action'] = 'edit_record';

    internalredirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$newtableid, 'back'=>internalurl($parameters)));
  }

  /********************************************************************************************/

  if ($action == 'update_record' || $action == 'add_record' || $action == 'add_record_and_edit') {
    $metabasename = parameter('get', 'metabasename');
    $databasename = parameter('get', 'databasename');
    $tableid      = parameter('get', 'tableid');
    $uniquevalue  = parameter('get', 'uniquevalue');
    $back         = parameter('get', 'back');

    list($tablename, $uniquefieldname) = tableanduniquefieldname($metabasename, $tableid);

    get_presentations();

    $fieldnamesandvalues = array();
    $fields = fieldsforpurpose($metabasename, $tableid, array('edit'));
    while ($field = mysql_fetch_assoc($fields)) {
      $fieldnamesandvalues[$field['fieldname']] = call_user_func("formvalue_$field[presentation]", $field);
    }

    $uniquevalue = insertorupdate($databasename, $tablename, $fieldnamesandvalues, $uniquevalue ? $uniquefieldname : null, $uniquevalue);
    if ($action == 'add_record_and_edit')
      internalredirect(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, 'uniquevalue'=>$uniquevalue, 'back'=>$back));

    back();
  }

  /********************************************************************************************/

  if ($action == 'show_users') {
    $users = query('root', "SELECT * FROM mysql.user ORDER BY host, user");
    while ($user = mysql_fetch_assoc($users)) {
      $privcols = '';
      foreach ($privileges as $title=>$privilege)
        $privcols .= html('td', array('class'=>'center'), checkboxyn($user[$privilege]));
      $table .= html('tr', array(), html('td', array(), array($user['User'], $user['Host'])).$privcols.html('td', array(), array(internalreference(array('action'=>'edit_user', 'host'=>$user['Host'], 'username'=>$user['User']), 'edit'), internalreference(array('action'=>'delete_user', 'host'=>$user['Host'], 'username'=>$user['User']), 'delete'))));
    }
    foreach ($privileges as $title=>$privilege)
      $privheader .= html('th', array(), $title);
    page($action, null,
      internalreference(array('action'=>'edit_user'), 'new user').
      html('table', array(),
        html('tr', array(), html('th', array(), array('username', 'host')).$privheader.html('th', array(), array('', ''))).
        $table
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'edit_user') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');

    $user = $host || $username ? query1('root', "SELECT * FROM mysql.user WHERE host='$host' AND user='$username'") : array();

    foreach ($privileges as $title=>$privilege)
      $privrows .= html('tr', array(), html('td', array(), array($title, checkboxyn($user[$privilege], $title))));

    if ($host || $username) {
      $databases = query('root', "SELECT * FROM mysql.db WHERE host='$host' AND user='$username'");
      while ($database = mysql_fetch_assoc($databases)) {
        $privcols = '';
        foreach ($privileges as $title=>$privilege)
          $privcols .= html('td', array('class'=>'center'), checkboxyn($database[$privilege]));
        $table .=
          html('tr', array(),
            html('td', array(), $database['Db']).
            $privcols.
            html('td', array(), array(internalreference(array('action'=>'edit_privileges_on_db', 'host'=>$host, 'username'=>$username, 'databasename'=>$database['Db']), 'edit'), internalreference(array('action'=>'delete_privileges_on_db', 'host'=>$host, 'username'=>$username, 'databasename'=>$database['Db']), 'delete')))
          );
      }
      foreach ($privileges as $title=>$privilege)
        $privheader .= html('th', array(), $title);
    }

    page($action, null,
      form(
        html('input', array('type'=>'hidden', 'name'=>'old_username', 'value'=>$username)).
        html('input', array('type'=>'hidden', 'name'=>'old_host', 'value'=>$host)).
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array(), array('username'       , html('input', array('type'=>'text', 'name'=>'username', 'value'=>$user['User'])))),
              html('td', array(), array('host'           , html('input', array('type'=>'text', 'name'=>'host', 'value'=>$user['Host'])))),
              html('td', array(), array('new password'   , html('input', array('type'=>'password', 'name'=>'password1')))),
              html('td', array(), array('retype password', html('input', array('type'=>'password', 'name'=>'password2'))))
            )
          ).
          $privrows
        ).
        html('p', array(),
          $host || $username
          ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'update_user', 'class'=>'button'))
          : html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_user', 'class'=>'button'))
        ).
        ($host || $username
        ? internalreference(array('action'=>'edit_privileges_on_db', 'host'=>$host, 'username'=>$username), 'privileges for other database').
          ($table
          ? html('table', array(),
              html('tr', array(), html('th', array(), 'db').$privheader.html('th', array(), array('', ''))).
              $table
            )
          : ''
          )
        : ''
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'add_user') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');
    $password1 = parameter('get', 'password1');
    $password2 = parameter('get', 'password2');
    if (($password1 || $password2) && $password1 != $password2)
      error('two different passwords');

    foreach ($privileges as $title=>$privilege)
      $privsets .= ($privsets ? ', ' : '')."$privilege='".(parameter('get', $title) ? 'Y' : 'N')."'";

    query('root', "INSERT INTO mysql.user SET Host='$host', User='$username', ".($password1 ? "Password=password('$password1'), " : '').$privsets);
    query('root', "FLUSH PRIVILEGES");
    internalredirect(array('action'=>'show_users'));
  }

  /********************************************************************************************/

  if ($action == 'update_user') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');
    $oldhost     = parameter('get', 'old_host');
    $oldusername = parameter('get', 'old_username');
    $password1 = parameter('get', 'password1');
    $password2 = parameter('get', 'password2');
    if (($password1 || $password2) && $password1 != $password2)
      error('two different passwords');

    foreach ($privileges as $title=>$privilege)
      $privsets .= ($privsets ? ', ' : '')."$privilege='".(parameter('get', $title) ? 'Y' : 'N')."'";

    query('root', "UPDATE mysql.user SET ".($host != $oldhost ? "Host='$host', " : '').($username != $oldusername ? "User='$username', " : '').($password1 ? "Password=password('$password1'), " : '').$privsets." WHERE Host='$oldhost' AND User='$oldusername'");
    if ($host != $oldhost || $username != $oldusername) {
      query('root', "UPDATE mysql.db SET Host='$host', User='$username' WHERE Host='$oldhost' AND User='$oldusername'");
      query('root', "UPDATE mysql.tables_priv SET Host='$host', User='$username' WHERE Host='$oldhost' AND User='$oldusername'");
      query('root', "UPDATE mysql.columns_priv SET Host='$host', User='$username' WHERE Host='$oldhost' AND User='$oldusername'");
      query('root', "FLUSH PRIVILEGES");
    }
    query('root', "FLUSH PRIVILEGES");
    internalredirect(array('action'=>'show_users'));
  }

  /********************************************************************************************/

  if ($action == 'delete_user') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');

    query('root', "DELETE FROM mysql.user WHERE Host='$host' AND User='$username'");
    query('root', "DELETE FROM mysql.db WHERE Host='$host' AND User='$username'");
    query('root', "DELETE FROM mysql.tables_priv WHERE Host='$host' AND User='$username'");
    query('root', "DELETE FROM mysql.columns_priv WHERE Host='$host' AND User='$username'");
    query('root', "FLUSH PRIVILEGES");
    internalredirect(array('action'=>'show_users'));
  }

  /********************************************************************************************/

  if ($action == 'edit_privileges_on_db') {
    $host         = parameter('get', 'host');
    $username     = parameter('get', 'username');
    $databasename = parameter('get', 'databasename');

    $database = $databasename ? query1('root', "SELECT * FROM mysql.db WHERE Host='$host' AND User='$username' AND Db='$databasename'") : array();

    if (!$databasename) {
      $dbs = query('root', 'SHOW DATABASES');
      while ($db = mysql_fetch_assoc($dbs)) {
        $dbname = $db['Database'];
        $checkoption = $databasename == $dbname;
        $checked = $checked || $checkoption;
        $options .= html('option', array_merge(array('value'=>$dbname), $checkoption ? array('selected'=>'selected') : array()), $dbname);
      }
      if (!$checked)
        $options =
          html('option', array_merge(array('value'=>''), $checked ? array() : array('selected'=>'selected')), '').
          $options;
    }

    foreach ($privileges as $title=>$privilege)
      $privrows .= html('tr', array(), html('td', array(), array($title, checkboxyn($database[$privilege], $title))));

    page($action, null,
      form(
        html('table', array(),
          html('tr', array(),
            array(
              html('td', array(), array('host'           , $host.html('input', array('type'=>'hidden', 'name'=>'host', 'value'=>$host)))),
              html('td', array(), array('username'       , $username.html('input', array('type'=>'hidden', 'name'=>'username', 'value'=>$username)))),
              html('td', array(), array('databasename'   , $databasename ? $databasename.html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)) : html('select', array('name'=>'databasename'), $options)))
            )
          ).
          $privrows
        ).
        html('p', array(),
          $databasename
          ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'update_privileges_on_db', 'class'=>'button'))
          : html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_privileges_on_db', 'class'=>'button'))
        )
      )
    );
  }

  /********************************************************************************************/

  if ($action == 'add_privileges_on_db') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');
    $databasename = parameter('get', 'databasename');

    foreach ($privileges as $title=>$privilege)
      $privsets .= ($privsets ? ', ' : '')."$privilege='".(parameter('get', $title) ? 'Y' : 'N')."'";

    query('root', "INSERT INTO mysql.db SET Host='$host', User='$username', Db='$databasename', ".$privsets);
    query('root', "FLUSH PRIVILEGES");
    internalredirect(array('action'=>'edit_user', 'host'=>$host, 'username'=>$username, 'databasename'=>$databasename));
  }

  /********************************************************************************************/

  if ($action == 'update_privileges_on_db') {
    $host     = parameter('get', 'host');
    $username = parameter('get', 'username');
    $databasename = parameter('get', 'databasename');

    foreach ($privileges as $title=>$privilege)
      $privsets .= ($privsets ? ', ' : '')."$privilege='".(parameter('get', $title) ? 'Y' : 'N')."'";

    query('root', "UPDATE mysql.db SET ".$privsets." WHERE Host='$host' AND User='$username' AND Db='$databasename'");
    query('root', "FLUSH PRIVILEGES");
    page($action, null, '');
    internalredirect(array('action'=>'edit_user', 'host'=>$host, 'username'=>$username));
  }

  /********************************************************************************************/

  if ($action == 'delete_privileges_on_db') {
    $host         = parameter('get', 'host');
    $username     = parameter('get', 'username');
    $databasename = parameter('get', 'databasename');

    query('root', "DELETE FROM mysql.db WHERE Host='$host' AND User='$username' AND Db='$databasename'");
    query('root', "FLUSH PRIVILEGES");
    internalredirect(array('action'=>'edit_user', 'host'=>$host, 'username'=>$username));
  }

  /********************************************************************************************/

  if ($action == 'cancel') {
    back();
  }

  /********************************************************************************************/

  error('unknown action '.$action);
?>
