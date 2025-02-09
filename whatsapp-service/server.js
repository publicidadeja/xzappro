const express = require('express');
const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const mysql = require('mysql2/promise');
const path = require('path');
const fs = require('fs');
const app = express();

app.use(express.json());

// Configuração CORS
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
    if (req.method === 'OPTIONS') {
        return res.sendStatus(200);
    }
    next();
});

// Configuração do banco de dados
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'balcao'
};

// Criar diretório para armazenar sessões
const SESSION_DIR = path.join(__dirname, '.wwebjs_auth');
if (!fs.existsSync(SESSION_DIR)) {
    fs.mkdirSync(SESSION_DIR, { recursive: true });
}

// Armazenar clientes ativos
const clients = new Map();

// Função para limpar sessão antiga
async function clearSession(deviceId) {
    const sanitizedDeviceId = sanitizeDeviceId(deviceId);
    const sessionDir = path.join(SESSION_DIR, `session-${sanitizedDeviceId}`);
    if (fs.existsSync(sessionDir)) {
        fs.rmSync(sessionDir, { recursive: true, force: true });
    }
}

app.post('/process-queue', async (req, res) => {
    const { usuario_id, dispositivo_id } = req.body;
    console.log('Processando fila para usuário:', usuario_id);

    try {
        const client = clients.get(dispositivo_id);
        if (!client) {
            throw new Error('Dispositivo não encontrado ou não conectado');
        }

        const connection = await mysql.createConnection(dbConfig);
        
        // Buscar mensagens pendentes em lotes menores
        const [messages] = await connection.execute(
            'SELECT * FROM fila_mensagens WHERE usuario_id = ? AND status = "PENDENTE" LIMIT 10',
            [usuario_id]
        );

        console.log('Mensagens encontradas:', messages.length);

        for (const message of messages) {
            try {
                // Formatar número corretamente
                let formattedNumber = message.numero.replace(/\D/g, '');
                if (!formattedNumber.startsWith('55')) {
                    formattedNumber = '55' + formattedNumber;
                }
                formattedNumber = `${formattedNumber}@c.us`;

                // Verificar se o número é válido
                const isRegistered = await client.isRegisteredUser(formattedNumber);
                if (!isRegistered) {
                    throw new Error('Número não registrado no WhatsApp');
                }

                // Enviar mensagem
                await client.sendMessage(formattedNumber, message.mensagem);

                // Atualizar status
                await connection.execute(
                    'UPDATE fila_mensagens SET status = "ENVIADO", updated_at = NOW() WHERE id = ?',
                    [message.id]
                );

                await connection.execute(
                    'UPDATE leads_enviados SET status = "ENVIADO" WHERE numero = ? AND usuario_id = ?',
                    [message.numero, usuario_id]
                );

                // Intervalo entre mensagens (entre 3 e 8 segundos)
                await new Promise(resolve => setTimeout(resolve, Math.random() * 5000 + 3000));

            } catch (error) {
                console.error('Erro ao enviar mensagem:', error);
                await connection.execute(
                    'UPDATE fila_mensagens SET status = "ERRO", error_message = ? WHERE id = ?',
                    [error.message.substring(0, 255), message.id]
                );
            }
        }

        await connection.end();
        res.json({ success: true });
    } catch (error) {
        console.error('Erro ao processar fila:', error);
        res.status(500).json({ success: false, error: error.message });
    }
});


app.post('/send-message', async (req, res) => {
    const { deviceId, number, message, mediaPath } = req.body;

    try {
        const client = clients.get(deviceId);
        if (!client) {
            throw new Error('Dispositivo não encontrado ou não conectado');
        }

        let formattedNumber = number;
        if (!formattedNumber.includes('@c.us')) {
            formattedNumber = `${formattedNumber}@c.us`;
        }

        // Enviar arquivo se existir
        if (mediaPath) {
            try {
                // Verifica se o arquivo existe
                if (!fs.existsSync(mediaPath)) {
                    throw new Error('Arquivo não encontrado: ' + mediaPath);
                }

                console.log('Enviando mídia:', mediaPath);
                const media = MessageMedia.fromFilePath(mediaPath);
                
                // Envia a mídia primeiro
                await client.sendMessage(formattedNumber, media);
                
                // Pequeno intervalo após envio de mídia
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                console.log('Mídia enviada com sucesso');
            } catch (mediaError) {
                console.error('Erro ao enviar mídia:', mediaError);
                return res.status(500).json({
                    success: false,
                    message: 'Erro ao enviar mídia: ' + mediaError.message
                });
            }
        }

        // Enviar mensagem de texto se existir
        if (message && message.trim()) {
            try {
                await client.sendMessage(formattedNumber, message);
                console.log('Mensagem de texto enviada com sucesso');
            } catch (messageError) {
                console.error('Erro ao enviar mensagem:', messageError);
                return res.status(500).json({
                    success: false,
                    message: 'Erro ao enviar mensagem: ' + messageError.message
                });
            }
        }

        res.json({ success: true });
    } catch (error) {
        console.error('Erro geral:', error);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});


// Função para atualizar status no banco
async function updateDeviceStatus(deviceId, status, qrCode = null) {
    try {
        const connection = await mysql.createConnection(dbConfig);
        if (qrCode) {
            await connection.execute(
                'UPDATE dispositivos SET status = ?, qr_code = ? WHERE device_id = ?',
                [status, qrCode, deviceId]
            );
        } else {
            await connection.execute(
                'UPDATE dispositivos SET status = ?, qr_code = NULL WHERE device_id = ?',
                [status, deviceId]
            );
        }
        await connection.end();
    } catch (error) {
        console.error(`Erro ao atualizar status: ${error.message}`);
    }
}

// Função para limpar o deviceId, removendo caracteres inválidos
function sanitizeDeviceId(deviceId) {
    // Remove caracteres inválidos, mantendo apenas alfanuméricos, underscores e hífens
    return deviceId.replace(/[^a-zA-Z0-9_-]/g, '_');
}

// Função para criar novo cliente WhatsApp
async function createWhatsAppClient(deviceId) {
    console.log(`Iniciando criação do cliente WhatsApp para deviceId: ${deviceId}`);
    
    try {
        // Sanitiza o deviceId antes de usar como clientId
        const sanitizedDeviceId = sanitizeDeviceId(deviceId);
        
        // Limpar sessão antiga antes de criar nova
        await clearSession(sanitizedDeviceId);

        const client = new Client({
            authStrategy: new LocalAuth({
                clientId: sanitizedDeviceId,
                dataPath: SESSION_DIR
            }),
            puppeteer: {
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-accelerated-2d-canvas',
                    '--no-first-run',
                    '--no-zygote',
                    '--disable-gpu'
                ],
                headless: true,
                timeout: 60000 // Aumentar timeout para 60 segundos
            }
        });

        // Evento QR Code
        client.on('qr', async (qr) => {
            console.log(`Novo QR Code gerado para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'WAITING_QR', qr);
        });

        // Evento Ready
        client.on('ready', async () => {
            console.log(`Cliente WhatsApp pronto para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'CONNECTED');
        });

        // Evento de falha na autenticação
        client.on('auth_failure', async () => {
            console.log(`Falha na autenticação para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'AUTH_FAILURE');
            await clearSession(deviceId);
            clients.delete(deviceId);
        });

        // Evento de desconexão
        client.on('disconnected', async () => {
            console.log(`Cliente desconectado para deviceId: ${deviceId}`);
            await updateDeviceStatus(deviceId, 'DISCONNECTED');
            await clearSession(deviceId);
            clients.delete(deviceId);
        });

        await client.initialize();
        clients.set(deviceId, client);
        return client;
    } catch (error) {
        console.error(`Erro ao criar cliente WhatsApp: ${error.message}`);
        await updateDeviceStatus(deviceId, 'ERROR');
        throw error;
    }
}

// Endpoint para verificar status
app.get('/check-status/:deviceId', async (req, res) => {
    const { deviceId } = req.params;
    try {
        const connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute(
            'SELECT status FROM dispositivos WHERE device_id = ?',
            [deviceId]
        );
        await connection.end();

        if (rows.length > 0) {
            res.json({
                success: true,
                status: rows[0].status
            });
        } else {
            res.status(404).json({
                success: false,
                message: 'Dispositivo não encontrado'
            });
        }
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Endpoint para iniciar dispositivo
app.post('/init-device', async (req, res) => {
    const { deviceId } = req.body;
    if (!deviceId) {
        return res.status(400).json({
            success: false,
            message: 'deviceId é obrigatório'
        });
    }

    try {
        // Destruir cliente existente se houver
        if (clients.has(deviceId)) {
            console.log(`Destruindo cliente existente para deviceId: ${deviceId}`);
            const existingClient = clients.get(deviceId);
            await existingClient.destroy();
            clients.delete(deviceId);
        }

        // Limpar sessão antiga
        await clearSession(deviceId);
        
        console.log(`Iniciando novo cliente para deviceId: ${deviceId}`);
        await createWhatsAppClient(deviceId);
        
        res.json({ success: true });
    } catch (error) {
        console.error(`Erro ao iniciar dispositivo: ${error.message}`);
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Endpoint para obter QR code
app.get('/get-qr/:deviceId', async (req, res) => {
    const deviceId = req.params.deviceId;
    
    try {
        const connection = await mysql.createConnection(dbConfig);
        const [rows] = await connection.execute(
            'SELECT qr_code, status FROM dispositivos WHERE device_id = ?',
            [deviceId]
        );
        await connection.end();

        if (rows.length > 0) {
            const device = rows[0];
            
            // Verificação mais precisa do status de conexão
            if (device.status === 'CONNECTED' && clients.has(deviceId)) {
                const client = clients.get(deviceId);
                if (client && client.info) {
                    res.json({
                        success: true,
                        status: 'CONNECTED'
                    });
                    return;
                }
            }

            res.json({
                success: true,
                qr: device.qr_code,
                status: device.status
            });
        } else {
            res.status(404).json({
                success: false,
                message: 'Dispositivo não encontrado'
            });
        }
    } catch (error) {
        res.status(500).json({
            success: false,
            message: error.message
        });
    }
});

// Iniciar servidor
const port = 3000;
app.listen(port, () => {
    console.log(`Servidor rodando na porta ${port}`);
});

// Tratamento de erros não capturados
process.on('unhandledRejection', (error) => {
    console.error('Erro não tratado:', error);
});

process.on('uncaughtException', (error) => {
    console.error('Exceção não capturada:', error);
});

// Limpeza na saída
process.on('SIGINT', async () => {
    console.log('Encerrando servidor...');
    for (const [deviceId, client] of clients) {
        try {
            await client.destroy();
            console.log(`Cliente ${deviceId} destruído`);
        } catch (error) {
            console.error(`Erro ao destruir cliente ${deviceId}:`, error);
        }
    }
    process.exit(0);
});