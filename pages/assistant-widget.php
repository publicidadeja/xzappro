<?php
function zaplocal_assistant_widget() {
    ob_start();
    ?>
    <div id="zaplocal-assistant" class="assistant-widget">
        <button class="assistant-toggle">
            <i class="fas fa-robot"></i>
        </button>
        
        <div class="assistant-container">
            <div class="assistant-header">
                <h3>Assistente de Marketing ZapLocal</h3>
                <button class="close-assistant">×</button>
            </div>
            
            <div class="assistant-chat">
                <div class="chat-messages"></div>
                <div class="chat-input">
                    <textarea placeholder="Como posso ajudar com seu marketing?"></textarea>
                    <button class="send-message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .assistant-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .assistant-toggle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #3547DB;
            color: white;
            border: none;
            cursor: pointer;
        }

        .assistant-container {
            display: none;
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        /* Adicione mais estilos conforme necessário */
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const assistant = {
                init: function() {
                    this.container = document.querySelector('.assistant-container');
                    this.toggle = document.querySelector('.assistant-toggle');
                    this.closeBtn = document.querySelector('.close-assistant');
                    this.chatInput = document.querySelector('.chat-input textarea');
                    this.sendBtn = document.querySelector('.send-message');
                    this.messagesContainer = document.querySelector('.chat-messages');
                    
                    this.bindEvents();
                },

                bindEvents: function() {
                    this.toggle.addEventListener('click', () => this.toggleAssistant());
                    this.closeBtn.addEventListener('click', () => this.toggleAssistant());
                    this.sendBtn.addEventListener('click', () => this.sendMessage());
                },

                toggleAssistant: function() {
                    this.container.style.display = 
                        this.container.style.display === 'none' ? 'block' : 'none';
                },

                sendMessage: async function() {
                    const message = this.chatInput.value.trim();
                    if (!message) return;

                    this.addMessage('user', message);
                    this.chatInput.value = '';

                    try {
                        const response = await fetch('http://localhost:3001/api/assistant', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                prompt: message,
                                context: 'Usuário do ZapLocal buscando ajuda com marketing'
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.addMessage('assistant', data.response);
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                    }
                },

                addMessage: function(type, content) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = `message ${type}`;
                    messageDiv.textContent = content;
                    this.messagesContainer.appendChild(messageDiv);
                    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
                }
            };

            assistant.init();
        });
    </script>
    <?php
    return ob_get_clean();
}

// Shortcode para inserir o assistente
add_shortcode('zaplocal_assistant', 'zaplocal_assistant_widget');
?>