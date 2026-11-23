<?php

namespace App\Repository;

use App\Entity\Commentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commentaire>
 */
class CommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commentaire::class);
    }

    // Tous les commentaires avec leur auteur et leur programme, en une seule requête (page de modération)
    public function findTousAvecDetails(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('u', 'p')
            ->join('c.utilisateur', 'u')
            ->join('c.programme', 'p')
            ->orderBy('c.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
