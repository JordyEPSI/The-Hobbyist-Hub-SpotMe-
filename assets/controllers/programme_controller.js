import { Controller } from '@hotwired/stimulus';

// Construction d'un programme en glisser-déposer (API HTML5 : draggable, dragstart, dragover, drop)
export default class extends Controller {
    static targets = ['jours', 'jour', 'modeleJour', 'modeleLigne', 'boutonJour'];
    static values = { maxJours: Number };

    // Au chargement : rien n'est en cours de déplacement
    connect() {
        this.ligneDeplacee = null;
        this.exerciceDuCatalogue = null;
        this.majBoutonJour();
    }

    // On commence à glisser un exercice depuis le catalogue (il sera copié)
    prendreDuCatalogue(event) {
        this.exerciceDuCatalogue = {
            id: event.currentTarget.dataset.id,
            nom: event.currentTarget.dataset.nom,
        };
        this.ligneDeplacee = null;
        event.dataTransfer.effectAllowed = 'copy';
        // Firefox ne lance pas le glisser sans setData
        event.dataTransfer.setData('text/plain', event.currentTarget.dataset.nom);
    }

    // On commence à glisser un exercice déjà placé (il sera déplacé)
    prendreLigne(event) {
        this.ligneDeplacee = event.currentTarget;
        this.exerciceDuCatalogue = null;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', '');
        event.currentTarget.classList.add('en-deplacement');
    }

    // Fin du glisser d'une ligne, déposée ou non
    lacherLigne(event) {
        event.currentTarget.classList.remove('en-deplacement');
    }

    // Survol d'une zone de jour : preventDefault() autorise le dépôt
    survoler(event) {
        event.preventDefault();
        event.currentTarget.classList.add('zone-active');
    }

    // La souris quitte la zone : on retire la surbrillance
    quitter(event) {
        event.currentTarget.classList.remove('zone-active');
    }

    // Dépôt dans un jour : on ajoute l'exercice, ou on déplace la ligne
    deposer(event) {
        event.preventDefault();
        const zone = event.currentTarget;
        zone.classList.remove('zone-active');

        let ligne = null;
        if (this.ligneDeplacee !== null) {
            ligne = this.ligneDeplacee;
        } else if (this.exerciceDuCatalogue !== null) {
            ligne = this.creerLigne(this.exerciceDuCatalogue.id, this.exerciceDuCatalogue.nom);
        }

        if (ligne === null) {
            return;
        }

        // On insère avant la ligne sous la souris, sinon à la fin du jour
        const ligneSousLaSouris = event.target.closest('.ligne-programme');
        if (ligneSousLaSouris !== null && ligneSousLaSouris !== ligne && zone.contains(ligneSousLaSouris)) {
            zone.insertBefore(ligne, ligneSousLaSouris);
        } else {
            zone.appendChild(ligne);
        }

        this.ligneDeplacee = null;
        this.exerciceDuCatalogue = null;
    }

    // Crée une ligne d'exercice à partir du modèle <template> de la page
    creerLigne(id, nom) {
        const copie = this.modeleLigneTarget.content.cloneNode(true);
        const ligne = copie.querySelector('.ligne-programme');

        ligne.querySelector('[data-champ="exercice"]').value = id;
        ligne.querySelector('[data-role="nom"]').textContent = nom;
        ligne.querySelector('button').setAttribute('aria-label', 'Retirer ' + nom);

        return ligne;
    }

    // Ajoute un jour vide en bas du programme
    ajouterJour() {
        if (this.jourTargets.length >= this.maxJoursValue) {
            return;
        }

        const copie = this.modeleJourTarget.content.cloneNode(true);
        const jour = copie.querySelector('.jour-construction');
        jour.querySelector('[data-champ="nom"]').value = 'Jour ' + (this.jourTargets.length + 1);

        this.joursTarget.appendChild(jour);
        this.majBoutonJour();
    }

    // Supprime le jour du bouton cliqué
    retirerJour(event) {
        event.target.closest('.jour-construction').remove();
        this.majBoutonJour();
    }

    // Supprime la ligne d'exercice du bouton cliqué
    retirerLigne(event) {
        event.target.closest('.ligne-programme').remove();
    }

    // Désactive « Ajouter un jour » quand le maximum est atteint
    majBoutonJour() {
        this.boutonJourTarget.disabled = this.jourTargets.length >= this.maxJoursValue;
    }

    // Juste avant l'envoi : chaque champ reçoit un nom selon sa position,
    // par exemple jours[0][exercices][2][series], pour que PHP retrouve la structure
    nommerChamps() {
        const jours = this.jourTargets;

        for (let i = 0; i < jours.length; i++) {
            jours[i].querySelector('[data-champ="nom"]').name = 'jours[' + i + '][nom]';

            const lignes = jours[i].querySelectorAll('.ligne-programme');
            for (let j = 0; j < lignes.length; j++) {
                const champs = lignes[j].querySelectorAll('[data-champ]');
                for (let k = 0; k < champs.length; k++) {
                    champs[k].name = 'jours[' + i + '][exercices][' + j + '][' + champs[k].dataset.champ + ']';
                }
            }
        }
    }
}
