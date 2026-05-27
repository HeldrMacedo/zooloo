<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class SorteioRestService
{
    /**
     * Retorna os sorteios abertos disponíveis para a área do vendedor autenticado.
     * Filtra por:
     *   - situacao = 'A' (Aberto)
     *   - extração ativa na área via cfg_area_extracao
     *   - hora_limite da extração ainda não atingida
     */
    public static function abertos($param)
    {
        try {

            $usuario_id = $param['_auth']['id'] ?? null;
            // var_dump($usuario_id);
            if (!$usuario_id) {
                throw new Exception('Usuário não autenticado');
            }

            $data_sorteio = $param['data']['data_sorteio'] ?? date('Y-m-d');

            TTransaction::open('permission');
            // var_dump($param);
            $vendedor = self::getVendedor($usuario_id);
            $area_id = $vendedor->area_id;
            // var_dump($area_id);
            $conn = TTransaction::get();
            $sql = "
                SELECT
                    ms.sorteio_id,
                    ms.sorteio_numero,
                    ms.data_sorteio,
                    ms.hora_sorteio,
                    ms.situacao,
                    e.extracao_id,
                    COALESCE(e.descricao_mobile, e.descricao) AS extracao_descricao,
                    e.descricao                               AS extracao_descricao_completa,
                    e.hora_limite,
                    e.extracao_instantanea
                FROM mov_sorteio ms
                JOIN cad_extracao e ON e.extracao_id = ms.extracao_id
                JOIN cfg_area_extracao ae ON ae.extracao_id = e.extracao_id
                    AND ae.area_id = :area_id
                    AND ae.ativo = true
                WHERE ms.situacao = 'A'
                  AND e.ativo = 'S'
                  AND ms.data_sorteio = :data_sorteio
                ORDER BY e.hora_limite ASC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':area_id' => $area_id,
                ':data_sorteio' => $data_sorteio

            ]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            // var_dump($rows);

            TTransaction::close();

            $sorteios = [];
            foreach ($rows as $row) {
                $minutos_restantes = self::minutosAteHoraLimite($row['data_sorteio'], $row['hora_limite']);
                $sorteios[] = [
                    'sorteio_id' => (int) $row['sorteio_id'],
                    'sorteio_numero' => (int) $row['sorteio_numero'],
                    'data_sorteio' => $row['data_sorteio'],
                    'hora_sorteio' => $row['hora_sorteio'],
                    'situacao' => $row['situacao'],
                    'extracao_id' => (int) $row['extracao_id'],
                    'descricao_mobile' => $row['extracao_descricao'],
                    'descricao' => $row['extracao_descricao_completa'],
                    'hora_limite' => $row['hora_limite'],
                    'extracao_instantanea' => $row['extracao_instantanea'],
                    'minutos_restantes' => $minutos_restantes,
                    'urgente' => $minutos_restantes <= 30,
                ];
            }

            return $sorteios;
        } catch (Exception $e) {
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

        if (empty($lista)) {
            throw new Exception('Vendedor não encontrado');
        }
        return $lista[0];
    }

    private static function minutosAteHoraLimite($data_sorteio, $hora_limite)
    {
        $agora = new \DateTime('now');
        $limite = \DateTime::createFromFormat('Y-m-d H:i:s', $data_sorteio . ' ' . $hora_limite);
        if (!$limite) {
            return 0;
        }
        if ($agora >= $limite) {
            return 0;
        }
        $diff = $agora->diff($limite);
        return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }
}
