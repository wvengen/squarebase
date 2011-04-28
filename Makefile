PHPFILES=*.php presentation/*.php
NLDIR=./locale/nl_NL/LC_MESSAGES/
PIDFILE=/tmp/SELENIUM_SERVER_PID

.PHONY: none install test testbegin testmiddle testend selenium_install commit inventory locales clean

none:

install:
	sudo chown :www-data session upload
	chmod g+w session
	chmod a+x $(PHPFILES)

test: testend testbegin testmiddle testend

# auto-install of Selenium 1.0.3 for testing
selenium_install:
	wget -c -O test/selenium_rc.zip http://selenium.googlecode.com/files/selenium-remote-control-1.0.3.zip
	unzip -p test/selenium_rc.zip selenium-server-1.0.3/selenium-server.jar >test/selenium-server.jar
	for SRC in `unzip -Z -1 test/selenium_rc.zip | grep 'PEAR/Testing/.*[^/]$$'`; do \
		DST=`echo "$$SRC" | sed 's@^.*/Testing/@test/Testing/@p;d'`; \
		mkdir -p `dirname "$$DST"`; \
		unzip -p test/selenium_rc.zip "$$SRC" >"$$DST"; \
	done
	[ -e test/selenium-server.jar -a -e test/Testing/Selenium.php ] && rm -f test/selenium_rc.zip

test/selenium-server.jar: test/Testing/Selenium.php

test/Testing/Selenium.php:
	@echo
	@echo "    Type 'make selenium_install' to fetch and extract Selenium 1.0.3 locally"
	@echo
	@false

testbegin: test/selenium-server.jar test/Testing/Selenium.php
	java -jar test/selenium-server.jar 1>/dev/null 2>/dev/null & echo $$!>$(PIDFILE)
	sleep 10

testmiddle:
	php test/test.php

testend:
	if [ -e "$(PIDFILE)" ]; then \
		kill `cat $(PIDFILE)`; \
		rm -f "$(PIDFILE)"; \
	fi

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

clean:
	rm -f session/* upload/*.*
	rm -f test/selenium_rc.zip test/selenium-server.jar
	rm -f test/Testing/Selenium.php test/Testing/Selenium/Exception.php
	rmdir test/Testing/Selenium test/Testing

# $@ is the name of the file to be made. 
# $? is the names of the changed dependents. 
