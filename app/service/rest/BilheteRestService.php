<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class BilheteRestService
{
    /**
     * Registra um novo bilhete JB.
     *
     * Body esperado:
     * {
     *   "data": {
     *     "terminal_id": 1,
     *     "nome_cliente": "João",       // opcional
     *     "fone_cliente": "11999999999", // opcional
     *     "jogos": [
     *       {
     *         "sorteio_id": 123,
     *         "modalidade_id": 2,
     *         "palpites": ["1234", "5678"],
     *         "colocacao_inicial": 1,
     *         "colocacao_final": 5,
     *         "valor_palpite": 2.00
     *       }
     *     ]
     *   }
     * }
     */
    public static function registrar($param)
    {
        try
        {
            $usuario_id  = $param['_auth']['id'] ?? null;
            $data        = $param['data'] ?? [];
            $terminal_id = (int) ($data['terminal_id'] ?? 0);
            $jogos       = $data['jogos'] ?? [];

            if (!$usuario_id)                throw new Exception('Usuário não autenticado');
            if (!$terminal_id)               throw new Exception('terminal_id é obrigatório');
            if (empty($jogos))               throw new Exception('Nenhum jogo informado');

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            self::validarTerminal($terminal_id, $vendedor->vendedor_id);

            // Valida e agrupa jogos por sorteio
            $sorteiosMap = [];
            $totalBilhete = 0.0;

            foreach ($jogos as $jogo)
            {
                $sorteio_id    = (int) ($jogo['sorteio_id'] ?? 0);
                $modalidade_id = (int) ($jogo['modalidade_id'] ?? 0);
                $palpites      = $jogo['palpites'] ?? [];
                $col_ini       = (int) ($jogo['colocacao_inicial'] ?? 1);
                $col_fim       = (int) ($jogo['colocacao_final'] ?? 1);
                $valor_palpite = (float) ($jogo['valor_palpite'] ?? 0);

                if (!$sorteio_id)    throw new Exception('sorteio_id inválido');
                if (!$modalidade_id) throw new Exception('modalidade_id inválido');
                if (empty($palpites)) throw new Exception('Palpites não informados');
                if ($valor_palpite <= 0) throw new Exception('Valor do palpite deve ser maior que zero');

                self::validarSorteioAberto($sorteio_id, $vendedor->area_id);
                self::validarLimitePalpite($sorteio_id, $modalidade_id, $vendedor->area_id, $valor_palpite);

                $total_jogo = $valor_palpite * count($palpites);
                $totalBilhete += $total_jogo;

                $sorteiosMap[$sorteio_id][] = [
                    'modalidade_id'    => $modalidade_id,
                    'palpites'         => $palpites,
                    'colocao_inicial'  => $col_ini,
                    'colocao_final'    => $col_fim,
                    'valor_palpite'    => $valor_palpite,
                    'total_jogo'       => $total_jogo,
                ];
            }

            self::validarLimiteVenda($vendedor, $totalBilhete);

            $sorteios_ids      = implode(',', array_keys($sorteiosMap));
            $sorteios_qtde     = count($sorteiosMap);
            $bilhete_numero    = self::proximoBilheteNumero($vendedor->vendedor_id);
            $string_autorizacao = self::gerarAutorizacao();
            $agora             = date('Y-m-d H:i:s');

            // Insere cabeçalho do bilhete
            $jb                     = new MovJb;
            $jb->area_id            = $vendedor->area_id;
            $jb->coletor_id         = $vendedor->coletor_id;
            $jb->terminal_id        = $terminal_id;
            $jb->sorteios_ids       = $sorteios_ids;
            $jb->sorteios_quantidade = $sorteios_qtde;
            $jb->vendedor_id        = $vendedor->vendedor_id;
            $jb->bilhete_numero     = $bilhete_numero;
            $jb->data_hora          = $agora;
            $jb->nome_cliente       = $data['nome_cliente'] ?? null;
            $jb->fone_cliente       = $data['fone_cliente'] ?? null;
            $jb->total_bilhete      = $totalBilhete;
            $jb->comissao_valor     = 0;
            $jb->comissao_pago      = 'N';
            $jb->string_autorizacao = $string_autorizacao;
            $jb->cancelado          = 'N';
            $jb->reimpressao        = 0;
            $jb->store();

            $jb_id = (int) $jb->jb_id;

            // Insere mov_jb_sorteio e mov_jb_sort_palpite por sorteio × modalidade
            foreach ($sorteiosMap as $sorteio_id => $modalidades)
            {
                foreach ($modalidades as $mod)
                {
                    $palpites_str = implode(',', $mod['palpites']);

                    $jbSorteio                    = new MovJbSorteio;
                    $jbSorteio->jb_id             = $jb_id;
                    $jbSorteio->sorteio_id        = $sorteio_id;
                    $jbSorteio->modalidade_id     = $mod['modalidade_id'];
                    $jbSorteio->palpites          = $palpites_str;
                    $jbSorteio->palpites_quantidade = count($mod['palpites']);
                    $jbSorteio->colocao_inicial   = $mod['colocao_inicial'];
                    $jbSorteio->colocao_final      = $mod['colocao_final'];
                    $jbSorteio->valor_palpites     = $mod['valor_palpite'];
                    $jbSorteio->total_sorteio      = $mod['total_jogo'];
                    $jbSorteio->comissao_sorteio   = 0; // trigger calcula
                    $jbSorteio->sorteado           = 'N';
                    $jbSorteio->sorteado_colocacao = '0';
                    $jbSorteio->sorteado_valor     = 0;
                    $jbSorteio->sorteado_pago      = 'N';
                    $jbSorteio->previsao_premio    = 0;
                    $jbSorteio->sorteado_valor_pago = 0;
                    $jbSorteio->store();

                    $jb_sorteio_id = (int) $jbSorteio->jb_sorteio_id;

                    // Insere um registro de palpite por número apostado
                    foreach ($mod['palpites'] as $numero)
                    {
                        $palpite                = new MovJbSortPalpite;
                        $palpite->jb_sorteio_id = $jb_sorteio_id;
                        $palpite->jb_id         = $jb_id;
                        $palpite->sorteio_id    = $sorteio_id;
                        $palpite->modalidade_id = $mod['modalidade_id'];
                        $palpite->palpite       = $numero;
                        $palpite->valor_palpite = $mod['valor_palpite'];
                        // Colocações jogadas
                        for ($c = 1; $c <= 10; $c++)
                        {
                            $campo = 'jogou_colocacao_' . str_pad($c, 2, '0', STR_PAD_LEFT);
                            $palpite->$campo = ($c >= $mod['colocao_inicial'] && $c <= $mod['colocao_final']) ? 'S' : 'N';
                        }
                        // Prêmios zerados — triggers preenchem após sorteio
                        for ($c = 1; $c <= 5; $c++)
                        {
                            $campo = 'premio_colocacao_' . str_pad($c, 2, '0', STR_PAD_LEFT);
                            $palpite->$campo = 0;
                        }
                        $palpite->ganhou_premio_total = 0;
                        $palpite->pago_premio_total   = 'N';
                        $palpite->store();
                    }
                }
            }

            TTransaction::close();

            return [
                'jb_id'              => $jb_id,
                'bilhete_numero'     => $bilhete_numero,
                'string_autorizacao' => $string_autorizacao,
                'total_bilhete'      => $totalBilhete,
                'data_hora'          => $agora,
                'vendedor_nome'      => $vendedor->nome,
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Cancela um bilhete, respeitando as permissões do vendedor.
     */
    public static function cancelar($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            $jb_id      = (int) ($param['bilhete_id'] ?? 0);

            if (!$usuario_id) throw new Exception('Usuário não autenticado');
            if (!$jb_id)      throw new Exception('bilhete_id é obrigatório');

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);

            if ($vendedor->pode_cancelar !== 'S')
            {
                throw new Exception('Vendedor não tem permissão para cancelar bilhetes');
            }

            $jb = new MovJb($jb_id);

            if ($jb->vendedor_id != $vendedor->vendedor_id)
            {
                throw new Exception('Bilhete não pertence a este vendedor');
            }
            if ($jb->cancelado === 'S')
            {
                throw new Exception('Bilhete já está cancelado');
            }

            // Valida tempo limite de cancelamento
            if (!empty($vendedor->pode_cancelar_tempo) && $vendedor->pode_cancelar_tempo !== '00:00:00')
            {
                $data_bilhete  = new \DateTime($jb->data_hora);
                $agora         = new \DateTime;
                $diff_segundos = $agora->getTimestamp() - $data_bilhete->getTimestamp();
                list($h, $m, $s) = explode(':', $vendedor->pode_cancelar_tempo);
                $limite_segundos  = ((int)$h * 3600) + ((int)$m * 60) + (int)$s;

                if ($diff_segundos > $limite_segundos)
                {
                    throw new Exception('Tempo limite para cancelamento esgotado');
                }
            }

            // Valida quantidade de cancelamentos do dia
            if ((int) $vendedor->pode_cancelar_qtde > 0)
            {
                $conn = TTransaction::get();
                $stmt = $conn->prepare(
                    "SELECT COUNT(*) FROM mov_jb
                     WHERE vendedor_id = :vid AND cancelado = 'S'
                       AND data_cancelamento::date = CURRENT_DATE"
                );
                $stmt->execute([':vid' => $vendedor->vendedor_id]);
                $qtde_cancelados = (int) $stmt->fetchColumn();

                if ($qtde_cancelados >= (int) $vendedor->pode_cancelar_qtde)
                {
                    throw new Exception('Limite diário de cancelamentos atingido');
                }
            }

            $jb->cancelado          = 'S';
            $jb->cancelado_motivo   = $param['data']['motivo'] ?? 'Cancelado pelo vendedor';
            $jb->data_cancelamento  = date('Y-m-d H:i:s');
            $jb->store();

            TTransaction::close();

            return [
                'jb_id'    => $jb_id,
                'cancelado' => true,
                'data_cancelamento' => $jb->data_cancelamento,
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Retorna os dados completos de um bilhete para reimpressão.
     */
    public static function detalhe($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            $jb_id      = (int) ($param['bilhete_id'] ?? 0);

            if (!$usuario_id) throw new Exception('Usuário não autenticado');
            if (!$jb_id)      throw new Exception('bilhete_id é obrigatório');

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            $jb       = new MovJb($jb_id);

            if ($jb->vendedor_id != $vendedor->vendedor_id)
            {
                throw new Exception('Bilhete não pertence a este vendedor');
            }

            // Reimpressão: valida e registra
            if ((int) ($param['reimprimir'] ?? 0) === 1)
            {
                self::processarReimpressao($jb, $vendedor);
            }

            $conn = TTransaction::get();

            // Busca sorteios do bilhete
            $stmtSorteios = $conn->prepare(
                "SELECT js.jb_sorteio_id, js.sorteio_id, js.modalidade_id,
                        js.palpites, js.colocao_inicial, js.colocao_final,
                        js.valor_palpites, js.total_sorteio,
                        js.sorteado, js.sorteado_valor, js.previsao_premio,
                        m.apresentacao AS modalidade_apresentacao,
                        ms.data_sorteio, ms.sorteio_numero,
                        COALESCE(e.descricao_mobile, e.descricao) AS extracao_descricao
                 FROM mov_jb_sorteio js
                 JOIN cad_modalidade m ON m.modalidade_id = js.modalidade_id
                 JOIN mov_sorteio ms ON ms.sorteio_id = js.sorteio_id
                 JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                 WHERE js.jb_id = :jb_id
                 ORDER BY js.sorteio_id, js.modalidade_id"
            );
            $stmtSorteios->execute([':jb_id' => $jb_id]);
            $sorteios = $stmtSorteios->fetchAll(\PDO::FETCH_ASSOC);

            TTransaction::close();

            $area = Area::find($vendedor->area_id);

            return [
                'jb_id'              => (int) $jb->jb_id,
                'bilhete_numero'     => (int) $jb->bilhete_numero,
                'string_autorizacao' => $jb->string_autorizacao,
                'data_hora'          => $jb->data_hora,
                'nome_cliente'       => $jb->nome_cliente,
                'fone_cliente'       => $jb->fone_cliente,
                'total_bilhete'      => (float) $jb->total_bilhete,
                'cancelado'          => $jb->cancelado,
                'reimpressao'        => (int) $jb->reimpressao,
                'vendedor_nome'      => $vendedor->nome,
                'area_descricao'     => $area ? $area->descricao : '',
                'sorteios'           => array_map(function($s) {
                    return [
                        'jb_sorteio_id'          => (int) $s['jb_sorteio_id'],
                        'sorteio_id'             => (int) $s['sorteio_id'],
                        'sorteio_numero'         => (int) $s['sorteio_numero'],
                        'data_sorteio'           => $s['data_sorteio'],
                        'extracao_descricao'     => $s['extracao_descricao'],
                        'modalidade_id'          => (int) $s['modalidade_id'],
                        'modalidade_apresentacao'=> $s['modalidade_apresentacao'],
                        'palpites'               => explode(',', $s['palpites']),
                        'colocao_inicial'        => (int) $s['colocao_inicial'],
                        'colocao_final'          => (int) $s['colocao_final'],
                        'valor_palpites'         => (float) $s['valor_palpites'],
                        'total_sorteio'          => (float) $s['total_sorteio'],
                        'sorteado'               => $s['sorteado'],
                        'sorteado_valor'         => (float) $s['sorteado_valor'],
                        'previsao_premio'        => (float) $s['previsao_premio'],
                    ];
                }, $sorteios),
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Lista os bilhetes do vendedor autenticado com filtros opcionais.
     */
    public static function lista($param)
    {
        try
        {
            $usuario_id  = $param['_auth']['id'] ?? null;
            $data_inicio = $param['data_inicio'] ?? date('Y-m-d');
            $data_fim    = $param['data_fim']    ?? date('Y-m-d');
            $situacao    = $param['situacao']    ?? 'todos'; // todos|ativos|cancelados|premiados
            $pagina      = max(1, (int) ($param['pagina'] ?? 1));
            $por_pagina  = min(50, max(10, (int) ($param['por_pagina'] ?? 20)));
            $offset      = ($pagina - 1) * $por_pagina;

            if (!$usuario_id) throw new Exception('Usuário não autenticado');

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            $conn     = TTransaction::get();

            $where  = "WHERE jb.vendedor_id = :vid AND DATE(jb.data_hora) BETWEEN :di AND :df";
            $binds  = [':vid' => $vendedor->vendedor_id, ':di' => $data_inicio, ':df' => $data_fim];

            if ($situacao === 'ativos')     $where .= " AND jb.cancelado = 'N'";
            if ($situacao === 'cancelados') $where .= " AND jb.cancelado = 'S'";

            $sql = "
                SELECT
                    jb.jb_id, jb.bilhete_numero, jb.data_hora,
                    jb.total_bilhete, jb.comissao_valor,
                    jb.cancelado, jb.string_autorizacao,
                    jb.sorteios_ids, jb.sorteios_quantidade,
                    jb.nome_cliente
                FROM mov_jb jb
                {$where}
                ORDER BY jb.data_hora DESC
                LIMIT {$por_pagina} OFFSET {$offset}
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute($binds);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $stmtTotal = $conn->prepare("SELECT COUNT(*) FROM mov_jb jb {$where}");
            $stmtTotal->execute($binds);
            $total = (int) $stmtTotal->fetchColumn();

            TTransaction::close();

            return [
                'total'     => $total,
                'pagina'    => $pagina,
                'por_pagina'=> $por_pagina,
                'bilhetes'  => array_map(fn($r) => [
                    'jb_id'              => (int)   $r['jb_id'],
                    'bilhete_numero'     => (int)   $r['bilhete_numero'],
                    'data_hora'          =>          $r['data_hora'],
                    'total_bilhete'      => (float) $r['total_bilhete'],
                    'comissao_valor'     => (float) $r['comissao_valor'],
                    'cancelado'          =>          $r['cancelado'],
                    'string_autorizacao' =>          $r['string_autorizacao'],
                    'sorteios_ids'       =>          $r['sorteios_ids'],
                    'nome_cliente'       =>          $r['nome_cliente'],
                ], $rows),
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    // ─── helpers privados ────────────────────────────────────────────────────

    private static function getVendedor($usuario_id)
    {
        $repo = new TRepository('Vendedor');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('usuario_id', '=', $usuario_id));
        $criteria->add(new TFilter('ativo', '=', 'S'));
        $lista = $repo->load($criteria);

        if (empty($lista)) throw new Exception('Vendedor não encontrado');
        return $lista[0];
    }

    private static function validarTerminal($terminal_id, $vendedor_id)
    {
        $repo = new TRepository('Terminal');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('terminal_id', '=', $terminal_id));
        $criteria->add(new TFilter('vendedor_id', '=', $vendedor_id));
        $criteria->add(new TFilter('ativo', '=', 'S'));
        $lista = $repo->load($criteria);

        if (empty($lista)) throw new Exception('Terminal inválido para este vendedor');
    }

    private static function validarSorteioAberto($sorteio_id, $area_id)
    {
        $conn = TTransaction::get();
        $stmt = $conn->prepare(
            "SELECT ms.sorteio_id FROM mov_sorteio ms
             JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
             JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id
                AND ae.area_id = :area_id AND ae.ativo = true
             WHERE ms.sorteio_id = :sid
               AND ms.situacao = 'A'
               AND e.hora_limite > CURRENT_TIME"
        );
        $stmt->execute([':area_id' => $area_id, ':sid' => $sorteio_id]);

        if (!$stmt->fetch()) throw new Exception("Sorteio {$sorteio_id} não disponível para esta área ou já encerrado");
    }

    private static function validarLimitePalpite($sorteio_id, $modalidade_id, $area_id, $valor)
    {
        $conn = TTransaction::get();
        $stmt = $conn->prepare(
            "SELECT COALESCE(al.limite_palpite, m.limite_palpite, 0) AS limite
             FROM cad_modalidade m
             LEFT JOIN cfg_area_limite al
                ON al.modalidade_id = m.modalidade_id AND al.area_id = :area_id
             WHERE m.modalidade_id = :mid"
        );
        $stmt->execute([':area_id' => $area_id, ':mid' => $modalidade_id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row && (float) $row['limite'] > 0 && $valor > (float) $row['limite'])
        {
            throw new Exception("Valor R$ {$valor} excede o limite de R$ {$row['limite']} para esta modalidade");
        }
    }

    private static function validarLimiteVenda($vendedor, $total)
    {
        if ((float) $vendedor->limite_venda > 0 && $total > (float) $vendedor->limite_venda)
        {
            throw new Exception("Total do bilhete R$ {$total} excede o limite de venda do vendedor");
        }
    }

    private static function proximoBilheteNumero($vendedor_id)
    {
        $conn = TTransaction::get();
        $stmt = $conn->prepare(
            "SELECT COALESCE(MAX(bilhete_numero), 0) + 1 FROM mov_jb
             WHERE vendedor_id = :vid AND DATE(data_hora) = CURRENT_DATE"
        );
        $stmt->execute([':vid' => $vendedor_id]);
        return (int) $stmt->fetchColumn();
    }

    private static function gerarAutorizacao()
    {
        return strtoupper(date('ymd') . substr(md5(uniqid('', true)), 0, 6));
    }

    private static function processarReimpressao($jb, $vendedor)
    {
        if ($vendedor->pode_reimprimir !== 'S')
        {
            throw new Exception('Vendedor não tem permissão para reimprimir');
        }

        $qtde_max = (int) $vendedor->pode_reimprimir_qtde;
        if ($qtde_max > 0 && (int) $jb->reimpressao >= $qtde_max)
        {
            throw new Exception('Limite de reimpressões atingido para este bilhete');
        }

        $jb->reimpressao     = (int) $jb->reimpressao + 1;
        $jb->data_reimpressao = date('Y-m-d H:i:s');
        $jb->store();
    }
}
