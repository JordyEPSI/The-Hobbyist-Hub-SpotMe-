<?php

namespace App\Repository;

use App\Entity\Programme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Programme>
 */
class ProgrammeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Programme::class);
    }

    // Programmes publics, du plus récent au plus ancien
    public function findPublics(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u', 'j', 'n')
            ->join('p.utilisateur', 'u')
            ->leftJoin('p.jourProgrammes', 'j')
            ->leftJoin('p.notes', 'n')
            ->where('p.estPublic = true')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Tous les programmes avec leur auteur, du plus récent au plus ancien (page de modération)
    public function findTousAvecAuteur(): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('u')
            ->join('p.utilisateur', 'u')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Un programme par son slug, avec ses jours et ses exercices dans l'ordre
    public function findUnAvecDetails(string $slug): ?Programme
    {
        // Les commentaires et leurs auteurs sont chargés dans la même requête :
        // sinon Doctrine ferait une requête par commentaire pour afficher le pseudo (problème « N+1 »)
        return $this->createQueryBuilder('p')
            ->addSelect('u', 'j', 'ep', 'e', 'co', 'cu')
            ->join('p.utilisateur', 'u')
            ->leftJoin('p.jourProgrammes', 'j')
            ->leftJoin('j.exerciceProgrammes', 'ep')
            ->leftJoin('ep.exercice', 'e')
            ->leftJoin('p.commentaires', 'co')
            ->leftJoin('co.utilisateur', 'cu')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('j.position', 'ASC')
            ->addOrderBy('ep.position', 'ASC')
            ->addOrderBy('co.dateCreation', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
