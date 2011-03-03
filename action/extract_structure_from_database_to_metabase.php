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

  include('functions.php');

  init();

  $databasename = get_post('databasename');
  $metabasename = get_post('metabasename');

  query('DROP DATABASE IF EXISTS `<metabasename>`', array('metabasename'=>$metabasename));

  query('CREATE DATABASE IF NOT EXISTS `<metabasename>`', array('metabasename'=>$metabasename));

  query(
    'CREATE TABLE `<metabasename>`.languages ('.
      'languageid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'languagename     VARCHAR(100) NOT NULL,'.
      'UNIQUE KEY (languagename)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  query(
    'CREATE TABLE `<metabasename>`.databases ('.
      'databaseid       INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'databasename     VARCHAR(100) NOT NULL,'.
      'UNIQUE KEY (databasename)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  query(
    'CREATE TABLE `<metabasename>`.tables ('.
      'tableid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'tablename        VARCHAR(100) NOT NULL,'.
      'singular         VARCHAR(100) NOT NULL,'.
      'plural           VARCHAR(100) NOT NULL,'.
      'uniquefieldid    INT UNSIGNED NOT NULL REFERENCES `fields` (fieldid),'.
      'intablelist      BOOLEAN      NOT NULL,'.
      'UNIQUE KEY (tablename),'.
      'INDEX (uniquefieldid)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  query(
    'CREATE TABLE `<metabasename>`.fields ('.
      'fieldid          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'tableid          INT UNSIGNED NOT NULL REFERENCES `tables` (tableid),'.
      'fieldname        VARCHAR(100) NOT NULL,'.
      'title            VARCHAR(100) NOT NULL,'.
      'type             VARCHAR(100) NOT NULL,'.
      'nullallowed      BOOLEAN      NOT NULL,'.
      'defaultvalue     VARCHAR(100)         ,'.
      'presentationid   INT UNSIGNED NOT NULL REFERENCES `presentations` (presentationid),'.
      'indesc           BOOLEAN      NOT NULL,'.
      'inlist           BOOLEAN      NOT NULL,'.
      'inedit           BOOLEAN      NOT NULL,'.
      'foreigntableid   INT UNSIGNED          REFERENCES `tables` (tableid),'.
      'UNIQUE KEY (tableid, fieldname),'.
      'INDEX (foreigntableid),'.
      'INDEX (presentationid)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  query(
    'CREATE TABLE `<metabasename>`.views ('.
      'viewid           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'viewname         VARCHAR(100) NOT NULL,'.
      'tableid          INT UNSIGNED NOT NULL REFERENCES `tables` (tableid),'.
      'UNIQUE KEY (viewname),'.
      'INDEX (tableid)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  query(
    'CREATE TABLE `<metabasename>`.presentations ('.
      'presentationid   INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
      'presentationname VARCHAR(100) NOT NULL,'.
      'UNIQUE KEY (presentationname)'.
    ')',
    array('metabasename'=>$metabasename)
  );

  insert_or_update($metabasename, 'languages', array('languagename'=>get_post('language')));

  insert_or_update($metabasename, 'databases', array('databasename'=>$databasename));

  $presentationnames = get_presentationnames();
  $presentationids = array();
  foreach ($presentationnames as $presentationname)
    $presentationids[$presentationname] = insert_or_update($metabasename, 'presentations', array('presentationname'=>$presentationname));

  $tables = query(
    'SELECT tb.table_name, vw.table_name AS view_name, is_updatable, view_definition '.
    'FROM information_schema.tables tb '.
    'LEFT JOIN information_schema.views vw ON vw.table_schema = tb.table_schema AND vw.table_name = tb.table_name '.
    'WHERE tb.table_schema = "<databasename>" '.
    'ORDER BY vw.table_name, tb.table_name', // base tables first, views last, so the table id of a view can be set to that of the underlying base table for alternative views
    array('databasename'=>$databasename)
  );
  $tableids = array();
  while ($table = mysql_fetch_assoc($tables)) {
    $tablename = $table['table_name'];
    if (get_post("$tablename:viewfortable", null) && get_post("$tablename:possibleviewfortable", null))
      insert_or_update($metabasename, 'views', array('viewname'=>$tablename, 'tableid'=>$tableids[get_post("$tablename:possibleviewfortable")]));
    elseif (get_post("$tablename:include", null))
      $tableids[$tablename] = insert_or_update($metabasename, 'tables', array('tablename'=>$tablename, 'singular'=>get_post("$tablename:singular"), 'plural'=>get_post("$tablename:plural"), 'intablelist'=>get_post("$tablename:intablelist", null) == 'on'));
  }

  $errors = array();
  for (mysql_data_reset($tables); $table = mysql_fetch_assoc($tables); ) {
    $tablename = $table['table_name'];
    if (get_post("$tablename:include", null)) {
      if (!get_post("$tablename:viewfortable", null)) {
        $tableid = $tableids[$tablename];

        $indescs = $inlists = $inedits = 0;
        $fields = query(
          'SELECT c.table_schema, c.table_name, c.column_name, column_key, column_type, is_nullable, column_default, referenced_table_name '.
          'FROM information_schema.columns c '.
          'LEFT JOIN information_schema.key_column_usage kcu ON kcu.table_schema = c.table_schema AND kcu.table_name = c.table_name AND kcu.column_name = c.column_name AND referenced_table_schema = c.table_schema '.
          'WHERE c.table_schema = "<databasename>" AND c.table_name = "<tablename>"',
          array('databasename'=>$databasename, 'tablename'=>$tablename)
        );
        while ($field = mysql_fetch_assoc($fields)) {
          $fieldname = $field['column_name'];

          $foreigntablename = get_post("$tablename:$fieldname:foreigntablename", null);

          $indesc = get_post("$tablename:$fieldname:indesc", null) ? true : false;
          $inlist = get_post("$tablename:$fieldname:inlist", null) ? true : false;
          $inedit = get_post("$tablename:$fieldname:inedit", null) ? true : false;

          $fieldid = insert_or_update($metabasename, 'fields', array('tableid'=>$tableid, 'fieldname'=>$fieldname, 'title'=>get_post("$tablename:$fieldname:title"), 'type'=>$field['column_type'], 'presentationid'=>$presentationids[get_post("$tablename:$fieldname:presentationname")], 'foreigntableid'=>$foreigntablename ? $tableids[$foreigntablename] : null, 'nullallowed'=>$field['is_nullable'] == 'YES' ? true : false, 'defaultvalue'=>$field['column_default'], 'indesc'=>$indesc, 'inlist'=>$inlist, 'inedit'=>$inedit));

          $indescs += $indesc;
          $inlists += $inlist;
          $inedits += $inedit;

          if (get_post("$tablename:primary") == $fieldname)
            query('UPDATE `<metabasename>`.tables SET uniquefieldid = <fieldid> WHERE tableid = <tableid>', array('metabasename'=>$metabasename, 'fieldid'=>$fieldid, 'tableid'=>$tableid));
        }
        if (!$indescs)
          $errors[] = sprintf(_('no fields to desc for %s'), $tablename);
        if (!$inlists)
          $errors[] = sprintf(_('no fields to list for %s'), $tablename);
        if (!$inedits)
          $errors[] = sprintf(_('no fields to edit for %s'), $tablename);
      }
    }
  }

  if ($errors)
    error(join(', ', $errors));

  if (has_preference('messagy'))
    add_log('message', sprintf(_('structure of database %s extracted to metabase %s'), $databasename, $metabasename));

  internal_redirect(array('action'=>'show_database', 'metabasename'=>$metabasename, 'databasename'=>$databasename));
?>
