<?php
// includes/database.php

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            // URL de connexion depuis les variables d'environnement
            $databaseUrl = $_ENV['DATABASE_URL'] ?? 'postgresql://postgres:Fw70359545@db.lrvwhewjudeiuwqcaeqz.supabase.co:5432/postgres';
            
            // Parser l'URL de connexion Supabase
            $url = parse_url($databaseUrl);
            
            $host = $url['host'];
            $port = $url['port'] ?? 5432;
            $dbname = ltrim($url['path'] ?? '', '/');
            $username = $url['user'] ?? 'postgres';
            $password = $url['pass'] ?? '';
            
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            
            $this->connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            // Test de connexion
            $stmt = $this->connection->query("SELECT NOW() as current_time");
            $result = $stmt->fetch();
            error_log("✅ Connexion Supabase réussie - " . $result['current_time']);
            
        } catch (Exception $e) {
            error_log("❌ Erreur connexion Supabase: " . $e->getMessage());
            die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px;'>
                <h3>❌ Erreur de connexion base de données</h3>
                <p>Vérifiez la configuration Supabase.</p>
                <details style='margin-top: 10px;'>
                    <summary>Détails techniques</summary>
                    <code>" . $e->getMessage() . "</code>
                </details>
            </div>");
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>