<?php
/**-------------------------------------------------
 | EasyZip.class V0.8 -  by Alban LOPEZ
 | Copyright (c) 2007 Alban LOPEZ
 | Email bugs/suggestions to alban.lopez+easyzip@gmail.com
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
//updated to replace PHP8+ deprecated methods
class zip
{
	public function infosZip ($src, $data=true)
	{
		$zip = new ZipArchive();
		if ($zip->open(realpath($src)) === true)
		{
			$content = [];
			for ($i = 0, $l = $zip->numFiles; $i < $l; $i++)
			{
				$file_name = $zip->getNameIndex($i);
				// skip directories
				if (in_array(substr($file_name, -1), ['/', '\\'], true)) {
					continue;
				}
				$entry = $zip->statIndex($i);
				$path = $entry['name'];
				$ins = $entry['comp_size'];
				$outs = $entry['size'];
				$ratio = ($outs > 0) ? round(($ins / $outs), 1) : 0.0;
				$content[$path] = [
					'Ratio' => 1.0 - $ratio,
					'Size' => $ins,
					'UnCompSize' => $outs
				];
				if ($data)
				{
					$content[$path]['Data'] = $zip->getFromIndex($i, $outs); //, flags?
				}
			}
			$zip->close();
			return $content;
		}
		return [];
	}

	public function extractZip ($src, $dest)
	{
		$zip = new ZipArchive();
		if ($zip->open(realpath($src)) === true)
		{
			$zip->extractTo($dest); // TODO prevent zip-slip NOTE temporary chage of 'open_basedir' ini setting causes problems later?  
			$zip->close();
			return true;
		}
		return false;
	}

	public function makeZip ($src, $dest)
	{
		$zip = new ZipArchive();
		if ($zip->open($dest, ZipArchive::CREATE | ZipArchive::EXCL) === true)
		{
			if (!is_array($src)) { $src = array($src); }
			foreach ($src as $item)
			{
				$real = realpath($item);
				if ($real) {
					$tmp = strtr($real, '\\', '/'); //TODO if Windows absolute path c.f. C:\over\there.ext
					if (is_dir($item)) {
						$real = realpath(dirname($item));
						$tmp2 = strtr($real, '\\', '/'); // ibid
						$this->addZipItem($zip, $tmp2.'/', $tmp.'/');
					}
					elseif (is_file($item)) {
						$zip->addFile($tmp);
					}
				}
				else {
					//TODO handle error
				}
			}
			$zip->close();
			return true;
		}
		return false;
	}

	public function addZipItem ($zip, $racine, $dir)
	{
		if (is_dir($dir))
		{
			$zip->addEmptyDir(str_replace($racine, '', $dir));
			$lst = scandir($dir);
			if (!empty($lst[0])) {
				if ($lst[0] == '.' || $lst[0] == '..') {
					array_shift($lst);
					if (!empty($lst[0])) {
						if ($lst[0] == '..' || $lst[0] == '.') {
							array_shift($lst);
						}
					}
				}
			}
			foreach ($lst as $item) {
				$this->addZipItem($zip, $racine, $dir.$item.(is_dir($dir.$item) ? '/' : ''));
			}
		}
		elseif (is_file($dir))
		{
			$zip->addFile($dir, str_replace($racine, '', $dir));
		}
	}
}
?>
