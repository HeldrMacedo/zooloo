-- Etapa 2 - Autenticação móvel: refresh tokens revogáveis
-- Idempotente. Rodar no banco "applications" (mesmo de cad_vendedor / system_users).
-- psql -h postgres -U postgres -d applications -f etapa2_auth.sql

CREATE TABLE IF NOT EXISTS mob_auth_token (
    jti              varchar(64) PRIMARY KEY,
    user_id          integer     NOT NULL,
    token_type       varchar(10) NOT NULL CHECK (token_type IN ('access','refresh')),
    parent_jti       varchar(64),
    issued_at        timestamp   NOT NULL DEFAULT now(),
    expires_at       timestamp   NOT NULL,
    revoked          boolean     NOT NULL DEFAULT false,
    revoked_at       timestamp,
    revoked_reason   varchar(40),
    user_agent       varchar(255),
    ip_address       varchar(45)
);

CREATE INDEX IF NOT EXISTS idx_mob_auth_token_user      ON mob_auth_token (user_id);
CREATE INDEX IF NOT EXISTS idx_mob_auth_token_expires   ON mob_auth_token (expires_at);
CREATE INDEX IF NOT EXISTS idx_mob_auth_token_revoked   ON mob_auth_token (revoked);
