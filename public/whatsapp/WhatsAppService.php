<?php
// public/frontend/whatsapp/WhatsAppService.php

class WhatsAppService {
    private $apiUrl;
    
    public function __construct() {
        // URL CORRECTE de votre backend sur Render
        $this->apiUrl = $_ENV['BACKEND_URL'] ?? 'https://whatsapp-backend-e6sw.onrender.com';
    }
    
    /**
     * Démarre la connexion WhatsApp
     * @return array
     */
    public function startConnection() {
        return $this->callAPI('/api/start', 'POST');
    }
    
    /**
     * Arrête la connexion WhatsApp
     * @return array
     */
    public function stopConnection() {
        return $this->callAPI('/api/stop', 'POST');
    }
    
    /**
     * Envoie un message WhatsApp
     * @param string $phone Numéro de téléphone
     * @param string $message Message à envoyer
     * @return array
     */
    public function sendMessage($phone, $message) {
        // Validation basique
        if (empty($phone) || empty($message)) {
            return [
                'success' => false, 
                'error' => 'Le numéro et le message sont obligatoires'
            ];
        }
        
        return $this->callAPI('/api/send', 'POST', [
            'phone' => $this->formatPhone($phone),
            'message' => $message
        ]);
    }
    
    /**
     * Récupère les informations complètes de statut
     * @return array
     */
    public function getStatus() {
        return $this->callAPI('/api/status', 'GET');
    }
    
    /**
     * Vérifie si le backend est accessible
     * @return bool
     */
    public function isBackendAlive() {
        $result = $this->callAPI('/api/status', 'GET', null, 5);
        return !isset($result['error']);
    }
    
    /**
     * Méthode générique pour appeler l'API
     * @param string $endpoint
     * @param string $method
     * @param array|null $data
     * @param int $timeout
     * @return array
     */
    private function callAPI($endpoint, $method = 'GET', $data = null, $timeout = 30) {
        $url = $this->apiUrl . $endpoint;
        
        $options = [
            'http' => [
                'method' => $method,
                'header' => 'Content-type: application/json',
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ];
        
        if ($data && $method !== 'GET') {
            $options['http']['content'] = json_encode($data);
        }
        
        try {
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === FALSE) {
                return [
                    'success' => false, 
                    'error' => 'Service WhatsApp indisponible',
                    'details' => 'Impossible de contacter le serveur backend: ' . $url
                ];
            }
            
            $result = json_decode($response, true);
            
            // Vérifier si le JSON est valide
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Réponse invalide du serveur',
                    'details' => json_last_error_msg()
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'success' => false, 
                'error' => 'Erreur de connexion',
                'details' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Formate un numéro de téléphone pour WhatsApp
     * @param string $phone
     * @return string
     */
    private function formatPhone($phone) {
        // Nettoyer le numéro
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        
        // Supprimer le préfixe international s'il existe
        if (substr($cleanPhone, 0, 2) === '33') {
            $cleanPhone = substr($cleanPhone, 2);
        }
        
        // Supprimer le 0 initial pour les numéros français
        if (substr($cleanPhone, 0, 1) === '0') {
            $cleanPhone = substr($cleanPhone, 1);
        }
        
        return $cleanPhone;
    }
}
?>