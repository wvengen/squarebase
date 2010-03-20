<?php
  /*
    Copyright 2009,2010 Frans Reijnhoudt

    This file is part of Squarebase.

    Squarebase is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Squarebase is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program. If not, see <http://www.gnu.org/licenses/>.
  */

  include('inflection.php');

  umask(0177); // => maximum = rw-.---.---

  set_preference('scripty', 1);
  set_preference('ajaxy', 1);
  set_preference('logsy', 0);

  error_reporting(php_sapi_name() == 'cli' || parameter('cookie', 'logsy') ? E_ALL : 0);

  if (parameter('cookie', 'logsy')) {
    if (parameter('get') && parameter('post'))
      error(_('both get and post parameters'));
    $parametersource = parameter('post') ? 'post' : 'get';
    add_log($parametersource, $parametersource.': '.html('div', array('class'=>'arrayshow'), array_show(parameter($parametersource))));
    add_log('cookie', 'cookie: '.html('div', array('class'=>'arrayshow'), array_show(parameter('cookie'))));
  }
 
  function is_local() {
    return parameter('server', 'HTTP_HOST') == 'localhost';
  }

  function parameter($type, $name = null, $new_value = null, $default = null) {
    static $arrays = null;
    if (!$arrays)
      $arrays = array(
        'get'=>$_GET,
        'post'=>$_POST,
        'get_or_post'=>$_GET ? $_GET : $_POST,
        'server'=>$_SERVER,
        'files'=>$_FILES,
        'session'=>$_SESSION,
        'cookie'=>$_COOKIE
      );
    $array = isset($arrays[$type]) ? $arrays[$type] : array();
    if (is_null($name)) {
      if (!is_null($new_value)) {
        $arrays[$type] = $new_value;
        if ($type == 'session')
          $_SESSION = $new_value;
      }
      return $array;
    }
    if (!is_null($new_value)) {
      $arrays[$type][$name] = $new_value;
      if ($type == 'cookie')
        setcookie($name, $new_value, time() + 365 * 24 * 60 * 60);
      if ($type == 'session')
        $_SESSION[$name] = $new_value;
    }
    $value = isset($array[$name]) ? $array[$name] : null;
    return is_null($value) ? $default : str_replace(array('\\"', '\\\''), array('"', '\''), $value);
  }

  function is_non_null($var) {
    return !is_null($var);
  }

  function join_non_null() {
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

    return join($glue, array_filter($pieces, 'is_non_null'));
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

  /* all atribute names and values will be encoded using htmlentities; the text however won't, because it may contain other HTML code from previous calls to this function */
  function html($tag, $attributes = array(), $text = null) {
    if (parameter('cookie', 'logsy')) {
      static $types = array( // in attributes: 0=required, 1=optional
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
        'input'   =>array('empty'=>true,  'attributes'=>array('id'=>1, 'class'=>1, 'name'=>0, 'value'=>1, 'type'=>0, 'readonly'=>1, 'disabled'=>1, 'checked'=>1, 'title'=>1))
      );
      $type = $types[$tag];
      if ($type['empty'] === false) {
        if (is_null($text))
          add_log('warning', sprintf(_('missing text for html tag %s'), $tag));
      }
      elseif ($type['empty'] === true) {
        if (!is_null($text))
          add_log('warning', sprintf(_('text for html tag %s: %s'), $tag, $text));
      }
      else
        add_log('warning', sprintf(_('unknown html tag %s'), $tag));
      $possible_attributes = array();
      foreach ($type['attributes'] as $attribute=>$value)
        $possible_attributes[$attribute] = $value;
      foreach ($attributes as $attribute=>$value)
        if ($attribute) {
          if (array_key_exists($attribute, $possible_attributes))
            $possible_attributes[$attribute]++;
          else
            add_log('warning', sprintf(_('unknown attribute %s for tag %s'), $attribute, $tag));
        }
      foreach ($possible_attributes as $attribute=>$value)
        if ($value === 0)
          add_log('warning', sprintf(_('missing required attribute %s for tag %s'), $attribute, $tag));
    }
    $attributelist = array();
    foreach ($attributes as $attribute=>$value)
      if ($attribute && !is_null($value))
        $attributelist[] = htmlentities($attribute).'="'.htmlentities($value).'"';
    $starttag = $tag ? '<'.$tag.($attributelist ? ' '.join(' ', $attributelist) : '').(is_null($text) ? ' /' : '').'>' : '';
    $endtag = $tag ? "</$tag>" : '';
    return $starttag.(is_null($text) ? '' : (is_array($text) ? join_non_null($endtag.$starttag, $text) : $text).$endtag);
  }

  function directory_part($part) {
    return preg_match1('@(\w+\.?\w*)$@', $part);
  }

  function file_name($parts) {
    return join('/', array_map('directory_part', $parts));
  }

  function http_parse_query($query) {
    if (is_null($query))
      return null;
    parse_str(preg_replace('@^.*\?@', '', $query), $parameters);
    return $parameters;
  }

  function http_url($parameters = null) {
    return parameter('server', 'SCRIPT_NAME').($parameters ? '?'.http_build_query($parameters) : '');
  }

  function internal_url($parameters) {
    return http_url($parameters);
  }

  function external_reference($url, $text) {
    return html('a', array('href'=>$url), $text);
  }

  function internal_reference($parameters, $text, $extra = array()) {
    return html('a', array_merge($extra, array('href'=>internal_url($parameters))), $text);
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

  function internal_redirect($parameters) {
    http_response('Location: '.http_url($parameters));
  }

  function include_phpfile($parts) {
    include_once(file_name($parts));
  }

  function include_presentation($presentationname) {
    include_phpfile(array('presentation', "$presentationname.php"));
  }

  function call_function($querystring) {
    if (!$querystring)
      return;
    $parameters = http_parse_query($querystring);
    if (parameter('cookie', 'logsy'))
      add_log('call', 'call_function: '.html('div', array('class'=>'arrayshow'), array_show($parameters)));
    $definitions = join(read_file($parameters['presentationname'] ? array('presentation', $parameters['presentationname'].'.php') : array('functions.php')));
    $definition = preg_match1("@\n *function +$parameters[functionname]\((.*?)\)@", $definitions);

    $function_parameter_list = array();
    if (preg_match_all('@(?:^|,) *\$(\w+)@', $definition, $function_parameter_names, PREG_SET_ORDER))
      foreach ($function_parameter_names as $function_parameter_name)
        $function_parameter_list[] = $parameters[$function_parameter_name[1]];

    if ($parameters['presentationname'])
      include_presentation($parameters['presentationname']);
    page($parameters['functionname'], null, call_user_func_array($parameters['functionname'], $function_parameter_list));
  }

  function back() {
    call_function(parameter('get_or_post', 'ajax'));
    internal_redirect(http_parse_query(first_non_null(parameter('get_or_post', 'back'), parameter('server', 'HTTP_REFERER'))));
  }

  function error($error) {
    if (parameter('cookie', 'logsy')) {
      $stack = debug_backtrace();
      $mainpath = preg_match1('@^.*/@', $stack[0]['file']);
      $traces = array();
      foreach ($stack as $element) {
        $args = array();
        if ($element['args']) {
          foreach ($element['args'] as $arg)
            $args[] = preg_replace('@<(.*?)>@', '&lt;$1&gt;', "'$arg'");
        }
        $traces[] = html('div', array('class'=>'trace'), preg_replace("@$mainpath@", '', "$element[file]:$element[line] $element[function](".join(',', $args).")"));
      }
    }
    page('error', null,
      html('div', array('id'=>'error'), $error).
      ($traces ? html('p', array('class'=>'trace'), html('ol', array(), html('li', array(), $traces))) : '')
    );
    exit;
  }

  function add_log($class, $text) {
    $list = $class == 'warning' ? 'warnings' : 'logs';
    parameter('session', $list, array_merge(parameter('session', $list) ? parameter('session', $list) : array(), array(html('li', array('class'=>$class), $text))));
  }

  function get_logs($list) {
    return parameter('session', $list, array(), array());
  }

  function query($metaordata, $query, $arguments = array(), $connection = null) {
    static $session_connection = null;
    if (!$connection) {
      if (!$session_connection) {
        if (!extension_loaded('mysql'))
          error(_('mysql module not found'));
        if (!parameter('session', 'username'))
          internal_redirect(array('action'=>'login'));
        $session_connection = @mysql_connect(parameter('session', 'host'), parameter('session', 'username'), parameter('session', 'password'));
        if (mysql_errno())
          logout(sprintf(_('problem connecting to the database manager: %s'), mysql_error()));
      }
      $connection = $session_connection;
    }

    if (preg_match('@= *\'<\w+>\'@', $query))
      add_log('warning', sprintf(_('wrong single quotes around value in query: %s'), $query));

    $fullquery = preg_replace('@(["`])?<(\w+)>(["`])?@e', '(is_null($arguments["$2"]) ? "NULL" : (is_bool($arguments["$2"]) ? ($arguments["$2"] ? "TRUE" : "FALSE") : (is_numeric($arguments["$2"]) ? (int) $arguments["$2"] : "$1".mysql_escape_string($arguments["$2"])."$3")))', $query);

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
      for (mysql_data_reset($result); $resultrow = mysql_fetch_assoc($result); ) {
        if (count($resultlist) == 10 - 1 && $numresults > 10) {
          $resultlist[] = html('li', array('class'=>'arrayshow'), "&hellip; $numresults.");
          break;
        }
        $resultlist[] = html('li', array('class'=>'arrayshow'), array_show($resultrow));
      }
      mysql_data_reset($result);
    }

    if (parameter('cookie', 'logsy')) {
      $stack = debug_backtrace();
      $traces = array();
      foreach ($stack as $element) {
        $traces[] = (isset($element['file']) ? preg_match1('@\/(\w+)\.php$@', $element['file']) : '').(isset($element['line']) ? '#'.$element['line'] : '').':'.$element['function'];
      }

      add_log("query$metaordata",
        html('div', array('class'=>'query'),
          preg_replace(
            array('@<@' , '@>@' , '@& @'  ),
            array('&lt;', '&gt;', '&amp; '),
            $fullquery
          ).
          ' '.'['.sprintf(_('%.2f sec'), ($aftersec + $aftermsec) - ($beforesec + $beforemsec)).']'.
          ' '.internal_reference(array('action'=>'explain_query', 'query'=>$fullquery), _('explain')).
          ' '.html('span', array('class'=>'traces'), join(' ', array_reverse($traces)))
        ).
        (isset($errno) && $errno != '0'
        ? html('ul', array(), html('li', array(), $errno.'='.mysql_error()))
        : (!is_null($numresults)
          ? ($resultlist ? html('ol', array(), join($resultlist)) : '')
          : html('ul', array(), html('li', array(), sprintf($sqlcommand == 'INSERT' ? _('%d inserted') : ($sqlcommand == 'UPDATE' ? _('%d updated') : _('%d affected')), mysql_affected_rows($connection))))
          )
        )
      );
    }

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
        add_log('warning', $warning ? $warning : $error);
        return null;
      case 1369: // CHECK OPTION failed '%s'
        $error = mysql_error();
        if (preg_match('@^CHECK OPTION failed \'(.*?)\.(.*?)\'$@', $error, $matches)) {
          $warning = _('not allowed to add a record with these values');
          $view = query01($metaordata, 'SELECT view_definition FROM INFORMATION_SCHEMA.VIEWS WHERE table_schema = "<databasename>" AND table_name = "<tablename>"', array('databasename'=>$matches[1], 'tablename'=>$matches[2]));
          if ($view && preg_match('@ where \(`(.*?)`\.`(.*?)`\.`(.*?)` = (.*?)\)+$@', $view['view_definition'], $where))
            $warning = sprintf(_('only allowed to add a record with %s = %s'), $where[3], preg_replace('@^_\w+@', '', $where[4]));
        }
        add_log('warning', $warning ? $warning : $error);
        return null;
      default:
        error(_('problem while querying the database manager').html('p', array(), "$errno: ".mysql_error()).$fullquery);
    }
  }

  function query01($metaordata, $query, $arguments = array(), $connection = null) {
    $results = query($metaordata, $query, $arguments, $connection);
    if (!$results || mysql_num_rows($results) == 0)
      return null;
    if (mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error(sprintf(_('problem because there are %s results'), mysql_num_rows($results)).html('p', array(), htmlentities($query)));
  }

  function query1($metaordata, $query, $arguments = array(), $connection = null) {
    $results = query($metaordata, $query, $arguments, $connection);
    if ($results && mysql_num_rows($results) == 1)
      return mysql_fetch_assoc($results);
    error(sprintf(_('problem because there are %s results'), $results ? mysql_num_rows($results) : 'no').html('p', array(), htmlentities($query)));
  }

  function query1field($metaordata, $query, $arguments = array(), $field = null, $connection = null) {
    $result = query1($metaordata, $query, $arguments, $connection);
    return is_null($field) ? (count($result) == 1 ? array_shift(array_values($result)) : error(sprintf(_('problem retrieving 1 field, because there are %s fields'), count($result)))) : $result[$field];
  }

  function ajaxcontent($content) {
    return html('div', array('class'=>'ajaxcontent'), html('div', array('class'=>'ajaxcontainer'), $content));
  }

  function page($action, $breadcrumbs, $content) {
    $title = str_replace('_', ' ', $action);

    $error = parameter('get', 'error');

    http_response(
      array('Content-Type: text/html; charset=utf-8', 'Content-Language: '.get_locale()),
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'.
      html('html', array(),
        html('head', array(),
          html('title', array(), $title).
          html('link', array('href'=>internal_url(array('action'=>'style', 'metabasename'=>parameter('get', 'metabasename'))), 'type'=>'text/css', 'rel'=>'stylesheet')).
          (parameter('cookie', 'scripty')
          ? html('script', array('type'=>'text/javascript', 'src'=>'jquery.min.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>'jquery.requirescript.js'), '').
            html('script', array('type'=>'text/javascript', 'src'=>internal_url(array('action'=>'script', 'metabasename'=>parameter('get', 'metabasename')))), '')
          : ''
          )
        ).
        html('body', array('class'=>join_non_null(' ', preg_replace('@_@', '', $action), parameter('cookie', 'ajaxy') ? 'ajaxy' : null)),
          html('div', array('id'=>'header'),
            html('div', array('id'=>'id'),
              (is_local()
              ? html('ul', array(),
                  html('li', array('id'=>'togglescripty'),
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internal_reference(array_merge(parameter('get'), array('scripty'=>parameter('cookie', 'scripty') ? 'off' : 'on')), parameter('cookie', 'scripty') ? _('javascript is on') : _('javascript is off')) : (parameter('cookie', 'scripty') ? _('javascript is on') : _('javascript is off'))
                  ).
                  html('li', array('id'=>'toggleajaxy'),
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? (parameter('cookie', 'scripty') ? internal_reference(array_merge(parameter('get'), array('ajaxy'=>parameter('cookie', 'ajaxy') ? 'off' : 'on')), parameter('cookie', 'ajaxy') ? _('ajax is on') : _('ajax is off')) : _('ajax is off')) : (parameter('cookie', 'ajaxy') ? _('ajax is on') : _('ajax is off'))
                  ).
                  html('li', array('id'=>'togglelogsy'),
                    preg_match('@\?@', parameter('server', 'REQUEST_URI')) ? internal_reference(array_merge(parameter('get'), array('logsy'=>parameter('cookie', 'logsy') ? 'off' : 'on')), parameter('cookie', 'logsy') ? _('logging is on') : _('logging is off')) : (parameter('cookie', 'logsy') ? _('logging is on') : _('logging is off'))
                  )
                )
              : ''
              ).
              html('ul', array(),
                html('li', array('id'=>'currentusernameandhost'),
                  parameter('session', 'username') ? parameter('session', 'username').'@'.parameter('session', 'host') : ''
                ).
                html('li', array('id'=>'logout'),
                  parameter('session', 'username') ? internal_reference(array('action'=>'logout'), 'logout') : ''
                ).
                html('li', array('id'=>'locale'),
                  get_locale()
                )
              )
            ).
            html('h1', array('id'=>'title'), $title).
            ($breadcrumbs ? $breadcrumbs : '&nbsp;')
          ).
          html('div', array('id'=>'content'),
            ($error ?  html('div', array('id'=>'error'), $error) : '').
            html('ol', array('id'=>'warnings'), join(get_logs('warnings'))).
            $content.
            (parameter('cookie', 'logsy') ? html('ol', array('class'=>'logs'), join(get_logs('logs'))) : '')
          ).
          html('div', array('id'=>'footer'),
            html('div', array('id'=>'poweredby'), external_reference('http://squarebase.org/', html('img', array('src'=>'powered_by_squarebase.png', 'alt'=>'powered by squarebase'))))
          )
        )
      )
    );
  }

  function form($content, $method = 'get') {
    return html('form', array('action'=>http_url(), 'enctype'=>'multipart/form-data', 'method'=>$method), $content);
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

  function breadcrumbs($metabasename, $databasename = null, $tablename = null, $uniquefieldname = null, $uniquevalue = null) {
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
      html('ul', array('id'=>'breadcrumbs', 'class'=>'compact'),
        html('li', array(), internal_reference(array('action'=>'index'), 'index')).
        html('li', array('class'=>'notfirst'),
          array(
            !is_null($metabasename) ? (has_grant('DROP', $metabasename) ? internal_reference(array('action'=>'form_metabase_for_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename), $metabasename) : $metabasename) : '&hellip;',
            !is_null($databasename) ? ($metabasename ? internal_reference(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'back'=>parameter('server', 'REQUEST_URI')), $databasename) : $databasename) : null,
            !is_null($tablename)    ? ($metabasename && $databasename && $uniquefieldname ? internal_reference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'uniquefieldname'=>$uniquefieldname), $tablename) : $tablename) : null,
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
      include_presentation($field['presentationname']);
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

        $header[] =
          html('th', array('class'=>join_non_null(' ', $field['presentationname'], !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? 'thisrecord' : null)),
            !is_null($foreignvalue) || !call_user_func("is_sortable_$field[presentationname]")
            ? $field['title']
            : internal_reference(
                array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'orderfieldname'=>$field['fieldname'], 'orderasc'=>$field['fieldname'] == $orderfieldname ? ($orderasc ? '' : 'on') : 'on'),
                $field['title'].($field['fieldname'] == $orderfieldname ? ' '.($orderasc ? '&#x25be;' : '&#x25b4;') : ''),
                array('class'=>'ajaxreload')
              )
          );
        $quickadd[] = html('td', array('class'=>join_non_null(' ', 'quickadd', !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? 'thisrecord' : null)), call_user_func("formfield_$field[presentationname]", $metabasename, $databasename, array_merge($field, array('uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$uniquevalue)), !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname ? $foreignvalue : $field['defaultvalue'], (!is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname) || !$field['privilege_insert'], false));
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
          $field['descriptor'] = isset($row["$field[foreigntablename]_$field[fieldname]_descriptor"]) ? $row["$field[foreigntablename]_$field[fieldname]_descriptor"] : null;
          $field['thisrecord'] = !is_null($foreignvalue) && $field['fieldname'] == $foreignfieldname;
          $field['uniquefieldname'] = $uniquefieldname;
          $field['uniquevalue'] = $row[$uniquefieldname];
          $columns[] =
            html('td', array('class'=>join_non_null(' ', 'column', $field['presentationname'], $field['thisrecord'] ? 'thisrecord' : null)),
              ''.call_user_func("list_$field[presentationname]", $metabasename, $databasename, $field, $row["${tablename}_$field[fieldname]"])
            );
        }
      }
      $columns[] = html('td', array('class'=>'filler'), '');
      $rows[] =
        html('tr', array('class'=>join_non_null(' ', count($rows) % 2 ? 'rowodd' : 'roweven', 'list')),
          html('td', array(),
            $can_update
            ? internal_reference(array('action'=>'edit_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('edit'), array('class'=>'editrecord'))
            : internal_reference(array('action'=>'show_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'uniquevalue'=>$row[$uniquefieldname], "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('show'), array('class'=>'showrecord'))
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
              internal_reference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('full record')).
              (is_null($foreignvalue) ? '' : html('span', array('class'=>'changeslost'), _('(changes to form fields are lost)')))
            ).
            (is_null($uniquevalue) ? '' : ajaxcontent(edit_record('UPDATE', $metabasename, $databasename, $tablename, $tablenamesingular, $uniquefieldname, $uniquevalue)))
          )
        )
      : html('tr', array(),
          html('td', array('colspan'=>count($header)),
            html('div', array(),
              internal_reference(array('action'=>'new_record', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, "field:$foreignfieldname"=>$foreignvalue, 'back'=>parameter('server', 'REQUEST_URI')), _('add record'))
            )
          )
        );
    }

    if ($limit && $foundrecords > $limit) {
      $offsets = array();
      for ($otheroffset = 0; $otheroffset < $foundrecords; $otheroffset += $limit) {
        $title = sprintf($otheroffset + 1 < $foundrecords ? _('record %d till %d') : _('record %d'), $otheroffset + 1, min($otheroffset + $limit, $foundrecords));
        $page = round($otheroffset / $limit) + 1;
        $offsets[] = $offset == $otheroffset ? html('span', array('title'=>$title), $page) : internal_reference(array('action'=>'show_table', 'metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'tablenamesingular'=>$tablenamesingular, 'uniquefieldname'=>$uniquefieldname, 'offset'=>$otheroffset, 'orderfieldname'=>$originalorderfieldname, 'orderasc'=>$orderasc ? 'on' : ''), $page, array('class'=>'ajaxreload', 'title'=>$title));
      }
    }

    if (is_null($foreignvalue) || isset($offsets))
      $rows[] =
        html('tr', array(),
          html('td', array(), is_null($foreignvalue) ? internal_reference(http_parse_query(parameter('server', 'HTTP_REFERER')), 'close', array('class'=>'close')) : '').
          html('td', array('colspan'=>count($header) - 1),
            isset($offsets) ? html('ol', array('class'=>'offsets'), html('li', array(), $offsets)) : ''
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
            html('input', array('type'=>'hidden', 'name'=>'back', 'value'=>isset($back) ? $back : parameter('server', 'HTTP_REFERER'))).
            html('table', array('class'=>join_non_null(' ', $interactive ? 'box' : null, 'tablelist')), join($rows)),
            'post'
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
      html('td', array('class'=>'field'),
        (($privilege == 'UPDATE' || $privilege == 'INSERT') && has_grant($privilege, $databasename, $viewname, '?') ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>$privilege == 'UPDATE' ? 'update_record' : 'add_record', 'class'=>'mainsubmit')) : '').
        ($privilege == 'INSERT' && has_grant($privilege, $databasename, $viewname, '?') ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'add_record_and_edit', 'class'=>'minorsubmit')) : '').
        (($privilege == 'UPDATE' || $privilege == 'SELECT') && has_grant('DELETE', $databasename, $viewname) ? html('input', array('type'=>'submit', 'name'=>'action', 'value'=>'delete_record', 'class'=>join_non_null(' ', 'mainsubmit', 'delete'))) : '')
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
          html('table', array('class'=>'tableedit'), html('tr', array(), $lines)),
          'post'
        ).
        ($referrers ? html('table', array('class'=>'referringlist'), join($referrers)) : '').
        ($privilege == 'SELECT' || !has_grant($privilege, $databasename, $viewname, '?')
        ? internal_reference(http_parse_query($back ? $back : parameter('server', 'HTTP_REFERER')), _('close'), array('class'=>'close'))
        : internal_reference(http_parse_query($back ? $back : parameter('server', 'HTTP_REFERER')), _('cancel'), array('class'=>'cancel'))
        )
      );
  }

  function insert_or_update($databasename, $tablename, $fieldnamesandvalues, $uniquefieldname = null, $uniquevalue = null) {
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
    if (!isset($alternatives[$metabasename][$databasename])) {
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
        join_non_null(', ',
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
        'AND '.$wherepart.
      'ORDER BY fld.fieldid',
      array('metabasename'=>$metabasename, 'databasename'=>$databasename, 'tablename'=>$tablename, 'viewname'=>$viewname, 'username'=>parameter('session', 'username'), 'host'=>parameter('session', 'host'))
    );
  }

  function descriptor($metabasename, $databasename, $tablename, $tablealias, $stack = array()) {
    static $descriptors = array();
    $viewname = table_or_view($metabasename, $databasename, $tablename);
    if (!isset($descriptors[$viewname])) {
      $selects = $joins = $orders = array();
      $fields = fields_from_table($metabasename, $databasename, $tablename, $viewname);
      while ($field = mysql_fetch_assoc($fields)) {
        if ($field['indesc']) {
          include_presentation($field['presentationname']);
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

  function set_preference($item, $default) {
    $value = first_non_null(is_local() ? null : $default, parameter('get', $item) == 'on' ? 1 : null, parameter('get', $item) == 'off' ? 0 : null, parameter('cookie', $item), $default);
    if ($value !== parameter('cookie', $item)) {
      parameter('cookie', $item, $value);
      if (is_local())
        internal_redirect(http_parse_query(preg_replace("@&$item=\w+@", '', parameter('server', 'REQUEST_URI'))));
    }
  }

  function forget($usernameandhost) {
    parameter('cookie', 'lastusernamesandhosts', join_non_null(',', array_diff(explode(',', parameter('cookie', 'lastusernamesandhosts')), array($usernameandhost))));
  }

  function login($username, $host, $password, $language) {
    parameter('session', 'username', $username);
    parameter('session', 'host'    , $host);
    parameter('session', 'password', $password);
    parameter('cookie', 'language', $language);
    parameter('cookie', 'lastusernamesandhosts', join_non_null(',', array_diff(array_unique(array_merge(array("$username@$host"), array_diff(explode(',', parameter('cookie', 'lastusernamesandhosts')), array("$username@$host")))), array(''))));
  }

  function logout($error = null) {
    parameter('session', null, array());
    session_destroy();
    internal_redirect(array('action'=>'login', 'error'=>$error));
  }

  function get_presentationnames() {
    static $presentationnames = array();
    if ($presentationnames)
      return $presentationnames;
    $dir = opendir('presentation');
    while ($file = readdir($dir)) {
      if (preg_match('@^(.*)\.php$@', $file, $matches)) {
        $presentationnames[] = $matches[1];
        include_presentation($matches[1]);
      }
    }
    closedir($dir);
    sort($presentationnames);
    return $presentationnames;
  }

  function read_file($filenameparts, $flags = null) {
    $content = @file(file_name($filenameparts), $flags);
    return $content === false ? array() : $content;
  }

  function augment_file($filename, $extension, $content_type) {
    $content = join(read_file(array("$filename.$extension")));

    if (preg_match_all('@/\* *(\w+)_\* *\*/ *\n@', $content, $function_prefixes, PREG_SET_ORDER)) {
      $presentationnames = get_presentationnames();
      foreach ($function_prefixes as $function_prefix) {
        $extra = array();
        foreach ($presentationnames as $presentationname)
          $extra[] =
            "/* $function_prefix[1]_$presentationname */\n".
            @call_user_func("$function_prefix[1]_$presentationname");

        $metabasename = parameter('get', 'metabasename');
        if ($metabasename) {
          @include_phpfile(array('metabase', "$metabasename.php"));
          $extra[] =
            "/* $function_prefix[1]_$metabasename */\n".
            @call_user_func("$function_prefix[1]_$metabasename");
        }

        $content = preg_replace("@( *)/\* *$function_prefix[1]_\* *\*/ *\n@e", '"$1".preg_replace("@\n(?=.)@", "\n$1", join($extra))', $content);
      }
    }

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
        $exploded[$id] = isset($exploded[$id]) ? max($exploded[$id], $value) : $value;
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
      $charset = isset($matches[1]) ? bare($matches[1]) : '';
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
        array('databasename'=>$databasename, 'tablename'=>$tablename, 'fieldname'=>$fieldname, 'username'=>parameter('session', 'username'), 'host'=>parameter('session', 'host'), 'privilege'=>$privilege)
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
      array('username'=>parameter('session', 'username'), 'host'=>parameter('session', 'host'), 'privilege'=>$privilege)
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
