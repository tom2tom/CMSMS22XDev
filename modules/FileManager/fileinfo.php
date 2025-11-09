<?php

use CMSMS\FileTypeHelper;

// These constants can be used for the $out parameter in image_info().
define ('IMAGE_WIDTH', 'width');
define ('IMAGE_HEIGHT', 'height');
define ('IMAGE_TYPE', 'type');
define ('IMAGE_ATTR', 'attr');
define ('IMAGE_BITS', 'bits');
define ('IMAGE_CHANNELS', 'channels');
define ('IMAGE_MIME', 'mime');

/**
 * Get information about the specified image-file
 *
 * @param string $file Filepath to be processed. Default ''
 * @param string $out Wanted image-parameter. Default ''
 * Valid values for $out are:
 *  'width','height','type','attr','bits','channels','mime'
 *  and equivalent defined constants
 *  IMAGE_WIDTH,IMAGE_HEIGHT,IMAGE_TYPE,IMAGE_ATTR,IMAGE_BITS,
 *  IMAGE_CHANNELS,IMAGE_MIME.
 *
 * @return mixed array | string | false
 * If $out is not empty, a string representing that information will be returned.
 * If $out is empty, an array containing all the information is returned,
 * which will look like the following:
 *  [width] => int (width),
 *  [height] => int (height),
 *  [type] => string (type),
 *  [attr] => string (attributes formatted for IMG tags),
 *  [bits] => int (bits),
 *  [channels] => int (channels),
 *  [mime] => string (mime-type)
 * Returns false if $file is empty or is not a file or not an image file,
 * or the function otherwise fails.
 */
function image_info($file = '', $out = '')
{
  if (!$file || !is_file($file)) {
   // echo '<p><b>Error:</b> image_info() => first argument must be a file.</p>';
    return false;
  }
  // Per PHP advice - don't rely on getimagesize() to detect image.
  $helper = new FileTypeHelper();
  if (!$helper->is_image($file)) {
    //echo '<p><b>Error:</b> image_info() => first argument must be an image file.</p>';
    return false;
  }

  // The keys we want instead of 0, 1, 2, 3, plus 'bits', 'channels', 'mime'
  $redefine_keys = array(
    'width',
    'height',
    'type',
    'attr',
    'bits',
    'channels',
    'mime'
  );

  // If $out is supplied, but is not a valid key, return everything.
  if ($out && !in_array($out, $redefine_keys)) $out = '';

  // Useful values for the third index.
  $types = array(
    IMAGETYPE_GIF => 'GIF',
    IMAGETYPE_JPEG => 'JPG',
    IMAGETYPE_JPEG2000 => 'JPEG',
    IMAGETYPE_PNG => 'PNG',
    IMAGETYPE_SWF => 'SWF',
    IMAGETYPE_PSD => 'PSD',
    IMAGETYPE_BMP => 'BMP',
    IMAGETYPE_WBMP => 'WBMP',
    IMAGETYPE_XBM => 'XBM',
    IMAGETYPE_TIFF_II => 'TIFF(intel byte order)',
    IMAGETYPE_TIFF_MM => 'TIFF(motorola byte order)',
    IMAGETYPE_IFF => 'IFF',
    IMAGETYPE_JB2 => 'JB2',
    IMAGETYPE_JPC => 'JPC',
    IMAGETYPE_JP2 => 'JP2',
    IMAGETYPE_JPX => 'JPX',
    IMAGETYPE_ICO => 'ICO',
    IMAGETYPE_UNKNOWN => 'UNKNOWN'
  );
  if (defined('IMAGETYPE_AVIF')) $types[IMAGETYPE_AVIF] = 'AVIF'; // PHP 8.1+
  if (defined('IMAGETYPE_SWC')) $types[IMAGETYPE_SWC] = 'SWC'; // sometimes N/A
  if (defined('IMAGETYPE_WEBP')) $types[IMAGETYPE_WEBP] = 'WEBP'; // PHP 7.1+

  // Get the image info.
  if (!$temp = @getimagesize($file)) {
    //echo "<p><b>Error:</b>getimagesize failed for $file.</p>";
    return false;
  }

  // Get the values returned by getimagesize().
  $temp = array_values($temp);

  $data = array();
  // Make an array using values from $redefine_keys as keys and values from $temp as values.
  foreach ($temp as $k => $v) {
    if (array_key_exists($k, $redefine_keys)) { //TODO deal with undocumented extra data sometimes
      if ($v === null) { $v = ''; } // just in case
      $data[$redefine_keys[$k]] = $v;
    }
  }

  // Make 'type' useful.
  // see also: image_type_to_extension($data['type'], false);
  if (array_key_exists($data['type'], $types)) {
      $data['type'] = $types[$data['type']];
  } else {
      $data['type'] = 'UNKNOWN';
  }

  // Return the desired information.
  return ($out) ? $data[$out] : $data;
}

/**
 * Get image width, height, bits properties
 *
 * @param string $filename Filesystem path
 * @param string $ext file extension, any case. But only 'png' or 'gif' or 'jpg' will be processed
 * @param bool $dir Whether processing a folder. Default false
 * @return string maybe '&nbsp;'
*/
function GetFileInfo($filename, $ext, $dir = false)
{
  if ($dir) {
    $result = '&nbsp;';
  } else {
    switch (strtolower($ext)) {
      //TODO handle all types supported by image_info()
      case 'png':
      case 'gif':
      case 'jpg':
        $imginfo = image_info($filename);
        if ($imginfo) {
          $result = $imginfo['width'].'x'.$imginfo['height'].'x'.$imginfo['bits'];
        }
        break;
      default:
        $result = '&nbsp;';
    }
  }
  return $result;
}
?>
