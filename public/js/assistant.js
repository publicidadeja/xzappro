// public/js/assistant.js
class ZapLocalAssistantUI {
    constructor() {
        this.initializeElements();
        this.bindEvents();
    }

    initializeElements() {
        this.widget = document.getElementById('zapLocalAssistant');
        this.messagesContainer = document.getElementById('chatMessages');
        this.messageInput = document.getElementById('userMessage');
        this.sendButton = document.getElementById('sendMessage');
        this.toggleButton = document.getElementById('toggleAssistant');
    }

    bindEvents() {
        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        this.toggleButton.addEventListener('click', () => this.toggleWidget());
    }

    async sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;

        this.addMessage(message, 'user');
        this.messageInput.value = '';

        try {
            const response = await fetch('/api/assistant/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    message: message,
                    businessContext: window.businessContext // Definido globalmente
                })
            });

            const data = await response.json();
            this.addMessage(data.response, 'assistant');
        } catch (error) {
            console.error('Erro ao enviar mensagem:', error);
            this.addMessage('Desculpe, ocorreu um erro ao processar sua mensagem.', 'assistant');
        }
    }

    addMessage(message, type) {
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}-message`;
        messageElement.textContent = message;
        this.messagesContainer.appendChild(messageElement);
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }

    toggleWidget() {
        this.widget.classList.toggle('collapsed');
    }
}

// Inicializar o assistente quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.zapLocalAssistant = new ZapLocalAssistantUI();
});