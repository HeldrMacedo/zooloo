<?php

use Adianti\Database\TRecord;
use Adianti\Database\TCriteria;
use Adianti\Database\TFilter;
use Adianti\Database\TRepository;

/**
 * Token de autenticação móvel (refresh-token revogável).
 * Tabela criada em app/migrations/etapa2_auth.sql.
 */
class MobAuthToken extends TRecord
{
    const TABLENAME  = 'mob_auth_token';
    const PRIMARYKEY = 'jti';
    const IDPOLICY   = 'serial';

    public function __construct($id = null, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('user_id');
        parent::addAttribute('token_type');
        parent::addAttribute('parent_jti');
        parent::addAttribute('issued_at');
        parent::addAttribute('expires_at');
        parent::addAttribute('revoked');
        parent::addAttribute('revoked_at');
        parent::addAttribute('revoked_reason');
        parent::addAttribute('user_agent');
        parent::addAttribute('ip_address');
    }

    /**
     * Marca token como revogado (não deleta — auditoria).
     */
    public function revoke($reason = 'manual')
    {
        $this->revoked        = 't';
        $this->revoked_at     = date('Y-m-d H:i:s');
        $this->revoked_reason = substr($reason, 0, 40);
        $this->store();
    }

    /**
     * Retorna true se o token existe na base, é do tipo esperado, não está revogado e não expirou.
     */
    public static function isUsable($jti, $expected_type)
    {
        if (empty($jti)) return false;
        $token = self::find($jti);
        if (!$token) return false;
        if ($token->token_type !== $expected_type) return false;
        if ($token->revoked === 't' || $token->revoked === true || $token->revoked === '1') return false;
        if (strtotime($token->expires_at) < time()) return false;
        return true;
    }

    /**
     * Revoga todos os refresh tokens ativos de um usuário (logout global).
     */
    public static function revokeAllForUser($user_id, $reason = 'logout_all')
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('user_id', '=', $user_id));
        $criteria->add(new TFilter('revoked', '=', 'f'));
        $repo = new TRepository(self::class);
        foreach ((array) $repo->load($criteria) as $tk) {
            $tk->revoke($reason);
        }
    }

    /**
     * Best-effort: remove tokens expirados há mais de N dias (chamado oportunisticamente).
     */
    public static function purgeOlderThan($days = 30)
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $criteria = new TCriteria;
        $criteria->add(new TFilter('expires_at', '<', $cutoff));
        $repo = new TRepository(self::class);
        $repo->delete($criteria);
    }
}
