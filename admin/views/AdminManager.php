<?php
include_once __DIR__ . '/../config/conexao.php';

class AdminManager {
    private $conn;

    public function __construct($database) {
        $this->conn = $database->conn;
    }

    // Função para cadastrar novo admin
    public function registerAdmin($nome_usuario, $senha) {
        // Verifica se já existe o usuário
        $sql_check = "SELECT id FROM administradores WHERE nome_usuario = ?";
        $stmt_check = $this->conn->prepare($sql_check);
        $stmt_check->bind_param("s", $nome_usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            return false; // Usuário já existe
        }

        // Criptografa a senha antes de salvar
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        $sql = "INSERT INTO administradores (nome_usuario, senha) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $nome_usuario, $senhaHash);

        return $stmt->execute();
    }

    // Função para login
    public function loginAdmin($nome_usuario, $senha) {
        $sql = "SELECT id, senha FROM administradores WHERE nome_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $nome_usuario);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($senha, $row['senha'])) {
                return true; // Login OK
            }
        }
        return false; // Usuário ou senha inválidos
    }
}
?>