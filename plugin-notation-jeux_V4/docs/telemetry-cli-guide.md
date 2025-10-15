# WP-CLI – Gestion de la télémétrie

## Objectif
Fournir une procédure rapide pour auditer ou réinitialiser les métriques collectées par `JLG\Notation\Telemetry` lors des tests ou du débogage multi-environnements.

## Pré-requis
- Accès à la ligne de commande du site WordPress (SSH ou WP-CLI local).
- Droits suffisants pour exécuter `wp` et modifier les options WordPress.

## Commandes disponibles
### Afficher un résumé
```bash
wp jlg telemetry summary
```
Affiche, pour chaque canal de télémétrie, le nombre total d’événements, la répartition succès/échecs, le dernier statut et les durées moyenne / maximale (en secondes).

### Réinitialiser l’historique
```bash
wp jlg telemetry reset
```
Purge l’intégralité des métriques stockées (`Telemetry::reset_metrics()`). Un alias `wp jlg telemetry clear` est également disponible.

## Cas d’usage
- **Environnement de test** : réinitialiser les métriques avant une campagne d’intégration continue afin de ne capturer que les événements du run courant.
- **Audit accessibilité** : vérifier rapidement que le canal `live_announcer` n’enregistre pas d’erreurs après la simulation des scénarios NVDA/VoiceOver.
- **Support** : fournir au support éditorial un script simple pour vider les statistiques en cas de données obsolètes.

## Notes complémentaires
- Les sorties des commandes sont internationalisées et peuvent être utilisées dans les notes de version ou rapports de QA.
- Les filtres `jlg_live_announcer_enabled`, `jlg_live_announcer_default_duration` et `jlg_live_announcer_default_politeness` permettent d’ajuster le comportement du module avant d’exécuter ces commandes.
