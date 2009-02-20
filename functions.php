<?php
  umask(0077); //no rwx for group and other, so only owner gets permissions

  function parameter($type, $name = null, $default = null) {
    $array = $type == 'get' ? ($_POST ? $_POST : $_GET) : ($type == 'server' ? $_SERVER : array());
    if (!$name)
      return $array;
    $value = $array[$name];
    return $value ? str_replace("\\'", "'", $value) : $default;
  }

  function cleanlist($list) {
    return array_diff($list, array(null));
  }

  function html($tag, $parameters = array(), $text = null) {
    foreach ($parameters as $parameter=>$value)
      if ($parameter && !is_null($value))
        $parameterlist .= " $parameter=\"$value\"";
    $starttag = $tag ? '<'.$tag.$parameterlist.(is_null($text) ? ' /' : '').'>' : '';
    $endtag = $tag ? "</$tag>" : '';
    return $starttag.(is_null($text) ? '' : (is_array($text) ? join($endtag.$starttag, cleanlist($text)) : $text).$endtag);
  }

  function httpurl($parameters) {
    $parameters['session'] = session();
    foreach ($parameters as $name=>$value)
      if (!is_null($value))
        $parameterlist .= ($parameterlist ? '&' : '').$name.'='.rawurlencode($value);
    return parameter('server', 'SCRIPT_NAME').'?'.$parameterlist;
  }

  function internalurl($parameters) {
    return htmlentities(httpurl($parameters));
  }

  function externalreference($url, $text) {
    return html('a', array('href'=>htmlentities($url)), $text);
  }

  function internalreference($parameters, $text) {
    return html('a', array('href'=>internalurl($parameters)), $text);
  }

  function redirect($url) {
    header('Location: '.$url);
    exit;
  }

  function internalredirect($parameters) {
    redirect(httpurl($parameters));
  }
  
  function back() {
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
    page('error',
      html('p', array('class'=>'error'), $error).
      html('p', array('class'=>'trace'), html('ol', array(), html('li', array(), $traces)))
    );
    exit;
  }
  
  function addlog($class, $text) {
    $text = html('li', array('class'=>$class), $text);
    $file = fopen('tmp/log', 'a');
    if (!$file)
      error('error opening log file');
    if (!fwrite($file, $text))
      error('error writing to log file');
    if (!fclose($file))
      error('error closing log file');
  }

  function getlogs() {
    $logs = @file('tmp/log');
    @unlink('tmp/log');
    return $logs;
  }

  function query($metaordata, $query) {
    $before = microtime();
    $result = mysql_query($query);
    $after = microtime();
    list($beforemsec, $beforesec) = explode(' ', $before);
    list($aftermsec, $aftersec) = explode(' ', $after);

    $errno = mysql_errno();

    addlog(
      "query$metaordata",
      '['.
      sprintf('%.2f sec', ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).
      ', '.
      ($errno
      ? html('span', array('class'=>'error'), 'error: '.$errno.' '.mysql_error())
      : (preg_match('@^[^A-Z]*(EXPLAIN|SELECT|SHOW) @i', $query)
        ? mysql_num_rows($result).' results'
        : mysql_affected_rows(connection()).' affected'
        )
      ).
      '] '.
      preg_replace(
        array('@<@' , '@>@' , '@& @'  ),
        array('&lt;', '&gt;', '&amp; '),
        $query
      )
    );

    if ($result)
      return $result;
    if ($errno == 1044) // Access denied for user '%s'@'%s' to database '%s'
      return null;
    error('problem while querying the databasemanager'.html('p', array('class'=>'error'), "$errno: ".mysql_error()).$query);
  }
  
  function query1($metaordata, $query) {
    $results = query($metaordata, $query);
    if ($results && mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error('problem retrieving 1 result, because there are '.($results ? mysql_num_rows($results) : 'no').' results'.html('p', array(), $query));
  }

  function query1field($metaordata, $query, $field) {
    $result = query1($metaordata, $query);
    return $result[$field];
  }
  
  function page($action, $content) {
    $title = str_replace('_', ' ', $action);

    $error = parameter('get', 'error');

    header('Content-Type: text/html; charset=iso-8859-1');
    echo
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>'style.css', 'type'=>'text/css', 'rel'=>'stylesheet')).
          html('script', array('type'=>'text/javascript', 'src'=>'script.js'), '')
        ).
        html('body', array(),
          html('h1', array('class'=>'title'), $title).
          (username() ? html('div', array('class'=>'id'), username().'@'.host()." &ndash; ".internalreference(array('action'=>'logout'), 'logout')) : '').
          html('hr').
          ($error ? html('div', array('class'=>'error'), $error) : '').
          $content.
          html('ol', array('class'=>'logs'), join('', getlogs())).
          html('script', array('type'=>'text/javascript'), 'onload();')
        )
      );
    exit;
  }

  function form($content) {
    return
      html('form', array('action'=>parameter('server', 'SCRIPT_NAME'), 'method'=>'post'),
        html('input', array('type'=>'hidden', 'name'=>'session', 'value'=>session())).
        $content
      );
  }
  
  function databasenames($metabasename) {
    $tables = query('meta', "SHOW TABLES FROM `$metabasename`");
    if ($tables)
      while ($table = mysql_fetch_assoc($tables)) {
        $tablename = $table["Tables_in_$metabasename"];
        if ($tablename == 'metaconstant')
          return query('meta', "SELECT * FROM `$metabasename`.metavalue mv LEFT JOIN `$metabasename`.metaconstant mc ON mv.constantid = mc.constantid WHERE constantname = 'database'");
      }
    return null;
  }
  
  function tableanduniquefieldname($metabasename, $tableid) {
    $uniquefield = query1('meta', "SELECT tablename, fieldname FROM `$metabasename`.metatable mt LEFT JOIN `$metabasename`.metafield mf ON mt.uniquefieldid = mf.fieldid WHERE mt.tableid = $tableid");
    return array($uniquefield['tablename'], $uniquefield['fieldname']);
  }
  
  function path($metabasename, $databasename = null, $tablename = null, $tableid = null, $description = null) {
    return html('h2', array(), 
      $metabasename.
      ($databasename
      ? " - ".internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename).
        ($tablename
        ? " - ".internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid), $tablename).
          ($description
          ? " : $description"
          : ''
          )
        : ''
        )
      : ''
      )
    );
  }
  
  function array_plus($stack, $element) {
    array_push($stack, $element);
    return $stack;
  }

  function array_concat() {
    $result = array();
    for ($i = 0; $i < func_num_args(); ++$i) {
      $param = func_get_arg($i);
      if (!is_array($param))
        $param = array($param);
      $result = array_merge($result, $param);
    }
    return $result;
  }
  
  function description($metabasename, $databasename, $tableid, $reference, $tableids = array()) {
    static $descriptors;
    if (!$descriptors[$tableid])
      $descriptors[$tableid] = fieldsforpurpose($metabasename, $tableid, array('desc'));
    for (mysql_data_reset($descriptors[$tableid]); $descriptor = mysql_fetch_assoc($descriptors[$tableid]); ) {
      $value = $reference[$descriptor['fieldname']];
//      echo "$descriptor[fieldname] - $value".html('br');
      $description .= 
        ($description ? ' ' : '').
//        $descriptor['fieldname'].':'.
        ($descriptor['foreigntableid'] && $value
        ? (in_array($descriptor['foreigntableid'], $tableids)
          ? "[$value]"
          : description($metabasename, $databasename, $descriptor['foreigntableid'], 
              query1('data', "SELECT * FROM $databasename.$descriptor[foreigntablename] WHERE $descriptor[foreigntablename].$descriptor[foreignuniquefieldname] = '$value'"),
              array_plus($tableids, $tableid)
            )
          )
        : $value
        );
    }
    return $description;
  }
  
  function rows($metabasename, $databasename, $tableid, $tablename, $limit, $offset, $uniquefieldname, $orderfieldid, $foreignfieldname = null, $foreignvalue = null, $parenttableid = null, $interactive = TRUE) {
    $originalorderfieldid = $orderfieldid;
    list($rows, $fields, $orderfieldid, $foundrows) = orderedrows($metabasename, $databasename, $tableid, $tablename, $limit, $offset, $uniquefieldname, 'list', $foreignfieldname, $foreignvalue, $orderfieldid);
    while ($row = mysql_fetch_assoc($rows)) {
      $line = '';
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        if ($field['purpose'] == 'list') {
          $value = $row["${tablename}_$field[fieldname]"];
          $class = 'column';
          $field['descriptor'] = $row["$field[foreigntablename]_$field[fieldname]_descriptor"];
          $field['thisrecord'] = $foreignvalue && $field['fieldname'] == $foreignfieldname;
          include_once("presentation/$field[presentation].php");
          $cell = call_user_func("cell_$field[presentation]", $metabasename, $databasename, $field, $value);
          $line .= html('td', array('class'=>'column '.$field['presentation']), $cell);
        }
      }
      $rownumber++;
      $lines .= 
        html('tr', array('class'=>$rownumber % 2 == 0 ? 'roweven' : 'rowodd'), 
          $line.
          ($interactive
          ? html('td', array(),
              array(
                internalreference(array('action'=>'edit_record',   'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, 'uniquevalue'=>$row[$uniquefieldname], 'back'=>parameter('server', 'REQUEST_URI')), 'edit'  ),
                internalreference(array('action'=>'delete_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, 'uniquevalue'=>$row[$uniquefieldname], 'back'=>parameter('server', 'REQUEST_URI')), 'delete')
              )
            )
          : ''
          )
        );
    }

    if ($lines) {
      $line = '';
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        if ($field['purpose'] == 'list') {
          $line .= 
            html('th', $foreignvalue && $field['fieldname'] == $foreignfieldname ? array('class'=>'thisrecord') : array(), 
              ($foreignvalue && $foreignfieldname) || $field['fieldid'] == $orderfieldid || !$field['sortable']
              ? preg_replace('/(?<=[a-z_])id$/', '', $field['fieldname'])
              : internalreference(
                  array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, 'orderfieldid'=>$field['fieldid']), 
                  $field['fieldname']
                )
            );
        }
      }
      $lines = 
        html('table', array(), 
          html('tr', array(), 
            $line.
            ($interactive
            ? html('th', array(), array('&nbsp;', '&nbsp;'))
            : ''
            )
          ).
          $lines
        );
    }
    $offsets = array();
    if ($limit > 0 && $foundrows['number'] > $limit) {
      for ($otheroffset = 0; $otheroffset < $foundrows['number']; $otheroffset += $limit) {
        $lastrecord = min($otheroffset + $limit, $foundrows['number']);
        $text = ($otheroffset + 1).($otheroffset + 1 == $lastrecord ? '' : '-'.$lastrecord);
        $offsets[] = $offset == $otheroffset ? $text : internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, 'offset'=>$otheroffset, 'orderfieldid'=>$originalorderfieldid), $text);
      }
    }

    return 
      ($interactive
      ? internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$tableid, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), "new $tablename")
      : ($foreignvalue ? $tablename : '')
      ).
      $lines.
      join(' &minus; ', $offsets);
  }
  
  function insertorupdate($databasename, $tablename, $fieldnamesandvalues, $uniquefieldname = null, $uniquevalue = null) {
    foreach ($fieldnamesandvalues as $fieldname=>$fieldvalue)
      if (!is_null($fieldvalue))
        $sets .= ($sets ? ', ' : '')."$fieldname = ".(is_null($fieldvalue) ? 'NULL' : (is_numeric($fieldvalue) ? $fieldvalue : "'$fieldvalue'"));
    query('data',
      $uniquevalue
      ? "UPDATE `$databasename`.$tablename SET $sets WHERE $uniquefieldname = '$uniquevalue'"
      : "INSERT INTO `$databasename`.$tablename SET $sets"
    );
    return $uniquevalue ? $uniquevalue : mysql_insert_id();
  }
  
  function preg_delete($pattern, $subject) {
    if (!preg_match($pattern, $subject, $matches))
      return array($subject, null);
    return array(preg_replace($pattern, '', $subject), $matches[1]);
  }
  
  function setelement($metabasename, $tablename, $fieldid, $fieldname, $purposeids, $purpose) {
    $value = parameter('get', "$tablename:$fieldname:$purpose");
    return $value ? insertorupdate($metabasename, 'metaelement', array('purposeid'=>$purposeids[$purpose], 'fieldid'=>$fieldid, 'rank'=>$value)) : 0;
  }
  
  function totaltype($metafield) {
    return
      $metafield['type'].
      ($metafield['typelength']                             ? "($metafield[typelength])" : '').
      ($metafield['typeunsigned']                           ? " unsigned"                : '').
      ($metafield['autoincrement']                          ? " auto_increment"          : '').
      ($metafield['uniquefieldid'] == $metafield['fieldid'] ? " primary key"             : '').
      (!$metafield['nullallowed']                           ? " not null"                : '');
  }
  
  function mysql_data_reset($results) {
    if (mysql_num_rows($results) > 0)
      mysql_data_seek($results, 0);
  }
  
  function queriesused($query, $old) {
    query('data', $query);
    return
      html('tr', array(),
        html('td', array(), 
          array(
            $query,
            "WAS $old"
          )
        )
      );
  }

  function fieldsforpurpose($metabasename, $tableid, $purposes, $firstfieldid = null) {
    return query('meta', "SELECT ".($firstfieldid ? "IF(mf.fieldid = $firstfieldid AND mp.purpose = 'sort', 0, me.rank)" : "me.rank")." AS myrank, mp.purpose, mf.fieldid, mf.fieldname, mr.presentation, mf.tableid, mf.autoincrement, mf.foreigntableid, mf.nullallowed, mt.tablename AS foreigntablename, mf2.fieldname AS foreignuniquefieldname, me2.rank AS sortable FROM `$metabasename`.metaelement me LEFT JOIN `$metabasename`.metapurpose mp ON me.purposeid = mp.purposeid LEFT JOIN `$metabasename`.metafield mf ON mf.fieldid = me.fieldid LEFT JOIN `$metabasename`.metatype my ON my.typeid = mf.typeid LEFT JOIN `$metabasename`.metapresentation mr ON mr.presentationid = my.presentationid LEFT JOIN `$metabasename`.metatable mt ON mf.foreigntableid = mt.tableid LEFT JOIN `$metabasename`.metafield mf2 ON mf2.fieldid = mt.uniquefieldid LEFT JOIN `$metabasename`.metapurpose mp2 ON mp2.purpose = 'sort' LEFT JOIN `$metabasename`.metaelement me2 ON me2.fieldid = mf.fieldid AND me2.purposeid = mp2.purposeid WHERE mf.tableid = $tableid".($purposes ? " AND ".(count($purposes) == 1 ? "mp.purpose = '$purposes[0]'" : "(mp.purpose = '".join("' OR mp.purpose = '", $purposes)."')") : '')." ORDER BY purpose, myrank");

  }
  
  function orderedrows($metabasename, $databasename, $tableid, $tablename, $limit, $offset, $uniquefieldname, $purpose, $foreignfieldname = null, $foreignvalue = null, $orderfieldid = null) {
    $fields = fieldsforpurpose($metabasename, $tableid, array_merge($purpose == 'desc' ? array() : array($purpose), $foreignfieldname ? array() : array('sort')), $orderfieldid);
    $joins = array();
    $fieldnamelist = array();
//  print "---".html('br');
    while ($field = mysql_fetch_assoc($fields)) {
//    print "$field[fieldname] $field[purpose] $field[fieldid] -> $field[myrank]".html('br');
      if ($field['purpose'] != 'sort' || ($field['purpose'] == 'sort' && !$field['foreigntableid'])) {
        $fieldnamelist[$field['purpose']] .= ($fieldnamelist[$field['purpose']] ? ', ' : '')."$tablename.$field[fieldname]".($field['purpose'] == 'sort' ? '' : " AS ${tablename}_$field[fieldname]");
        if (!$orderfieldid && $field['purpose'] == 'sort')
          $orderfieldid = $field['fieldid'];
      }
      if ($field['foreigntableid']) {
        $join = " LEFT JOIN `$databasename`.$field[foreigntablename] AS <table> ON <table>.$field[foreignuniquefieldname]=$tablename.$field[fieldname]";
        if (!$joins[$join])
          $joins[$join] = count($joins) + 1;
        $uniquenumber = $joins[$join];

        $fieldnamelist[$field['purpose']] .= ($fieldnamelist[$field['purpose']] ? ', ' : '').descriptor($metabasename, $field['foreigntableid'], "table$uniquenumber").($field['purpose'] == 'sort' ? '' : " AS $field[foreigntablename]_$field[fieldname]_descriptor");
      }
    }

    foreach ($joins as $join=>$number)
      $joinstext .= preg_replace('/<table>/', "table$number", $join);
    
    return array(query('data', "SELECT ".($limit ? "SQL_CALC_FOUND_ROWS " : "")."$tablename.$uniquefieldname AS $uniquefieldname".($fieldnamelist[$purpose] ? ", $fieldnamelist[$purpose]" : '').($purpose == 'desc' ? ', '.descriptor($metabasename, $tableid, $tablename)." AS ${tablename}_descriptor" : '')." FROM `$databasename`.$tablename$joinstext".($foreignvalue ? " WHERE $tablename.$foreignfieldname = '$foreignvalue'" : '').($fieldnamelist['sort'] ? " ORDER BY $fieldnamelist[sort]" : '').($limit ? " LIMIT $limit".($offset ? " OFFSET $offset" : '') : '')), $fields, $orderfieldid, $limit ? query1('data', 'SELECT FOUND_ROWS() AS number') : null);
  }

  function descriptor($metabasename, $tableid, $tablealias) {
    static $descriptions = array();
    $descriptor = $descriptions[$tableid];
    if (!$descriptor) {
      $descriptorfields = fieldsforpurpose($metabasename, $tableid, array('desc'));
      while($descriptorfield = mysql_fetch_assoc($descriptorfields) ) 
        $descriptor .= ($descriptor ? ', ' : '')."<table>.$descriptorfield[fieldname]";
      $descriptor  = "CONCAT_WS(' ', $descriptor)";
      $descriptions[$tableid] = $descriptor;
    }
    return preg_replace('/<table>/', $tablealias, $descriptor);
  }

  function checkboxyn($value, $name = null) {
    return html('input', array_merge(array('type'=>'checkbox'), $name ? array('class'=>'checkboxedit', 'name'=>$name) : array('class'=>'checkboxlist', 'readonly'=>'readonly', 'disabled'=>'disabled'), $value == 'Y' ? array('checked'=>'checked') : array()));
  }

  function username($newusername = null) {
    static $username = null;
    if (!is_null($newusername))
      $username = $newusername ? $newusername : null;
    return $username;
  }

  function host($newhost = null) {
    static $host = null;
    if (!is_null($newhost))
      $host = $newhost ? $newhost : null;
    return $host;
  }

  function password($newpassword = null) {
    static $password = null;
    if (!is_null($newpassword))
      $password = $newpassword ? $newpassword : null;
    return $password;
  }

  function session($what = null, $newsession = null, $username = null, $host = null, $password = null) {
    static $session = null;
    switch ($what) {
    case 'new':
      $tries = 0;
      while (true) {
        $newsession = '';
        for ($i = 0; $i < 20; $i++)
          $newsession .= rand(0, 9);
        $file = fopen("session/$newsession", 'x');
        if ($file) {
          if (!fwrite($file, "$username\n$host\n$password\n"))
            logout('error writing session');
          if (!fclose($file))
            logout('error closing session');
          break;
        }
        $tries++;
        if ($tries > 5)
          logout('error finding unique session id');
      }
      //no break in the switch, so continue
    case 'get':
      $session = $newsession ? $newsession : null;
      if (!$session)
        logout('missing session id');
      $sessionlines = @file("session/$session");
      if (!$sessionlines)
        logout('corrupt session id');
      if (count($sessionlines) != 3)
        logout('corrupt session');
      username(chop($sessionlines[0]));
      host(chop($sessionlines[1]));
      password(chop($sessionlines[2]));
      break;
    case 'del':
      $session = null;
      break;
    }
    return $session;
  }

  function login($username, $host, $password) {
    session('new', null, $username, $host, $password);
  }

  function connection() {
    static $connection = null;
    if (!$connection)
      $connection = mysql_connect(host(), username(), password());
    if (!$connection)
      logout('problem connecting to the databasemanager: '.mysql_error());
    return $connection;
  }

  function connect() {
    session('get', parameter('get', 'session'));
    connection();
  }

  function logout($error = null) {
    if (session())
      @unlink('session/'.session());
    session('del');
    internalredirect(array('action'=>'login', 'error'=>$error));
  }

  function grant($databasename, $privilege) {
    static $grants = null;
    if (!$grants)
      $grants = query('root', "SHOW GRANTS FOR '".username()."'@'".host()."'");
    for (mysql_data_reset($grants); $grant = mysql_fetch_assoc($grants); )
      if (preg_match("/^GRANT (.*?) ON (.*?) /", $grant["Grants for ".username()."@".host()], $matches) && ($matches[1] == 'ALL PRIVILEGES' || preg_match("/\b$privilege\b/", $matches[1])) && (preg_match("/^(`$databasename`|\*)/", $matches[2])))
        return TRUE;
    return FALSE;
  }

  function option($current, $value = null, $text = null) {
    return 
      !$current || $value 
      ? html('option', array_merge(array('value'=>$value), $value == $current ? array('selected'=>'selected') : array()), $text ? $text : $value)
      : '';
  }

  function show_array($arr) {
    return html('pre', array(), print_r($arr, true));
  }
?>
