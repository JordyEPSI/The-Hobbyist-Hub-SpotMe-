<?php

namespace App\Wger;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// Client de l'API wger (https://wger.de/api/v2/), réponses gardées en cache une journée
class WgerClient
{
    private const FRANCAIS = 12;
    private const ANGLAIS = 2;
    private const DUREE_CACHE = 86400;

    private HttpClientInterface $client;

    public function __construct(
        HttpClientInterface $client,
        private readonly CacheInterface $cache,
    ) {
        $this->client = $client->withOptions([
            'base_uri' => 'https://wger.de/api/v2/',
            'timeout' => 10,
        ]);
    }

    // Exercices qui travaillent un muscle, ceux traduits en français en premier
    public function rechercher(int $muscleWger, ?int $materielWger = null, int $limite = 50): array
    {
        $parametres = ['muscles' => $muscleWger, 'limit' => $limite];
        if ($materielWger !== null) {
            $parametres['equipment'] = $materielWger;
        }

        $donnees = $this->appeler('exerciseinfo/', $parametres);

        // On range les exercices dans deux listes, puis on met les français en premier
        $francais = [];
        $autres = [];
        foreach ($donnees['results'] as $brut) {
            $exercice = $this->convertir($brut);
            if ($exercice === null) {
                continue;
            }
            if ($exercice->estEnFrancais) {
                $francais[] = $exercice;
            } else {
                $autres[] = $exercice;
            }
        }

        return array_merge($francais, $autres);
    }

    // Retourne un exercice wger par son id, ou null s'il n'existe pas
    public function recuperer(int $idWger): ?ExerciceWger
    {
        try {
            $brut = $this->appeler('exerciseinfo/'.$idWger.'/');
        } catch (WgerIndisponibleException $e) {
            if ($e->getCode() === 404) {
                return null;
            }
            throw $e;
        }

        return $this->convertir($brut);
    }

    // Fait l'appel HTTP à wger, ou relit la réponse dans le cache si on l'a déjà
    private function appeler(string $chemin, array $parametres = []): array
    {
        // Une clé de cache différente pour chaque adresse appelée
        $cle = 'wger_'.md5($chemin.'?'.http_build_query($parametres));

        return $this->cache->get($cle, function (ItemInterface $item) use ($chemin, $parametres) {
            $item->expiresAfter(self::DUREE_CACHE);

            try {
                $reponse = $this->client->request('GET', $chemin, ['query' => $parametres]);
                $code = $reponse->getStatusCode();
                if ($code !== 200) {
                    throw new WgerIndisponibleException('wger a répondu avec le code '.$code.'.', $code);
                }

                return $reponse->toArray();
            } catch (ExceptionInterface $e) {
                throw new WgerIndisponibleException('Impossible de joindre wger pour le moment.', 0, $e);
            }
        });
    }

    // Transforme la réponse brute de wger en objet ExerciceWger (null si aucun nom FR ou EN)
    private function convertir(array $brut): ?ExerciceWger
    {
        // On cherche la traduction française, sinon l'anglaise
        $francais = null;
        $anglais = null;
        foreach ($brut['translations'] ?? [] as $traduction) {
            if ($traduction['language'] === self::FRANCAIS) {
                $francais = $traduction;
            } elseif ($traduction['language'] === self::ANGLAIS) {
                $anglais = $traduction;
            }
        }

        $traduction = $francais ?? $anglais;
        if ($traduction === null || trim($traduction['name']) === '') {
            return null;
        }

        return new ExerciceWger(
            $brut['id'],
            mb_substr(trim($traduction['name']), 0, 100),
            $francais !== null,
            $this->texteBrut($traduction['description'] ?? ''),
            $this->imagePrincipale($brut['images'] ?? []),
            CorrespondanceWger::materielPrincipal($this->identifiants($brut['equipment'] ?? [])),
            CorrespondanceWger::groupes($this->identifiants($brut['muscles'] ?? [])),
            CorrespondanceWger::groupes($this->identifiants($brut['muscles_secondary'] ?? [])),
        );
    }

    // Retourne uniquement les ids d'une liste d'éléments wger
    private function identifiants(array $elements): array
    {
        $ids = [];
        foreach ($elements as $element) {
            $ids[] = $element['id'];
        }

        return $ids;
    }

    // Retourne l'adresse de l'image principale, ou null s'il n'y en a pas
    private function imagePrincipale(array $images): ?string
    {
        if (count($images) === 0) {
            return null;
        }

        // Par défaut la première image, remplacée par celle marquée « principale » si elle existe
        $image = $images[0];
        foreach ($images as $candidate) {
            if (!empty($candidate['is_main'])) {
                $image = $candidate;
            }
        }

        // Miniature 400 px, bien plus légère que l'originale
        $url = $image['thumbnails']['medium'] ?? $image['image'];
        if (!str_starts_with($url, 'https://') || strlen($url) > 255) {
            return null;
        }

        return $url;
    }

    // wger renvoie du HTML : on ne garde que le texte
    private function texteBrut(string $html): ?string
    {
        $html = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $html);
        $texte = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // On retire les lignes vides et les espaces en trop
        $lignes = [];
        foreach (explode("\n", $texte) as $ligne) {
            $ligne = trim($ligne);
            if ($ligne !== '') {
                $lignes[] = $ligne;
            }
        }

        if (count($lignes) === 0) {
            return null;
        }

        return implode("\n", $lignes);
    }
}
