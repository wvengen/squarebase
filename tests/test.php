<?php
  require_once('../functions.php');
  require_once('My_Selenium.php');

  //global variables
  $source = file(__FILE__);
  $unequals = 0;

  //prints the progress in this source file
  function progress($line) {
    global $source;
    $percentage = max(min(floor($line / count($source) * 100), 100), 0);
    print sprintf("\r%3d/%d = %3d%% [%s>%s]", $line, count($source), $percentage, str_repeat('=', $percentage), str_repeat(' ', 100 - $percentage));
  }

  //clears the progress in this source file, so a message can be printed
  function clear_progress() {
    print sprintf("\r%s\r", str_repeat(' ', 120));
  }

  //returns a nicely formatted value
  function value($value) {
    return is_null($value) ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (is_numeric($value) ? $value : (is_array($value) ? preg_replace(array('@^Array\n\(\n *@', '@ => Array\s*\(\s*@s', '@\n *\)\n *@s', '@\n +@', '@\[(.*?)\] *=> *@'), array('(', '=>(', ')', ',', '$1=>'), print_r($value, true)) : '"'.$value.'"')));
  }       

  //tests the equality of its parameters and updates the progress
  function equal($found, $expected) {
    global $source, $unequals;
    $trace = debug_backtrace();
    if ($found != $expected) {
      clear_progress();
      print sprintf("%3d: %s == %s != %s\n", $trace[0]['line'], preg_replace('@\$selenium->@', '', preg_match1('@^\s*equal\((.+),.+?\);\s+$@', $source[$trace[0]['line'] - 1])), value($found), value($expected));
      $unequals++;
    }
    progress($trace[0]['line']);
  }

  //return the database in an array
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

  //make a test database
  $connection = mysql_connect('localhost', 'sqbase', 'sqbase');
  query('data', 'DROP DATABASE IF EXISTS inventory', array(), $connection);
  query('data', 'CREATE DATABASE inventory', array(), $connection);
  query('data',
    'CREATE TABLE inventory.computers ('.
      'computerID   INT(11)      NOT NULL AUTO_INCREMENT,'.
      'description  VARCHAR(80)  NOT NULL,'.
      'PRIMARY KEY (computerID),'.
      'UNIQUE KEY (description)'.
    ')',
    array(),
    $connection
  );
  query('data',
    'CREATE TABLE inventory.employees ('.
      'employeeID   INT(11)      NOT NULL AUTO_INCREMENT,'.
      'firstName    VARCHAR(20)  NOT NULL,'.
      'lastName     VARCHAR(20)  NOT NULL,'.
      'PRIMARY KEY (employeeID),'.
      'UNIQUE KEY (firstName, lastName)'.
    ')',
    array(),
    $connection
  );
  query('data',
    'CREATE TABLE inventory.usage ('.
      'usageID      INT(11)      NOT NULL AUTO_INCREMENT,'.
      'dateAcquired DATE         NOT NULL,'.
      'computerID   INT(11)      NOT NULL,'.
      'employeeID   INT(11)      NOT NULL,'.
      'comments     VARCHAR(200) NOT NULL,'.
      'PRIMARY KEY (usageID),'.
      'KEY (computerID),'.
      'KEY (employeeID),'.
      'UNIQUE KEY (dateAcquired, computerID, employeeID, comments)'.
    ')',
    array(),
    $connection
  );
  query('meta', 'DROP DATABASE IF EXISTS inventory_metabase', array(), $connection);

  //check the database
  $database = array('computers'=>array(), 'employees'=>array(), 'usage'=>array());
  equal(readDatabase($connection), $database);

  //start
  progress(0);
  $selenium = new My_Selenium('*firefox', 'http://localhost/');
  $selenium->start();

  //logout
  $selenium->open('/squarebase.org/index.php?action=logout');
  equal($selenium->getContent('error'), null);
  equal($selenium->getTitle(), 'login');
  equal($selenium->getContent('currentusernameandhost'), '');

  //login
  $selenium->type('usernameandhost', 'sqbase');
  $selenium->type('password', 'sqbase');
  $selenium->clickAndWaitForPageToLoad('action');
  equal($selenium->getContent('error'), null);
  equal($selenium->getContent('currentusernameandhost'), 'sqbase@localhost');

  //new metabase from database
  equal($selenium->getTitle(), 'new metabase from database');
  $selenium->clickAndWaitForPageToLoad('link=inventory');

  //language for database
  equal($selenium->getTitle(), 'language for database');
  $selenium->clickAndWaitForPageToLoad('action');

  //form metabase for database
  equal($selenium->getTitle(), 'form metabase for database');
  $selenium->clickAndWaitForPageToLoad('action');

  //show database inventory
  equal($selenium->getTitle(), 'show database');
  $selenium->clickAndWaitForAjaxToLoad('link=computers');

  //quickadd computer
  $selenium->type('field:description', 'Dell Optiplex');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['computers'][] = array('computerID'=>1, 'description'=>'Dell Optiplex');
  equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field:description', 'Dell Inspiron');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['computers'][] = array('computerID'=>2, 'description'=>'Dell Inspiron');
  equal(readDatabase($connection), $database);

  //full record computer
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field:description"]', 'Dell Dimension');
  $selenium->clickAndWaitForAjaxToLoad('document.forms[1].elements["action"][0]');

  $database['computers'][] = array('computerID'=>3, 'description'=>'Dell Dimension');
  equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field:description', 'iMac');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['computers'][] = array('computerID'=>4, 'description'=>'iMac');
  equal(readDatabase($connection), $database);

  //close table computers
  $selenium->clickAndWaitForAjaxToLoad('link=computers');

  //show table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //quickadd employee
  $selenium->type('field:firstName', 'John');
  $selenium->type('field:lastName', 'Doe');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['employees'][] = array('employeeID'=>1, 'firstName'=>'John', 'lastName'=>'Doe');
  equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field:firstName', 'Daffy');
  $selenium->type('field:lastName', 'Duck');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['employees'][] = array('employeeID'=>2, 'firstName'=>'Daffy', 'lastName'=>'Duck');
  equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field:firstName', 'Mickey');
  $selenium->type('field:lastName', 'Mouse');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['employees'][] = array('employeeID'=>3, 'firstName'=>'Mickey', 'lastName'=>'Mouse');
  equal(readDatabase($connection), $database);

  //close table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //show table usage
  $selenium->clickAndWaitForAjaxToLoad('link=usage');

  //quickadd usage
  $selenium->type('field:dateAcquired', '06/03/1999');
  $selenium->select('field:computerID', 'label=iMac');
  $selenium->select('field:employeeID', 'label=Mickey Mouse');
  $selenium->type('field:comments', 'the purple one');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['usage'][] = array('usageID'=>1, 'dateAcquired'=>'1999-06-03', 'computerID'=>4, 'employeeID'=>3, 'comments'=>'the purple one');
  equal(readDatabase($connection), $database);

  //quickadd usage
  $selenium->type('field:dateAcquired', '09/15/2000');
  $selenium->select('field:computerID', 'label=Dell Inspiron');
  $selenium->select('field:employeeID', 'label=John Doe');
  $selenium->type('field:comments', 'for home use');
  $selenium->clickAndWaitForAjaxToLoad('action');

  $database['usage'][] = array('usageID'=>2, 'dateAcquired'=>'2000-09-15', 'computerID'=>2, 'employeeID'=>1, 'comments'=>'for home use');
  equal(readDatabase($connection), $database);

  $selenium->stop();
  clear_progress();

  exit($unequals == 0 ? 0 : 1);
?>
