<?php

use Adianti\Database\TTransaction;

class VendasJbRestService
{
    /**
     * Equivalente ao endpoint Java GET /api/rel-vendasjb
     * Parâmetros: datainicio, datafim, combo (obrigatórios) + filtros opcionais.
     */
    public static function relVendasJb($param)
    {
        try
        {
            $data_inicio = self::getParam($param, 'datainicio');
            $data_fim    = self::getParam($param, 'datafim');
            $combo       = (int) self::getParam($param, 'combo', 0);

            if (empty($data_inicio) || empty($data_fim))
            {
                throw new Exception('datainicio e datafim são obrigatórios');
            }
            if (!self::isDate($data_inicio) || !self::isDate($data_fim))
            {
                throw new Exception('Formato de data inválido. Use YYYY-MM-DD');
            }

            $area_id       = self::toNullableInt(self::getParam($param, 'area'));
            $extracao_id   = self::toNullableInt(self::getParam($param, 'extracao'));
            $vendedor_id   = self::toNullableInt(self::getParam($param, 'vendedor'));
            $situacao      = self::getParam($param, 'situacao');
            $valor_min     = self::toNullableFloat(self::getParam($param, 'total_sorteio'));
            $nsu           = self::toNullableInt(self::getParam($param, 'nsu'));

            $inicio = new DateTime($data_inicio . ' 00:00:00');
            $fim    = (new DateTime($data_fim . ' 00:00:00'))->modify('+1 day');

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = "
                SELECT
                    v.nsu, v.sorteio_id, v.data_hora, v.vendedor, v.modalidade, v.situacao, v.extracao,
                    v.comissao_sorteio, v.total_sorteio, v.cancelado, v.fone, v.palpites, v.poule,
                    v.qtde_palpites, v.qtde_sorteio, v.reduzida, v.cliente, v.md5, v.colocao_inicial,
                    v.colocao_final, v.sorteado, v.sorteado_valor, v.previsao_premio, v.sorteado_valor_pago,
                    v.apresentacao, v.reimpressao, v.data_reimpressao
                FROM vw_vendajb v
                WHERE v.data_hora >= :data_inicio
                  AND v.data_hora < :data_fim
                  AND (v.filtro_banca = :filtro OR :filtro = 0)
            ";

            $bind = [
                ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
                ':data_fim'    => $fim->format('Y-m-d H:i:s'),
                ':filtro'      => $combo,
            ];

            if ($area_id !== null)
            {
                $sql .= " AND v.area_id = :area_id";
                $bind[':area_id'] = $area_id;
            }
            if ($extracao_id !== null)
            {
                $sql .= " AND v.extracao_id = :extracao_id";
                $bind[':extracao_id'] = $extracao_id;
            }
            if ($vendedor_id !== null)
            {
                $sql .= " AND v.vendedor_id = :vendedor_id";
                $bind[':vendedor_id'] = $vendedor_id;
            }
            if ($situacao !== null && $situacao !== '')
            {
                $sql .= " AND v.situacao ILIKE :situacao";
                $bind[':situacao'] = '%' . $situacao . '%';
            }
            if ($valor_min !== null)
            {
                $sql .= " AND v.total_sorteio >= :valor_min";
                $bind[':valor_min'] = $valor_min;
            }
            if ($nsu !== null)
            {
                $sql .= " AND v.nsu = :nsu";
                $bind[':nsu'] = $nsu;
            }

            $sql .= " ORDER BY v.nsu DESC, v.extracao";

            $stmt = $conn->prepare($sql);
            $stmt->execute($bind);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            TTransaction::close();

            return array_map([self::class, 'mapVenda'], $rows);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Equivalente ao endpoint Java GET /api/vendasnsu/{nsu}
     */
    public static function getNsu($param)
    {
        try
        {
            $nsu = self::toNullableInt(self::getParam($param, 'nsu'));
            if ($nsu === null)
            {
                throw new Exception('nsu é obrigatório');
            }

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = "
                SELECT
                    v.nsu, v.sorteio_id, v.data_hora, v.vendedor, v.modalidade, v.situacao, v.extracao,
                    v.comissao_sorteio, v.total_sorteio, v.cancelado, v.fone, v.palpites, v.poule,
                    v.qtde_palpites, v.qtde_sorteio, v.reduzida, v.cliente, v.md5, v.colocao_inicial,
                    v.colocao_final, v.sorteado, v.sorteado_valor, v.previsao_premio, v.sorteado_valor_pago,
                    v.apresentacao, v.reimpressao, v.data_reimpressao
                FROM vw_vendajb v
                WHERE v.nsu = :nsu
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':nsu' => $nsu]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            TTransaction::close();

            return array_map([self::class, 'mapVenda'], $rows);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Equivalente ao endpoint Java GET /api/vendas-detalhe/{id}
     * Retorna vendedores com venda no dia (filtrando coletor quando id != 0).
     */
    public static function pegarDetalhesVendas($param)
    {
        try
        {
            $id = (int) (self::getParam($param, 'id', 0));

            $inicio = new DateTime(date('Y-m-d') . ' 00:00:00');
            $fim    = (clone $inicio)->modify('+1 day');

            TTransaction::open('permission');
            $conn = TTransaction::get();

            $sql = "
                SELECT vv.vendedor
                FROM vw_vendajb vv
                WHERE vv.data_hora >= :data_inicio
                  AND vv.data_hora < :data_fim
            ";
            $bind = [
                ':data_inicio' => $inicio->format('Y-m-d H:i:s'),
                ':data_fim'    => $fim->format('Y-m-d H:i:s'),
            ];

            if ($id !== 0)
            {
                $sql .= " AND vv.coletor_id = :coletor_id";
                $bind[':coletor_id'] = $id;
            }

            $sql .= " GROUP BY vv.vendedor";

            $stmt = $conn->prepare($sql);
            $stmt->execute($bind);
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

            TTransaction::close();
            return $rows;
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }

    // aliases para facilitar migração gradual
    public static function consultaVendasJb($param)
    {
        return self::relVendasJb($param);
    }

    public static function buscarNsu($param)
    {
        return self::getNsu($param);
    }

    public static function buscarDetalhes($param)
    {
        return self::pegarDetalhesVendas($param);
    }

    private static function mapVenda(array $row)
    {
        return [
            'nsu'                => isset($row['nsu']) ? (int) $row['nsu'] : 0,
            'sorteio_id'         => isset($row['sorteio_id']) ? (int) $row['sorteio_id'] : 0,
            'data_hora'          => $row['data_hora'] ?? null,
            'vendedor'           => $row['vendedor'] ?? null,
            'modalidade'         => $row['modalidade'] ?? null,
            'situacao'           => $row['situacao'] ?? null,
            'extracao'           => $row['extracao'] ?? null,
            'comissao_sorteio'   => isset($row['comissao_sorteio']) ? (float) $row['comissao_sorteio'] : 0.0,
            'total_sorteio'      => isset($row['total_sorteio']) ? (float) $row['total_sorteio'] : 0.0,
            'cancelado'          => $row['cancelado'] ?? null,
            'fone'               => $row['fone'] ?? null,
            'palpite'            => $row['palpites'] ?? null,
            'pouple'             => isset($row['poule']) ? (int) $row['poule'] : 0,
            'qtde_palpites'      => isset($row['qtde_palpites']) ? (int) $row['qtde_palpites'] : 0,
            'qtde_sorteio'       => isset($row['qtde_sorteio']) ? (int) $row['qtde_sorteio'] : 0,
            'reduzida'           => $row['reduzida'] ?? null,
            'cliente'            => $row['cliente'] ?? null,
            'md5'                => $row['md5'] ?? null,
            'colocacao_inicial'  => isset($row['colocao_inicial']) ? (int) $row['colocao_inicial'] : 0,
            'colocacao_final'    => isset($row['colocao_final']) ? (int) $row['colocao_final'] : 0,
            'sorteado'           => $row['sorteado'] ?? null,
            'sorteado_valor'     => isset($row['sorteado_valor']) ? (float) $row['sorteado_valor'] : 0.0,
            'previsao_premio'    => isset($row['previsao_premio']) ? (float) $row['previsao_premio'] : 0.0,
            'sorteado_valor_pago'=> isset($row['sorteado_valor_pago']) ? (float) $row['sorteado_valor_pago'] : 0.0,
            'apresentacao'       => $row['apresentacao'] ?? null,
            'reimpressao'        => isset($row['reimpressao']) ? (int) $row['reimpressao'] : 0,
            'data_reimpressao'   => $row['data_reimpressao'] ?? null,
        ];
    }

    private static function getParam(array $param, string $key, $default = null)
    {
        if (array_key_exists($key, $param))
        {
            return $param[$key];
        }
        if (isset($param['data']) && is_array($param['data']) && array_key_exists($key, $param['data']))
        {
            return $param['data'][$key];
        }
        return $default;
    }

    private static function toNullableInt($value)
    {
        if ($value === null || $value === '')
        {
            return null;
        }
        return (int) $value;
    }

    private static function toNullableFloat($value)
    {
        if ($value === null || $value === '')
        {
            return null;
        }
        return (float) $value;
    }

    private static function isDate(string $value)
    {
        $d = DateTime::createFromFormat('Y-m-d', $value);
        return $d && $d->format('Y-m-d') === $value;
    }
}
