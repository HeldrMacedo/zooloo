<?php

use Adianti\Database\TRecord;

class Parametros extends TRecord
{
    const TABLENAME = 'cfg_parametros';
    const PRIMARYKEY = 'parametros_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome_banca');
        parent::addAttribute('cnpj');
        parent::addAttribute('telefone');
        parent::addAttribute('cidade');
        parent::addAttribute('estado');
        parent::addAttribute('site');
        parent::addAttribute('email');
        parent::addAttribute('mensagem_01');
        parent::addAttribute('mensagem_02');
        parent::addAttribute('mensagem_03');
        parent::addAttribute('mensagem_04');
        parent::addAttribute('mensagem_05');
        parent::addAttribute('valor_milhar_brinde');
        parent::addAttribute('valor_bilhetinho');
        parent::addAttribute('ativo_bilhetinho');
        parent::addAttribute('ativo_modalidade');
        parent::addAttribute('qtde_num_mi');
        parent::addAttribute('qtde_num_ci');
        parent::addAttribute('qtde_num_mci');
        parent::addAttribute('ativo_instantaneo');
        parent::addAttribute('ativo_quininha');
        parent::addAttribute('ativo_seninha');
        parent::addAttribute('ativo_lotinha');
        parent::addAttribute('ativo_jb');
        parent::addAttribute('sena_inc_quina');
        parent::addAttribute('sena_inc_quadra');
        parent::addAttribute('quina_inc_quadra');
        parent::addAttribute('quina_inc_terno');
        parent::addAttribute('limite_extracao');
        parent::addAttribute('ativo_milharpremiada');
        parent::addAttribute('valor_milharpremiada');
    }
}