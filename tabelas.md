-- public.system_users definição

-- Drop table

-- DROP TABLE public.system_users;

CREATE TABLE public.system_users (
	id int4 NOT NULL,
	"name" varchar(256) NULL,
	login varchar(256) NULL,
	"password" varchar(256) NULL,
	email varchar(256) NULL,
	accepted_term_policy bpchar(1) NULL,
	phone varchar(256) NULL,
	address varchar(256) NULL,
	function_name varchar(256) NULL,
	about text NULL,
	accepted_term_policy_at varchar(20) NULL,
	accepted_term_policy_data text NULL,
	frontpage_id int4 NULL,
	system_unit_id int4 NULL,
	active bpchar(1) NULL,
	custom_code varchar(256) NULL,
	otp_secret varchar(256) NULL,
	CONSTRAINT system_users_pkey PRIMARY KEY (id),
	CONSTRAINT system_users_frontpage_id_fkey FOREIGN KEY (frontpage_id) REFERENCES public.system_program(id),
	CONSTRAINT system_users_system_unit_id_fkey FOREIGN KEY (system_unit_id) REFERENCES public.system_unit(id)
);
CREATE INDEX sys_user_program_idx ON public.system_users USING btree (frontpage_id);
CREATE INDEX sys_users_name_idx ON public.system_users USING btree (name);



-- public.cad_vendedor definição

-- Drop table

-- DROP TABLE public.cad_vendedor;

CREATE TABLE public.cad_vendedor (
	vendedor_id serial4 NOT NULL,
	area_id int4 NOT NULL,
	coletor_id int4 NOT NULL,
	nome varchar(100) NOT NULL,
	cep varchar(9) NULL,
	rua varchar(100) NULL,
	numero varchar(10) NULL,
	bairro varchar(60) NULL,
	cidade varchar(60) NULL,
	uf bpchar(2) NULL,
	comissao numeric(5, 2) NOT NULL,
	pode_cancelar bpchar(1) NOT NULL,
	limite_venda numeric(15, 2) NOT NULL,
	exibe_comissao bpchar(1) NOT NULL,
	exibe_premiacao bpchar(1) NOT NULL,
	tipo_limite bpchar(1) NULL,
	treinamento bpchar(1) NOT NULL,
	usuario_id int8 NULL,
	observacao varchar(100) NULL,
	ativo bpchar(1) DEFAULT 'S'::bpchar NULL,
	pode_cancelar_tempo time DEFAULT '00:00:00'::time without time zone NULL,
	pode_cancelar_qtde int2 DEFAULT 0 NULL,
	pode_pagar bpchar(1) DEFAULT 'S'::bpchar NULL,
	pode_pagar_outro bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir_qtde int2 DEFAULT 0 NULL,
	pode_reimprimir_tempo time DEFAULT '00:00:00'::time without time zone NULL,
	pode_reimprimir_sort_naopg bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir_sort_pago bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir_outro bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir_sort_naopg_outro bpchar(1) DEFAULT 'N'::bpchar NULL,
	pode_reimprimir_sort_pago_outro bpchar(1) DEFAULT 'N'::bpchar NULL,
	reimprimir_data date NULL,
	reimprimir_qtde int2 DEFAULT 0 NULL,
	CONSTRAINT pk_vendedor PRIMARY KEY (vendedor_id),
	CONSTRAINT fk_vendedor_area FOREIGN KEY (area_id) REFERENCES public.cad_area(area_id) ON UPDATE CASCADE,
	CONSTRAINT fk_vendedor_coletor FOREIGN KEY (coletor_id) REFERENCES public.cad_coletor(coletor_id) ON UPDATE CASCADE,
	CONSTRAINT fk_vendedor_usuario FOREIGN KEY (usuario_id) REFERENCES public.system_users(id) ON UPDATE CASCADE
);