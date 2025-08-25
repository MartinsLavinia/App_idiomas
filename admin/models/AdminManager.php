<?php
// Certifique-se de que a sua classe Database já está incluída
include_once __DIR__ . '/../../conexao.php';

class AdminManager {
    private $conn;

    // O construtor recebe a conexão com o banco de dados
    public function __construct($database) {
        $this->conn = $database->conn;
    }

    /**
     * Registra um novo administrador no banco de dados.
     * @return bool Retorna true em caso de sucesso, false caso contrário.
     */
    public function registerAdmin($nome_usuario, $senha) {
        // Gera um hash seguro da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // Prepara a consulta SQL
        $sql = "INSERT INTO administradores (nome_usuario, senhaadm) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("ss", $nome_usuario, $senha_hash);
            if ($stmt->execute()) {
                $stmt->close();
                return true;
            }
            $stmt->close();
        }
        return false;
    }

    /**
     * Processa o login do administrador.
     * @return bool Retorna true se o login for bem-sucedido, false caso contrário.
     */
    public function loginAdmin($nome_usuario, $senha) {
        // Prepara a consulta para buscar o usuário
        $sql = "SELECT id, nome_usuario, senhaadm FROM administradores WHERE nome_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $nome_usuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                // Verifica a senha fornecida com o hash no banco de dados
                if (password_verify($senha, $admin['senhaadm'])) {
                    // Login bem-sucedido: inicia a sessão
                    session_start();
                    $_SESSION['id_admin'] = $admin['id'];
                    $_SESSION['nome_admin'] = $admin['nome_usuario'];
                    $stmt->close();
                    return true;
                }
            }
            $stmt->close();
        }
        return false;
    }
}
//Faz o processamento do adm
//E para deixar organizado todo o código relacionado à gestão de administradores em um único lugar. 
//Para Facilitar o uso de Orientaçao Objeto 
//A classe "esconde" os detalhes de como o login funciona.
// O arquivo login_admin.
//php não precisa saber como a conexão com o banco de dados é feita, como a senha é 
//verificada ou como a sessão é iniciada. Ele apenas diz: "Ei, AdminManager, tente fazer o login com este usuário e esta senha." Isso torna a lógica mais segura e menos propensa a erros.