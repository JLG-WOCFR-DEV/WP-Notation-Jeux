# Automatisation du statut de review

## Objectif
La feuille de route d’octobre 2025 introduit un mode « Review en cours » automatisé. Ce document décrit le fonctionnement du cron `jlg_review_status_auto_finalize`, la configuration côté rédaction et les points d’extension disponibles pour les développeurs.

## Fonctionnement général
1. Chaque review peut renseigner la date du **dernier patch vérifié** via la metabox « 📝 Verdict de la rédaction ».
2. Lorsque l’option **Finalisation auto du statut** est active dans `Notation – JLG > Réglages > Modules`, le plugin planifie un événement quotidien (`jlg_review_status_auto_finalize`).
3. À chaque exécution, le cron recherche les articles :
   - dont le statut courant figure parmi les statuts surveillés (par défaut `in_progress`),
   - dont la métadonnée `_jlg_last_patch_date` est antérieure ou égale au seuil calculé (`today - N jours`).
4. Les reviews éligibles sont automatiquement basculées vers `final`. Le hook `jlg_review_status_transition` reçoit les paramètres `(post_id, statut_précédent, statut_cible, 'auto_finalize')` pour permettre la journalisation ou des webhooks externes.

## Options disponibles
- **Finalisation auto du statut** (checkbox) : active le cron et affiche un champ « Dernier patch vérifié » dans la metabox.
- **Délai avant finalisation (jours)** : entier entre 1 et 60. Définit le nombre de jours à attendre après la date de dernier patch avant de repasser en version finale.

## Hooks & filtres
| Hook | Description |
| --- | --- |
| `jlg_review_status_auto_finalize_threshold_timestamp` | Ajuste le timestamp calculé à partir du délai configuré. |
| `jlg_review_status_auto_finalize_threshold` | Permet de substituer la date seuil (format `Y-m-d`). |
| `jlg_review_status_auto_finalize_statuses` | Modifie la liste des statuts surveillés (par défaut `in_progress`). |
| `jlg_review_status_auto_finalize_target` | Change le statut cible (par défaut `final`). |
| `jlg_review_status_auto_finalize_batch_size` | Ajuste la taille des lots traités par requête. |
| `jlg_review_status_auto_finalize_query_args` | Permet de personnaliser l’argumentation `WP_Query`. |
| `jlg_review_status_transition` | Action déclenchée après chaque bascule automatique ou manuelle du statut. |

## Checklist manuelle
1. Dans l’admin, cochez **Finalisation auto du statut** et définissez un délai de test (ex. 3 jours).
2. Sur un article existant, positionnez le statut en « Mise à jour en cours », remplissez le champ « Dernier patch vérifié » avec une date passée et enregistrez.
3. Exécutez le cron via WP-CLI `wp cron event run jlg_review_status_auto_finalize --allow-root` (ou attendez la prochaine exécution). Vérifiez que :
   - le statut passe automatiquement à « Version finale »,
   - la notice d’automatisation apparaît dans les logs si votre thème écoute `jlg_review_status_transition`.
4. Répétez avec une date future pour confirmer qu’aucune bascule n’a lieu et qu’aucune action n’est déclenchée.

## Points d’attention
- Le champ « Dernier patch vérifié » est facultatif. Sans valeur, aucun traitement automatique n’est appliqué.
- Les sites très volumineux peuvent augmenter/diminuer la taille des lots via `jlg_review_status_auto_finalize_batch_size`.
- Pensez à informer la rédaction que la bascule est automatique pour éviter les modifications contradictoires côté manuel.
