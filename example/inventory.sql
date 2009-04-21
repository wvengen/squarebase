# From http://www.itc.virginia.edu/desktop/web/database/mysql_instructions.html
# Enhanced by Squarebase

CREATE DATABASE IF NOT EXISTS inventory;

USE inventory;

DROP TABLE IF EXISTS computers;

CREATE TABLE computers (
   computerID   INT(11)      NOT NULL AUTO_INCREMENT,
   description  VARCHAR(80)  NOT NULL,
   PRIMARY KEY (computerID)
);

DROP TABLE IF EXISTS employees;

CREATE TABLE employees (
   employeeID   INT(11)      NOT NULL AUTO_INCREMENT,
   firstName    VARCHAR(20)  NOT NULL,
   lastName     VARCHAR(20)  NOT NULL,
   picture      BLOB,
   PRIMARY KEY (employeeID)
);

DROP TABLE IF EXISTS inventory;

CREATE TABLE inventory (
   inventoryID  INT(11)      NOT NULL AUTO_INCREMENT,
   dateAcquired DATE         NOT NULL,
   computerID   INT(11)      NOT NULL,
   employeeID   INT(11)      NOT NULL,
   comments     VARCHAR(200) NOT NULL,
   PRIMARY KEY (inventoryID),
   KEY (computerID),
   KEY (employeeID)
);

INSERT INTO computers (computerID, description) VALUES
( 1, 'Dell Optiplex'),
( 2, 'Dell Inspiron'),
( 3, 'Dell Dimension'),
( 4, 'iMac'),
( 5, 'Sun Ultra 1'),
( 6, 'Gateway laptop'),
( 7, 'Barbie Computer'),
( 8, 'Optiplex GX100'),
( 9, 'Palm IIIX'),
(10, 'Hot Wheels Computer'),
(33, 'Big Blue'),
(25, 'TravelMate 5000'),
(21, 'Hp Vectra 500'),
(27, 'Mac G4'),
(16, 'Gateway 800'),
(30, 'Palm VII'),
(31, 'Handspring Visor'),
(32, 'Atari'),
(36, 'Beowolf Cluster II');

INSERT INTO employees (employeeID, firstName, lastName) VALUES
( 1, 'John', 'Doe'),
( 2, 'Daffy', 'Duck'),
( 3, 'Mickey', 'Mouse'),
( 4, 'Minnie', 'Mouse'),
( 5, 'Ronald', 'McDonald'),
( 6, 'Homer', 'Simpson'),
( 9, 'Darth', 'Vader'),
(10, 'Bugs', 'Bunny'),
(11, 'Yosemite', 'Sam'),
(12, 'Hokey', 'Pokey'),
(13, 'Elroy', 'Jetson'),
(14, 'George', 'Jetson'),
(15, 'Bubba', 'JoeBob'),
(16, 'Billy', 'Bob'),
(17, 'Monty', 'Python');

INSERT INTO inventory (inventoryID, dateAcquired, computerID, employeeID, comments) VALUES
( 1, '1999-06-03',  4,  3, 'the purple one'),
( 2, '2000-09-15',  2,  1, 'for home use'),
( 3, '1999-10-02',  1,  5, 'on temporary loan'),
( 4, '1999-12-01',  3,  4, 'sent for repairs on 12/14'),
( 5, '2000-03-27',  5,  2, 'departmental web server'),
( 6, '2000-05-13',  4,  4, 'the teal one'),
( 7, '2000-09-19',  1,  1, 'for home use'),
( 8, '2000-07-12',  2,  3, 'for home use'),
( 9, '2000-10-09',  4,  1, 'john\'s first iMac'),
(15, '2000-05-03',  5,  3, 'mickey\'s web server'),
(17, '2000-10-01',  3,  3, 'for home use'),
(19, '2000-10-16',  4,  6, 'on loan for personal use'),
(13, '2000-10-05',  1,  2, 'daffy\'s dell'),
(14, '2000-10-12',  5,  4, 'minnie\'s web server'),
(21, '2000-10-18',  7,  9, 'Darth really likes the color'),
(20, '2000-10-17',  6,  1, 'for business trip'),
(23, '2000-10-18',  7,  6, 'for home use'),
(36, '2001-03-09',  7, 12, 'His last computer fell off a wall'),
(25, '2000-09-19',  7,  2, 'for upcoming business trip'),
(54, '2001-06-12', 33,  1, 'Comment One'),
(28, '2001-02-01',  1,  9, 'Please get this to me fast'),
(47, '2001-04-15', 31, 15, 'will arrive shortly'),
(48, '2001-04-15', 10, 10, 'woo hoo!'),
(56, '2001-08-10', 31, 17, 'yup'),
(55, '2000-04-23',  3, 13, 'Dell Rocks');
