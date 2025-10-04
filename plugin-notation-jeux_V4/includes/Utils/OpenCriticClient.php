<?php
/**
 * Lightweight client to communicate with the OpenCritic API.
 *
 * @package JLG_Notation\Utils
 */

namespace JLG\Notation\Utils;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

class OpenCriticClient {
private const DEFAULT_API_BASE   = 'https://api.opencritic.com/api';
private const CACHE_PREFIX       = 'jlg_opencritic_';
private const DEFAULT_SEARCH_TTL = 3;
private const DEFAULT_GAME_TTL   = 12;

/**
 * API key provided by the site owner.
 *
 * @var string
 */
private $api_key = '';

/**
 * Indicates whether the client is running in mock mode.
 *
 * @var bool
 */
private $mock_mode = false;

/**
 * Base URL for the API.
 *
 * @var string
 */
private $api_base = '';

/**
 * Constructor.
 *
 * @param string $api_key Optional API key to authenticate requests.
 */
public function __construct( $api_key = '' ) {
$this->api_key   = is_string( $api_key ) ? trim( (string) $api_key ) : '';
$this->mock_mode = $this->api_key === '';
$default_base    = apply_filters( 'jlg_opencritic_api_base', self::DEFAULT_API_BASE );
$this->api_base  = is_string( $default_base ) && $default_base !== ''
? rtrim( $default_base, '/' )
: self::DEFAULT_API_BASE;
}

/**
 * Indicates whether the client operates with mock data.
 *
 * @return bool
 */
public function is_mock_mode() {
return $this->mock_mode;
}

/**
 * Searches the OpenCritic catalogue for games matching the provided query.
 *
 * @param string $query Search keywords.
 * @param int    $limit Maximum number of results to return.
 *
 * @return array<int, array<string, mixed>>|WP_Error
 */
public function search_games( $query, $limit = 8 ) {
$query = is_string( $query ) ? trim( wp_unslash( $query ) ) : '';
$limit = (int) $limit;

if ( $limit <= 0 ) {
$limit = 8;
}

$limit = min( max( 1, $limit ), 20 );

if ( $query === '' ) {
return new WP_Error( 'opencritic_invalid_query', __( 'La requête de recherche est vide.', 'notation-jlg' ) );
}

$cache_key = $this->build_cache_key( 'search', array( $query, $limit ) );
$cached    = get_transient( $cache_key );

if ( $cached !== false && is_array( $cached ) ) {
return $cached;
}

if ( $this->mock_mode ) {
$mock = $this->generate_mock_results( $query, $limit );
$this->set_transient( $cache_key, $mock, self::DEFAULT_SEARCH_TTL, 'search' );

return $mock;
}

if ( ! function_exists( 'wp_remote_get' ) ) {
return new WP_Error( 'opencritic_http_unavailable', __( 'La fonction HTTP de WordPress est indisponible.', 'notation-jlg' ) );
}

$endpoint = trailingslashit( $this->api_base ) . 'game/search';
$url      = add_query_arg(
array(
'criteria' => rawurlencode( $query ),
'limit'    => $limit,
),
$endpoint
);

$args = apply_filters(
'jlg_opencritic_http_request_args',
array(
'timeout' => 10,
'headers' => $this->build_headers(),
),
'game/search',
$query
);

$response = wp_remote_get( $url, $args );

if ( is_wp_error( $response ) ) {
return $response;
}

$status_code = (int) wp_remote_retrieve_response_code( $response );

if ( $status_code < 200 || $status_code >= 300 ) {
return new WP_Error(
'opencritic_http_error',
sprintf(
/* translators: %d: HTTP status code returned by the OpenCritic API. */
__( 'La requête OpenCritic a échoué avec le code %d.', 'notation-jlg' ),
$status_code
)
);
}

$body    = wp_remote_retrieve_body( $response );
$decoded = json_decode( $body, true );

if ( ! is_array( $decoded ) ) {
return new WP_Error( 'opencritic_invalid_payload', __( 'Réponse OpenCritic invalide : JSON non valide.', 'notation-jlg' ) );
}

$normalized = $this->normalize_search_payload( $decoded );

$this->set_transient( $cache_key, $normalized, self::DEFAULT_SEARCH_TTL, 'search' );

return $normalized;
}

/**
 * Retrieves detailed information for a specific game.
 *
 * @param int|string $game_id OpenCritic numeric identifier.
 *
 * @return array<string, mixed>|WP_Error
 */
public function get_game_details( $game_id ) {
$identifier = $this->sanitize_identifier( $game_id );

if ( $identifier <= 0 ) {
return new WP_Error( 'opencritic_invalid_identifier', __( 'Identifiant OpenCritic invalide.', 'notation-jlg' ) );
}

$cache_key = $this->build_cache_key( 'game', $identifier );
$cached    = get_transient( $cache_key );

if ( $cached !== false && is_array( $cached ) ) {
return $cached;
}

if ( $this->mock_mode ) {
$mock = $this->generate_mock_game( $identifier );
$this->set_transient( $cache_key, $mock, self::DEFAULT_GAME_TTL, 'game' );

return $mock;
}

if ( ! function_exists( 'wp_remote_get' ) ) {
return new WP_Error( 'opencritic_http_unavailable', __( 'La fonction HTTP de WordPress est indisponible.', 'notation-jlg' ) );
}

$endpoint = sprintf( '%s/game/%d', rtrim( $this->api_base, '/' ), $identifier );
$args     = apply_filters(
'jlg_opencritic_http_request_args',
array(
'timeout' => 10,
'headers' => $this->build_headers(),
),
'game',
$identifier
);

$response = wp_remote_get( $endpoint, $args );

if ( is_wp_error( $response ) ) {
return $response;
}

$status_code = (int) wp_remote_retrieve_response_code( $response );

if ( $status_code < 200 || $status_code >= 300 ) {
return new WP_Error(
'opencritic_http_error',
sprintf(
/* translators: %d: HTTP status code returned by the OpenCritic API. */
__( 'La requête OpenCritic a échoué avec le code %d.', 'notation-jlg' ),
$status_code
)
);
}

$body    = wp_remote_retrieve_body( $response );
$decoded = json_decode( $body, true );

if ( ! is_array( $decoded ) ) {
return new WP_Error( 'opencritic_invalid_payload', __( 'Réponse OpenCritic invalide : JSON non valide.', 'notation-jlg' ) );
}

$normalized = $this->normalize_game_entry( $decoded );

if ( empty( $normalized ) ) {
return new WP_Error( 'opencritic_game_not_found', __( 'Jeu introuvable sur OpenCritic.', 'notation-jlg' ) );
}

$this->set_transient( $cache_key, $normalized, self::DEFAULT_GAME_TTL, 'game' );

return $normalized;
}

/**
 * Builds the public URL for a specific game.
 *
 * @param array<string, mixed> $game Game payload.
 * @return string
 */
public function build_game_url( $game ) {
$identifier = 0;
$slug       = '';

if ( is_array( $game ) ) {
if ( isset( $game['id'] ) ) {
$identifier = $this->sanitize_identifier( $game['id'] );
}

if ( isset( $game['slug'] ) && is_string( $game['slug'] ) ) {
$slug = sanitize_title( $game['slug'] );
}
}

if ( $identifier <= 0 ) {
return '';
}

$url = sprintf( 'https://opencritic.com/game/%d', $identifier );

if ( $slug !== '' ) {
$url .= '/' . rawurlencode( $slug );
}

return apply_filters( 'jlg_opencritic_game_url', $url, $identifier, $slug, $game );
}

/**
 * Normalizes the payload returned by the API for search queries.
 *
 * @param array<string, mixed> $payload Raw decoded payload.
 *
 * @return array<int, array<string, mixed>>
 */
private function normalize_search_payload( array $payload ) {
$entries = array();

if ( isset( $payload['results'] ) && is_array( $payload['results'] ) ) {
$entries = $payload['results'];
} elseif ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
$entries = $payload['items'];
} else {
$entries = $payload;
}

$normalized = array();

foreach ( $entries as $entry ) {
if ( ! is_array( $entry ) ) {
continue;
}

$normalized_entry = $this->normalize_game_entry( $entry );

if ( empty( $normalized_entry ) ) {
continue;
}

$normalized[] = $normalized_entry;
}

return $normalized;
}

/**
 * Normalizes a single game entry.
 *
 * @param array<string, mixed> $entry Raw game entry.
 *
 * @return array<string, mixed>
 */
private function normalize_game_entry( array $entry ) {
$id = 0;

if ( isset( $entry['id'] ) ) {
$id = $this->sanitize_identifier( $entry['id'] );
}

if ( $id <= 0 ) {
return array();
}

$name = '';
if ( isset( $entry['name'] ) && is_string( $entry['name'] ) ) {
$name = sanitize_text_field( $entry['name'] );
}

$slug = '';
if ( isset( $entry['slug'] ) && is_string( $entry['slug'] ) ) {
$slug = sanitize_title( $entry['slug'] );
} elseif ( $name !== '' ) {
$slug = sanitize_title( $name );
}

$score = null;
if ( isset( $entry['topCriticScore'] ) ) {
$score = $this->normalize_score( $entry['topCriticScore'] );
} elseif ( isset( $entry['score'] ) ) {
$score = $this->normalize_score( $entry['score'] );
}

$tier = '';
if ( isset( $entry['tier'] ) && is_string( $entry['tier'] ) ) {
$tier = sanitize_text_field( $entry['tier'] );
}

$release_date = '';
$release_year = null;

if ( isset( $entry['firstReleaseDate'] ) ) {
$release_date = $this->normalize_date( $entry['firstReleaseDate'] );
}

if ( isset( $entry['first_release_date'] ) && $release_date === '' ) {
$release_date = $this->normalize_date( $entry['first_release_date'] );
}

if ( isset( $entry['releaseDate'] ) && $release_date === '' ) {
$release_date = $this->normalize_date( $entry['releaseDate'] );
}

if ( $release_date !== '' ) {
$timestamp = strtotime( $release_date );
if ( $timestamp ) {
$release_year = (int) gmdate( 'Y', $timestamp );
}
}

$url = $this->build_game_url(
array(
'id'   => $id,
'slug' => $slug,
)
);

return array(
'id'                => $id,
'name'              => $name,
'slug'              => $slug,
'topCriticScore'    => $score,
'tier'              => $tier,
'firstReleaseDate'  => $release_date,
'releaseYear'       => $release_year,
'url'               => $url,
'is_mock'           => $this->mock_mode,
);
}

/**
 * Normalizes date strings accepted by the API.
 *
 * @param mixed $value Raw value.
 *
 * @return string
 */
private function normalize_date( $value ) {
if ( is_numeric( $value ) ) {
$timestamp = (int) $value;
if ( $timestamp > 0 ) {
return gmdate( 'Y-m-d', $timestamp );
}
}

if ( is_string( $value ) && $value !== '' ) {
$value = trim( $value );
if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
return $value;
}

$timestamp = strtotime( $value );
if ( $timestamp ) {
return gmdate( 'Y-m-d', $timestamp );
}
}

return '';
}

/**
 * Normalizes a score value (0-100 range expected).
 *
 * @param mixed $value Raw value.
 *
 * @return float|null
 */
private function normalize_score( $value ) {
if ( is_array( $value ) ) {
return null;
}

if ( $value === null || $value === '' ) {
return null;
}

if ( is_string( $value ) ) {
$value = str_replace( ',', '.', $value );
}

if ( ! is_numeric( $value ) ) {
return null;
}

$score = (float) $value;
if ( $score < 0 ) {
$score = 0.0;
}

if ( $score > 100 ) {
$score = 100.0;
}

return round( $score, 1 );
}

/**
 * Builds HTTP headers for API requests.
 *
 * @return array<string, string>
 */
private function build_headers() {
$headers = array(
'Accept'     => 'application/json',
'User-Agent' => 'JLG-Notation/5.0 (+https://opencritic.com)',
);

if ( $this->api_key !== '' ) {
$headers['X-API-Key'] = $this->api_key;
}

return apply_filters( 'jlg_opencritic_request_headers', $headers, $this->api_key );
}

/**
 * Generates mock search results when no API key is configured.
 *
 * @param string $query Search query.
 * @param int    $limit Number of results.
 *
 * @return array<int, array<string, mixed>>
 */
private function generate_mock_results( $query, $limit ) {
$results = array();

for ( $index = 0; $index < $limit; $index++ ) {
$identifier = 1000 + $index + wp_rand( 0, 50 );
$name       = sprintf( '%s – Mock %d', $query, $index + 1 );
$score      = 60 + ( $index * 5 ) % 30;

$results[] = array(
'id'               => $identifier,
'name'             => $name,
'slug'             => sanitize_title( $name ),
'topCriticScore'   => (float) $score,
'tier'             => 'Mock',
'firstReleaseDate' => gmdate( 'Y-m-d', strtotime( '-' . ( $index * 120 ) . ' days' ) ),
'releaseYear'      => (int) gmdate( 'Y', strtotime( '-' . ( $index * 120 ) . ' days' ) ),
'url'              => $this->build_game_url(
array(
'id'   => $identifier,
'slug' => sanitize_title( $name ),
)
),
'is_mock'          => true,
);
}

return $results;
}

/**
 * Generates a mock game payload.
 *
 * @param int $identifier Game identifier.
 *
 * @return array<string, mixed>
 */
private function generate_mock_game( $identifier ) {
$name  = sprintf( 'Mock Game %d', $identifier );
$score = 75 + ( $identifier % 15 );

return array(
'id'               => $identifier,
'name'             => $name,
'slug'             => sanitize_title( $name ),
'topCriticScore'   => (float) $score,
'tier'             => 'Mock',
'firstReleaseDate' => gmdate( 'Y-m-d', strtotime( '-180 days' ) ),
'releaseYear'      => (int) gmdate( 'Y', strtotime( '-180 days' ) ),
'url'              => $this->build_game_url(
array(
'id'   => $identifier,
'slug' => sanitize_title( $name ),
)
),
'is_mock'          => true,
);
}

/**
 * Creates a cache key for the given parameters.
 *
 * @param string       $type Identifier for the cache segment.
 * @param int|array    $data Data to hash.
 *
 * @return string
 */
private function build_cache_key( $type, $data ) {
$hash = md5( wp_json_encode( array( $type, $data ) ) );

return self::CACHE_PREFIX . $hash;
}

/**
 * Stores the provided value inside a transient.
 *
 * @param string $cache_key Cache key.
 * @param mixed  $value     Cached value.
 * @param int    $fallback  Default TTL in hours.
 * @param string $type      Cache segment type.
 */
private function set_transient( $cache_key, $value, $fallback, $type ) {
$hours = (int) apply_filters( 'jlg_opencritic_cache_hours', $fallback, $type, $cache_key );

if ( $hours <= 0 ) {
return;
}

$expiration = $hours * ( defined( 'HOUR_IN_SECONDS' ) ? HOUR_IN_SECONDS : 3600 );
set_transient( $cache_key, $value, $expiration );
}

/**
 * Sanitizes a game identifier.
 *
 * @param mixed $identifier Raw identifier.
 *
 * @return int
 */
private function sanitize_identifier( $identifier ) {
if ( is_numeric( $identifier ) ) {
return absint( $identifier );
}

if ( is_string( $identifier ) ) {
$identifier = preg_replace( '/[^0-9]/', '', $identifier );
return absint( $identifier );
}

return 0;
}
}
