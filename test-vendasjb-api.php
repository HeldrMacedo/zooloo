<?php
/**
 * Teste manual dos endpoints migrados de VendasJb (Java -> Adianti REST).
 */

require_once 'rest/request.php';

echo "<h1>Teste API VendasJb (Adianti)</h1>";

$base_url = 'http://localhost/zooloo/rest.php';
$token    = ''; // opcional: preencha com um Bearer token válido se o endpoint exigir autenticação
$auth     = $token ? 'Bearer ' . $token : null;

function callApi($base_url, $params, $auth = null)
{
    echo "<h3>{$params['method']}</h3>";
    try {
        $result = request($base_url, 'POST', $params, $auth);
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Erro: {$e->getMessage()}</p>";
    }
}

callApi($base_url, [
    'class'      => 'VendasJbRestService',
    'method'     => 'relVendasJb',
    'datainicio' => date('Y-m-d'),
    'datafim'    => date('Y-m-d'),
    'combo'      => 0,
], $auth);

callApi($base_url, [
    'class'  => 'VendasJbRestService',
    'method' => 'getNsu',
    'nsu'    => 1,
], $auth);

callApi($base_url, [
    'class'  => 'VendasJbRestService',
    'method' => 'pegarDetalhesVendas',
    'id'     => 0,
], $auth);
