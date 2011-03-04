create database coffeebreak;

use coffeebreak;

-- coffees

create table coffees (
  coffeeid     integer,
  coffeename   varchar(32),
  supplierid   integer,
  price        float,
  sales        integer,
  total        integer,
  primary key (`coffeeid`)
);

insert into coffees values (3, 'Colombian',          101, 7.99, 0, 0);
insert into coffees values (5, 'French_Roast',        49, 8.99, 0, 0);
insert into coffees values (6, 'Espresso',           150, 9.99, 0, 0);
insert into coffees values (8, 'Colombian_Decaf',    101, 8.99, 0, 0);
insert into coffees values (9, 'French_Roast_Decaf',  49, 9.99, 0, 0);

-- suppliers

create table suppliers (
  supplierid   integer,
  suppliername varchar(40),
  street       varchar(40),
  city         varchar(20),
  state        char(2),
  zip          char(5),
  primary key (`supplierid`)
);

insert into suppliers values (101, 'Acme, Inc.',      '99 Market Street', 'Groundsville', 'CA', '95199');
insert into suppliers values ( 49, 'Superior Coffee', '1 Party Place',    'Mendocino',    'CA', '95460');
insert into suppliers values (150, 'The High Ground', '100 Coffee Lane',  'Meadows',      'CA', '93966');
