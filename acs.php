<?php
// Define o timezone para o log e timestamps do DB (Importante!)
date_default_timezone_set('America/Sao_Paulo');

// Inclui os modelos de classe (POO)
require_once __DIR__ . '/src/CPE.php';
require_once __DIR__ . '/src/DBManager.php';

use CyberACS\CPE;
use CyberACS\DBManager; // Não é usado diretamente aqui, mas é exigido pela CPE

// =========================================================
// 1. CHECAGEM INICIAL E LEITURA DA REQUISIÇÃO
// =========================================================

// Define o cabeçalho de resposta (text/xml) - OBRIGATÓRIO para TR-069
header('Content-Type: text/xml; charset="utf-8"');

// O TR-069 SÓ USA POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  die("Apenas requisições POST são permitidas para o ACS.");
}

$soap_message = file_get_contents('php://input');

// Log inicial (sempre bom ter o RAW XML)
$log_entry = "[" . date("Y-m-d H:i:s") . "] Requisição recebida. IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$log_entry .= "Conteúdo SOAP:\n" . $soap_message . "\n";
error_log($log_entry, 3, __DIR__ . '/log_cpe.txt');


// =========================================================
// 2. EXTRAÇÃO DE DADOS DO XML (DOMDocument)
// =========================================================

$doc = new \DOMDocument();
// O '@' desabilita warnings de XML (alguns CPEs são chatos).
@$doc->loadXML($soap_message);

$deviceIdNode = $doc->getElementsByTagName('DeviceId')->item(0);
$informNode = $doc->getElementsByTagName('Inform')->item(0);
$paramsList = $doc->getElementsByTagName('ParameterValueStruct');


if ($deviceIdNode && $informNode) {
  // --- Extração dos dados principais (DeviceId) ---
  $sn = $deviceIdNode->getElementsByTagName('SerialNumber')->item(0)->nodeValue ?? 'N/A';
  $manu = $deviceIdNode->getElementsByTagName('Manufacturer')->item(0)->nodeValue ?? 'N/A';
  $oui = $deviceIdNode->getElementsByTagName('OUI')->item(0)->nodeValue ?? 'N/A';
  $pc = $deviceIdNode->getElementsByTagName('ProductClass')->item(0)->nodeValue ?? 'N/A';

  // --- Extração de um Parâmetro Específico (HardwareVersion) ---
  $hwVersion = 'Desconhecida';
  foreach ($paramsList as $paramStruct) {
    $name = $paramStruct->getElementsByTagName('Name')->item(0)->nodeValue;
    if ($name === 'InternetGatewayDevice.DeviceInfo.HardwareVersion') {
      $hwVersion = $paramStruct->getElementsByTagName('Value')->item(0)->nodeValue;
      break;
    }
  }

  // =========================================================
  // 3. LÓGICA DE NEGÓCIO (POO + PERSISTÊNCIA NO DB)
  // =========================================================

  try {
    // Criação do Objeto CPE
    $cpe = new CPE($sn, $manu, $oui, $pc, $hwVersion);

    // Salva/Atualiza no MariaDB
    if ($cpe->save()) {
      error_log("[" . date("Y-m-d H:i:s") . "] CPE Salvo/Atualizado no DB: " . $cpe->getInfo() . "\n", 3, __DIR__ . '/log_cpe.txt');
    } else {
      // Em caso de erro de execução (raro se a conexão funcionou)
      error_log("[" . date("Y-m-d H:i:s") . "] ERRO AO SALVAR CPE no DB.\n", 3, __DIR__ . '/log_cpe.txt');
    }
  } catch (\Throwable $e) {
    // Erro geral (ex: Falha de conexão com o DB)
    error_log("[" . date("Y-m-d H:i:s") . "] ERRO FATAL: " . $e->getMessage() . "\n", 3, __DIR__ . '/log_cpe.txt');
    // Para o CPE não ficar tentando infinitamente, a gente responde de forma vazia ou com um erro 500
    http_response_code(500);
    die();
  }
} else {
  // Se não encontrou as tags Inform ou DeviceId
  error_log("[" . date("Y-m-d H:i:s") . "] Erro: Mensagem SOAP não contém Inform ou DeviceId.\n", 3, __DIR__ . '/log_cpe.txt');
}


// =========================================================
// 4. MONTAR E ENVIAR RESPOSTA (InformResponse)
// =========================================================

// Tenta pegar o ID da requisição original, senão usa '1'
$response_id = $doc->getElementsByTagName('ID')->item(0)->nodeValue ?? '1';

$xml_response = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
$xml_response .= '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" '
  . 'xmlns:cwmp="urn:dslforum-org:cwmp-1-0">' . "\n";
$xml_response .= '  <soap-env:Header>' . "\n";
$xml_response .= '    <cwmp:ID soap-env:mustUnderstand="1">' . $response_id . '</cwmp:ID>' . "\n";
$xml_response .= '  </soap-env:Header>' . "\n";
$xml_response .= '  <soap-env:Body>' . "\n";
$xml_response .= '    <cwmp:InformResponse>' . "\n";
$xml_response .= '      <MaxEnvelopes>1</MaxEnvelopes>' . "\n";
$xml_response .= '    </cwmp:InformResponse>' . "\n";
$xml_response .= '  </soap-env:Body>' . "\n";
$xml_response .= '</soap-env:Envelope>';

echo $xml_response;

error_log("[" . date("Y-m-d H:i:s") . "] Resposta SOAP enviada para o CPE.\n", 3, __DIR__ . '/log_cpe.txt');
