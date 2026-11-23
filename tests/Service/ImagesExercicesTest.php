<?php

namespace App\Tests\Service;

use App\Entity\Exercice;
use App\Service\ImagesExercices;
use App\Tests\Wger\ReponsesWger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ImagesExercicesTest extends TestCase
{
    private string $dossier;

    protected function setUp(): void
    {
        $this->dossier = sys_get_temp_dir().'/spotme-tests-images';
    }

    private function exercice(?string $image): Exercice
    {
        $exercice = new Exercice();
        $exercice->setSlug('squat');
        $exercice->setImage($image);

        return $exercice;
    }

    public function testImageCopieeReduiteEtConvertieEnWebp(): void
    {
        $exercice = $this->exercice('https://wger.de/media/squat.png');

        self::assertTrue(ReponsesWger::images($this->dossier)->copierEnLocal($exercice));

        self::assertSame('/images/exercices/squat.webp', $exercice->getImage());
        $fichier = $this->dossier.'/images/exercices/squat.webp';
        $infos = getimagesize($fichier);
        self::assertSame('image/webp', $infos['mime']);
        self::assertSame(320, $infos[0], 'Largeur réduite à 320 px');
        self::assertSame(213, $infos[1], 'Proportions gardées');
        self::assertLessThan(strlen(ReponsesWger::imagePng()), filesize($fichier), 'Plus léger que l\'original');
    }

    public function testImageDejaLocaleOuAbsenteNonTouchee(): void
    {
        $images = ReponsesWger::images($this->dossier);

        $locale = $this->exercice('/images/exercices/squat.webp');
        self::assertFalse($images->copierEnLocal($locale));
        self::assertSame('/images/exercices/squat.webp', $locale->getImage());

        self::assertFalse($images->copierEnLocal($this->exercice(null)));
    }

    public function testEchecDeTelechargementGardeLImageDistante(): void
    {
        $images = new ImagesExercices(new MockHttpClient(new MockResponse('', ['http_code' => 404])), $this->dossier);
        $exercice = $this->exercice('https://wger.de/media/absente.png');

        self::assertFalse($images->copierEnLocal($exercice));
        self::assertSame('https://wger.de/media/absente.png', $exercice->getImage());
    }

    public function testFichierQuiNEstPasUneImage(): void
    {
        $images = new ImagesExercices(new MockHttpClient(new MockResponse('<html>pas une image</html>')), $this->dossier);

        self::assertFalse($images->copierEnLocal($this->exercice('https://wger.de/media/faux.png')));
    }
}
