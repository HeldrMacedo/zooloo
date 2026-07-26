<?php

use Adianti\Database\TTransaction;
use Adianti\Database\TRepository;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;

/**
 * REST de terminal para o app móvel.
 *
 * Não autocadastra dispositivo: o serial deve ser pré-cadastrado em cad_terminal
 * pelo back-office (TerminalList / TerminalForm).
 */
class TerminalRestService
{
    /**
     * Confirma o terminal do dispositivo para o vendedor autenticado.
     * Busca pelo serial; se existir e estiver autorizado, devolve os dados.
     * Não cria registro novo.
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
            if ($serial === '')
            {
                throw new Exception('Serial do dispositivo é obrigatório');
            }

            TTransaction::open('permission');

            $vendedor = self::getVendedor($usuario_id);
            $terminalData = TerminalAuthHelper::validateForLogin($serial, $vendedor->vendedor_id);

            // Atualiza tipo se informado (validateForLogin já exige ativo=S).
            $terminal = TerminalAuthHelper::findBySerial($serial);
            if ($terminal && $tipo !== '')
            {
                $terminal->tipo = $tipo;
                $terminal->store();
                $terminalData['tipo'] = (string) $terminal->tipo;
            }

            TTransaction::close();

            return [
                'terminal_id'  => $terminalData['terminal_id'],
                'vendedor_id'  => (int) $vendedor->vendedor_id,
                'serial'       => $terminalData['serial'],
                'tipo'         => $terminalData['tipo'],
            ];
        }
        catch (Exception $e)
        {
            if (TTransaction::get())
            {
                TTransaction::rollback();
            }
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
