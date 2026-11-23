<?php

namespace App\Tests\Controller;

use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Utilisateur;
use App\Tests\FabriqueDeDonnees;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SeanceControllerTest extends WebTestCase
{
    use FabriqueDeDonnees;

    private KernelBrowser $client;
    private Utilisateur $membre;
    private Exercice $developpe;
    private Exercice $squat;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->viderLaBase();

        $this->membre = $this->creerUtilisateur('membre');
        $this->developpe = $this->creerExercice('Développé couché', 'developpe-couche');
        $this->squat = $this->creerExercice('Squat', 'squat');
    }

    private function soumettreSeance(string $url, array $series): void
    {
        $crawler = $this->client->request('GET', $url);
        $form = $crawler->selectButton('Enregistrer la séance')->form();

        $valeurs = $form->getPhpValues();
        $valeurs['seance']['dateSeance'] = '2026-11-20';
        $valeurs['seance']['dureeMin'] = '75';
        $valeurs['seance']['ressenti'] = 'difficile';
        $valeurs['seance']['series'] = $series;

        $this->client->request('POST', $form->getUri(), $valeurs);
    }

    public function testJournalReserveAuxMembres(): void
    {
        $this->client->request('GET', '/seances');
        self::assertResponseRedirects('/connexion');

        $this->client->request('GET', '/progression');
        self::assertResponseRedirects('/connexion');
    }

    public function testCreerUneSeanceAvecPlusieursSeries(): void
    {
        $this->client->loginUser($this->membre);

        $this->soumettreSeance('/seances/nouvelle', [
            ['exercice' => $this->developpe->getId(), 'repetitions' => 10, 'chargeKg' => '60'],
            ['exercice' => $this->developpe->getId(), 'repetitions' => 8, 'chargeKg' => '62.5'],
            ['exercice' => $this->squat->getId(), 'repetitions' => 5, 'chargeKg' => '100'],
        ]);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-succes', 'Séance enregistrée');
        self::assertSelectorTextContains('h1', 'Séance du 20/11/2026');

        $this->em->clear();
        $seance = $this->em->getRepository(Seance::class)->findOneBy(['utilisateur' => $this->membre->getId()]);
        self::assertCount(3, $seance->getSeries());
        // Les séries sont numérotées par exercice : 1, 2 pour le développé, 1 pour le squat
        $numeros = $seance->getSeries()->map(fn ($s) => $s->getExercice()->getNom().' '.$s->getNumeroSerie())->getValues();
        sort($numeros);
        self::assertSame(['Développé couché 1', 'Développé couché 2', 'Squat 1'], $numeros);
        self::assertSame(1600.0, $seance->getVolumeTotal());
    }

    public function testSeanceSansSerieRefusee(): void
    {
        $this->client->loginUser($this->membre);

        $this->soumettreSeance('/seances/nouvelle', []);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Ajoute au moins une série.');
    }

    public function testSerieInvalideRefusee(): void
    {
        $this->client->loginUser($this->membre);

        $this->soumettreSeance('/seances/nouvelle', [
            ['exercice' => $this->developpe->getId(), 'repetitions' => 0, 'chargeKg' => '-5'],
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em->getRepository(Seance::class)->count([]));
    }

    public function testModifierUneSeanceRemplaceLesSeries(): void
    {
        $seance = $this->creerSeance($this->membre, $this->developpe, '2026-11-18', [[10, '60'], [10, '60']]);
        $this->client->loginUser($this->membre);

        $this->soumettreSeance('/seances/'.$seance->getId().'/modifier', [
            ['exercice' => $this->squat->getId(), 'repetitions' => 5, 'chargeKg' => '110'],
        ]);

        self::assertResponseRedirects('/seances/'.$seance->getId());
        $this->em->clear();
        $seance = $this->em->find(Seance::class, $seance->getId());
        self::assertCount(1, $seance->getSeries());
        self::assertSame('Squat', $seance->getSeries()->first()->getExercice()->getNom());
    }

    public function testUnMembreNeVoitPasLesSeancesDUnAutre(): void
    {
        $seance = $this->creerSeance($this->membre, $this->developpe, '2026-11-18', [[10, '60']]);
        $this->client->loginUser($this->creerUtilisateur('curieux'));

        $this->client->request('GET', '/seances/'.$seance->getId());
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/seances/'.$seance->getId().'/modifier');
        self::assertResponseStatusCodeSame(403);

        $this->client->request('GET', '/seances');
        self::assertSelectorTextContains('.vide', 'Ton journal est vide');
    }

    public function testSupprimerUneSeance(): void
    {
        $seance = $this->creerSeance($this->membre, $this->developpe, '2026-11-18', [[10, '60']]);
        $this->client->loginUser($this->membre);

        $this->client->request('GET', '/seances/'.$seance->getId());
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/seances');
        self::assertSame(0, $this->em->getRepository(Seance::class)->count([]));
    }

    public function testDonneesDeProgressionEnJson(): void
    {
        $this->creerSeance($this->membre, $this->developpe, '2026-11-10', [[10, '60'], [8, '65']]);
        $this->creerSeance($this->membre, $this->developpe, '2026-11-17', [[10, '70']]);
        // Les séries d'un autre membre ne doivent jamais apparaître
        $this->creerSeance($this->creerUtilisateur('autre'), $this->developpe, '2026-11-12', [[1, '200']]);

        $this->client->loginUser($this->membre);
        $this->client->request('GET', '/progression/donnees/'.$this->developpe->getId());

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        $donnees = json_decode($this->client->getResponse()->getContent(), true);

        self::assertSame('Développé couché', $donnees['exercice']);
        self::assertCount(2, $donnees['points']);
        // JSON ne distingue pas 65 et 65.0 : comparaison souple sur les nombres
        self::assertEquals(['date' => '2026-11-10', 'chargeMax' => 65, 'unRmEstime' => 82.3, 'volume' => 1120], $donnees['points'][0]);
        self::assertSame('2026-11-17', $donnees['points'][1]['date']);
        self::assertEquals(70, $donnees['points'][1]['chargeMax']);
    }

    public function testPageProgressionListeLesExercicesPratiques(): void
    {
        $this->creerSeance($this->membre, $this->squat, '2026-11-10', [[5, '100']]);
        $this->client->loginUser($this->membre);

        $this->client->request('GET', '/progression');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-controller="progression"]');
        self::assertSelectorTextContains('#choix-exercice', 'Squat');
        self::assertSelectorTextNotContains('#choix-exercice', 'Développé couché');
    }

    public function testPlusDe50SeriesRefusees(): void
    {
        $this->client->loginUser($this->membre);

        $series = [];
        for ($i = 0; $i < 51; $i++) {
            $series[] = ['exercice' => $this->squat->getId(), 'repetitions' => 5, 'chargeKg' => '100'];
        }
        $this->soumettreSeance('/seances/nouvelle', $series);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', '50 séries maximum par séance.');
        self::assertSame(0, $this->em->getRepository(Seance::class)->count([]));
    }
}
