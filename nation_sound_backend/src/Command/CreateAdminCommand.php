<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'create admin user',
)]
class CreateAdminCommand extends Command
{
    protected static $defaultDescription = 'Crée un utilisateur admin';
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager
    )   {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'adminpassword')
        );

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('Admin créé avec succès !');
        return Command::SUCCESS;
    }
}
