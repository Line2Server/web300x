<?php
$status = 'offline';
$latency = 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Erro de conexão com banco</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #0f1115;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .box {
            width: 90%;
            max-width: 700px;
            background: #181c23;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,.35);
        }
        .title {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .status {
            color: #ff5b5b;
            font-weight: bold;
        }
        .chart {
            margin-top: 20px;
            height: 18px;
            background: #2a313c;
            border-radius: 999px;
            overflow: hidden;
        }
        .chart span {
            display: block;
            width: 15%;
            height: 100%;
            background: linear-gradient(90deg, #ff4d4d, #ff9f43);
        }
        .meta {
            margin-top: 20px;
            color: #b9c1cc;
            line-height: 1.8;
        }
        .code {
            margin-top: 15px;
            font-family: Consolas, monospace;
            background: #0b0d11;
            padding: 12px;
            border-radius: 10px;
            color: #9ad0ff;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="title">Banco de dados indisponível</div>
        <p>O site não conseguiu estabelecer conexão com o banco no momento.</p>

        <p>Status atual: <span class="status">OFFLINE</span></p>

        <div class="chart">
            <span></span>
        </div>

        <div class="meta">
            <div>Timezone do site: America/Sao_Paulo</div>
            <div>Timezone do banco: America/Sao_Paulo</div>
            <div>Ambiente: <?= e(APP_ENV) ?></div>
            <div>Data/Hora: <?= date('d/m/Y H:i:s') ?></div>
        </div>

        <?php if (APP_ENV === 'development'): ?>
            <div class="code">Modo development ativo: verifique host, porta, banco, usuário e senha.</div>
        <?php endif; ?>
    </div>
</body>
</html>