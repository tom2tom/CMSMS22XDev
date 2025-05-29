<?php
#FileManager module class
#c) 2006-08 Morten Poulsen <morten@poulsen.org>
#(c) 2008 CMS Made Simple Foundation Inc <foundation@cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program; if not, write to the Free Software
#Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

/**
 * Public utility class used to manipulate images.
 */
final class imageEditor
{
	private function __construct() {}

	/**
	 * Resize an image
	 *
	 * @param GDImage $image the image
	 * @param string $mimeType the mimetype of the image
	 * @param int $image_width the new width
	 * @param int $image_height the new height
	 *
	 * @return GDImage $image after resizing
	 */
	public static function resize($image, $mimeType, $image_width, $image_height)
	{
		$newImage = @imagecreatetruecolor($image_width, $image_height);
		// c.f. typehelper image types 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff'.'tif','webp','avif','heif','svg'
		if ($mimeType && ($mimeType == 'image/gif' || $mimeType == 'image/png')) {
			//Keep transparency
			imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}

		imagecopyresampled($newImage, $image, 0, 0, 0, 0, $image_width, $image_height, imagesx($image), imagesy($image));
		return $newImage;
	}

	/**
	 * Crop an image
	 *
	 * @param GDImage $image the instance of image
	 * @param string $mimeType the mimetype of the image
	 * @param int $crop_x the x position to begin the crop (top-left)
	 * @param int $crop_y the y position to begin the crop (top-left)
	 * @param int $crop_width the width to end the crop (from the left to the right)
	 * @param int $crop_height the height to end the crop (from the top to the bottom)
	 *
	 * @return GDImage $image after cropping
	 */
	public static function crop($image, $mimeType, $crop_x, $crop_y, $crop_width, $crop_height)
	{
		$newImage = @imagecreatetruecolor($crop_width, $crop_height);
		// c.f. typehelper image types 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff'.'tif','webp','avif','heif','svg'
		if ($mimeType && ($mimeType == 'image/gif' || $mimeType == 'image/png')) {
			//Keep transparency
			imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
			imagealphablending($newImage, false);
			imagesavealpha($newImage, true);
		}

		imagecopyresampled($newImage, $image, 0, 0, $crop_x, $crop_y, $crop_width, $crop_height, $crop_width, $crop_height);
		return $newImage;
	}

	/**
	 * Get the mime type of a file
	 *
	 * @param string $path the path of the file
	 *
	 * @return string
	 */
	public static function getMime($path)
	{
		$info = getimagesize($path); //TODO better approach c.f. FileManager
		if (!$info) {
			return '';
		}
		// c.f. typehelper image types 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff'.'tif','webp','avif','heif','svg'
		$mime = image_type_to_mime_type($info[2]); //OR mime_content_type($path)
		switch ($mime) {
			case 'image/jpeg':
			case 'image/gif':
			case 'image/png':
			case 'image/bmp':
			case 'image/x-ms-bmp':
			case 'image/vnd.wap.wbmp':
			case 'image/webp':
			case 'image/avif':
			case 'image/apng':
			return $mime;
		default:
			return '';
		}
	}

	/**
	 * Get the width of an image stored in a file
	 *
	 * @param string $path the path of the file
	 *
	 * @return int
	 */
	public static function getWidth($path)
	{
		$info = getimagesize($path);
		if (!$info) {
			return 0;
		}
		return $info[0];
	}

	/**
	 * Load the specified file and return an image object
	 *
	 * @param string $path the path of the file
	 *
	 * @return  GdImage | error message | false | null
	 */
	public static function open($path)
	{
		$mimeType = self::getMime($path);
		if (!$mimeType){
			return "Invalid image type"; //TODO langify this e.g send lang key
		}
		// c.f. typehelper image types 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff'.'tif','webp','avif','heif','svg'
		switch ($mimeType) {
			case 'image/jpeg':
				return imagecreatefromjpeg($path);
			case 'image/gif':
				return imagecreatefromgif($path);
			case 'image/png': //'image/apng ok here ?
				return imagecreatefrompng($path);
			case 'image/bmp':
			case 'image/x-ms-bmp':
				return (PHP_VERSION_ID >= 70200) ? imagecreatefrombmp($path) : null;
			case 'image/vnd.wap.wbmp':
				return imagecreatefromwbmp($path);
			case 'image/webp':
				return imagecreatefromwebp($path);
			case 'image/avif':
				return (PHP_VERSION_ID >= 80100) ? imagecreatefromavif($path) : null;
			default:
				return null; // no object for unsupported filetype
		}
	}

	/**
	 * Save $image into a file
	 *
	 * @param GdImage $image instance of the image
	 * @param string $path the path of the file
	 * @param string $mimeType the mimetype of the image
	 * @return bool
	 */
	public static function save($image, $path, $mimeType)
	{
		// TODO c.f. typehelper image types 'jpg','jpeg','jpe','bmp','wbmp','gif','png','tiff','tif','webp','avif','heif','svg'
		switch ($mimeType) {
			case 'image/jpeg':
				return imagejpeg($image, $path);
			case 'image/gif':
				return imagegif($image, $path);
			case 'image/png': //NOT image/apng
				imagesavealpha($image, true);
				return imagepng($image, $path);
			case 'image/bmp':
			case 'image/x-ms-bmp':
				return (PHP_VERSION_ID >= 70200) ? imgbmp($image, $path) : false;
			case 'image/vnd.wap.wbmp':
				return imagewbmp($image, $path);
			case 'image/webp':
				imagesavealpha($image, true);
				return imagewebp($image, $path);
			case 'image/avif':
				if( PHP_VERSION_ID >= 80100 ) {
					imagesavealpha($image, true);
					return imageavif($image, $path); //TODO other params quality etc
				}
				//no break here
			default:
				return false;
		}
	}
}
?>
