PHPFILES=*.php presentation/*.php
NLDIR=./locale/nl_NL/LC_MESSAGES/
PIDFILE=/tmp/SELENIUM_SERVER_PID

none:

install:
	sudo chown :www-data session uploads
	chmod g+w session
	chmod a+x $(PHPFILES)

test:
	java -jar ~/bin/selenium-server.jar 1>/dev/null & echo $$!>$(PIDFILE)
	sleep 10
	php tests/test.php
	kill `cat $(PIDFILE)`

commit: test
	svn commit

inventory:
	mysql < example/inventory_schema.sql
	mysql < example/inventory_data.sql
	mysql < example/inventory_user.sql

locales: $(NLDIR)messages.mo

$(NLDIR)messages.mo: $(NLDIR)messages.po
	msgfmt -o $@ $?

$(NLDIR)messages.po: $(PHPFILES)
	xgettext --omit-header -j -o $@ $(PHPFILES)

# $@ is the name of the file to be made. 
# $? is the names of the changed dependents. 
