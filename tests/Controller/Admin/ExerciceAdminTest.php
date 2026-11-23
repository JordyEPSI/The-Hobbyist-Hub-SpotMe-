<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Exercice;
use App\Entity\GroupeMusculaire;
use App\Service\ImagesExercices;
use App\Tests\FabriqueDeDonnees;
use App\Tests\Wger\ReponsesWger;
use App\Wger\WgerClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExerciceAdminTest extends WebTestCase
{
    use FabriqueDeDonnees;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->viderLaBase();
    }

    private function connecterAdmin(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin', true));
    }

    private function idGroupe(string $slug): string
    {
        return (string) $this->em->getRepository(GroupeMusculaire::class)->findOneBy(['slug' => $slug])->getId();
    }

    public function testReserveAuxAdministrateurs(): void
    {
        $this->client->loginUser($this->creerUtilisateur('membre'));

        foreach (['/admin/exercices', '/admin/exercices/nouveau', '/admin/wger'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }

    public function testCreerPuisModifierUnExercice(): void
    {
        $this->connecterAdmin();

        $crawler = $this->client->request('GET', '/admin/exercices/nouveau');
        $materiel = $crawler->filter('#exercice_materiel option:contains("Haltères")')->attr('value');
        $form = $crawler->selectButton('Enregistrer')->form([
            'exercice[nom]' => 'Curl marteau',
            'exercice[materiel]' => $materiel,
            'exercice[difficulte]' => 'debutant',
        ]);
        $valeurs = $form->getPhpValues();
        $valeurs['exercice']['musclesPrincipaux'] = [$this->idGroupe('biceps')];
        $this->client->request('POST', $form->getUri(), $valeurs);

        self::assertResponseRedirects('/admin/exercices');
        $exercice = $this->em->getRepository(Exercice::class)->findOneBy(['slug' => 'curl-marteau']);
        self::assertNotNull($exercice);
        self::assertSame('Haltères', $exercice->getMateriel()->getNom());

        // Modification : la case du muscle déjà choisi est pré-cochée
        $crawler = $this->client->request('GET', '/admin/exercices/'.$exercice->getId().'/modifier');
        self::assertSelectorExists('input[name="exercice[musclesPrincipaux][]"][value="'.$this->idGroupe('biceps').'"][checked]');
        $this->client->submit($crawler->selectButton('Enregistrer')->form(['exercice[nom]' => 'Curl marteau assis']));

        self::assertResponseRedirects('/admin/exercices');
        $this->em->clear();
        $exercice = $this->em->find(Exercice::class, $exercice->getId());
        self::assertSame('Curl marteau assis', $exercice->getNom());
        self::assertSame('curl-marteau-assis', $exercice->getSlug());
        self::assertCount(1, $exercice->getCiblers());
    }

    public function testUnExerciceSansMusclePrincipalEstRefuse(): void
    {
        $this->connecterAdmin();

        $crawler = $this->client->request('GET', '/admin/exercices/nouveau');
        $this->client->submit($crawler->selectButton('Enregistrer')->form([
            'exercice[nom]' => 'Mystère',
            'exercice[materiel]' => $crawler->filter('#exercice_materiel option')->eq(1)->attr('value'),
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Choisis au moins un muscle principal.');
    }

    public function testSupprimerUnExerciceInutilise(): void
    {
        $this->connecterAdmin();
        $exercice = $this->creerExercice('Dips', 'dips');

        $this->client->request('GET', '/admin/exercices');
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/admin/exercices');
        $this->em->clear();
        self::assertNull($this->em->find(Exercice::class, $exercice->getId()));
    }

    public function testUnExerciceUtiliseDansUneSeanceNEstPasSupprime(): void
    {
        $exercice = $this->creerExercice('Dips', 'dips');
        $this->creerSeance($this->creerUtilisateur('membre'), $exercice, '2026-11-20', [[10, '0']]);
        $this->connecterAdmin();

        $this->client->request('GET', '/admin/exercices');
        $this->client->submitForm('Supprimer');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.flash-erreur', 'ne peut pas être supprimé');
        $this->em->clear();
        self::assertNotNull($this->em->find(Exercice::class, $exercice->getId()));
    }

    public function testRechercherEtImporterDepuisWger(): void
    {
        // Faux client wger (pas de réseau) ; le noyau ne doit pas redémarrer entre deux requêtes pour le garder
        $this->client->disableReboot();
        static::getContainer()->set(WgerClient::class, ReponsesWger::client());
        static::getContainer()->set(ImagesExercices::class, ReponsesWger::images(sys_get_temp_dir().'/spotme-tests'));
        $this->connecterAdmin();

        $crawler = $this->client->request('GET', '/admin/wger?groupe=pectoraux');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.tableau', 'Développé couché');
        self::assertSelectorTextContains('.tableau', 'Crunches');

        $form = $crawler->selectButton('Importer la sélection')->form();
        $form['ids'][0]->tick(); // Développé couché (id wger 192)
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/wger?groupe=pectoraux&materiel=');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-succes', '1 exercice(s) importé(s)');
        self::assertSelectorTextContains('.tableau', 'déjà importé');
        self::assertNotNull($this->em->getRepository(Exercice::class)->findOneBy(['idWger' => 192]));
    }

    public function testImportWgerSansJetonCsrfRefuse(): void
    {
        // Faux client wger (pas de réseau) ; le noyau ne doit pas redémarrer entre deux requêtes pour le garder
        $this->client->disableReboot();
        static::getContainer()->set(WgerClient::class, ReponsesWger::client());
        $this->connecterAdmin();

        $this->client->request('POST', '/admin/wger/importer', ['_token' => 'faux', 'ids' => [192]]);

        self::assertSame(0, $this->em->getRepository(Exercice::class)->count([]));
    }
}
