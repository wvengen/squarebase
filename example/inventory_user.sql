CREATE USER sqbase@localhost IDENTIFIED BY 'sqbase';
GRANT ALL PRIVILEGES ON `inventory`.* TO sqbase@localhost;
GRANT ALL PRIVILEGES ON `inventory_metabase`.* TO sqbase@localhost;
