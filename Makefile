none:

install:
	sudo chown :www-data session
	chmod g+w session

./locale/nl_NL/LC_MESSAGES/messages.mo: ./locale/nl_NL/LC_MESSAGES/messages.po
	msgfmt -o $@ $?

./locale/nl_NL/LC_MESSAGES/messages.po: *.php presentation/*.php
	xgettext --omit-header -j -o $@ $?

# $@ is the name of the file to be made. 
# $? is the names of the changed dependents. 
