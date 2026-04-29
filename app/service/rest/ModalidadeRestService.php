<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class ModalidadeRestService
{
    /**
     * Retorna as modalidades disponíveis para um sorteio na área do vendedor.
     * Inclui cotação (cfg_area_cotacao), limite da área (cfg_area_limite) e
     * informações do jogo (int_jogo) necessárias para a UI do app.
     */
    public static function disponiveis($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            $sorteio_id = (int) ($param['sorteio_id'] ?? 0);

            if (!$usuario_id)
            {
                throw new Exception('Usuário não autenticado');
            }
            if (!$sorteio_id)
            {
                throw new Exception('sorteio_id é obrigatório');
            }

            TTransaction::open('permission');

            $vendedor   = self::getVendedor($usuario_id);
            $area_id    = $vendedor->area_id;

            $conn = TTransaction::get();

            // Busca o sorteio para obter extracao_id
            $stmtS = $conn->prepare('SELECT extracao_id FROM mov_sorteio WHERE sorteio_id = :id AND situacao = \'A\'');
            $stmtS->execute([':id' => $sorteio_id]);
            $sorteio = $stmtS->fetch(\PDO::FETCH_ASSOC);

            if (!$sorteio)
            {
                TTransaction::close();
                throw new Exception('Sorteio não encontrado ou não está aberto');
            }

            $extracao_id = (int) $sorteio['extracao_id'];

            $sql = "
                SELECT
                    m.modalidade_id,
                    m.apresentacao,
                    m.ordem,
                    m.limite_palpite                     AS limite_palpite_modalidade,
                    m.limite_aceite,
                    m.multiplicador_colocacao_01,
                    m.multiplicador_colocacao_02,
                    m.multiplicador_colocacao_03,
                    m.multiplicador_colocacao_04,
                    m.multiplicador_colocacao_05,
                    j.jogo_id,
                    j.descricao_grupo,
                    j.descricao                          AS jogo_descricao,
                    j.abreviacao,
                    j.tamanho_max,
                    j.qtd_colocacao_premio,
                    j.informar_valores_modalidade,
                    j.orientacao,
                    COALESCE(ac.multiplicador, m.multiplicador) AS multiplicador,
                    COALESCE(al.limite_palpite, m.limite_palpite) AS limite_palpite
                FROM cad_modalidade m
                JOIN int_jogo j ON j.jogo_id = m.jogo_id
                LEFT JOIN cfg_area_cotacao ac
                    ON ac.modalidade_id = m.modalidade_id
                    AND ac.area_id = :area_id
                    AND (ac.extracao_id = :extracao_id OR ac.extracao_id IS NULL)
                LEFT JOIN cfg_area_limite al
                    ON al.modalidade_id = m.modalidade_id
                    AND al.area_id = :area_id2
                WHERE m.ativo = 'S'
                  AND j.ativo = 'S'
                  AND EXISTS (
                      SELECT 1 FROM cfg_extracao_modalidade em
                      WHERE em.extracao_id = :extracao_id2
                        AND em.modalidade_id = m.modalidade_id
                  )
                ORDER BY j.descricao_grupo, m.ordem
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':area_id'      => $area_id,
                ':extracao_id'  => $extracao_id,
                ':area_id2'     => $area_id,
                ':extracao_id2' => $extracao_id,
            ]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Busca palpites com cotação especial (cfg_palpite_cotado)
            $palpitesCotados = self::getPalpitesCotados($conn, array_column($rows, 'modalidade_id'));

            TTransaction::close();

            // Agrupa por descricao_grupo para facilitar a UI
            $grupos = [];
            foreach ($rows as $row)
            {
                $grupo = $row['descricao_grupo'];
                if (!isset($grupos[$grupo]))
                {
                    $grupos[$grupo] = ['grupo' => $grupo, 'modalidades' => []];
                }

                $modalidade_id = (int) $row['modalidade_id'];
                $grupos[$grupo]['modalidades'][] = [
                    'modalidade_id'               => $modalidade_id,
                    'apresentacao'                => $row['apresentacao'],
                    'ordem'                       => (int) $row['ordem'],
                    'multiplicador'               => (float) $row['multiplicador'],
                    'limite_palpite'              => (float) $row['limite_palpite'],
                    'limite_aceite'               => (float) $row['limite_aceite'],
                    'jogo_id'                     => (int) $row['jogo_id'],
                    'jogo_descricao'              => $row['jogo_descricao'],
                    'jogo_abreviacao'             => $row['abreviacao'],
                    'tamanho_max'                 => (int) $row['tamanho_max'],
                    'qtd_colocacao_premio'        => (int) $row['qtd_colocacao_premio'],
                    'informar_valores_modalidade' => $row['informar_valores_modalidade'],
                    'orientacao'                  => $row['orientacao'],
                    'mult_col_01'                 => (float) $row['multiplicador_colocacao_01'],
                    'mult_col_02'                 => (float) $row['multiplicador_colocacao_02'],
                    'mult_col_03'                 => (float) $row['multiplicador_colocacao_03'],
                    'mult_col_04'                 => (float) $row['multiplicador_colocacao_04'],
                    'mult_col_05'                 => (float) $row['multiplicador_colocacao_05'],
                    'palpites_cotados'            => $palpitesCotados[$modalidade_id] ?? [],
                ];
            }

            return array_values($grupos);
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

        if (empty($lista))
        {
            throw new Exception('Vendedor não encontrado');
        }
        return $lista[0];
    }

    private static function getPalpitesCotados($conn, array $modalidade_ids)
    {
        if (empty($modalidade_ids))
        {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($modalidade_ids), '?'));
        $stmt = $conn->prepare(
            "SELECT modalidade_id, palpite, cotacao FROM cfg_palpite_cotado
             WHERE ativo = 'S' AND modalidade_id IN ({$placeholders})"
        );
        $stmt->execute($modalidade_ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $r)
        {
            $map[(int) $r['modalidade_id']][] = [
                'palpite' => $r['palpite'],
                'cotacao' => (float) $r['cotacao'],
            ];
        }
        return $map;
    }
}
