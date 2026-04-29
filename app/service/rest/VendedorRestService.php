<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class VendedorRestService
{
    /**
     * Retorna o perfil e permissões do vendedor autenticado.
     */
    public static function me($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            if (!$usuario_id)
            {
                throw new Exception('Usuário não autenticado');
            }

            TTransaction::open('permission');

            $repo = new TRepository('Vendedor');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('usuario_id', '=', $usuario_id));
            $criteria->add(new TFilter('ativo', '=', 'S'));
            $vendedores = $repo->load($criteria);

            if (empty($vendedores))
            {
                TTransaction::close();
                throw new Exception('Vendedor não encontrado para este usuário');
            }

            $vendedor = $vendedores[0];
            $area     = Area::find($vendedor->area_id);

            TTransaction::close();

            return [
                'vendedor_id'                     => (int) $vendedor->vendedor_id,
                'nome'                            => $vendedor->nome,
                'area_id'                         => (int) $vendedor->area_id,
                'area_descricao'                  => $area ? $area->descricao : '',
                'coletor_id'                      => (int) $vendedor->coletor_id,
                'comissao'                        => (float) $vendedor->comissao,
                'limite_venda'                    => (float) $vendedor->limite_venda,
                'tipo_limite'                     => $vendedor->tipo_limite,
                'exibe_comissao'                  => $vendedor->exibe_comissao,
                'exibe_premiacao'                 => $vendedor->exibe_premiacao,
                'pode_cancelar'                   => $vendedor->pode_cancelar,
                'pode_cancelar_tempo'             => $vendedor->pode_cancelar_tempo,
                'pode_cancelar_qtde'              => (int) $vendedor->pode_cancelar_qtde,
                'pode_pagar'                      => $vendedor->pode_pagar,
                'pode_pagar_outro'                => $vendedor->pode_pagar_outro,
                'pode_reimprimir'                 => $vendedor->pode_reimprimir,
                'pode_reimprimir_qtde'            => (int) $vendedor->pode_reimprimir_qtde,
                'pode_reimprimir_tempo'           => $vendedor->pode_reimprimir_tempo,
                'treinamento'                     => $vendedor->treinamento,
            ];
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            throw $e;
        }
    }
}
