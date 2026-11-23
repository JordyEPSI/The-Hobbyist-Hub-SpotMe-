<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercice>
 */
class ExerciceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercice::class);
    }

    // Catalogue trié par nom, avec matériel et muscles chargés en une seule requête.
    // Filtres facultatifs : un mot du nom, un groupe musculaire principal, un matériel (slugs).
    public function findCatalogue(string $recherche = '', string $groupe = '', string $materiel = ''): array
    {
        $requete = $this->createQueryBuilder('e')
            ->addSelect('m', 'c', 'g')
            ->join('e.materiel', 'm')
            ->leftJoin('e.ciblers', 'c')
            ->leftJoin('c.groupeMusculaire', 'g')
            ->orderBy('e.nom', 'ASC');

        if ($recherche !== '') {
            $requete->andWhere('e.nom LIKE :recherche')
                ->setParameter('recherche', '%'.$recherche.'%');
        }

        if ($materiel !== '') {
            $requete->andWhere('m.slug = :materiel')
                ->setParameter('materiel', $materiel);
        }

        // Sous-requête : on garde tous les muscles de l'exercice pour l'affichage,
        // mais seulement les exercices dont ce groupe est un muscle principal
        if ($groupe !== '') {
            $requete->andWhere('e.id IN (
                    SELECT IDENTITY(c2.exercice) FROM App\Entity\Cibler c2
                    JOIN c2.groupeMusculaire g2
                    WHERE g2.slug = :groupe AND c2.estPrincipal = true
                )')
                ->setParameter('groupe', $groupe);
        }

        return $requete->getQuery()->getResult();
    }

    // Un exercice par son slug, avec matériel et muscles
    public function findUnAvecDetails(string $slug): ?Exercice
    {
        return $this->createQueryBuilder('e')
            ->addSelect('m', 'c', 'g')
            ->join('e.materiel', 'm')
            ->leftJoin('e.ciblers', 'c')
            ->leftJoin('c.groupeMusculaire', 'g')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Parmi ces identifiants wger, ceux déjà présents dans le catalogue
    public function findIdsWgerImportes(array $idsWger): array
    {
        if (count($idsWger) === 0) {
            return [];
        }

        $exercices = $this->createQueryBuilder('e')
            ->where('e.idWger IN (:ids)')
            ->setParameter('ids', $idsWger)
            ->getQuery()
            ->getResult();

        $importes = [];
        foreach ($exercices as $exercice) {
            $importes[] = $exercice->getIdWger();
        }

        return $importes;
    }

    // Exercices sur lesquels le membre a enregistré au moins une série
    public function findPratiquesPar(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.series', 's')
            ->join('s.seance', 'se')
            ->where('se.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->groupBy('e.id')
            ->orderBy('e.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
