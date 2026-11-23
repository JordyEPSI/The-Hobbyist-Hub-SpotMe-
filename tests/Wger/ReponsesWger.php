<?php

namespace App\Tests\Wger;

use App\Service\ImagesExercices;
use App\Wger\WgerClient;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

// Fausses réponses de l'API wger, pour tester sans réseau
final class ReponsesWger
{
    public static function developpeCouche(): array
    {
        return [
            'id' => 192,
            'muscles' => [['id' => 4], ['id' => 3]],
            'muscles_secondary' => [['id' => 2], ['id' => 5]],
            'equipment' => [['id' => 8], ['id' => 1]],
            'images' => [
                ['is_main' => false, 'image' => 'https://wger.de/media/b.png', 'thumbnails' => ['medium' => 'https://wger.de/media/b.png.400.png']],
                ['is_main' => true, 'image' => 'https://wger.de/media/a.png', 'thumbnails' => ['medium' => 'https://wger.de/media/a.png.400.png']],
            ],
            'translations' => [
                ['language' => 2, 'name' => 'Bench Press', 'description' => '<p>Lie on the bench.</p>'],
                ['language' => 12, 'name' => 'Développé couché', 'description' => '<p>Allongez-vous sur le banc.</p><ol><li>Descendez la barre</li><li>Poussez &amp; expirez</li></ol><script>alert(1)</script>'],
            ],
        ];
    }

    public static function crunchEnAnglais(): array
    {
        return [
            'id' => 91,
            'muscles' => [['id' => 6]],
            'muscles_secondary' => [['id' => 14]],
            'equipment' => [],
            'images' => [],
            'translations' => [
                ['language' => 2, 'name' => 'Crunches', 'description' => ''],
            ],
        ];
    }

    // Client wger branché sur un faux serveur ; chaque URL appelée est ajoutée à $urls
    public static function client(array &$urls = []): WgerClient
    {
        $serveur = function (string $methode, string $url) use (&$urls): MockResponse {
            $urls[] = $url;

            if (str_contains($url, 'exerciseinfo/192/')) {
                return new JsonMockResponse(self::developpeCouche());
            }
            if (str_contains($url, 'exerciseinfo/91/')) {
                return new JsonMockResponse(self::crunchEnAnglais());
            }
            if (str_contains($url, 'exerciseinfo/?')) {
                $exerciceEnAllemand = ['id' => 5, 'translations' => [['language' => 7, 'name' => 'Abdominal']]];

                return new JsonMockResponse(['results' => [self::crunchEnAnglais(), self::developpeCouche(), $exerciceEnAllemand]]);
            }

            return new JsonMockResponse(['detail' => 'Not found.'], ['http_code' => 404]);
        };

        return new WgerClient(new MockHttpClient($serveur), new ArrayAdapter());
    }

    // Une vraie image PNG de 600 x 400 px, fabriquée avec GD
    public static function imagePng(): string
    {
        $image = imagecreatetruecolor(600, 400);
        imagefill($image, 0, 0, imagecolorallocate($image, 234, 88, 12));
        ob_start();
        imagepng($image);

        return ob_get_clean();
    }

    // Service d'images branché sur un faux serveur qui renvoie toujours imagePng(),
    // et qui écrit dans un dossier temporaire au lieu de public/
    public static function images(string $dossierPublic): ImagesExercices
    {
        return new ImagesExercices(new MockHttpClient(new MockResponse(self::imagePng())), $dossierPublic);
    }
}
