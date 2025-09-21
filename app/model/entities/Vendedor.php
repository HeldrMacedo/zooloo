<?php

use Adianti\Database\TRecord;

class Vendedor extends TRecord
{
    const TABLENAME = 'cad_vendedor';
    const PRIMARYKEY = 'vendedor_id';
    const IDPOLICY = 'max';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('area_id');
        parent::addAttribute('coletor_id');
        parent::addAttribute('nome');
        parent::addAttribute('cep');
        parent::addAttribute('rua');
        parent::addAttribute('numero');
        parent::addAttribute('bairro');
        parent::addAttribute('cidade');
        parent::addAttribute('uf');
        parent::addAttribute('comissao');
        parent::addAttribute('pode_cancelar');
        parent::addAttribute('limite_venda');
        parent::addAttribute('exibe_comissao');
        parent::addAttribute('exibe_premiacao');
        parent::addAttribute('tipo_limite');
        parent::addAttribute('treinamento');
        parent::addAttribute('usuario_id');
        parent::addAttribute('observacao');
        parent::addAttribute('ativo');
        parent::addAttribute('pode_cancelar_tempo');
        parent::addAttribute('pode_cancelar_qtde');
        parent::addAttribute('pode_pagar');
        parent::addAttribute('pode_pagar_outro');
        parent::addAttribute('pode_reimprimir');
        parent::addAttribute('pode_reimprimir_qtde');
        parent::addAttribute('pode_reimprimir_tempo');
        parent::addAttribute('pode_reimprimir_sort_naopg');
        parent::addAttribute('pode_reimprimir_sort_pago');
        parent::addAttribute('pode_reimprimir_outro');
        parent::addAttribute('pode_reimprimir_sort_naopg_outro');
        parent::addAttribute('pode_reimprimir_sort_pago_outro');
        parent::addAttribute('reimprimir_data');
        parent::addAttribute('reimprimir_qtde');
    }

    public function get_area()
    {
        return Area::find($this->area_id);
    }

    public function get_coletor()
    {
        return Gerente::find($this->coletor_id);
    }

    public function get_usuario()
    {
        return SystemUser::find($this->usuario_id);
    }
}

