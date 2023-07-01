{strip}
	{process_pagedata}
{/strip}<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html lang="{cms_get_language}">

<head>
	<title>{title} - {sitename}</title>
	{metadata}
	{cms_stylesheet}
</head>

<body>
	<header id="header">
		<h1>{sitename}</h1>
	</header>

	<nav id="menu">
		{Navigator}
	</nav>

	<section id="content">
		<h1>{title}</h1>
		{content}
	</section>
</body>

</html>
