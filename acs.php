<?php
// Define o tipo de conteúdo como texto/xml. O CPE espera isso.
header('Content-Type: text/xml');

// Verifica se a requisição é POST (OBRIGATÓRIO para TR-069)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se não for POST, dá um erro e morre.
    http_response_code(405); // Método não permitido
    die("Apenas requisições POST são permitidas para o ACS.");
}

// =========================================================
// 1. LER O XML/SOAP BRUTO DO CPE
// O XML do SOAP está no corpo da requisição.
// =========================================================
$soap_message = file_get_contents('php://input');

// Se a mensagem estiver vazia, o CPE não enviou nada.
if (empty($soap_message)) {
    http_response_code(400); // Requisição inválida
    die("Corpo da requisição SOAP vazio.");
}

// =========================================================
// 2. LOGGING INICIAL (IMPORTANTE PARA DEBUG!)
// Apenas para vermos que o CPE conectou
// =========================================================
$log_file = __DIR__ . '/log_cpe.txt';
$timestamp = date("Y-m-d H:i:s");
$log_entry = "[$timestamp] Requisição recebida. IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$log_entry .= "Conteúdo SOAP:\n" . $soap_message . "\n";
$log_entry .= "========================================================\n";

// Salva o log. O usuário www-data precisa de permissão de escrita!
error_log($log_entry, 3, $log_file);

// =========================================================
// 3. RETORNO PARA O CPE (AINDA NÃO É UM SOAP VÁLIDO, SÓ UM TESTE)
// Um ACS sempre deve retornar um XML/SOAP para o CPE.
// Por enquanto, vamos retornar uma mensagem vazia (mas XML válido).
// =========================================================
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
echo '<soap-env:Envelope xmlns:soap-env="http://schemas.xmlsoap.org/soap/envelope/" />';

// Fim do script. O CPE vai receber a resposta XML acima.
?>
