<?php

declare(strict_types=1);

namespace App\Web\AdminModule\UserModule\Control\UserForm;

use Throwable;
use App\Web\Ui\Control;
use App\Domain\User\RolesEnum;
use Nette\Application\UI\Form;
use App\Web\Utils\TranslatorUtils;
use Nette\Forms\Controls\TextInput;
use App\ReadModel\Project\ProjectView;
use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\Ui\Form\FormFactoryOptionsTrait;
use App\ReadModel\Project\FindAllProjectsQuery;
use App\ReadModel\Project\FindUserProjectsQuery;
use SixtyEightPublishers\UserBundle\ReadModel\View\UserView;
use SixtyEightPublishers\UserBundle\Domain\ValueObject\UserId;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface;
use SixtyEightPublishers\UserBundle\Domain\Command\CreateUserCommand;
use SixtyEightPublishers\UserBundle\Domain\Command\UpdateUserCommand;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserCreatedEvent;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserUpdatedEvent;
use SixtyEightPublishers\UserBundle\Domain\Exception\UsernameUniquenessException;
use SixtyEightPublishers\UserBundle\Domain\Exception\EmailAddressUniquenessException;
use App\Web\AdminModule\UserModule\Control\UserForm\Event\UserFormProcessingFailedEvent;

final class UserFormControl extends Control
{
	use FormFactoryOptionsTrait;

	private FormFactoryInterface $formFactory;

	private CommandBusInterface $commandBus;

	private QueryBusInterface $queryBus;

	private ?UserView $default;

	/**
	 * @param \App\Web\Ui\Form\FormFactoryInterface                            $formFactory
	 * @param \SixtyEightPublishers\ArchitectureBundle\Bus\CommandBusInterface $commandBus
	 * @param \SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface   $queryBus
	 * @param \SixtyEightPublishers\UserBundle\ReadModel\View\UserView|NULL    $default
	 */
	public function __construct(FormFactoryInterface $formFactory, CommandBusInterface $commandBus, QueryBusInterface $queryBus, ?UserView $default = NULL)
	{
		$this->formFactory = $formFactory;
		$this->commandBus = $commandBus;
		$this->queryBus = $queryBus;
		$this->default = $default;
	}

	/**
	 * @return \Nette\Application\UI\Form
	 */
	protected function createComponentForm(): Form
	{
		$form = $this->formFactory->create($this->getFormFactoryOptions());
		$translator = $this->getPrefixedTranslator();

		$form->setTranslator($translator);

		$emailAddressField = $form->addText('email_address', 'email_address.field')
			->setHtmlType('email')
			->setRequired('email_address.required')
			->addRule($form::EMAIL, 'email_address.rule');

		$form->addText('firstname', 'firstname.field')
			->setRequired('firstname.required');

		$form->addText('surname', 'surname.field')
			->setRequired('surname.required');

		$form->addCheckboxList('roles', 'roles.field', array_combine(RolesEnum::values(), TranslatorUtils::translateArray($translator, '//layout.role_name.', RolesEnum::values())))
			->setTranslator(NULL)
			->setRequired('roles.required');

		$form->addPassword('password', 'password.field')
			->setOption('description', NULL === $this->default ? 'password.description.add' : 'password.description.edit');

		$projects = [];

		foreach ($this->queryBus->dispatch(FindAllProjectsQuery::create()) as $projectView) {
			assert($projectView instanceof ProjectView);
			$projects[$projectView->id->toString()] = $projectView->name->value();
		}

		$form->addCheckboxList('projects', 'projects.field', $projects)
			->checkDefaultValue(FALSE)
			->setTranslator(NULL);

		$form->addProtection('//layout.form_protection');

		$form->addSubmit('save', NULL === $this->default ? 'save.field' : 'update.field');

		if (NULL !== $this->default) {
			$emailAddressField->setRequired(FALSE)
				->setDisabled();

			$form->setDefaults([
				'email_address' => $this->default->emailAddress->value(),
				'firstname' => $this->default->name->firstname(),
				'surname' => $this->default->name->surname(),
				'roles' => $this->default->roles->toArray(),
				'projects' => array_map(
					static fn (ProjectView $projectView): string => $projectView->id->toString(),
					$this->queryBus->dispatch(FindUserProjectsQuery::create($this->default->id->toString()))
				),
			]);
		}

		$form->onSuccess[] = function (Form $form) {
			$this->saveUser($form);
		};

		return $form;
	}

	/**
	 * @param \Nette\Application\UI\Form $form
	 *
	 * @return void
	 */
	private function saveUser(Form $form): void
	{
		$values = $form->values;

		if (NULL === $this->default) {
			$userId = UserId::new();
			$command = CreateUserCommand::create(
				$values->email_address,
				'' === $values->password ? NULL : $values->password,
				$values->email_address,
				$values->firstname,
				$values->surname,
				$values->roles,
				$userId->toString()
			);
		} else {
			$userId = $this->default->id;
			$command = UpdateUserCommand::create($userId->toString())
				->withFirstname($values->firstname)
				->withSurname($values->surname)
				->withRoles($values->roles);

			if ('' !== $values->password) {
				$command = $command->withPassword($values->password);
			}
		}

		$command = $command->withParam('project_ids', $values->projects);

		try {
			$this->commandBus->dispatch($command);
		} catch (UsernameUniquenessException|EmailAddressUniquenessException $e) {
			$emailAddressField = $form->getComponent('email_address');
			assert($emailAddressField instanceof TextInput);

			$emailAddressField->addError('email_address.error.duplicated_value');

			return;
		} catch (Throwable $e) {
			$this->logger->error((string) $e);
			$this->dispatchEvent(new UserFormProcessingFailedEvent($e));

			return;
		}

		$this->dispatchEvent(NULL === $this->default ? new UserCreatedEvent($userId) : new UserUpdatedEvent($userId));
		$this->redrawControl();
	}
}