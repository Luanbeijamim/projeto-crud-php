<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $id = $_GET["id"];
    $stmt = $conn->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $produto = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST["id"];
    $nome = $_POST["nome"];
    $categoria = $_POST["categoria"];
    $validade = $_POST["validade"];
    $quantidade = $_POST["quantidade"];
    $precoUnitario = $_POST["preco_unitario"];
    $precoPacote = $_POST["preco_pacote"];

    $stmt = $conn->prepare("UPDATE produtos SET nome = ?, categoria = ?, validade = ?, quantidade = ?, preco_unitario = ?, preco_pacote = ? WHERE id = ?");
    $stmt->bind_param("sssiddi", $nome, $categoria, $validade, $quantidade, $precoUnitario, $precoPacote, $id);
    $stmt->execute();

    echo "Produto atualizado com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center">Editar Produto</h1>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $produto['id'] ?>">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome do Produto</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= $produto['nome'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="categoria" class="form-label">Categoria</label>
                <input type="text" class="form-control" id="categoria" name="categoria" value="<?= $produto['categoria'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="validade" class="form-label">Validade</label>
                <input type="date" class="form-control" id="validade" name="validade" value="<?= $produto['validade'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="quantidade" class="form-label">Quantidade</label>
                <input type="number" class="form-control" id="quantidade" name="quantidade" value="<?= $produto['quantidade'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="preco_unitario" class="form-label">Preço Unitário</label>
                <input type="number" step="0.01" class="form-control" id="preco_unitario" name="preco_unitario" value="<?= $produto['preco_unitario'] ?>" required>
            </div>
            <div class="mb-3">
                <label for="preco_pacote" class="form-label">Preço Pacote</label>
                <input type="number" step="0.01" class="form-control" id="preco_pacote" name="preco_pacote" value="<?= $produto['preco_pacote'] ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
