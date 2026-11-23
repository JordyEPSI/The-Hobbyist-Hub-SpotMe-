<?php

namespace App\Repository;

use App\Entity\Exercice;
use App\Entity\Serie;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Serie>
 */
class SerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Serie::class);
    }

    // Une ligne par séance : charge max, 1RM estimé (formule d'Epley) et volume
    public function findProgression(Utilisateur $utilisateur, Exercice $exercice): array
    {
        $lignes = $this->createQueryBuilder('s')
            ->select(
                'se.dateSeance AS date',
                'MAX(s.chargeKg) AS chargeMax',
                'MAX(s.chargeKg * (1 + s.repetitions / 30.0)) AS unRmEstime',
                'SUM(s.chargeKg * s.repetitions) AS volume',
            )
            ->join('s.seance', 'se')
            ->where('se.utilisateur = :utilisateur')
            ->andWhere('s.exercice = :exercice')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('exercice', $exercice)
            ->groupBy('se.id', 'se.dateSeance')
            ->orderBy('se.dateSeance', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $points = [];
        foreach ($lignes as $ligne) {
            $points[] = [
                'date' => $ligne['date']->format('Y-m-d'),
                'chargeMax' => round($ligne['chargeMax'], 1),
                'unRmEstime' => round($ligne['unRmEstime'], 1),
                'volume' => round($ligne['volume'], 1),
            ];
        }

        return $points;
    }
}
