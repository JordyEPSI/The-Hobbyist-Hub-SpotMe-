<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Utilisateur;
use App\Tests\FabriqueDeDonnees;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UtilisateurAdminTest extends WebTestCase
{
    use FabriqueDeDonnees;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->viderLaBase();
    }

    private function creerCompte(string $pseudo, string $role): void
    {
        $this->client->request('GET', '/admin/utilisateurs/nouveau');
        $this->client->submitForm('Créer le compte', [
            'creation_compte[pseudo]' => $pseudo,
            'creation_compte[email]' => $pseudo.'@exemple.fr',
            'creation_compte[motDePasseClair][first]' => 'motdepasse123',
            'creation_compte[motDePasseClair][second]' => 'motdepasse123',
            'creation_compte[role]' => $role,
        ]);
    }

    public function testUnAdminCreeUnMembre(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin', true));

        $this->creerCompte('lucas', 'membre');

        self::assertResponseRedirects('/admin/utilisateurs');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-succes', 'Compte de lucas créé.');

        $membre = $this->em->getRepository(Utilisateur::class)->findOneBy(['pseudo' => 'lucas']);
        self::assertSame(['ROLE_USER'], $membre->getRoles());
        self::assertNotSame('motdepasse123', $membre->getPassword());
    }

    public function testUnAdminCreeUnAutreAdmin(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin', true));

        $this->creerCompte('chef', 'admin');

        $nouveau = $this->em->getRepository(Utilisateur::class)->findOneBy(['pseudo' => 'chef']);
        self::assertTrue($nouveau->isAdmin());
    }

    public function testPseudoDejaPrisRefuse(): void
    {
        $this->client->loginUser($this->creerUtilisateur('admin', true));

        $this->creerCompte('admin', 'membre');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Ce pseudo est déjà pris.');
    }

    public function testUnMembreNePeutPasCreerDeCompte(): void
    {
        $this->client->loginUser($this->creerUtilisateur('membre'));

        $this->client->request('GET', '/admin/utilisateurs/nouveau');

        self::assertResponseStatusCodeSame(403);
    }
}
