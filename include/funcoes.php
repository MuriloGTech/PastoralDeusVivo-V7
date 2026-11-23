<?php

function h(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function inicioDaSemana(?DateTime $data = null): DateTime
{
    $data = $data ? clone $data : new DateTime('today');
    $data->setTime(0, 0, 0);
    $data->modify('-' . ((int) $data->format('N') - 1) . ' days');
    return $data;
}

function fimDaSemana(DateTime $inicio): DateTime
{
    $fim = clone $inicio;
    $fim->modify('+6 days');
    return $fim;
}

function semanaAtual(): array
{
    $inicio = inicioDaSemana();
    return [$inicio, fimDaSemana($inicio)];
}

function semanaPassada(): array
{
    $inicio = inicioDaSemana();
    $inicio->modify('-7 days');
    return [$inicio, fimDaSemana($inicio)];
}

function periodoDoMes(string $mes, int $semana = 0): array
{
    $inicioMes = DateTime::createFromFormat('!Y-m-d', $mes . '-01') ?: new DateTime('first day of this month');
    $ultimoDia = (int) $inicioMes->format('t');

    if ($semana < 1) {
        $fim = (clone $inicioMes)->modify('+' . ($ultimoDia - 1) . ' days');
        return [$inicioMes, $fim];
    }

    $diaIni = (($semana - 1) * 7) + 1;
    $diaFim = min($diaIni + 6, $ultimoDia);
    if ($diaIni > $ultimoDia) {
        $vazio = (clone $inicioMes)->modify('+1 month');
        return [$vazio, (clone $vazio)->modify('-1 day')];
    }

    return [
        (clone $inicioMes)->modify('+' . ($diaIni - 1) . ' days'),
        (clone $inicioMes)->modify('+' . ($diaFim - 1) . ' days'),
    ];
}

function semanasDoMes(string $mes): int
{
    $inicioMes = DateTime::createFromFormat('!Y-m-d', $mes . '-01') ?: new DateTime('first day of this month');
    return (int) ceil(((int) $inicioMes->format('t')) / 7);
}

function formatoData(?string $data): string
{
    if (!$data) {
        return '—';
    }
    $dt = DateTime::createFromFormat('Y-m-d', substr($data, 0, 10));
    return $dt ? $dt->format('d/m/Y') : '—';
}

function moeda($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function numeroBr($valor): string
{
    $n = (float) $valor;
    if (abs($n - round($n)) < 0.0000001) {
        return number_format($n, 0, ',', '.');
    }
    return rtrim(rtrim(number_format($n, 3, ',', '.'), '0'), ',');
}

function formatarCpf(?string $cpf): string
{
    if (!$cpf || strlen($cpf) !== 11) {
        return '—';
    }
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

function formatarTelefone(?string $tel): string
{
    $digitos = preg_replace('/\D/', '', (string) $tel);
    if (strlen($digitos) !== 11) {
        return trim((string) $tel) !== '' ? (string) $tel : '—';
    }
    return '(' . substr($digitos, 0, 2) . ') ' . substr($digitos, 2, 5) . '-' . substr($digitos, 7, 4);
}

function textoNivel(string $nota): string
{
    $textos = [
        '1' => 'Pouca distribuição',
        '2' => 'Média distribuição',
        '3' => 'Muita distribuição',
    ];
    return $textos[$nota] ?? '';
}

function custoLinha(float $preco, float $quantidadeCadastro, float $quantidadeUsada): float
{
    if ($quantidadeCadastro <= 0 || $quantidadeUsada <= 0) {
        return 0.0;
    }
    return ($quantidadeUsada / $quantidadeCadastro) * $preco;
}

function precoParaDecimal(string $preco): ?string
{
    $preco = str_replace(['R$', ' '], '', trim($preco));
    if (strpos($preco, ',') !== false) {
        $preco = str_replace('.', '', $preco);
        $preco = str_replace(',', '.', $preco);
    }
    return is_numeric($preco) ? $preco : null;
}

function flashDefinir(string $tipo, string $texto): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

function flashLer(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function salvarImagem(array $arquivo, string $pastaRelativa, bool $obrigatoria): array
{
    $temArquivo = isset($arquivo['error']) && $arquivo['error'] !== UPLOAD_ERR_NO_FILE;
    if (!$temArquivo) {
        if ($obrigatoria) {
            return ['ok' => false, 'erro' => 'Envie a fotografia.', 'caminho' => ''];
        }
        return ['ok' => true, 'erro' => '', 'caminho' => ''];
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'erro' => 'Não foi possível enviar a fotografia.', 'caminho' => ''];
    }
    if (($arquivo['size'] ?? 0) > 5 * 1024 * 1024) {
        return ['ok' => false, 'erro' => 'A fotografia deve ter no máximo 5 MB.', 'caminho' => ''];
    }

    $info = @getimagesize($arquivo['tmp_name']);
    $extensoes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($extensoes[$info[2]])) {
        return ['ok' => false, 'erro' => 'A fotografia deve ser JPG, PNG ou WEBP.', 'caminho' => ''];
    }

    $pasta = dirname(__DIR__) . '/' . $pastaRelativa . '/';
    if (!is_dir($pasta) && !mkdir($pasta, 0755, true) && !is_dir($pasta)) {
        return ['ok' => false, 'erro' => 'Não foi possível preparar a pasta das fotografias.', 'caminho' => ''];
    }

    $nome = uniqid() . '.' . $extensoes[$info[2]];
    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . $nome)) {
        return ['ok' => false, 'erro' => 'Não foi possível salvar a fotografia.', 'caminho' => ''];
    }

    return ['ok' => true, 'erro' => '', 'caminho' => $pastaRelativa . '/' . $nome];
}

function apagarArquivoProjeto(string $relativo): void
{
    $relativo = str_replace('\\', '/', $relativo);
    if (!preg_match('#^img/(documentos|cardapios)/[A-Za-z0-9._-]+$#', $relativo)) {
        return;
    }
    $absoluto = dirname(__DIR__) . '/' . $relativo;
    if (is_file($absoluto)) {
        unlink($absoluto);
    }
}
