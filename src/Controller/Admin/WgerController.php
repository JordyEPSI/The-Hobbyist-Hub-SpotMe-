<?php

namespace App\Controller\Admin;

use App\Repository\ExerciceRepository;
use App\Service\CatalogueExercices;
use App\Wger\CorrespondanceWger;
use App\Wger\ExerciceWger;
use App\Wger\WgerClient;
use App\Wger\WgerIndisponibleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/wger')]
#[IsGranted('ROLE_ADMIN')]
class WgerController extends AbstractController
{
    private const MAX_PAR_IMPORT = 50;

    // Recherche sur wger par groupe musculaire et matériel
    #[Route('', name: 'admin_wger', methods: ['GET'])]
    public function rechercher(Request $request, WgerClient $wger, ExerciceRepository $exercices): Response
    {
        $groupe = $request->query->getString('groupe');
        $materiel = $request->query->getString('materiel');
        $resultats = [];
        $importes = [];

        if (isset(CorrespondanceWger::GROUPES[$groupe])) {
            try {
                $muscleWger = CorrespondanceWger::GROUPES[$groupe][2];
                $resultats = $wger->rechercher($muscleWger, CorrespondanceWger::idWgerMateriel($materiel));

                $idsWger = [];
                foreach ($resultats as $resultat) {
                    $idsWger[] = $resultat->idWger;
                }
                $importes = $exercices->findIdsWgerImportes($idsWger);
            } catch (WgerIndisponibleException $e) {
                $this->addFlash('erreur', $e->getMessage());
            }
        }

        return $this->render('admin/wger/index.html.twig', [
            'groupes' => CorrespondanceWger::nomsGroupes(),
            'materiels' => CorrespondanceWger::nomsMateriels(),
            'groupe' => $groupe,
            'materiel' => $materiel,
            'resultats' => $resultats,
            'importes' => $importes,
        ]);
    }

    // Importe les exercices cochés (50 maximum à la fois)
    #[Route('/importer', name: 'admin_wger_importer', methods: ['POST'])]
    public function importer(Request $request, WgerClient $wger, CatalogueExercices $catalogue): Response
    {
        $groupe = $request->getPayload()->getString('groupe');
        $materiel = $request->getPayload()->getString('materiel');
        $retour = $this->redirectToRoute('admin_wger', ['groupe' => $groupe, 'materiel' => $materiel]);

        if (!$this->isCsrfTokenValid('import-wger', $request->getPayload()->getString('_token'))) {
            $this->addFlash('erreur', 'Jeton de sécurité invalide, réessaie.');

            return $retour;
        }

        $ids = $request->getPayload()->all('ids');
        if (count($ids) === 0) {
            $this->addFlash('erreur', 'Coche au moins un exercice à importer.');

            return $retour;
        }

        $importes = 0;
        try {
            // Optimisation : on relance la même recherche, déjà en cache, au lieu
            // d'appeler wger une fois par exercice coché (20 cochés = 20 appels réseau)
            $resultats = [];
            if (isset(CorrespondanceWger::GROUPES[$groupe])) {
                $resultats = $wger->rechercher(CorrespondanceWger::GROUPES[$groupe][2], CorrespondanceWger::idWgerMateriel($materiel));
            }

            foreach (array_slice($ids, 0, self::MAX_PAR_IMPORT) as $id) {
                $exerciceWger = $this->trouver($resultats, (int) $id);

                // Pas dans les résultats (cas rare) : on le demande directement à wger
                if ($exerciceWger === null) {
                    $exerciceWger = $wger->recuperer((int) $id);
                }

                if ($exerciceWger !== null && $catalogue->importer($exerciceWger) !== null) {
                    $importes++;
                }
            }
        } catch (WgerIndisponibleException $e) {
            $this->addFlash('erreur', $e->getMessage());
        }

        $this->addFlash('succes', $importes.' exercice(s) importé(s) depuis wger.');

        return $retour;
    }

    // Retourne l'exercice qui a cet id wger dans la liste, ou null
    private function trouver(array $resultats, int $idWger): ?ExerciceWger
    {
        foreach ($resultats as $resultat) {
            if ($resultat->idWger === $idWger) {
                return $resultat;
            }
        }

        return null;
    }
}
