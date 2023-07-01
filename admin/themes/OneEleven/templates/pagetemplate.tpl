<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html lang="{$lang|truncate:'2':''}" dir="{$lang_dir}">
	<head>
	{$thetitle=$pagetitle}
	{if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
	{if $thetitle}{$thetitle="{$thetitle} - "}{/if}
		<meta charset="utf-8" />
		<title>{$thetitle}{sitename}</title>
		<base href="{$config.admin_url}/" />
		<meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2023. All rights reserved." />
		<meta name="robots" content="noindex, nofollow" />
		<meta name="referrer" content="origin" />
		<meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
		<meta name="HandheldFriendly" content="True" />
		<meta name='msapplication-TileImage' content='{$config.admin_url}/themes/OneEleven/images/favicon/ms-application-icon.png' />
		<meta name='msapplication-TileColor' content='#f89938' />
		<link rel="shortcut icon" href="{$config.admin_url}/themes/OneEleven/images/favicon/cmsms-favicon.ico" />
		<link rel='apple-touch-icon' href='{$config.admin_url}/themes/OneEleven/images/favicon/apple-touch-icon-iphone.png' />
		<link rel='apple-touch-icon' sizes='72x72' href='{$config.admin_url}/themes/OneEleven/images/favicon/apple-touch-icon-ipad.png' />
		<link rel='apple-touch-icon' sizes='114x114' href='{$config.admin_url}/themes/OneEleven/images/favicon/apple-touch-icon-iphone4.png' />
		<link rel='apple-touch-icon' sizes='144x144' href='{$config.admin_url}/themes/OneEleven/images/favicon/apple-touch-icon-ipad3.png' />
		<!-- custom jQueryUI 1.12.1 styling See link in JQUI stylesheet for color reference //-->
		<link href="themes/OneEleven/css/default-cmsms/jquery-ui-1.12.1.custom.min.css" rel="stylesheet" />
		<link href="style.php" rel="stylesheet" />
		<!-- learn IE html5 -->
		<!--[if lt IE 9]>
		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		{cms_jquery include_css=0}
		<script type="text/javascript" src="themes/OneEleven/includes/standard.js"></script>
		<!-- THIS IS WHERE EXTRA HEADER STUFF GOES -->
		{$headertext|default:''}
	</head>
	<body lang="{$lang|truncate:'2':''}" id="{$pagetitle|adjust:'md5'}" class="oe_{$pagealias}">
		<!-- start container -->
		<div id="oe_container" class="sidebar-on">
			<!-- start header -->
			<header role="banner" class="cf header">
				<!-- start header-top -->
				<div class="header-top cf">
					<!-- logo -->
					<div class="cms-logo">
						<a href="http://www.cmsmadesimple.org" rel="external"><img src="{$config.admin_url}/themes/OneEleven/images/layout/cmsms-logo.jpg" width="205" height="69" alt="CMS Made Simple" title="CMS Made Simple" /></a>
					</div>
					<!-- title -->
					<span class="admin-title"> {lang('adminpaneltitle')} - {sitename}</span>
				</div>
				<div class='clear'></div>
				<!-- end header-top //-->
				<!-- start header-bottom -->
				<div class="header-bottom cf">
					<!-- welcome -->
					<div class="welcome">
					{if isset($myaccount)}
						<span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{lang('myaccount')}">{lang('myaccount')}</a> {lang('welcome_user')}: <a href="myaccount.php?{$secureparam}">{$user->username}</a></span>
					{else}
						<span><a class="welcome-user">{lang('myaccount')}</a> {lang('welcome_user')}: {$user->username}</span>
					{/if}
					</div>
					<!-- bookmarks -->
					{include file='shortcuts.tpl'}
				</div>
				<!-- end header-bottom //-->
			</header>
			<!-- end header //-->
			<!-- start content -->
			<div id="oe_admin-content">
				<div class="shadow">
					&nbsp;
				</div>
				<!-- start sidebar -->
				<div id="oe_sidebar">
					<aside>
						<span title="{lang('open')}/{lang('close')}" class="toggle-button close">{lang('open')}/{lang('close')}</span>
						{include file='navigation.tpl' nav=$theme->get_navigation_tree() depth=0 nocache}
					</aside>
				</div>
				<!-- end sidebar //-->
				<!-- start main -->
				<div id="oe_mainarea" class="cf">
					{strip}
					{include file='messages.tpl'}
					<article role="main" class="content-inner">
						<header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
							{if isset($module_icon_url) or isset($pagetitle)}
							<h1>{if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if}
							{$pagetitle|default:''}
							</h1>
							{/if}
							{if isset($module_help_url)}<span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
						</header>
						{if $pagetitle && $subtitle}<header class="subheader"><h3 class="subtitle">{$subtitle}</h3></header>{/if}
						<section class="cf">
							{$content}
						</section>
					</article>
					{/strip}
				</div>
				<!-- end main //-->
				<div class="spacer">
					&nbsp;
				</div>
			</div>
			<!-- end content //-->
			<!-- start footer -->
			{include file='footer.tpl'}
			<!-- end footer //-->
			{$footertext|default:''}
		</div>
		<!-- end container //-->
	</body>
</html>
