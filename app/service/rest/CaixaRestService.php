<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class CaixaRestService
{
    /**
     * Retorna o resumo financeiro do vendedor para a data informada (padrão: hoje).
     */
    public static function resumo($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            $data       = $param['data'] ?? date('Y-m-d');

            if (!$usuario_id) throw new Exception('Usuário não autenticado');

            TTransaction::open('permission');

            $vendedor    = self::getVendedor($usuario_id);
            $vendedor_id = $vendedor->vendedor_id;
            $conn        = TTransaction::get();

            // Totais de venda e comissão do dia
            $stmtVendas = $conn->prepare(
                "SELECT
                    COUNT(*)                                   AS qtde_bilhetes,
                    COALESCE(SUM(total_bilhete), 0)            AS total_vendido,
                    COALESCE(SUM(comissao_valor), 0)           AS total_comissao,
                    COUNT(*) FILTER (WHERE cancelado = 'S')    AS qtde_cancelados,
                    COALESCE(SUM(total_bilhete) FILTER (WHERE cancelado = 'S'), 0) AS total_cancelado
                 FROM mov_jb
                 WHERE vendedor_id = :vid AND DATE(data_hora) = :data"
            );
            $stmtVendas->execute([':vid' => $vendedor_id, ':data' => $data]);
            $vendas = $stmtVendas->fetch(\PDO::FETCH_ASSOC);

            // Total de prêmios pagos no dia
            $stmtPremios = $conn->prepare(
                "SELECT COALESCE(SUM(jsp.sorteado_valor_pago), 0) AS total_premios_pagos
                 FROM mov_jb_sorteio jsp
                 JOIN mov_jb jb ON jb.jb_id = jsp.jb_id
                 WHERE jb.vendedor_id = :vid
                   AND DATE(jb.data_hora) = :data
                   AND jsp.sorteado_pago = 'S'"
            );
            $stmtPremios->execute([':vid' => $vendedor_id, ':data' => $data]);
            $premios = $stmtPremios->fetch(\PDO::FETCH_ASSOC);

            // Totais por extração
            $stmtExtracoes = $conn->prepare(
                "SELECT
                    COALESCE(e.descricao_mobile, e.descricao) AS extracao_descricao,
                    COUNT(DISTINCT jb.jb_id)                  AS qtde_bilhetes,
                    COALESCE(SUM(js.total_sorteio), 0)        AS total_vendido
                 FROM mov_jb_sorteio js
                 JOIN mov_jb jb ON jb.jb_id = js.jb_id
                 JOIN mov_sorteio ms ON ms.sorteio_id = js.sorteio_id
                 JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                 WHERE jb.vendedor_id = :vid
                   AND DATE(jb.data_hora) = :data
                   AND jb.cancelado = 'N'
                 GROUP BY e.extracao_id, e.descricao, e.descricao_mobile, e.hora_limite
                 ORDER BY e.hora_limite"
            );
            $stmtExtracoes->execute([':vid' => $vendedor_id, ':data' => $data]);
            $extracoes = $stmtExtracoes->fetchAll(\PDO::FETCH_ASSOC);

            TTransaction::close();

            $total_vendido  = (float) $vendas['total_vendido'];
            $total_cancelado = (float) $vendas['total_cancelado'];
            $total_comissao = (float) $vendas['total_comissao'];
            $total_premios  = (float) $premios['total_premios_pagos'];
            $saldo_liquido  = $total_vendido - $total_cancelado - $total_premios;

            return [
                'data'              => $data,
                'vendedor_id'       => (int) $vendedor_id,
                'vendedor_nome'     => $vendedor->nome,
                'exibe_comissao'    => $vendedor->exibe_comissao,
                'qtde_bilhetes'     => (int) $vendas['qtde_bilhetes'],
                'qtde_cancelados'   => (int) $vendas['qtde_cancelados'],
                'total_vendido'     => $total_vendido,
                'total_cancelado'   => $total_cancelado,
                'total_liquido'     => $total_vendido - $total_cancelado,
                'total_comissao'    => $total_comissao,
                'total_premios_pagos' => $total_premios,
                'saldo_liquido'     => $saldo_liquido,
                'por_extracao'      => array_map(fn($e) => [
                    'extracao_descricao' => $e['extracao_descricao'],
                    'qtde_bilhetes'      => (int)   $e['qtde_bilhetes'],
                    'total_vendido'      => (float) $e['total_vendido'],
                ], $extracoes),
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

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
}
