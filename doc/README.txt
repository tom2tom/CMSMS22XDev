CMS Made Simple https://www.cmsmadesimple.org

For information on installation, see INSTALL.txt.
For information on upgrading from a previous version, see UPGRADE.txt.

Official documentation website: https://docs.cmsmadesimple.org

CMSMS uses the Smarty template engine extensively.
Its documentation website is: https://smarty-php.github.io/smarty/stable
Smarty's LGPL3 license and various documentation files are located in the Smarty subdirectory of this distribution.

CMSMS uses jQuery extensively.
Its documentation website is: https://api.jquery.com
jQuery's MIT license is summarised at https://www.tldrlegal.com/license/mit-license.

CMSMS uses PHPMailer for backend functionality to send email.
Its documentation website is: https://phpmailer.github.io/PHPMailer
PHPMailer's license is LGPL 2.1 (only)

System configuration settings

In addition to the settings documented at
https://docs.cmsmadesimple.org/configuration/config-file/config-reference

the following might also be relevant in some circumstances:

developer_mode whether special circumstances apply
tmp_config_location filepath of default folder for storing Smarty config files
host_whitelist for working around $_SERVER['HTTP_HOST'] spoofing

FileTypeHelper_audio_extensions
FileTypeHelper_document_extensions
FileTypeHelper_executable_extensions
FileTypeHelper_image_extensions
FileTypeHelper_video_extensions
FileTypeHelper_xml_extensions
If used, values for the above properties are expected each to be a
string of comma-separated lower-case filename extensions which are to
supplement the corresponding built-in extensions.
Refer to file <website path to>/lib/classes/class.FileTypeHelper.php.

ssl_url
ssl_uploads_url
ssl_image_uploads_url
These are all deprecated, instead use root_url uploads_url image_uploads_url
which all suffice regardless of request type.
