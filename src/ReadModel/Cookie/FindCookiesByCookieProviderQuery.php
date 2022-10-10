<?php

declare(strict_types=1);

namespace App\ReadModel\Cookie;

use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\AbstractQuery;

/**
 * Returns CookieView[]
 */
final class FindCookiesByCookieProviderQuery extends AbstractQuery
{
	/**
	 * @param string $cookieProviderId
	 *
	 * @return static
	 */
	public static function create(string $cookieProviderId): self
	{
		return self::fromParameters([
			'cookie_provider_id' => $cookieProviderId,
		]);
	}

	/**
	 * @return string
	 */
	public function cookieProviderId(): string
	{
		return $this->getParam('cookie_provider_id');
	}
}