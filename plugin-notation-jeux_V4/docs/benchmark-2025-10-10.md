# Benchmark notation – 10 octobre 2025

## Périmètre
- Plugin WP Notation Jeux v4 (endpoint REST, export CLI, agrégateur d'insights).
- Références étudiées :
  - **IGN Review Center** – focus sur filtres dynamiques, deltas éditorial vs lecteurs clairement signés, exports CSV avec tri multi critères.
  - **OpenCritic** – timeline interactive et badges de divergence soulignant les écarts marquants entre moyenne presse et joueurs.

## Constats clés
| Axe | IGN | OpenCritic | Plugin actuel |
| --- | --- | --- | --- |
| Filtres temporels | Filtres "From/To" toujours disponibles et respectés dans les exports | Timeline filtrable par période | REST `from/to` ignorait certains cas (WP_Query stub) → résultats incohérents |
| Delta de notation | Deltas affichés comme avantage rédaction (positive = rédaction plus haute) | Affiche l'écart absolu avec signe cohérent | Delta REST calculé depuis lecteurs → signe inversé par rapport aux attentes front |
| Export CSV | CSV fournit un fallback clair et échappe correctement les données | — | Export CLI n'indiquait pas le paramètre d'échappement → dépréciation PHP 8.4 |
| Timeline | Timeline interactive toujours active, même avec objets légers | Timeline toujours affichée (fallback sur dates locales) | Agrégateur ignorait les objets non `WP_Post` → timeline marquée indisponible |

## Opportunités priorisées
1. **Aligner le signe du delta REST sur la lecture éditoriale** pour respecter les conventions observées chez IGN et les maquettes front.
2. **Renforcer la robustesse des filtres temporels** (REST + timeline) pour éviter des listes trop verbeuses lors des exports comparatifs.
3. **Durcir l’export CSV** en anticipant les changements de signature PHP 8.4 et en garantissant un format stable pour les équipes data.

Ces ajustements rapprochent le plugin des standards pro identifiés et servent de base pour itérer sur la mise en forme front (badges et timeline).
