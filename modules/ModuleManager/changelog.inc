<ul>
<li>Version 1.0. 10 January 2006. Initial Release.</li>
<li>Version 1.1. July, 2006. Released with the 1.0- beta</li>
<li>Version 1.1.1 August, 2006.  Require 1.0.1 of nuSOAP</li>
<li>Version 1.1.2 September, 2006.  Fixed a mistake that resulted in upgrade not not working at all</li>
<li>Version 1.1.3 September, 2006.
<ul>
  <li>Bumped minimum CMS Version to 1.0</li>
  <li>Now use 1 request to get the complete list of modules from the repository</li>
  <li>Added some missing lang strings</li>
  <li>Added the ability to reset the local cache of repository information</li>
  <li>Added the ability to restore the repository url to factory defaults</li>
</ul>
</li>
<li>Version 1.1.4 February, 2007.  Now handles the safe mode check, and disables upgrading or installing modules if the permissions are wrong.</li>
<li>Version 1.1.5 September, 2007. New preference to make only latest module version show. Added nice message after saving preferences</li>
<li>Version 1.1.6 May, 2008. Now show if available modules are incompatible with the current CMS_VERSION.</li>
<li>Version 1.2 June, 2008.<br>
This version should reduce the memory requirements of this module, and trade it off for performance on the server, and more requests to the server.
<ul>
  <li>Bumped Minimum CMS Version to 1.3</li>
  <li>Bumped Minimum repository version to 1.1</li>
  <li>Get rid of all of the session stuff</li>
  <li>Add support for requesting modules beginning with a prefix (usually a single letter)</li>
  <li>Add support for requesting only the newest versions of the modules</li>
</ul>
</li>
<li>Version 1.2.1 August, 2008.<br>
Added a warning message to the top of the admin display.
</li>
<li>Version 1.3 May, 2009.<br>
Added dependency checking.
</li>
<li>Version 1.3.3 March, 2010.
<ul>
  <li>PHP 5.x improvements (specifically remove warnings for PHP 5.3)</li>
  <li>Minor bug fixes.</li>
</ul>
</li>
<li>Version 1.4 June, 2010.
<ul>
  <li>Implemented automatic dependency calculation, and one-click installation.</li>
  <li>Assorted usability improvements.</li>
  <li>Minor bug fixes.</li>
</ul>
<li>Version 1.5 July, 2011.
<ul>
  <li>Changes to REST API.. No longer uses nuSOAP.</li>
  <li>Many optimizations to downloading and installing modules.</li>
  <li>Can now install older versions of modules easily.</li>
  <li>Handle automatic upgrading of dependencies.</li>
</ul>
</li>
<li>Version 2.1.1 Jul, 2017.<br>
Handle remote module installs upgrades, and activates via a 2 request process.
</li>
<li>Version 2.1.2 Aug, 2017.
<ul>
  <li>Sort modules in the installed tab better.</li>
  <li>Handle module not available better.</li>
</ul>
</li>
<li>Version 2.1.3. Nov, 2017.<br>
Add audit line displaying status if cannot connect to ModuleRepository.
</li>
<li>Version 2.1.4. Jul, 2018.<br>
PHP 7.2+ fixes.
</li>
<li>Version 2.1.5. Jan, 2019<br>
PHP 7.2+ fixes.
</li>
<li>Version 2.1.6 March, 2019.
<ul>
  <li>Fix authenticated object insertion vulnerability in the install-module action.</li>
  <li>Improve ordering of the dependencies before installing or upgrading modules.</li>
  <li>Add more auditing, particularly in the cached request stuff.</li>
</ul>
</li>
<li>Version 2.1.7 Sept, 2019.
<ul>
  <li>Minor exception handling improvements.</li>
  <li>Minor improvement to dependency detection for modules not in ModuleRepository.</li>
  <li>Minor fixes for PHP 7.3.</li>
</ul>
</li>
<li>Version 2.1.8 Oct, 2020.
<ul>
  <li>Handle reflected cross site scripting.</li>
  <li>Handle stored cross-site scripting.</li>
  <li>Download chunk size field size increased to 4.</li>
</ul>
</li>
<li>Version 2.1.9 July, 2023.
<ul>
  <li>Corrections, PHP compatibilty to 8.1.</li>
</ul>
</li>
</ul>
