<?php

declare(strict_types=1);

namespace App\Web\AdminModule\UserModule\Control\UserList;

use App\Web\Ui\Control;
use App\Web\Ui\DataGrid\DataGrid;
use App\ReadModel\Query\UsersDataGridQuery;
use App\Web\Ui\DataGrid\Helper\FilterHelper;
use App\Web\Ui\DataGrid\DataGridFactoryInterface;

final class UserListControl extends Control
{
	private DataGridFactoryInterface $dataGridFactory;

	/**
	 * @param \App\Web\Ui\DataGrid\DataGridFactoryInterface $dataGridFactory
	 */
	public function __construct(DataGridFactoryInterface $dataGridFactory)
	{
		$this->dataGridFactory = $dataGridFactory;
	}

	/**
	 * @return \App\Web\Ui\DataGrid\DataGrid
	 * @throws \Ublaboo\DataGrid\Exception\DataGridException
	 */
	protected function createComponentGrid(): DataGrid
	{
		$grid = $this->dataGridFactory->create(UsersDataGridQuery::create());

		$grid->setTranslator($this->getPrefixedTranslator());

		$grid->setDefaultSort([
			'created_at' => 'DESC',
		]);

		$grid->addColumnText('id', 'id', 'id.toString')
			->setFilterText('id');

		$grid->addColumnText('email_address', 'email_address', 'emailAddress.value')
			->setSortable('emailAddress')
			->setFilterText('emailAddress');

		$grid->addColumnText('name', 'name', 'name.name')
			->setSortable('name')
			->setFilterText('name');

		$grid->addColumnDateTimeTz('created_at', 'created_at', 'createdAt')
			->setFormat('j.n.Y H:i:s')
			->setSortable('createdAt')
			->setFilterDate('createdAt');

		$grid->addColumnText('roles', 'roles')
			->setTemplate(__DIR__ . '/templates/column.roles.latte');
		//->setFilterMultiSelect(FilterHelper::items(['admin', 'supervisor'], FALSE, $this->getTranslator(), '//layout.role_name.'));

		return $grid;
	}
}
