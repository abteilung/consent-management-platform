<?php

declare(strict_types=1);

namespace App\Web\Utils;

use Nette\Localization\Translator;

final class TranslatorUtils
{
	private function __construct()
	{
	}

	/**
	 * @param \Nette\Localization\Translator $translator
	 * @param string                         $prefix
	 * @param array                          $array
	 *
	 * @return array
	 */
	public static function translateArray(Translator $translator, string $prefix, array $array): array
	{
		return array_map(static function ($item) use ($prefix, $translator): string {
			return $translator->translate($prefix . $item);
		}, $array);
	}
}
