<?php
  require_once('../functions.php');
  require_once('My_Selenium.php');

  function value($value) {
    return is_null($value) ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (is_numeric($value) ? $value : (is_array($value) ? preg_replace(array('@Array\s*(?=\()@s', '@ *\n *@s'), array('', ''), print_r($value, true)) : '"'.$value.'"')));
  }       

  function progress($line) {
    global $file;
    $percentage = max(min(floor($line / count($file) * 100), 100), 0);
    print sprintf("\r%3d/%d = %3d%% [%s>%s]", $line, count($file), $percentage, str_repeat('=', $percentage), str_repeat(' ', 100 - $percentage));
  }

  function equal($found, $expected) {
    global $file;
    $trace = debug_backtrace();
    progress($trace[0]['line']);
    if ($found != $expected)
      print sprintf("\r%s\r%3d: %s == %s != %s\n", str_repeat(' ', 120), $trace[0]['line'], preg_replace('@\$selenium->@', '', preg_match1('@^\s*equal\((.+),.+?\);\s+$@', $file[$trace[0]['line'] - 1])), value($found), value($expected));
  }

  function readDatabase($connection) {
    $database = array();
    $tables = query('meta', 'SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema = "<databasename>"', array('databasename'=>'inventory'), $connection);
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      $database[$tablename] = array();
      $records = query('meta', 'SELECT * FROM <databasename>.<tablename>', array('databasename'=>'inventory', 'tablename'=>$tablename), $connection);
      while ($record = mysql_fetch_assoc($records)) {
        $database[$tablename][] = $record;
      }
    }
    return $database;
  }

  $file = file(__FILE__);

  // start
  progress(0);
  $selenium = new My_Selenium('*firefox', 'http://localhost/');
  $selenium->start();

  // logout
  $selenium->open('/squarebase.org/index.php?action=logout');
  equal($selenium->getContent('error'), null);
  equal($selenium->getTitle(), 'login');
  equal($selenium->getContent('currentusernameandhost'), '');

  // login
  $selenium->type('usernameandhost', 'sqbase');
  $selenium->type('password', 'sqbase');
  $selenium->click('action');
  $selenium->waitForPageToLoad(5000);
  equal($selenium->getContent('error'), null);
  equal($selenium->getTitle(), 'index');
  equal($selenium->getContent('currentusernameandhost'), 'sqbase@localhost');

  //make a test database
  $database = array('computers'=>array(), 'employees'=>array(), 'inventory'=>array());
  $connection = @mysql_connect('localhost', 'sqbase', 'sqbase');
  foreach ($database as $tablename=>$content)
    query('data', 'TRUNCATE TABLE inventory.<tablename>', array('tablename'=>$tablename), $connection);
  equal(readDatabase($connection), $database);

  //index
  $selenium->click('link=inventory');
  $selenium->waitForPageToLoad(5000);

  //show database inventory
  $selenium->click('link=computers');
  $selenium->waitForAjaxToLoad(5000);

  //show table computers
  $selenium->type('field:description', 'Dell Optiplex');
  $selenium->click('action');
  $selenium->waitForAjaxToLoad(5000);

  $database['computers'][] = array('computerID'=>1, 'description'=>'Dell Optiplex');
  equal(readDatabase($connection), $database);

  $selenium->stop();
  progress(count($file));
  print "\n";
?>
