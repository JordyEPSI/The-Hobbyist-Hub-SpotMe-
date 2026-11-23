<?php

namespace App\Tests\Wger;

use App\Wger\WgerClient;
use App\Wger\WgerIndisponibleException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class WgerClientTest extends TestCase
{
    public function testRecupererConvertitUnExerciceEnFrancais(): void
    {
        $exercice = ReponsesWger::client()->recuperer(192);

        self::assertNotNull($exercice);
        self::assertSame(192, $exercice->idWger);
        self::assertSame('Développé couché', $exercice->nom, 'La traduction française est préférée');
        self::assertTrue($exercice->estEnFrancais);
        self::assertSame('barre', $exercice->materiel, 'Le banc passe après la barre');
        self::assertSame(['pectoraux'], $exercice->musclesPrincipaux, 'Grand pectoral et dentelé tombent dans le même groupe');
        self::assertSame(['epaules', 'triceps'], $exercice->musclesSecondaires);
        self::assertSame('https://wger.de/media/a.png.400.png', $exercice->image, 'Miniature de l\'image principale');
    }

    public function testLaDescriptionNeGardeQueLeTexte(): void
    {
        $description = ReponsesWger::client()->recuperer(192)->description;

        self::assertStringNotContainsString('<', $description);
        self::assertSame("Allongez-vous sur le banc.\nDescendez la barre\nPoussez & expirez\nalert(1)", $description);
    }

    public function testExerciceSansTraductionFrancaiseNiMateriel(): void
    {
        $exercice = ReponsesWger::client()->recuperer(91);

        self::assertSame('Crunches', $exercice->nom);
        self::assertFalse($exercice->estEnFrancais);
        self::assertSame('poids-du-corps', $exercice->materiel);
        self::assertSame(['abdominaux'], $exercice->musclesSecondaires);
        self::assertNull($exercice->image);
        self::assertNull($exercice->description);
    }

    public function testRechercheTrieLeFrancaisEnPremierEtIgnoreLesAutresLangues(): void
    {
        $urls = [];
        $resultats = ReponsesWger::client($urls)->rechercher(6, 7);

        self::assertSame(['Développé couché', 'Crunches'], array_map(static fn ($e) => $e->nom, $resultats));
        self::assertStringContainsString('muscles=6', $urls[0]);
        self::assertStringContainsString('equipment=7', $urls[0]);
    }

    public function testLesReponsesSontMisesEnCache(): void
    {
        $urls = [];
        $client = ReponsesWger::client($urls);
        $client->recuperer(192);
        $client->recuperer(192);

        self::assertCount(1, $urls, 'Le deuxième appel ne doit pas repartir sur le réseau');
    }

    public function testExerciceIntrouvable(): void
    {
        self::assertNull(ReponsesWger::client()->recuperer(999999));
    }

    public function testWgerInjoignable(): void
    {
        $client = new WgerClient(new MockHttpClient(new MockResponse('', ['error' => 'Connexion refusée'])), new ArrayAdapter());

        $this->expectException(WgerIndisponibleException::class);
        $client->rechercher(4);
    }
}
