PHPFILES=*.php presentation/*.php
NLDIR=./locale/nl_NL/LC_MESSAGES/
PIDFILE=/tmp/SELENIUM_SERVER_PID

.PHONY: none install test testbegin testmiddle testend commit inventory locales

none:

install:
	sudo chown :www-data session uploads
	chmod g+w session
	chmod a+x $(PHPFILES)

test: testend testbegin testmiddle testend

testbegin:
	java -jar ~/bin/selenium-server.jar 1>/dev/null 2>/dev/null & echo $$!>$(PIDFILE)
	sleep 10

testmiddle:
	php tests/test.php

testend:
	-kill `cat $(PIDFILE)`

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
