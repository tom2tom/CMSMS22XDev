{strip}
	{process_pagedata}
{/strip}<!DOCTYPE html>
<html lang="{cms_get_language}">

<head>
	<title>{title} - {sitename}</title>
	{metadata}
	{cms_stylesheet}
</head>

<body>
	<header id="header">
		<h2>{sitename}</h2>
	</header>

	<nav id="menu">
		{Navigator}
	</nav>

	<section id="content">
		<h2>{title}</h2>
		{content}
	</section>
</body>

</html>
