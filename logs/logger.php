<?php
// logger.php
class Logger {
    private static $logFile = 'claude_api.log';
    private static $logPath;

    public static function init() {
        // Define o caminho do arquivo de log
        self::$logPath = dirname(__FILE__) . '/logs/' . self::$logFile;
        
        // Cria o diretório de logs se não existir
        if (!file_exists(dirname(__FILE__) . '/logs')) {
            mkdir(dirname(__FILE__) . '/logs', 0777, true);
        }
    }

    public static function log($message, $type = 'INFO') {
        // Inicializa se ainda não foi feito
        if (!isset(self::$logPath)) {
            self::init();
        }

        // Formata a mensagem
        $dateTime = date('Y-m-d H:i:s');
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT);
        }
        
        $logMessage = "[$dateTime][$type] $message" . PHP_EOL;

        // Escreve no arquivo de log
        file_put_contents(self::$logPath, $logMessage, FILE_APPEND);
    }

    public static function getLogContent($lines = 100) {
        if (!isset(self::$logPath)) {
            self::init();
        }

        if (!file_exists(self::$logPath)) {
            return "Nenhum log encontrado.";
        }

        // Lê as últimas linhas do arquivo
        $file = new SplFileObject(self::$logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $start = max(0, $totalLines - $lines);
        $logs = [];

        $file->seek($start);
        while (!$file->eof()) {
            $logs[] = $file->current();
            $file->next();
        }

        return implode('', $logs);
    }

    public static function clear() {
        if (!isset(self::$logPath)) {
            self::init();
        }

        if (file_exists(self::$logPath)) {
            unlink(self::$logPath);
        }
    }
}