# From http://www.itc.virginia.edu/desktop/web/database/mysql_instructions.html
# Enhanced by Squarebase

DROP DATABASE IF EXISTS inventory;

CREATE DATABASE inventory;

CREATE TABLE inventory.computers (
   computerID   INT(11)      NOT NULL AUTO_INCREMENT,
   description  VARCHAR(80)  NOT NULL,
   PRIMARY KEY (computerID),
   UNIQUE KEY (description)
);

CREATE TABLE inventory.employees (
   employeeID   INT(11)      NOT NULL AUTO_INCREMENT,
   firstName    VARCHAR(20)  NOT NULL,
   lastName     VARCHAR(20)  NOT NULL,
   PRIMARY KEY (employeeID),
   UNIQUE KEY (firstName, lastName)
);

CREATE TABLE inventory.usages (
   usageID      INT(11)      NOT NULL AUTO_INCREMENT,
   dateAcquired DATE         NOT NULL,
   computerID   INT(11)      NOT NULL,
   employeeID   INT(11)      NOT NULL,
   comments     VARCHAR(200) NOT NULL,
   PRIMARY KEY (usageID),
   KEY (computerID),
   KEY (employeeID),
   UNIQUE KEY (dateAcquired, computerID, employeeID, comments)
);

DROP DATABASE IF EXISTS inventory_metabase;
