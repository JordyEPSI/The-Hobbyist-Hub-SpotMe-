<?php

namespace App\Command;

use App\Service\CatalogueExercices;
use App\Wger\CorrespondanceWger;
use App\Wger\WgerClient;
use App\Wger\WgerIndisponibleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:wger:importer', description: 'Remplit le catalogue avec les exercices classiques (API wger)')]
class ImporterWgerCommand extends Command
{
    // id wger => nom affiché (null : on garde le nom français de wger)
    private const CLASSIQUES = [
        73 => null,
        537 => 'Développé incliné aux haltères',
        1551 => 'Pompes',
        1801 => 'Squat',
        184 => 'Soulevé de terre',
        984 => null,
        294 => 'Hip thrust',
        475 => null,
        83 => null,
        1893 => 'Développé militaire',
        348 => null,
        92 => null,
        194 => null,
        1933 => 'Crunch',
        622 => null,
    ];

    public function __construct(
        private readonly WgerClient $wger,
        private readonly CatalogueExercices $catalogue,
    ) {
        parent::__construct();
    }

    // Options de la commande : --par-groupe et --anglais
    protected function configure(): void
    {
        $this
            ->addOption('par-groupe', null, InputOption::VALUE_REQUIRED, 'Au lieu des classiques : N exercices par groupe musculaire')
            ->addOption('anglais', null, InputOption::VALUE_NONE, 'Accepter aussi les exercices sans traduction française');
    }

    // Point d'entrée de la commande : importe les classiques, ou N exercices par groupe
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->catalogue->initialiserReferentiel();

        try {
            if ($input->getOption('par-groupe') === null) {
                $total = $this->importerClassiques($io);
            } else {
                $parGroupe = max(1, (int) $input->getOption('par-groupe'));
                $total = $this->importerParGroupe($io, $parGroupe, $input->getOption('anglais'));
            }
        } catch (WgerIndisponibleException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success($total.' exercice(s) ajouté(s) au catalogue.');

        return Command::SUCCESS;
    }

    // Importe la liste CLASSIQUES, retourne le nombre d'exercices ajoutés
    private function importerClassiques(SymfonyStyle $io): int
    {
        $total = 0;
        foreach (self::CLASSIQUES as $idWger => $nom) {
            $exerciceWger = $this->wger->recuperer($idWger);
            if ($exerciceWger === null) {
                $io->warning('Exercice wger '.$idWger.' introuvable.');
                continue;
            }
            if ($nom !== null) {
                $exerciceWger = $exerciceWger->avecNom($nom);
            }
            if ($this->catalogue->importer($exerciceWger) !== null) {
                $io->text('+ '.$exerciceWger->nom);
                $total++;
            }
        }

        return $total;
    }

    // Importe jusqu'à N exercices pour chaque groupe musculaire
    private function importerParGroupe(SymfonyStyle $io, int $parGroupe, bool $avecAnglais): int
    {
        $total = 0;
        foreach (CorrespondanceWger::GROUPES as $groupe) {
            $importes = 0;
            foreach ($this->wger->rechercher($groupe[2]) as $exerciceWger) {
                if ($importes >= $parGroupe) {
                    break;
                }
                if (!$exerciceWger->estEnFrancais && !$avecAnglais) {
                    continue;
                }
                if ($this->catalogue->importer($exerciceWger) !== null) {
                    $importes++;
                }
            }

            $io->text($groupe[0].' : '.$importes.' exercice(s)');
            $total += $importes;
        }

        return $total;
    }
}
