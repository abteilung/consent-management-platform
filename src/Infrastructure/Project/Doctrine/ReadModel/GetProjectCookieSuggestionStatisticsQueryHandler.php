<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use App\ReadModel\Project\ProjectCookieSuggestionsStatistics;
use App\ReadModel\Project\GetProjectCookieSuggestionStatisticsQuery;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class GetProjectCookieSuggestionStatisticsQueryHandler implements QueryHandlerInterface
{
	private EntityManagerInterface $em;

	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	/**
	 * @throws Exception
	 * @throws \Exception
	 */
	public function __invoke(GetProjectCookieSuggestionStatisticsQuery $query): ?ProjectCookieSuggestionsStatistics
	{
		$row = $this->em->getConnection()->createQueryBuilder()
			->select('ps.missing, ps.unassociated, ps.problematic, ps.unproblematic, ps.ignored, ps.total, ps.total_without_virtual, ps.latest_found_at')
			->from('project_cookie_suggestion_statistics', 'ps')
			->where('ps.project_id = :projectId')
			->setMaxResults(1)
			->setParameter('projectId', $query->projectId(), Types::GUID)
			->fetchAssociative();

		if (!$row) {
			return NULL;
		}

		return new ProjectCookieSuggestionsStatistics(
			$row['missing'],
			$row['unassociated'],
			$row['problematic'],
			$row['unproblematic'],
			$row['ignored'],
			$row['total'],
			$row['total_without_virtual'],
			NULL !== $row['latest_found_at'] ? new DateTimeImmutable($row['latest_found_at']) : NULL,
		);
	}
}
