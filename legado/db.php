<?php
// ATENÇÃO: credenciais removidas antes de publicar no GitHub (versão original MySQL/mysqli).
// A versão nova lê a conexão do arquivo .env — veja config/config.php e app/Core/Database.php.
$servername = "SEU_HOST";
$username = "SEU_USUARIO";
$password = "SUA_SENHA";
$database = "SEU_BANCO";

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Função para buscar produtos pelo código ou nome
function buscarProduto($campo, $valor) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE $campo LIKE ?");
    $valor = "%$valor%";
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    return $stmt->get_result();
}
?>
