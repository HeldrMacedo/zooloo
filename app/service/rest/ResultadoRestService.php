<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class ResultadoRestService
{
    /**
     * Retorna os resultados recentes dos sorteios disponíveis para a área do vendedor.
     * Parâmetros opcionais: dias (padrão 3), extracao_id
     */
    public static function recentes($param)
    {
        try
        {
            $usuario_id  = $param['_auth']['id'] ?? null;
            $dias        = min(30, max(1, (int) ($param['dias'] ?? 3)));
            $extracao_id = (int) ($param['extracao_id'] ?? 0);

            if (!$usuario_id) throw new Exception('Usuário não autenticado');

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            $area_id  = $vendedor->area_id;
            $conn     = TTransaction::get();

            $filtro_extracao = $extracao_id ? "AND ms.extracao_id = {$extracao_id}" : '';

            $sql = "
                SELECT
                    ms.sorteio_id,
                    ms.sorteio_numero,
                    ms.data_sorteio,
                    ms.hora_sorteio,
                    ms.numeros_sorteados,
                    ms.situacao,
                    e.extracao_id,
                    COALESCE(e.descricao_mobile, e.descricao) AS extracao_descricao
                FROM mov_sorteio ms
                JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id
                    AND ae.area_id = :area_id AND ae.ativo = true
                WHERE ms.situacao = 'F'
                  AND ms.data_sorteio >= CURRENT_DATE - INTERVAL '{$dias} days'
                  {$filtro_extracao}
                ORDER BY ms.data_sorteio DESC, e.hora_limite DESC
                LIMIT 50
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':area_id' => $area_id]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            TTransaction::close();

            $grupos = [
                1  => 'Avestruz',  2  => 'Águia',   3  => 'Burro',    4  => 'Borboleta',
                5  => 'Cachorro',  6  => 'Cabra',    7  => 'Carneiro', 8  => 'Camelo',
                9  => 'Cobra',     10 => 'Coelho',   11 => 'Cavalo',   12 => 'Elefante',
                13 => 'Galo',      14 => 'Gato',     15 => 'Jacaré',   16 => 'Leão',
                17 => 'Macaco',    18 => 'Porco',    19 => 'Pavão',    20 => 'Peru',
                21 => 'Touro',     22 => 'Tigre',    23 => 'Urso',     24 => 'Veado',
                25 => 'Vaca',
            ];

            $resultados = [];
            foreach ($rows as $row)
            {
                $numeros = [];
                if (!empty($row['numeros_sorteados']))
                {
                    $partes = explode(',', $row['numeros_sorteados']);
                    foreach ($partes as $i => $num)
                    {
                        $num    = trim($num);
                        $milhar = str_pad($num, 4, '0', STR_PAD_LEFT);
                        $grupo  = (int) ceil((int) substr($milhar, -2) / 4);
                        $grupo  = max(1, min(25, $grupo));
                        $numeros[] = [
                            'posicao'         => $i + 1,
                            'numero'          => $milhar,
                            'grupo'           => $grupo,
                            'grupo_descricao' => $grupos[$grupo] ?? '',
                        ];
                    }
                }

                $resultados[] = [
                    'sorteio_id'         => (int) $row['sorteio_id'],
                    'sorteio_numero'     => (int) $row['sorteio_numero'],
                    'data_sorteio'       => $row['data_sorteio'],
                    'hora_sorteio'       => $row['hora_sorteio'],
                    'extracao_id'        => (int) $row['extracao_id'],
                    'extracao_descricao' => $row['extracao_descricao'],
                    'numeros'            => $numeros,
                ];
            }

            return $resultados;
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
