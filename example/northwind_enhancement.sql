alter table customers drop primary key;
alter table customers change column CustomerID CustomerAcronym varchar(5) not NULL default '';
alter table customers add column CustomerID int(11) not NULL auto_increment primary key first;
alter table customers add unique index CustomerAcronym (CustomerAcronym);

alter table orders change column CustomerID CustomerAcronym varchar(5) default '';
alter table orders add column CustomerID int(11) default NULL after OrderID;
update orders left join customers on customers.CustomerAcronym = orders.CustomerAcronym set orders.CustomerID = customers.CustomerID;
alter table orders drop column CustomerAcronym;

alter table orders change ShipVia ShipperID int(11) default NULL;
