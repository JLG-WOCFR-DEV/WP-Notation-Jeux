# Endpoint REST `/jlg/v1/ratings`

Ce document décrit l’API REST exposée par `JLG\Notation\Rest\RatingsController`. Elle permet aux partenaires ou équipes internes
de récupérer les informations clés des reviews notées afin d’alimenter dashboards, newsletters ou intégrations tierces.

## Authentification

- L’endpoint nécessite les mécanismes natifs WordPress : nonce + cookie pour les requêtes depuis l’admin, Application Password,
  OAuth ou toute solution de proxy REST déjà en place.
- Un filtre `jlg_ratings_rest_is_public` est disponible pour autoriser un accès anonyme si besoin (désactivé par défaut).

## Requête

```
GET /wp-json/jlg/v1/ratings
```

### Paramètres disponibles

| Paramètre | Type | Description |
| --- | --- | --- |
| `post_id` | entier | Limite la réponse à un article précis. Retourne une erreur 404 si l’ID ne correspond à aucune review notée. |
| `slug` | chaîne | Filtre sur le slug WordPress. Peut être combiné avec `post_id`. |
| `platform` | chaîne | Slug de plateforme (`pc`, `playstation-5`, etc.). La comparaison tient compte des labels personnalisés et des slugs enregistrés dans Notation → Plateformes. |
| `search` | chaîne | Recherche insensible à la casse sur le titre et le slug du post. |
| `status` | chaîne | Liste de statuts WordPress séparés par des virgules (`publish`, `draft`, `future`…). Par défaut seuls les articles publiés sont retournés. |
| `from` | chaîne | Date minimale (`YYYY-MM-DD`) appliquée sur `post_date_gmt`. |
| `to` | chaîne | Date maximale (`YYYY-MM-DD`) appliquée sur `post_date_gmt`. |
| `orderby` | chaîne | Champ de tri (`date`, `editorial`, `reader`, `title`, `user_votes`). |
| `order` | chaîne | Ordre de tri (`asc` ou `desc`). |
| `per_page` | entier | Nombre de reviews par page (1 à 50, défaut 10). |
| `page` | entier | Index de pagination (défaut 1). |

## Structure de réponse

La réponse JSON retourne :

- `items[]`
  - `id`, `title`, `slug`, `permalink`
  - `editorial` : score, valeur formatée, pourcentage par rapport au barème courant (`score_max`)
  - `readers` : moyenne, format, nombre de votes, delta vs rédaction, histogramme 1→5 étoiles
  - `review_status` : slug/label/description prêts pour l’affichage
  - `platforms`
    - `labels` : plateformes associées dans la metabox
    - `items` : couples `{label, slug}` normalisés
    - `breakdown` : contenu du comparatif plateformes (si renseigné)
  - `timestamps` : dates ISO 8601 de publication et de dernière mise à jour
  - `links.self` : permalien de la review
- `pagination` : total, total_pages, per_page, page courante
- `summary` : agrégats Score Insights (moyenne, médiane, distribution, podium plateformes, badges de divergence, segments, timeline, sentiments)
- `filters` : rappel des filtres appliqués
- `score_max` : barème de notation actif
- `platforms` : dictionnaire slug → label issu de Notation → Plateformes
- `generated_at` : timestamp ISO 8601 (UTC)

En cas d’erreur (review introuvable, accès refusé), l’API renvoie un objet `WP_Error` standard avec code et message localisé.

## Tests

Les vérifications unitaires résident dans `tests/RestRatingsEndpointTest.php` et couvrent :

- l’enregistrement de la route REST,
- la protection via `current_user_can( 'read' )`,
- la pagination/tri/filtrage (`platform`, `search`, `status`, `from`, `to`, `orderby`),
- le formatage du delta lecteurs vs rédaction,
- la gestion des erreurs pour les IDs inconnus.

## Exemple de réponse

```json
{
  "items": [
    {
      "id": 101,
      "title": "Stellar Blade Review",
      "slug": "stellar-blade-review",
      "permalink": "https://example.com/tests/stellar-blade-review",
      "editorial": { "score": 9.1, "formatted": "9,1", "percentage": 91, "scale": 10 },
      "readers": {
        "average": 8.7,
        "formatted": "8,7",
        "votes": 142,
        "delta": { "value": -0.4, "formatted": "-0,4" },
        "histogram": [{ "stars": 5, "count": 82, "percentage": 57.7 }, …]
      },
      "review_status": { "slug": "in_progress", "label": "Mise à jour en cours" },
      "platforms": {
        "labels": ["PlayStation 5", "PC"],
        "items": [{ "label": "PlayStation 5", "slug": "ps5" }, …],
        "breakdown": [{ "id": "pc", "label": "PC", "performance": "60fps", … }]
      },
      "timestamps": {
        "published": "2025-01-10T08:00:00+00:00",
        "modified": "2025-01-12T09:30:00+00:00"
      },
      "links": { "self": "https://example.com/tests/stellar-blade-review" }
    }
  ],
  "pagination": { "total": 12, "total_pages": 2, "per_page": 10, "page": 1 },
  "summary": { "total": 12, "mean": { "value": 8.1, "formatted": "8,1" }, … },
  "filters": { "platform": null, "search": null, "orderby": "date", "order": "desc" },
  "score_max": 10,
  "platforms": { "pc": "PC", "ps5": "PlayStation 5" },
  "generated_at": "2025-10-17T12:00:00+00:00"
}
```

## Notes d’intégration

- Pour limiter la charge sur les sites volumineux, gardez `per_page` raisonnable (≤ 25) et mettez en cache la réponse côté
  partenaire.
- Les champs `summary.timeline` et `summary.sentiments` permettent de reconstruire les graphiques Score Insights côté client.
- Utilisez le paramètre `platform` pour produire des hubs thématiques (ex. pages PlayStation ou Switch).
