import { Controller } from '@hotwired/stimulus';

// Ajoute ou retire des lignes de séries dans le formulaire de séance
export default class extends Controller {
    static targets = ['liste', 'ligne'];
    static values = { prototype: String, index: Number };

    // Ajoute une nouvelle ligne de série en bas de la liste
    ajouter() {
        // Symfony met « __name__ » dans le modèle de ligne : on le remplace par un numéro unique
        const html = this.prototypeValue.replace(/__name__/g, this.indexValue);
        this.indexValue = this.indexValue + 1;

        const modele = document.createElement('template');
        modele.innerHTML = html.trim();
        const nouvelleLigne = modele.content.firstElementChild;

        // Pré-remplit la nouvelle ligne avec les valeurs de la précédente
        const lignes = this.ligneTargets;
        if (lignes.length > 0) {
            this.recopier(lignes[lignes.length - 1], nouvelleLigne);
        }

        this.listeTarget.appendChild(nouvelleLigne);
    }

    // Supprime la ligne du bouton cliqué (la dernière ligne est seulement vidée)
    retirer(event) {
        const ligne = event.target.closest('.ligne-serie');

        if (this.ligneTargets.length > 1) {
            ligne.remove();
        } else {
            const champs = ligne.querySelectorAll('input, select');
            for (let i = 0; i < champs.length; i++) {
                champs[i].value = '';
            }
        }
    }

    // Reprend l'exercice, les répétitions et la charge de la ligne précédente (pas le RPE)
    recopier(source, cible) {
        const champsSource = source.querySelectorAll('input, select');
        const champsCible = cible.querySelectorAll('input, select');

        for (let i = 0; i < champsCible.length; i++) {
            if (!champsCible[i].name.endsWith('[rpe]')) {
                champsCible[i].value = champsSource[i].value;
            }
        }
    }
}
