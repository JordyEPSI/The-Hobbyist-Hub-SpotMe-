<?php

namespace App\Service;

use App\Entity\Exercice;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// Éco-conception : on copie les images des exercices sur notre serveur, réduites et en WebP.
// Avantages : images bien plus légères, et aucune requête des visiteurs vers un site tiers.
class ImagesExercices
{
    private const LARGEUR_MAX = 320;
    private const QUALITE_WEBP = 80;
    private const DOSSIER_PUBLIC = '/images/exercices/';

    public function __construct(
        private readonly HttpClientInterface $client,
        #[Autowire('%kernel.project_dir%/public')]
        private readonly string $dossierPublic,
    ) {
    }

    // Copie l'image distante de l'exercice en local et met à jour son adresse.
    // Retourne false si ce n'est pas possible : l'exercice garde alors son image distante.
    public function copierEnLocal(Exercice $exercice): bool
    {
        $url = $exercice->getImage();
        if ($url === null || !str_starts_with($url, 'https://')) {
            return false;
        }

        // 1. Téléchargement de l'image d'origine
        try {
            $contenu = $this->client->request('GET', $url, ['timeout' => 10])->getContent();
        } catch (ExceptionInterface $e) {
            return false;
        }

        // 2. Lecture de l'image avec GD (renvoie false si ce n'est pas une image valide)
        $image = @imagecreatefromstring($contenu);
        if ($image === false) {
            return false;
        }

        // 3. Réduction à 320 px de large au maximum, en gardant les proportions
        if (imagesx($image) > self::LARGEUR_MAX) {
            $image = imagescale($image, self::LARGEUR_MAX);
        }

        // Le WebP n'accepte pas les images « à palette » (certains PNG) : on les convertit
        imagepalettetotruecolor($image);

        // 4. Enregistrement en WebP dans public/images/exercices/
        $dossier = $this->dossierPublic.self::DOSSIER_PUBLIC;
        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $nomFichier = $exercice->getSlug().'.webp';
        if (!imagewebp($image, $dossier.$nomFichier, self::QUALITE_WEBP)) {
            return false;
        }

        $exercice->setImage(self::DOSSIER_PUBLIC.$nomFichier);

        return true;
    }
}
