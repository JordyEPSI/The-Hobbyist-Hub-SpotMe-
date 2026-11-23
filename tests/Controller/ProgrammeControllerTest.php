<?php

namespace App\Tests\Controller;

use App\Entity\Commentaire;
use App\Entity\Exercice;
use App\Entity\Noter;
use App\Entity\Programme;
use App\Entity\Utilisateur;
use App\Tests\FabriqueDeDonnees;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Création de programmes (glisser-déposer), commentaires, notes et droits d'accès
class ProgrammeControllerTest extends WebTestCase
{
    use FabriqueDeDonnees;

    private KernelBrowser $client;
    private Utilisateur $auteur;
    private Exercice $squat;
    private Exercice $dips;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->viderLaBase();

        $this->auteur = $this->creerUtilisateur('auteur');
        $this->squat = $this->creerExercice('Squat', 'squat');
        $this->dips = $this->creerExercice('Dips', 'dips');
    }

    // Envoie la structure comme le ferait le JavaScript, avec un vrai jeton CSRF
    private function envoyerStructure(Programme $programme, array $jours): void
    {
        $crawler = $this->client->request('GET', '/mes-programmes/'.$programme->getSlug().'/construire');
        $jeton = $crawler->filter('input[name="_token"]')->attr('value');

        $this->client->request('POST', '/mes-programmes/'.$programme->getSlug().'/construire', [
            '_token' => $jeton,
            'jours' => $jours,
        ]);
    }

    private function relire(Programme $programme): ?Programme
    {
        $this->em->clear();

        return $this->em->find(Programme::class, $programme->getId());
    }

    public function testCreerUnProgrammePuisLeConstruire(): void
    {
        $this->client->loginUser($this->auteur);

        $this->client->request('GET', '/mes-programmes/nouveau');
        $this->client->submitForm('Continuer', [
            'programme[titre]' => 'Full body débutant',
            'programme[niveau]' => 'debutant',
        ]);
        self::assertResponseRedirects('/mes-programmes/full-body-debutant/construire');

        $programme = $this->em->getRepository(Programme::class)->findOneBy(['slug' => 'full-body-debutant']);
        $this->envoyerStructure($programme, [
            ['nom' => 'Jour A', 'exercices' => [
                ['exercice' => $this->squat->getId(), 'series' => 4, 'reps' => '6-8', 'repos' => 120],
                ['exercice' => $this->dips->getId(), 'series' => 3, 'reps' => '10', 'repos' => 90],
            ]],
            ['nom' => 'Jour B', 'exercices' => []],
        ]);

        self::assertResponseRedirects('/programmes/full-body-debutant');
        $programme = $this->relire($programme);
        self::assertCount(2, $programme->getJourProgrammes());
        $jourA = $programme->getJourProgrammes()->first();
        self::assertSame('Jour A', $jourA->getNom());
        self::assertSame('Squat', $jourA->getExerciceProgrammes()[0]->getExercice()->getNom());
        self::assertSame(1, $jourA->getExerciceProgrammes()[1]->getPosition());

        $this->client->request('GET', '/programmes/full-body-debutant');
        self::assertSelectorTextContains('.jour', 'Squat');
    }

    public function testReconstruireRemplaceLAncienneStructure(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->auteur);

        $this->envoyerStructure($programme, [['nom' => 'Push', 'exercices' => [
            ['exercice' => $this->dips->getId(), 'series' => 3, 'reps' => '10', 'repos' => 90],
        ]]]);
        $this->envoyerStructure($programme, [['nom' => 'Legs', 'exercices' => []]]);

        $programme = $this->relire($programme);
        self::assertCount(1, $programme->getJourProgrammes());
        self::assertSame('Legs', $programme->getJourProgrammes()->first()->getNom());
    }

    public function testStructureInvalideRefuseeSansRienEnregistrer(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->auteur);

        $cas = [
            'exercice inexistant' => [['nom' => 'A', 'exercices' => [['exercice' => 999999, 'series' => 3, 'reps' => '10', 'repos' => 60]]]],
            'séries hors limite' => [['nom' => 'A', 'exercices' => [['exercice' => $this->squat->getId(), 'series' => 50, 'reps' => '10', 'repos' => 60]]]],
            'nom de jour vide' => [['nom' => '', 'exercices' => []]],
            'données mal formées' => ['texte'],
            'trop de jours' => array_fill(0, 8, ['nom' => 'Jour', 'exercices' => []]),
            'tableau à la place d\'un texte' => [['nom' => ['x'], 'exercices' => []]],
            'tableau à la place d\'un nombre' => [['nom' => 'A', 'exercices' => [['exercice' => $this->squat->getId(), 'series' => ['3'], 'reps' => ['10'], 'repos' => 60]]]],
        ];

        foreach ($cas as $description => $jours) {
            $this->envoyerStructure($programme, $jours);
            self::assertResponseRedirects('/mes-programmes/ppl/construire', null, $description);
            self::assertCount(0, $this->relire($programme)->getJourProgrammes(), $description);
        }
    }

    public function testConstructionSansJetonCsrfRefusee(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->auteur);

        $this->client->request('POST', '/mes-programmes/ppl/construire', [
            '_token' => 'faux',
            'jours' => [['nom' => 'Pirate', 'exercices' => []]],
        ]);

        self::assertCount(0, $this->relire($programme)->getJourProgrammes());
    }

    public function testUnAutreMembreNePeutNiModifierNiSupprimer(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->creerUtilisateur('curieux'));

        foreach (['/mes-programmes/ppl/construire', '/mes-programmes/ppl/modifier'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }

        $this->client->request('POST', '/mes-programmes/ppl/supprimer', ['_token' => 'peu-importe']);
        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->relire($programme));
    }

    public function testUnVisiteurDoitSeConnecter(): void
    {
        $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);

        $this->client->request('GET', '/mes-programmes/nouveau');
        self::assertResponseRedirects('/connexion');

        $this->client->request('POST', '/programmes/ppl/commenter', ['commentaire' => ['contenu' => 'Spam']]);
        self::assertResponseRedirects('/connexion');
        self::assertSame(0, $this->em->getRepository(Commentaire::class)->count([]));
    }

    public function testLAuteurSupprimeSonProgramme(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->auteur);

        $this->client->request('GET', '/programmes/ppl');
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/mes-programmes');
        self::assertNull($this->relire($programme));
    }

    public function testCommenterUnProgramme(): void
    {
        $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->creerUtilisateur('lecteur'));

        $this->client->request('GET', '/programmes/ppl');
        $this->client->submitForm('Publier', ['commentaire[contenu]' => 'Super programme <b>merci</b>']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.flash-succes', 'Commentaire publié.');
        self::assertSelectorTextContains('.commentaire', '<b>merci</b>');
        self::assertSelectorNotExists('.commentaire b');
    }

    public function testCommentaireVideRefuse(): void
    {
        $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->creerUtilisateur('lecteur'));

        $this->client->request('GET', '/programmes/ppl');
        $this->client->submitForm('Publier', ['commentaire[contenu]' => '   ']);
        $this->client->followRedirect();

        self::assertSelectorExists('.flash-erreur');
        self::assertSame(0, $this->em->getRepository(Commentaire::class)->count([]));
    }

    public function testImpossibleDeCommenterUnProgrammePriveDUnAutre(): void
    {
        $this->creerProgramme($this->auteur, 'Secret', 'secret', false);
        $this->client->loginUser($this->creerUtilisateur('curieux'));

        $this->client->request('POST', '/programmes/secret/commenter', ['commentaire' => ['contenu' => 'Coucou']]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testNoterPuisChangerSaNote(): void
    {
        $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $this->client->loginUser($this->creerUtilisateur('lecteur'));

        $this->client->request('GET', '/programmes/ppl');
        $this->client->submitForm('Valider ma note', ['note[note]' => '4']);
        $this->client->followRedirect();
        $this->client->submitForm('Valider ma note', ['note[note]' => '2']);
        $this->client->followRedirect();

        $notes = $this->em->getRepository(Noter::class)->findAll();
        self::assertCount(1, $notes, 'Une seule note par membre');
        self::assertSame(2, $notes[0]->getNote());
        self::assertSelectorTextContains('.badges', '2,0 / 5');
    }

    public function testOnNeNotePasSonPropreProgrammeNiHorsLimite(): void
    {
        $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);

        $this->client->loginUser($this->auteur);
        $this->client->request('GET', '/programmes/ppl');
        self::assertSelectorNotExists('.form-note');
        $this->client->request('POST', '/programmes/ppl/noter', ['note' => ['note' => 5]]);

        $this->client->loginUser($this->creerUtilisateur('tricheur'));
        $this->client->request('POST', '/programmes/ppl/noter', ['note' => ['note' => 9]]);

        self::assertSame(0, $this->em->getRepository(Noter::class)->count([]));
    }

    public function testLAdminMasqueUnCommentaire(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'PPL', 'ppl', true);
        $commentaire = (new Commentaire())->setContenu('Message insultant')->setProgramme($programme)
            ->setUtilisateur($this->auteur)->setDateCreation(new \DateTimeImmutable())->setEstMasque(false);
        $this->em->persist($commentaire);
        $this->em->flush();

        $this->client->loginUser($this->creerUtilisateur('admin', true));
        $this->client->request('GET', '/admin/commentaires');
        $this->client->submitForm('Masquer');

        $this->client->request('GET', '/programmes/ppl');
        self::assertSelectorTextNotContains('body', 'Message insultant');
    }

    public function testLAdminSupprimeUnProgrammeDepuisLeBackOffice(): void
    {
        $programme = $this->creerProgramme($this->auteur, 'Secret', 'secret', false);
        $this->client->loginUser($this->creerUtilisateur('admin', true));

        $this->client->request('GET', '/admin/programmes');
        self::assertSelectorTextContains('.tableau', 'Secret');
        $this->client->submitForm('Supprimer');

        self::assertResponseRedirects('/admin/programmes');
        self::assertNull($this->relire($programme));
    }

    public function testModerationReserveeAuxAdmins(): void
    {
        $this->client->loginUser($this->creerUtilisateur('membre'));

        foreach (['/admin/programmes', '/admin/commentaires'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }
}
