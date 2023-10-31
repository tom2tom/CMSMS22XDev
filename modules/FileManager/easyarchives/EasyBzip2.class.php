<?php
/**-------------------------------------------------
 | EasyBzip2.class V0.8 -  by Alban LOPEZ
 | Copyright (c) 2007 Alban LOPEZ
 | Email bugs/suggestions to alban.lopez+eazybzip2@gmail.com
 +--------------------------------------------------
 | This file is part of EasyArchive.class V0.9.
 | EasyArchive is free software: you can redistribute it and/or modify
 | it under the terms of the GNU General Public License as published by
 | the Free Software Foundation, either version 3 of the License, or
 | (at your option) any later version.
 | EasyArchive is distributed in the hope that it will be useful,
 | but WITHOUT ANY WARRANTY; without even the implied warranty of
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 | See the GNU General Public License for more details on http://www.gnu.org/licenses/
 +--------------------------------------------------
 http://www.phpclasses.org/browse/package/4239.html **/
class bzip2
{
/**
// You can use this class like this.
$test = new bzip2();
$test->makeBzip2('./','./toto.bzip2');
var_export($test->infosBzip2('./toto.bzip2'));
$test->extractBzip2('./toto.bzip2', './new/');
**/
	public function makeBzip2($src, $dest='')//: string|int|false
	{
		$Bzip2 = bzcompress((strpos(chr(0),$src) ? file_get_contents ($src) : $src), 6);// compressed string or int error no.
		if (empty($dest)) return $Bzip2;
		elseif (file_put_contents($dest, $Bzip2)) return $dest;
		return false;
	}

	public function infosBzip2 ($src, $data=true)//: array
	{
		$all = $this->extractBzip2($src);
		$outs = strlen($all);
		$ins = filesize($src);
		$ratio = ($outs > 0) ? round(100 - ($ins / $outs * 100), 1) : 1.0;
		$content = [
			'UnCompSize'=>$outs,
			'Size'=>$ins,
			'Ratio'=>$ratio
		];
		if ($data) $content['Data'] = $data;
		return $content;
	}

	public function extractBzip2($src, $dest='')//: string | false
	{
		$bz = bzopen($src, "r");
		$data = '';
		while (!feof($bz)) {
			$block = bzread($bz, 1048576); // aka 1024*1024, too bad about huge content!
			if ($block === false) {
				bzclose($bz);
				throw new Exception('Bzip2 archive read failed : '.$src);
			}
			if (bzerrno($bz) !== 0) {
				bzclose($bz);
				throw new Exception('Bzip2 archive compression problem : '.$src);
			}
			$data .= $block;
		}
		bzclose($bz);
		if (!$dest) return $data;
		elseif (file_put_contents($dest, $data)) return $dest;
		return false;
	}
}
?>
