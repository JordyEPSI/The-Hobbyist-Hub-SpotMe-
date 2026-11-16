<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'app:creer-admin', description: 'Crée un compte administrateur (ou promeut un compte existant)')]
class CreerAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurs,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email du compte')
            ->addArgument('pseudo', InputArgument::OPTIONAL, 'Pseudo (pour un nouveau compte)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = mb_strtolower(trim($input->getArgument('email')));

        $existant = $this->utilisateurs->findOneBy(['email' => $email]);
        if ($existant) {
            $existant->setRoles(['ROLE_ADMIN']);
            $this->em->flush();
            $io->success(sprintf('%s est maintenant administrateur.', $existant->getPseudo()));

            return Command::SUCCESS;
        }

        $pseudo = $input->getArgument('pseudo');
        if (!$pseudo) {
            $io->error('Aucun compte avec cet email : indique aussi un pseudo pour le créer.');

            return Command::INVALID;
        }

        $motDePasse = $io->askHidden('Mot de passe (8 caractères minimum)');
        if (!is_string($motDePasse) || mb_strlen($motDePasse) < 8) {
            $io->error('Mot de passe trop court.');

            return Command::INVALID;
        }

        $admin = (new Utilisateur())
            ->setEmail($email)
            ->setPseudo($pseudo)
            ->setRoles(['ROLE_ADMIN']);
        $admin->setMotDePasse($this->hasher->hashPassword($admin, $motDePasse));

        $erreurs = $this->validator->validate($admin);
        if (count($erreurs) > 0) {
            foreach ($erreurs as $erreur) {
                $io->error($erreur->getPropertyPath().' : '.$erreur->getMessage());
            }

            return Command::INVALID;
        }

        $this->em->persist($admin);
        $this->em->flush();
        $io->success(sprintf('Administrateur %s créé.', $pseudo));

        return Command::SUCCESS;
    }
}
