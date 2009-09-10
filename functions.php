<?php
  include('inflection.php');

  function parameter($type, $name = null, $default = null) {
    $arrays = array(
      'get'=>$_POST ? $_POST : $_GET,
      'server'=>$_SERVER,
      'files'=>$_FILES,
      'session'=>$_SESSION
    );
    $array = $arrays[$type];
    if (!$array)
      $array = array();
    if (!$name)
      return $array;
    $value = $array[$name];
    return is_null($value) ? (is_array($default) ? array_shift(array_filter($default, 'is_not_null')) : $default) : str_replace("\\'", "'", $value);
  }

  function is_not_null($var) {
    return !is_null($var);
  }

  function join_clean() {
    $args = func_get_args();
    switch (count($args)) {
      case 0:
        return FALSE;
      case 1:
        $glue = '';
        break;
      default:
        $glue = array_shift($args);
    }

    $pieces = array();
    foreach ($args as $arg)
      $pieces = array_merge($pieces, is_array($arg) ? $arg : array($arg));

    return join($glue, array_filter($pieces, 'is_not_null'));
  }

  function array_show($array) {
    return preg_replace(array('@^Array\s*\(\s*(.*?)\s*\)\s*$@s', '@ *\n *@s'), array('$1', "\n"), print_r($array, TRUE));
  }

  function html($tag, $parameters = array(), $text = null) {
    $parameterlist = array();
    foreach ($parameters as $parameter=>$value)
      if ($parameter && !is_null($value))
        $parameterlist[] = " $parameter=\"$value\"";
    $starttag = $tag ? '<'.$tag.join($parameterlist).(is_null($text) ? ' /' : '').'>' : '';
    $endtag = $tag ? "</$tag>" : '';
    return $starttag.(is_null($text) ? '' : (is_array($text) ? join_clean($endtag.$starttag, $text) : $text).$endtag);
  }

  function httpurl($parameters) {
    return parameter('server', 'SCRIPT_NAME').'?'.http_build_query($parameters);
  }

  function internalurl($parameters) {
    return htmlentities(httpurl($parameters));
  }

  function externalreference($url, $text) {
    return html('a', array('href'=>htmlentities($url)), $text);
  }

  function internalreference($parameters, $text, $extra = array()) {
    return html('a', array_merge($extra, array('href'=>is_array($parameters) ? internalurl($parameters) : $parameters)), $text);
  }

  function redirect($url) {
    header('Location: '.$url);
    exit;
  }

  function internalredirect($parameters) {
    redirect(httpurl($parameters));
  }

  function back() {
    $ajax = parameter('get', 'ajax');
    if ($ajax) {
      parse_str($ajax, $parameters);
      addtolist('logs', '', 'ajax_'.$parameters['function'], array_show($parameters));
      switch ($parameters['function']) {
        case 'list_table':
          $output = list_table($parameters['metabasename'], $parameters['databasename'], $parameters['tablename'], $parameters['tablenamesingular'], $parameters['limit'], $parameters['offset'], $parameters['uniquefieldname'], $parameters['orderfieldname'], $parameters['orderasc'], $parameters['foreignfieldname'], $parameters['foreignvalue'], $parameters['parenttableid'], $parameters['interactive']);
          break;
        case 'ajax_lookup':
          $output = ajax_lookup($parameters['metabasename'], $parameters['databasename'], $parameters['fieldname'], query1field('data', 'SELECT MAX(<foreignuniquefieldname>) FROM `<databasename>`.<foreigntablename>', array('foreigntablename'=>$parameters['foreigntablename'], 'foreignuniquefieldname'=>$parameters['foreignuniquefieldname'], 'databasename'=>$parameters['databasename'])), $parameters['presentationname'], $parameters['foreigntablename'], $parameters['foreigntablenamesingular'], $parameters['foreignuniquefieldname'], $parameters['nullallowed'], $parameters['readonly']);
          break;
      }
      page($parameters['function'], null, $output);
    }
    $url = parameter('get', 'back');
    redirect($url ? $url : parameter('server', 'HTTP_REFERER'));
  }

  function error($error) {
    $stack = debug_backtrace();
    $traces = array();
    foreach ($stack as $element) {
      $args = array();
      if ($element['args']) {
        foreach ($element['args'] as $arg)
          $args[] = preg_replace('@<(.*?)>@', '&lt;$1&gt;', "'$arg'");
      }
      $traces[] = html('div', array('class'=>'trace'), "$element[file]:$element[line] $element[function](".join(',', $args).")");
    }
    page('error', null,
      html('p', array('class'=>'error'), $error).
      html('p', array('class'=>'trace'), html('ol', array(), html('li', array(), $traces)))
    );
    exit;
  }

  function addtolist($list, $class, $text, $rest = null) {
    if ($_SESSION['logsy']) {
      if (!$_SESSION[$list])
        $_SESSION[$list] = array();
      $_SESSION[$list][] = html('li', array('class'=>$class), preg_replace("@\r@", '\\n', $text).$rest);
    }
  }

  function getlist($list) {
    $result = $_SESSION[$list];
    unset($_SESSION[$list]);
    return $result ? $result : array();
  }

  function query($metaordata, $query, $arguments = array()) {
    static $connection = null;
    if (!$connection) {
      if (!extension_loaded('mysql'))
        logout(_('mysql module not found'));
      $connection = @mysql_connect($_SESSION['host'], $_SESSION['username'], $_SESSION['password']);
      if (mysql_errno())
        logout(sprintf(_('problem connecting to the databasemanager: %s').array_show(debug_backtrace()), mysql_error()));
      $_SESSION['timesconnected'] += 1;
    }

    $fullquery = preg_replace(array("@'@", '@(["`])?<(\w+)>(["`])?@e'), array('"', '(is_null($arguments["$2"]) ? "NULL" : (is_numeric($arguments["$2"]) ? (int) $arguments["$2"] : "$1".mysql_escape_string($arguments["$2"])."$3"))'), $query);

    $before = microtime();
    $result = mysql_query($fullquery);
    $after = microtime();
    list($beforemsec, $beforesec) = explode(' ', $before);
    list($aftermsec, $aftersec) = explode(' ', $after);

    $errno = mysql_errno();

    $numresults = preg_match('@^[^A-Z]*(EXPLAIN|SELECT|SHOW) @i', $fullquery) && $result ? mysql_num_rows($result) : null;
    if (!is_null($numresults)) {
      $resultlist = array();
      while ($resultrow = mysql_fetch_assoc($result)) {
        if (count($resultlist) == 10 - 1 && $numresults > 10) {
          $resultlist[] = html('li', array(), "&hellip; $numresults.");
          break;
        }
        $resultlist[] = html('li', array('class'=>'resultlist'), array_show($resultrow));
      }
      mysql_data_reset($result);
    }

    $stack = debug_backtrace();
    $traces = array();
    foreach ($stack as $element) {
      $filename = preg_match1('@\/(\w+)\.php$@', $element['file']);
      $traces[] = "$filename#$element[line]&rarr;$element[function]";
    }

    addtolist('logs',
      "query$metaordata",
      html('span', array('class'=>'query'),
        preg_replace(
          array('@<@' , '@>@' , '@& @'  ),
          array('&lt;', '&gt;', '&amp; '),
          $fullquery
        ).
        ' ['.sprintf(_('%.2f sec'), ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).']'.
        ' '.html('span', array('class'=>'traces'), join(' ', array_reverse($traces)))
      ),
      ($errno
      ? html('ul', array(), html('li', array(), $errno.'='.mysql_error()))
      : (!is_null($numresults)
        ? ($resultlist
          ? html('ol', array(), join($resultlist))
          : null
          )
        : html('ul', array(), html('li', array(), sprintf(_('%d affected'), mysql_affected_rows($connection))))
        )
      )
    );
    if (preg_match('@#warning @', $fullquery))
      addtolist('warnings', 'warning', $fullquery);

    if ($result)
      return $result;
    if ($errno == 1044) // Access denied for user '%s'@'%s' to database '%s'
      return null;
    if ($errno == 1062) { // Duplicate entry '%s' for key %d
      $tablename = preg_match1('@^INSERT INTO (\S+)@', $fullquery);
      $error = mysql_error();
      $keyvalues = preg_match1('@Duplicate entry (\'.*\')@', $error);
      $keynr = preg_match1('@for key (\d+)@', $error);
      $warning = "tablename=$tablename, keyvalues=$keyvalues, keynr=$keynr, $error: $fullquery";
      if ($tablename && $keynr) {
        $keyfields = array();
        $keys = query($metaordata, 'SHOW INDEX FROM `<tablename>`', array('tablename'=>$tablename));
        while ($key = mysql_fetch_assoc($keys)) {
          if ($key['Seq_in_index'] == 1)
            $keynr--;
          if ($keynr < 0)
            break;
          if ($keynr == 0)
            $keyfields[] = $key['Column_name'];
        }
        $warning = sprintf(_('%s with %s = %s already exists'), ucfirst(preg_match1('@\.(.*)@', $tablename)), join(', ', $keyfields), $keyvalues);
      }
      addtolist('warnings', 'warning', $warning);
      return null;
    }
    error(_('problem while querying the databasemanager').html('p', array('class'=>'error'), "$errno: ".mysql_error()).$fullquery);
  }

  function query1($metaordata, $query, $arguments = array()) {
    $results = query($metaordata, $query, $arguments);
    if ($results && mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error(sprintf(_('problem retrieving 1 result, because there are %s results'), $results ? mysql_num_rows($results) : 'no').html('p', array(), $query));
  }

  function query1field($metaordata, $query, $arguments = array(), $field = null) {
    $result = query1($metaordata, $query, $arguments);
    return is_null($field) ? (count($result) == 1 ? array_shift(array_values($result)) : error(sprintf(_('problem retrieving 1 field, because there are %s fields'), count($result)))) : $result[$field];
  }

  function page($action, $path, $content) {
    $title = str_replace('_', ' ', $action);

    $error = parameter('get', 'error');

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Language: '.get_locale());
    print
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>internalurl(array('action'=>'style', 'metabasename'=>parameter('get', 'metabasename'))), 'type'=>'text/css', 'rel'=>'stylesheet')).
          ($_SESSION['ajaxy']
          ? html('script', array('type'=>'text/javascript', 'src'=>'jquery.min.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>'jquery.autogrow.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>internalurl(array('action'=>'script'))), '')
          : ''
          )
        ).
        html('body', array('class'=>preg_replace('@_@', '', $action)),
          html('div', array('id'=>'header'),
            html('h1', array('id'=>'title'), $title).
            ($_SESSION['username']
            ? html('div', array('id'=>'id'),
                join_clean(' &ndash; ',
                  get_locale(),
                  "$_SESSION[username]@$_SESSION[host]",
                  preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internalreference(parameter('server', 'REQUEST_URI').'&ajaxy='.($_SESSION['ajaxy'] ? 'off' : 'on'), $_SESSION['ajaxy'] ? _('ajax is on') : _('ajax is off')) : ($_SESSION['ajaxy'] ? _('ajax is on') : _('ajax is off')),
                  preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internalreference(parameter('server', 'REQUEST_URI').'&logsy='.($_SESSION['logsy'] ? 'off' : 'on'), $_SESSION['logsy'] ? _('logging is on') : _('logging is off')) : ($_SESSION['logsy'] ? _('logging is on') : _('logging is off')),
                  internalreference(array('action'=>'logout'), 'logout')
                )
              )
            : ''
            ).

            html('div', array('id'=>'messages'),
              $error ? html('div', array('class'=>'error'), $error) : ''
            ).
            $path
          ).
          html('div', array('id'=>'content'),
            html('ol', array('id'=>'warnings'), join(getlist('warnings'))).
            $content.
            ($_SESSION['logsy'] ? html('ol', array('class'=>'logs'), join(getlist('logs'))) : '')
          ).
          html('div', array('id'=>'footer'),
            html('div', array('id'=>'poweredby'), externalreference('http://squarebase.org/', html('img', array('src'=>'powered_by_squarebase.png'))))
          )
        )
      );
    exit;
  }

  function form($content) {
    return html('form', array('action'=>parameter('server', 'SCRIPT_NAME'), 'enctype'=>'multipart/form-data', 'method'=>'post'), $content);
  }

  function databasenames($metabasename) {
    if (mysql_num_rows(query('meta', 'SHOW TABLES FROM `<metabasename>` LIKE \'databases\'', array('metabasename'=>$metabasename))) == 0)
      return array();
    $databases = array();
    $results = query('meta', 'SELECT databasename FROM `<metabasename>`.`databases`', array('metabasename'=>$metabasename));
    while ($result = mysql_fetch_assoc($results))
      $databases[] = $result['databasename'];
    return $databases;
  }

  function all_databases() {
    return query('root', 'SHOW DATABASES WHERE `Database` != "information_schema" AND `Database` != "mysql"');
  }

  function path($metabasename, $databasename = null, $tablename = null, $uniquefieldname = null, $uniquevalue = null) {
    if (!is_null($uniquevalue)) {
      if ($metabasename && $databasename && $tablename && $uniquefieldname) {
        $descriptor = descriptor($metabasename, $databasename, $tablename, $tablename);
        $uniquepart = query1field('data', "SELECT $descriptor[select] FROM `<databasename>`.`<tablename>`$descriptor[joins] WHERE <uniquefieldname> = <uniquevalue>", array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
      }
      else
        $uniquepart = $uniquevalue;
    }
    else
      $uniquepart = null;
    return html('h2', array(),
      join_clean(' - ',
        !is_null($metabasename) ? $metabasename : '&hellip;',
        !is_null($databasename) ? ($metabasename ? internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename) : $databasename) : null,
        !is_null($tablename)    ? ($metabasename && $databasename && $uniquefieldname ? internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename), $tablename) : $tablename) : null,
        $uniquepart
      )
    );
  }

  function list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, $orderfieldname, $orderasc = TRUE, $foreignfieldname = null, $foreignvalue = null, $parenttablename = null, $interactive = TRUE) {
    $originalorderfieldname = $orderfieldname;
    $joins = $selectnames = $ordernames = array();
    $header = array(html('th', array('class'=>'small'), ''));
    $fields = fieldsforpurpose($metabasename, $tablename, 'inlist');
    while ($field = mysql_fetch_assoc($fields)) {
      $selectnames[] = "$tablename.$field[fieldname] AS ${tablename}_$field[fieldname]";
      if ($field['foreigntablename']) {
        $joins[] = " LEFT JOIN `$databasename`.$field[foreigntablename] AS $field[foreigntablename]_$field[fieldname] ON $field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname] = $tablename.$field[fieldname]";
        $descriptor = descriptor($metabasename, $databasename, $field['foreigntablename'], "$field[foreigntablename]_$field[fieldname]");
        $selectnames[] = $descriptor['select']." AS $field[foreigntablename]_$field[fieldname]_descriptor";
        $joins[] = $descriptor['joins'];
        $ordernames[] = "$field[foreigntablename]_$field[fieldname]_descriptor";
      }
      else
        $ordernames[] = "${tablename}_$field[fieldname]";
      if ($field['fieldname'] == $orderfieldname)
        array_unshift($ordernames, array_pop($ordernames));
      if (!$orderfieldname)
        $orderfieldname = $field['fieldname'];

      include_once("presentation/$field[presentationname].php");
      $header[] =
        html('th', array('class'=>join_clean(' ', $field['presentationname'], !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? 'thisrecord' : null)),
          !is_null($foreignvalue) || !call_user_func("is_sortable_$field[presentationname]")
          ? $field['title']
          : internalreference(
              array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$field['fieldname'], 'orderasc'=>$field['fieldname'] == $orderfieldname ? ($orderasc ? '' : 'on') : 'on'),
              $field['title'].($field['fieldname'] == $orderfieldname ? ' '.($orderasc ? '&#x25be;' : '&#x25b4;') : ''),
              array('class'=>'ajaxreload')
            )
        );
    }
    if ($ordernames)
      $ordernames[0] = $ordernames[0].' '.($orderasc ? 'ASC' : 'DESC');
    $records = query('data',
      "SELECT ".
      ($limit ? "SQL_CALC_FOUND_ROWS " : "").
      "$tablename.$uniquefieldname AS $uniquefieldname".
      ($selectnames ? ', '.join(', ', $selectnames) : '').
      " FROM `$databasename`.$tablename".
      join(array_unique($joins)).
      (!is_null($foreignvalue) ? " WHERE $tablename.$foreignfieldname = '$foreignvalue'" : '').
      ($ordernames ? " ORDER BY ".join(', ', $ordernames) : '').
      ($limit ? " LIMIT $limit".($offset ? " OFFSET $offset" : '') : '')
    );
    $foundrecords = $limit ? query1('data', 'SELECT FOUND_ROWS() AS number') : null;

    $rows = array(html('tr', array(), join($header)));
    while ($row = mysql_fetch_assoc($records)) {
      $columns = array();
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        $field['descriptor'] = $row["$field[foreigntablename]_$field[fieldname]_descriptor"];
        $field['thisrecord'] = !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname;
        $field['uniquefieldname'] = $uniquefieldname;
        $field['uniquevalue'] = $row[$uniquefieldname];
        $columns[] =
          html('td', array('class'=>join_clean(' ', 'column', $field['presentationname'], $field['thisrecord'] ? 'thisrecord' : null)),
            call_user_func("list_$field[presentationname]", $metabasename, $databasename, $field, $row["${tablename}_$field[fieldname]"])
          );
      }
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          ($interactive ? html('td', array('class'=>'small'), internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), 'edit')) : '').
          join($columns)
        );
    }

    $offsets = array();
    if ($limit > 0 && $foundrecords['number'] > $limit) {
      for ($otheroffset = 0; $otheroffset < $foundrecords['number']; $otheroffset += $limit) {
        $lastrecord = min($otheroffset + $limit, $foundrecords['number']);
        $text = ($otheroffset + 1).($otheroffset + 1 == $lastrecord ? '' : '-'.$lastrecord);
        $offsets[] = $offset == $otheroffset ? $text : internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'offset'=>$otheroffset, 'orderfieldname'=>$originalorderfieldname, 'orderasc'=>$orderasc), $text);
      }
    }

    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'list_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'limit'=>$limit, 'offset'=>$offset, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$orderfieldname, 'orderasc'=>$orderasc ? 'on' : '', 'foreignfieldname'=>$foreignfieldname, 'foreignvalue'=>$foreignvalue, 'parenttablename'=>$parenttablename, 'interactive'=>$interactive))),
        ($interactive
        ? html('div', array(),
            internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), sprintf(_('new %s'), $tablenamesingular)).
            (is_null($foreignvalue) ? '' : html('span', array('class'=>'changeslost'), _('(changes to form fields are lost)')))
          )
        : (is_null($foreignvalue) ? '' : $tablename)
        ).
        (count($rows) > 1 ? html('table', array('class'=>'tablelist'), join($rows)) : '').
        join(' ', $offsets).
        (is_null($foreignvalue) ? internalreference(parameter('server', 'HTTP_REFERER'), 'close', array('class'=>'close')) : '')
      );
  }

  function insertorupdate($databasename, $tablename, $fieldnamesandvalues, $uniquefieldname = null, $uniquevalue = null) {
    $sets = $arguments = array();
    foreach ($fieldnamesandvalues as $fieldname=>$fieldvalue) {
      $sets[] = "<_name_$fieldname> = '<_value_$fieldname>'";
      $arguments["_name_$fieldname"] = $fieldname;
      $arguments["_value_$fieldname"] = $fieldvalue;
    }
    query('data',
      $uniquefieldname && !is_null($uniquevalue)
      ? "UPDATE `<databasename>`.`<tablename>` SET ".join(', ', $sets)." WHERE <uniquefieldname> = '<uniquevalue>'"
      : "INSERT INTO `<databasename>`.`<tablename>` SET ".join(', ', $sets),
      array_merge($arguments, array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue))
    );
    return !is_null($uniquevalue) ? $uniquevalue : mysql_insert_id();
  }

  function preg_match1($pattern, $subject, $default = null) {
    return preg_match($pattern, $subject, $matches) ? (count($matches) == 2 ? $matches[1] : $matches[0]) : $default;
  }

  function preg_delete($pattern, $subject) {
    if (!preg_match($pattern, $subject, $matches))
      return array($subject, null);
    return array(preg_replace($pattern, '', $subject), $matches[1]);
  }

  function mysql_data_reset($results) {
    if (mysql_num_rows($results) > 0)
      mysql_data_seek($results, 0);
  }

  function fieldsforpurpose($metabasename, $tablename, $purpose) {
    return query('meta',
      "SELECT mt.tablename, mt.singular, mt.plural, mt.tableid, mf.fieldid, mf.fieldname, mf.title, mr.presentationname, mf.foreigntableid, mf.nullallowed, mf.indesc, mf.inlist, mf.inedit, mt2.tablename AS foreigntablename, mt2.singular AS foreigntablenamesingular, mf2.fieldname AS foreignuniquefieldname ".
      "FROM `<metabasename>`.tables mt ".
      "RIGHT JOIN `<metabasename>`.fields mf ON mf.tableid = mt.tableid ".
      "LEFT JOIN `<metabasename>`.presentations mr ON mr.presentationid = mf.presentationid ".
      "LEFT JOIN `<metabasename>`.tables mt2 ON mt2.tableid = mf.foreigntableid ".
      "LEFT JOIN `<metabasename>`.fields mf2 ON mf2.fieldid = mt2.uniquefieldid ".
      "WHERE mt.tablename = '<tablename>' AND mf.<purpose> ".
      "ORDER BY mf.fieldid",
      array('metabasename'=>$metabasename, 'tablename'=>$tablename, 'purpose'=>$purpose)
    );
  }

  function descriptor($metabasename, $databasename, $tablename, $tablealias, $stack = array()) {
    static $descriptors = array();
    if (!$descriptors[$tablename]) {
      $arguments = $joins = array();
      $fields = fieldsforpurpose($metabasename, $tablename, 'indesc');
      while ($field = mysql_fetch_assoc($fields)) {
        $selectnames[] = "$tablename.$field[fieldname] AS ${tablename}_$field[fieldname]";
        if ($field['foreigntablename'] && !in_array($field['foreigntablename'], $stack)) {
          $joins[] = " LEFT JOIN `$databasename`.$field[foreigntablename] AS {tablealias}_$field[foreigntablename]_$field[fieldname] ON {tablealias}_$field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname] = {tablealias}.$field[fieldname]";
          $descriptor = descriptor($metabasename, $databasename, $field['foreigntablename'], "{tablealias}_$field[foreigntablename]_$field[fieldname]", array_merge($stack, array($field['foreigntablename'])));
          $arguments[] = $descriptor['select'];
          $joins[] = $descriptor['joins'];
        }
        else
          $arguments[] = "{tablealias}.$field[fieldname]";
      }
      $descriptors[$tablename] = array(
        'select'=>count($arguments) == 1 ? $arguments[0] : 'CONCAT_WS(" ", '.join(', ', $arguments).')',
        'joins' =>$joins ? join($joins) : null
      );
    }
    return array(
      'select'=>preg_replace('@{tablealias}@', $tablealias, $descriptors[$tablename]['select']),
      'joins' =>preg_replace('@{tablealias}@', $tablealias, $descriptors[$tablename]['joins'])
    );
  }

  function login($username, $host, $password, $language) {
    $_SESSION['username'] = $username;
    $_SESSION['host']     = $host;
    $_SESSION['password'] = $password;
    $_SESSION['language'] = $language;

    $expire = time() + 365 * 24 * 60 * 60;
    setcookie('lastusername', $username, $expire);
    setcookie('lasthost', $host, $expire);
  }

  function logout($error = null) {
    $_SESSION = array();
    session_destroy();
    internalredirect(array('action'=>'login', 'error'=>$error));
  }

  function get_presentationnames() {
    static $presentationnames = array();
    if ($presentationnames)
      return $presentationnames;
    $dir = opendir('presentation');
    while ($file = readdir($dir)) {
      if (preg_match('@^(.*)\.php$@', $file, $matches)) {
        $presentationnames[] = $matches[1];
        include_once("presentation/$file");
      }
    }
    closedir($dir);
    sort($presentationnames);
    return $presentationnames;
  }

  function read_file($filename, $flags = null) {
    $content = @file($filename, $flags);
    return $content === FALSE ? array() : $content;
  }

  function augment_file($filename, $content_type) {
    $content = join(read_file($filename));

    if (preg_match_all('@// *(\w+)_presentation\b@', $content, $function_prefixes, PREG_SET_ORDER)) {
      $presentationnames = get_presentationnames();
      foreach ($function_prefixes as $function_prefix) {
        $extra = array();
        foreach ($presentationnames as $presentationname)
          $extra[] = @call_user_func("$function_prefix[1]_$presentationname");

        $content = preg_replace("@( *)// *$function_prefix[1]_presentation\b.*\n@e", '"$1".preg_replace("@\n(?=.)@", "\n$1", join($extra))', $content);
      }
    }

    if (parameter('get', 'metabasename'))
      $content .= join(read_file('metabase/'.parameter('get', 'metabasename').'.css'));

    header("Content-Type: $content_type");
    print $content;
    exit;
  }

  function change_datetime_format($value, $from, $to) {
    if (!$value)
      return $value;
    $matches = strptime($value, $from);
    return strftime($to, mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'] + 1, $matches['tm_mday'], $matches['tm_year']));
  }

  function find_datetime_format($format) {
    $fmts = array('d'=>_('dd'), 'e'=>_('d'), 'b'=>_('mon'), 'B'=>_('month'), 'm'=>_('mm'), 'y'=>_('yy'), 'Y'=>_('yyyy'), 'H'=>_('hh'), 'I'=>_('hh'), 'l'=>_('hh'), 'M'=>_('mm'), 'p'=>_('AM/PM'), 'P'=>_('am/pm'), 'S'=>_('ss'));
    $matches = array('tm_hour'=>23, 'tm_min'=>34, 'tm_sec'=>45, 'tm_mon'=>4, 'tm_mday'=>2, 'tm_year'=>2003);
    $date = mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'], $matches['tm_mday'], $matches['tm_year']);
    $output = strftime($format, $date);
    foreach ($fmts as $fmt=>$representation) {
      $result = strftime("%$fmt", $date);
      if ($result)
        $output = preg_replace("@\b$result\b@", $representation, $output);
    }
    return $output;
  }

  function bare($text) {
    return preg_replace('@[^a-z0-9\._]@', '', strtolower($text));
  }

  function explode_with_priority($text) {
    $exploded = array();
    $parts = explode(',', $text);
    foreach ($parts as $part) {
      if (preg_match('@([^;]+)(?:;q=(\d*(\.\d*)?))?@i', $part, $matches)) {
        $id = bare($matches[1]);
        $value = (float) (isset($matches[2]) ? $matches[2] : 1);
        $exploded[$id] = max($exploded[$id], $value);
      }
    }
    arsort($exploded);
    return $exploded;
  }

  function set_best_locale($accepted_languages, $accepted_charsets) {
    //$accepted_* is of the form (<id>(;q=<number>))*
    $wanted_languages = explode_with_priority(preg_replace('@-@', '_', $accepted_languages));
    $wanted_charsets = explode_with_priority($accepted_charsets);

    $wanted_locales = array();
    $system_locales = get_system_locales();
    foreach ($system_locales as $system_locale) {
      $matches = explode('.', $system_locale);
      $language = bare($matches[0]);
      $charset = bare($matches[1]);
      $general_language = preg_replace('@_[a-z]*@', '', $language);
      $wanted_locales[$system_locale] =
        10 * (array_key_exists($language, $wanted_languages) ? 1 + $wanted_languages[$language] : (array_key_exists($general_language, $wanted_languages) ? 1 + $wanted_languages[$general_language] : 0)) +
         1 * (array_key_exists($charset, $wanted_charsets) ? 1 + $wanted_charsets[$charset] : 0);
    }
    arsort($wanted_locales);
    $wanted_locales = array_keys($wanted_locales);

    $best_locale = setlocale(LC_ALL, $wanted_locales);
  }

  function get_locale() {
    return setlocale(LC_ALL, 0);
  }

  function get_system_locales() {
    exec('locale -a', $system_locales);
    return $system_locales;
  }

  function select_locale($name = 'language') {
    $current_locale = get_locale();

    $localeoptions = array();
    $system_locales = get_system_locales();
    foreach ($system_locales as $locale)
      $localeoptions[] = html('option', array('value'=>$locale, 'selected'=>$locale == $current_locale ? 'selected' : null), $locale);

    return html('select', array('id'=>$name, 'name'=>$name), join($localeoptions));
  }

  function all_grants() {
    static $grants = null;
    if (is_null($grants))
      $grants = query('top', 'SHOW GRANTS');
    return $grants;
  }

  function has_grant($privilege, $databasename, $tablename = '*') {
    //for privilege see http://dev.mysql.com/doc/refman/5.0/en/privileges-provided.html
    //$databasename == '*' means privilege on all databases
    //$databasename == '?' means privilege on at least one database
    $grants = all_grants();
    for (mysql_data_reset($grants); $grant = mysql_fetch_assoc($grants); ) {
      if (
          preg_match("/^GRANT (.*?) ON `?(.*?)`?\.`?(.*?)`? /", $grant["Grants for $_SESSION[username]@$_SESSION[host]"], $matches) &&
          preg_match("/(^ALL PRIVILEGES$|\b$privilege\b)/", $matches[1]) &&
          ($matches[2] == '*' || $databasename == '?' || $matches[2] == $databasename) &&
          ($matches[3] == '*' || $tablename == '?' || $matches[3] == $tablename)
         )
        return TRUE;
    }
    return FALSE;
  }

  function databases_with_grant($privilege) {
    //for privilege see http://dev.mysql.com/doc/refman/5.0/en/privileges-provided.html
    $databases = array();
    $grants = all_grants();
    for (mysql_data_reset($grants); $grant = mysql_fetch_assoc($grants); ) {
      if (
          preg_match("/^GRANT (.*?) ON `?(.*?)`?\.\* /", $grant["Grants for $_SESSION[username]@$_SESSION[host]"], $matches) &&
          preg_match("/(^ALL PRIVILEGES$|\b$privilege\b)/", $matches[1]) 
         )
        $databases[] = $matches[2];
    }
    return $databases;
  }
?>
