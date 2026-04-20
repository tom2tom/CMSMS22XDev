Quick notes for debugging/developing the .phar installer

1:
--
For developing with the .phar installer without building the thing for
each test, it might be useful to specify a
 ?dest=/full/path/to/siterootdirectory
argument in the INITIAL url e.g:
 https://www.yoursite.com/cmsms-9.10.11-install.php?dest=/var/www/cmsms_dir
