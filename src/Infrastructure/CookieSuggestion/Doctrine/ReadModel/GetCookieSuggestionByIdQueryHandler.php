<?php

declare(strict_types=1);

namespace App\Infrastructure\CookieSuggestion\Doctrine\ReadModel;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use App\ReadModel\CookieSuggestion\CookieSuggestion;
use App\ReadModel\CookieSuggestion\GetCookieSuggestionByIdQuery;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class GetCookieSuggestionByIdQueryHandler implements QueryHandlerInterface
{
	private EntityManagerInterface $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	/**
	 * @throws Exception
	 */
	public function __invoke(GetCookieSuggestionByIdQuery $query): ?CookieSuggestion
	{
		$connection = $this->em->getConnection();

		$result = $connection->createQueryBuilder()
			->select('cs.id, cs.project_id, cs.name, cs.domain, cs.ignored_until_next_occurrence')
			->from('cookie_suggestion', 'cs')
			->where('cs.id = :id')
			->setParameters([
				'id' => $query->cookieSuggestionId(),
			])
			->setMaxResults(1)
			->fetchAssociative();

		return $result ? new CookieSuggestion(
			$result['id'],
			$result['project_id'],
			$result['name'],
			$result['domain'],
			$result['ignored_until_next_occurrence'],
		) : NULL;
	}
}