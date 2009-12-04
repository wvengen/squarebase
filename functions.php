<?php
  include('inflection.php');

  function parameter($type, $name = null) {
    $arrays = array(
      'get'=>array_merge($_POST, $_GET),
      'post'=>array_merge($_GET, $_POST),
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
    return is_null($value) ? null : str_replace(array('\\"', '\\\''), array('"', '\''), $value);
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
      $pieces = array_merge($pieces, make_array($arg));

    return join($glue, array_filter($pieces, 'is_not_null'));
  }

  function first_non_null() {
    foreach (func_get_args() as $arg)
      if (!is_null($arg))
        return $arg;
    return null;
  }

  function array_show($array) {
    return preg_replace(array('@^Array\s*\(\s*(.*?)\s*\)\s*$@s', '@ *\n *@s'), array('$1', "\n"), print_r($array, true));
  }

  function html($tag, $attributes = array(), $text = null) {
    if ($_COOKIE['logsy']) {
        static $types = array( // in attributes: 0=required, 1=implied
        'html'    =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'head'    =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'title'   =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'script'  =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'type'=>0, 'src'=>0)),
        'body'    =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'pre'     =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'div'     =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'span'    =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'p'       =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'h1'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'h2'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'ol'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'ul'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'li'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'a'       =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'href'=>0)),
        'table'   =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'tr'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'th'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'colspan'=>1, 'rowspan'=>1)),
        'td'      =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'colspan'=>1, 'rowspan'=>1)),
        'form'    =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'action'=>0, 'enctype'=>1, 'method'=>0)),
        'fieldset'=>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'optgroup'=>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'label'=>0)),
        'label'   =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'for'=>1)),
        'select'  =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'name'=>0, 'readonly'=>1)),
        'option'  =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'value'=>1, 'selected'=>1)),
        'textarea'=>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1, 'name'=>0, 'rows'=>0, 'cols'=>0, 'readonly'=>1)),
        'strong'  =>array('empty'=>false, 'attributes'=>array('id'=>1, 'class'=>1)),
        'link'    =>array('empty'=>true,  'attributes'=>array('id'=>1, 'class'=>1, 'href'=>0, 'type'=>0, 'rel'=>0)),
        'img'     =>array('empty'=>true,  'attributes'=>array('id'=>1, 'class'=>1, 'src'=>0, 'alt'=>0, 'title'=>1)),
        'input'   =>array('empty'=>true,  'attributes'=>array('id'=>1, 'class'=>1, 'name'=>0, 'value'=>1, 'type'=>0, 'readonly'=>1, 'disabled'=>1, 'checked'=>1))
      );
      $type = $types[$tag];
      if ($type['empty'] === false) {
        if (is_null($text))
          addtolist('warnings', 'warning', sprintf(_('missing text for html tag %s'), $tag));
      }
      elseif ($type['empty'] === true) {
        if (!is_null($text))
          addtolist('warnings', 'warning', sprintf(_('text for html tag %s: %s'), $tag, $text));
      }
      else
        addtolist('warnings', 'warning', sprintf(_('unknown html tag %s'), $tag));
      $possible_attributes = array();
      foreach ($type['attributes'] as $attribute=>$value)
        $possible_attributes[$attribute] = $value;
      foreach ($attributes as $attribute=>$value)
        if ($attribute) {
          if (array_key_exists($attribute, $possible_attributes))
            $possible_attributes[$attribute]++;
          else
            addtolist('warnings', 'warning', sprintf(_('unknown attribute %s for tag %s'), $attribute, $tag));
        }
      foreach ($possible_attributes as $attribute=>$value)
        if ($value === 0)
          addtolist('warnings', 'warning', sprintf(_('missing mandatory attribute %s for tag %s'), $attribute, $tag));
    }
    $attributelist = array();
    foreach ($attributes as $attribute=>$value)
      if ($attribute && !is_null($value))
        $attributelist[] = " $attribute=\"$value\"";
    $starttag = $tag ? '<'.$tag.join($attributelist).(is_null($text) ? ' /' : '').'>' : '';
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

  function make_array($value) {
    return is_array($value) ? $value : array($value);
  }

  function http_response($headers, $content = null) {
    session_write_close();
    foreach (make_array($headers) as $header)
      header($header);
    print $content;
    exit;
  }

  function redirect($url) {
    http_response('Location: '.$url);
  }

  function internalredirect($parameters) {
    redirect(httpurl($parameters));
  }

  function call_function($querystring) {
    if (!$querystring)
      return;
    parse_str($querystring, $parameters);
    addtolist('logs', 'call', 'call_function: '.html('div', array('class'=>'arrayshow'), array_show($parameters)));
    $definitions = join(read_file($parameters['presentationname'] ? "presentation/$parameters[presentationname].php" : 'functions.php'));
    $definition = preg_match1("@\n *function +$parameters[functionname]\((.*?)\)@", $definitions);

    $function_parameter_list = array();
    if (preg_match_all('@(?:^|,) *\$(\w+)@', $definition, $function_parameter_names, PREG_SET_ORDER))
      foreach ($function_parameter_names as $function_parameter_name)
        $function_parameter_list[] = $parameters[$function_parameter_name[1]];

    if ($parameters['presentationname'])
      include_once("presentation/$parameters[presentationname].php");
    page($parameters['functionname'], null, call_user_func_array($parameters['functionname'], $function_parameter_list));
  }

  function back() {
    call_function(parameter('get', 'ajax'));
    redirect(first_non_null(parameter('get', 'back'), parameter('server', 'HTTP_REFERER')));
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
    if ($list == 'logs' && !$_COOKIE['logsy'])
      return;
    if (!$_SESSION[$list])
      $_SESSION[$list] = array();
    $_SESSION[$list][] = html('li', array('class'=>$class), $text);
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
      if (!$_SESSION['username'])
        internalredirect(array('action'=>'login'));
      $connection = @mysql_connect($_SESSION['host'], $_SESSION['username'], $_SESSION['password']);
      if (mysql_errno())
        logout(sprintf(_('problem connecting to the databasemanager: %s'), mysql_error()));
    }

    if (preg_match('@= *\'<\w+>\'@', $query))
      addtolist('warnings', 'warning', sprintf(_('wrong single quotes around value in query: %s'), $query));

    $fullquery = preg_replace('@(["`])?<(\w+)>(["`])?@e', '(is_null($arguments["$2"]) ? "NULL" : (is_bool($arguments["$2"]) ? ($arguments["$2"] ? "TRUE" : "FALSE") : (is_numeric($arguments["$2"]) ? (int) $arguments["$2"] : "$1".mysql_escape_string($arguments["$2"])."$3")))', $query);

    static $cache = array();
    $fromcache = array_key_exists($fullquery, $cache);
    if ($fromcache)
      $result = $cache[$fullquery];
    else {
      $before = microtime();
      $result = $cache[$fullquery] = mysql_query($fullquery);
      $after = microtime();
      list($beforemsec, $beforesec) = explode(' ', $before);
      list($aftermsec, $aftersec) = explode(' ', $after);
      $errno = mysql_errno();
    }

    $sqlcommand = preg_match1('@^[^A-Z]*([A-Z]+) @i', $fullquery);
    $numresults = preg_match('@^(EXPLAIN|SELECT|SHOW)$@i', $sqlcommand) && $result ? mysql_num_rows($result) : null;
    if (!is_null($numresults)) {
      $resultlist = array();
      for (mysql_data_reset($result); $resultrow = mysql_fetch_assoc($result); ) {
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
        ' '.
        ($fromcache
        ? '['._('from cache').']'
        : '['.sprintf(_('%.2f sec'), ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).']'
        ).
        ' '.internalreference(array('action'=>'explain_query', 'query'=>$fullquery), _('explain')).
        ' '.html('span', array('class'=>'traces'), join(' ', array_reverse($traces)))
      ).
      ($errno
      ? html('ul', array(), html('li', array(), $errno.'='.mysql_error()))
      : (!is_null($numresults)
        ? ($resultlist ? html('ol', array(), join($resultlist)) : '')
        : html('ul', array(), html('li', array(), sprintf($sqlcommand == 'INSERT' ? _('%d inserted') : ($sqlcommand == 'UPDATE' ? _('%d updated') : _('%d affected')), mysql_affected_rows($connection))))
        )
      )
    );
    if (preg_match('@#warning @', $fullquery))
      addtolist('warnings', 'warning', $fullquery);

    if ($result)
      return $result;
    switch ($errno) {
      case 1044: // Access denied for user '%s'@'%s' to database '%s'
        return null;
      case 1062: // Duplicate entry '%s' for key %d
        $error = mysql_error();
        $keyvalues = explode('-', preg_match1('@Duplicate entry \'(.*)\'@', $error));
        $keynr = preg_match1('@for key (\d+)@', $error);
        if ($keynr) {
          $databasename = preg_match1('@^INSERT INTO `(.*?)`@', $fullquery);
          $tablename    = preg_match1('@^INSERT INTO `.*?`\.`(.*?)`@', $fullquery);
          $keyfields = array();
          $keys = query($metaordata, 'SELECT seq_in_index, column_name FROM INFORMATION_SCHEMA.STATISTICS WHERE table_schema = "<databasename>" AND table_name = "<tablename>"', array('databasename'=>$databasename, 'tablename'=>$tablename));
          while ($key = mysql_fetch_assoc($keys)) {
            if ($key['seq_in_index'] == 1)
              $keynr--;
            if ($keynr == 0)
              $keyfields[] = $key['column_name'];
            if ($keynr < 0)
              break;
          }
          if (count($keyfields) == count($keyvalues)) {
            $combined = array();
            foreach ($keyfields as $num=>$keyfield)
              $combined[] = "$keyfield = $keyvalues[$num]";
            $keyfieldsandvalues = join(', ', $combined);
          }
          else
            $keyfieldsandvalues = join('-', $keyfields).' = '.join('-', $keyvalues);
          $warning = sprintf(_('%s with %s already exists'), ucfirst($tablename), $keyfieldsandvalues);
        }
        addtolist('warnings', 'warning', $warning ? $warning : $error);
        return null;
      case 1369: // CHECK OPTION failed '%s'
        $error = mysql_error();
        if (preg_match('@^CHECK OPTION failed \'(.*?)\.(.*?)\'$@', $error, $matches)) {
          $warning = _('not allowed to add a record with these values');
          $view = query01($metaordata, 'SELECT view_definition FROM INFORMATION_SCHEMA.VIEWS WHERE table_schema = "<databasename>" AND table_name = "<tablename>"', array('databasename'=>$matches[1], 'tablename'=>$matches[2]));
          if ($view && preg_match('@ where \(`(.*?)`\.`(.*?)`\.`(.*?)` = (.*?)\)+$@', $view['view_definition'], $where))
            $warning = sprintf(_('only allowed to add a record with %s = %s'), $where[3], preg_replace('@^_\w+@', '', $where[4]));
        }
        addtolist('warnings', 'warning', $warning ? $warning : $error);
        return null;
      default:
        error(_('problem while querying the databasemanager').html('p', array('class'=>'error'), "$errno: ".mysql_error()).$fullquery);
    }
  }

  function query01($metaordata, $query, $arguments = array()) {
    $results = query($metaordata, $query, $arguments);
    if (!$results || mysql_num_rows($results) == 0)
      return null;
    if (mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error(sprintf(_('problem because there are %s results'), mysql_num_rows($results)).html('p', array(), htmlentities($query)));
  }

  function query1($metaordata, $query, $arguments = array()) {
    $results = query($metaordata, $query, $arguments);
    if ($results && mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error(sprintf(_('problem because there are %s results'), $results ? mysql_num_rows($results) : 'no').html('p', array(), htmlentities($query)));
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

    http_response(
      array('Content-Type: text/html; charset=utf-8', 'Content-Language: '.get_locale()),
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>internalurl(array('action'=>'style', 'metabasename'=>parameter('get', 'metabasename'))), 'type'=>'text/css', 'rel'=>'stylesheet')).
          ($_COOKIE['scripty']
          ? html('script', array('type'=>'text/javascript', 'src'=>'jquery.min.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>'jquery.requirescript.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>internalurl(array('action'=>'script'))), '')
          : ''
          )
        ).
        html('body', array('class'=>join_clean(' ', preg_replace('@_@', '', $action), $_COOKIE['ajaxy'] ? 'ajaxy' : null)),
          html('div', array('id'=>'header'),
            html('div', array('id'=>'id'),
              html('ul', array(),
                html('li', array(),
                  array(
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internalreference(parameter('server', 'REQUEST_URI').'&scripty='.($_COOKIE['scripty'] ? 'off' : 'on'), $_COOKIE['scripty'] ? _('javascript is on') : _('javascript is off')) : ($_COOKIE['scripty'] ? _('javascript is on') : _('javascript is off')),
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? ($_COOKIE['scripty'] ? internalreference(parameter('server', 'REQUEST_URI').'&ajaxy='.($_COOKIE['ajaxy'] ? 'off' : 'on'), $_COOKIE['ajaxy'] ? _('ajax is on') : _('ajax is off')) : _('ajax is off')) : ($_COOKIE['ajaxy'] ? _('ajax is on') : _('ajax is off')),
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internalreference(parameter('server', 'REQUEST_URI').'&logsy='.($_COOKIE['logsy'] ? 'off' : 'on'), $_COOKIE['logsy'] ? _('logging is on') : _('logging is off')) : ($_COOKIE['logsy'] ? _('logging is on') : _('logging is off'))
                  )
                )
              ).
              html('ul', array(),
                html('li', array(),
                  array(
                    $_SESSION['username'] ? "$_SESSION[username]@$_SESSION[host]" : '&nbsp;',
                    $_SESSION['username'] ? internalreference(array('action'=>'logout'), 'logout') : '&nbsp;',
                    get_locale()
                  )
                )
              )
            ).
            html('h1', array('id'=>'title'), $title).
            ($path ? $path : '&nbsp;')
          ).
          html('div', array('id'=>'content'),
            ($error ?  html('div', array('class'=>'error'), $error) : '').
            html('ol', array('id'=>'warnings'), join(getlist('warnings'))).
            $content.
            ($_COOKIE['logsy'] ? html('ol', array('class'=>'logs'), join(getlist('logs'))) : '')
          ).
          html('div', array('id'=>'footer'),
            html('div', array('id'=>'poweredby'), externalreference('http://squarebase.org/', html('img', array('src'=>'powered_by_squarebase.png', 'alt'=>'powered by squarebase'))))
          )
        )
      )
    );
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
        $viewname = table_or_view($metabasename, $databasename, $tablename);
        $descriptor = descriptor($metabasename, $databasename, $tablename, $viewname);
        $uniquepart = query1field('data', "SELECT $descriptor[select] FROM `<databasename>`.`<viewname>` ".join(' ', $descriptor['joins'])."WHERE `<viewname>`.<uniquefieldname> = <uniquevalue>", array('databasename'=>$databasename, 'viewname'=>$viewname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
      }
      else
        $uniquepart = $uniquevalue;
    }
    else
      $uniquepart = null;
    return
      html('ul', array('id'=>'path', 'class'=>'compact'),
        html('li', array(), internalreference(array('action'=>'index'), 'index')).
        html('li', array('class'=>'notfirst'),
          array(
            !is_null($metabasename) ? (has_grant('DROP', $metabasename) ? internalreference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename) : $metabasename) : '&hellip;',
            !is_null($databasename) ? ($metabasename ? internalreference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename) : $databasename) : null,
            !is_null($tablename)    ? ($metabasename && $databasename && $uniquefieldname ? internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname), $tablename) : $tablename) : null,
            $uniquepart
          )
        )
      );
  }

  function list_table($metabasename, $databasename, $tablename, $tablenamesingular, $limit, $offset, $uniquefieldname, $uniquevalue, $orderfieldname, $orderasc = true, $foreignfieldname = null, $foreignvalue = null, $parenttablename = null, $interactive = true) {
    $viewname = table_or_view($metabasename, $databasename, $tablename);
    $originalorderfieldname = $orderfieldname;
    $joins = $selectnames = $ordernames = array();
    $can_insert = $can_quickadd = has_grant('INSERT', $databasename, $viewname, '?');
    $can_update = has_grant('UPDATE', $databasename, $viewname, '?');
    $header = $quickadd = array();
    $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname, 'SELECT', true);
    while ($field = mysql_fetch_assoc($fields)) {
      $can_quickadd = $can_quickadd && ($field['fieldid'] == $field['uniquefieldid'] || $field['nullallowed'] || $field['defaultvalue'] || ($field['inlist'] && $field['privilege_insert'])) && call_user_func("is_quickaddable_$field[presentationname]");

      if ($field['inlist']) {
        $selectnames[] = "$viewname.$field[fieldname] AS ${tablename}_$field[fieldname]";
        if ($field['foreigntablename']) {
          $foreignviewname = table_or_view($metabasename, $databasename, $field['foreigntablename']);
          $joins[] = "LEFT JOIN `$databasename`.$foreignviewname AS $field[foreigntablename]_$field[fieldname] ON $field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname] = $viewname.$field[fieldname]";
          $descriptor = descriptor($metabasename, $databasename, $field['foreigntablename'], "$field[foreigntablename]_$field[fieldname]");
          $selectnames[] = "$descriptor[select] AS $field[foreigntablename]_$field[fieldname]_descriptor";
          $joins = array_merge($joins, $descriptor['joins']);
          $ordernames = array_merge($ordernames, $descriptor['orders']);
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
        $quickadd[] = html('td', array('class'=>!is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? 'thisrecord' : null), call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, array_merge($field, array('uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)), !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? $foreignvalue : $field['defaultvalue'], (!is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname) || !$field['privilege_insert'], false));
      }
    }
    $header[] = html('th', array('class'=>'filler'), '');
    array_unshift($header, html('th', array(), ''));
    $quickadd[] = html('td', array('class'=>'filler'), '');
    array_unshift($quickadd, html('td', array(), $can_insert ? 'add' : ''));
    if ($ordernames)
      $ordernames[0] = $ordernames[0].' '.($orderasc ? 'ASC' : 'DESC');
    $records = query('data',
      "SELECT ".
      ($limit ? "SQL_CALC_FOUND_ROWS " : "").
      "$viewname.$uniquefieldname AS $uniquefieldname".
      ($selectnames ? ', '.join(', ', $selectnames) : '').
      " FROM `$databasename`.$viewname ".
      join(' ', array_unique($joins)).
      (!is_null($foreignvalue) ? " WHERE $viewname.$foreignfieldname = '$foreignvalue'" : '').
      ($ordernames ? " ORDER BY ".join(', ', $ordernames) : '').
      ($limit ? " LIMIT $limit".($offset ? " OFFSET $offset" : '') : '')
    );
    $foundrecords = $limit ? query1field('data', 'SELECT FOUND_ROWS()') : null;

    $rows = array(html('tr', array(), join($header)));
    while ($row = mysql_fetch_assoc($records)) {
      $columns = array();
      for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
        if ($field['inlist']) {
          $field['descriptor'] = $row["$field[foreigntablename]_$field[fieldname]_descriptor"];
          $field['thisrecord'] = !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname;
          $field['uniquefieldname'] = $uniquefieldname;
          $field['uniquevalue'] = $row[$uniquefieldname];
          $columns[] =
            html('td', array('class'=>join_clean(' ', 'column', $field['presentationname'], $field['thisrecord'] ? 'thisrecord' : null)),
              ''.call_user_func("list_$field[presentationname]", $metabasename, $databasename, $field, $row["${tablename}_$field[fieldname]"])
            );
        }
      }
      $columns[] = html('td', array('class'=>'filler'), '');
      $rows[] =
        html('tr', array('class'=>join_clean(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(), 
            $can_update 
            ? internalreference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('edit'))
            : internalreference(array('action'=>'show_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('show'))
          ).
          join($columns)
        );
    }
    if ($interactive && ($can_quickadd || $can_insert)) {
      $rows[] = $can_quickadd
      ? html('tr', array(), join($quickadd)).
        html('tr', array(),
          html('td', array(), '').
          html('td', array('colspan'=>count($quickadd) - 1),
            html('div', array(), 
              html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record', 'class'=>'mainsubmit')).
              html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit')).
              internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('full record')).
              (is_null($foreignvalue) ? '' : html('span', array('class'=>'changeslost'), _('(changes to form fields are lost)')))
            ).
            (is_null($uniquevalue) ? '' : ajaxcontent(edit_record('UPDATE', $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue)))
          )
        )
      : html('tr', array(),
          html('td', array('colspan'=>count($header)),
            html('div', array(), 
              internalreference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('add record'))
            )
          )
        );
    }

    if ($limit && $foundrecords > $limit) {
      $offsets = array();
      for ($otheroffset = 0; $otheroffset < $foundrecords; $otheroffset += $limit) {
        $title = sprintf($otheroffset + 1 < $foundrecords ? _('record %d till %d') : _('record %d'), $otheroffset + 1, min($otheroffset + $limit, $foundrecords));
        $page = round($otheroffset / $limit) + 1;
        $offsets[] = $offset == $otheroffset ? html('span', array('title'=>$title), $page) : internalreference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'offset'=>$otheroffset, 'orderfieldname'=>$originalorderfieldname, 'orderasc'=>$orderasc ? 'on' : ''), $page, array('class'=>'ajaxreload', 'title'=>$title));
      }
    }

    if (is_null($foreignvalue) || $offsets)
      $rows[] =
        html('tr', array(),
          html('td', array(), is_null($foreignvalue) ? internalreference(parameter('server', 'HTTP_REFERER'), 'close', array('class'=>'close')) : '').
          html('td', array('colspan'=>count($header) - 1),
            $offsets ? html('ol', array('class'=>'offsets'), html('li', array(), $offsets)) : ''
          )
        );

    return
      html('div', array('class'=>'ajax', 'id'=>http_build_query(array('action'=>'call_function', 'functionname'=>'list_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'limit'=>$limit, 'offset'=>$offset, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$orderfieldname, 'orderasc'=>$orderasc ? 'on' : '', 'foreignfieldname'=>$foreignfieldname, 'foreignvalue'=>$foreignvalue, 'parenttablename'=>$parenttablename, 'interactive'=>$interactive))),
        (count($rows) > 1
        ? form(
            html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
            html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
            html('input', array('type'=>'hidden', 'name'=>'tablename', 'value'=>$tablename)).
            html('input', array('type'=>'hidden', 'name'=>'tablenamesingular', 'value'=>$tablenamesingular)).
            html('input', array('type'=>'hidden', 'name'=>'uniquefieldname', 'value'=>$uniquefieldname)).
            html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
            html('table', array('class'=>join_clean(' ', $interactive ? 'box' : null, 'tablelist')), join($rows)) 
          )
        : ''
        )
      );
  }

  function edit_record($privilege, $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue, $referencedfromfieldname, $back = null) {
    $viewname = table_or_view($metabasename, $databasename, $tablename, $uniquefieldname, $uniquevalue);
    $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname, 'SELECT', true);

    if ($privilege != 'INSERT') {
      $fieldnames = array();
      while ($field = mysql_fetch_assoc($fields))
        if ($field['inedit'])
          $fieldnames[] = $field['fieldname'];
      $row = query1('data', 'SELECT '.join(', ', $fieldnames).' FROM `<databasename>`.`<viewname>` WHERE <uniquefieldname> = "<uniquevalue>"', array('databasename'=>$databasename, 'viewname'=>$viewname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue));
    }

    get_presentationnames();

    $lines = array(
      html('th', array('colspan'=>2, 'class'=>'heading'), $tablenamesingular).
      html('th', array('class'=>'filler'), '')
    );
    for (mysql_data_reset($fields); $field = mysql_fetch_assoc($fields); ) {
      if ($field['inedit'])
        $lines[] =
          html('td', array('class'=>'description'), html('label', array('for'=>"field:$field[fieldname]"), $field['title'])).
          html('td', array(), call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, array_merge($field, array('uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)), first_non_null(parameter('get', "field:$field[fieldname]"), $privilege == 'INSERT' ? $field['defaultvalue'] : $row[$field['fieldname']]), $privilege == 'SELECT' || ($privilege == 'INSERT' && (!$field['privilege_insert'] || parameter('get', "field:$field[fieldname]"))) || ($privilege == 'UPDATE' && !$field['privilege_update']), true)).
          html('td', array('class'=>'filler'), '');
    }

    $lines[] =
      html('td', array('class'=>'description'), '').
      html('td', array(),
        (($privilege == 'UPDATE' || $privilege == 'INSERT') && has_grant($privilege, $databasename, $viewname, '?') ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$privilege == 'UPDATE' ? 'update_record' : 'add_record', 'class'=>'mainsubmit')) : '').
        ($privilege == 'INSERT' && has_grant($privilege, $databasename, $viewname, '?') ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit')) : '').
        (($privilege == 'UPDATE' || $privilege == 'SELECT') && has_grant('DELETE', $databasename, $viewname) ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'delete_record', 'class'=>join_clean(' ', 'mainsubmit', 'delete'))) : '')
      ).
      html('td', array('class'=>'filler'), '');

    if (!is_null($uniquevalue)) {
      $referrers = array();
      $referringfields = query('meta', 'SELECT tbl.tablename, tbl.singular, fld.fieldname AS fieldname, fld.title AS title, mfu.fieldname AS uniquefieldname FROM `<metabasename>`.fields fld LEFT JOIN `<metabasename>`.tables mtf ON mtf.tableid = fld.foreigntableid LEFT JOIN `<metabasename>`.tables tbl ON tbl.tableid = fld.tableid LEFT JOIN `<metabasename>`.fields mfu ON tbl.uniquefieldid = mfu.fieldid WHERE mtf.tablename = "<tablename>"', array('metabasename'=>$metabasename, 'tablename'=>$tablename));
      while ($referringfield = mysql_fetch_assoc($referringfields)) {
        $viewname = table_or_view($metabasename, $databasename, $referringfield['tablename']);
        if ($viewname)
          $referrers[] =
            html('tr', array(),
              html('td', array(),
                $referringfield['tablename'].
                ($referringfield['title'] == $tablenamesingular ? '' : html('span', array('class'=>'referrer'), sprintf(_('via %s'), $referringfield['title'])))
              ).
              html('td', array(),
                list_table($metabasename, $databasename, $referringfield['tablename'], $referringfield['singular'], 0, 0, $referringfield['uniquefieldname'], null, null, true, $referringfield['fieldname'], $uniquevalue, $tablename, $privilege != 'SELECT')
              ).
              html('td', array('class'=>'filler'), '')
            );
      }
    }

    return
      html('div', array('class'=>'box'),
        form(
          html('input', array('type'=>'hidden', 'name'=>'metabasename', 'value'=>$metabasename)).
          html('input', array('type'=>'hidden', 'name'=>'databasename', 'value'=>$databasename)).
          html('input', array('type'=>'hidden', 'name'=>'tablename', 'value'=>$tablename)).
          html('input', array('type'=>'hidden', 'name'=>'tablenamesingular', 'value'=>$tablenamesingular)).
          html('input', array('type'=>'hidden', 'name'=>'uniquefieldname', 'value'=>$uniquefieldname)).
          html('input', array('type'=>'hidden', 'name'=>'uniquevalue', 'value'=>$uniquevalue)).
          html('input', array('type'=>'hidden', 'name'=>'referencedfromfieldname', 'value'=>$referencedfromfieldname)).
          html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>$back ? $back : parameter('server', 'HTTP_REFERER'))).
          html('table', array('class'=>'tableedit'), html('tr', array(), $lines))
        ).
        ($referrers ? html('table', array('class'=>'referringlist'), join($referrers)) : '').
        ($privilege == 'SELECT' || !has_grant($privilege, $databasename, $viewname, '?')
        ? internalreference($back ? $back : parameter('server', 'HTTP_REFERER'), _('close'), array('class'=>'close'))
        : internalreference($back ? $back : parameter('server', 'HTTP_REFERER'), _('cancel'), array('class'=>'cancel'))
        )
      );
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

  function table_or_view($metabasename, $databasename, $tablename, $uniquefieldname = null, $uniquevalue = null) {
    static $alternatives = array();
    if (!$alternatives[$metabasename][$databasename]) {
      $views = query('meta',
        '('.
          'SELECT tablename, tablename AS viewname '.
          'FROM `<metabasename>`.tables '.
          'LEFT JOIN INFORMATION_SCHEMA.TABLES tbl ON tbl.table_schema = "<databasename>" AND tbl.table_name = tablename '.
          'WHERE table_name IS NOT NULL'.
        ') '.
        'UNION '.
        '('.
          'SELECT tablename, viewname '.
          'FROM `<metabasename>`.views '.
          'LEFT JOIN `<metabasename>`.tables ON tables.tableid = views.tableid '.
          'LEFT JOIN INFORMATION_SCHEMA.TABLES tbl ON tbl.table_schema = "<databasename>" AND tbl.table_name = viewname '.
          'WHERE table_name IS NOT NULL'.
        ')',
        array('metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename)
      );
      while ($view = mysql_fetch_assoc($views))
        $alternatives[$metabasename][$databasename][$view['tablename']][] = $view['viewname'];
    }
    if ($alternatives[$metabasename][$databasename][$tablename][0] == $tablename || is_null($uniquefieldname))
      return $alternatives[$metabasename][$databasename][$tablename][0];
    foreach ($alternatives[$metabasename][$databasename][$tablename] as $viewname)
      if (query1field('data', 'SELECT COUNT(*) FROM `<databasename>`.`<viewname>` WHERE <uniquefieldname> = <uniquevalue>', array('databasename'=>$databasename, 'viewname'=>$viewname, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)))
        return $viewname;
    return null;
  }

  function fields_from_table($metabasename, $databasename, $tablename, $viewname, $privilege = 'SELECT', $allprivileges = false) {
    $selectparts = $joinparts = array();
    foreach (array('SELECT', 'INSERT', 'UPDATE') as $oneprivilege) {
      if ($allprivileges || $privilege == $oneprivilege) {
        $letter = strtolower($oneprivilege{0});
        $selectparts[] = "COALESCE(u$letter.privilege_type, s$letter.privilege_type, t$letter.privilege_type, c$letter.privilege_type) AS privilege_".strtolower($oneprivilege);
        $joinparts[] = 
          "LEFT JOIN INFORMATION_SCHEMA.USER_PRIVILEGES   u$letter ON u$letter.privilege_type = \"$oneprivilege\" AND u$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") ".
          "LEFT JOIN INFORMATION_SCHEMA.SCHEMA_PRIVILEGES s$letter ON s$letter.privilege_type = \"$oneprivilege\" AND s$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND s$letter.table_schema = \"<databasename>\" ".
          "LEFT JOIN INFORMATION_SCHEMA.TABLE_PRIVILEGES  t$letter ON t$letter.privilege_type = \"$oneprivilege\" AND t$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND t$letter.table_schema = \"<databasename>\" AND t$letter.table_name = \"<viewname>\" ".
          "LEFT JOIN INFORMATION_SCHEMA.COLUMN_PRIVILEGES c$letter ON c$letter.privilege_type = \"$oneprivilege\" AND c$letter.grantee IN (\"'<username>'@'<host>'\", \"'<username>'@'%'\") AND c$letter.table_schema = \"<databasename>\" AND c$letter.table_name = \"<viewname>\" AND c$letter.column_name = fld.fieldname ";
        if ($privilege == $oneprivilege)
          $wherepart = "COALESCE(u$letter.privilege_type, s$letter.privilege_type, t$letter.privilege_type, c$letter.privilege_type) IS NOT NULL ";
      }
    }
    return query('meta',
      'SELECT '.
        join_clean(', ',
          ($viewname == $tablename ? 'tbl.tablename' : 'vw.viewname').' AS viewname',
          'tbl.tablename',
          'tbl.singular',
          'tbl.plural',
          'tbl.tableid',
          'tbl.uniquefieldid',
          'fld.fieldid',
          'fld.fieldname',
          'fld.title',
          'fld.type',
          'pst.presentationname',
          'fld.nullallowed',
          'fld.defaultvalue',
          'fld.indesc',
          'fld.inlist',
          'fld.inedit',
          'ftbl.tablename AS foreigntablename',
          'ftbl.singular AS foreigntablenamesingular',
          'ffld.fieldname AS foreignuniquefieldname',
          $selectparts
        ).' '.
      ($viewname == $tablename
      ? 'FROM `<metabasename>`.tables tbl '
      : 'FROM `<metabasename>`.views vw '.
        'LEFT JOIN `<metabasename>`.tables tbl ON tbl.tableid = vw.tableid '
      ).
      'RIGHT JOIN `<metabasename>`.fields fld ON fld.tableid = tbl.tableid '.
      'LEFT JOIN `<metabasename>`.presentations pst ON pst.presentationid = fld.presentationid '.
      'LEFT JOIN `<metabasename>`.tables ftbl ON ftbl.tableid = fld.foreigntableid '.
      'LEFT JOIN `<metabasename>`.fields ffld ON ffld.fieldid = ftbl.uniquefieldid '.
      join($joinparts).
      'WHERE '.
        'tbl.tablename = "<tablename>" '.
        ($viewname == $tablename ? '' : 'AND vw.viewname = "<viewname>" ').
        'AND '.  $wherepart.
      'ORDER BY fld.fieldid',
      array('metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'viewname'=>$viewname, 'username'=>$_SESSION['username'], 'host'=>$_SESSION['host'])
    );
  }

  function descriptor($metabasename, $databasename, $tablename, $tablealias, $stack = array()) {
    static $descriptors = array();
    $viewname = table_or_view($metabasename, $databasename, $tablename);
    if (!$descriptors[$viewname]) {
      $selects = $joins = $orders = array();
      $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname);
      while ($field = mysql_fetch_assoc($fields)) {
        if ($field['indesc']) {
          include_once("presentation/$field[presentationname].php");
          $selectnames[] = "$viewname.$field[fieldname] AS ${viewname}_$field[fieldname]";
          if ($field['foreigntablename'] && !in_array($field['foreigntablename'], $stack)) {
            $foreignviewname = table_or_view($metabasename, $databasename, $field['foreigntablename']);
            $joins[] = "LEFT JOIN `$databasename`.$foreignviewname AS {tablealias}_$field[foreigntablename]_$field[fieldname] ON {tablealias}_$field[foreigntablename]_$field[fieldname].$field[foreignuniquefieldname] = {tablealias}.$field[fieldname] ";
            $descriptor = descriptor($metabasename, $databasename, $field['foreigntablename'], "{tablealias}_$field[foreigntablename]_$field[fieldname]", array_merge($stack, array($field['foreigntablename'])));
            $selects[] = $descriptor['select'];
            $orders = array_merge($orders, $descriptor['orders']);
            $joins = array_merge($joins, $descriptor['joins']);
          }
          else {
            $selects[] = first_non_null(@call_user_func("formattedsql_$field[presentationname]", "{tablealias}.$field[fieldname]"), "{tablealias}.$field[fieldname]");
            $orders[] = "{tablealias}.$field[fieldname]";
          }
        }
      }
      $descriptors[$viewname] = array(
        'select'=>count($selects) == 1 ? $selects[0] : 'CONCAT_WS(" ", '.join(', ', $selects).')',
        'joins' =>$joins,
        'orders'=>$orders
      );
    }
    return array(
      'select'=>preg_replace('@{tablealias}@', $tablealias, $descriptors[$viewname]['select']),
      'joins' =>preg_replace('@{tablealias}@', $tablealias, $descriptors[$viewname]['joins']),
      'orders'=>preg_replace('@{tablealias}@', $tablealias, $descriptors[$viewname]['orders'])
    );
  }

  function set_cookie($name, $value) {
    static $expire = null;
    if (!$expire)
      $expire = time() + 365 * 24 * 60 * 60;
    setcookie($name, $value, $expire);
    $_COOKIE[$name] = $value;
  }

  function set_preference($item, $default) {
    $value = first_non_null(parameter('get', $item) == 'on' ? 1 : null, parameter('get', $item) == 'off' ? 0 : null, $_COOKIE[$item], $default);
    if ($value !== $_COOKIE[$item]) {
      set_cookie($item, $value);
      redirect(preg_replace("@&$item=\w+@", '', parameter('server', 'REQUEST_URI')));
    }
  }

  function forget($usernameandhost) {
    set_cookie('lastusernamesandhosts', join_clean(',', array_diff(explode(',', $_COOKIE['lastusernamesandhosts']), array($usernameandhost))));
  }

  function login($username, $host, $password, $language) {
    $_SESSION['username'] = $username;
    $_SESSION['host']     = $host;
    $_SESSION['password'] = $password;
    set_cookie('language', $language);
    set_cookie('lastusernamesandhosts', join_clean(',', array_diff(array_unique(array_merge(array("$username@$host"), array_diff(explode(',', $_COOKIE['lastusernamesandhosts']), array("$username@$host")))), array(''))));
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

    $metabasename = parameter('get', 'metabasename');
    if ($metabasename)
      $content .= join(read_file("metabase/$metabasename.css"));

    http_response("Content-Type: $content_type", $content);
  }

  function change_datetime_format($value, $from, $to) {
    if (!$value)
      return $value;
    $matches = strptime($value, $from);
    return strftime($to, mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'] + 1, $matches['tm_mday'], 1900 + $matches['tm_year']));
  }

  function find_datetime_format($format, $dest) {
    $subformats = array(
      array('php'=>'%d', 'jquery'=>'dd', 'text'=>_('dd'),    'mysql'=>'%d'), //%d = Two-digit day of the month (with leading zeros) = 01 to 31
      array('php'=>'%e', 'jquery'=>'d',  'text'=>_('d'),     'mysql'=>'%e'), //%e = Day of the month, with a space preceding single digits = 1 to 31
      array('php'=>'%b', 'jquery'=>'M',  'text'=>_('mon'),   'mysql'=>'%b'), //%b = Abbreviated month name, based on the locale = Jan through Dec
      array('php'=>'%B', 'jquery'=>'MM', 'text'=>_('month'), 'mysql'=>'%M'), //%B = Full month name, based on the locale = January through December
      array('php'=>'%m', 'jquery'=>'mm', 'text'=>_('mm'),    'mysql'=>'%m'), //%m = Two digit representation of the month = 01 (for January) through 12 (for December)
      array('php'=>'%y', 'jquery'=>'y',  'text'=>_('yy'),    'mysql'=>'%y'), //%y = Two digit representation of the year = Example: 09 for 2009, 79 for 1979
      array('php'=>'%Y', 'jquery'=>'yy', 'text'=>_('yyyy'),  'mysql'=>'%Y'), //%Y = Four digit representation for the year = Example: 2038
      array('php'=>'%H', 'jquery'=>'',   'text'=>_('hh'),    'mysql'=>'%H'), //%H = Two digit representation of the hour in 24-hour format = 00 through 23
      array('php'=>'%I', 'jquery'=>'',   'text'=>_('hh'),    'mysql'=>'%h'), //%I = Two digit representation of the hour in 12-hour format = 01 through 12
      array('php'=>'%l', 'jquery'=>'',   'text'=>_('hh'),    'mysql'=>'%l'), //%l = Hour in 12-hour format, with a space preceeding single digits = 1 through 12
      array('php'=>'%M', 'jquery'=>'',   'text'=>_('mm'),    'mysql'=>'%i'), //%M = Two digit representation of the minute = 00 through 59
      array('php'=>'%p', 'jquery'=>'',   'text'=>_('AM/PM'), 'mysql'=>'%p'), //%p = UPPER-CASE 'AM' or 'PM' based on the given time = Example: AM for 00:31, PM for 22:23
      array('php'=>'%P', 'jquery'=>'',   'text'=>_('am/pm'), 'mysql'=>'%p'), //%P = lower-case 'am' or 'pm' based on the given time = Example: am for 00:31, pm for 22:23
      array('php'=>'%S', 'jquery'=>'',   'text'=>_('ss'),    'mysql'=>'%S')  //%S = Two digit representation of the second = 00 through 59
    );
    $matches = array('tm_hour'=>23, 'tm_min'=>34, 'tm_sec'=>45, 'tm_mon'=>4, 'tm_mday'=>2, 'tm_year'=>2003);
    $date = mktime($matches['tm_hour'], $matches['tm_min'], $matches['tm_sec'], $matches['tm_mon'], $matches['tm_mday'], $matches['tm_year']);
    $output = strftime($format, $date);
    foreach ($subformats as $subformat) {
      $result = strftime($subformat['php'], $date);
      if ($result)
        $output = preg_replace("@\b$result\b@", $subformat[$dest], $output);
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
