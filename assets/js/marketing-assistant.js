document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('marketing-assistant');
    const toggleBtn = widget.querySelector('.toggle-widget');
    const chatArea = widget.querySelector('.chat-area');
    const textarea = widget.querySelector('textarea');
    const sendBtn = widget.querySelector('.send-button');
    
    toggleBtn.addEventListener('click', function() {
        const content = widget.querySelector('.widget-content');
        content.style.display = content.style.display === 'none' ? 'flex' : 'none';
        toggleBtn.querySelector('i').classList.toggle('fa-chevron-up');
        toggleBtn.querySelector('i').classList.toggle('fa-chevron-down');
    });
    
    sendBtn.addEventListener('click', async function() {
        const question = textarea.value.trim();
        if (!question) return;
        
        // Adiciona a pergunta do usuário ao chat
        appendMessage('user', question);
        textarea.value = '';
        
        try {
            const response = await fetch('ajax-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_marketing_advice',
                    question: question
                })
            });
            
            const data = await response.json();
            appendMessage('assistant', data.response);
        } catch (error) {
            console.error('Erro:', error);
            appendMessage('assistant', 'Desculpe, ocorreu um erro ao processar sua solicitação.');
        }
    });
    
    function appendMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}`;
        messageDiv.textContent = content;
        chatArea.appendChild(messageDiv);
        chatArea.scrollTop = chatArea.scrollHeight;
    }
});