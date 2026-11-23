/* stimulusFetch: 'lazy' */
// « lazy » : ce fichier et Chart.js ne sont téléchargés que sur la page Progression
import { Controller } from '@hotwired/stimulus';
import { BarController, BarElement, CategoryScale, Chart, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, Legend, LinearScale, LineController, LineElement, PointElement, Tooltip);

export default class extends Controller {
    static targets = ['choix', 'charge', 'volume', 'resume'];

    // Appelé quand la page s'affiche : on lit les couleurs du CSS puis on charge les données
    connect() {
        const style = getComputedStyle(document.documentElement);
        this.orange = style.getPropertyValue('--accent').trim();
        this.vert = style.getPropertyValue('--graphique-2').trim();
        this.grille = style.getPropertyValue('--bordure').trim();
        this.graphiqueCharge = null;
        this.graphiqueVolume = null;

        this.charger();
    }

    disconnect() {
        this.detruireGraphiques();
    }

    // Récupère en JSON les points de l'exercice choisi, puis met à jour la page
    async charger() {
        const url = this.choixTarget.selectedOptions[0].dataset.url;

        try {
            const reponse = await fetch(url);
            if (!reponse.ok) {
                throw new Error('Erreur HTTP ' + reponse.status);
            }
            const donnees = await reponse.json();
            this.afficherResume(donnees.points);
            this.dessiner(donnees.points);
        } catch (erreur) {
            this.resumeTarget.textContent = 'Impossible de charger les données, réessaie plus tard.';
            console.error(erreur);
        }
    }

    // Affiche les chiffres clés au-dessus des graphiques
    afficherResume(points) {
        let record = 0;
        let meilleur1Rm = 0;
        for (let i = 0; i < points.length; i++) {
            if (points[i].chargeMax > record) {
                record = points[i].chargeMax;
            }
            if (points[i].unRmEstime > meilleur1Rm) {
                meilleur1Rm = points[i].unRmEstime;
            }
        }
        const progres = points[points.length - 1].chargeMax - points[0].chargeMax;
        let signe = '';
        if (progres >= 0) {
            signe = '+';
        }

        this.resumeTarget.innerHTML = '';
        this.ajouterChiffre(points.length, 'séance(s)');
        this.ajouterChiffre(this.kg(record), 'record de charge');
        this.ajouterChiffre(this.kg(meilleur1Rm), '1RM estimé');
        this.ajouterChiffre(signe + this.kg(progres), 'depuis la 1re séance');
    }

    // Création avec le DOM (textContent) : aucun risque d'injection de HTML
    ajouterChiffre(valeur, libelle) {
        const bloc = document.createElement('div');
        bloc.className = 'chiffre';

        const spanValeur = document.createElement('span');
        spanValeur.className = 'chiffre-valeur';
        spanValeur.textContent = valeur;

        const spanLibelle = document.createElement('span');
        spanLibelle.className = 'chiffre-libelle';
        spanLibelle.textContent = libelle;

        bloc.appendChild(spanValeur);
        bloc.appendChild(spanLibelle);
        this.resumeTarget.appendChild(bloc);
    }

    // Dessine les deux graphiques : courbe de charge et barres de volume
    dessiner(points) {
        // Chart.js attend un tableau par donnée : dates, charges, 1RM et volumes
        const dates = [];
        const charges = [];
        const unRm = [];
        const volumes = [];
        for (let i = 0; i < points.length; i++) {
            const morceaux = points[i].date.split('-');
            dates.push(morceaux[2] + '/' + morceaux[1]);
            charges.push(points[i].chargeMax);
            unRm.push(points[i].unRmEstime);
            volumes.push(points[i].volume);
        }

        this.detruireGraphiques();

        this.graphiqueCharge = new Chart(this.chargeTarget, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    { label: 'Charge max', data: charges, borderColor: this.orange, backgroundColor: this.orange },
                    { label: '1RM estimé', data: unRm, borderColor: this.vert, backgroundColor: this.vert, borderDash: [6, 4] },
                ],
            },
            options: this.options(false, true),
        });

        this.graphiqueVolume = new Chart(this.volumeTarget, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: [
                    { label: 'Volume', data: volumes, backgroundColor: this.orange, borderRadius: 4, maxBarThickness: 32 },
                ],
            },
            options: this.options(true, false),
        });
    }

    // Options communes aux deux graphiques
    options(commencerAZero, afficherLegende) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            layout: { padding: { right: 12 } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: commencerAZero, grid: { color: this.grille } },
            },
            plugins: {
                legend: { display: afficherLegende },
            },
        };
    }

    // Supprime les anciens graphiques avant d'en dessiner de nouveaux
    detruireGraphiques() {
        if (this.graphiqueCharge !== null) {
            this.graphiqueCharge.destroy();
        }
        if (this.graphiqueVolume !== null) {
            this.graphiqueVolume.destroy();
        }
    }

    // Formate un nombre en kilos à la française : 82,5 kg
    kg(nombre) {
        return nombre.toLocaleString('fr-FR', { maximumFractionDigits: 1 }) + ' kg';
    }
}
