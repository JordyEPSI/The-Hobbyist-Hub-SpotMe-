<?php

namespace App\Tests\Service;

use App\Entity\Exercice;
use App\Entity\GroupeMusculaire;
use App\Entity\Materiel;
use App\Service\CatalogueExercices;
use App\Service\ImagesExercices;
use App\Tests\FabriqueDeDonnees;
use App\Tests\Wger\ReponsesWger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CatalogueExercicesTest extends KernelTestCase
{
    use FabriqueDeDonnees;

    private CatalogueExercices $catalogue;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->viderLaBase();
        // Pas de vrai téléchargement d'image pendant les tests
        static::getContainer()->set(ImagesExercices::class, ReponsesWger::images(sys_get_temp_dir().'/spotme-tests'));
        $this->catalogue = static::getContainer()->get(CatalogueExercices::class);
    }

    public function testImporterUnExerciceWger(): void
    {
        $exercice = $this->catalogue->importer(ReponsesWger::client()->recuperer(192));

        $this->em->clear();
        $exercice = $this->em->find(Exercice::class, $exercice->getId());

        self::assertSame('Développé couché', $exercice->getNom());
        self::assertSame('developpe-couche', $exercice->getSlug());
        self::assertSame(192, $exercice->getIdWger());
        self::assertSame('Barre', $exercice->getMateriel()->getNom());
        self::assertSame('/images/exercices/developpe-couche.webp', $exercice->getImage(), 'Image copiée en local');

        $muscles = [];
        foreach ($exercice->getCiblers() as $cibler) {
            $muscles[$cibler->getGroupeMusculaire()->getNom()] = $cibler->isEstPrincipal();
        }
        ksort($muscles);
        self::assertSame(['Pectoraux' => true, 'Triceps' => false, 'Épaules' => false], $muscles);
    }

    public function testUnExerciceNEstImporteQuUneFois(): void
    {
        $exerciceWger = ReponsesWger::client()->recuperer(192);

        self::assertNotNull($this->catalogue->importer($exerciceWger));
        self::assertNull($this->catalogue->importer($exerciceWger));
        self::assertSame(1, $this->em->getRepository(Exercice::class)->count([]));
    }

    public function testDeuxExercicesDeMemeNomOntDesSlugsDifferents(): void
    {
        $materiel = $this->creerMateriel();
        $premier = (new Exercice())->setNom('Squat')->setMateriel($materiel);
        $second = (new Exercice())->setNom('Squat')->setMateriel($materiel);

        $this->catalogue->enregistrer($premier);
        $this->catalogue->enregistrer($second);

        self::assertSame('squat', $premier->getSlug());
        self::assertSame('squat-2', $second->getSlug());
    }

    public function testModifierLesMusclesSansDoublon(): void
    {
        $pecs = $this->creerGroupe('Pectoraux', 'pectoraux');
        $triceps = $this->creerGroupe('Triceps', 'triceps');
        $exercice = (new Exercice())->setNom('Dips')->setMateriel($this->creerMateriel());
        $this->catalogue->enregistrer($exercice, [$pecs], [$triceps]);

        // On inverse principal / secondaire : les liens existants sont mis à jour, pas recréés
        $this->catalogue->enregistrer($exercice, [$triceps], [$pecs]);

        $this->em->clear();
        $exercice = $this->em->find(Exercice::class, $exercice->getId());
        $principaux = $exercice->getCiblers()->filter(fn ($c) => $c->isEstPrincipal())->map(fn ($c) => $c->getGroupeMusculaire()->getNom());
        self::assertCount(2, $exercice->getCiblers());
        self::assertSame(['Triceps'], $principaux->getValues());
    }

    public function testInitialiserLeReferentiel(): void
    {
        self::assertSame(23, $this->catalogue->initialiserReferentiel());
        self::assertSame(0, $this->catalogue->initialiserReferentiel(), 'Rien n\'est recréé la deuxième fois');
        self::assertSame(12, $this->em->getRepository(Materiel::class)->count([]));
        self::assertSame(11, $this->em->getRepository(GroupeMusculaire::class)->count([]));
    }
}
