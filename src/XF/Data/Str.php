<?php

namespace XF\Data;

class Str
{
	/**
	 * @return array<string, string>
	 */
	public function transliteratorIds(): array
	{
		return [
			'am' => 'Amharic-Latin',
			'ar' => 'Arabic-Latin',
			'az' => 'Azerbaijani-Latin',
			'be' => 'Belarusian-Latin',
			'bg' => 'Bulgarian-Latin',
			'bn' => 'Bengali-Latin',
			'de' => 'de-ASCII',
			'el' => 'Greek-Latin',
			'fa' => 'Persian-Latin',
			'he' => 'Hebrew-Latin',
			'hy' => 'Armenian-Latin',
			'ka' => 'Georgian-Latin',
			'kk' => 'Kazakh-Latin',
			'ky' => 'Kirghiz-Latin',
			'ko' => 'Korean-Latin',
			'mk' => 'Macedonian-Latin',
			'mn' => 'Mongolian-Latin',
			'or' => 'Oriya-Latin',
			'ps' => 'Pashto-Latin',
			'ru' => 'Russian-Latin',
			'sr' => 'Serbian-Latin',
			'sr_Cyrl' => 'Serbian-Latin',
			'th' => 'Thai-Latin',
			'tk' => 'Turkmen-Latin',
			'uk' => 'Ukrainian-Latin',
			'uz' => 'Uzbek-Latin',
			'zh' => 'Han-Latin',
		];
	}

	/**
	 * @return array<string, string>
	 */
	public function latinToAscii(): array
	{
		return [
			'Æ' => 'AE',
			'Ð' => 'D',
			'Ø' => 'O',
			'Þ' => 'TH',
			'ß' => 'ss',
			'æ' => 'ae',
			'ð' => 'd',
			'ø' => 'o',
			'þ' => 'th',
			'Đ' => 'D',
			'đ' => 'd',
			'Ħ' => 'H',
			'ħ' => 'h',
			'ı' => 'i',
			'ĸ' => 'q',
			'Ŀ' => 'L',
			'ŀ' => 'l',
			'Ł' => 'L',
			'ł' => 'l',
			"ŉ" => "'n",
			'Ŋ' => 'N',
			'ŋ' => 'n',
			'Œ' => 'OE',
			'œ' => 'oe',
			'Ŧ' => 'T',
			'ŧ' => 't',
			'ƀ' => 'b',
			'Ɓ' => 'B',
			'Ƃ' => 'B',
			'ƃ' => 'b',
			'Ƈ' => 'C',
			'ƈ' => 'c',
			'Ɖ' => 'D',
			'Ɗ' => 'D',
			'Ƌ' => 'D',
			'ƌ' => 'd',
			'Ɛ' => 'E',
			'Ƒ' => 'F',
			'ƒ' => 'f',
			'Ɠ' => 'G',
			'ƕ' => 'hv',
			'Ɩ' => 'I',
			'Ɨ' => 'I',
			'Ƙ' => 'K',
			'ƙ' => 'k',
			'ƚ' => 'l',
			'Ɲ' => 'N',
			'ƞ' => 'n',
			'Ƣ' => 'OI',
			'ƣ' => 'oi',
			'Ƥ' => 'P',
			'ƥ' => 'p',
			'ƫ' => 't',
			'Ƭ' => 'T',
			'ƭ' => 't',
			'Ʈ' => 'T',
			'Ʋ' => 'V',
			'Ƴ' => 'Y',
			'ƴ' => 'y',
			'Ƶ' => 'Z',
			'ƶ' => 'z',
			'Ǆ' => 'DZ',
			'ǅ' => 'Dz',
			'ǆ' => 'dz',
			'Ǥ' => 'G',
			'ǥ' => 'g',
			'ȡ' => 'd',
			'Ȥ' => 'Z',
			'ȥ' => 'z',
			'ȴ' => 'l',
			'ȵ' => 'n',
			'ȶ' => 't',
			'ȷ' => 'j',
			'ȸ' => 'db',
			'ȹ' => 'qp',
			'Ⱥ' => 'A',
			'Ȼ' => 'C',
			'ȼ' => 'c',
			'Ƚ' => 'L',
			'Ⱦ' => 'T',
			'ȿ' => 's',
			'ɀ' => 'z',
			'Ƀ' => 'B',
			'Ʉ' => 'U',
			'Ɇ' => 'E',
			'ɇ' => 'e',
			'Ɉ' => 'J',
			'ɉ' => 'j',
			'Ɍ' => 'R',
			'ɍ' => 'r',
			'Ɏ' => 'Y',
			'ɏ' => 'y',
			'ɓ' => 'b',
			'ɕ' => 'c',
			'ɖ' => 'd',
			'ɗ' => 'd',
			'ɛ' => 'e',
			'ɟ' => 'j',
			'ɠ' => 'g',
			'ɡ' => 'g',
			'ɢ' => 'G',
			'ɦ' => 'h',
			'ɧ' => 'h',
			'ɨ' => 'i',
			'ɪ' => 'I',
			'ɫ' => 'l',
			'ɬ' => 'l',
			'ɭ' => 'l',
			'ɱ' => 'm',
			'ɲ' => 'n',
			'ɳ' => 'n',
			'ɴ' => 'N',
			'ɶ' => 'OE',
			'ɼ' => 'r',
			'ɽ' => 'r',
			'ɾ' => 'r',
			'ʀ' => 'R',
			'ʂ' => 's',
			'ʈ' => 't',
			'ʉ' => 'u',
			'ʋ' => 'v',
			'ʏ' => 'Y',
			'ʐ' => 'z',
			'ʑ' => 'z',
			'ʙ' => 'B',
			'ʛ' => 'G',
			'ʜ' => 'H',
			'ʝ' => 'j',
			'ʟ' => 'L',
			'ʠ' => 'q',
			'ʣ' => 'dz',
			'ʥ' => 'dz',
			'ʦ' => 'ts',
			'ʪ' => 'ls',
			'ʫ' => 'lz',
			'ᴀ' => 'A',
			'ᴁ' => 'AE',
			'ᴃ' => 'B',
			'ᴄ' => 'C',
			'ᴅ' => 'D',
			'ᴆ' => 'D',
			'ᴇ' => 'E',
			'ᴊ' => 'J',
			'ᴋ' => 'K',
			'ᴌ' => 'L',
			'ᴍ' => 'M',
			'ᴏ' => 'O',
			'ᴘ' => 'P',
			'ᴛ' => 'T',
			'ᴜ' => 'U',
			'ᴠ' => 'V',
			'ᴡ' => 'W',
			'ᴢ' => 'Z',
			'ᵫ' => 'ue',
			'ᵬ' => 'b',
			'ᵭ' => 'd',
			'ᵮ' => 'f',
			'ᵯ' => 'm',
			'ᵰ' => 'n',
			'ᵱ' => 'p',
			'ᵲ' => 'r',
			'ᵳ' => 'r',
			'ᵴ' => 's',
			'ᵵ' => 't',
			'ᵶ' => 'z',
			'ᵺ' => 'th',
			'ᵻ' => 'I',
			'ᵽ' => 'p',
			'ᵾ' => 'U',
			'ᶀ' => 'b',
			'ᶁ' => 'd',
			'ᶂ' => 'f',
			'ᶃ' => 'g',
			'ᶄ' => 'k',
			'ᶅ' => 'l',
			'ᶆ' => 'm',
			'ᶇ' => 'n',
			'ᶈ' => 'p',
			'ᶉ' => 'r',
			'ᶊ' => 's',
			'ᶌ' => 'v',
			'ᶍ' => 'x',
			'ᶎ' => 'z',
			'ᶏ' => 'a',
			'ᶑ' => 'd',
			'ᶒ' => 'e',
			'ᶓ' => 'e',
			'ᶖ' => 'i',
			'ᶙ' => 'u',
			'ẚ' => 'a',
			'ẜ' => 's',
			'ẝ' => 's',
			'ẞ' => 'SS',
			'Ỻ' => 'LL',
			'ỻ' => 'll',
			'Ỽ' => 'V',
			'ỽ' => 'v',
			'Ỿ' => 'Y',
			'ỿ' => 'y',
			'©' => '(C)',
			'®' => '(R)',
			'₠' => 'CE',
			'₢' => 'Cr',
			'₣' => 'Fr.',
			'₤' => 'L.',
			'₧' => 'Pts',
			'₺' => 'TL',
			'₹' => 'Rs',
			'ℌ' => 'x',
			'℞' => 'Rx',
			'㎧' => 'm/s',
			'㎮' => 'rad/s',
			'㏆' => 'C/kg',
			'㏗' => 'pH',
			'㏞' => 'V/m',
			'㏟' => 'A/m',
			'¼' => ' 1/4',
			'½' => ' 1/2',
			'¾' => ' 3/4',
			'⅓' => ' 1/3',
			'⅔' => ' 2/3',
			'⅕' => ' 1/5',
			'⅖' => ' 2/5',
			'⅗' => ' 3/5',
			'⅘' => ' 4/5',
			'⅙' => ' 1/6',
			'⅚' => ' 5/6',
			'⅛' => ' 1/8',
			'⅜' => ' 3/8',
			'⅝' => ' 5/8',
			'⅞' => ' 7/8',
			'⅟' => ' 1/',
			'〇' => '0',
			"‘" => "'",
			"’" => "'",
			'‚' => ',',
			"‛" => "'",
			'“' => '"',
			'”' => '"',
			'„' => ',,',
			'‟' => '"',
			"′" => "'",
			'″' => '"',
			'〝' => '"',
			'〞' => '"',
			'«' => '<<',
			'»' => '>>',
			'‹' => '<',
			'›' => '>',
			'‐' => '-',
			'‑' => '-',
			'‒' => '-',
			'–' => '-',
			'—' => '-',
			'―' => '-',
			'︱' => '-',
			'︲' => '-',
			'﹘' => '-',
			'‖' => '||',
			'⁄' => '/',
			'⁅' => '[',
			'⁆' => ']',
			'⁎' => '*',
			'、' => ',',
			'。' => '.',
			'〈' => '<',
			'〉' => '>',
			'《' => '<<',
			'》' => '>>',
			'〔' => '[',
			'〕' => ']',
			'〘' => '[',
			'〙' => ']',
			'〚' => '[',
			'〛' => ']',
			'︑' => ',',
			'︒' => '.',
			'︹' => '[',
			'︺' => ']',
			'︽' => '<<',
			'︾' => '>>',
			'︿' => '<',
			'﹀' => '>',
			'﹑' => ',',
			'﹝' => '[',
			'﹞' => ']',
			'｟' => '((',
			'｠' => '))',
			'｡' => '.',
			'､' => ',',
			'×' => '*',
			'÷' => '/',
			'−' => '-',
			'∕' => '/',
			'∖' => '\\',
			'∣' => '|',
			'∥' => '||',
			'≪' => '<<',
			'≫' => '>>',
			'⦅' => '((',
			'⦆' => '))',
		];
	}

	public function badUtf8(): string
	{
		return
			'([\x00-\x7F]' .                          # ASCII (including control chars)
			'|[\xC2-\xDF][\x80-\xBF]' .               # non-overlong 2-byte
			'|\xE0[\xA0-\xBF][\x80-\xBF]' .           # excluding overlongs
			'|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}' .    # straight 3-byte
			'|\xED[\x80-\x9F][\x80-\xBF]' .           # excluding surrogates
			'|\xF0[\x90-\xBF][\x80-\xBF]{2}' .        # planes 1-3
			'|[\xF1-\xF3][\x80-\xBF]{3}' .            # planes 4-15
			'|\xF4[\x80-\x8F][\x80-\xBF]{2}' .        # plane 16
			'|(.{1}))';                               # invalid byte
	}

	/**
	 * @return array<string, string>
	 */
	public function lowerAccents(): array
	{
		$lowerAccents = [
			'á' => 'a',
			'à' => 'a',
			'ă' => 'a',
			'â' => 'a',
			'å' => 'a',
			'ä' => 'ae',
			'ã' => 'a',
			'ą' => 'a',
			'ā' => 'a',
			'æ' => 'ae',
			'ḃ' => 'b',
			'ć' => 'c',
			'ĉ' => 'c',
			'č' => 'c',
			'ċ' => 'c',
			'ç' => 'c',
			'ď' => 'd',
			'ḋ' => 'd',
			'đ' => 'd',
			'ð' => 'dh',
			'é' => 'e',
			'è' => 'e',
			'ĕ' => 'e',
			'ê' => 'e',
			'ě' => 'e',
			'ë' => 'e',
			'ė' => 'e',
			'ę' => 'e',
			'ē' => 'e',
			'ḟ' => 'f',
			'ƒ' => 'f',
			'ğ' => 'g',
			'ĝ' => 'g',
			'ġ' => 'g',
			'ģ' => 'g',
			'ĥ' => 'h',
			'ħ' => 'h',
			'í' => 'i',
			'ì' => 'i',
			'î' => 'i',
			'ï' => 'i',
			'ĩ' => 'i',
			'į' => 'i',
			'ī' => 'i',
			'ı' => 'i',
			'ĵ' => 'j',
			'ķ' => 'k',
			'ĺ' => 'l',
			'ľ' => 'l',
			'ļ' => 'l',
			'ł' => 'l',
			'ṁ' => 'm',
			'ń' => 'n',
			'ň' => 'n',
			'ñ' => 'n',
			'ņ' => 'n',
			'ó' => 'o',
			'ò' => 'o',
			'ô' => 'o',
			'ö' => 'oe',
			'ő' => 'o',
			'õ' => 'o',
			'ø' => 'o',
			'ō' => 'o',
			'ơ' => 'o',
			'ṗ' => 'p',
			'ŕ' => 'r',
			'ř' => 'r',
			'ŗ' => 'r',
			'ś' => 's',
			'ŝ' => 's',
			'š' => 's',
			'ṡ' => 's',
			'ş' => 's',
			'ș' => 's',
			'ß' => 'ss',
			'ť' => 't',
			'ṫ' => 't',
			'ţ' => 't',
			'ț' => 't',
			'ŧ' => 't',
			'ú' => 'u',
			'ù' => 'u',
			'ŭ' => 'u',
			'û' => 'u',
			'ů' => 'u',
			'ü' => 'ue',
			'ű' => 'u',
			'ũ' => 'u',
			'ų' => 'u',
			'ū' => 'u',
			'ư' => 'u',
			'ẃ' => 'w',
			'ẁ' => 'w',
			'ŵ' => 'w',
			'ẅ' => 'w',
			'ý' => 'y',
			'ỳ' => 'y',
			'ŷ' => 'y',
			'ÿ' => 'y',
			'ź' => 'z',
			'ž' => 'z',
			'ż' => 'z',
			'þ' => 'th',
			'µ' => 'u',
		];

		\XF::app()->fire('string_data_lower_accents', [&$lowerAccents]);

		return $lowerAccents;
	}

	/**
	 * @return array<string, string>
	 */
	public function upperAccents(): array
	{
		$upperAccents = [
			'Á' => 'A',
			'À' => 'A',
			'Ă' => 'A',
			'Â' => 'A',
			'Å' => 'A',
			'Ä' => 'Ae',
			'Ã' => 'A',
			'Ą' => 'A',
			'Ā' => 'A',
			'Æ' => 'Ae',
			'Ḃ' => 'B',
			'Ć' => 'C',
			'Ĉ' => 'C',
			'Č' => 'C',
			'Ċ' => 'C',
			'Ç' => 'C',
			'Ď' => 'D',
			'Ḋ' => 'D',
			'Đ' => 'D',
			'Ð' => 'Dh',
			'É' => 'E',
			'È' => 'E',
			'Ĕ' => 'E',
			'Ê' => 'E',
			'Ě' => 'E',
			'Ë' => 'E',
			'Ė' => 'E',
			'Ę' => 'E',
			'Ē' => 'E',
			'Ḟ' => 'F',
			'Ƒ' => 'F',
			'Ğ' => 'G',
			'Ĝ' => 'G',
			'Ġ' => 'G',
			'Ģ' => 'G',
			'Ĥ' => 'H',
			'Ħ' => 'H',
			'Í' => 'I',
			'Ì' => 'I',
			'Î' => 'I',
			'Ï' => 'I',
			'Ĩ' => 'I',
			'Į' => 'I',
			'Ī' => 'I',
			'Ĵ' => 'J',
			'Ķ' => 'K',
			'Ĺ' => 'L',
			'Ľ' => 'L',
			'Ļ' => 'L',
			'Ł' => 'L',
			'Ṁ' => 'M',
			'Ń' => 'N',
			'Ň' => 'N',
			'Ñ' => 'N',
			'Ņ' => 'N',
			'Ó' => 'O',
			'Ò' => 'O',
			'Ô' => 'O',
			'Ö' => 'Oe',
			'Ő' => 'O',
			'Õ' => 'O',
			'Ø' => 'O',
			'Ō' => 'O',
			'Ơ' => 'O',
			'Ṗ' => 'P',
			'Ŕ' => 'R',
			'Ř' => 'R',
			'Ŗ' => 'R',
			'Ś' => 'S',
			'Ŝ' => 'S',
			'Š' => 'S',
			'Ṡ' => 'S',
			'Ş' => 'S',
			'Ș' => 'S',
			'Ť' => 'T',
			'Ṫ' => 'T',
			'Ţ' => 'T',
			'Ț' => 'T',
			'Ŧ' => 'T',
			'Ú' => 'U',
			'Ù' => 'U',
			'Ŭ' => 'U',
			'Û' => 'U',
			'Ů' => 'U',
			'Ü' => 'Ue',
			'Ű' => 'U',
			'Ũ' => 'U',
			'Ų' => 'U',
			'Ū' => 'U',
			'Ư' => 'U',
			'Ẃ' => 'W',
			'Ẁ' => 'W',
			'Ŵ' => 'W',
			'Ẅ' => 'W',
			'Ý' => 'Y',
			'Ỳ' => 'Y',
			'Ŷ' => 'Y',
			'Ÿ' => 'Y',
			'Ź' => 'Z',
			'Ž' => 'Z',
			'Ż' => 'Z',
			'Þ' => 'Th',
		];

		\XF::app()->fire('string_data_upper_accents', [&$upperAccents]);

		return $upperAccents;
	}

	/**
	 * @return array<string, string>
	 */
	public function romanization(): array
	{
		$romanization = [
			// scandinavian - differs from what we do in deaccent
			'å' => 'a',
			'Å' => 'A',
			'ä' => 'a',
			'Ä' => 'A',
			'ö' => 'o',
			'Ö' => 'O',

			//russian cyrillic
			'а' => 'a',
			'А' => 'A',
			'б' => 'b',
			'Б' => 'B',
			'в' => 'v',
			'В' => 'V',
			'г' => 'g',
			'Г' => 'G',
			'д' => 'd',
			'Д' => 'D',
			'е' => 'e',
			'Е' => 'E',
			'ё' => 'jo',
			'Ё' => 'Jo',
			'ж' => 'zh',
			'Ж' => 'Zh',
			'з' => 'z',
			'З' => 'Z',
			'и' => 'i',
			'И' => 'I',
			'й' => 'j',
			'Й' => 'J',
			'к' => 'k',
			'К' => 'K',
			'л' => 'l',
			'Л' => 'L',
			'м' => 'm',
			'М' => 'M',
			'н' => 'n',
			'Н' => 'N',
			'о' => 'o',
			'О' => 'O',
			'п' => 'p',
			'П' => 'P',
			'р' => 'r',
			'Р' => 'R',
			'с' => 's',
			'С' => 'S',
			'т' => 't',
			'Т' => 'T',
			'у' => 'u',
			'У' => 'U',
			'ф' => 'f',
			'Ф' => 'F',
			'х' => 'x',
			'Х' => 'X',
			'ц' => 'c',
			'Ц' => 'C',
			'ч' => 'ch',
			'Ч' => 'Ch',
			'ш' => 'sh',
			'Ш' => 'Sh',
			'щ' => 'sch',
			'Щ' => 'Sch',
			'ъ' => '',
			'Ъ' => '',
			'ы' => 'y',
			'Ы' => 'Y',
			'ь' => '',
			'Ь' => '',
			'э' => 'eh',
			'Э' => 'Eh',
			'ю' => 'ju',
			'Ю' => 'Ju',
			'я' => 'ja',
			'Я' => 'Ja',

			// Ukrainian cyrillic
			'Ґ' => 'Gh',
			'ґ' => 'gh',
			'Є' => 'Je',
			'є' => 'je',
			'І' => 'I',
			'і' => 'i',
			'Ї' => 'Ji',
			'ї' => 'ji',

			// Georgian
			'ა' => 'a',
			'ბ' => 'b',
			'გ' => 'g',
			'დ' => 'd',
			'ე' => 'e',
			'ვ' => 'v',
			'ზ' => 'z',
			'თ' => 'th',
			'ი' => 'i',
			'კ' => 'p',
			'ლ' => 'l',
			'მ' => 'm',
			'ნ' => 'n',
			'ო' => 'o',
			'პ' => 'p',
			'ჟ' => 'zh',
			'რ' => 'r',
			'ს' => 's',
			'ტ' => 't',
			'უ' => 'u',
			'ფ' => 'ph',
			'ქ' => 'kh',
			'ღ' => 'gh',
			'ყ' => 'q',
			'შ' => 'sh',
			'ჩ' => 'ch',
			'ც' => 'c',
			'ძ' => 'dh',
			'წ' => 'w',
			'ჭ' => 'j',
			'ხ' => 'x',
			'ჯ' => 'jh',
			'ჰ' => 'xh',

			//Sanskrit
			'अ' => 'a',
			'आ' => 'ah',
			'इ' => 'i',
			'ई' => 'ih',
			'उ' => 'u',
			'ऊ' => 'uh',
			'ऋ' => 'ry',
			'ॠ' => 'ryh',
			'ऌ' => 'ly',
			'ॡ' => 'lyh',
			'ए' => 'e',
			'ऐ' => 'ay',
			'ओ' => 'o',
			'औ' => 'aw',
			'अं' => 'amh',
			'अः' => 'aq',
			'क' => 'k',
			'ख' => 'kh',
			'ग' => 'g',
			'घ' => 'gh',
			'ङ' => 'nh',
			'च' => 'c',
			'छ' => 'ch',
			'ज' => 'j',
			'झ' => 'jh',
			'ञ' => 'ny',
			'ट' => 'tq',
			'ठ' => 'tqh',
			'ड' => 'dq',
			'ढ' => 'dqh',
			'ण' => 'nq',
			'त' => 't',
			'थ' => 'th',
			'द' => 'd',
			'ध' => 'dh',
			'न' => 'n',
			'प' => 'p',
			'फ' => 'ph',
			'ब' => 'b',
			'भ' => 'bh',
			'म' => 'm',
			'य' => 'z',
			'र' => 'r',
			'ल' => 'l',
			'व' => 'v',
			'श' => 'sh',
			'ष' => 'sqh',
			'स' => 's',
			'ह' => 'x',

			//Sanskrit diacritics
			'Ā' => 'A',
			'Ī' => 'I',
			'Ū' => 'U',
			'Ṛ' => 'R',
			'Ṝ' => 'R',
			'Ṅ' => 'N',
			'Ñ' => 'N',
			'Ṭ' => 'T',
			'Ḍ' => 'D',
			'Ṇ' => 'N',
			'Ś' => 'S',
			'Ṣ' => 'S',
			'Ṁ' => 'M',
			'Ṃ' => 'M',
			'Ḥ' => 'H',
			'Ḷ' => 'L',
			'Ḹ' => 'L',
			'ā' => 'a',
			'ī' => 'i',
			'ū' => 'u',
			'ṛ' => 'r',
			'ṝ' => 'r',
			'ṅ' => 'n',
			'ñ' => 'n',
			'ṭ' => 't',
			'ḍ' => 'd',
			'ṇ' => 'n',
			'ś' => 's',
			'ṣ' => 's',
			'ṁ' => 'm',
			'ṃ' => 'm',
			'ḥ' => 'h',
			'ḷ' => 'l',
			'ḹ' => 'l',

			//Hebrew
			'א' => 'a',
			'ב' => 'b',
			'ג' => 'g',
			'ד' => 'd',
			'ה' => 'h',
			'ו' => 'v',
			'ז' => 'z',
			'ח' => 'kh',
			'ט' => 'th',
			'י' => 'y',
			'ך' => 'h',
			'כ' => 'k',
			'ל' => 'l',
			'ם' => 'm',
			'מ' => 'm',
			'ן' => 'n',
			'נ' => 'n',
			'ס' => 's',
			'ע' => 'ah',
			'ף' => 'f',
			'פ' => 'p',
			'ץ' => 'c',
			'צ' => 'c',
			'ק' => 'q',
			'ר' => 'r',
			'ש' => 'sh',
			'ת' => 't',

			//Arabic
			'ا' => 'a',
			'ب' => 'b',
			'ت' => 't',
			'ث' => 'th',
			'ج' => 'g',
			'ح' => 'xh',
			'خ' => 'x',
			'د' => 'd',
			'ذ' => 'dh',
			'ر' => 'r',
			'ز' => 'z',
			'س' => 's',
			'ش' => 'sh',
			'ص' => 's\'',
			'ض' => 'd\'',
			'ط' => 't\'',
			'ظ' => 'z\'',
			'ع' => 'y',
			'غ' => 'gh',
			'ف' => 'f',
			'ق' => 'q',
			'ك' => 'k',
			'ل' => 'l',
			'م' => 'm',
			'ن' => 'n',
			'ه' => 'x\'',
			'و' => 'u',
			'ي' => 'i',

			// Japanese characters  (last update: 2008-05-09)

			// Japanese hiragana

			// 3 character syllables, っ doubles the consonant after
			'っびゃ' => 'bbya',
			'っびぇ' => 'bbye',
			'っびぃ' => 'bbyi',
			'っびょ' => 'bbyo',
			'っびゅ' => 'bbyu',
			'っぴゃ' => 'ppya',
			'っぴぇ' => 'ppye',
			'っぴぃ' => 'ppyi',
			'っぴょ' => 'ppyo',
			'っぴゅ' => 'ppyu',
			'っちゃ' => 'ccha',
			'っちぇ' => 'cche',
			'っちょ' => 'ccho',
			'っちゅ' => 'cchu',
			// 'っひゃ'=>'hya',
			// 'っひぇ'=>'hye',
			// 'っひぃ'=>'hyi',
			// 'っひょ'=>'hyo',
			// 'っひゅ'=>'hyu',
			'っきゃ' => 'kkya',
			'っきぇ' => 'kkye',
			'っきぃ' => 'kkyi',
			'っきょ' => 'kkyo',
			'っきゅ' => 'kkyu',
			'っぎゃ' => 'ggya',
			'っぎぇ' => 'ggye',
			'っぎぃ' => 'ggyi',
			'っぎょ' => 'ggyo',
			'っぎゅ' => 'ggyu',
			'っみゃ' => 'mmya',
			'っみぇ' => 'mmye',
			'っみぃ' => 'mmyi',
			'っみょ' => 'mmyo',
			'っみゅ' => 'mmyu',
			'っにゃ' => 'nnya',
			'っにぇ' => 'nnye',
			'っにぃ' => 'nnyi',
			'っにょ' => 'nnyo',
			'っにゅ' => 'nnyu',
			'っりゃ' => 'rrya',
			'っりぇ' => 'rrye',
			'っりぃ' => 'rryi',
			'っりょ' => 'rryo',
			'っりゅ' => 'rryu',
			'っしゃ' => 'ssha',
			'っしぇ' => 'sshe',
			'っしょ' => 'ssho',
			'っしゅ' => 'sshu',

			// seperate hiragana 'n' ('n' + 'i' != 'ni', normally we would write "kon'nichi wa" but the
			// apostrophe would be converted to _ anyway)
			'んあ' => 'n_a',
			'んえ' => 'n_e',
			'んい' => 'n_i',
			'んお' => 'n_o',
			'んう' => 'n_u',
			'んや' => 'n_ya',
			'んよ' => 'n_yo',
			'んゆ' => 'n_yu',

			// 2 character syllables - normal
			'ふぁ' => 'fa',
			'ふぇ' => 'fe',
			'ふぃ' => 'fi',
			'ふぉ' => 'fo',
			'ちゃ' => 'cha',
			'ちぇ' => 'che',
			'ちょ' => 'cho',
			'ちゅ' => 'chu',
			'ひゃ' => 'hya',
			'ひぇ' => 'hye',
			'ひぃ' => 'hyi',
			'ひょ' => 'hyo',
			'ひゅ' => 'hyu',
			'びゃ' => 'bya',
			'びぇ' => 'bye',
			'びぃ' => 'byi',
			'びょ' => 'byo',
			'びゅ' => 'byu',
			'ぴゃ' => 'pya',
			'ぴぇ' => 'pye',
			'ぴぃ' => 'pyi',
			'ぴょ' => 'pyo',
			'ぴゅ' => 'pyu',
			'きゃ' => 'kya',
			'きぇ' => 'kye',
			'きぃ' => 'kyi',
			'きょ' => 'kyo',
			'きゅ' => 'kyu',
			'ぎゃ' => 'gya',
			'ぎぇ' => 'gye',
			'ぎぃ' => 'gyi',
			'ぎょ' => 'gyo',
			'ぎゅ' => 'gyu',
			'みゃ' => 'mya',
			'みぇ' => 'mye',
			'みぃ' => 'myi',
			'みょ' => 'myo',
			'みゅ' => 'myu',
			'にゃ' => 'nya',
			'にぇ' => 'nye',
			'にぃ' => 'nyi',
			'にょ' => 'nyo',
			'にゅ' => 'nyu',
			'りゃ' => 'rya',
			'りぇ' => 'rye',
			'りぃ' => 'ryi',
			'りょ' => 'ryo',
			'りゅ' => 'ryu',
			'しゃ' => 'sha',
			'しぇ' => 'she',
			'しょ' => 'sho',
			'しゅ' => 'shu',
			'じゃ' => 'ja',
			'じぇ' => 'je',
			'じょ' => 'jo',
			'じゅ' => 'ju',
			'うぇ' => 'we',
			'うぃ' => 'wi',
			'いぇ' => 'ye',

			// 2 character syllables, っ doubles the consonant after
			'っば' => 'bba',
			'っべ' => 'bbe',
			'っび' => 'bbi',
			'っぼ' => 'bbo',
			'っぶ' => 'bbu',
			'っぱ' => 'ppa',
			'っぺ' => 'ppe',
			'っぴ' => 'ppi',
			'っぽ' => 'ppo',
			'っぷ' => 'ppu',
			'った' => 'tta',
			'って' => 'tte',
			'っち' => 'cchi',
			'っと' => 'tto',
			'っつ' => 'ttsu',
			'っだ' => 'dda',
			'っで' => 'dde',
			'っぢ' => 'ddi',
			'っど' => 'ddo',
			'っづ' => 'ddu',
			'っが' => 'gga',
			'っげ' => 'gge',
			'っぎ' => 'ggi',
			'っご' => 'ggo',
			'っぐ' => 'ggu',
			'っか' => 'kka',
			'っけ' => 'kke',
			'っき' => 'kki',
			'っこ' => 'kko',
			'っく' => 'kku',
			'っま' => 'mma',
			'っめ' => 'mme',
			'っみ' => 'mmi',
			'っも' => 'mmo',
			'っむ' => 'mmu',
			'っな' => 'nna',
			'っね' => 'nne',
			'っに' => 'nni',
			'っの' => 'nno',
			'っぬ' => 'nnu',
			'っら' => 'rra',
			'っれ' => 'rre',
			'っり' => 'rri',
			'っろ' => 'rro',
			'っる' => 'rru',
			'っさ' => 'ssa',
			'っせ' => 'sse',
			'っし' => 'sshi',
			'っそ' => 'sso',
			'っす' => 'ssu',
			'っざ' => 'zza',
			'っぜ' => 'zze',
			'っじ' => 'jji',
			'っぞ' => 'zzo',
			'っず' => 'zzu',

			// 1 character syllabels
			'あ' => 'a',
			'え' => 'e',
			'い' => 'i',
			'お' => 'o',
			'う' => 'u',
			'ん' => 'n',
			'は' => 'ha',
			'へ' => 'he',
			'ひ' => 'hi',
			'ほ' => 'ho',
			'ふ' => 'fu',
			'ば' => 'ba',
			'べ' => 'be',
			'び' => 'bi',
			'ぼ' => 'bo',
			'ぶ' => 'bu',
			'ぱ' => 'pa',
			'ぺ' => 'pe',
			'ぴ' => 'pi',
			'ぽ' => 'po',
			'ぷ' => 'pu',
			'た' => 'ta',
			'て' => 'te',
			'ち' => 'chi',
			'と' => 'to',
			'つ' => 'tsu',
			'だ' => 'da',
			'で' => 'de',
			'ぢ' => 'di',
			'ど' => 'do',
			'づ' => 'du',
			'が' => 'ga',
			'げ' => 'ge',
			'ぎ' => 'gi',
			'ご' => 'go',
			'ぐ' => 'gu',
			'か' => 'ka',
			'け' => 'ke',
			'き' => 'ki',
			'こ' => 'ko',
			'く' => 'ku',
			'ま' => 'ma',
			'め' => 'me',
			'み' => 'mi',
			'も' => 'mo',
			'む' => 'mu',
			'な' => 'na',
			'ね' => 'ne',
			'に' => 'ni',
			'の' => 'no',
			'ぬ' => 'nu',
			'ら' => 'ra',
			'れ' => 're',
			'り' => 'ri',
			'ろ' => 'ro',
			'る' => 'ru',
			'さ' => 'sa',
			'せ' => 'se',
			'し' => 'shi',
			'そ' => 'so',
			'す' => 'su',
			'わ' => 'wa',
			'を' => 'wo',
			'ざ' => 'za',
			'ぜ' => 'ze',
			'じ' => 'ji',
			'ぞ' => 'zo',
			'ず' => 'zu',
			'や' => 'ya',
			'よ' => 'yo',
			'ゆ' => 'yu',
			// old characters
			'ゑ' => 'we',
			'ゐ' => 'wi',

			//  convert what's left (probably only kicks in when something's missing above)
			// 'ぁ'=>'a','ぇ'=>'e','ぃ'=>'i','ぉ'=>'o','ぅ'=>'u',
			// 'ゃ'=>'ya','ょ'=>'yo','ゅ'=>'yu',

			// never seen one of those (disabled for the moment)
			// 'ヴぁ'=>'va','ヴぇ'=>'ve','ヴぃ'=>'vi','ヴぉ'=>'vo','ヴ'=>'vu',
			// 'でゃ'=>'dha','でぇ'=>'dhe','でぃ'=>'dhi','でょ'=>'dho','でゅ'=>'dhu',
			// 'どぁ'=>'dwa','どぇ'=>'dwe','どぃ'=>'dwi','どぉ'=>'dwo','どぅ'=>'dwu',
			// 'ぢゃ'=>'dya','ぢぇ'=>'dye','ぢぃ'=>'dyi','ぢょ'=>'dyo','ぢゅ'=>'dyu',
			// 'ふぁ'=>'fwa','ふぇ'=>'fwe','ふぃ'=>'fwi','ふぉ'=>'fwo','ふぅ'=>'fwu',
			// 'ふゃ'=>'fya','ふぇ'=>'fye','ふぃ'=>'fyi','ふょ'=>'fyo','ふゅ'=>'fyu',
			// 'すぁ'=>'swa','すぇ'=>'swe','すぃ'=>'swi','すぉ'=>'swo','すぅ'=>'swu',
			// 'てゃ'=>'tha','てぇ'=>'the','てぃ'=>'thi','てょ'=>'tho','てゅ'=>'thu',
			// 'つゃ'=>'tsa','つぇ'=>'tse','つぃ'=>'tsi','つょ'=>'tso','つ'=>'tsu',
			// 'とぁ'=>'twa','とぇ'=>'twe','とぃ'=>'twi','とぉ'=>'two','とぅ'=>'twu',
			// 'ヴゃ'=>'vya','ヴぇ'=>'vye','ヴぃ'=>'vyi','ヴょ'=>'vyo','ヴゅ'=>'vyu',
			// 'うぁ'=>'wha','うぇ'=>'whe','うぃ'=>'whi','うぉ'=>'who','うぅ'=>'whu',
			// 'じゃ'=>'zha','じぇ'=>'zhe','じぃ'=>'zhi','じょ'=>'zho','じゅ'=>'zhu',
			// 'じゃ'=>'zya','じぇ'=>'zye','じぃ'=>'zyi','じょ'=>'zyo','じゅ'=>'zyu',

			// 'spare' characters from other romanization systems
			// 'だ'=>'da','で'=>'de','ぢ'=>'di','ど'=>'do','づ'=>'du',
			// 'ら'=>'la','れ'=>'le','り'=>'li','ろ'=>'lo','る'=>'lu',
			// 'さ'=>'sa','せ'=>'se','し'=>'si','そ'=>'so','す'=>'su',
			// 'ちゃ'=>'cya','ちぇ'=>'cye','ちぃ'=>'cyi','ちょ'=>'cyo','ちゅ'=>'cyu',
			//'じゃ'=>'jya','じぇ'=>'jye','じぃ'=>'jyi','じょ'=>'jyo','じゅ'=>'jyu',
			//'りゃ'=>'lya','りぇ'=>'lye','りぃ'=>'lyi','りょ'=>'lyo','りゅ'=>'lyu',
			//'しゃ'=>'sya','しぇ'=>'sye','しぃ'=>'syi','しょ'=>'syo','しゅ'=>'syu',
			//'ちゃ'=>'tya','ちぇ'=>'tye','ちぃ'=>'tyi','ちょ'=>'tyo','ちゅ'=>'tyu',
			//'し'=>'ci',,い'=>'yi','ぢ'=>'dzi',
			//'っじゃ'=>'jja','っじぇ'=>'jje','っじ'=>'jji','っじょ'=>'jjo','っじゅ'=>'jju',


			// Japanese katakana

			// 4 character syllables: ッ doubles the consonant after, ー doubles the vowel before
			// (usualy written with macron, but we don't want that in our URLs)
			'ッビャー' => 'bbyaa',
			'ッビェー' => 'bbyee',
			'ッビィー' => 'bbyii',
			'ッビョー' => 'bbyoo',
			'ッビュー' => 'bbyuu',
			'ッピャー' => 'ppyaa',
			'ッピェー' => 'ppyee',
			'ッピィー' => 'ppyii',
			'ッピョー' => 'ppyoo',
			'ッピュー' => 'ppyuu',
			'ッキャー' => 'kkyaa',
			'ッキェー' => 'kkyee',
			'ッキィー' => 'kkyii',
			'ッキョー' => 'kkyoo',
			'ッキュー' => 'kkyuu',
			'ッギャー' => 'ggyaa',
			'ッギェー' => 'ggyee',
			'ッギィー' => 'ggyii',
			'ッギョー' => 'ggyoo',
			'ッギュー' => 'ggyuu',
			'ッミャー' => 'mmyaa',
			'ッミェー' => 'mmyee',
			'ッミィー' => 'mmyii',
			'ッミョー' => 'mmyoo',
			'ッミュー' => 'mmyuu',
			'ッニャー' => 'nnyaa',
			'ッニェー' => 'nnyee',
			'ッニィー' => 'nnyii',
			'ッニョー' => 'nnyoo',
			'ッニュー' => 'nnyuu',
			'ッリャー' => 'rryaa',
			'ッリェー' => 'rryee',
			'ッリィー' => 'rryii',
			'ッリョー' => 'rryoo',
			'ッリュー' => 'rryuu',
			'ッシャー' => 'sshaa',
			'ッシェー' => 'sshee',
			'ッショー' => 'sshoo',
			'ッシュー' => 'sshuu',
			'ッチャー' => 'cchaa',
			'ッチェー' => 'cchee',
			'ッチョー' => 'cchoo',
			'ッチュー' => 'cchuu',
			'ッティー' => 'ttii',
			'ッヂィー' => 'ddii',

			// 3 character syllables - doubled vowels
			'ファー' => 'faa',
			'フォー' => 'foo',
			'フャー' => 'fyaa',
			'フェー' => 'fee',
			'フィー' => 'fyii',
			'フョー' => 'fyoo',
			'フュー' => 'fyuu',
			'ヒャー' => 'hyaa',
			'ヒェー' => 'hyee',
			'ヒィー' => 'hyii',
			'ヒョー' => 'hyoo',
			'ヒュー' => 'hyuu',
			'ビャー' => 'byaa',
			'ビェー' => 'byee',
			'ビィー' => 'byii',
			'ビョー' => 'byoo',
			'ビュー' => 'byuu',
			'ピャー' => 'pyaa',
			'ピェー' => 'pyee',
			'ピィー' => 'pyii',
			'ピョー' => 'pyoo',
			'ピュー' => 'pyuu',
			'キャー' => 'kyaa',
			'キェー' => 'kyee',
			'キィー' => 'kyii',
			'キョー' => 'kyoo',
			'キュー' => 'kyuu',
			'ギャー' => 'gyaa',
			'ギェー' => 'gyee',
			'ギィー' => 'gyii',
			'ギョー' => 'gyoo',
			'ギュー' => 'gyuu',
			'ミャー' => 'myaa',
			'ミェー' => 'myee',
			'ミィー' => 'myii',
			'ミョー' => 'myoo',
			'ミュー' => 'myuu',
			'ニャー' => 'nyaa',
			'ニェー' => 'nyee',
			'ニィー' => 'nyii',
			'ニョー' => 'nyoo',
			'ニュー' => 'nyuu',
			'リャー' => 'ryaa',
			'リェー' => 'ryee',
			'リィー' => 'ryii',
			'リョー' => 'ryoo',
			'リュー' => 'ryuu',
			'シャー' => 'shaa',
			'シェー' => 'shee',
			'ショー' => 'shoo',
			'シュー' => 'shuu',
			'ジャー' => 'jaa',
			'ジェー' => 'jee',
			'ジョー' => 'joo',
			'ジュー' => 'juu',
			'スァー' => 'swaa',
			'スェー' => 'swee',
			'スィー' => 'swii',
			'スォー' => 'swoo',
			'スゥー' => 'swuu',
			'デァー' => 'daa',
			'デェー' => 'dee',
			'ディー' => 'dii',
			'デォー' => 'doo',
			'デゥー' => 'duu',
			'チャー' => 'chaa',
			'チェー' => 'chee',
			'チョー' => 'choo',
			'チュー' => 'chuu',
			'ヂャー' => 'dyaa',
			'ヂェー' => 'dyee',
			'ヂョー' => 'dyoo',
			'ヂュー' => 'dyuu',
			'ツャー' => 'tsaa',
			'ツェー' => 'tsee',
			'ツィー' => 'tsii',
			'ツョー' => 'tsoo',
			'トァー' => 'twaa',
			'トェー' => 'twee',
			'トィー' => 'twii',
			'トォー' => 'twoo',
			'トゥー' => 'twuu',
			'ドァー' => 'dwaa',
			'ドェー' => 'dwee',
			'ドィー' => 'dwii',
			'ドォー' => 'dwoo',
			'ドゥー' => 'dwuu',
			'ウァー' => 'whaa',
			'ウォー' => 'whoo',
			'ウゥー' => 'whuu',
			'ヴャー' => 'vyaa',
			'ヴョー' => 'vyoo',
			'ヴュー' => 'vyuu',
			'ヴァー' => 'vaa',
			'ヴェー' => 'vee',
			'ヴィー' => 'vii',
			'ヴォー' => 'voo',
			'ヴー' => 'vuu',
			'ウェー' => 'wee',
			'ウィー' => 'wii',
			'イェー' => 'yee',
			'ティー' => 'tii',
			'ヂィー' => 'dii',

			// 3 character syllables - doubled consonants
			'ッビャ' => 'bbya',
			'ッビェ' => 'bbye',
			'ッビィ' => 'bbyi',
			'ッビョ' => 'bbyo',
			'ッビュ' => 'bbyu',
			'ッピャ' => 'ppya',
			'ッピェ' => 'ppye',
			'ッピィ' => 'ppyi',
			'ッピョ' => 'ppyo',
			'ッピュ' => 'ppyu',
			'ッキャ' => 'kkya',
			'ッキェ' => 'kkye',
			'ッキィ' => 'kkyi',
			'ッキョ' => 'kkyo',
			'ッキュ' => 'kkyu',
			'ッギャ' => 'ggya',
			'ッギェ' => 'ggye',
			'ッギィ' => 'ggyi',
			'ッギョ' => 'ggyo',
			'ッギュ' => 'ggyu',
			'ッミャ' => 'mmya',
			'ッミェ' => 'mmye',
			'ッミィ' => 'mmyi',
			'ッミョ' => 'mmyo',
			'ッミュ' => 'mmyu',
			'ッニャ' => 'nnya',
			'ッニェ' => 'nnye',
			'ッニィ' => 'nnyi',
			'ッニョ' => 'nnyo',
			'ッニュ' => 'nnyu',
			'ッリャ' => 'rrya',
			'ッリェ' => 'rrye',
			'ッリィ' => 'rryi',
			'ッリョ' => 'rryo',
			'ッリュ' => 'rryu',
			'ッシャ' => 'ssha',
			'ッシェ' => 'sshe',
			'ッショ' => 'ssho',
			'ッシュ' => 'sshu',
			'ッチャ' => 'ccha',
			'ッチェ' => 'cche',
			'ッチョ' => 'ccho',
			'ッチュ' => 'cchu',
			'ッティ' => 'tti',
			'ッヂィ' => 'ddi',

			// 3 character syllables - doubled vowel and consonants
			'ッバー' => 'bbaa',
			'ッベー' => 'bbee',
			'ッビー' => 'bbii',
			'ッボー' => 'bboo',
			'ッブー' => 'bbuu',
			'ッパー' => 'ppaa',
			'ッペー' => 'ppee',
			'ッピー' => 'ppii',
			'ッポー' => 'ppoo',
			'ップー' => 'ppuu',
			'ッケー' => 'kkee',
			'ッキー' => 'kkii',
			'ッコー' => 'kkoo',
			'ックー' => 'kkuu',
			'ッカー' => 'kkaa',
			'ッガー' => 'ggaa',
			'ッゲー' => 'ggee',
			'ッギー' => 'ggii',
			'ッゴー' => 'ggoo',
			'ッグー' => 'gguu',
			'ッマー' => 'maa',
			'ッメー' => 'mee',
			'ッミー' => 'mii',
			'ッモー' => 'moo',
			'ッムー' => 'muu',
			'ッナー' => 'nnaa',
			'ッネー' => 'nnee',
			'ッニー' => 'nnii',
			'ッノー' => 'nnoo',
			'ッヌー' => 'nnuu',
			'ッラー' => 'rraa',
			'ッレー' => 'rree',
			'ッリー' => 'rrii',
			'ッロー' => 'rroo',
			'ッルー' => 'rruu',
			'ッサー' => 'ssaa',
			'ッセー' => 'ssee',
			'ッシー' => 'sshii',
			'ッソー' => 'ssoo',
			'ッスー' => 'ssuu',
			'ッザー' => 'zzaa',
			'ッゼー' => 'zzee',
			'ッジー' => 'jjii',
			'ッゾー' => 'zzoo',
			'ッズー' => 'zzuu',
			'ッター' => 'ttaa',
			'ッテー' => 'ttee',
			'ッチー' => 'chii',
			'ットー' => 'ttoo',
			'ッツー' => 'ttsuu',
			'ッダー' => 'ddaa',
			'ッデー' => 'ddee',
			'ッヂー' => 'ddii',
			'ッドー' => 'ddoo',
			'ッヅー' => 'dduu',

			// 2 character syllables - normal
			'ファ' => 'fa',
			'フォ' => 'fo',
			'フゥ' => 'fu',
			// 'フャ'=>'fya',
			// 'フェ'=>'fye',
			// 'フィ'=>'fyi',
			// 'フョ'=>'fyo',
			// 'フュ'=>'fyu',
			'フャ' => 'fa',
			'フェ' => 'fe',
			'フィ' => 'fi',
			'フョ' => 'fo',
			'フュ' => 'fu',
			'ヒャ' => 'hya',
			'ヒェ' => 'hye',
			'ヒィ' => 'hyi',
			'ヒョ' => 'hyo',
			'ヒュ' => 'hyu',
			'ビャ' => 'bya',
			'ビェ' => 'bye',
			'ビィ' => 'byi',
			'ビョ' => 'byo',
			'ビュ' => 'byu',
			'ピャ' => 'pya',
			'ピェ' => 'pye',
			'ピィ' => 'pyi',
			'ピョ' => 'pyo',
			'ピュ' => 'pyu',
			'キャ' => 'kya',
			'キェ' => 'kye',
			'キィ' => 'kyi',
			'キョ' => 'kyo',
			'キュ' => 'kyu',
			'ギャ' => 'gya',
			'ギェ' => 'gye',
			'ギィ' => 'gyi',
			'ギョ' => 'gyo',
			'ギュ' => 'gyu',
			'ミャ' => 'mya',
			'ミェ' => 'mye',
			'ミィ' => 'myi',
			'ミョ' => 'myo',
			'ミュ' => 'myu',
			'ニャ' => 'nya',
			'ニェ' => 'nye',
			'ニィ' => 'nyi',
			'ニョ' => 'nyo',
			'ニュ' => 'nyu',
			'リャ' => 'rya',
			'リェ' => 'rye',
			'リィ' => 'ryi',
			'リョ' => 'ryo',
			'リュ' => 'ryu',
			'シャ' => 'sha',
			'シェ' => 'she',
			'ショ' => 'sho',
			'シュ' => 'shu',
			'ジャ' => 'ja',
			'ジェ' => 'je',
			'ジョ' => 'jo',
			'ジュ' => 'ju',
			'スァ' => 'swa',
			'スェ' => 'swe',
			'スィ' => 'swi',
			'スォ' => 'swo',
			'スゥ' => 'swu',
			'デァ' => 'da',
			'デェ' => 'de',
			'ディ' => 'di',
			'デォ' => 'do',
			'デゥ' => 'du',
			'チャ' => 'cha',
			'チェ' => 'che',
			'チョ' => 'cho',
			'チュ' => 'chu',
			// 'ヂャ'=>'dya',
			// 'ヂェ'=>'dye',
			// 'ヂィ'=>'dyi',
			// 'ヂョ'=>'dyo',
			// 'ヂュ'=>'dyu',
			'ツャ' => 'tsa',
			'ツェ' => 'tse',
			'ツィ' => 'tsi',
			'ツョ' => 'tso',
			'トァ' => 'twa',
			'トェ' => 'twe',
			'トィ' => 'twi',
			'トォ' => 'two',
			'トゥ' => 'twu',
			'ドァ' => 'dwa',
			'ドェ' => 'dwe',
			'ドィ' => 'dwi',
			'ドォ' => 'dwo',
			'ドゥ' => 'dwu',
			'ウァ' => 'wha',
			'ウォ' => 'who',
			'ウゥ' => 'whu',
			'ヴャ' => 'vya',
			'ヴョ' => 'vyo',
			'ヴュ' => 'vyu',
			'ヴァ' => 'va',
			'ヴェ' => 've',
			'ヴィ' => 'vi',
			'ヴォ' => 'vo',
			'ヴ' => 'vu',
			'ウェ' => 'we',
			'ウィ' => 'wi',
			'イェ' => 'ye',
			'ティ' => 'ti',
			'ヂィ' => 'di',

			// 2 character syllables - doubled vocal
			'アー' => 'aa',
			'エー' => 'ee',
			'イー' => 'ii',
			'オー' => 'oo',
			'ウー' => 'uu',
			'ダー' => 'daa',
			'デー' => 'dee',
			'ヂー' => 'dii',
			'ドー' => 'doo',
			'ヅー' => 'duu',
			'ハー' => 'haa',
			'ヘー' => 'hee',
			'ヒー' => 'hii',
			'ホー' => 'hoo',
			'フー' => 'fuu',
			'バー' => 'baa',
			'ベー' => 'bee',
			'ビー' => 'bii',
			'ボー' => 'boo',
			'ブー' => 'buu',
			'パー' => 'paa',
			'ペー' => 'pee',
			'ピー' => 'pii',
			'ポー' => 'poo',
			'プー' => 'puu',
			'ケー' => 'kee',
			'キー' => 'kii',
			'コー' => 'koo',
			'クー' => 'kuu',
			'カー' => 'kaa',
			'ガー' => 'gaa',
			'ゲー' => 'gee',
			'ギー' => 'gii',
			'ゴー' => 'goo',
			'グー' => 'guu',
			'マー' => 'maa',
			'メー' => 'mee',
			'ミー' => 'mii',
			'モー' => 'moo',
			'ムー' => 'muu',
			'ナー' => 'naa',
			'ネー' => 'nee',
			'ニー' => 'nii',
			'ノー' => 'noo',
			'ヌー' => 'nuu',
			'ラー' => 'raa',
			'レー' => 'ree',
			'リー' => 'rii',
			'ロー' => 'roo',
			'ルー' => 'ruu',
			'サー' => 'saa',
			'セー' => 'see',
			'シー' => 'shii',
			'ソー' => 'soo',
			'スー' => 'suu',
			'ザー' => 'zaa',
			'ゼー' => 'zee',
			'ジー' => 'jii',
			'ゾー' => 'zoo',
			'ズー' => 'zuu',
			'ター' => 'taa',
			'テー' => 'tee',
			'チー' => 'chii',
			'トー' => 'too',
			'ツー' => 'tsuu',
			'ワー' => 'waa',
			'ヲー' => 'woo',
			'ヤー' => 'yaa',
			'ヨー' => 'yoo',
			'ユー' => 'yuu',
			'ヵー' => 'kaa',
			'ヶー' => 'kee',
			// old characters
			'ヱー' => 'wee',
			'ヰー' => 'wii',

			// seperate katakana 'n'
			'ンア' => 'n_a',
			'ンエ' => 'n_e',
			'ンイ' => 'n_i',
			'ンオ' => 'n_o',
			'ンウ' => 'n_u',
			'ンヤ' => 'n_ya',
			'ンヨ' => 'n_yo',
			'ンユ' => 'n_yu',

			// 2 character syllables - doubled consonants
			'ッバ' => 'bba',
			'ッベ' => 'bbe',
			'ッビ' => 'bbi',
			'ッボ' => 'bbo',
			'ッブ' => 'bbu',
			'ッパ' => 'ppa',
			'ッペ' => 'ppe',
			'ッピ' => 'ppi',
			'ッポ' => 'ppo',
			'ップ' => 'ppu',
			'ッケ' => 'kke',
			'ッキ' => 'kki',
			'ッコ' => 'kko',
			'ック' => 'kku',
			'ッカ' => 'kka',
			'ッガ' => 'gga',
			'ッゲ' => 'gge',
			'ッギ' => 'ggi',
			'ッゴ' => 'ggo',
			'ッグ' => 'ggu',
			'ッマ' => 'ma',
			'ッメ' => 'me',
			'ッミ' => 'mi',
			'ッモ' => 'mo',
			'ッム' => 'mu',
			'ッナ' => 'nna',
			'ッネ' => 'nne',
			'ッニ' => 'nni',
			'ッノ' => 'nno',
			'ッヌ' => 'nnu',
			'ッラ' => 'rra',
			'ッレ' => 'rre',
			'ッリ' => 'rri',
			'ッロ' => 'rro',
			'ッル' => 'rru',
			'ッサ' => 'ssa',
			'ッセ' => 'sse',
			'ッシ' => 'sshi',
			'ッソ' => 'sso',
			'ッス' => 'ssu',
			'ッザ' => 'zza',
			'ッゼ' => 'zze',
			'ッジ' => 'jji',
			'ッゾ' => 'zzo',
			'ッズ' => 'zzu',
			'ッタ' => 'tta',
			'ッテ' => 'tte',
			'ッチ' => 'cchi',
			'ット' => 'tto',
			'ッツ' => 'ttsu',
			'ッダ' => 'dda',
			'ッデ' => 'dde',
			'ッヂ' => 'ddi',
			'ッド' => 'ddo',
			'ッヅ' => 'ddu',

			// 1 character syllables
			'ア' => 'a',
			'エ' => 'e',
			'イ' => 'i',
			'オ' => 'o',
			'ウ' => 'u',
			'ン' => 'n',
			'ハ' => 'ha',
			'ヘ' => 'he',
			'ヒ' => 'hi',
			'ホ' => 'ho',
			'フ' => 'fu',
			'バ' => 'ba',
			'ベ' => 'be',
			'ビ' => 'bi',
			'ボ' => 'bo',
			'ブ' => 'bu',
			'パ' => 'pa',
			'ペ' => 'pe',
			'ピ' => 'pi',
			'ポ' => 'po',
			'プ' => 'pu',
			'ケ' => 'ke',
			'キ' => 'ki',
			'コ' => 'ko',
			'ク' => 'ku',
			'カ' => 'ka',
			'ガ' => 'ga',
			'ゲ' => 'ge',
			'ギ' => 'gi',
			'ゴ' => 'go',
			'グ' => 'gu',
			'マ' => 'ma',
			'メ' => 'me',
			'ミ' => 'mi',
			'モ' => 'mo',
			'ム' => 'mu',
			'ナ' => 'na',
			'ネ' => 'ne',
			'ニ' => 'ni',
			'ノ' => 'no',
			'ヌ' => 'nu',
			'ラ' => 'ra',
			'レ' => 're',
			'リ' => 'ri',
			'ロ' => 'ro',
			'ル' => 'ru',
			'サ' => 'sa',
			'セ' => 'se',
			'シ' => 'shi',
			'ソ' => 'so',
			'ス' => 'su',
			'ザ' => 'za',
			'ゼ' => 'ze',
			'ジ' => 'ji',
			'ゾ' => 'zo',
			'ズ' => 'zu',
			'タ' => 'ta',
			'テ' => 'te',
			'チ' => 'chi',
			'ト' => 'to',
			'ツ' => 'tsu',
			'ダ' => 'da',
			'デ' => 'de',
			'ヂ' => 'di',
			'ド' => 'do',
			'ヅ' => 'du',
			'ワ' => 'wa',
			'ヲ' => 'wo',
			'ヤ' => 'ya',
			'ヨ' => 'yo',
			'ユ' => 'yu',
			'ヵ' => 'ka',
			'ヶ' => 'ke',
			// old characters
			'ヱ' => 'we',
			'ヰ' => 'wi',

			//  convert what's left (probably only kicks in when something's missing above)
			'ァ' => 'a',
			'ェ' => 'e',
			'ィ' => 'i',
			'ォ' => 'o',
			'ゥ' => 'u',
			'ャ' => 'ya',
			'ョ' => 'yo',
			'ュ' => 'yu',

			// special characters
			'・' => '_',
			'、' => '_',
			'ー' => '_',
			// when used with hiragana (seldom), this character would not be converted otherwise

			// 'ラ'=>'la',
			// 'レ'=>'le',
			// 'リ'=>'li',
			// 'ロ'=>'lo',
			// 'ル'=>'lu',
			// 'チャ'=>'cya',
			// 'チェ'=>'cye',
			// 'チィ'=>'cyi',
			// 'チョ'=>'cyo',
			// 'チュ'=>'cyu',
			// 'デャ'=>'dha',
			// 'デェ'=>'dhe',
			// 'ディ'=>'dhi',
			// 'デョ'=>'dho',
			// 'デュ'=>'dhu',
			// 'リャ'=>'lya',
			// 'リェ'=>'lye',
			// 'リィ'=>'lyi',
			// 'リョ'=>'lyo',
			// 'リュ'=>'lyu',
			// 'テャ'=>'tha',
			// 'テェ'=>'the',
			// 'ティ'=>'thi',
			// 'テョ'=>'tho',
			// 'テュ'=>'thu',
			// 'ファ'=>'fwa',
			// 'フェ'=>'fwe',
			// 'フィ'=>'fwi',
			// 'フォ'=>'fwo',
			// 'フゥ'=>'fwu',
			// 'チャ'=>'tya',
			// 'チェ'=>'tye',
			// 'チィ'=>'tyi',
			// 'チョ'=>'tyo',
			// 'チュ'=>'tyu',
			// 'ジャ'=>'jya',
			// 'ジェ'=>'jye',
			// 'ジィ'=>'jyi',
			// 'ジョ'=>'jyo',
			// 'ジュ'=>'jyu',
			// 'ジャ'=>'zha',
			// 'ジェ'=>'zhe',
			// 'ジィ'=>'zhi',
			// 'ジョ'=>'zho',
			// 'ジュ'=>'zhu',
			// 'ジャ'=>'zya',
			// 'ジェ'=>'zye',
			// 'ジィ'=>'zyi',
			// 'ジョ'=>'zyo',
			// 'ジュ'=>'zyu',
			// 'シャ'=>'sya',
			// 'シェ'=>'sye',
			// 'シィ'=>'syi',
			// 'ショ'=>'syo',
			// 'シュ'=>'syu',
			// 'シ'=>'ci',
			// 'フ'=>'hu',
			// 'シ'=>'si',
			// 'チ'=>'ti',
			// 'ツ'=>'tu',
			// 'イ'=>'yi',
			// 'ヂ'=>'dzi',

			// "Greeklish"
			'Α' => 'a',
			'Ά' => 'a',
			'Β' => 'b',
			'Γ' => 'g',
			'Δ' => 'd',
			'Ε' => 'e',
			'Έ' => 'e',
			'Ζ' => 'z',
			'Η' => 'i',
			'Ή' => 'i',
			'Θ' => 'th',
			'Ι' => 'i',
			'Ί' => 'i',
			'Ϊ' => 'i',
			'ΐ' => 'i',
			'Κ' => 'k',
			'Λ' => 'l',
			'Μ' => 'm',
			'Ν' => 'n',
			'Ξ' => 'x',
			'Ο' => 'o',
			'Ό' => 'o',
			'Π' => 'p',
			'Ρ' => 'r',
			'Σ' => 's',
			'Τ' => 't',
			'Υ' => 'y',
			'Ύ' => 'y',
			'Ϋ' => 'y',
			'ΰ' => 'y',
			'Φ' => 'f',
			'Χ' => 'ch',
			'Ψ' => 'ps',
			'Ω' => 'o',
			'Ώ' => 'o',
			'α' => 'a',
			'ά' => 'a',
			'β' => 'b',
			'γ' => 'g',
			'δ' => 'd',
			'ε' => 'e',
			'έ' => 'e',
			'ζ' => 'z',
			'η' => 'i',
			'ή' => 'i',
			'θ' => 'th',
			'ι' => 'i',
			'ί' => 'i',
			'ϊ' => 'i',
			'κ' => 'k',
			'λ' => 'l',
			'μ' => 'm',
			'ν' => 'n',
			'ξ' => 'x',
			'ο' => 'o',
			'ό' => 'o',
			'π' => 'p',
			'ρ' => 'r',
			'σ' => 's',
			'ς' => 's',
			'τ' => 't',
			'υ' => 'y',
			'ύ' => 'y',
			'ϋ' => 'y',
			'φ' => 'f',
			'χ' => 'ch',
			'ψ' => 'ps',
			'ω' => 'o',
			'ώ' => 'o',

			// Thai
			'ก' => 'k',
			'ข' => 'kh',
			'ฃ' => 'kh',
			'ค' => 'kh',
			'ฅ' => 'kh',
			'ฆ' => 'kh',
			'ง' => 'ng',
			'จ' => 'ch',
			'ฉ' => 'ch',
			'ช' => 'ch',
			'ซ' => 's',
			'ฌ' => 'ch',
			'ญ' => 'y',
			'ฎ' => 'd',
			'ฏ' => 't',
			'ฐ' => 'th',
			'ฑ' => 'd',
			'ฒ' => 'th',
			'ณ' => 'n',
			'ด' => 'd',
			'ต' => 't',
			'ถ' => 'th',
			'ท' => 'th',
			'ธ' => 'th',
			'น' => 'n',
			'บ' => 'b',
			'ป' => 'p',
			'ผ' => 'ph',
			'ฝ' => 'f',
			'พ' => 'ph',
			'ฟ' => 'f',
			'ภ' => 'ph',
			'ม' => 'm',
			'ย' => 'y',
			'ร' => 'r',
			'ฤ' => 'rue',
			'ฤๅ' => 'rue',
			'ล' => 'l',
			'ฦ' => 'lue',
			'ฦๅ' => 'lue',
			'ว' => 'w',
			'ศ' => 's',
			'ษ' => 's',
			'ส' => 's',
			'ห' => 'h',
			'ฬ' => 'l',
			'ฮ' => 'h',
			'ะ' => 'a',
			'ั' => 'a',
			'รร' => 'a',
			'า' => 'a',
			'ๅ' => 'a',
			'ำ' => 'am',
			'ํา' => 'am',
			'ิ' => 'i',
			'ึ' => 'ue',
			'ี' => 'ue',
			'ุ' => 'u',
			'ู' => 'u',
			'เ' => 'e',
			'แ' => 'ae',
			'โ' => 'o',
			'อ' => 'o',
			'ียะ' => 'ia',
			'ีย' => 'ia',
			'ือะ' => 'uea',
			'ือ' => 'uea',
			'ัวะ' => 'ua',
			'ัว' => 'ua',
			'ใ' => 'ai',
			'ไ' => 'ai',
			'ัย' => 'ai',
			'าย' => 'ai',
			'าว' => 'ao',
			'ุย' => 'ui',
			'อย' => 'oi',
			'ือย' => 'ueai',
			'วย' => 'uai',
			'ิว' => 'io',
			'็ว' => 'eo',
			'ียว' => 'iao',
			'่' => '',
			'้' => '',
			'๊' => '',
			'๋' => '',
			'็' => '',
			'์' => '',
			'๎' => '',
			'ํ' => '',
			'ฺ' => '',
			'ๆ' => '2',
			'๏' => 'o',
			'ฯ' => '-',
			'๚' => '-',
			'๛' => '-',
			'๐' => '0',
			'๑' => '1',
			'๒' => '2',
			'๓' => '3',
			'๔' => '4',
			'๕' => '5',
			'๖' => '6',
			'๗' => '7',
			'๘' => '8',
			'๙' => '9',

			// Korean
			'ㄱ' => 'k', 'ㅋ' => 'kh',
			'ㄲ' => 'kk',
			'ㄷ' => 't',
			'ㅌ' => 'th',
			'ㄸ' => 'tt',
			'ㅂ' => 'p',
			'ㅍ' => 'ph',
			'ㅃ' => 'pp',
			'ㅈ' => 'c',
			'ㅊ' => 'ch',
			'ㅉ' => 'cc',
			'ㅅ' => 's',
			'ㅆ' => 'ss',
			'ㅎ' => 'h',
			'ㅇ' => 'ng',
			'ㄴ' => 'n',
			'ㄹ' => 'l',
			'ㅁ' => 'm',
			'ㅏ' => 'a',
			'ㅓ' => 'e',
			'ㅗ' => 'o',
			'ㅜ' => 'wu',
			'ㅡ' => 'u',
			'ㅣ' => 'i',
			'ㅐ' => 'ay',
			'ㅔ' => 'ey',
			'ㅚ' => 'oy',
			'ㅘ' => 'wa',
			'ㅝ' => 'we',
			'ㅟ' => 'wi',
			'ㅙ' => 'way',
			'ㅞ' => 'wey',
			'ㅢ' => 'uy',
			'ㅑ' => 'ya',
			'ㅕ' => 'ye',
			'ㅛ' => 'oy',
			'ㅠ' => 'yu',
			'ㅒ' => 'yay',
			'ㅖ' => 'yey',
		];

		\XF::app()->fire('string_data_romanization', [&$romanization]);

		return $romanization;
	}
}
