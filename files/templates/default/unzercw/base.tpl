{foreach from=$js item=file}
	<script type="text/javascript" src="{$file}"></script>
{/foreach}

{foreach from=$css item=file}
	<link rel="stylesheet" type="text/css" href="{$file}" />
{/foreach}

<h1>{$title}</h1>

{$content}
