<?php

namespace App\Tests\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecuriteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Base de test vide au début de chaque test
        $this->em->createQuery('DELETE FROM '.Utilisateur::class)->execute();
    }

    private function creerUtilisateur(string $email, string $pseudo, bool $admin = false): Utilisateur
    {
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $utilisateur = (new Utilisateur())->setEmail($email)->setPseudo($pseudo);
        $utilisateur->setMotDePasse($hasher->hashPassword($utilisateur, 'motdepasse123'));
        if ($admin) {
            $utilisateur->setRoles(['ROLE_ADMIN']);
        }

        $this->em->persist($utilisateur);
        $this->em->flush();

        return $utilisateur;
    }

    public function testPageConnexionAccessible(): void
    {
        $this->client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Connexion');
    }

    public function testInscriptionCreeUnCompteAvecMotDePasseHache(): void
    {
        $this->client->request('GET', '/inscription');
        $this->client->submitForm("S'inscrire", [
            'inscription[pseudo]' => 'jordy_lift',
            'inscription[email]' => 'Jordy@Exemple.fr',
            'inscription[motDePasseClair][first]' => 'motdepasse123',
            'inscription[motDePasseClair][second]' => 'motdepasse123',
        ]);

        self::assertResponseRedirects('/connexion');

        $utilisateur = static::getContainer()->get(UtilisateurRepository::class)->findOneBy(['pseudo' => 'jordy_lift']);
        self::assertNotNull($utilisateur);
        self::assertSame('jordy@exemple.fr', $utilisateur->getEmail(), 'L\'email est enregistré en minuscules');
        self::assertNotSame('motdepasse123', $utilisateur->getPassword(), 'Le mot de passe ne doit pas être stocké en clair');
        self::assertSame(['ROLE_USER'], $utilisateur->getRoles());
    }

    public function testInscriptionRefuseUnEmailDejaUtilise(): void
    {
        $this->creerUtilisateur('deja@exemple.fr', 'deja');

        $this->client->request('GET', '/inscription');
        $this->client->submitForm("S'inscrire", [
            'inscription[pseudo]' => 'nouveau',
            'inscription[email]' => 'deja@exemple.fr',
            'inscription[motDePasseClair][first]' => 'motdepasse123',
            'inscription[motDePasseClair][second]' => 'motdepasse123',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Un compte existe déjà avec cet email.');
    }

    public function testInscriptionRefuseDesMotsDePasseDifferents(): void
    {
        $this->client->request('GET', '/inscription');
        $this->client->submitForm("S'inscrire", [
            'inscription[pseudo]' => 'nouveau',
            'inscription[email]' => 'nouveau@exemple.fr',
            'inscription[motDePasseClair][first]' => 'motdepasse123',
            'inscription[motDePasseClair][second]' => 'autrechose123',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Les deux mots de passe ne correspondent pas.');
    }

    public function testConnexionAvecLeFormulaire(): void
    {
        $this->creerUtilisateur('membre@exemple.fr', 'membre');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            'email' => 'membre@exemple.fr',
            'mot_de_passe' => 'motdepasse123',
        ]);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.nav-pseudo', 'membre');
    }

    public function testConnexionRefuseUnMauvaisMotDePasse(): void
    {
        $this->creerUtilisateur('membre@exemple.fr', 'membre');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            'email' => 'membre@exemple.fr',
            'mot_de_passe' => 'mauvais',
        ]);

        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();
        self::assertSelectorExists('.flash-erreur');
    }

    public function testAdminRedirigeUnVisiteurVersLaConnexion(): void
    {
        $this->client->request('GET', '/admin');

        self::assertResponseRedirects('/connexion');
    }

    public function testAdminInterditAUnMembre(): void
    {
        $this->client->loginUser($this->creerUtilisateur('membre@exemple.fr', 'membre'));
        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminAccessibleAUnAdministrateur(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', 'admin', true));

        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/admin/utilisateurs');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'admin@exemple.fr');
    }

    public function testAdminPeutPromouvoirUnMembre(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', 'admin', true));
        $membre = $this->creerUtilisateur('membre@exemple.fr', 'membre');

        $this->client->request('GET', '/admin/utilisateurs');
        $this->client->submitForm('Passer admin');

        self::assertResponseRedirects('/admin/utilisateurs');
        $this->em->clear();
        self::assertTrue($this->em->find(Utilisateur::class, $membre->getId())->isAdmin());
    }

    public function testActionAdminRefuseeSansJetonCsrf(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin@exemple.fr', 'admin', true));
        $membre = $this->creerUtilisateur('membre@exemple.fr', 'membre');

        $this->client->request('POST', '/admin/utilisateurs/'.$membre->getId().'/supprimer', ['_token' => 'faux']);

        $this->em->clear();
        self::assertNotNull($this->em->find(Utilisateur::class, $membre->getId()), 'Le compte ne doit pas être supprimé');
    }
}
