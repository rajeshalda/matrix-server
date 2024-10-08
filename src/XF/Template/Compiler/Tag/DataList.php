<?php

namespace XF\Template\Compiler\Tag;

use XF\Template\Compiler;
use XF\Template\Compiler\Syntax\Tag;

class DataList extends AbstractFormElement
{
	public function compile(Tag $tag, Compiler $compiler, array $context, $inlineExpected)
	{
		$options = $this->compileAttributesAsArray($tag->attributes, $compiler, $context);
		$indent = $compiler->indent();
		$optionCode = "array(" . implode('', $options) . "\n$indent)";

		$contentHtml = $compiler->compileInlineList($tag->children, $context);

		return "{$compiler->templaterVariable}->dataList($contentHtml, $optionCode)";
	}
}
