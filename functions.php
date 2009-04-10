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
        $output = ajax_lookup($parameters['metabasename'], $parameters['databasename'], $parameters['fieldname'], query1field('data', 'SELECT MAX(<foreignuniquefieldname>) FROM `<databasename>`.<foreigntablename>', array('foreigntablename'=>$parameters['foreigntablename'], 'foreignuniquefieldname'=>$parameters['foreignuniquefieldname'], 'databasename'=>$parameters['databasename'])), $parameters['presentation'], $parameters['foreigntablename'], $parameters['foreignuniquefieldname'], $parameters['nullallowed'], $parameters['readonly']);
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
          html('link', array('href'=>'style.php', 'type'=>'text/css', 'rel'=>'stylesheet')).
          ($_SESSION['ajaxy']
          ? html('script', array('type'=>'text/javascript', 'src'=>'jquery.min.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>'script.php'), '')
          : ''
          )
        ).
        html('body', array('class'=>preg_replace('@_@', '', $action)),
          html('div', array('id'=>'header'),
            html('h1', array('id'=>'title'), $title).
            ($_SESSION['username'] ? html('div', array('id'=>'id'), join(' &ndash; ', array(get_locale(), "$_SESSION[username]@$_SESSION[host]", internalreference(parameter('server', 'REQUEST_URI').'&ajaxy='.($_SESSION['ajaxy'] ? 'off' : 'on'), 'ajax is '.($_SESSION['ajaxy'] ? 'on' : 'off')), internalreference(array('action'=>'logout'), 'logout')))) : '').
            
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
            !is_null($metabasename) ? $metabasename : '&hellip;',
            !is_null($databasename) ? ($metabasename ? internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename) : $databasename) : null,
            !is_null($tablename)    ? ($metabasename && $databasename && $uniquefieldname ? internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename), $tablename) : $tablename) : null,
            !is_null($uniquevalue)  ? ($metabasename && $databasename && $tablename && $uniquefieldname ? query1field('data', 'SELECT '.descriptor($metabasename, $tablename, $tablename).' FROM `<databasename>`.`<tablename>` WHERE <uniquefieldname> = <uniquevalue>', array('databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)) : $uniquevalue) : null
          )
        )
      )
    );
  }
  
  function clean_name($name, $forbiddennoun = null) {
    return preg_replace(
      array('/(?<=\w)id$/i', '/(?<=[a-z])([A-Z]+)/e', "/\b$forbiddennoun\b/i", "/\b".singularize_noun($forbiddennoun)."\b/i"),
      array('',              'strtolower(" \\1")',    '',                      ''                                           ),
      $name
    );
  }

  function list_table($metabasename, $databasename, $tablename, $limit, $offset, $uniquefieldname, $orderfieldname, $foreignfieldname = null, $foreignvalue = null, $parenttablename = null, $interactive = TRUE) {
    $originalorderfieldname = $orderfieldname;
    $joins = $selectnames = $ordernames = array();
    $header = array(html('th', array('class'=>'small'), '&nbsp;'));
    $fields = fieldsforpurpose($metabasename, $tablename, 'inlist');
    while ($field = mysql_fetch_assoc($fields)) {
      $selectnames[] = "$tablename.$field[fieldname] AS ${tablename}_$field[fieldname]";
      if ($field['foreigntablename']) {
        $joins[] = " LEFT JOIN `$databasename`.$field[foreigntablename] AS $field[foreigntablename]_$field[fieldname] ON $field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname] = $tablename.$field[fieldname]";
        $selectnames[] = descriptor($metabasename, $field['foreigntablename'], "$field[foreigntablename]_$field[fieldname]")." AS $field[foreigntablename]_$field[fieldname]_descriptor";
      }

      $ordernames[] = $field['foreigntablename'] ? "$field[foreigntablename]_$field[fieldname]_descriptor" : "${tablename}_$field[fieldname]";
      if (!$orderfieldname)
        $orderfieldname = $field['fieldname'];
      if ($orderfieldname == $field['fieldname'])
        array_unshift($ordernames, array_pop($ordernames));

      include_once("presentation/$field[presentation].php");
      $header[] = 
        html('th', !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? array('class'=>'thisrecord') : array(), 
          !is_null($foreignvalue) || !call_user_func("is_sortable_$field[presentation]")
          ? clean_name($field['fieldname'], $tablename)
          : ($field['fieldname'] == $orderfieldname
            ? clean_name($field['fieldname'], $tablename).' &#x25be;'
            : internalreference(
                array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$field['fieldname']), 
                clean_name($field['fieldname'], $tablename),
                array('class'=>'ajaxreload')
              )
            )
        );
    }
    $rows = query('data', "SELECT ".($limit ? "SQL_CALC_FOUND_ROWS " : "")."$tablename.$uniquefieldname AS $uniquefieldname".($selectnames ? ', '.join(', ', $selectnames) : '')." FROM `$databasename`.$tablename".join(array_unique($joins)).(!is_null($foreignvalue) ? " WHERE $tablename.$foreignfieldname = '$foreignvalue'" : '').($ordernames ? " ORDER BY ".join(', ', $ordernames) : '').($limit ? " LIMIT $limit".($offset ? " OFFSET $offset" : '') : ''));
    $foundrows = $limit ? query1('data', 'SELECT FOUND_ROWS() AS number') : null;

    $lines = array(html('tr', array(), join($header)));
    while ($row = mysql_fetch_assoc($rows)) {
      $line = array();
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        $value = $row["${tablename}_$field[fieldname]"];
        $field['descriptor'] = $row["$field[foreigntablename]_$field[fieldname]_descriptor"];
        $field['thisrecord'] = !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname;
        $field['uniquefieldname'] = $uniquefieldname;
        $field['uniquevalue'] = $row[$uniquefieldname];
        $cell = call_user_func("list_$field[presentation]", $metabasename, $databasename, $field, $value);
        $line[] = html('td', array('class'=>join(' ', array_clean(array('column '.$field['presentation'], $field['thisrecord'] ? 'thisrecord' : null)))), $cell);
      }
      $lines[] = 
        html('tr', array('class'=>join(' ', array(count($lines) % 2 ? 'rowodd' : 'roweven', 'list'))),
          ($interactive
          ? html('td', array('class'=>'small'),
              array(
                internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), 'edit'  )
//              internalreference(array('action'=>'show_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], 'back'=>parameter('server', 'REQUEST_URI')), 'delete')
              )
            )
          : ''
          ).
          join($line)
        );
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
        ? html('div', array(),
            internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), sprintf(_('new %s'), singularize_noun($tablename))).
            (is_null($foreignvalue) ? '' : html('span', array('class'=>'changeslost'), ' '._('(changes to form fields are lost)')))
          )
        : (is_null($foreignvalue) ? '' : $tablename)
        ).
        (count($lines) > 1 ? html('table', array('class'=>'tablelist'), join($lines)) : '').
        join(' &nbsp; ', $offsets).
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
    $_SESSION['ajaxy']    = true;
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

  function read_file($filename, $flags = null) {
    $content = @file($filename, $flags);
    return $content === false ? array() : $content;
  }

  function augment_file($filename, $content_type) {
    $content = join(read_file($filename));

    if (preg_match_all('@// *(\w+)_presentation\b@', $content, $function_prefixes, PREG_SET_ORDER)) {
      $presentations = get_presentations();
      foreach ($function_prefixes as $function_prefix) {
        $extra = array();
        foreach ($presentations as $presentation)
          $extra[] = @call_user_func("$function_prefix[1]_$presentation");

        $content = preg_replace("@( *)// *$function_prefix[1]_presentation\b.*\n@e", '"\\1".preg_replace("@\n(?=.)@", "\n\\1", join($extra))', $content);
      }
    }

    header("Content-Type: $content_type"); 
    print $content;
    exit;
  }

  function change_datetime_format($value, $from, $to) {
    if (!$value)
      return $value;
    $matches = strptime($value, $from);
    return strftime($to, mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'], $matches['tm_mday'], $matches['tm_year']));
  }

  function bare($text) {
    return preg_replace('@[^a-z0-9\._]@', '', strtolower($text));
  }

  function explode_with_priority($text) {
    $exploded = array();
    $parts = explode(',', $text);
    foreach ($parts as $part) {
      if (preg_match('/([^;]+)(?:;q=(\d*(\.\d*)?))?/i', $part, $matches)) {
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

//  if ($best_locale != $preferred_locale)
//    addtolist('warnings', 'warning', sprintf(_('preferred locale %s not found on operating system level, falling back to %s'), $preferred_locale, $best_locale));
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
?>
