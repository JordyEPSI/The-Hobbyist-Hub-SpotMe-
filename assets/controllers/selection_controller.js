import { Controller } from '@hotwired/stimulus';

// Import wger : compteur de cases cochées et bouton « Tout cocher »
export default class extends Controller {
    static targets = ['case', 'compteur'];

    connect() {
        this.compter();
    }

    // Coche toutes les cases des exercices en français, ou les décoche si elles le sont déjà
    toutCocher() {
        const cases = this.caseTargets;

        let toutesCochees = true;
        for (let i = 0; i < cases.length; i++) {
            if (cases[i].dataset.francais === '1' && !cases[i].checked) {
                toutesCochees = false;
            }
        }

        for (let i = 0; i < cases.length; i++) {
            if (cases[i].dataset.francais === '1') {
                cases[i].checked = !toutesCochees;
            }
        }

        this.compter();
    }

    // Affiche le nombre de cases cochées
    compter() {
        let nombre = 0;
        for (let i = 0; i < this.caseTargets.length; i++) {
            if (this.caseTargets[i].checked) {
                nombre++;
            }
        }
        this.compteurTarget.textContent = nombre;
    }
}
