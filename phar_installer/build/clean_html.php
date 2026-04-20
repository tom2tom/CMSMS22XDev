#!/usr/bin/env php
<?php
$srcpath = __DIR__.DIRECTORY_SEPARATOR.'';
if (!is_file($srcpath)) {
	exit("OOPS, cannot find '$srcpath'!\n");
}

require '/usr/share/downloads/unpack/htmLawed.php';

$checktext = file_get_contents($srcpath);
if ($checktext) {
	echo "Cleaning content of file '$srcpath'\n";
	//see https://www.bioinformatics.org/phplabware/internal_utilities/htmLawed/htmLawed_README.htm
	$config = ['valid_xhtml'=>0]; //TODO keep <br> <hr> {* .... *} 'base_url'=>
	$processed = htmLawed($checktext, $config);
//	$processed = htmLawed($checktext);

	$destpath = dirname($srcpath).DIRECTORY_SEPARATOR.'awed.html';
	file_put_contents($destpath, $processed);
	echo "Check content of file '$destpath'\n";
} else {
	echo "Nothing to process\n";
}
