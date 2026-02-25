<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:hash-passwords', description: 'Hash all plain-text passwords for users')]
class HashPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->em->getRepository(User::class);
        $users = $userRepository->findAll();

        $hashedCount = 0;
        foreach ($users as $user) {
            $plainPassword = $user->getPassword();
            if ($plainPassword && !$this->isAlreadyHashed($plainPassword)) {
                $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashed);
                $hashedCount++;
                $output->writeln(sprintf('Hashed password for user: %s (id: %d)', $user->getLogin(), $user->getId()));
            }
        }

        if ($hashedCount > 0) {
            $this->em->flush();
            $output->writeln(sprintf('Successfully hashed %d passwords.', $hashedCount));
        } else {
            $output->writeln('No plain-text passwords found.');
        }

        return Command::SUCCESS;
    }

    private function isAlreadyHashed(string $password): bool
    {
        return str_starts_with($password, '$2y$')
            || str_starts_with($password, '$2a$')
            || str_starts_with($password, '$argon2');
    }
}