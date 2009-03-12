<?php
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
    return $value ? str_replace("\\'", "'", $value) : $default;
  }

  function array_clean($list) {
    return array_diff($list, array(null));
  }

  function array_show($array) {
    return preg_replace('/^Array/', '', print_r($array, true));
  }

  function html($tag, $parameters = array(), $text = null) {
    $parameterlist = array();
    foreach ($parameters as $parameter=>$value)
      if ($parameter && !is_null($value))
        $parameterlist[] = " $parameter=\"$value\"";
    $starttag = $tag ? '<'.$tag.join($parameterlist).(is_null($text) ? ' /' : '').'>' : '';
    $endtag = $tag ? "</$tag>" : '';
    return $starttag.(is_null($text) ? '' : (is_array($text) ? join($endtag.$starttag, array_clean($text)) : $text).$endtag);
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
      addtolist('logs', '', 'ajax_'.$parameters['function'].' '.array_show($parameters));
      switch ($parameters['function']) {
      case 'list_table':
        $output = list_table($parameters['metabasename'], $parameters['databasename'], $parameters['tablename'], $parameters['limit'], $parameters['offset'], $parameters['uniquefieldname'], $parameters['orderfieldid'], $parameters['foreignfieldname'], $parameters['foreignvalue'], $parameters['parenttableid'], $parameters['interactive']);
        break;
      case 'ajax_lookup':
        $output = ajax_lookup($parameters['metabasename'], $parameters['databasename'], $parameters['fieldname'], $parameters['value'], $parameters['presentation'], $parameters['foreigntablename'], $parameters['foreignuniquefieldname'], $parameters['nullallowed'], $parameters['readonly']);
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
          $args[] = preg_replace('/<(.*?)>/', '&lt;\1&gt;', "'$arg'");
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
    if (!$_SESSION[$list])
      $_SESSION[$list] = array();
    $_SESSION[$list][] = html('li', array('class'=>$class), htmlspecialchars(preg_replace("/\r/", '\\n', $text)));
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
      $connection = mysql_connect($_SESSION['host'], $_SESSION['username'], $_SESSION['password']);
      if ($connection === FALSE)
        logout(sprintf(_('problem connecting to the databasemanager: %s'), mysql_error()));
    }

    $fullquery = preg_replace(array("/'/", '/(["`])?<(\w+)>(["`])?/e'), array('"', '(is_null($arguments["\\2"]) ? "NULL" : (is_numeric($arguments["\\2"]) ? (int) $arguments["\\2"] : "\\1".mysql_escape_string($arguments["\\2"])."\\3"))'), $query);

    $before = microtime();
    $result = mysql_query($fullquery);
    $after = microtime();
    list($beforemsec, $beforesec) = explode(' ', $before);
    list($aftermsec, $aftersec) = explode(' ', $after);

    $errno = mysql_errno();

    addtolist('logs', 
      "query$metaordata",
      '['.
      sprintf('%.2f sec', ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).
      ', '.
      ($errno
      ? $errno.' '.mysql_error()
      : (preg_match('@^[^A-Z]*(EXPLAIN|SELECT|SHOW) @i', $fullquery)
        ? mysql_num_rows($result).' results'
        : mysql_affected_rows($connection).' affected'
        )
      ).
      '] '.
      preg_replace(
        array('@<@' , '@>@' , '@& @'  ),
        array('&lt;', '&gt;', '&amp; '),
        $fullquery
      )
    );

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
    header('Content-Language: '.best_locale());
    print
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>'style.php', 'type'=>'text/css', 'rel'=>'stylesheet')).
          html('script', array('type'=>'text/javascript', 'src'=>'jquery.min.js'), '').
          html('script', array('type'=>'text/javascript', 'src'=>'script.php'), '')
        ).
        html('body', array('class'=>preg_replace('@_@', '', $action)),
          html('div', array('id'=>'header'),
            html('h1', array('id'=>'title'), $title).
            ($_SESSION['username'] ? html('div', array('id'=>'id'), join(' &ndash; ', array(strtolower(best_locale()), "$_SESSION[username]@$_SESSION[host]", internalreference(array('action'=>'logout'), 'logout')))) : '').
            html('div', array('id'=>'messages'),
              $error ? html('div', array('class'=>'error'), $error) : ''
            ).
            $path
          ).
          html('div', array('id'=>'content'),
            html('ol', array('id'=>'warnings'), join(getlist('warnings'))).
            $content
          ).
          html('div', array('id'=>'footer'),
            html('ol', array('id'=>'logs'), join(getlist('logs')))
          )
        )
      );
    exit;
  }

  function form($content) {
    return html('form', array('action'=>parameter('server', 'SCRIPT_NAME'), 'enctype'=>'multipart/form-data', 'method'=>'post'), $content);
  }
  
  function databasenames($metabasename) {
    $tables = query('meta', 'SHOW TABLES FROM `<metabasename>`', array('metabasename'=>$metabasename));
    if ($tables)
      while ($table = mysql_fetch_assoc($tables)) {
        $tablename = $table["Tables_in_$metabasename"];
        if ($tablename == 'metaconstant')
          return query('meta', 'SELECT * FROM `<metabasename>`.metavalue mv LEFT JOIN `<metabasename>`.metaconstant mc ON mv.constantid = mc.constantid WHERE constantname = \'database\'', array('metabasename'=>$metabasename));
      }
    return null;
  }
  
  function path($metabasename, $databasename = null, $tablename = null, $uniquefieldname = null, $uniquevalue = null) {
    return html('h2', array(), 
      join(' - ',
        array_clean(
          array(
            $metabasename,
            $metabasename && $databasename ? internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename) : null,
            $metabasename && $databasename && $tablename && $uniquefieldname ? internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename), $tablename) : null,
            $metabasename && $databasename && $tablename && $uniquefieldname && !is_null($uniquevalue) ? query1field('data', "SELECT ".descriptor($metabasename, $tablename, $tablename)." AS _descriptor FROM `$databasename`.$tablename WHERE $uniquefieldname = $uniquevalue") : null
          )
        )
      )
    );
  }
  
  function list_table($metabasename, $databasename, $tablename, $limit, $offset, $uniquefieldname, $orderfieldname, $foreignfieldname = null, $foreignvalue = null, $parenttablename = null, $interactive = TRUE) {
    $originalorderfieldname = $orderfieldname;
    $joins = $selectnames = $ordernames = array();
    $fields = fieldsforpurpose($metabasename, $tablename, 'inlist');
    while ($field = mysql_fetch_assoc($fields)) {
//    print html('p', array(), array_show($field));
      $selectnames[] = "$tablename.$field[fieldname] AS ${tablename}_$field[fieldname]";
      if ($field['foreigntablename']) {
        $joins[] = " LEFT JOIN `$databasename`.$field[foreigntablename] AS $field[foreigntablename]_$field[fieldname] ON $field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname]=$tablename.$field[fieldname]";
        $selectnames[] = descriptor($metabasename, $field['foreigntablename'], "$field[foreigntablename]_$field[fieldname]")." AS $field[foreigntablename]_$field[fieldname]_descriptor";
      }
      $ordernames[] = $field['foreigntablename'] ? "$field[foreigntablename]_$field[fieldname]_descriptor" : "${tablename}_$field[fieldname]";
      if (!$orderfieldname)
        $orderfieldname = $field['fieldname'];
      if ($orderfieldname == $field['fieldname'])
        $orderfieldalias = array_pop($ordernames);
    }
    if ($orderfieldalias)
      array_unshift($ordernames, $orderfieldalias);
    $rows = query('data', "SELECT ".($limit ? "SQL_CALC_FOUND_ROWS " : "")."$tablename.$uniquefieldname AS $uniquefieldname".($selectnames ? ', '.join(', ', $selectnames) : '')." FROM `$databasename`.$tablename".join(array_unique($joins)).($foreignvalue ? " WHERE $tablename.$foreignfieldname = '$foreignvalue'" : '').($ordernames ? " ORDER BY ".join(', ', $ordernames) : '').($limit ? " LIMIT $limit".($offset ? " OFFSET $offset" : '') : ''));
    $foundrows = $limit ? query1('data', 'SELECT FOUND_ROWS() AS number') : null;

    $lines = array();
    while ($row = mysql_fetch_assoc($rows)) {
      $line = array();
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        if ($field['inlist']) {
          $value = $row["${tablename}_$field[fieldname]"];
          $field['descriptor'] = $row["$field[foreigntablename]_$field[fieldname]_descriptor"];
          $field['thisrecord'] = $foreignvalue && $field['fieldname'] == $foreignfieldname;
          include_once("presentation/$field[presentation].php");
          $cell = call_user_func("list_$field[presentation]", $metabasename, $databasename, $field, $value);
          $line[] = html('td', array('class'=>'column '.$field['presentation']), $cell);
        }
      }
      $rownumber++;
      $lines[] = 
        html('tr', array('class'=>$rownumber % 2 == 0 ? 'roweven' : 'rowodd'), 
          join($line).
          ($interactive
          ? html('td', array(),
              array(
                internalreference(array('action'=>'edit_record',   'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), 'edit'  ),
                internalreference(array('action'=>'delete_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], 'back'=>parameter('server', 'REQUEST_URI')), 'delete')
              )
            )
          : ''
          )
        );
    }

    if ($lines) {
      $header = array();
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        if ($field['inlist']) {
          $header[] = 
            html('th', $foreignvalue && $field['fieldname'] == $foreignfieldname ? array('class'=>'thisrecord') : array(), 
              ($foreignvalue && $foreignfieldname) || $field['fieldname'] == $orderfieldname
              ? preg_replace('/(?<=\w)id$/i', '', $field['fieldname'])
              : internalreference(
                  array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$field['fieldname']), 
                  preg_replace('/(?<=\w)id$/i', '', $field['fieldname'])
                )
            );
        }
      }
    }
    $offsets = array();
    if ($limit > 0 && $foundrows['number'] > $limit) {
      for ($otheroffset = 0; $otheroffset < $foundrows['number']; $otheroffset += $limit) {
        $lastrecord = min($otheroffset + $limit, $foundrows['number']);
        $text = ($otheroffset + 1).($otheroffset + 1 == $lastrecord ? '' : '-'.$lastrecord);
        $offsets[] = $offset == $otheroffset ? $text : internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'offset'=>$otheroffset, 'orderfieldname'=>$originalorderfieldname), $text);
      }
    }

    return 
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('function'=>'list_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablename'=>$tablename, 'limit'=>$limit, 'offset'=>$offset, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$orderfieldname, 'foreignfieldname'=>$foreignfieldname, 'foreignvalue'=>$foreignvalue, 'parenttablename'=>$parenttablename, 'interactive'=>$interactive))), 
        ($interactive
        ? internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), sprintf(_('new %s'), $tablename)).
          html('span', array('class'=>'changeslost'), ' '._('(changes to form fields are lost)'))
        : ($foreignvalue ? $tablename : '')
        ).
        html('div', array('class'=>'ajaxcontent'), '').
        ($lines
        ? html('table', array(), 
            html('tr', array(), 
              join($header).
              ($interactive
              ? html('th', array(), array('&nbsp;', '&nbsp;'))
              : ''
              )
            ).
            join($lines)
          )
        : ''
        ).
        join(' &nbsp; ', $offsets)
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
      $uniquevalue
      ? "UPDATE `<databasename>`.`<tablename>` SET ".join(', ', $sets)." WHERE <uniquefieldname> = '<uniquevalue>'"
      : "INSERT INTO `<databasename>`.`<tablename>` SET ".join(', ', $sets),
      array_merge($arguments, array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue))
    );
    return $uniquevalue ? $uniquevalue : mysql_insert_id();
  }
  
  function preg_match1($pattern, $subject, $default = null) {
    return preg_match($pattern, $subject, $matches) ? (count($matches) == 2 ? $matches[1] : $matches[0]) : $default;
  }
  
  function preg_delete($pattern, $subject) {
    if (!preg_match($pattern, $subject, $matches))
      return array($subject, null);
    return array(preg_replace($pattern, '', $subject), $matches[1]);
  }
  
  function totaltype($metafield) {
    return
      strtoupper($metafield['type']).
      ($metafield['typelength']                             ? "($metafield[typelength])" : '').
      ($metafield['typeunsigned']                           ? " UNSIGNED"                : '').
      ($metafield['autoincrement']                          ? " AUTO_INCREMENT"          : '').
      ($metafield['uniquefieldid'] == $metafield['fieldid'] ? " PRIMARY KEY"             : '').
      (!$metafield['nullallowed']                           ? " NOT NULL"                : '');
  }
  
  function mysql_data_reset($results) {
    if (mysql_num_rows($results) > 0)
      mysql_data_seek($results, 0);
  }
  
  function fieldsforpurpose($metabasename, $tablename, $purpose) {
    return query('meta', 
      "SELECT mt.tablename, mt.tableid, mf.fieldid, mf.fieldname, mr.presentation, mf.autoincrement, mf.foreigntableid, mf.nullallowed, mf.indesc, mf.inlist, mf.inedit, mt2.tablename AS foreigntablename, mf2.fieldname AS foreignuniquefieldname ".
      "FROM `$metabasename`.metatable mt ".
      "RIGHT JOIN `$metabasename`.metafield mf ON mf.tableid = mt.tableid ".
      "LEFT JOIN `$metabasename`.metatype my ON my.typeid = mf.typeid ".
      "LEFT JOIN `$metabasename`.metapresentation mr ON mr.presentationid = my.presentationid ".
      "LEFT JOIN `$metabasename`.metatable mt2 ON mt2.tableid = mf.foreigntableid ".
      "LEFT JOIN `$metabasename`.metafield mf2 ON mf2.fieldid = mt2.uniquefieldid ".
      "WHERE mt.tablename = '$tablename' AND mf.$purpose ".
      "ORDER BY mf.fieldid");
  }
  
  function descriptor($metabasename, $tablename, $tablealias) {
    static $descriptors = array();
    if (!$descriptor[$tablename]) {
      $arguments = array();
      $descriptorfields = fieldsforpurpose($metabasename, $tablename, 'indesc');
      while($descriptorfield = mysql_fetch_assoc($descriptorfields) ) 
        $arguments[] = "<table>.$descriptorfield[fieldname]";
      $descriptors[$tablename] = count($arguments) == 1 ? $arguments[0] : 'CONCAT_WS(" ", '.join(', ', $arguments).')';
    }
    return preg_replace('/<table>/', $tablealias, $descriptors[$tablename]);
  }

  function login($username, $host, $password, $language) {
    $_SESSION['username'] = $username;
    $_SESSION['host']     = $host;
    $_SESSION['password'] = $password;
    $_SESSION['language'] = $language;
  }

  function logout($error = null) {
    $_SESSION = array();
    session_destroy();
    internalredirect(array('action'=>'login', 'error'=>$error));
  }

  function get_presentations() {
    static $presentations = array();
    if ($presentations)
      return $presentations;
    $dir = opendir('presentation');
    while ($file = readdir($dir)) {
      if (preg_match('/^(.*)\.php$/', $file, $matches)) {
        $presentations[] = $matches[1];
        include("presentation/$file");
      }
    }
    closedir($dir);
    sort($presentations);
    return $presentations;
  }

  function augment_file($filename, $function_prefix, $content_type) {
    $content = join(file($filename));

    $extra = array();
    $presentations = get_presentations();
    foreach ($presentations as $presentation)
      $extra[] = @call_user_func("${function_prefix}_$presentation");

    header("Content-Type: $content_type"); 
    print preg_replace("@( *)// *${function_prefix}_presentation\b.*\n@e", '"\\1".preg_replace("@\n(?=.)@", "\n\\1", join($extra))', $content);
    exit;
  }

  function best_locale() {
    static $best_locale = null;
    if ($best_locale)
      return $best_locale;

    $http_accept = parameter('session', 'language', parameter('server', 'HTTP_ACCEPT_LANGUAGE', null));

    $locales = array();
    $parts = explode(',', preg_replace('/ /', '', $http_accept));
    foreach ($parts as $part)
      if (preg_match('/([^;]+)(?:;q=([01]?\.\d{0,4}))?/i', $part, $matches))
        $locales[$matches[1]] = (float) (isset($matches[2]) ? $matches[2] : 1);

    $best_locale = 'en-us';
    $maxq = 0;
    foreach ($locales as $locale=>$q) {
      if ($q > $maxq) {
        $maxq = $q;
        $best_locale = $locale;
      }
    }

    $best_locale = preg_replace('/^(\w\w)(?:(-)(\w\w))?$/e', "'\\1'.('\\2' ? '_'.strtoupper('\\3') : '')", $best_locale);
    $best_locale = preg_replace('/\.utf8$/', '', setlocale(LC_ALL, "$best_locale.utf8"));

    return $best_locale;
  }

  function change_datetime_format($value, $from, $to) {
    if (!$value)
      return $value;
    $matches = strptime($from, $value);
    return strftime($to, mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'], $matches['tm_mday'], $matches['tm_year']));
  }
?>
