# Test Driven Development

## Objectifs
Mettre en évidence l'intérêt du TDD pour gérer des besoins métier complexes, ambigus ou évolutifs.

## Contexte
Vous travaillez pour une entreprise éditrice d'un logiciel de gestion des ressources humaines. Votre équipe doit développer une fonction permettant de calculer le nombre de jours de congés à déduire pour un salarié en fonction des dates et du type de demandes.

**Cette fonctionnalité est critique** : une erreur peut impacter la paie, les droits du salarié et créer des conflits.

## Spécifications

### Signature de fonction
```javascript
calculJoursCongés(dateDebut, periodeDebut, dateFin, periodeFin, planning, typeCalcul, typeDemande)
```

### Paramètres
| Paramètre | Type | Format |
|-----------|------|--------|
| `dateDebut` / `dateFin` | string | `YYYY-MM-DD` |
| `periodeDebut` / `periodeFin` | string | `'morning'` ou `'afternoon'` |
| `planning` | Array | `{ "day": { "morning": bool, "afternoon": bool } }` |
| `typeCalcul` | string | `'ouvre'` ou `'ouvrable'` |
| `typeDemande` | number | `1` (congés payés), `2`, `3`, `4` |

### Règles métier
- Calculer le nombre de demi-journées du premier moment d'absence jusqu'au retour au travail
- Jours ouvrés : 5 jours/semaine | Jours ouvrable : 6 jours/semaine
- En jours ouvrable : maximum 5 demi-journées de majoration par demi-journée de semaine par an
- Le planning peut changer d'une semaine à l'autre
- Si non-congés payés + jours ouvrable : pas de majoration
- **Ne jamais compter les jours fériés** dans le décompte
- Temps partiel en jours ouvrés : même décompte (10 demi-journées à retirer)
- Jours fériés France : https://www.data.gouv.fr/datasets/jours-feries-en-france

## Démarche

### Groupe A — Développement classique
Implémentez la fonction avec votre méthode habituelle. Organisation libre, tests non obligatoires.

### Groupe B — Développement en TDD
Suivez une démarche TDD stricte :
1. Écrire un test (cas simple)
2. Vérifier qu'il échoue
3. Écrire le minimum de code pour le faire passer
4. Refactoriser
5. Recommencer avec un nouveau test

### Préparation commune
- Comprendre le besoin AVANT de coder
- Lister les règles métier
- Identifier les cas simples
- Identifier les cas limites
