PHPFILES=*.php presentation/*.php
NLDIR=./locale/nl_NL/LC_MESSAGES/

none:

install:
	sudo chown :www-data session uploads
	chmod g+w session
	chmod a+x $(PHPFILES)

inventory_database:
	mysql < example/inventory_database.sql

inventory_user:
	mysql < example/inventory_user.sql

locales: $(NLDIR)messages.mo

$(NLDIR)messages.mo: $(NLDIR)messages.po
	msgfmt -o $@ $?

$(NLDIR)messages.po: $(PHPFILES)
	xgettext --omit-header -o $@ $(PHPFILES)

# $@ is the name of the file to be made. 
# $? is the names of the changed dependents. 
