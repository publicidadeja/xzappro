<?php
class MarketingAssistant {
    private $anthropic_key;
    private $base_url = 'https://api.anthropic.com/v1/messages';
    
    public function __construct($anthropic_key) {
        $this->anthropic_key = $anthropic_key;
    }
    
    public function getMarketingAdvice($prompt) {
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->anthropic_key,
            'anthropic-version: 2023-06-01'
        ];
        
        $data = [
            'model' => 'claude-3-opus-20240229',
            'max_tokens' => 1000,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];
        
        $ch = curl_init($this->base_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

function marketing_assistant_widget() {
    $html = '
    <div id="marketing-assistant" class="marketing-widget">
        <div class="widget-header">
            <h3><i class="fas fa-robot"></i> Assistente de Marketing ZapLocal</h3>
            <button class="toggle-widget"><i class="fas fa-chevron-up"></i></button>
        </div>
        <div class="widget-content">
            <div class="chat-area"></div>
            <div class="input-area">
                <textarea placeholder="Faça uma pergunta sobre marketing..."></textarea>
                <button class="send-question"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    ';
    
    return $html;
}

// Shortcode para incluir o widget
function register_marketing_assistant_shortcode() {
    return marketing_assistant_widget();
}
add_shortcode('marketing_assistant', 'register_marketing_assistant_shortcode');
?>