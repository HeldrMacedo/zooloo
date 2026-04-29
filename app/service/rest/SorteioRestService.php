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
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            if (!$usuario_id)
            {
                throw new Exception('Usuário não autenticado');
            }

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            $area_id  = $vendedor->area_id;

            $conn = TTransaction::get();
            $sql  = "
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
                  AND ms.data_sorteio = CURRENT_DATE
                  AND e.hora_limite > CURRENT_TIME
                ORDER BY e.hora_limite ASC
            ";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':area_id' => $area_id]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            TTransaction::close();

            $sorteios = [];
            foreach ($rows as $row)
            {
                $minutos_restantes = self::minutosAteHoraLimite($row['hora_limite']);
                $sorteios[] = [
                    'sorteio_id'                 => (int) $row['sorteio_id'],
                    'sorteio_numero'             => (int) $row['sorteio_numero'],
                    'data_sorteio'               => $row['data_sorteio'],
                    'hora_sorteio'               => $row['hora_sorteio'],
                    'situacao'                   => $row['situacao'],
                    'extracao_id'                => (int) $row['extracao_id'],
                    'extracao_descricao'         => $row['extracao_descricao'],
                    'extracao_descricao_completa'=> $row['extracao_descricao_completa'],
                    'hora_limite'                => $row['hora_limite'],
                    'extracao_instantanea'       => $row['extracao_instantanea'],
                    'minutos_restantes'          => $minutos_restantes,
                    'urgente'                    => $minutos_restantes <= 30,
                ];
            }

            return $sorteios;
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

    private static function minutosAteHoraLimite($hora_limite)
    {
        $agora  = new \DateTime('now');
        $limite = \DateTime::createFromFormat('H:i:s', $hora_limite);
        if (!$limite)
        {
            return 0;
        }
        $limite->setDate((int) $agora->format('Y'), (int) $agora->format('m'), (int) $agora->format('d'));
        $diff = $agora->diff($limite);
        if ($diff->invert)
        {
            return 0;
        }
        return ($diff->h * 60) + $diff->i;
    }
}
