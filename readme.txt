Hi, welcome to the wonderful world of Squarebase

Squarebase gives you web-based access to your MySql databases without having to write a single line of programming code.

Notes:

1. Squarebase can be found in the Subversion repository at http://svn.xp-dev.com/svn/fransre-squarebase/
   Check out with the following command.
   $ svn checkout http://svn.xp-dev.com/svn/fransre-squarebase/

2. After checking out, perform the following commands, because Subversion doesn't set the permissions right.
   $ sudo chown :www-data session tmp
   $ chmod g+w session tmp
