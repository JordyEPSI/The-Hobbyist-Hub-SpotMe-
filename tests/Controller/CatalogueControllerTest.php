<?php

namespace App\Tests\Controller;

use App\Entity\ExerciceProgramme;
use App\Entity\JourProgramme;
use App\Tests\FabriqueDeDonnees;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CatalogueControllerTest extends WebTestCase
{
    use FabriqueDeDonnees;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->viderLaBase();
    }

    public function testCatalogueVisibleSansCompte(): void
    {
        $this->creerExercice('Développé couché', 'developpe-couche', $this->creerGroupe());

        $this->client->request('GET', '/exercices');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.grille', 'Développé couché');
        self::assertSelectorTextContains('.badge-principal', 'Pectoraux');
    }

    public function testFicheExercice(): void
    {
        $exercice = $this->creerExercice('Développé couché', 'developpe-couche', $this->creerGroupe());
        $exercice->setDescription("Allongé sur le banc.\nDescendre la barre <b>lentement</b>.");
        $this->em->flush();

        $this->client->request('GET', '/exercices/developpe-couche');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Développé couché');
        self::assertSelectorTextContains('.infos', 'Barre');
        // Le texte est échappé : pas de HTML injecté depuis la description
        self::assertSelectorNotExists('.description b');
        self::assertSelectorTextContains('.description', '<b>lentement</b>');
    }

    public function testFicheExerciceInconnueRenvoie404(): void
    {
        $this->client->request('GET', '/exercices/n-existe-pas');

        self::assertResponseStatusCodeSame(404);
    }

    public function testSeulsLesProgrammesPublicsSontListes(): void
    {
        $auteur = $this->creerUtilisateur('auteur');
        $this->creerProgramme($auteur, 'Full body débutant', 'full-body-debutant', true);
        $this->creerProgramme($auteur, 'Mon programme secret', 'secret', false);

        $this->client->request('GET', '/programmes');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.grille', 'Full body débutant');
        self::assertSelectorTextNotContains('.grille', 'Mon programme secret');
    }

    public function testFicheProgrammeAvecSesJours(): void
    {
        $programme = $this->creerProgramme($this->creerUtilisateur('auteur'), 'PPL', 'ppl', true);
        $jour = (new JourProgramme())->setNom('Push')->setPosition(0)->setProgramme($programme);
        $ligne = (new ExerciceProgramme())->setPosition(0)->setSeriesCibles(4)->setRepsCibles('8-10')->setReposSecondes(120)
            ->setExercice($this->creerExercice('Dips', 'dips'))->setJourProgramme($jour);
        $this->em->persist($jour);
        $this->em->persist($ligne);
        $this->em->flush();
        $this->em->clear(); // la page doit relire le programme et ses jours depuis la base

        $this->client->request('GET', '/programmes/ppl');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.jour h2', 'Push');
        self::assertSelectorTextContains('.jour', 'Dips');
        self::assertSelectorTextContains('.jour', '8-10');
    }

    public function testProgrammePriveReserveASonAuteur(): void
    {
        $auteur = $this->creerUtilisateur('auteur');
        $this->creerProgramme($auteur, 'Secret', 'secret', false);

        $this->client->request('GET', '/programmes/secret');
        self::assertResponseRedirects('/connexion', null, 'Un visiteur doit se connecter');

        $this->client->loginUser($this->creerUtilisateur('curieux'));
        $this->client->request('GET', '/programmes/secret');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($auteur);
        $this->client->request('GET', '/programmes/secret');
        self::assertResponseIsSuccessful();
    }

    public function testRechercheEtFiltresDuCatalogue(): void
    {
        $pecs = $this->creerGroupe('Pectoraux', 'pectoraux');
        $quads = $this->creerGroupe('Quadriceps', 'quadriceps');
        $this->creerExercice('Développé couché', 'developpe-couche', $pecs);
        $this->creerExercice('Squat', 'squat', $quads);
        $pompes = $this->creerExercice('Pompes', 'pompes', $pecs);
        $pompes->setMateriel($this->creerMateriel('Poids du corps', 'poids-du-corps'));
        $this->em->flush();

        $this->client->request('GET', '/exercices?q=squ');
        self::assertSelectorCount(1, '.grille > li');
        self::assertSelectorTextContains('.grille', 'Squat');

        $this->client->request('GET', '/exercices?groupe=pectoraux');
        self::assertSelectorCount(2, '.grille > li');
        self::assertSelectorTextNotContains('.grille', 'Squat');

        $this->client->request('GET', '/exercices?groupe=pectoraux&materiel=poids-du-corps');
        self::assertSelectorCount(1, '.grille > li');
        self::assertSelectorTextContains('.grille', 'Pompes');

        $this->client->request('GET', '/exercices?q=introuvable');
        self::assertSelectorTextContains('.vide', 'Aucun exercice');
    }
}
