<?php

namespace XF\Template\Compiler\Tag;

use XF\Template\Compiler;
use XF\Template\Compiler\Syntax\Tag;

class SideNav extends AbstractTag
{
	public function compile(Tag $tag, Compiler $compiler, array $context, $inlineExpected)
	{
		$value = $compiler->compileInlineList($tag->children, $context);

		if (isset($tag->attributes['mode']))
		{
			$mode = $tag->attributes['mode']->compile($compiler, $context, true);
		}
		else
		{
			$mode = "'replace'";
		}

		if (isset($tag->attributes['key']))
		{
			$key = $tag->attributes['key']->compile($compiler, $context, true);
		}
		else
		{
			$key = "null";
		}

		$compiler->write("{$compiler->templaterVariable}->modifySideNavHtml({$key}, {$value}, {$mode});");
		return $inlineExpected ? "''" : false;
	}
}
