<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Logger;
use App\Core\RegraNegocioException;

final class UploadService
{
    private string $diretorio;
    private int $tamanhoMax;
    private array $tipos;

    public function __construct(?string $diretorio = null)
    {
        $this->diretorio = $diretorio ?? (string) Config::get('upload.diretorio');
        $this->tamanhoMax = (int) Config::get('upload.tamanho_max', 2 * 1024 * 1024);
        $this->tipos = (array) Config::get('upload.tipos', []);
    }

    public function salvarImagem(array $arquivo): string
    {
        $erro = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($erro !== UPLOAD_ERR_OK) {
            throw new RegraNegocioException($this->mensagemErroUpload($erro));
        }

        $tmp = (string) ($arquivo['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RegraNegocioException('Arquivo de imagem inválido.');
        }

        if ((int) ($arquivo['size'] ?? 0) > $this->tamanhoMax) {
            throw new RegraNegocioException('A imagem deve ter no máximo ' . ($this->tamanhoMax / 1024 / 1024) . ' MB.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        if (!isset($this->tipos[$mime]) || @getimagesize($tmp) === false) {
            throw new RegraNegocioException('Formato de imagem não permitido. Envie JPG, PNG ou WEBP.');
        }

        if (!is_dir($this->diretorio) && !@mkdir($this->diretorio, 0775, true)) {
            Logger::erro('Diretório de upload inexistente e não foi possível criá-lo', ['dir' => $this->diretorio]);
            throw new RegraNegocioException('Não foi possível salvar a imagem (pasta de uploads indisponível).');
        }

        $nome = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $this->tipos[$mime];

        if (!@move_uploaded_file($tmp, $this->diretorio . '/' . $nome)) {
            Logger::erro('Falha no move_uploaded_file', ['dir' => $this->diretorio, 'gravavel' => is_writable($this->diretorio)]);
            throw new RegraNegocioException('Não foi possível salvar a imagem. Verifique as permissões da pasta de uploads.');
        }

        return $nome;
    }

    public function remover(?string $nome): void
    {
        if ($nome === null || $nome === '') {
            return;
        }
        $caminho = $this->diretorio . '/' . basename($nome);
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }

    private function mensagemErroUpload(int $codigo): string
    {
        return match ($codigo) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem é maior que o limite permitido pelo servidor.',
            UPLOAD_ERR_PARTIAL => 'O envio da imagem foi interrompido. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar a imagem.',
            default => 'Falha no envio da imagem.',
        };
    }
}
