<?php

declare(strict_types=1);

namespace Ibs\Context\SecurityIdentity\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ibs\Context\SecurityIdentity\Entity\MedicalPersonnel;
use Ibs\Context\SecurityIdentity\Entity\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Создание пользователя (логин/пароль/роль) для демо и посева.')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('login', null, InputOption::VALUE_REQUIRED, 'Логин пользователя.')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Пароль (открытый текст на входе; в БД — только хэш).')
            ->addOption('role', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Роль (можно повторять).')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Имя медперсонала для привязки MedicalPersonnel.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $login = $input->getOption('login');
        if (!\is_string($login) || '' === \trim($login)) {
            $output->writeln('<error>Опция --login обязательна.</error>');

            return Command::INVALID;
        }
        $login = \trim($login);

        $password = $input->getOption('password');
        if (!\is_string($password) || '' === $password) {
            $output->writeln('<error>Опция --password обязательна.</error>');

            return Command::INVALID;
        }

        // Идемпотентность: существующий login — пропускаем без дублирования.
        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['login' => $login]);
        if (null !== $existing) {
            $output->writeln(\sprintf('<comment>Пользователь «%s» уже существует — пропускаю.</comment>', $login));

            return Command::SUCCESS;
        }

        $user = new User();
        $user->setLogin($login);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $roles = $input->getOption('role');
        $roles = \is_array($roles) && [] !== $roles ? \array_values($roles) : ['ROLE_USER'];
        $user->setRoles($roles);

        $name = $input->getOption('name');
        if (\is_string($name) && '' !== \trim($name)) {
            $medicalPersonnel = new MedicalPersonnel();
            $medicalPersonnel->setName(\trim($name));
            $this->entityManager->persist($medicalPersonnel);
            $user->setMedicalPersonnel($medicalPersonnel);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln(\sprintf('<info>Пользователь «%s» создан.</info>', $login));

        return Command::SUCCESS;
    }
}
