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
        return false;
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
    return preg_replace(array('@^Array\s*\(\s*(.*?)\s*\)\s*$@s', '@ *\n *@s'), array('$1', "\n"), print_r($array, true));
  }

  function html($tag, $parameters = array(), $text = null) {
    if ($_SESSION['logsy']) {
      static $types = array(
        'html'=>1, 'head'=>1, 'title'=>1, 'script'=>1, 'body'=>1, 'div'=>1, 'span'=>1, 'p'=>1, 'h1'=>1, 'h2'=>1, 'ol'=>1, 'ul'=>1, 'li'=>1, 'a'=>1, 'table'=>1, 'tr'=>1, 'th'=>1, 'td'=>1, 'form'=>1, 'optgroup'=>1, 'label'=>1, 'select'=>1, 'option'=>1, 'textarea'=>1, 'strong'=>1,
        'link'=>0, 'img'=>0, 'input'=>0
      );
      $type = $types[$tag];
      if ($type === 1) {
        if (is_null($text))
          $warning = _('missing text for html tag %s');
      }
      elseif ($type === 0) {
        if (!is_null($text))
          $warning = _('text for html tag %s');
      }
      else
        $warning = _('unknown html tag %s');
      if ($warning) {
        addtolist('warnings', 'warning', sprintf($warning, $tag));
        $parameters['class'] = join(' ', array_merge(explode(' ', $parameters['class']), array('tagwarning')));
      }
    }
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
      addtolist('logs', 'ajax', 'ajax: '.html('div', array('class'=>'arrayshow'), array_show($parameters)));
      switch ($parameters['function']) {
        case 'list_table':
          $output = list_table($parameters['metabasename'], $parameters['databasename'], $parameters['tablename'], $parameters['tablenamesingular'], $parameters['limit'], $parameters['offset'], $parameters['uniquefieldname'], $parameters['uniquevalue'], $parameters['orderfieldname'], $parameters['orderasc'], $parameters['foreignfieldname'], $parameters['foreignvalue'], $parameters['parenttableid'], $parameters['interactive']);
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

  function addtolist($list, $class, $text) {
    if ($_SESSION['logsy']) {
      if (!$_SESSION[$list])
        $_SESSION[$list] = array();
      $_SESSION[$list][] = html('li', array('class'=>$class), $text);
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

    if (preg_match('@= *\'<\w+>\'@', $query))
      addtolist('warnings', 'warning', sprintf(_('wrong single quotes around value in query: %s'), $query));

    $fullquery = preg_replace('@(["`])?<(\w+)>(["`])?@e', '(is_null($arguments["$2"]) ? "NULL" : (is_numeric($arguments["$2"]) ? (int) $arguments["$2"] : "$1".mysql_escape_string($arguments["$2"])."$3"))', $query);

    $before = microtime();
    $result = mysql_query($fullquery);
    $after = microtime();
    list($beforemsec, $beforesec) = explode(' ', $before);
    list($aftermsec, $aftersec) = explode(' ', $after);

    $errno = mysql_errno();

    $sqlcommand = preg_match1('@^[^A-Z]*([A-Z]+) @i', $fullquery);
    $numresults = preg_match('@^(EXPLAIN|SELECT|SHOW)$@i', $sqlcommand) && $result ? mysql_num_rows($result) : null;
    if (!is_null($numresults)) {
      $resultlist = array();
      while ($resultrow = mysql_fetch_assoc($result)) {
        if (count($resultlist) == 10 - 1 && $numresults > 10) {
          $resultlist[] = html('li', array(), "&hellip; $numresults.");
          break;
        }
        $resultlist[] = html('li', array('class'=>'arrayshow'), array_show($resultrow));
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
      html('div', array('class'=>'query'),
        preg_replace(
          array('@<@' , '@>@' , '@& @'  ),
          array('&lt;', '&gt;', '&amp; '),
          $fullquery
        ).
        ' ['.sprintf(_('%.2f sec'), ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).']'.
        ' '.internalreference(array('action'=>'explain_query', 'query'=>$fullquery), _('explain')).
        ' '.html('span', array('class'=>'traces'), join(' ', array_reverse($traces)))
      ).
      ($errno
      ? html('ul', array(), html('li', array(), $errno.'='.mysql_error()))
      : (!is_null($numresults)
        ? ($resultlist
          ? html('ol', array(), join($resultlist))
          : null
          )
        : html('ul', array(), html('li', array(), sprintf($sqlcommand == 'INSERT' ? _('%d inserted') : ($sqlcommand == 'UPDATE' ? _('%d updated') : _('%d affected')), mysql_affected_rows($connection))))
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
        $databasename = preg_match1('@^INSERT INTO `(.*?)`@', $fullquery);
        $tablename    = preg_match1('@^INSERT INTO `.*?`\.`(.*?)`@', $fullquery);
        $keyfields = array();
        $keys = query($metaordata, 'SELECT seq_in_index, column_name FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = "<databasename>" AND table_name = "<tablename>"', array('databasename'=>$databasename, 'tablename'=>$tablename));
        while ($key = mysql_fetch_assoc($keys)) {
          if ($key['seq_in_index'] == 1)
            $keynr--;
          if ($keynr < 0)
            break;
          if ($keynr == 0)
            $keyfields[] = $key['column_name'];
        }

        $warning = sprintf(_('%s with %s = %s already exists'), ucfirst($tablename), join(', ', $keyfields), $keyvalues);
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
    error(sprintf(_('problem retrieving 1 result, because there are %s results'), $results ? mysql_num_rows($results) : 'no').html('p', array(), htmlentities($query)));
  }

  function query1field($metaordata, $query, $arguments = array(), $field = null) {
    $result = query1($metaordata, $query, $arguments);
    return is_null($field) ? (count($result) == 1 ? array_shift(array_values($result)) : error(sprintf(_('problem retrieving 1 field, because there are %s fields'), count($result)))) : $result[$field];
  }

  function ajaxcontent($content) {
    return html('div', array('class'=>'ajaxcontent'), html('div', array('class'=>'ajaxcontainer'), $content));
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
            html('div', array('id'=>'poweredby'), externalreference('http://squarebase.org/', html('img', array('src'=>'powered_by_squarebase.png', 'alt'=>'powered by squarebase'))))
          )
        )
      );
    exit;
  }

  function form($content) {
    return html('form', array('action'=>parameter('server', 'SCRIPT_NAME'), 'enctype'=>'multipart/form-data', 'method'=>'post'), $content);
  }

  function databasenames($metabasename) {
    if (mysql_num_rows(query('meta', 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "<metabasename>" AND table_name LIKE "databases"', array('metabasename'=>$metabasename))) == 0)
      return array();
    $databases = array();
    $results = query('meta', 'SELECT databasename FROM `<metabasename>`.`databases`', array('metabasename'=>$metabasename));
    while ($result = mysql_fetch_assoc($results))
      $databases[] = $result['databasename'];
    return $databases;
  }

  function all_databases() {
    return query('root', 'SELECT schema_name FROM INFORMATION_SCHEMA.SCHEMATA WHERE schema_name NOT IN ("information_schema", "mysql")');
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

  function list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, $uniquevalue, $orderfieldname, $orderasc = true, $foreignfieldname = null, $foreignvalue = null, $parenttablename = null, $interactive = true) {
    $originalorderfieldname = $orderfieldname;
    $joins = $selectnames = $ordernames = array();
    $can_insert = $can_update = false;
    $header = $quickadd = array();
    $fields = fieldsforpurpose($metabasename, $databasename, $tablename, 'inlist', 'SELECT', true);
    while ($field = mysql_fetch_assoc($fields)) {
      $can_insert = $can_insert || $field['privilege_insert'];
      $can_update = $can_update || $field['privilege_update'];
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
              array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$field['fieldname'], 'orderasc'=>$field['fieldname'] == $orderfieldname ? ($orderasc ? '' : 'on') : 'on'),
              $field['title'].($field['fieldname'] == $orderfieldname ? ' '.($orderasc ? '&#x25be;' : '&#x25b4;') : ''),
              array('class'=>'ajaxreload')
            )
        );
      $quickadd[] = html('td', array('class'=>!is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? 'thisrecord' : null), call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, array_merge($field, array('uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)), !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? $foreignvalue : null, (!is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname) || !$field['privilege_insert'], false));
    }
    if ($can_update)
      array_unshift($header, html('th', array('class'=>'small'), ''));
    if ($can_insert)
      array_unshift($quickadd, html('td', array('class'=>'small'), ''));
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
    $foundrecords = $limit ? query1field('data', 'SELECT FOUND_ROWS()') : null;

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
            ''.call_user_func("list_$field[presentationname]", $metabasename, $databasename, $field, $row["${tablename}_$field[fieldname]"])
          );
      }
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          ($interactive
          ? ($can_update
            ? html('td', array('class'=>'small'), internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), 'edit'))
            : ''
            )
          : ''
          ).
          join($columns)
        );
    }
    if ($interactive)
      $rows[] =
        html('tr', array(), join($quickadd)).
        html('tr', array(),
          $quickadd[0].
          html('td', array('colspan'=>count($quickadd) - 1),
            html('div', array(), 
              html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record', 'class'=>'mainsubmit')).
              html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit')).
              internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('full record')).
              (is_null($foreignvalue) ? '' : html('span', array('class'=>'changeslost'), _('(changes to form fields are lost)')))
            ).
            (is_null($uniquevalue) ? '' : ajaxcontent(edit_record('UPDATE', $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue)))
          )
        );

    $offsets = array();
    if ($limit > 0 && $foundrecords > $limit) {
      for ($otheroffset = 0; $otheroffset < $foundrecords; $otheroffset += $limit) {
        $lastrecord = min($otheroffset + $limit, $foundrecords);
        $text = ($otheroffset + 1).($otheroffset + 1 == $lastrecord ? '' : '-'.$lastrecord);
        $offsets[] = $offset == $otheroffset ? $text : internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'offset'=>$otheroffset, 'orderfieldname'=>$originalorderfieldname, 'orderasc'=>$orderasc), $text);
      }
    }

    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'list_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'limit'=>$limit, 'offset'=>$offset, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$orderfieldname, 'orderasc'=>$orderasc ? 'on' : '', 'foreignfieldname'=>$foreignfieldname, 'foreignvalue'=>$foreignvalue, 'parenttablename'=>$parenttablename, 'interactive'=>$interactive))),
        (count($rows) > 1
        ? form(
            html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
            html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
            html('input', array('type'=>'hidden', 'name'=>'tablename', 'value'=>$tablename)).
            html('input', array('type'=>'hidden', 'name'=>'tablenamesingular', 'value'=>$tablenamesingular)).
            html('input', array('type'=>'hidden', 'name'=>'uniquefieldname', 'value'=>$uniquefieldname)).
            html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
            html('table', array('class'=>'tablelist'), join($rows)) 
          )
        : ''
        ).
        join(' ', $offsets).
        (is_null($foreignvalue) ? internalreference(parameter('server', 'HTTP_REFERER'), 'close', array('class'=>'close')) : '')
      );
  }

  function edit_record($privilege, $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue, $back = null) {
    $fields = fieldsforpurpose($metabasename, $databasename, $tablename, 'inedit', $privilege, true);

    if (!is_null($uniquevalue)) {
      $fieldnames = array();
      while ($field = mysql_fetch_assoc($fields))
        $fieldnames[] = $field['fieldname'];
      $row = query1('data', 'SELECT '.join(', ', $fieldnames).' FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
    }

    get_presentationnames();

    $lines = array(
      html('th', array('colspan'=>2), $tablenamesingular)
    );
    for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
      $lines[] =
        html('td', array('class'=>'description'), html('label', array('for'=>"field:$field[fieldname]"), $field['title'])).
        html('td', array(), call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, array_merge($field, array('uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)), is_null($uniquevalue) ? parameter('get', "field:$field[fieldname]") : $row[$field['fieldname']], $privilege == 'SELECT' || ($privilege == 'INSERT' && !$field['privilege_insert']) || ($privilege == 'UPDATE' && !$field['privilege_update']), true));
    }

    $lines[] =
      html('td', array('class'=>'description'), '').
      html('td', array(),
        html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$privilege == 'SELECT' ? 'delete_record' : ($privilege == 'UPDATE' ? 'update_record' : 'add_record'), 'class'=>'mainsubmit')).
        (is_null($uniquevalue) ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit')) : '').
        internalreference($back ? $back : parameter('server', 'HTTP_REFERER'), 'cancel', array('class'=>'cancel')).
        ($privilege == 'UPDATE' && has_grant('DELETE', $databasename, $tablename) ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'delete_record', 'class'=>join_clean(' ', 'mainsubmit', 'delete'))) : '')
      );

    if (!is_null($uniquevalue)) {
      $linesreferrers = array();
      $referringfields = query('meta', 'SELECT mt.tablename, mt.singular, mf.fieldname AS fieldname, mf.title AS title, mfu.fieldname AS uniquefieldname FROM `<metabasename>`.fields mf LEFT JOIN `<metabasename>`.tables mtf ON mtf.tableid = mf.foreigntableid LEFT JOIN `<metabasename>`.tables mt ON mt.tableid = mf.tableid LEFT JOIN `<metabasename>`.fields mfu ON mt.uniquefieldid = mfu.fieldid WHERE mtf.tablename = "<tablename>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename));
      while ($referringfield = mysql_fetch_assoc($referringfields)) {
        $linesreferrers[] =
          html('td', array('class'=>'description'), 
            $referringfield['tablename'].
            ($referringfield['title'] == $tablenamesingular ? '' : html('div', array('class'=>'referrer'), sprintf(_('via %s'), $referringfield['title'])))
          ).
          html('td', array(), list_table($metabasename, $databasename, $referringfield['tablename'], $referringfield['singular'], 0, 0, $referringfield['uniquefieldname'], null, null, true, $referringfield['fieldname'], $uniquevalue, $tablename, $privilege != 'SELECT'));
      }
    }

    return
      form(
        html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
        html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
        html('input', array('type'=>'hidden', 'name'=>'tablename', 'value'=>$tablename)).
        html('input', array('type'=>'hidden', 'name'=>'tablenamesingular', 'value'=>$tablenamesingular)).
        html('input', array('type'=>'hidden', 'name'=>'uniquefieldname', 'value'=>$uniquefieldname)).
        html('input', array('type'=>'hidden', 'name'=>'uniquevalue', 'value'=>$uniquevalue)).
        html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
        html('table', array('class'=>'tableedit'), html('tr', array(), $lines))
      ).
      ($linesreferrers ? html('table', array('class'=>'tablereferrers'), html('tr', array(), $linesreferrers)) : '');
  }

  function insertorupdate($databasename, $tablename, $fieldnamesandvalues, $uniquefieldname = null, $uniquevalue = null) {
    $sets = $arguments = array();
    foreach ($fieldnamesandvalues as $fieldname=>$fieldvalue) {
      $sets[] = "<_name_$fieldname> = \"<_value_$fieldname>\"";
      $arguments["_name_$fieldname"] = $fieldname;
      $arguments["_value_$fieldname"] = $fieldvalue;
    }
    query('data',
      $uniquefieldname && !is_null($uniquevalue)
      ? "UPDATE `<databasename>`.`<tablename>` SET ".join(', ', $sets)." WHERE <uniquefieldname> = \"<uniquevalue>\""
      : "INSERT INTO `<databasename>`.`<tablename>` SET ".join(', ', $sets),
      array_merge($arguments, array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue))
    );
    return $uniquefieldname && !is_null($uniquevalue) ? $uniquevalue : mysql_insert_id();
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

  function fieldsforpurpose($metabasename, $databasename, $tablename, $purpose, $privilege = 'SELECT', $allprivileges = false) {
    $selectparts = $joinparts = array();
    foreach (array('SELECT', 'INSERT', 'UPDATE') as $oneprivilege) {
      if ($allprivileges || $privilege == $oneprivilege) {
        $letter = strtolower($oneprivilege{0});
        $selectparts[] = "COALESCE(u$letter.privilege_type, s$letter.privilege_type, t$letter.privilege_type, c$letter.privilege_type) AS privilege_".strtolower($oneprivilege);
        $joinparts[] = 
          "LEFT JOIN INFORMATION_SCHEMA.USER_PRIVILEGES   u$letter ON u$letter.privilege_type = \"$oneprivilege\" AND u$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") ".
          "LEFT JOIN INFORMATION_SCHEMA.SCHEMA_PRIVILEGES s$letter ON s$letter.privilege_type = \"$oneprivilege\" AND s$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND s$letter.table_schema = \"<databasename>\" ".
          "LEFT JOIN INFORMATION_SCHEMA.TABLE_PRIVILEGES  t$letter ON t$letter.privilege_type = \"$oneprivilege\" AND t$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND t$letter.table_schema = \"<databasename>\" AND t$letter.table_name = mt.tablename ".
          "LEFT JOIN INFORMATION_SCHEMA.COLUMN_PRIVILEGES c$letter ON c$letter.privilege_type = \"$oneprivilege\" AND c$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND c$letter.table_schema = \"<databasename>\" AND c$letter.table_name = mt.tablename AND c$letter.column_name = mf.fieldname ";
        if ($privilege == $oneprivilege)
          $wherepart = "COALESCE(u$letter.privilege_type, s$letter.privilege_type, t$letter.privilege_type, c$letter.privilege_type) IS NOT NULL ";
      }
    }
    return query('meta',
      'SELECT '.
        join_clean(', ',
          'mt.tablename',
          'mt.singular',
          'mt.plural',
          'mt.tableid',
          'mf.fieldid',
          'mf.fieldname',
          'mf.title',
          'mr.presentationname',
          'mf.foreigntableid',
          'mf.nullallowed',
          'mf.indesc',
          'mf.inlist',
          'mf.inedit',
          'mt2.tablename AS foreigntablename',
          'mt2.singular AS foreigntablenamesingular',
          'mf2.fieldname AS foreignuniquefieldname',
          $selectparts
        ).' '.
      'FROM `<metabasename>`.tables mt '.
      'RIGHT JOIN `<metabasename>`.fields mf ON mf.tableid = mt.tableid '.
      'LEFT JOIN `<metabasename>`.presentations mr ON mr.presentationid = mf.presentationid '.
      'LEFT JOIN `<metabasename>`.tables mt2 ON mt2.tableid = mf.foreigntableid '.
      'LEFT JOIN `<metabasename>`.fields mf2 ON mf2.fieldid = mt2.uniquefieldid '.
      join($joinparts).
      'WHERE '.
        'mt.tablename = "<tablename>" AND '.
        'mf.<purpose> AND '.
        $wherepart.
      'ORDER BY mf.fieldid',
      array('metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'purpose'=>$purpose, 'username'=>$_SESSION['username'], 'host'=>$_SESSION['host'])
    );
  }

  function descriptor($metabasename, $databasename, $tablename, $tablealias, $stack = array()) {
    static $descriptors = array();
    if (!$descriptors[$tablename]) {
      $arguments = $joins = array();
      $fields = fieldsforpurpose($metabasename, $databasename, $tablename, 'indesc');
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

  function forget($usernameandhost) {
    $expire = time() + 365 * 24 * 60 * 60;
    setcookie('lastusernamesandhosts', join_clean(',', array_diff(explode(',', $_COOKIE['lastusernamesandhosts']), array($usernameandhost))), $expire);
  }

  function login($username, $host, $password, $language) {
    $_SESSION['username'] = $username;
    $_SESSION['host']     = $host;
    $_SESSION['password'] = $password;
    $_SESSION['language'] = $language;

    $expire = time() + 365 * 24 * 60 * 60;
    setcookie('lastusernamesandhosts', join_clean(',', array_diff(array_unique(array_merge(array("$username@$host"), array_diff(explode(',', $_COOKIE['lastusernamesandhosts']), array("$username@$host")))), array(''))), $expire);
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
    return $content === false ? array() : $content;
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

  function has_grant($privilege, $databasename = '*', $tablename = '*', $fieldname = '*') {
    //for privilege see http://dev.mysql.com/doc/refman/5.0/en/privileges-provided.html
    //$databasename == '*' means privilege on all databases
    //$databasename == '?' means privilege on at least one database
    return mysql_num_rows(
      query('meta',
        'SELECT "<privilege>" '.
        'FROM INFORMATION_SCHEMA.SCHEMATA sc '.
        'LEFT JOIN INFORMATION_SCHEMA.USER_PRIVILEGES   up ON up.privilege_type = "<privilege>" AND up.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") '.
        ($databasename == '*' ? '' : 'LEFT JOIN INFORMATION_SCHEMA.SCHEMA_PRIVILEGES sp ON sp.privilege_type = "<privilege>" AND sp.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") '.($databasename == '?' ? '' : 'AND sp.table_schema = "<databasename>" ')).
        ($tablename    == '*' ? '' : 'LEFT JOIN INFORMATION_SCHEMA.TABLE_PRIVILEGES  tp ON tp.privilege_type = "<privilege>" AND tp.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") AND tp.table_schema = "<databasename>" '.($tablename == '?' ? '' : 'AND tp.table_name = "<tablename>" ')).
        ($fieldname    == '*' ? '' : 'LEFT JOIN INFORMATION_SCHEMA.COLUMN_PRIVILEGES cp ON cp.privilege_type = "<privilege>" AND cp.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") AND cp.table_schema = "<databasename>" AND cp.table_name = "<tablename>" '.($fieldname == '?' ? '' : 'AND cp.column_name = "<fieldname>" ')).
        'WHERE '.($databasename == '?' || $databasename == '*' ? '' : 'sc.schema_name = "<databasename>" AND ').'(up.privilege_type IS NOT NULL OR sp.privilege_type IS NOT NULL '.($tablename == '*' ? '' : 'OR tp.privilege_type IS NOT NULL ').($fieldname == '*' ? '' : 'OR cp.privilege_type IS NOT NULL').') '.
        'LIMIT 1',
        array('databasename'=>$databasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname, 'username'=>$_SESSION['username'], 'host'=>$_SESSION['host'], 'privilege'=>$privilege)
      )
    ) > 0;
  }

  function databases_with_grant($privilege) {
    //for privilege see http://dev.mysql.com/doc/refman/5.0/en/privileges-provided.html
    $grants = query('meta',
      '( '.
        'SELECT up.privilege_type, sc.schema_name '.
        'FROM INFORMATION_SCHEMA.SCHEMATA sc '.
        'LEFT JOIN INFORMATION_SCHEMA.USER_PRIVILEGES   up ON up.privilege_type = "<privilege>" AND up.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") '.
        'LEFT JOIN INFORMATION_SCHEMA.SCHEMA_PRIVILEGES sp ON sp.privilege_type = "<privilege>" AND sp.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") AND sp.table_schema = sc.schema_name '.
        'WHERE sc.schema_name NOT IN ("mysql", "information_schema") '.
      ') '.
      'UNION '.
      '( '.
        'SELECT up.privilege_type, table_schema '.
        'FROM INFORMATION_SCHEMA.SCHEMA_PRIVILEGES sp '.
        'LEFT JOIN INFORMATION_SCHEMA.USER_PRIVILEGES   up ON up.privilege_type = "<privilege>" AND up.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") '.
        'WHERE table_schema NOT IN ("mysql", "information_schema") AND sp.privilege_type = "<privilege>" AND sp.grantee IN ("\'<username>\'@\'<host>\'", "\'<username>\'@\'%\'") '.
      ')',
      array('username'=>$_SESSION['username'], 'host'=>$_SESSION['host'], 'privilege'=>$privilege)
    );
    $databases = array();
    while ($grant = mysql_fetch_assoc($grants)) {
      if ($grant['privilege_type'] == $privilege)
        return array('*');
      $databases[] = $grant['schema_name'];
    }
    return $databases;
  }
?>
