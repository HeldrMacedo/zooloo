<?php

use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;

/**
 * Validação de terminal (cad_terminal) compartilhada entre login e bilhetes.
 *
 * Regras:
 * - serial deve existir em cad_terminal
 * - ativo = 'S'
 * - se multi_usuario != 'S', o terminal deve pertencer ao vendedor informado
 */
class TerminalAuthHelper
{
    /**
     * Busca terminal pelo serial (caller deve estar em transação permission).
     *
     * @return object|null registro Terminal
     */
    public static function findBySerial(string $serial)
    {
        $serial = trim($serial);
        if ($serial === '') {
            return null;
        }

        $repo = new TRepository('Terminal');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('serial', '=', $serial));
        $lista = $repo->load($criteria);

        return !empty($lista) ? $lista[0] : null;
    }

    /**
     * Busca terminal pelo id (caller deve estar em transação permission).
     *
     * @return object|null
     */
    public static function findById(int $terminal_id)
    {
        if ($terminal_id <= 0) {
            return null;
        }

        $repo = new TRepository('Terminal');
        $criteria = new TCriteria;
        $criteria->add(new TFilter('terminal_id', '=', $terminal_id));
        $lista = $repo->load($criteria);

        return !empty($lista) ? $lista[0] : null;
    }

    /**
     * Valida se o terminal pode ser usado pelo vendedor.
     * Lança Exception com mensagem amigável em caso de falha.
     *
     * @param object|null $terminal    registro Terminal
     * @param int|null    $vendedor_id id do vendedor autenticado (pode ser null)
     * @return object terminal validado
     * @throws Exception
     */
    public static function assertUsable($terminal, $vendedor_id = null)
    {
        if (!$terminal) {
            throw new Exception('Terminal não cadastrado. Informe o serial ao administrador.');
        }

        if (($terminal->ativo ?? 'N') !== 'S') {
            throw new Exception('Terminal bloqueado.');
        }

        $multi = strtoupper((string) ($terminal->multi_usuario ?? 'N'));
        if ($multi !== 'S') {
            if ($vendedor_id === null || (int) $vendedor_id <= 0) {
                throw new Exception('Terminal vinculado a outro vendedor.');
            }
            if ((int) $terminal->vendedor_id !== (int) $vendedor_id) {
                throw new Exception('Terminal vinculado a outro vendedor.');
            }
        }

        return $terminal;
    }

    /**
     * Valida serial no contexto de login.
     *
     * @return array{terminal_id:int,serial:string,tipo:string,multi_usuario:string}
     * @throws Exception
     */
    public static function validateForLogin(string $serial, $vendedor_id = null): array
    {
        $serial = trim($serial);
        if ($serial === '') {
            throw new Exception('Serial do terminal é obrigatório.');
        }

        $terminal = self::findBySerial($serial);
        self::assertUsable($terminal, $vendedor_id);

        return [
            'terminal_id'   => (int) $terminal->terminal_id,
            'serial'        => (string) $terminal->serial,
            'tipo'          => (string) ($terminal->tipo ?? 'APP'),
            'multi_usuario' => (string) ($terminal->multi_usuario ?? 'N'),
        ];
    }

    /**
     * Valida terminal_id no contexto de emissão de bilhete.
     *
     * @throws Exception
     */
    public static function validateForBilhete(int $terminal_id, $vendedor_id): void
    {
        $terminal = self::findById($terminal_id);
        self::assertUsable($terminal, $vendedor_id);
    }
}
