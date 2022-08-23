<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Doctrine\ReadModel;

use App\Domain\Project\Project;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\ReadModel\Project\ProjectExportQuery;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\Batch;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\BatchUtils;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\QueryHandlerInterface;

final class ProjectExportQueryHandler implements QueryHandlerInterface
{
	private EntityManagerInterface $em;

	/**
	 * @param \Doctrine\ORM\EntityManagerInterface $em
	 */
	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}

	/**
	 * @param \App\ReadModel\Project\ProjectExportQuery $query
	 *
	 * @return iterable|\SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\Batch[]
	 */
	public function __invoke(ProjectExportQuery $query): iterable
	{
		$q = $this->em->createQueryBuilder()
			->select('p.id, p.name, p.code, p.color, p.description, p.active, p.locales.locales, p.locales.defaultLocale')
			->from(Project::class, 'p')
			->where('p.deletedAt IS NULL')
			->orderBy('p.name', 'ASC')
			->getQuery()
			->setHydrationMode(AbstractQuery::HYDRATE_ARRAY);

		$paginator = new Paginator($q, FALSE);
		$paginator->setUseOutputWalkers(FALSE);
		$totalCount = count($paginator);

		foreach (BatchUtils::from($totalCount, $query->batchSize()) as [$limit, $offset]) {
			$paginator->getQuery()
				->setMaxResults($limit)
				->setFirstResult($query->staticOffset() ?? $offset);

			$results = [];

			foreach ($paginator as $item) {
				$item['locales'] = $item['locales.locales'];
				$item['defaultLocale'] = $item['locales.defaultLocale'];
				unset($item['locales.locales'], $item['locales.defaultLocale']);

				$results[] = $item;
			}

			yield Batch::create(
				$query->batchSize(),
				$query->staticOffset() ?? $offset,
				$totalCount,
				$results
			);
		}
	}
}