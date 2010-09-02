<?php
  require_once('functions.php');
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
    return
      (is_null($value)
      ? 'null'
      : (is_bool($value)
        ? ($value ? 'true' : 'false')
        : (is_numeric($value)
          ? $value
          : (is_array($value)
            ? preg_replace(array('@^Array\n\(\n *@', '@ => Array\s*\(\s*@s', '@\n *\)\n *@s', '@\n +@', '@\[(.*?)\] *=> *@'), array('(', '=>(', ')', ',', '$1=>'), print_r($value, true))
            : (preg_match('@[\x00-\x1f\x7f-\xff]@', $value)
              ? 'md5='.md5($value)
              : '"'.$value.'"'
              )
            )
          )
        )
      );
  }       

  //tests the equality of its parameters and updates the progress
  function equal($found, $expected) {
    global $source, $unequals;
    $trace = debug_backtrace();
    $linenumber = $trace[0]['line'];
    if ($found != $expected) {
      clear_progress();
      print sprintf("%3d: %s == %s != %s\n", $linenumber, preg_replace('@\$selenium->@', '', preg_match1('@^\s*equal\((.+),.+?\);\s+$@', $source[$linenumber - 1])), value($found), value($expected));
      $unequals++;
      exit(1);
    }
    progress($linenumber);
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

  progress(0);

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
      'picture      BLOB,'.
      'PRIMARY KEY (employeeID),'.
      'UNIQUE KEY (firstName, lastName)'.
    ')',
    array(),
    $connection
  );
  query('data',
    'CREATE TABLE inventory.usages ('.
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
  $database = array('computers'=>array(), 'employees'=>array(), 'usages'=>array());
  equal(readDatabase($connection), $database);

  //start selenium
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
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_computer');

  $database['computers'][] = array('computerID'=>1, 'description'=>'Dell Optiplex');
  equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field:description', 'Dell Inspiron');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_computer');

  $database['computers'][] = array('computerID'=>2, 'description'=>'Dell Inspiron');
  equal(readDatabase($connection), $database);

  //full record computer
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field:description"]', 'Dell Dimension');
  $selenium->clickAndWaitForAjaxToLoad('add_record_computer');

  $database['computers'][] = array('computerID'=>3, 'description'=>'Dell Dimension');
  equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field:description', 'iMcDonalds');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_computer');

  $database['computers'][] = array('computerID'=>4, 'description'=>'iMcDonalds');
  equal(readDatabase($connection), $database);

  //edit computer
  $selenium->clickAndWaitForAjaxToLoad('edit_record_computer_4');
  $selenium->type('field:description', 'iMac');
  $selenium->clickAndWaitForAjaxToLoad('update_record_computer');

  $database['computers'][4 - 1]['description'] = 'iMac';
  equal(readDatabase($connection), $database);

  //close table computers
  $selenium->clickAndWaitForAjaxToLoad('link=computers');

  //show table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //quickadd employee
  $selenium->type('field:firstName', 'John');
  $selenium->type('field:lastName', 'Doe');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>1, 'firstName'=>'John', 'lastName'=>'Doe', 'picture'=>null);
  equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field:firstName', 'Daffy');
  $selenium->type('field:lastName', 'Duck');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>2, 'firstName'=>'Daffy', 'lastName'=>'Duck', 'picture'=>null);
  equal(readDatabase($connection), $database);

  //full record employee, upload image 1
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field:firstName"]', 'Mickey');
  $selenium->type('document.forms[1].elements["field:lastName"]', 'Mouse');
  $filename1 = dirname(__FILE__).'/mickeymouse1.jpg';
  $selenium->type('document.forms[1].elements["field:picture"]', $filename1);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('add_record_employee');

  $database['employees'][] = array('employeeID'=>3, 'firstName'=>'Mickey', 'lastName'=>'Mouse', 'picture'=>file_get_contents($filename1));
  equal(readDatabase($connection), $database);

  //edit employee, remove image 1
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $selenium->check('radio:none:picture');
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = null;
  equal(readDatabase($connection), $database);

  //edit employee, upload image 2
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $filename2 = dirname(__FILE__).'/mickeymouse2.jpg';
  $selenium->type('document.forms[1].elements["field:picture"]', $filename2);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = file_get_contents($filename2);
  equal(readDatabase($connection), $database);

  //edit employee, upload image 1 to replace image 2
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $selenium->type('document.forms[1].elements["field:picture"]', $filename1);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = file_get_contents($filename1);
  equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field:firstName', 'Minnie');
  $selenium->type('field:lastName', 'Mouse');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>4, 'firstName'=>'Minnie', 'lastName'=>'Mouse', 'picture'=>null);
  equal(readDatabase($connection), $database);

  //close table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //show table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //quickadd usage
  $selenium->type('field:dateAcquired', '06/03/1999');
  $selenium->select('field:computerID', 'label=iMac');
  $selenium->select('field:employeeID', 'label=Mickey Mouse');
  $selenium->type('field:comments', 'the purple one');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>1, 'dateAcquired'=>'1999-06-03', 'computerID'=>4, 'employeeID'=>3, 'comments'=>'the purple one');
  equal(readDatabase($connection), $database);

  //quickadd usage
  $selenium->type('field:dateAcquired', '09/15/2000');
  $selenium->select('field:computerID', 'label=Dell Inspiron');
  $selenium->select('field:employeeID', 'label=John Doe');
  $selenium->type('field:comments', 'for home use');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>2, 'dateAcquired'=>'2000-09-15', 'computerID'=>2, 'employeeID'=>1, 'comments'=>'for home use');
  equal(readDatabase($connection), $database);

  //close table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //show table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //quickadd_and_edit employee
  $selenium->type('field:firstName', 'Ronald');
  $selenium->type('field:lastName', 'McDonald');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_and_edit_employee');

  $database['employees'][] = array('employeeID'=>5, 'firstName'=>'Ronald', 'lastName'=>'McDonald', 'picture'=>null);
  equal(readDatabase($connection), $database);

  //add usage
  $selenium->type('field:dateAcquired', '10/02/1999');
  $selenium->select('field:computerID', 'label=Dell Optiplex');
  $selenium->type('field:comments', 'on temporary loan');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>3, 'dateAcquired'=>'1999-10-02', 'computerID'=>1, 'employeeID'=>5, 'comments'=>'on temporary loan');
  equal(readDatabase($connection), $database);

  $selenium->clickAndWaitForAjaxToLoad('link=cancel');

  //edit employee
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_4');

  //delete employee
  $selenium->clickAndWaitForAjaxToLoad('delete_record_employee');

  unset($database['employees'][4 - 1]);
  $database['employees'] = array_values($database['employees']); //renumber the array
  equal(readDatabase($connection), $database);

  //show table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //edit usage
  $selenium->clickAndWaitForAjaxToLoad('edit_record_usage_1');

  //subadd computer
  $selenium->clickAndWaitForAjaxToLoad('link=new computer');
  $selenium->type('field:description', 'iPhone');
  $selenium->clickAndWaitForAjaxToLoad('add_record_computer');

  //update usage
  $selenium->clickAndWaitForAjaxToLoad('update_record_usage');

  $database['computers'][] = array('computerID'=>5, 'description'=>'iPhone');
  $database['usages'][0]['computerID'] = 5;
  equal(readDatabase($connection), $database);

  //stop selenium
  $selenium->stop();

  clear_progress();

  exit($unequals);
?>
