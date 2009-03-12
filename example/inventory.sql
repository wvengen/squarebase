# Enhanced by Squarebase - mediumint -> int & char -> varchar
# Added the following lines

DROP DATABASE IF EXISTS mst3k_Inventory;

CREATE DATABASE mst3k_Inventory;

USE mst3k_Inventory;

# Original script from http://www.itc.virginia.edu/desktop/web/database/mysql_instructions.html follows

# --------------------------------------------------------
#
# Table structure for table 'computers'
#

CREATE TABLE computers (
   computerID int(9) NOT NULL auto_increment,
   computerDescription varchar(80) NOT NULL,
   PRIMARY KEY (computerID),
   KEY ComputerID (computerID),
   UNIQUE ComputerID_2 (computerID)
);

#
# Dumping data for table 'computers'
#

INSERT INTO computers VALUES( '1', 'Dell Optiplex');
INSERT INTO computers VALUES( '2', 'Dell Inspiron');
INSERT INTO computers VALUES( '3', 'Dell Dimension');
INSERT INTO computers VALUES( '4', 'iMac');
INSERT INTO computers VALUES( '5', 'Sun Ultra 1');
INSERT INTO computers VALUES( '6', 'Gateway laptop');
INSERT INTO computers VALUES( '7', 'Barbie Computer');
INSERT INTO computers VALUES( '8', 'Optiplex GX100');
INSERT INTO computers VALUES( '9', 'Palm IIIX');
INSERT INTO computers VALUES( '10', 'Hot Wheels Computer');
INSERT INTO computers VALUES( '33', 'Big Blue');
INSERT INTO computers VALUES( '25', 'TravelMate 5000');
INSERT INTO computers VALUES( '21', 'Hp Vectra 500');
INSERT INTO computers VALUES( '27', 'Mac G4');
INSERT INTO computers VALUES( '16', 'Gateway 800');
INSERT INTO computers VALUES( '30', 'Palm VII');
INSERT INTO computers VALUES( '31', 'Handspring Visor');
INSERT INTO computers VALUES( '32', 'Atari');
INSERT INTO computers VALUES( '36', 'Beowolf Cluster II');

# --------------------------------------------------------
#
# Table structure for table 'employees'
#

CREATE TABLE employees (
   employeeID int(6) NOT NULL auto_increment,
   firstName varchar(15) NOT NULL,
   lastName varchar(25) NOT NULL,
   PRIMARY KEY (employeeID)
);

#
# Dumping data for table 'employees'
#

INSERT INTO employees VALUES( '1', 'John', 'Doe');
INSERT INTO employees VALUES( '2', 'Daffy', 'Duck');
INSERT INTO employees VALUES( '3', 'Mickey', 'Mouse');
INSERT INTO employees VALUES( '4', 'Minnie', 'Mouse');
INSERT INTO employees VALUES( '5', 'Ronald', 'McDonald');
INSERT INTO employees VALUES( '6', 'Homer', 'Simpson');
INSERT INTO employees VALUES( '10', 'Bugs', 'Bunny');
INSERT INTO employees VALUES( '9', 'Darth', 'Vader');
INSERT INTO employees VALUES( '11', 'Yosemite', 'Sam');
INSERT INTO employees VALUES( '12', 'Hokey', 'Pokey');
INSERT INTO employees VALUES( '13', 'Elroy', 'Jetson');
INSERT INTO employees VALUES( '14', 'George', 'Jetson');
INSERT INTO employees VALUES( '15', 'Bubba', 'JoeBob');
INSERT INTO employees VALUES( '16', 'Billy', 'Bob');
INSERT INTO employees VALUES( '17', 'Monty', 'Python');

# --------------------------------------------------------
#
# Table structure for table 'inventory'
#

CREATE TABLE inventory (
   inventoryID int(9) NOT NULL auto_increment,
   dateAcquired date DEFAULT '0000-00-00' NOT NULL,
   computerID int(9) DEFAULT '0' NOT NULL,
   employeeID int(9) DEFAULT '0' NOT NULL,
   comments varchar(200) NOT NULL,
   PRIMARY KEY (inventoryID),
   KEY InventoryID (inventoryID),
   UNIQUE InventoryID_2 (inventoryID)
);

#
# Dumping data for table 'inventory'
#

INSERT INTO inventory VALUES( '1', '1999-06-03', '4', '3', 'the purple one');
INSERT INTO inventory VALUES( '2', '2000-09-15', '2', '1', 'for home use');
INSERT INTO inventory VALUES( '3', '1999-10-02', '1', '5', 'on temporary loan');
INSERT INTO inventory VALUES( '4', '1999-12-01', '3', '4', 'sent for repairs on 12/14');
INSERT INTO inventory VALUES( '5', '2000-03-27', '5', '2', 'departmental web server');
INSERT INTO inventory VALUES( '6', '2000-05-13', '4', '4', 'the teal one');
INSERT INTO inventory VALUES( '7', '2000-09-19', '1', '1', 'for home use');
INSERT INTO inventory VALUES( '8', '2000-07-12', '2', '3', 'for home use');
INSERT INTO inventory VALUES( '9', '2000-10-09', '4', '1', 'john\'s first iMac');
INSERT INTO inventory VALUES( '15', '2000-05-03', '5', '3', 'mickey\'s web server');
INSERT INTO inventory VALUES( '17', '2000-10-01', '3', '3', 'for home use');
INSERT INTO inventory VALUES( '19', '2000-10-16', '4', '6', 'on loan for personal use');
INSERT INTO inventory VALUES( '13', '2000-10-05', '1', '2', 'daffy\'s dell');
INSERT INTO inventory VALUES( '14', '2000-10-12', '5', '4', 'minnie\'s web server');
INSERT INTO inventory VALUES( '21', '2000-10-18', '7', '9', 'Darth really likes the color');
INSERT INTO inventory VALUES( '20', '2000-10-17', '6', '1', 'for business trip');
INSERT INTO inventory VALUES( '23', '2000-10-18', '7', '6', 'for home use');
INSERT INTO inventory VALUES( '36', '2001-03-09', '7', '12', 'His last computer fell off a wall');
INSERT INTO inventory VALUES( '25', '2000-09-19', '7', '2', 'for upcoming business trip');
INSERT INTO inventory VALUES( '54', '2001-06-12', '33', '1', 'Comment One');
INSERT INTO inventory VALUES( '28', '2001-02-01', '1', '9', 'Please get this to me fast');
INSERT INTO inventory VALUES( '47', '2001-04-15', '31', '15', 'will arrive shortly');
INSERT INTO inventory VALUES( '48', '2001-04-15', '10', '10', 'woo hoo!');
INSERT INTO inventory VALUES( '56', '2001-08-10', '31', '17', 'yup');
INSERT INTO inventory VALUES( '55', '2000-04-23', '3', '13', 'Dell Rocks');
