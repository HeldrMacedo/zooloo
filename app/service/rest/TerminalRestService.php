<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

class TerminalRestService
{
    /**
     * Registra ou atualiza o terminal do dispositivo.
     * Busca pelo serial; se não existir, cria novo vinculado ao vendedor autenticado.
     */
    public static function registrar($param)
    {
        try
        {
            $usuario_id = $param['_auth']['id'] ?? null;
            $serial     = trim($param['data']['serial'] ?? '');
            $tipo       = trim($param['data']['tipo'] ?? 'APP');

            if (!$usuario_id)
            {
                throw new Exception('Usuário não autenticado');
            }
            if (empty($serial))
            {
                throw new Exception('Serial do dispositivo é obrigatório');
            }

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);

            $repo     = new TRepository('Terminal');
            $criteria = new TCriteria;
            $criteria->add(new TFilter('vendedor_id', '=', $vendedor->vendedor_id));
            $criteria->add(new TFilter('serial', '=', $serial));
            $terminais = $repo->load($criteria);

            if (!empty($terminais))
            {
                $terminal       = $terminais[0];
                $terminal->tipo = $tipo;
                $terminal->ativo = 'S';
                $terminal->store();
            }
            else
            {
                $terminal              = new Terminal;
                $terminal->vendedor_id = $vendedor->vendedor_id;
                $terminal->serial      = $serial;
                $terminal->tipo        = $tipo;
                $terminal->multi_usuario = 'N';
                $terminal->ativo       = 'S';
                $terminal->store();
            }

            TTransaction::close();

            return [
                'terminal_id'  => (int) $terminal->terminal_id,
                'vendedor_id'  => (int) $terminal->vendedor_id,
                'serial'       => $terminal->serial,
                'tipo'         => $terminal->tipo,
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

        if (empty($lista))
        {
            throw new Exception('Vendedor não encontrado para este usuário');
        }
        return $lista[0];
    }
}
