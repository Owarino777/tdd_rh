# Demarche TDD - Calcul des conges

## Objectif
Implementer un calculateur de jours de conges robuste en PHP vanilla avec PHPUnit, en couvrant les cas metier critiques:
- jours feries
- jours ouvres / ouvrables
- majoration ouvrable pour conges payes
- temps partiel en ouvre
- planning decale
- planning fragmente
- planning variable selon la semaine

## Cycle TDD applique
1. Ecriture des tests metier dans tests/LeaveCalculatorTest.php
2. Execution immediate pour verifier l'etat rouge
3. Implementation minimale dans src/
4. Re-execution des tests jusqu'au vert
5. Petit refactoring sans casser les tests

## Regles metier implementees
- Signature supportee: calculJoursConges(dateDebut, periodeDebut, dateFin, periodeFin, planning, typeCalcul, typeDemande)
- Formats valides:
  - date: YYYY-MM-DD
  - periode: morning | afternoon
  - typeCalcul: ouvre | ouvrable
  - typeDemande: 1 | 2 | 3 | 4
- Les jours feries ne sont jamais comptes
- En type ouvre:
  - demande 1 (conges payes): decompte standard du lundi au vendredi, independant du planning (regle temps partiel)
  - autres demandes: decompte base sur le planning reel
- En type ouvrable:
  - dimanche non compte
  - demande 1 (conges payes): decompte de la plage demandee + majoration jusqu'au retour au travail
  - autres demandes: pas de majoration, decompte base planning
- Le planning hebdomadaire peut etre surcharge par semaine via la cle weeks (format ISO: YYYY-Www)

## Structure retenue (POO/SOLID)
- src/Application/LeaveCalculator.php
  - orchestration du calcul
  - validation des entrees
  - API metier: checkFerie, checkIsOuvrable, countDemiJourneeLoupee, calculJoursConges
- src/Domain/FrenchPublicHolidayProvider.php
  - encapsulation des jours feries fixes + mobiles
- src/Domain/Planning.php
  - resolution du planning par jour/periode avec surcharge hebdomadaire
- src/Domain/IsOuvrable.php
  - regle ouvrable (lundi-samedi)

## Lancer les tests
- vendor/bin/phpunit
- ou composer test

## Resultat
- 11 tests
- 13 assertions
- Suite verte
