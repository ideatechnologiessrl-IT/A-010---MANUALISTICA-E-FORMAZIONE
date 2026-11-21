#!/usr/bin/env python3
# F023-porta-licenza.py — RelDoxAcademy · Formazione F023 · 21/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Porta in RelDoxAcademy la «Licenza d uso» di RelDox CRD 3.76.03G (voce 150, `reldox-crd/includes/license/`),
# come dice il capo famiglia (bacheca/reldox-crd/messaggi/2026-11-20-a-formazione-licenza-d-uso-academy.md):
# prefisso `rdacademy`, prodotto della chiave `reldox-academy`, stessa chiave pubblica, sola consultazione = D-150
# con le pagine pubbliche dei corsi accese. Mockup v2 approvato dall owner 21/11/2026 19:05 (chat Formazione F023).
# Idempotente: i file della licenza si riscrivono interi; gli innesti negli altri file hanno il segno F023_LICENZA.
#
# uso:  python3 -I banco/F023-porta-licenza.py <radice del clone di RelDox CRD> <radice del repository A-010>
import os, re, sys

CRD, REPO = sys.argv[1], sys.argv[2]
SRC = os.path.join(CRD, 'reldox-crd', 'includes', 'license')
PLUG = os.path.join(REPO, 'reldox-academy')
DST = os.path.join(PLUG, 'includes', 'license')
SEGNO = 'F023_LICENZA'

def leggi(p): return open(p, encoding='utf-8').read()
def scrivi(p, s): open(p, 'w', encoding='utf-8', newline='').write(s)

def sostituisci_una(s, vecchio, nuovo, dove):
    if s.count(vecchio) != 1:
        sys.exit('ROSSO: «%s» trovato %d volte in %s' % (vecchio[:60], s.count(vecchio), dove))
    return s.replace(vecchio, nuovo)

def e_commento(riga):
    t = riga.lstrip()
    return t.startswith(('*', '/*', '//', '#'))

# ── 1. i otto file della licenza ────────────────────────────────────────────────
os.makedirs(DST, exist_ok=True)
for nome in sorted(os.listdir(SRC)):
    if not nome.endswith('.php'): continue
    s = leggi(os.path.join(SRC, nome))
    s = s.replace('Reldox_', 'Rdacademy_').replace('RELDOX_', 'RDACADEMY_')
    for a, b in (('reldox_licenza_', 'rdacademy_licenza_'), ('reldox_license_', 'rdacademy_license_'),
                 ('reldox_impostazioni', 'rdacademy_impostazioni'), ('reldox_options', 'rdacademy_options'),
                 ("'reldox-licenza'", "'rdacademy-licenza'"), ('page=reldox-licenza', 'page=rdacademy-licenza'),
                 ("'rlic_pdf'", "'rdlic_pdf'"),("'/reldox/v1", "'/rdacademy/v1"),
                 ('public/img/logo/reldox-lockup.png', 'public/img/logo/reldox-academy-lockup.png')):
        s = s.replace(a, b)
    if nome == 'class-reldox-licenza-stato.php':
        s = sostituisci_una(s, "const PRODOTTO         = 'reldox-crd';", "const PRODOTTO         = 'reldox-academy';   // " + SEGNO + ': capo famiglia 20/11/2026 § 2', nome)
        s = re.sub(r"const ATTIVITA_ACCESE = array\([^;]*\);", "const ATTIVITA_ACCESE = array( 'rdacademy_backup_daily', 'rdacademy_sorveglianza_sicurezza' );", s, count=1)
        s = re.sub(r"const ATTIVITA_SPENTE = array\([^;]*\);", "const ATTIVITA_SPENTE = array();   // " + SEGNO + ": più ogni altra `rdacademy_*` in calendario", s, count=1, flags=re.S)
        s = re.sub(r"const AUTH_SPENTE = array\([^;]*\);", "const AUTH_SPENTE = array();   // " + SEGNO + ": le azioni personali di RelDoxAcademy (preferenze, uscita) restano accese (D-150.7)", s, count=1, flags=re.S)
        s = re.sub(r"const AMMESSE_EXTRA = array\([^;]*\);",
                   "const AMMESSE_EXTRA = array( 'get_access_log', 'get_user', 'esporta_tutto', 'backup_list', 'backup_run', 'restore_preview', 'tech_check',\n"
                   "\t\t'db_health', 'db_capacity', 'dbconfig_status', 'spedizioni_csv', 'datasource_get', 'save_user', 'licenza_striscia_chiudi' );   // " + SEGNO,
                   s, count=1, flags=re.S)
        s = sostituisci_una(s, "strpos( (string) $h, 'reldox' ) === 0", "strpos( (string) $h, 'rdacademy_' ) === 0", nome)
    if nome == 'class-reldox-license-admin.php':
        s = sostituisci_una(s, "'reldox-crd',   // S615", "'rdacademy',   // " + SEGNO + ": il menu di RelDoxAcademy; S615", nome)
    # i nomi del prodotto nei testi a video (non nei commenti, che raccontano la storia del CRD)
    righe = []
    for r in s.split('\n'):
        if not e_commento(r):
            r = r.replace('RelDox CRD', 'RelDoxAcademy')
            r = re.sub(r"RelDox(?!Academy|Food|Checks|Portal| Chiavi| CRD|-)", 'RelDoxAcademy', r)
        righe.append(r)
    s = '\n'.join(righe)
    s = s.replace('<?php\n', '<?php\n// ' + SEGNO + ' — copiato da RelDox CRD 3.76.03G (`' + nome + '`) con banco/F023-porta-licenza.py: non si cambia a mano.\n', 1)
    scrivi(os.path.join(DST, nome.replace('class-reldox-', 'class-rdacademy-')), s)

# ── 2. il file principale: carica la licenza e la pagina ──────────────────────────
p = os.path.join(PLUG, 'reldox-academy.php'); s = leggi(p)
if SEGNO not in s:
    blocco = ("require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-router.php';\n"
              "// " + SEGNO + " — 0.1.10A: la «Licenza d uso» come RelDox CRD 3.76.03G (voce 150), prodotto della chiave `reldox-academy`\n")
    for f in ('validate', 'license', 'testi', 'pdf', 'firma', 'verifica'):
        nomef = 'class-rdacademy-license.php' if f == 'license' else 'class-rdacademy-license-%s.php' % f
        blocco += "require_once RDACADEMY_PLUGIN_DIR . 'includes/license/%s';\n" % nomef
    blocco += ("require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-licenza-stato.php';\n"
               "if ( class_exists( 'Rdacademy_Licenza_Stato' ) ) { Rdacademy_Licenza_Stato::init(); }\n"
               "require_once RDACADEMY_PLUGIN_DIR . 'includes/license/class-rdacademy-license-admin.php';\n")
    s = sostituisci_una(s, "require_once RDACADEMY_PLUGIN_DIR . 'includes/class-rdacademy-router.php';\n", blocco, 'reldox-academy.php')
    s = sostituisci_una(s, "\t$router = new Rdacademy_Router();\n\t$router->init();\n",
                        "\t$router = new Rdacademy_Router();\n\t$router->init();\n"
                        "\t$license_admin = new Rdacademy_License_Admin();   // " + SEGNO + "\n\t$license_admin->init();\n", 'reldox-academy.php')
    scrivi(p, s)

# ── 3. router: sola consultazione (come il router del CRD, 4a) e la X della striscia ─
p = os.path.join(PLUG, 'includes', 'class-rdacademy-router.php'); s = leggi(p)
if SEGNO not in s:
    s = sostituisci_una(s, "            'logout'                   => 'auth',\n",
                        "            'logout'                   => 'auth',\n            'licenza_striscia_chiudi'  => 'admin',   // " + SEGNO + "\n", 'router')
    cancello = (
        "        // ── 2b. " + SEGNO + " — SOLA CONSULTAZIONE A LICENZA CESSATA (D-150.3, D-150.7, D-150.9, D-150.10), come RelDox CRD ──\n"
        "        if ( class_exists( 'Rdacademy_Licenza_Stato' ) && ( $required === 'public' || Rdacademy_Auth::get_current_user() ) && Rdacademy_Licenza_Stato::cessata() ) {\n"
        "            if ( $action === 'save_user' ) {\n"
        "                $rx_lm = Rdacademy_Licenza_Stato::save_user_a_licenza_cessata( (array) $_POST );\n"
        "                if ( $rx_lm !== '' ) wp_send_json_error( $rx_lm, 403 );\n"
        "            } elseif ( ! Rdacademy_Licenza_Stato::azione_ammessa( $action, (string) $required ) ) {\n"
        "                wp_send_json_error( Rdacademy_Licenza_Stato::MESSAGGIO . '.', 403 );\n"
        "            }\n"
        "        }\n"
        "        // ── 3. Autenticazione e capability check")
    s = sostituisci_una(s, "        // ── 3. Autenticazione e capability check", cancello, 'router')
    s = sostituisci_una(s, "        switch ( $action ) {\n",
                        "        switch ( $action ) {\n"
                        "            case 'licenza_striscia_chiudi': $rx_u = Rdacademy_Auth::get_current_user(); Rdacademy_Licenza_Stato::chiudi_striscia( (int) ( $rx_u['id'] ?? 0 ) ); wp_send_json_success( array( 'chiusa' => true ) ); break;   // " + SEGNO + "\n",
                        'router')
    scrivi(p, s)

# ── 4. posta: la porta di Casella::spedisci (come Reldox_Mailer::consegna), il recupero password esce sempre ─
p = os.path.join(PLUG, 'includes', 'security', 'class-rdacademy-casella.php'); s = leggi(p)
if SEGNO not in s:
    s = sostituisci_una(s, "\t\tself::$ultima_risposta = ''; self::$ultimo_mid = '';\n\t\t$e = self::spedisci_motore(",
                        "\t\tself::$ultima_risposta = ''; self::$ultimo_mid = '';\n"
                        "\t\tif ( class_exists( 'Rdacademy_Licenza_Stato' ) ) { $rx_no = Rdacademy_Licenza_Stato::porta_posta(); if ( $rx_no !== '' ) return array( 'success' => false, 'error' => $rx_no ); }   // " + SEGNO + " (D-150.9)\n"
                        "\t\t$e = self::spedisci_motore(", 'casella')
    scrivi(p, s)

p = os.path.join(PLUG, 'includes', 'security', 'class-rdacademy-password-mail.php'); s = leggi(p)
if SEGNO not in s:
    m = re.search(r"(public static function invia_reimpostazione\( array \$utente, string \$token_grezzo \): array \{\n)", s)
    if not m: sys.exit('ROSSO: invia_reimpostazione non trovata')
    corpo_vecchio = s[m.end():s.index('\n\t}\n', m.end())]
    s = s.replace(m.group(1) + corpo_vecchio,
                  m.group(1) + "\t\tif ( class_exists( 'Rdacademy_Licenza_Stato' ) ) {   // " + SEGNO + " — D-150.9: il recupero della password esce anche a licenza cessata\n"
                  "\t\t\treturn Rdacademy_Licenza_Stato::come_posta_di_accesso( function () use ( $utente, $token_grezzo ) { return self::invia_reimpostazione_motore( $utente, $token_grezzo ); } );\n"
                  "\t\t}\n\t\treturn self::invia_reimpostazione_motore( $utente, $token_grezzo );\n\t}\n\n"
                  "\tprivate static function invia_reimpostazione_motore( array $utente, string $token_grezzo ): array {\n" + corpo_vecchio, 1)
    contratto = '''
	/**
	 * ''' + SEGNO + ''' — Licenza d uso: trasmette il contratto firmato a Idea Technologies (come Reldox_Password_Mail::invia_contratto
	 * di RelDox CRD 3.76.03G), dalla casella di servizio, col file in allegato. Senza casella non si ripiega: il motivo torna a chi trasmette.
	 * @param array $allegati [[nome, byte, tipo], …] — i byte in memoria (Rdacademy_Casella li allega senza file su disco)
	 * @return array{success:bool,error:?string}
	 */
	public static function invia_contratto( string $a, array $v, array $allegati ): array {
		$stato = self::stato();
		if ( empty( $stato['configurata'] ) ) {
			return array( 'success' => false, 'error' => (string) ( $stato['motivo'] ?? 'La casella di servizio non è configurata.' ) );
		}
		$m  = Rdacademy_License_Testi::modello( 'licenza.trasmissione', 'it' );
		$mf = Rdacademy_License_Testi::modello( 'licenza.trasmissione_firma', 'it' );
		$oggetto = Rdacademy_License_Testi::riempi( $m['oggetto'], $v, false );
		$corpo =
			'<div style="margin:0;padding:24px 0;background:#F3F4F6;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,sans-serif">'
			. '<div style="max-width:560px;margin:0 auto;background:#FFFFFF;border:1px solid #E5E7EB;border-radius:12px;overflow:hidden">'
			. self::testata()
			. '<div style="padding:18px 28px 26px;font-size:14px;line-height:1.65;color:#111827">'
			. '<p style="margin:0 0 14px">' . Rdacademy_License_Testi::riempi( $m['testo'], $v, true ) . '</p>'
			. '<p style="margin:0;padding:12px 16px;background:#F9FAFB;border:1px solid #E5E7EB;border-radius:10px;font-size:13px">'
			. '<b>' . esc_html( $mf['oggetto'] ) . '</b><br>' . Rdacademy_License_Testi::riempi( rtrim( (string) $mf['testo'] ), $v, true ) . '</p>'
			. '</div>'
			. '<div style="padding:14px 28px 20px;border-top:1px solid #F3F4F6;font-size:13px;line-height:1.6;color:#6B7280;text-align:center">'
			. 'Messaggio automatico di ' . esc_html( defined( 'RDACADEMY_PRODUCT' ) ? RDACADEMY_PRODUCT : 'RelDoxAcademy' ) . ' (Licenza d uso).'
			. '</div></div></div>';
		$file = array();
		foreach ( $allegati as $x ) { $file[] = array( (string) ( $x[0] ?? 'contratto.pdf' ), (string) ( $x[1] ?? '' ), (string) ( $x[2] ?? 'application/pdf' ) ); }
		return Rdacademy_Casella::spedisci( $a, '', $oggetto, $corpo, $file );
	}
'''
    i = s.rindex('\n}')
    s = s[:i] + '\n' + contratto + s[i:]
    scrivi(p, s)

# ── 5. la striscia: nell applicazione (sopra il contenuto) e nelle pagine di RelDoxAcademy in wp-admin ─
p = os.path.join(PLUG, 'public', 'views', 'layout.php'); s = leggi(p)
if SEGNO not in s:
    s = sostituisci_una(s, '    <main class="rx-main" id="rx-main" role="main">\n',
        '    <main class="rx-main" id="rx-main" role="main">\n'
        "        <?php if ( class_exists( 'Rdacademy_Licenza_Stato' ) ) { $rx_lu = Rdacademy_Auth::get_current_user(); echo Rdacademy_Licenza_Stato::striscia( (bool) ( $rx_lu && ( ! empty( $rx_lu['is_backdoor'] ) || Rdacademy_Security::is_admin( (int) $rx_lu['id'] ) ) ), (int) ( $rx_lu['id'] ?? 0 ) ); } // phpcs:ignore — " + SEGNO + " ?>\n"
        "        <script>/* " + SEGNO + " — la X della striscia: chiusa per oggi */document.addEventListener('click',function(e){var x=e.target&&e.target.closest?e.target.closest('[data-rx-lic-chiudi]'):null;if(!x)return;var s=x.closest('.rx-lic');if(s)s.remove();var R=window.RDACADEMY||{},b=new URLSearchParams();b.set('action','rdacademy');b.set('rdacademy_action','licenza_striscia_chiudi');b.set('nonce',R.nonce||'');fetch(R.ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:b.toString(),credentials:'same-origin'}).catch(function(){});});</script>\n",
        'layout')
    scrivi(p, s)

p = os.path.join(PLUG, 'public', 'css', 'app.css'); s = leggi(p)
if SEGNO not in s:
    s += ('\n/* ' + SEGNO + ' — la striscia della licenza, come RelDox CRD 3.76.03G (app.css r.8208) */\n'
          '.rx-lic{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;padding:12px 20px;font-size:15.5px;font-weight:600;margin:0 0 12px;border-radius:10px}\n'
          '.rx-lic b{font-weight:600}\n.rx-lic svg{width:22px;height:22px;flex:none}\n'
          '.rx-lic-scad{background:#FDECEC;color:#B91C1C;border:1px solid #F5C2C2}\n.rx-lic-pre{background:#FDF3E1;color:#9A5B00;border:1px solid #F3DDB4}\n'
          '.rx-lic-dx{margin-left:auto;font-weight:500;font-size:14.5px}\n.rx-lic a{color:inherit}\n'
          '.rx-lic-x{border:0;background:none;color:inherit;font-size:20px;cursor:pointer;min-width:36px;min-height:36px}\n'
          '@media (max-width:640px){.rx-lic{padding:10px 14px}.rx-lic-dx{margin-left:0}}\n')
    scrivi(p, s)

print('VERDE: licenza portata in', DST)
