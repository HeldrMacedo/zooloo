<?php

use Adianti\Database\TRecord;

class IntJogo extends TRecord
{
    const TABLENAME = 'int_jogo';
    const PRIMARYKEY = 'jogo_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('filtro_banca');
        parent::addAttribute('descricao_grupo');
        parent::addAttribute('descricao');
        parent::addAttribute('abreviacao');
        parent::addAttribute('tamanho_max');
        parent::addAttribute('ativo');
        parent::addAttribute('qtd_colocacao_premio');
        parent::addAttribute('informar_valores_modalidade');
        parent::addAttribute('orientacao');
        parent::addAttribute('habilitar_edicao_regular');
    }
}