<?php

namespace XF\Template\Compiler\Tag;

use XF\Template\Compiler;
use XF\Template\Compiler\Syntax\Tag;

class Trim extends AbstractTag
{
	public function compile(Tag $tag, Compiler $compiler, array $context, $inlineExpected)
	{
		$valueHtml = $compiler->compileInlineList($tag->children, $context);

		return "{$compiler->templaterVariable}->func('trim', array($valueHtml), false)";
	}
}
