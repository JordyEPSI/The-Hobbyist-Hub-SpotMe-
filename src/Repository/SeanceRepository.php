<?php

namespace App\Repository;

use App\Entity\Seance;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Seance>
 */
class SeanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seance::class);
    }

    // Séances d'un membre avec leurs séries, de la plus récente à la plus ancienne
    public function findJournal(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('se')
            ->addSelect('s', 'e')
            ->leftJoin('se.series', 's')
            ->leftJoin('s.exercice', 'e')
            ->where('se.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('se.dateSeance', 'DESC')
            ->addOrderBy('se.id', 'DESC')
            ->addOrderBy('s.numeroSerie', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
