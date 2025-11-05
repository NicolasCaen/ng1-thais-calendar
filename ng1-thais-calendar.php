<?php
/**
 * Plugin Name:       NG1 Thais Calendar
 * Description:       Intégration du calendrier/moteur de réservation Thais via shortcode avec options Back-Office.
 * Version:           1.0.0
 * Author:            NG1
 * License:           GPL v2 or later
 * Text Domain:       ng1-thais
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const NG1_THAIS_DEFAULTS_OPTION = 'ng1_thais_defaults';

function ng1_thais_get_defaults() {
    $defaults = [
        'calendar_instance_domain' => '',
        'calendar_lang'            => 'fr',
        'widget_script_src'        => '',
        'widget_instance'          => '',
        'widget_lang'              => 'fr',
        'reservation_url'          => '',
        'reservation_label'        => __( 'Réserver', 'ng1-thais' ),
    ];

    $saved = get_option( NG1_THAIS_DEFAULTS_OPTION, [] );

    $options = wp_parse_args( (array) $saved, $defaults );

    if ( '' === $options['widget_instance'] && '' !== $options['calendar_instance_domain'] ) {
        $domain = $options['calendar_instance_domain'];
        $parts = explode( '.', $domain );
        if ( ! empty( $parts[0] ) ) {
            $options['widget_instance'] = $parts[0];
        }
    }

    if ( '' === $options['widget_script_src'] && '' !== $options['widget_instance'] ) {
        $options['widget_script_src'] = sprintf( 'https://%s.thais-hotel.com/direct-booking/widget/thais_widget.js', $options['widget_instance'] );
    }

    if ( '' === $options['reservation_label'] ) {
        $options['reservation_label'] = __( 'Réserver', 'ng1-thais' );
    }

    return $options;
}

function ng1_thais_enqueue_widget_script( $script ) {
    $script = trim( (string) $script );
    if ( '' === $script ) {
        return false;
    }

    $script_url = esc_url_raw( $script );

    if ( '' === $script_url ) {
        $maybe_url = filter_var( $script, FILTER_VALIDATE_URL );
        $script_url = $maybe_url ? $maybe_url : esc_url_raw( $script, [ 'http', 'https' ] );
    }

    if ( '' === $script_url ) {
        return false;
    }

    $handle = 'ng1-thais-widget-script-' . md5( $script_url );

    if ( ! wp_script_is( $handle, 'enqueued' ) ) {
        if ( ! wp_script_is( $handle, 'registered' ) ) {
            wp_register_script( $handle, $script_url, [], null, true );
        }
        wp_enqueue_script( $handle );
    }

    return [ $handle, $script_url ];
}

function ng1_thais_register_settings() {
    register_setting( 'ng1_thais_defaults', NG1_THAIS_DEFAULTS_OPTION, function( $input ) {
        $defaults = ng1_thais_get_defaults();

        $sanitized = [];
        $sanitized['calendar_instance_domain'] = sanitize_text_field( $input['calendar_instance_domain'] ?? $defaults['calendar_instance_domain'] );
        $sanitized['calendar_lang']            = sanitize_text_field( $input['calendar_lang'] ?? $defaults['calendar_lang'] );
        $sanitized['widget_script_src']        = esc_url_raw( $input['widget_script_src'] ?? $defaults['widget_script_src'] );
        $sanitized['widget_instance']          = sanitize_text_field( $input['widget_instance'] ?? $defaults['widget_instance'] );
        $sanitized['widget_lang']              = sanitize_text_field( $input['widget_lang'] ?? $defaults['widget_lang'] );
        $sanitized['reservation_url']          = esc_url_raw( $input['reservation_url'] ?? $defaults['reservation_url'] );
        $sanitized['reservation_label']        = sanitize_text_field( $input['reservation_label'] ?? $defaults['reservation_label'] );

        return $sanitized;
    } );
}
add_action( 'admin_init', 'ng1_thais_register_settings' );

function ng1_thais_register_help_page() {
    add_options_page(
        __( 'Thais Calendar – Aide', 'ng1-thais' ),
        __( 'Thais Calendar', 'ng1-thais' ),
        'manage_options',
        'ng1-thais-help',
        'ng1_thais_render_help_page'
    );
}
add_action( 'admin_menu', 'ng1_thais_register_help_page' );

function ng1_thais_render_help_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $defaults = ng1_thais_get_defaults();

    $calendar_example = '[ng1_thais_calendar
  instance_domain="MON_INSTANCE.thais-hotel.com"
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
  referral="campagne-x"]';

    $widget_example = '[ng1_thais_widget
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
  promo="HIVER10"]';

    $widget_form_example = '[ng1_thais_widget_form
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
  promo="HIVER10"]';

    $reservation_button_example = '[ng1_thais_reservation_button
  url="https://MON_INSTANCE.thais-hotel.com/direct-booking/"
  label="Réserver maintenant"
  target="_blank"]';

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Thais Calendar – Guide d’utilisation', 'ng1-thais' ); ?></h1>

        <h2><?php esc_html_e( 'Valeurs par défaut (appliquées si les shortcodes ne précisent pas ces attributs)', 'ng1-thais' ); ?></h2>
        <form method="post" action="options.php" style="margin-bottom:2rem;">
            <?php settings_fields( 'ng1_thais_defaults' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="ng1_thais_calendar_instance_domain"><?php esc_html_e( 'Instance (calendrier)', 'ng1-thais' ); ?></label></th>
                    <td><input type="text" class="regular-text" id="ng1_thais_calendar_instance_domain" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[calendar_instance_domain]" value="<?php echo esc_attr( $defaults['calendar_instance_domain'] ); ?>" placeholder="MON_INSTANCE.thais-hotel.com"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_calendar_lang"><?php esc_html_e( 'Langue (calendrier)', 'ng1-thais' ); ?></label></th>
                    <td><input type="text" class="regular-text" id="ng1_thais_calendar_lang" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[calendar_lang]" value="<?php echo esc_attr( $defaults['calendar_lang'] ); ?>" placeholder="fr"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_widget_script_src"><?php esc_html_e( 'Script widget (URL)', 'ng1-thais' ); ?></label></th>
                    <td><input type="url" class="regular-text" id="ng1_thais_widget_script_src" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[widget_script_src]" value="<?php echo esc_attr( $defaults['widget_script_src'] ); ?>" placeholder="https://.../thais_widget.js"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_widget_instance"><?php esc_html_e( 'Instance (widget)', 'ng1-thais' ); ?></label></th>
                    <td><input type="text" class="regular-text" id="ng1_thais_widget_instance" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[widget_instance]" value="<?php echo esc_attr( $defaults['widget_instance'] ); ?>" placeholder="MON_INSTANCE"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_widget_lang"><?php esc_html_e( 'Langue (widget)', 'ng1-thais' ); ?></label></th>
                    <td><input type="text" class="regular-text" id="ng1_thais_widget_lang" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[widget_lang]" value="<?php echo esc_attr( $defaults['widget_lang'] ); ?>" placeholder="fr"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_reservation_url"><?php esc_html_e( 'URL réservation (bouton)', 'ng1-thais' ); ?></label></th>
                    <td><input type="url" class="regular-text" id="ng1_thais_reservation_url" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[reservation_url]" value="<?php echo esc_attr( $defaults['reservation_url'] ); ?>" placeholder="https://MON_INSTANCE.thais-hotel.com/direct-booking/"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="ng1_thais_reservation_label"><?php esc_html_e( 'Texte bouton réservation', 'ng1-thais' ); ?></label></th>
                    <td><input type="text" class="regular-text" id="ng1_thais_reservation_label" name="<?php echo esc_attr( NG1_THAIS_DEFAULTS_OPTION ); ?>[reservation_label]" value="<?php echo esc_attr( $defaults['reservation_label'] ); ?>" placeholder="<?php esc_attr_e( 'Réserver', 'ng1-thais' ); ?>"></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>

        <p><?php esc_html_e( 'Ce plugin propose trois shortcodes pour intégrer le moteur Thaïs : une version iframe, le widget script officiel et un formulaire interactif pour configurer le widget.', 'ng1-thais' ); ?></p>

        <h2><?php esc_html_e( 'Shortcode calendrier (iframe)', 'ng1-thais' ); ?></h2>
        <p><?php esc_html_e( 'Affiche la page direct-booking/calendar dans une iframe et expose un formulaire côté visiteur pour ajuster les paramètres.', 'ng1-thais' ); ?></p>
        <textarea readonly rows="13" style="width:100%; font-family: monospace;"><?php echo esc_textarea( $calendar_example ); ?></textarea>
        <p>
            <strong><?php esc_html_e( 'Attributs clés :', 'ng1-thais' ); ?></strong><br>
            <?php esc_html_e( 'instance_domain (obligatoire), lang, banner, nb_adults/children/infants, start_at / end_at / nb_nights, room_types, rates, promo, iframe_width/iframe_height, referral.', 'ng1-thais' ); ?>
        </p>
        <p><?php esc_html_e( 'Si instance_domain est défini dans le shortcode, le champ du formulaire est verrouillé pour éviter les modifications accidentelles.', 'ng1-thais' ); ?></p>

        <h2><?php esc_html_e( 'Shortcode widget script', 'ng1-thais' ); ?></h2>
        <p><?php esc_html_e( 'Insère le widget officiel via le script fourni par Thaïs. Le script est chargé en pied de page automatiquement.', 'ng1-thais' ); ?></p>
        <p class="description"><?php esc_html_e( 'Utilisez les valeurs de la section "Valeurs par défaut" pour éviter de répéter l’instance et l’URL du script dans chaque shortcode.', 'ng1-thais' ); ?></p>
        <textarea readonly rows="15" style="width:100%; font-family: monospace;"><?php echo esc_textarea( $widget_example ); ?></textarea>
        <p>
            <strong><?php esc_html_e( 'Attributs clés :', 'ng1-thais' ); ?></strong><br>
            <?php esc_html_e( 'script_src (obligatoire), instance, lang, width/height, nb_persons/nb_adults/nb_children/nb_infants, id_room_type, id_rate, mode, auto_search, open, nb_months/nb_months_mobile, promo.', 'ng1-thais' ); ?>
        </p>
        <p><?php esc_html_e( 'Les paramètres non fournis sont laissés vides pour conserver les valeurs par défaut côté Thaïs.', 'ng1-thais' ); ?></p>

        <h2><?php esc_html_e( 'Shortcode widget + formulaire', 'ng1-thais' ); ?></h2>
        <p><?php esc_html_e( 'Permet d’afficher un formulaire qui génère le widget script dynamiquement. Le widget reste caché tant que le formulaire n’est pas soumis.', 'ng1-thais' ); ?></p>
        <textarea readonly rows="15" style="width:100%; font-family: monospace;"><?php echo esc_textarea( $widget_form_example ); ?></textarea>
        <p><?php esc_html_e( 'Les champs du formulaire sont pré-remplis avec les valeurs par défaut et peuvent être ajustés avant d’afficher le widget.', 'ng1-thais' ); ?></p>

        <h2><?php esc_html_e( 'Shortcode bouton de réservation', 'ng1-thais' ); ?></h2>
        <p><?php esc_html_e( 'Affiche un bouton simple pointant vers votre moteur de réservation. Le lien et l’intitulé peuvent être définis ici ou via les attributs du shortcode.', 'ng1-thais' ); ?></p>
        <textarea readonly rows="7" style="width:100%; font-family: monospace;"><?php echo esc_textarea( $reservation_button_example ); ?></textarea>
        <p><?php esc_html_e( 'Par défaut, le lien et le texte proviennent des réglages ci-dessus. Utilisez ce shortcode pour placer un CTA « Réserver » rapide.', 'ng1-thais' ); ?></p>

        <h2><?php esc_html_e( 'Notes', 'ng1-thais' ); ?></h2>
        <ul>
            <li><?php esc_html_e( 'Les fonctions WordPress utilisées (shortcode_atts, wp_enqueue_script, etc.) sont natives : les avertissements IDE peuvent être ignorés.', 'ng1-thais' ); ?></li>
            <li><?php esc_html_e( 'Les réglages par défaut se gèrent depuis cette page pour éviter de répéter les mêmes attributs.', 'ng1-thais' ); ?></li>
            <li><?php esc_html_e( 'Vous pouvez personnaliser le style du widget via vos propres CSS si besoin.', 'ng1-thais' ); ?></li>
        </ul>
    </div>
    <?php
}

/**
 * Helpers
 */
function ng1_thais_sanitize_csv_ids( $value ) {
    $value = trim( (string) $value );
    if ( '' === $value ) return '';
    $parts = preg_split( '/\s*,\s*/', $value );
    $ids = [];
    foreach ( $parts as $p ) {
        if ( is_numeric( $p ) ) {
            $ids[] = (int) $p;
        }
    }
    return implode( ',', array_unique( $ids ) );
}

function ng1_thais_build_calendar_url( $instance_domain, $params ) {
    $base = trailingslashit( 'https://' . $instance_domain ) . 'direct-booking/calendar';

    // Map params following Thais documentation
    $query = [];

    $map = [
        'lang'        => 'lang',
        'banner'      => 'banner',
        'nb_adults'   => 'nb_adults',
        'nb_children' => 'nb_children',
        'nb_infants'  => 'nb_infants',
        'start_at'    => 'start_at',
        'end_at'      => 'end_at',
        'nb_nights'   => 'nb_nights',
        'promo'       => 'promo',
    ];

    foreach ( $map as $key => $q ) {
        if ( isset( $params[ $key ] ) && $params[ $key ] !== '' ) {
            $query[ $q ] = $params[ $key ];
        }
    }

    // room_types & rates as array syntax [1,2]
    foreach ( [ 'room_types', 'rates' ] as $arr_key ) {
        if ( isset( $params[ $arr_key ] ) && $params[ $arr_key ] !== '' ) {
            $csv = ng1_thais_sanitize_csv_ids( $params[ $arr_key ] );
            if ( $csv !== '' ) {
                $in = '[' . implode( ',', array_map( 'intval', explode( ',', $csv ) ) ) . ']';
                $query[ $arr_key ] = $in;
            }
        }
    }

    if ( ! empty( $params['referral'] ) ) {
        $query['ref'] = sanitize_text_field( $params['referral'] );
    }

    $url = $base;
    if ( ! empty( $query ) ) {
        $url .= '?' . http_build_query( $query );
    }
    return $url;
}

/**/ 

/**
 * Shortcode: [ng1_thais_calendar]
 * Attributs surchargent les options: instance_domain, lang, banner, nb_adults, nb_children, nb_infants, start_at, end_at, nb_nights, room_types, rates, promo, iframe_width, iframe_height, referral
 */
function ng1_thais_shortcode_calendar( $atts ) {
    $defaults = ng1_thais_get_defaults();

    $atts = shortcode_atts( [
        'instance_domain' => $defaults['calendar_instance_domain'],
        'lang'            => $defaults['calendar_lang'],
        'banner'          => '1',
        'nb_adults'       => '',
        'nb_children'     => '',
        'nb_infants'      => '',
        'start_at'        => '',
        'end_at'          => '',
        'nb_nights'       => '',
        'room_types'      => '',
        'rates'           => '',
        'promo'           => '',
        'iframe_width'    => '100%',
        'iframe_height'   => '700',
        'referral'        => '',
    ], $atts, 'ng1_thais_calendar' );

    $instance = trim( (string) $atts['instance_domain'] );
    if ( $instance === '' ) {
        return '<div class="ng1-thais-error">Erreur: instance_domain manquant (ex: MON_INSTANCE.thais-hotel.com).</div>';
    }

    $url = ng1_thais_build_calendar_url( $instance, $atts );

    $width  = esc_attr( $atts['iframe_width'] );
    $height = esc_attr( $atts['iframe_height'] );

    $form_id = 'ng1-thais-form-' . wp_rand( 1000, 9999 );
    $iframe_id = 'ng1-thais-iframe-' . wp_rand( 1000, 9999 );

    $html  = '<form class="ng1-thais-form" id="' . esc_attr( $form_id ) . '">';
    $html .= '<div class="ng1-row"><label>Domaine instance</label><input type="text" name="instance_domain" value="' . esc_attr( $atts['instance_domain'] ) . '" placeholder="ex: MON_INSTANCE.thais-hotel.com" required' . ( $atts['instance_domain'] !== '' ? ' readonly' : '' ) . '></div>';
    $html .= '<div class="ng1-row"><label>Langue</label><input type="text" name="lang" value="' . esc_attr( $atts['lang'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Banner</label><select name="banner"><option value="1"' . selected( $atts['banner'], '1', false ) . '>1</option><option value="0"' . selected( $atts['banner'], '0', false ) . '>0</option></select></div>';
    $html .= '<div class="ng1-row"><label>Adultes</label><input type="number" min="0" name="nb_adults" value="' . esc_attr( $atts['nb_adults'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Enfants</label><input type="number" min="0" name="nb_children" value="' . esc_attr( $atts['nb_children'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Bébés</label><input type="number" min="0" name="nb_infants" value="' . esc_attr( $atts['nb_infants'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Arrivée</label><input type="text" name="start_at" value="' . esc_attr( $atts['start_at'] ) . '" placeholder="YYYY-MM-DD"></div>';
    $html .= '<div class="ng1-row"><label>Départ</label><input type="text" name="end_at" value="' . esc_attr( $atts['end_at'] ) . '" placeholder="YYYY-MM-DD"></div>';
    $html .= '<div class="ng1-row"><label>Nuits</label><input type="number" min="1" name="nb_nights" value="' . esc_attr( $atts['nb_nights'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Room types (IDs CSV)</label><input type="text" name="room_types" value="' . esc_attr( $atts['room_types'] ) . '" placeholder="ex: 1,2"></div>';
    $html .= '<div class="ng1-row"><label>Rates (IDs CSV)</label><input type="text" name="rates" value="' . esc_attr( $atts['rates'] ) . '" placeholder="ex: 10,12"></div>';
    $html .= '<div class="ng1-row"><label>Code promo</label><input type="text" name="promo" value="' . esc_attr( $atts['promo'] ) . '"></div>';
    $html .= '<div class="ng1-row"><label>Mode (widget)</label><select name="mode"><option value="">(n/a pour iframe)</option><option value="line">line</option><option value="grid">grid</option></select><small style="opacity:.7;display:block;">Note: le mode s’applique au widget script Thais. L’iframe calendrier ne l’utilise pas.</small></div>';
    $html .= '<div class="ng1-row"><label>Largeur iframe</label><input type="text" name="iframe_width" value="' . esc_attr( $atts['iframe_width'] ) . '" placeholder="100%"></div>';
    $html .= '<div class="ng1-row"><label>Hauteur iframe (px)</label><input type="text" name="iframe_height" value="' . esc_attr( $atts['iframe_height'] ) . '" placeholder="700"></div>';
    $html .= '<div class="ng1-row"><label>ref (tracking)</label><input type="text" name="referral" value="' . esc_attr( $atts['referral'] ) . '"></div>';
    $html .= '<div class="ng1-actions"><button type="submit">Afficher le calendrier</button></div>';
    $html .= '</form>';

    $html .= '<div class="ng1-thais-calendar" style="margin-top:1rem;">';
    $html .= '<iframe id="' . esc_attr( $iframe_id ) . '" src="' . esc_url( $url ) . '" width="' . $width . '" height="' . $height . '" style="border:0; width:' . $width . '; height:' . $height . 'px;" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
    $html .= '</div>';

    $html .= '<script>(function(){
      function sanitizeCsvIds(v){v=(v||"").trim();if(!v)return"";return v.split(/\s*,\s*/).filter(Boolean).map(function(x){x=x.replace(/[^0-9]/g,"");return x?parseInt(x,10):null;}).filter(function(x){return x!==null;}).join(",");}
      function buildUrl(domain, data){var base="https://"+domain.replace(/\/$/,"")+"/direct-booking/calendar";var q={};
        ["lang","banner","nb_adults","nb_children","nb_infants","start_at","end_at","nb_nights","promo"].forEach(function(k){if(data[k]) q[k]=data[k];});
        ["room_types","rates"].forEach(function(k){if(data[k]){var csv=sanitizeCsvIds(data[k]); if(csv){q[k]="["+csv+"]";}}});
        if(data.referral){q.ref=data.referral;}
        var qs=Object.keys(q).map(function(k){return encodeURIComponent(k)+"="+encodeURIComponent(q[k]);}).join("&");
        return base+(qs?"?"+qs:"");
      }
      var f=document.getElementById("' . esc_js( $form_id ) . '");
      var iframe=document.getElementById("' . esc_js( $iframe_id ) . '");
      if(!f||!iframe) return;
      f.addEventListener("submit",function(e){e.preventDefault();var fd=new FormData(f);var data={};fd.forEach(function(v,k){data[k]=v;});
        if(!data.instance_domain){alert("Merci de saisir le domaine de l\'instance");return;}
        var url=buildUrl(data.instance_domain,data);iframe.src=url;
      });
    })();</script>';

    return $html;
}
add_shortcode( 'ng1_thais_calendar', 'ng1_thais_shortcode_calendar' );

/**
 * Shortcode: [ng1_thais_widget]
 * Attributes:
 * - script_src (required): URL du script du widget Thais
 * - instance: identifiant d'instance (data-instance)
 * - lang, width, height
 * - nb_persons (total), nb_adults, nb_children, nb_infants
 * - id_room_type, id_rate
 * - mode (line|grid), auto_search (true|false), open (ex: pop-up)
 * - nb_months, nb_months_mobile
 * - promo
 */
function ng1_thais_shortcode_widget( $atts ) {
    $defaults = ng1_thais_get_defaults();

    $atts = shortcode_atts( [
        'script_src'       => $defaults['widget_script_src'],
        'instance'         => $defaults['widget_instance'],
        'lang'             => $defaults['widget_lang'],
        'width'            => '100%',
        'height'           => 'auto',
        'nb_persons'       => '',
        'nb_adults'        => '',
        'nb_children'      => '',
        'nb_infants'       => '',
        'id_room_type'     => '',
        'id_rate'          => '',
        'mode'             => '',
        'auto_search'      => '',
        'open'             => '',
        'nb_months'        => '',
        'nb_months_mobile' => '',
        'promo'            => '',
    ], $atts, 'ng1_thais_widget' );

    $script = trim( (string) $atts['script_src'] );
    if ( $script === '' ) {
        return '<div class="ng1-thais-error">Erreur: script_src manquant (URL du script widget Thais).</div>';
    }

    $script_url = esc_url_raw( $script );

    if ( '' === $script_url ) {
        $maybe_url = filter_var( $script, FILTER_VALIDATE_URL );
        $script_url = $maybe_url ? $maybe_url : esc_url_raw( $script, [ 'http', 'https' ] );
    }

    $handle = 'ng1-thais-widget-script-' . md5( $script_url );

    if ( ! wp_script_is( $handle, 'enqueued' ) ) {
        if ( ! wp_script_is( $handle, 'registered' ) ) {
            wp_register_script( $handle, $script_url, [], null, true );
        }
        wp_enqueue_script( $handle );
    }

    $container_id = 'ng1-thais-widget-' . wp_rand( 1000, 9999 );

    // Build data attributes
    $data_attrs = [
        'data-instance'         => $atts['instance'],
        'data-lang'             => $atts['lang'],
        'data-nb-persons'       => $atts['nb_persons'],
        'data-nb-adults'        => $atts['nb_adults'],
        'data-nb-children'      => $atts['nb_children'],
        'data-nb-infants'       => $atts['nb_infants'],
        'data-id-room-type'     => $atts['id_room_type'],
        'data-id-rate'          => $atts['id_rate'],
        'data-mode'             => $atts['mode'],
        'data-auto-search'      => $atts['auto_search'],
        'data-open'             => $atts['open'],
        'data-nb-months'        => $atts['nb_months'],
        'data-nb-months-mobile' => $atts['nb_months_mobile'],
        'data-promo'            => $atts['promo'],
    ];

    $attr_html = '';
    foreach ( $data_attrs as $k => $v ) {
        $v = trim( (string) $v );
        if ( $v !== '' ) {
            $attr_html .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
        }
    }

    $width_attr = esc_attr( $atts['width'] );
    $height_attr = esc_attr( $atts['height'] );

    $html  = '<div id="' . esc_attr( $container_id ) . '" class="thais_calendar_widget" data-widget="calendar"';
    if ( $width_attr !== '' ) {
        $html .= ' width="' . $width_attr . '"';
    }
    if ( $height_attr !== '' ) {
        $html .= ' height="' . $height_attr . '"';
    }
    $html .= $attr_html . '></div>';

    return $html;
}
add_shortcode( 'ng1_thais_widget', 'ng1_thais_shortcode_widget' );

/**
 * Shortcode: [ng1_thais_widget_form]
 * Renders a form allowing editors to configure the widget parameters on the fly.
 */
function ng1_thais_shortcode_widget_form( $atts ) {
    $defaults = ng1_thais_get_defaults();

    $atts = shortcode_atts( [
        'script_src'       => $defaults['widget_script_src'],
        'instance'         => $defaults['widget_instance'],
        'lang'             => $defaults['widget_lang'],
        'width'            => '100%',
        'height'           => 'auto',
        'nb_persons'       => '',
        'nb_adults'        => '',
        'nb_children'      => '',
        'nb_infants'       => '',
        'nb_months'        => '',
        'nb_months_mobile' => '',
        'promo'            => '',
        'start_at'         => '',
        'nb_nights'        => '',
    ], $atts, 'ng1_thais_widget_form' );

    $script_info = ng1_thais_enqueue_widget_script( $atts['script_src'] );
    if ( false === $script_info ) {
        return '<div class="ng1-thais-error">' . esc_html__( 'Erreur: script_src manquant pour le widget.', 'ng1-thais' ) . '</div>';
    }

    list( $handle, $script_url ) = $script_info;

    $form_id    = 'ng1-thais-widget-form-' . wp_rand( 1000, 9999 );
    $wrapper_id = 'ng1-thais-widget-preview-' . wp_rand( 1000, 9999 );
    $widget_id  = 'ng1-thais-widget-live-' . wp_rand( 1000, 9999 );

    $fields = [
        'script_src'       => esc_attr( $atts['script_src'] ),
        'instance'         => esc_attr( $atts['instance'] ),
        'lang'             => esc_attr( $atts['lang'] ),
        'width'            => esc_attr( $atts['width'] ),
        'height'           => esc_attr( $atts['height'] ),
        'nb_persons'       => esc_attr( $atts['nb_persons'] ),
        'nb_adults'        => esc_attr( $atts['nb_adults'] ),
        'nb_children'      => esc_attr( $atts['nb_children'] ),
        'nb_infants'       => esc_attr( $atts['nb_infants'] ),
        'nb_months'        => esc_attr( $atts['nb_months'] ),
        'nb_months_mobile' => esc_attr( $atts['nb_months_mobile'] ),
        'promo'            => esc_attr( $atts['promo'] ),
        'start_at'         => esc_attr( $atts['start_at'] ),
        'nb_nights'        => esc_attr( $atts['nb_nights'] ),
    ];

    $data_attrs = [
        'data-instance'         => $fields['instance'],
        'data-lang'             => $fields['lang'],
        'data-nb-persons'       => $fields['nb_persons'],
        'data-nb-adults'        => $fields['nb_adults'],
        'data-nb-children'      => $fields['nb_children'],
        'data-nb-infants'       => $fields['nb_infants'],
        'data-nb-months'        => $fields['nb_months'],
        'data-nb-months-mobile' => $fields['nb_months_mobile'],
        'data-promo'            => $fields['promo'],
        'data-start-at'         => $fields['start_at'],
        'data-nb-nights'        => $fields['nb_nights'],
    ];

    ob_start();
    ?>
    <form id="<?php echo esc_attr( $form_id ); ?>" class="ng1-thais-widget-form" data-script="<?php echo esc_attr( $script_url ); ?>" style="margin-bottom:1.5rem;">
        <div class="ng1-row"><label><?php esc_html_e( 'Script widget (URL)', 'ng1-thais' ); ?></label><input type="url" name="script_src" value="<?php echo $fields['script_src']; ?>" required></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Instance', 'ng1-thais' ); ?></label><input type="text" name="instance" value="<?php echo $fields['instance']; ?>" placeholder="MON_INSTANCE" required></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Langue', 'ng1-thais' ); ?></label><input type="text" name="lang" value="<?php echo $fields['lang']; ?>" placeholder="fr"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Largeur', 'ng1-thais' ); ?></label><input type="text" name="width" value="<?php echo $fields['width']; ?>" placeholder="100%"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Hauteur', 'ng1-thais' ); ?></label><input type="text" name="height" value="<?php echo $fields['height']; ?>" placeholder="auto"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb personnes (total)', 'ng1-thais' ); ?></label><input type="number" min="0" name="nb_persons" value="<?php echo $fields['nb_persons']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb adultes', 'ng1-thais' ); ?></label><input type="number" min="0" name="nb_adults" value="<?php echo $fields['nb_adults']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb enfants', 'ng1-thais' ); ?></label><input type="number" min="0" name="nb_children" value="<?php echo $fields['nb_children']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb bébés', 'ng1-thais' ); ?></label><input type="number" min="0" name="nb_infants" value="<?php echo $fields['nb_infants']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb mois (desktop)', 'ng1-thais' ); ?></label><input type="number" min="1" name="nb_months" value="<?php echo $fields['nb_months']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb mois (mobile)', 'ng1-thais' ); ?></label><input type="number" min="1" name="nb_months_mobile" value="<?php echo $fields['nb_months_mobile']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Code promo', 'ng1-thais' ); ?></label><input type="text" name="promo" value="<?php echo $fields['promo']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Date d’arrivée', 'ng1-thais' ); ?></label><input type="date" name="start_at" value="<?php echo $fields['start_at']; ?>"></div>
        <div class="ng1-row"><label><?php esc_html_e( 'Nb nuits', 'ng1-thais' ); ?></label><input type="number" min="1" name="nb_nights" value="<?php echo $fields['nb_nights']; ?>"></div>
        <div class="ng1-actions"><button type="submit"><?php esc_html_e( 'Afficher le widget', 'ng1-thais' ); ?></button></div>
    </form>
    <div id="<?php echo esc_attr( $wrapper_id ); ?>" class="ng1-thais-widget-preview" style="display:none;">
        <div id="<?php echo esc_attr( $widget_id ); ?>" class="thais_calendar_widget" data-widget="calendar" style="display:none;"
            <?php
            foreach ( $data_attrs as $attr => $value ) {
                if ( '' !== $value ) {
                    echo ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
                }
            }
            if ( '' !== $fields['width'] ) {
                echo ' width="' . esc_attr( $fields['width'] ) . '"';
            }
            if ( '' !== $fields['height'] ) {
                echo ' height="' . esc_attr( $fields['height'] ) . '"';
            }
            ?>
        ></div>
    </div>
    <script>
    (function(){
        var form=document.getElementById('<?php echo esc_js( $form_id ); ?>');
        var wrapper=document.getElementById('<?php echo esc_js( $wrapper_id ); ?>');
        var widget=document.getElementById('<?php echo esc_js( $widget_id ); ?>');
        if(!form||!wrapper||!widget) return;
        var baseScript=form.getAttribute('data-script');
        var initialAttrs={};
        Array.prototype.slice.call(widget.attributes).forEach(function(attr){
            if(attr.name.indexOf('data-')===0 || attr.name==='width' || attr.name==='height'){
                initialAttrs[attr.name]=attr.value;
            }
        });
        var scriptHandles=window.__ng1ThaisWidgetScripts||{};
        window.__ng1ThaisWidgetScripts=scriptHandles;
        function loadScript(url){
            if(!url) return;
            url=url.split('?')[0];
            if(scriptHandles[url]){
                if(window.ThaisWidget && typeof window.ThaisWidget.reset==='function'){
                    try{ window.ThaisWidget.reset(); }catch(e){}
                }
                if(window.ThaisWidget && typeof window.ThaisWidget.init==='function'){
                    try{ window.ThaisWidget.init(); }catch(e){}
                }
                return;
            }
            var s=document.createElement('script');
            s.src=url;
            s.async=true;
            s.onload=function(){
                scriptHandles[url]=true;
                if(window.ThaisWidget && typeof window.ThaisWidget.init==='function'){
                    try{ window.ThaisWidget.init(); }catch(e){}
                }
            };
            document.body.appendChild(s);
        }
        function setAttr(el,name,value){
            if(value===null||value===undefined||value===''){el.removeAttribute(name);}else{el.setAttribute(name,value);}
        }
        function refreshWidget(fd){
            var width=fd.get('width')||initialAttrs['width']||'';
            var height=fd.get('height')||initialAttrs['height']||'';
            var mapping=[
                {attr:'data-instance', field:'instance'},
                {attr:'data-lang', field:'lang'},
                {attr:'data-nb-persons', field:'nb_persons'},
                {attr:'data-nb-adults', field:'nb_adults'},
                {attr:'data-nb-children', field:'nb_children'},
                {attr:'data-nb-infants', field:'nb_infants'},
                {attr:'data-nb-months', field:'nb_months'},
                {attr:'data-nb-months-mobile', field:'nb_months_mobile'},
                {attr:'data-promo', field:'promo'},
                {attr:'data-start-at', field:'start_at'},
                {attr:'data-nb-nights', field:'nb_nights'}
            ];
            var dataAttrs={};
            mapping.forEach(function(item){
                var val=fd.get(item.field);
                if(!val && initialAttrs[item.attr]){ val=initialAttrs[item.attr]; }
                if(val){ dataAttrs[item.attr]=val; }
            });
            Array.prototype.slice.call(widget.attributes).forEach(function(attr){
                if(attr.name.indexOf('data-')===0 || attr.name==='width' || attr.name==='height'){
                    widget.removeAttribute(attr.name);
                }
            });
            widget.setAttribute('data-widget','calendar');
            Object.keys(dataAttrs).forEach(function(attr){
                widget.setAttribute(attr,dataAttrs[attr]);
            });
            if(width){widget.setAttribute('width',width);} if(height){widget.setAttribute('height',height);}
            widget.style.display='block';
            wrapper.style.display='block';
            if(window.ThaisWidget && typeof window.ThaisWidget.reset==='function'){
                try { window.ThaisWidget.reset(); } catch(e){}
            }
            if(window.ThaisWidget && typeof window.ThaisWidget.init==='function'){
                try { window.ThaisWidget.init(); } catch(e){}
            } else {
                loadScript(baseScript);
            }
        }
        form.addEventListener('submit',function(e){
            e.preventDefault();
            var fd=new FormData(form);
            var scriptField=fd.get('script_src');
            if(scriptField && scriptField!==baseScript){
                baseScript=scriptField;
            }
            if(baseScript && baseScript.indexOf('ts=')!==-1){
                baseScript=baseScript.split('?')[0];
            }
            refreshWidget(fd);
        });
        if(baseScript){
            loadScript(baseScript);
        }
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'ng1_thais_widget_form', 'ng1_thais_shortcode_widget_form' );

/**
 * Shortcode: [ng1_thais_reservation_button]
 * Renders a simple reservation button using defaults as fallback.
 */
function ng1_thais_shortcode_reservation_button( $atts ) {
    $defaults = ng1_thais_get_defaults();

    $atts = shortcode_atts( [
        'url'    => $defaults['reservation_url'],
        'label'  => $defaults['reservation_label'],
        'target' => '_self',
        'class'  => 'ng1-thais-button',
    ], $atts, 'ng1_thais_reservation_button' );

    $url = esc_url( $atts['url'] );
    if ( '' === $url ) {
        return '<div class="ng1-thais-error">' . esc_html__( 'Erreur : veuillez définir l’URL de réservation (Réglages → NG1 Thais).', 'ng1-thais' ) . '</div>';
    }

    $label  = $atts['label'] !== '' ? esc_html( $atts['label'] ) : esc_html( $defaults['reservation_label'] );
    $target = in_array( $atts['target'], [ '_self', '_blank' ], true ) ? $atts['target'] : '_self';
    $class  = sanitize_html_class( $atts['class'], 'ng1-thais-button' );

    return '<div class="ng1-thais-reservation"><a class="' . esc_attr( $class ) . '" href="' . $url . '" target="' . esc_attr( $target ) . '" rel="nofollow noopener">' . $label . '</a></div>';
}
add_shortcode( 'ng1_thais_reservation_button', 'ng1_thais_shortcode_reservation_button' );
