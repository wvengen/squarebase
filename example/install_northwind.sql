CREATE DATABASE northwind IF NOT EXISTS;
USE northwind;

SOURCE example/northwind.sql

ALTER TABLE customers DROP PRIMARY KEY;
ALTER TABLE customers CHANGE COLUMN CustomerID CustomerAcronym VARCHAR(5) NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN CustomerID INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE customers ADD UNIQUE INDEX CustomerAcronym (CustomerAcronym);

ALTER TABLE orders CHANGE COLUMN CustomerID CustomerAcronym VARCHAR(5) DEFAULT '';
ALTER TABLE orders ADD COLUMN CustomerID INT(11) DEFAULT NULL AFTER OrderID;
UPDATE orders LEFT JOIN customers ON customers.CustomerAcronym = orders.CustomerAcronym SET orders.CustomerID = customers.CustomerID;
ALTER TABLE orders DROP COLUMN CustomerAcronym;

ALTER TABLE orders CHANGE ShipVia ShipperID INT(11) DEFAULT NULL;

QUIT
