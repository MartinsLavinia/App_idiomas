<?php
// Classe para gerenciar a conexão com o banco de dados
class Database {
    private $host = "sh-pro66.hostgator.com.br";
    private $user = "devgom44_aims-sub1";
    private $password = "aims-sub1@1234!";
    private $database = "devgom44_aims-sub1";
    public $conn;

    // Construtor da classe que estabelece a conexão automaticamente
    public function __construct() {
        $this->conn = new mysqli(
            $this->host, 
            $this->user, 
            $this->password, 
            $this->database
        );

        // Verifica se houve erro na conexão
        if ($this->conn->connect_error) {
            die("Falha na conexão com o banco de dados: " . $this->conn->connect_error);
        }

        // Define o charset para UTF-8
        $this->conn->set_charset("utf8");
    }

    // Método para fechar a conexão
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Para usar a conexão em outros arquivos:
// include 'conexao.php';
// $database = new Database();
// $conn = $database->conn;
?>