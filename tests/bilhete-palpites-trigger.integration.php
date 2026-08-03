<?php
/**
 * Teste isolado de integração para verificar a geração de palpites via DB trigger vs BilheteRestService.
 * Executar via: docker exec applications_www php /var/www/html/tests/bilhete-palpites-trigger.integration.php
 */
putenv('APP_ENV=test');
putenv('DATABASE_NAME=teste');
$_ENV['APP_ENV'] = 'test';
$_ENV['DATABASE_NAME'] = 'teste';

require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../app/service/rest/BilheteRestService.php';

use Adianti\Database\TTransaction;

try {
    TTransaction::open('permission');

    $conn = TTransaction::get();

    $stmtSorteio = $conn->query("
        SELECT ms.sorteio_id, ae.area_id
        FROM mov_sorteio ms
        JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
        JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id AND ae.ativo = true
        WHERE ms.situacao = 'A' AND e.hora_limite > (NOW() AT TIME ZONE 'America/Sao_Paulo')::time
        LIMIT 1
    ");
    $sorteio = $stmtSorteio->fetch(PDO::FETCH_ASSOC);

    if (!$sorteio) {
        TTransaction::rollback();
        echo "SKIP: Sem sorteio aberto para teste\n";
        exit(0);
    }

    $sorteio_id = (int)$sorteio['sorteio_id'];
    $area_id = (int)$sorteio['area_id'];

    $stmtVendedor = $conn->query("SELECT usuario_id, vendedor_id FROM cad_vendedor WHERE area_id = {$area_id} AND ativo = 'S' LIMIT 1");
    $vendedor = $stmtVendedor->fetch(PDO::FETCH_ASSOC);

    if (!$vendedor) {
        TTransaction::rollback();
        echo "SKIP: Sem vendedor para teste\n";
        exit(0);
    }

    $stmtTerminal = $conn->query("SELECT terminal_id FROM cad_terminal WHERE vendedor_id = {$vendedor['vendedor_id']} AND ativo = 'S' LIMIT 1");
    $terminal = $stmtTerminal->fetch(PDO::FETCH_ASSOC);
    if (!$terminal) {
        $stmtTerminal = $conn->query("SELECT terminal_id FROM cad_terminal WHERE ativo = 'S' LIMIT 1");
        $terminal = $stmtTerminal->fetch(PDO::FETCH_ASSOC);
    }

    if (!$terminal) {
        TTransaction::rollback();
        echo "SKIP: Sem terminal para teste\n";
        exit(0);
    }

    // -------------------------------------------------------------
    // TESTE 1: Modalidade SENINHA (jogo_id = 27)
    // -------------------------------------------------------------
    $stmtModSen = $conn->query("SELECT modalidade_id FROM cad_modalidade WHERE jogo_id = 27 LIMIT 1");
    $modSen = $stmtModSen->fetch(PDO::FETCH_ASSOC);
    $modalidade_sen = $modSen ? (int)$modSen['modalidade_id'] : 1;

    $paramSen = [
        '_auth' => ['id' => $vendedor['usuario_id']],
        'data' => [
            'terminal_id' => $terminal['terminal_id'],
            'jogos' => [
                [
                    'sorteio_id' => $sorteio_id,
                    'modalidade_id' => $modalidade_sen,
                    'palpites' => ['2569', '3031'],
                    'colocacao_inicial' => 1,
                    'colocacao_final' => 5,
                    'valor_palpite' => 1.00
                ]
            ]
        ]
    ];

    echo "--- TESTE 1: SENINHA (SEN) ---\n";
    $resSen = BilheteRestService::registrar($paramSen);
    $jb_id_sen = $resSen['jb_id'];

    $stmtPalpitesSen = $conn->prepare("SELECT jb_palpites_id, palpite FROM mov_jb_sort_palpite WHERE jb_id = :jb_id ORDER BY jb_palpites_id ASC");
    $stmtPalpitesSen->execute([':jb_id' => $jb_id_sen]);
    $palpitesSen = $stmtPalpitesSen->fetchAll(PDO::FETCH_ASSOC);

    echo "Palpites inseridos para Seninha bilhete {$jb_id_sen}:\n";
    foreach ($palpitesSen as $row) {
        echo "  ID: {$row['jb_palpites_id']} | Palpite: {$row['palpite']}\n";
    }

    if (count($palpitesSen) !== 1 || $palpitesSen[0]['palpite'] !== '2569,3031') {
        throw new Exception("Falha Seninha! Esperava 1 palpite ('2569,3031'), obteve: " . json_encode($palpitesSen));
    }

    // -------------------------------------------------------------
    // TESTE 2: Modalidade MILHAR BRINDE / MILHAR
    // -------------------------------------------------------------
    $modalidade_mil = 3;

    $paramMil = [
        '_auth' => ['id' => $vendedor['usuario_id']],
        'data' => [
            'terminal_id' => $terminal['terminal_id'],
            'jogos' => [
                [
                    'sorteio_id' => $sorteio_id,
                    'modalidade_id' => $modalidade_mil,
                    'palpites' => ['2569', '3031'],
                    'colocacao_inicial' => 1,
                    'colocacao_final' => 5,
                    'valor_palpite' => 1.00
                ]
            ]
        ]
    ];

    echo "\n--- TESTE 2: MILHAR (M) ---\n";
    $resMil = BilheteRestService::registrar($paramMil);
    $jb_id_mil = $resMil['jb_id'];

    $stmtPalpitesMil = $conn->prepare("SELECT jb_palpites_id, palpite FROM mov_jb_sort_palpite WHERE jb_id = :jb_id ORDER BY jb_palpites_id ASC");
    $stmtPalpitesMil->execute([':jb_id' => $jb_id_mil]);
    $palpitesMil = $stmtPalpitesMil->fetchAll(PDO::FETCH_ASSOC);

    echo "Palpites inseridos para Milhar bilhete {$jb_id_mil}:\n";
    foreach ($palpitesMil as $row) {
        echo "  ID: {$row['jb_palpites_id']} | Palpite: {$row['palpite']}\n";
    }

    $palpitesMilList = array_column($palpitesMil, 'palpite');
    if (count($palpitesMilList) !== 2 || !in_array('2569', $palpitesMilList) || !in_array('3031', $palpitesMilList)) {
        throw new Exception("Falha Milhar! Esperava 2 palpites ('2569' e '3031'), obteve: " . json_encode($palpitesMilList));
    }

    TTransaction::rollback();
    echo "\nSUCCESS: Todos os testes de verificação de inserção de palpites passaram perfeitamente!\n";
} catch (Exception $e) {
    if (TTransaction::get()) {
        TTransaction::rollback();
    }
    echo "\nERRO: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
