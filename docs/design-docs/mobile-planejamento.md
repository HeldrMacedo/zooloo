> **Nota (2026-10-05):** documento de planejamento de 2026-04. O app foi feito em **Expo** (não RN bare) e a lista de endpoints abaixo está superada por `docs/references/api-contract.md`. Mantido como histórico.

# Planejamento: App Cambista — Zooloo Mobile

## 1. Visão Geral e Decisões de Stack

### Por que React Native bare (não Expo Managed)?
- SDKs nativos de maquininha (Sunmi, PAX, Ingenico) exigem módulos nativos Android
- Impressão bluetooth precisa de permissões e APIs nativas
- Acesso ao serial do dispositivo para registro do terminal no backend
- Desenvolvimento focado em Android (99% dos dispositivos usados)

### Stack definida

| Camada | Tecnologia |
|---|---|
| Framework | React Native 0.75+ bare workflow |
| Linguagem | TypeScript |
| Navegação | React Navigation 6 (Stack + Drawer) |
| Estado global | Zustand |
| HTTP | Axios com interceptor JWT |
| Persistência local | MMKV (mais rápido que AsyncStorage) |
| Impressão BT externa | `react-native-thermal-receipt-printer-image-qr` |
| Impressora Sunmi interna | Módulo nativo customizado via SDK Sunmi |
| Formulários | React Hook Form |
| UI | StyleSheet puro (maquininha-friendly, sem dependências pesadas) |

---

## 2. Estrutura de Pastas

```
zooloo-cambista/
├── android/
│   └── app/libs/               ← SDKs: SunmiInnerPrinter.aar, etc.
├── src/
│   ├── api/
│   │   ├── client.ts           ← Axios instance + interceptores JWT
│   │   ├── auth.api.ts
│   │   ├── vendedor.api.ts
│   │   ├── sorteio.api.ts
│   │   ├── bilhete.api.ts
│   │   ├── resultado.api.ts
│   │   └── caixa.api.ts
│   ├── store/
│   │   ├── auth.store.ts       ← token, user, vendedor data
│   │   ├── bilhete.store.ts    ← rascunho do bilhete em montagem
│   │   └── config.store.ts     ← impressora, configs locais
│   ├── services/
│   │   ├── print/
│   │   │   ├── PrinterService.ts
│   │   │   ├── SunmiPrinter.ts
│   │   │   └── BluetoothPrinter.ts
│   │   └── ticket/
│   │       ├── TicketBuilder.ts
│   │       └── TicketFormatter.ts
│   ├── navigation/
│   │   ├── RootNavigator.tsx
│   │   ├── AuthNavigator.tsx
│   │   ├── MainNavigator.tsx
│   │   └── BilheteNavigator.tsx
│   ├── screens/
│   │   ├── auth/LoginScreen.tsx
│   │   ├── home/HomeScreen.tsx
│   │   ├── bilhete/
│   │   │   ├── SelecionarExtracaoScreen.tsx
│   │   │   ├── MontarBilheteScreen.tsx
│   │   │   ├── AdicionarPalpiteScreen.tsx
│   │   │   ├── ConfirmarBilheteScreen.tsx
│   │   │   └── BilheteImpressoScreen.tsx
│   │   ├── meus-bilhetes/MeusBilhetesScreen.tsx
│   │   ├── resultados/ResultadosScreen.tsx
│   │   ├── caixa/CaixaScreen.tsx
│   │   └── configuracoes/ImpressoraScreen.tsx
│   ├── components/
│   │   ├── common/
│   │   │   ├── NumericKeypad.tsx
│   │   │   ├── MoneyInput.tsx
│   │   │   └── PalpiteInput.tsx
│   │   └── bilhete/
│   │       ├── PalpiteRow.tsx
│   │       ├── SorteioSelector.tsx
│   │       └── ColocacaoSelector.tsx
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── usePrinter.ts
│   │   └── useBilhete.ts
│   ├── types/
│   │   ├── domain.types.ts
│   │   ├── api.types.ts
│   │   └── print.types.ts
│   └── utils/
│       ├── currency.ts
│       ├── palpite.ts
│       └── date.ts
```

---

## 3. Telas — Detalhamento

### LoginScreen
- Campo login + senha
- Após autenticar: salva JWT + dados do vendedor + registra terminal

### HomeScreen (Dashboard)
- Nome do vendedor + área
- Saldo do caixa do dia
- Total vendido hoje + comissão
- Botão grande "Novo Bilhete"
- Últimos resultados

### Fluxo: Novo Bilhete (Stack de 4 passos)

**Passo 1 — SelecionarExtracaoScreen**
- Lista sorteios abertos para a área do vendedor
- Destaca extrações que fecham em < 30 min
- Multi-seleção de sorteios

**Passo 2 — MontarBilheteScreen**
- Grupos de jogo agrupados por tipo (MILHAR, CENTENA, DEZENA, GRUPO, DUQUE, TERNO...)
- Botão `+` por modalidade → AdicionarPalpiteScreen
- Lista palpites adicionados + total parcial

**Passo 3 — AdicionarPalpiteScreen**
- NumericKeypad customizado com máscara por tamanho_max do jogo
- Seletor de colocações (1° ao N°)
- Campo valor + preview do prêmio (valor × cotação)
- Alerta se ultrapassar limite da área

**Passo 4 — ConfirmarBilheteScreen**
- Resumo completo do bilhete
- Nome e telefone do cliente (opcionais)
- Botão "Registrar e Imprimir"

**BilheteImpressoScreen**
- Número do bilhete + código de autorização
- Impressão automática
- Botões: "Novo Bilhete" / "Reimprimir"

### MeusBilhetesScreen
- Filtros: data, extração, situação
- Swipe to cancel (respeitando regras do vendedor)

### ResultadosScreen
- Sorteios recentes com os 10 números sorteados

### CaixaScreen
- Saldo, total vendido, comissão, prêmios pagos

### ImpressoraScreen
- Auto-detecta Sunmi → usa interna
- Senão → scan Bluetooth + pareamento
- Botão de teste de impressão

---

## 4. Contrato de API — Endpoints REST no Zooloo (PHP)

Todos com `Authorization: Bearer {token}`.

```
POST   /rest.php?class=ApplicationAuthenticationRestService&method=login
POST   /rest.php?class=ApplicationAuthenticationRestService&method=refreshToken

GET    /rest.php?class=VendedorRestService&method=me
GET    /rest.php?class=TerminalRestService&method=registrar   (POST body: serial, tipo)
GET    /rest.php?class=SorteioRestService&method=abertos
GET    /rest.php?class=ModalidadeRestService&method=disponiveis&sorteio_id=X
POST   /rest.php?class=BilheteRestService&method=registrar
DELETE /rest.php?class=BilheteRestService&method=cancelar&bilhete_id=X
GET    /rest.php?class=BilheteRestService&method=detalhe&bilhete_id=X
GET    /rest.php?class=BilheteRestService&method=lista
GET    /rest.php?class=ResultadoRestService&method=recentes
GET    /rest.php?class=CaixaRestService&method=resumo
```

---

## 5. Estrutura do Bilhete no Banco

```
mov_jb                          ← cabeçalho (1 por bilhete)
  sorteios_ids: "123,124"       ← sorteios cobertos
  └── mov_jb_sorteio            ← 1 por (sorteio × modalidade)
        palpites: "1234,5678"   ← palpites desta modalidade
        └── mov_jb_sort_palpite ← 1 por palpite individual
```

Payload da API para registrar bilhete:
```json
{
  "terminal_id": 1,
  "nome_cliente": "João",
  "fone_cliente": "11999999999",
  "jogos": [
    {
      "sorteio_id": 123,
      "modalidade_id": 2,
      "palpites": ["1234", "5678"],
      "colocacao_inicial": 1,
      "colocacao_final": 5,
      "valor_palpite": 2.00
    }
  ]
}
```

---

## 6. Impressão — Layout ESC/POS

```
================================
       NOME DA BANCA
================================
Bilhete: 000123
Autorização: ABC123XYZ
Data: 28/04/2026 14:35
Vendedor: JOÃO SILVA
================================
EXTRAÇÃO: FEDERAL 15H
Sorteio: #4521 – 28/04/2026

[MILHAR – 1° ao 5°]
  1234   R$ 2,00  Prêmio: R$ 5.200,00

[DEZENA – 1°]
  25     R$ 1,00  Prêmio: R$ 60,00
================================
TOTAL: R$ 3,00
================================
[QR Code com string_autorizacao]
================================
   Boa sorte!
================================
```

---

## 7. Suporte a Maquininhas

- **Sunmi V2 / P2 / T2:** Android 7.1+, impressora interna 58mm via `SunmiInnerPrinter.aar`
- UI com fonte mínima 16sp, botões mínimo 48dp, teclado numérico customizado
- Flavor Android: `maquininha` (Sunmi SDK) / `mobile` (smartphones)

---

## 8. Segurança

- Token JWT no Keystore Android via MMKV encriptado
- Refresh automático no interceptor Axios
- Logout automático em erro 401
- `cad_terminal.serial` vinculado ao dispositivo — backend valida terminal

---

## 9. Ordem de Implementação

1. Endpoints REST PHP no zooloo (semana 1)
2. Auth + navegação base do app (semana 1)
3. Fluxo completo novo bilhete sem impressão (semana 2)
4. Listagem bilhetes + resultados (semana 2)
5. Impressão Bluetooth (semana 3)
6. Suporte Sunmi / maquininha (semana 3)
7. Caixa + dashboard (semana 4)
8. Testes em dispositivo real (semana 4)
