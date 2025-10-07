<?php
// Inclui o modelo da classe CPE
require_once __DIR__ . '/src/CPE.php';

use CyberACS\CPE;

// Define o cabeçalho de resposta (text/xml)
header('Content-Type: text/xml; charset="utf-8"');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  die("Apenas POST.");
}

$soap_message = file_get_contents('php://input');

// --- Início da Extração de Dados (DOMDocument para iniciantes) ---

$doc = new DOMDocument();
// A @ desabilita erros de XML mal formatado, mas não recomendado em produção.
@$doc->loadXML($soap_message);

// Busca as tags de DeviceId e Inform
$deviceIdNode = $doc->getElementsByTagName('DeviceId')->item(0);
$informNode = $doc->getElementsByTagName('Inform')->item(0);
$paramsList = $doc->getElementsByTagName('ParameterValueStruct');


if ($deviceIdNode && $informNode) {
  // 1. Extração de Identificadores (DeviceId)
  $sn = $deviceIdNode->getElementsByTagName('SerialNumber')->item(0)->nodeValue ?? 'N/A';
  $manu = $deviceIdNode->getElementsByTagName('Manufacturer')->item(0)->nodeValue ?? 'N/A';
  $oui = $deviceIdNode->getElementsByTagName('OUI')->item(0)->nodeValue ?? 'N/A';
  $pc = $deviceIdNode->getElementsByTagName('ProductClass')->item(0)->nodeValue ?? 'N/A';

  // 2. Extração de Parâmetros (versão de hardware)
  $hwVersion = 'Desconhecida';
  foreach ($paramsList as $paramStruct) {
    $name = $paramStruct->getElementsByTagName('Name')->item(0)->nodeValue;
    if ($name === 'InternetGatewayDevice.DeviceInfo.HardwareVersion') {
      $hwVersion = $paramStruct->getElementsByTagName('Value')->item(0)->nodeValue;
      break;
    }
  }

  // 3. Criação do Objeto CPE
  $cpe = new CPE($sn, $manu, $oui, $pc, $hwVersion);

  // --- Lógica de Negócio (Futuro) ---
  // if (!$cpe->existsInDB()) { $cpe->save(); } 

  // Log detalhado (apenas para debug)
  error_log("CPE Identificado: " . $cpe->getInfo() . "\n", 3, __DIR__ . '/log_cpe.txt');
} else {
  // Se não encontrou as tags esperadas...
  error_log("Erro: Mensagem SOAP não contém Inform ou DeviceId.\n", 3, __DIR__ . '/log_cpe.txt');
}

// --- Fim da Extração de Dados ---


// 4. Montar o XML de Resposta (InformResponse)
// O ACS deve sempre responder a um Inform com um InformResponse
$response_id = $doc->getElementsByTagName('ID')->item(0)->nodeValue ?? '1';

$xml_response = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
$xml_response .= '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" '
  . 'xmlns:cwmp="urn:dslforum-org:cwmp-1-0">' . "\n";
$xml_response .= '  <soap-env:Header>' . "\n";
$xml_response .= '    <cwmp:ID soap-env:mustUnderstand="1">' . $response_id . '</cwmp:ID>' . "\n";
$xml_response .= '  </soap-env:Header>' . "\n";
$xml_response .= '  <soap-env:Body>' . "\n";
$xml_response .= '    <cwmp:InformResponse>' . "\n";
$xml_response .= '      <MaxEnvelopes>1</MaxEnvelopes>' . "\n"; // Diz que queremos apenas 1 mensagem por sessão
$xml_response .= '    </cwmp:InformResponse>' . "\n";
$xml_response .= '  </soap-env:Body>' . "\n";
$xml_response .= '</soap-env:Envelope>';

echo $xml_response;

// Log final (pode ser apagado depois)
error_log("Resposta SOAP enviada para o CPE.\n", 3, __DIR__ . '/log_cpe.txt');
