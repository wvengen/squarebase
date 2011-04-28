<?php
  // add directory to include path for in-tree installation
  set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__));

  require_once('functions.php');
  require_once('My_Selenium.php');
  require_once('local.php');

  //return the database in an array
  function readDatabase($connection) {
    $database = array();
    $tables = query('SELECT table_name, column_name FROM information_schema.columns WHERE table_schema="<databasename>" AND extra="auto_increment"', array('databasename'=>'inventory'), $connection);
    while ($table = mysql_fetch_assoc($tables)) {
      $tablename = $table['table_name'];
      $columnname = $table['column_name'];
      $database[$tablename] = array();
      $records = query('SELECT * FROM <databasename>.<tablename> ORDER BY <columnname>', array('databasename'=>'inventory', 'tablename'=>$tablename, 'columnname'=>$columnname), $connection);
      while ($record = mysql_fetch_assoc($records)) {
        $database[$tablename][] = $record;
      }
    }
    return $database;
  }

  //start selenium
  $selenium = new My_Selenium('*firefox', $site_url);
  $selenium->startAndShowProgress();

  //make a test database
  $connection = mysql_connect($mysql_host, $mysql_user, $mysql_passwd);

  query('DROP DATABASE IF EXISTS inventory', array(), $connection);
  query('CREATE DATABASE inventory', array(), $connection);
  query(
    'CREATE TABLE inventory.computers ('.
      'computerID   INT(11)      NOT NULL AUTO_INCREMENT,'.
      'description  VARCHAR(80)  NOT NULL,'.
      'PRIMARY KEY (computerID),'.
      'UNIQUE KEY (description)'.
    ')',
    array(),
    $connection
  );
  query(
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
  query(
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
  query('DROP DATABASE IF EXISTS inventory_metabase', array(), $connection);

  //check the database
  $database = array('computers'=>array(), 'employees'=>array(), 'usages'=>array());
  $selenium->equal(readDatabase($connection), $database);

  //logout
  $selenium->open('index.php?action=logout');
  $selenium->noErrorAndNoWarningAndTitle('login');
  $selenium->equal($selenium->getContent('currentusernameandhost'), '');

  //login
  $selenium->type('usernameandhost', "$mysql_user@$mysql_host");
  $selenium->type('password', $mysql_passwd);
  $selenium->clickAndWaitForPageToLoad('action');
  $selenium->equal($selenium->getContent('currentusernameandhost'), $mysql_user);

  // if metabases are present we get a list of databases
  if ($selenium->getTitle() == 'list databases') {
    // proceed to new metabase creation page
    $selenium->clickAndWaitForPageToLoad('link=new metabase from database', 'new metabase from database');
  } else {
    $selenium->noErrorAndNoWarningAndTitle('new metabase from database');
  }

  //new metabase from database
  $selenium->clickAndWaitForPageToLoad('link=inventory', 'language for database');

  //language for database (forming the metabase can be an intensive operation)
  $selenium->clickAndWaitForPageToLoad('action', 'form metabase for database', 20000);

  //form metabase for database
  $selenium->clickAndWaitForPageToLoad('action', 'show database');

  //show database inventory
  $selenium->clickAndWaitForAjaxToLoad('link=computers');

  //quickadd computer
  $selenium->type('field-description', 'Dell Optiplex');
  $selenium->clickAndWaitForAjaxToLoad('quickadd-record-computers');

  $database['computers'][] = array('computerID'=>1, 'description'=>'Dell Optiplex');
  $selenium->equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field-description', 'Dell Inspiron');
  $selenium->clickAndWaitForAjaxToLoad('quickadd-record-computers');

  $database['computers'][] = array('computerID'=>2, 'description'=>'Dell Inspiron');
  $selenium->equal(readDatabase($connection), $database);

  //full record computer
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field-description"]', 'Dell Dimension');
  $selenium->clickAndWaitForAjaxToLoad('add-record-computers');

  $database['computers'][] = array('computerID'=>3, 'description'=>'Dell Dimension');
  $selenium->equal(readDatabase($connection), $database);

  //quickadd computer
  $selenium->type('field-description', 'iMcDonalds');
  $selenium->clickAndWaitForAjaxToLoad('quickadd-record-computers');

  $database['computers'][] = array('computerID'=>4, 'description'=>'iMcDonalds');
  $selenium->equal(readDatabase($connection), $database);

  //edit computer
  $selenium->clickAndWaitForAjaxToLoad('edit_record_computer_4');
  $selenium->type('field-description', 'iMac');
  $selenium->clickAndWaitForAjaxToLoad('update_record_computer');

  $database['computers'][4 - 1]['description'] = 'iMac';
  $selenium->equal(readDatabase($connection), $database);

  //close table computers
  $selenium->clickAndWaitForAjaxToLoad('link=computers');

  //show table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //quickadd employee
  $selenium->type('field-firstName', 'John');
  $selenium->type('field-lastName', 'Doe');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>1, 'firstName'=>'John', 'lastName'=>'Doe', 'picture'=>null);
  $selenium->equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field-firstName', 'Daffy');
  $selenium->type('field-lastName', 'Duck');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>2, 'firstName'=>'Daffy', 'lastName'=>'Duck', 'picture'=>null);
  $selenium->equal(readDatabase($connection), $database);

  //full record employee, upload image 1
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field-firstName"]', 'Mickey');
  $selenium->type('document.forms[1].elements["field-lastName"]', 'Mouse');
  $filename1 = dirname(__FILE__).'/mickeymouse1.jpg';
  $selenium->type('document.forms[1].elements["field-picture"]', $filename1);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('add_record_employee');

  $database['employees'][] = array('employeeID'=>3, 'firstName'=>'Mickey', 'lastName'=>'Mouse', 'picture'=>file_get_contents($filename1));
  $selenium->equal(readDatabase($connection), $database);

  //edit employee, remove image 1
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $selenium->check('radio-none-picture');
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = null;
  $selenium->equal(readDatabase($connection), $database);

  //edit employee, upload image 2
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $filename2 = dirname(__FILE__).'/mickeymouse2.jpg';
  $selenium->type('document.forms[1].elements["field-picture"]', $filename2);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = file_get_contents($filename2);
  $selenium->equal(readDatabase($connection), $database);

  //edit employee, upload image 1 to replace image 2
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $selenium->type('document.forms[1].elements["field-picture"]', $filename1);
  sleep(2); //because TypeAndWaitForAjaxToLoad doesn't exist
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = file_get_contents($filename1);
  $selenium->equal(readDatabase($connection), $database);

  //edit employee, remove image 2
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_3');
  $selenium->check('radio-none-picture');
  $selenium->clickAndWaitForAjaxToLoad('update_record_employee');

  $database['employees'][3 - 1]['picture'] = null;
  $selenium->equal(readDatabase($connection), $database);

  //quickadd employee
  $selenium->type('field-firstName', 'Minnie');
  $selenium->type('field-lastName', 'Mouse');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_employee');

  $database['employees'][] = array('employeeID'=>4, 'firstName'=>'Minnie', 'lastName'=>'Mouse', 'picture'=>null);
  $selenium->equal(readDatabase($connection), $database);

  //close table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //show table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //quickadd usage
  $selenium->type('field-dateAcquired', '06/03/1999');
  $selenium->select('field-computerID', 'label=iMac');
  $selenium->select('field-employeeID', 'label=Mickey Mouse');
  $selenium->type('field-comments', 'the purple one');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>1, 'dateAcquired'=>'1999-06-03', 'computerID'=>4, 'employeeID'=>3, 'comments'=>'the purple one');
  $selenium->equal(readDatabase($connection), $database);

  //quickadd usage
  $selenium->type('field-dateAcquired', '09/15/2000');
  $selenium->select('field-computerID', 'label=Dell Inspiron');
  $selenium->select('field-employeeID', 'label=John Doe');
  $selenium->type('field-comments', 'for home use');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>2, 'dateAcquired'=>'2000-09-15', 'computerID'=>2, 'employeeID'=>1, 'comments'=>'for home use');
  $selenium->equal(readDatabase($connection), $database);

  //close table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //show table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //quickadd_and_edit employee
  $selenium->type('field-firstName', 'Ronald');
  $selenium->type('field-lastName', 'McDonald');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_and_edit_employee');

  $database['employees'][] = array('employeeID'=>5, 'firstName'=>'Ronald', 'lastName'=>'McDonald', 'picture'=>null);
  $selenium->equal(readDatabase($connection), $database);

  //add usage
  $selenium->type('field-dateAcquired', '10/02/1999');
  $selenium->select('field-computerID', 'label=Dell Optiplex');
  $selenium->type('field-comments', 'on temporary loan');
  $selenium->clickAndWaitForAjaxToLoad('quickadd_record_usage');

  $database['usages'][] = array('usageID'=>3, 'dateAcquired'=>'1999-10-02', 'computerID'=>1, 'employeeID'=>5, 'comments'=>'on temporary loan');
  $selenium->equal(readDatabase($connection), $database);

  $selenium->clickAndWaitForAjaxToLoad('link=cancel');

  //edit employee
  $selenium->clickAndWaitForAjaxToLoad('edit_record_employee_4');

  //delete employee
  $selenium->clickAndWaitForAjaxToLoad('delete_record_employee');

  unset($database['employees'][4 - 1]);
  $database['employees'] = array_values($database['employees']); //renumber the array
  $selenium->equal(readDatabase($connection), $database);

  //close table employees
  $selenium->clickAndWaitForAjaxToLoad('link=employees');

  //show table usages
  $selenium->clickAndWaitForAjaxToLoad('link=usages');

  //edit usage
  $selenium->clickAndWaitForAjaxToLoad('edit_record_usage_1');

  //subadd computer
  $selenium->clickAndWaitForAjaxToLoad('link=new computer');
  $selenium->type('field-description', 'iPhone');
  $selenium->clickAndWaitForAjaxToLoad('add_record_computer');

  //update usage
  $selenium->clickAndWaitForAjaxToLoad('update_record_usage');

  $database['computers'][] = array('computerID'=>5, 'description'=>'iPhone');
  $database['usages'][0]['computerID'] = 5;
  $selenium->equal(readDatabase($connection), $database);

  //add usage, full record
  $selenium->clickAndWaitForAjaxToLoad('link=full record');
  $selenium->type('document.forms[1].elements["field-dateAcquired"]', '03/20/2001');
  $selenium->type('document.forms[1].elements["field-comments"]', 'water resistant');

  //subadd computer
  $selenium->clickAndWaitForAjaxToLoad('link=new computer');
  $selenium->type('field-description', 'iPad');
  $selenium->clickAndWaitForAjaxToLoad('add_record_computer');

  //subadd employee
  $selenium->clickAndWaitForAjaxToLoad('link=new employee');
  $selenium->type('field-firstName', 'Popeye');
  $selenium->type('field-lastName', 'Sailorman, the');
  $selenium->clickAndWaitForAjaxToLoad('add_record_employee');

  //add usage
  $selenium->clickAndWaitForAjaxToLoad('add_record_usage');

  $database['computers'][] = array('computerID'=>6, 'description'=>'iPad');
  $database['employees'][] = array('employeeID'=>6, 'firstName'=>'Popeye', 'lastName'=>'Sailorman, the', 'picture'=>null);
  $database['usages'][] = array('usageID'=>4, 'dateAcquired'=>'2001-03-20', 'computerID'=>6, 'employeeID'=>6, 'comments'=>'water resistant');
  $selenium->equal(readDatabase($connection), $database);

  //stop selenium
  $selenium->stopAndClearProgress();
?>
