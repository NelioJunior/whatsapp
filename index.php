<?php
require_once 'vendor/autoload.php';
use Twilio\TwiML\MessagingResponse;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Captura os dados do Twilio
    $incoming_msg = $_POST['Body'] ?? '';
    $sender_number = $_POST['From'] ?? '';
    
    // Log dos dados recebidos
    error_log("Mensagem recebida: " . $incoming_msg);
    error_log("Número do remetente: " . $sender_number);
    
    // Prepara o payload para a API Flask
    $payload = json_encode([
        'Body' => $incoming_msg,
        'From' => $sender_number
    ]);
    
    // Configura a requisição para a API Flask
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $payload,
            'ignore_errors' => true,
        ],
    ];
    
    try {
        // Faz a requisição para a API Flask
        $context = stream_context_create($options);
        $result = @file_get_contents('http://127.0.0.1:8000/whatsapp', false, $context);        
        
        // Verifica se houve erro na requisição
        if ($result === FALSE) {
            error_log("Erro na requisição: " . error_get_last()['message']);
            throw new Exception('Falha na requisição ao servidor Flask');
        }
        
        // Log da resposta bruta recebida
        error_log("Resposta bruta do Flask: " . $result);
        
        // Como a resposta já está no formato TwiML, podemos retorná-la diretamente
        header('Content-Type: text/xml');
        echo $result;
        
    } catch (Exception $e) {
        // Em caso de erro, envia uma mensagem de erro para o usuário
        $twilio_response = new MessagingResponse();
        $twilio_response->message("Desculpe, ocorreu um erro ao processar sua mensagem. Por favor, tente novamente mais tarde.");
        
        error_log("Erro detalhado: " . $e->getMessage());
        
        header('Content-Type: text/xml');
        echo $twilio_response;
    }
    
} else {
    // Se não for POST, retorna erro
    http_response_code(405);
    echo "Método não permitido";
}
?>
