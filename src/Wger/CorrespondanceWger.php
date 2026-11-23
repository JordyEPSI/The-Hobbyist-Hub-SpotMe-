<?php

namespace App\Wger;

// Fait le lien entre les identifiants wger et notre catalogue en français
final class CorrespondanceWger
{
    // id wger => [slug, nom]
    public const MATERIELS = [
        1 => ['barre', 'Barre'],
        2 => ['barre-ez', 'Barre EZ'],
        3 => ['halteres', 'Haltères'],
        4 => ['tapis', 'Tapis'],
        5 => ['swiss-ball', 'Swiss ball'],
        6 => ['barre-de-traction', 'Barre de traction'],
        7 => ['poids-du-corps', 'Poids du corps'],
        8 => ['banc', 'Banc'],
        9 => ['banc-incline', 'Banc incliné'],
        10 => ['kettlebell', 'Kettlebell'],
        11 => ['elastique', 'Élastique'],
        12 => ['poulie', 'Poulie'],
    ];

    public const SANS_MATERIEL = 7;

    // Tapis et bancs : retenus seulement s'il n'y a pas d'autre matériel
    private const MATERIELS_SECONDAIRES = [4, 8, 9];

    // slug => [nom, zone du corps, id du muscle wger utilisé pour la recherche]
    public const GROUPES = [
        'pectoraux' => ['Pectoraux', 'Haut du corps', 4],
        'epaules' => ['Épaules', 'Haut du corps', 2],
        'dorsaux' => ['Dorsaux', 'Dos', 12],
        'trapezes' => ['Trapèzes', 'Dos', 9],
        'biceps' => ['Biceps', 'Bras', 1],
        'triceps' => ['Triceps', 'Bras', 5],
        'abdominaux' => ['Abdominaux', 'Tronc', 6],
        'quadriceps' => ['Quadriceps', 'Bas du corps', 10],
        'ischio-jambiers' => ['Ischio-jambiers', 'Bas du corps', 11],
        'fessiers' => ['Fessiers', 'Bas du corps', 8],
        'mollets' => ['Mollets', 'Bas du corps', 7],
    ];

    // id du muscle wger => slug de notre groupe
    public const MUSCLES = [
        1 => 'biceps',
        2 => 'epaules',
        3 => 'pectoraux',
        4 => 'pectoraux',
        5 => 'triceps',
        6 => 'abdominaux',
        7 => 'mollets',
        8 => 'fessiers',
        9 => 'trapezes',
        10 => 'quadriceps',
        11 => 'ischio-jambiers',
        12 => 'dorsaux',
        13 => 'biceps',
        14 => 'abdominaux',
        15 => 'mollets',
    ];

    // Retourne le slug du matériel principal parmi ceux donnés par wger
    public static function materielPrincipal(array $idsWger): string
    {
        $choix = null;
        foreach ($idsWger as $id) {
            if (!isset(self::MATERIELS[$id])) {
                continue;
            }
            if (!in_array($id, self::MATERIELS_SECONDAIRES)) {
                return self::MATERIELS[$id][0];
            }
            if ($choix === null) {
                $choix = $id;
            }
        }

        if ($choix === null) {
            $choix = self::SANS_MATERIEL;
        }

        return self::MATERIELS[$choix][0];
    }

    // Transforme des ids de muscles wger en slugs de nos groupes, sans doublon
    public static function groupes(array $idsWger): array
    {
        $slugs = [];
        foreach ($idsWger as $id) {
            if (isset(self::MUSCLES[$id]) && !in_array(self::MUSCLES[$id], $slugs)) {
                $slugs[] = self::MUSCLES[$id];
            }
        }

        return $slugs;
    }

    // Retourne le nom français d'un matériel à partir de son slug
    public static function nomMateriel(string $slug): string
    {
        foreach (self::MATERIELS as $materiel) {
            if ($materiel[0] === $slug) {
                return $materiel[1];
            }
        }

        return $slug;
    }

    // Retourne l'id wger d'un matériel, ou null s'il est inconnu
    public static function idWgerMateriel(string $slug): ?int
    {
        foreach (self::MATERIELS as $id => $materiel) {
            if ($materiel[0] === $slug) {
                return $id;
            }
        }

        return null;
    }

    // Retourne la liste slug => nom des matériels
    public static function nomsMateriels(): array
    {
        $noms = [];
        foreach (self::MATERIELS as $materiel) {
            $noms[$materiel[0]] = $materiel[1];
        }

        return $noms;
    }

    // Retourne la liste slug => nom des groupes musculaires
    public static function nomsGroupes(): array
    {
        $noms = [];
        foreach (self::GROUPES as $slug => $groupe) {
            $noms[$slug] = $groupe[0];
        }

        return $noms;
    }
}
