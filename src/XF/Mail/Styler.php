<?php

namespace XF\Mail;

use Pelago\Emogrifier\CssInliner;
use XF\CssRenderer;
use XF\Language;
use XF\Util\Str;

class Styler
{
	/**
	 * @var CssRenderer
	 */
	protected $renderer;

	protected $cssCache = [];

	public function __construct(CssRenderer $renderer)
	{
		$this->renderer = $renderer;
	}

	public function styleHtml($html, $includeDefaultCss = true, ?Language $language = null)
	{
		if ($html)
		{
			$inliner = CssInliner::fromHtml($html);

			$inliner->inlineCss(
				$includeDefaultCss ? $this->getEmailCss($language) : ''
			);

			$html = $inliner->render();
		}

		return trim($html);
	}

	protected function getEmailCss(?Language $language = null)
	{
		$templater = $this->renderer->getTemplater();

		if ($language)
		{
			$restoreLanguage = $templater->getLanguage();
			$templater->setLanguage($language);
		}
		else
		{
			$restoreLanguage = null;
		}

		$languageId = $this->renderer->getLanguageId();
		if (!isset($this->cssCache[$languageId]))
		{
			$this->cssCache[$languageId] = $this->renderCoreCss();
		}

		if ($restoreLanguage)
		{
			$templater->setLanguage($restoreLanguage);
		}

		return $this->cssCache[$languageId];
	}

	protected function renderCoreCss()
	{
		return $this->renderer->render('email:core.less', false);
	}

	public function generateTextBody($html)
	{
		if (preg_match('#<body[^>]*>(.*)</body>#siU', $html, $match))
		{
			$html = trim($match[1]);
		}

		$text = $html;
		$text = preg_replace('#\s*<style[^>]*>.*</style>\s*#siU', '', $text);
		$text = preg_replace('#\s*<script[^>]*>.*</script>\s*#siU', '', $text);
		$text = preg_replace('#<img[^>]*alt="([^"]+)"[^>]*>#siU', '$1', $text);
		$text = preg_replace_callback(
			'#<span class="inlineSpoiler"[^>]*>(.*)</span>#siU',
			function (array $matches)
			{
				$spoilerText = $matches[1];

				return \XF::phrase('(spoiler)') . ' ' . str_repeat('*', Str::strlen($spoilerText));
			},
			$text
		);
		$text = preg_replace_callback(
			'#<a[^>]+href="([^"]+)"[^>]*>(.*)</a>#siU',
			function (array $matches)
			{
				$href = $matches[1];
				$text = $matches[2];

				if (substr($href, 0, 7) == 'mailto:')
				{
					$href = substr($href, 7);
				}

				if ($href == $text)
				{
					return $text;
				}
				else
				{
					return "$text ($href)";
				}
			},
			$text
		);
		$text = preg_replace('#<(h[12])[^>]*>(.*)</\\1>#siU', '****** $2 ******', $text);
		$text = preg_replace('#<(h[34])[^>]*>(.*)</\\1>#siU', '**** $2 ****', $text);
		$text = preg_replace('#<(h[56])[^>]*>(.*)</\\1>#siU', '** $2 **', $text);
		$text = preg_replace('#<hr[^>]*>(</hr>)?#i', '---------------', $text);

		$text = preg_replace('#\s*</td>\s*<td[^>]*>\s*#', ' - ', $text);
		$text = preg_replace('#\s*</tr>\s*<tr[^>]*>\s*#', "\n", $text);

		$text = strip_tags($text);
		$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML401, "utf-8");

		$text = preg_replace('#\n\t+#', "\n", $text);
		$text = preg_replace('#(\r?\n){3,}#', "\n\n", $text);

		return trim($text);
	}
}
