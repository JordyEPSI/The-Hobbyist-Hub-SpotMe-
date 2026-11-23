<?php

namespace App\Command;

use App\Repository\ExerciceRepository;
use App\Service\ImagesExercices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Copie en local (WebP réduit) les images des exercices qui pointent encore vers un autre site
#[AsCommand(name: 'app:images:copier', description: 'Copie les images des exercices sur le serveur, en WebP allégé')]
class CopierImagesCommand extends Command
{
    public function __construct(
        private readonly ExerciceRepository $exercices,
        private readonly ImagesExercices $images,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    // Parcourt le catalogue et copie chaque image distante
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $copiees = 0;
        $echecs = 0;

        foreach ($this->exercices->findAll() as $exercice) {
            // Image absente ou déjà locale : rien à faire
            if ($exercice->getImage() === null || !str_starts_with($exercice->getImage(), 'https://')) {
                continue;
            }

            if ($this->images->copierEnLocal($exercice)) {
                $io->text('+ '.$exercice->getNom());
                $copiees++;
            } else {
                $io->warning('Image impossible à copier : '.$exercice->getNom());
                $echecs++;
            }
        }

        $this->em->flush();
        $io->success($copiees.' image(s) copiée(s), '.$echecs.' échec(s).');

        return Command::SUCCESS;
    }
}
