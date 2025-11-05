# NG1 Thais Calendar

Plugin WordPress permettant d’intégrer le moteur de réservation Thaïs soit via une iframe personnalisable, le widget script officiel ou un formulaire interactif qui prépare le widget.

## Installation

1. Copier le dossier `ng1-thais` dans `wp-content/plugins/`.
2. Activer le plugin « NG1 Thais Calendar » dans le back-office WordPress.
3. Insérer les shortcodes ci-dessous dans vos pages selon le rendu souhaité.

## Shortcode calendrier (iframe)

Affiche directement la page `direct-booking/calendar` dans un `<iframe>` et expose un formulaire permettant de modifier l’URL générée côté visiteur.

```
[ng1_thais_calendar
  instance_domain="lafermedeloudon.thais-hotel.com"
  lang="fr"
  banner="1"
  nb_adults="2"
  nb_children="0"
  nb_infants="0"
  start_at="2025-01-01"
  nb_nights="2"
  room_types="1,2"
  rates="10"
  promo="HIVER10"
  iframe_width="100%"
  iframe_height="700"
  referral="campagne-x"]
```

### Attributs principaux

| Attribut            | Description                                                                                       |
|---------------------|---------------------------------------------------------------------------------------------------|
| `instance_domain`   | **Obligatoire**. Domaine de l’instance Thaïs (ex: `lafermedeloudon.thais-hotel.com`).             |
| `lang`              | Langue (fr, en, de, es, it, nl, ro).                                                              |
| `banner`            | `1` ou `0` pour afficher/cacher le header du moteur.                                              |
| `nb_adults` / etc.  | Préremplissage du nombre d’adultes, enfants, bébés.                                               |
| `start_at` / `end_at` / `nb_nights` | Dates d’arrivée/départ ou nombre de nuits.                                       |
| `room_types`, `rates` | IDs séparés par des virgules. Transformés en syntaxe `[1,2]` dans l’URL.                       |
| `promo`             | Code promo appliqué automatiquement.                                                             |
| `iframe_width`, `iframe_height` | Taille de l’iframe (largeur CSS, hauteur en px).                                      |
| `referral`          | Paramètre `ref` pour le tracking.                                                                 |

Si `instance_domain` est renseigné dans le shortcode, le champ correspondant du formulaire devient en lecture seule. Le reste des paramètres reste modifiable par le visiteur avant d’actualiser l’iframe.

## Valeurs par défaut (réglages back-office)

Dans le back-office WordPress, rendez-vous dans **Réglages → NG1 Thais** pour définir les paramètres par défaut (instance, langue, URL du script widget). Ces valeurs sont utilisées automatiquement par les shortcodes si vous n’indiquez pas d’attributs.

## Shortcode widget script

Injecte le widget calendrier officiel Thaïs via le script fourni par Thaïs. Le script est enregistré/enqueue automatiquement (chargé en footer) en fonction de l’URL passée dans l’attribut `script_src`.

```
[ng1_thais_widget
  script_src="https://MON_INSTANCE.thais-hotel.com/direct-booking/widget/thais_widget.js"
  instance="MON_INSTANCE"
  lang="fr"
  width="800"
  height="auto"
  nb_persons="2"
  nb_adults="2"
  nb_children="0"
  nb_infants="0"
  id_room_type="1"
  id_rate="10"
  mode="grid"
  auto_search="true"
  open="pop-up"
  nb_months="2"
  nb_months_mobile="1"
  promo="HIVER10"]
```

### Attributs

| Attribut         | Description                                                                      |
|------------------|----------------------------------------------------------------------------------|
| `script_src`     | **Obligatoire**. URL du script Thaïs (`https://.../direct-booking/widget/...`).   |
| `instance`       | Valeur pour `data-instance`.                                                      |
| `lang`           | Valeur pour `data-lang`.                                                          |
| `width` / `height` | Attributs `width` et `height` appliqués sur le conteneur.                       |
| `nb_persons`, `nb_adults`, `nb_children`, `nb_infants` | Valeurs numériques pour les `data-*`.       |
| `id_room_type`, `id_rate` | IDs filtrant un type de chambre ou tarif.                               |
| `mode`           | `line` ou `grid`.                                                                 |
| `auto_search`    | `true`/`false`. Lance la recherche automatiquement au second clic.               |
| `open`           | Mode d’ouverture (`pop-up`, par exemple).                                        |
| `nb_months`, `nb_months_mobile` | Nombre de mois sur desktop / mobile.                              |
| `promo`          | Code promotionnel.                                                                |

Les paramètres non fournis ne sont pas ajoutés à la balise et conservent leur comportement par défaut côté Thaïs. Plusieurs shortcodes peuvent utiliser le même `script_src` : le script n’est chargé qu’une seule fois.

## Shortcode widget avec formulaire

Affiche un formulaire permettant de configurer le widget script dynamiquement. Le widget reste caché tant que le formulaire n’a pas été soumis.

```
[ng1_thais_widget_form
  script_src="https://MON_INSTANCE.thais-hotel.com/direct-booking/widget/thais_widget.js"
  instance="MON_INSTANCE"
  lang="fr"
  width="100%"
  height="auto"
  nb_adults="2"
  nb_children="0"
  nb_infants="0"
  nb_months="2"
  nb_months_mobile="1"
  promo="HIVER10"]
```

- Les champs sont préremplis avec les valeurs par défaut définies dans l’admin.
- Lors d’une nouvelle soumission, le script n’est injecté qu’une seule fois et `ThaisWidget` est réinitialisé automatiquement.

## Notes

- Les avertissements éventuels dans certains IDE concernant des fonctions WordPress (`shortcode_atts`, `wp_enqueue_script`, etc.) sont sans impact : ces fonctions sont natives de WordPress.
- Les réglages par défaut sont disponibles dans Réglages → NG1 Thais.
- Le CSS du widget peut être personnalisé via le back-office Thaïs si nécessaire.
