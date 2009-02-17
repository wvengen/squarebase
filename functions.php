<?php
  function parameter($type, $name = null, $default = null) {
    global $HTTP_POST_VARS, $HTTP_GET_VARS, $HTTP_SERVER_VARS;
    $array = $type == 'get' ? ($HTTP_POST_VARS ? $HTTP_POST_VARS : $HTTP_GET_VARS) : ($type == 'server' ? $HTTP_SERVER_VARS : array());
    if (!$name)
      return $array;
    $value = $array[$name];
    return $value ? str_replace("\\'", "'", $value) : $default;
  }

  function html($name, $parameters = array(), $text = null) {
    foreach ($parameters as $key=>$value)
      if (!is_null($value))
        $parameterlist .= ' '.$key.(is_null($value) ? '' : '='.(is_int($value) && $value >= 0 ? $value : '"'.$value.'"'));
    $starttag = $name ? "<$name$parameterlist>" : '';
    $closetag = $name ? "</$name>" : '';
    return $starttag.(is_null($text) ? '' : (is_array($text) ? JOIN($closetag.$starttag, $text) : $text).$closetag);
  }

  function httpurl($parameters) {
    global $session;
    $parameters['session'] = $session;
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

  function error($text) {
    $stack = debug_backtrace();
    foreach ($stack as $element) {
      $args = '';
      if ($element['args'])
        foreach ($element['args'] as $arg)
          $args .= ($args ? ', ' : '').preg_replace('/<(.*?)>/', '&lt;\1&gt;', "'$arg'");
      $trace .= html('div', array('class'=>'trace'), "$element[file]:$element[line] $element[function]($args)");
    }
    page('error',
      html('div', array('class'=>'error'), $text).
      html('div', array('class'=>'debug'), $trace)
    );
    exit;
  }
  
  $allqueries = array();
  
  function query($type, $query) {
    global $allqueries;
    $allqueries[$query] = $type;
    $result = mysql_query($query);
    if ($result)
      return $result;
    $errno = mysql_errno();
    if ($errno == 1044) /* Access denied for user '%s'@'%s' to database '%s' */
      return null;
    error('problem while querying the databasemanager'.html('p', array(), html('i', array(), "$errno: ".mysql_error())).$query);
  }
  
  function query1($type, $query) {
    $results = query($type, $query);
    if ($results && mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error('problem retrieving 1 result, because there are '.($results ? mysql_num_rows($results) : 'no').' results'.html('p', array(), $query));
  }

  function query1field($type, $query, $field) {
    $result = query1($type, $query);
    return $result[$field];
  }
  
  function page($action, $content) {
    global $sessionparts, $allqueries;
    
    $title = str_replace('_', ' ', $action);

    $error = parameter('get', 'error');

    foreach ($allqueries as $query=>$type)
      $allqueriesastext .= html('li', array('class'=>"query$type"), $query);

    header('Content-Type: text/html; charset=iso-8859-1');
    echo
      '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>'style.css', 'type'=>'text/css', 'rel'=>'stylesheet'))
        ).
        html('body',
          array('onload'=>
            'var formnr, elementnr, eerste = null;'.
            'for (formnr = 0; !eerste && formnr < document.forms.length; formnr++)'.
              'for (elementnr = 0; !eerste && elementnr < document.forms[formnr].elements.length; elementnr++)'.
                'if (document.forms[formnr].elements[elementnr].type != \'hidden\')'.
                  'eerste = document.forms[formnr].elements[elementnr];'.
            'if (eerste)'.
              'eerste.focus();'
          ),
          html('h1', array('class'=>'title'), $title).
          ($sessionparts ? html('div', array('class'=>'id'), "$sessionparts[1]@$sessionparts[2] | ".internalreference(array('action'=>'logout'), 'logout')) : '').
          html('hr').
          ($error ? html('div', array('class'=>'error'), $error) : '').
          $content.
          ($allqueriesastext ? html('ol', array('class'=>'debug'), $allqueriesastext) : '')
        )
      );
    exit;
  }

  function form($content) {
    global $session;

    return
      html('form', array('action'=>parameter('server', 'SCRIPT_NAME'), 'method'=>'post'),
        html('input', array('type'=>'hidden', 'name'=>'session', 'value'=>$session)).
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
      ? " - ".internalreference(array('action'=>'show_tables', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename).
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
    global $descriptors;
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
          if ($field['foreigntableid']) {
            if ($foreignvalue && $field['fieldname'] == $foreignfieldname) {
              $cell = '(this)';
              $class .= ' thisrecord'; 
            }
            elseif ($row["$field[foreigntablename]_$field[fieldname]_descriptor"])
              $cell = internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tableid'=>$field['foreigntableid'], 'uniquevalue'=>$value, 'back'=>parameter('server', 'REQUEST_URI')), $row["$field[foreigntablename]_$field[fieldname]_descriptor"]);
            else {
              $cell = $value;
              $class = ' problem';
            }
          }
          elseif ($field['typeyesno'])
            $cell = html('input', array_merge(array('type'=>'checkbox', 'class'=>'checkboxlist', 'readonly'=>'readonly', 'disabled'=>'disabled', 'name'=>$field['fieldname']), $value ? array('checked'=>'checked') : array()));
          else
            $cell = $value;
          $line .= html('td', array('valign'=>'top', 'class'=>$class, 'align'=>$field['typeyesno'] ? 'center' : ($field['type'] == 'int' && !$field['foreigntableid'] ? 'right' : 'left')), $cell);
        }
      }
      $rownumber++;
      $lines .= 
        html('tr', array('class'=>$rownumber % 2 == 0 ? 'roweven' : 'rowodd'), 
          $line.
          ($interactive
          ? html('td', array('valign'=>'top'), 
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
              ? ($field['foreigntableid'] ? preg_replace('/id$/', '', $field['fieldname']) : $field['fieldname'])
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
            ? html('th', array(), array('', ''))
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

  $descriptions = array();
  
  function descriptor($metabasename, $tableid, $tablealias) {
    global $descriptions;
    
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

  function login($username, $host, $password) {
    global $session;
    do {
      $session = '';
      for ($i = 0; $i < 20; $i++)
        $session .= rand(0, 9);
      $file = fopen("session/$session", 'x');
      if ($file) {
        if (!fwrite($file, "$username $host $password"))
          logout('error writing session');
        if (!fclose($file))
          logout('error closing session');
      }
    } while (!$file);
  }

  function connect() {
    global $session, $sessionparts;
    $session = parameter('get', 'session');
    if (!$session)
      logout('missing session id');

    $sessionlines = @file("session/$session");

    if (!$sessionlines || count($sessionlines) != 1 || !preg_match('/^(\S*) (\S*) (\S*)$/', $sessionlines[0], $sessionparts))
      logout('corrupted session id');

    if (!@mysql_connect($sessionparts[2], $sessionparts[1], $sessionparts[3]))
      logout('problem connecting to the databasemanager: '.mysql_error());
  }

  function logout($error = null) {
    global $session;
    if ($session)
      @unlink("session/$session");
    $session = null;
    internalredirect(array('action'=>'login', 'error'=>$error));
  }

  $grants = null;

  function grant($databasename, $privilege) {
    global $grants, $sessionparts;
    if (!$grants)
      $grants = query('top', "SHOW GRANTS FOR '$sessionparts[1]'@'$sessionparts[2]'");
    for (mysql_data_reset($grants); $grant = mysql_fetch_assoc($grants); )
      if (preg_match("/^GRANT (.*?) ON (.*?) /", $grant["Grants for $sessionparts[1]@$sessionparts[2]"], $matches) && ($matches[1] == 'ALL PRIVILEGES' || preg_match("/\b$privilege\b/", $matches[1])) && (preg_match("/^(`$databasename`|\*)/", $matches[2])))
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
