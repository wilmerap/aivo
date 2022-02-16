<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require '../../vendor/autoload.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// Create and configure Slim app
$config = ['settings' => [
    'addContentLengthHeader' => false,
]];

$app = new \Slim\App($config);

$app->get('/api/v1/albums/{artists}', function (Request $request, Response $response, array $args) {
    $artists = urlencode($args['artists']);

    /** Inicio - Obtener Token de Acceso de la APP Spotify **/
    $curl_token = curl_init();

    curl_setopt_array($curl_token, array(
    CURLOPT_URL => 'https://accounts.spotify.com/api/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ZDMzNTMwZDdhNWJhNGI5NWExZThhZGFiYzBlNmI3NjQ6MjFlNWFlYWY3Njk1NDc0NWFhM2FiMzM3MjY4YTgzOWQ='
    ),
    ));

    $response_token = curl_exec($curl_token);

    curl_close($curl_token);

    $items_token = json_decode($response_token, true);
    /** Fin - Obtener Token de Acceso de la APP Spotify **/

    /** Inicio - Buscador por Nombre de la Banda o Artista*/
    $curl_artist = curl_init();

    curl_setopt_array($curl_artist, array(
    CURLOPT_URL => 'https://api.spotify.com/v1/search?q='.$artists.'&type=artist',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.$items_token['access_token']
    ),
    ));

    $response_artist = curl_exec($curl_artist);

    curl_close($curl_artist);

    $items_artist = json_decode($response_artist, true);
    /** Fin - Buscador por Nombre de la Banda o Artista */

    /** ID del Artista */
    $id_artist = $items_artist['artists']['items'][0]['id'];

    /** Inicio - Consulta de Albunes del Artista APP Spotify */
    $curl_albums = curl_init();

    curl_setopt_array($curl_albums, array(
      CURLOPT_URL => 'https://api.spotify.com/v1/artists/'.$id_artist.'/albums',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer '.$items_token['access_token']
      ),
    ));

    $response_albums = curl_exec($curl_albums);

    curl_close($curl_albums);

    $items_artist = json_decode($response_albums, true);
    /** Fin - Consulta de Albunes del Artista APP Spotify */

    // Array de Listado de Albunes
    $album = array();
    $list_albums = array();

    foreach ($items_artist['items'] as $ia) {
        /**  Listado de Albunes  **/
        $album = [
            'name' => $ia['name'],
            'released' => $ia['release_date'],
            'tracks' => $ia['total_tracks'],
            'cover' => array('height' => $ia['images'][0]['height'], 'width' => $ia['images'][0]['width'], 'url' => $ia['images'][0]['url']),
            ];
          $list_albums[] = $album;

    }

    $result = $response->withHeader('Content-type', 'application/json')->withJson($list_albums);
    return $result;
});
$app->run();


?>