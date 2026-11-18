<?php
/**
 * Vista wizard bootstrap FASE 0 (needs_db) — Step 1 (BASE) + Step 2 (writer).
 * Contratto grafico: MOCKUP-wizard-fase0-v3.html (RP-MOCKUP-CONTRACT).
 * Auto-contenuta (CSS/JS inline): gli asset del plugin non sono caricati in needs_db.
 *
 * Variabili in scope (da Rdacademy_Bootstrap_Wizard::render): $nonce, $ajax_url.
 * Copiata da view-bootstrap-wizard.php di RelDox CRD 3.55.09G. Cambiano: marchio e
 * nome di prodotto, testi del CRM e segnaposti dell hosting (Sql…_2), alert() → avviso in pagina.
 *
 * @var string $nonce
 * @var string $ajax_url
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style>
  #rdacademy-boot{
    --ink:#111827; --mut:#6B7280; --line:#E5E7EB; --bg:#F3F4F6;
    --indigo:#4338CA; --indigo-l:#EEF2FF; --indigo-b:#C7D2FE;
    --green:#16A34A; --green-l:#ECFDF5; --green-b:#BBF7D0;
    --amber:#92400E; --amber-l:#FFFBEB; --amber-b:#FDE68A;
    --red:#B91C1C; --red-l:#FEF2F2; --red-b:#FECACA;
    --slate:#334155;
    max-width:880px;margin:16px auto 40px;
    font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--ink);
  }
  #rdacademy-boot *{box-sizing:border-box}
  #rdacademy-boot .panel{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:0 1px 2px rgba(0,0,0,.04)}
  #rdacademy-boot .topbar{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--line);background:#fff}
  #rdacademy-boot .brand{display:flex;align-items:center;gap:9px;font-weight:800;letter-spacing:.2px}
  #rdacademy-boot .brand .logo{width:26px;height:26px;border-radius:7px;background:var(--indigo);display:inline-flex;align-items:center;justify-content:center}
  /* 3.44.03 (S514, BUG-179.C) — il marchio di prodotto, lo stesso della testata dell app. */
  #rdacademy-boot .brand-mark{display:block;height:26px;width:auto}
  #rdacademy-boot .brand-nome{font-size:15px;font-weight:700;color:#0C6E86}
  #rdacademy-boot .brand-sub{font-size:12px;font-weight:600;color:#9CA3AF;letter-spacing:.2px;padding-left:10px;border-left:1px solid var(--line)}
  /* 3.44.03 (S514, BUG-179.B) — il grigio dei suggerimenti e dichiarato, non lasciato al browser. */
  #rdacademy-boot input::placeholder{color:#9CA3AF;opacity:1}
  #rdacademy-boot .fld label .hint{font-weight:500;color:#9CA3AF;font-size:11px;margin-left:4px;text-transform:none;letter-spacing:0}
  #rdacademy-boot .statebadge{display:inline-flex;align-items:center;gap:7px;padding:4px 10px;border-radius:999px;font-size:11.5px;font-weight:800;letter-spacing:.3px;text-transform:uppercase;background:var(--amber-l);color:var(--amber);border:1px solid var(--amber-b)}
  #rdacademy-boot .statebadge .dot{width:7px;height:7px;border-radius:50%;background:currentColor}
  #rdacademy-boot .statebadge.ready{background:var(--green-l);color:var(--green);border-color:var(--green-b)}
  #rdacademy-boot .actions{margin-left:auto;display:flex;gap:8px}
  #rdacademy-boot .btn{height:38px;padding:0 16px;border-radius:9px;border:1px solid var(--line);background:#fff;color:var(--ink);font:inherit;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px}
  #rdacademy-boot .btn:disabled{opacity:.45;cursor:not-allowed}
  #rdacademy-boot .btn.primary{background:var(--indigo);border-color:var(--indigo);color:#fff}
  #rdacademy-boot .btn.ghost{background:#fff}
  #rdacademy-boot .btn.danger{background:#DC2626;border-color:#DC2626;color:#fff}
  #rdacademy-boot .rx-dbuse{margin-top:14px;padding:14px 15px;border:1px solid #FCD34D;background:#FFFBEB;border-radius:10px}
  #rdacademy-boot .rx-dbuse-head{display:flex;align-items:center;gap:10px;font-size:14px;color:#92400E}
  #rdacademy-boot .rx-dbuse-head span{font-size:12px;font-weight:700;color:#B45309}
  #rdacademy-boot .rx-dbuse-sub{margin:8px 0 10px;font-size:12.5px;line-height:1.55;color:#78350F}
  #rdacademy-boot .rx-dbuse-det{font-size:12px;margin-bottom:12px}
  #rdacademy-boot .rx-dbuse-det summary{cursor:pointer;color:#92400E;font-weight:700}
  #rdacademy-boot .rx-dbuse-list{margin-top:8px;max-height:180px;overflow:auto;background:#fff;border:1px solid #FDE68A;border-radius:8px;padding:8px 10px;font:12px/1.6 ui-monospace,Menlo,Consolas,monospace;color:#334155}
  #rdacademy-boot .steps{display:flex;gap:6px;padding:12px 18px;border-bottom:1px solid var(--line);background:#FAFAFB;overflow-x:auto}
  #rdacademy-boot .step{display:flex;align-items:center;gap:8px;padding:5px 11px;border-radius:999px;font-size:12.5px;font-weight:700;color:var(--mut);background:#fff;border:1px solid var(--line);white-space:nowrap;flex:0 0 auto}
  #rdacademy-boot .step .n{width:19px;height:19px;border-radius:50%;background:var(--line);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:11px}
  #rdacademy-boot .step.on{color:var(--indigo);border-color:var(--indigo-b);background:var(--indigo-l)}
  #rdacademy-boot .step.on .n{background:var(--indigo)}
  #rdacademy-boot .step.done{color:var(--green);border-color:var(--green-b);background:var(--green-l)}
  #rdacademy-boot .step.done .n{background:var(--green)}
  #rdacademy-boot .body{padding:20px 18px}
  #rdacademy-boot .pane{display:none}
  #rdacademy-boot .pane.on{display:block}
  #rdacademy-boot h2.t{margin:0 0 4px;font-size:17px}
  #rdacademy-boot p.sub{margin:0 0 16px;color:var(--mut);font-size:13px}
  #rdacademy-boot code{background:#F1F5F9;border-radius:4px;padding:1px 5px;font-size:.92em}
  #rdacademy-boot .note{display:flex;gap:10px;align-items:flex-start;padding:11px 13px;border-radius:9px;background:var(--indigo-l);color:#3730A3;font-size:12.5px;line-height:1.5;border:1px solid var(--indigo-b);margin-bottom:16px}
  #rdacademy-boot .note.amber{background:var(--amber-l);color:var(--amber);border-color:var(--amber-b)}
  #rdacademy-boot .note.green{background:var(--green-l);color:#166534;border-color:var(--green-b)}
  #rdacademy-boot .note.red{background:var(--red-l);color:var(--red);border-color:var(--red-b)}
  #rdacademy-boot .note code{background:rgba(0,0,0,.05)}
  #rdacademy-boot .note svg{flex:0 0 auto;margin-top:1px}
  #rdacademy-boot .grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px}
  #rdacademy-boot .fld{display:flex;flex-direction:column;gap:5px}
  #rdacademy-boot .fld.full{grid-column:1 / -1}
  #rdacademy-boot .fld.wide{grid-column:span 2}
  #rdacademy-boot .fld label{font-size:12px;font-weight:700;color:var(--mut)}
  #rdacademy-boot .fld input{height:38px;padding:0 11px;border:1px solid var(--line);border-radius:9px;font:inherit;font-size:13px;background:#fff;color:var(--ink)}
  #rdacademy-boot .fld input:focus{outline:none;border-color:var(--indigo-b);box-shadow:0 0 0 3px var(--indigo-l)}
  #rdacademy-boot .section-h{font-size:12px;font-weight:800;color:var(--mut);text-transform:uppercase;letter-spacing:.4px;margin:16px 0 10px;display:flex;align-items:center;gap:8px}
  #rdacademy-boot .section-h:first-of-type{margin-top:6px}
  #rdacademy-boot .section-h .hint{font-weight:600;text-transform:none;letter-spacing:0;color:#9CA3AF;font-size:11px}
  #rdacademy-boot .baserow{display:grid;grid-template-columns:1.1fr 1.15fr auto auto auto;gap:10px;align-items:center;padding:11px 12px;border:1px solid var(--indigo-b);border-radius:11px;background:var(--indigo-l);margin-bottom:8px}
  #rdacademy-boot .baserow .role{display:flex;flex-direction:column;gap:2px}
  #rdacademy-boot .baserow .role b{font-size:13px}
  #rdacademy-boot .baserow .role small{font-size:11px;color:var(--mut)}
  #rdacademy-boot .baserow input{height:34px;padding:0 10px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:12.5px;background:#fff}
  #rdacademy-boot .tag{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;padding:3px 8px;border-radius:999px;white-space:nowrap;justify-self:start}
  #rdacademy-boot .tag.base{background:var(--indigo);color:#fff}
  #rdacademy-boot .testbtn{height:34px;padding:0 12px;border-radius:8px;border:1px solid var(--line);background:#fff;font:inherit;font-size:12px;font-weight:700;cursor:pointer;color:var(--ink)}
  #rdacademy-boot .pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-size:11.5px;font-weight:800;min-width:84px;justify-content:center;border:1px solid var(--line);color:var(--mut);background:#fff}
  #rdacademy-boot .pill .dot{width:8px;height:8px;border-radius:50%;background:var(--mut)}
  #rdacademy-boot .pill.ok{background:var(--green-l);color:var(--green);border-color:var(--green-b)}
  #rdacademy-boot .pill.ok .dot{background:var(--green)}
  #rdacademy-boot .pill.err{background:var(--red-l);color:var(--red);border-color:var(--red-b)}
  #rdacademy-boot .pill.err .dot{background:var(--red)}
  #rdacademy-boot .ladder{display:flex;flex-direction:column;gap:10px;margin:6px 0 16px}
  #rdacademy-boot .grad{display:flex;gap:12px;align-items:flex-start;padding:13px 14px;border:1px solid var(--line);border-radius:12px;background:#fff}
  #rdacademy-boot .grad .gn{width:28px;height:28px;border-radius:8px;background:var(--bg);color:var(--mut);flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:13px}
  #rdacademy-boot .grad .gt{flex:1}
  #rdacademy-boot .grad .gt b{font-size:13.5px}
  #rdacademy-boot .grad .gt p{margin:3px 0 0;font-size:12px;color:var(--mut);line-height:1.5}
  #rdacademy-boot .grad.sel{border-color:var(--green-b);background:var(--green-l)}
  #rdacademy-boot .grad.sel .gn{background:var(--green);color:#fff}
  #rdacademy-boot .grad.wait{opacity:.72}
  #rdacademy-boot .grad.wait .gn{background:var(--indigo-l);color:var(--indigo)}
  #rdacademy-boot .badge-auto{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:.3px;background:var(--green);color:#fff}
  #rdacademy-boot .grad .sub-form{margin-top:10px;padding-top:10px;border-top:1px dashed var(--line);display:none}
  #rdacademy-boot .grad.open .sub-form{display:block}
  #rdacademy-boot pre.code{margin:0;padding:13px 14px;background:#0F172A;color:#E2E8F0;border-radius:11px;font-size:11.5px;line-height:1.65;overflow-x:auto;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;white-space:pre-wrap;word-break:break-all}
  #rdacademy-boot pre.code .k{color:#93C5FD}
  #rdacademy-boot pre.code .s{color:#86EFAC}
  #rdacademy-boot pre.code .c{color:#64748B}
  #rdacademy-boot .done-box{text-align:center;padding:14px 0 4px}
  #rdacademy-boot .done-box .circ{width:64px;height:64px;border-radius:50%;background:var(--green-l);border:1px solid var(--green-b);display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px}
  #rdacademy-boot .done-box h3{margin:0 0 6px;font-size:18px}
  #rdacademy-boot .done-box p{margin:0 auto;max-width:520px;color:var(--mut);font-size:13px}
  /* 0.6.03A — il primo ingresso passa da Primo accesso: detto in rosso, non lasciato da indovinare. */
  #rdacademy-boot .primo{margin:18px auto 0;max-width:560px;text-align:left;background:#B91C1C;color:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 4px 14px rgba(185,28,28,.25)}
  #rdacademy-boot .primo .k{display:block;font-size:11.5px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;opacity:.9;margin-bottom:4px}
  #rdacademy-boot .primo h4{margin:0 0 8px;font-size:17px;line-height:1.3;color:#fff}
  #rdacademy-boot .primo p{margin:0 0 8px;max-width:none;color:#fff;font-size:14px;line-height:1.5}
  #rdacademy-boot .primo ol{margin:4px 0 14px;padding-left:20px;font-size:14px;line-height:1.6}
  #rdacademy-boot .primo b{color:#fff}
  #rdacademy-boot .primo .btn.vai{background:#fff;border-color:#fff;color:#B91C1C;height:44px;font-size:15px;text-decoration:none}
  #rdacademy-boot .primo .btn.vai:focus-visible{outline:3px solid #FDE68A;outline-offset:2px}
  #rdacademy-boot .done-box p.done-dopo{margin:18px auto 0}
  #rdacademy-boot .done-dopo .btn{font-weight:600;color:var(--mut)}
  #rdacademy-boot .roadmap{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:18px;padding:12px 14px;border:1px dashed var(--line);border-radius:11px;background:#FAFAFB;font-size:11.5px;color:var(--mut)}
  #rdacademy-boot .roadmap .s{padding:3px 9px;border-radius:999px;background:#fff;border:1px solid var(--line);font-weight:700}
  #rdacademy-boot .roadmap .s.now{background:var(--amber-l);color:var(--amber);border-color:var(--amber-b)}
  #rdacademy-boot .roadmap .s.out{opacity:.55;font-style:italic}
  #rdacademy-boot .roadmap .arr{color:#9CA3AF}
  #rdacademy-boot .roadmap .cap{width:100%;margin-bottom:4px;font-weight:800;letter-spacing:.3px;text-transform:uppercase;color:#9CA3AF}
  @media (max-width:680px){
    #rdacademy-boot .grid{grid-template-columns:1fr}
    #rdacademy-boot .fld.wide{grid-column:auto}
    #rdacademy-boot .baserow{grid-template-columns:1fr;gap:8px}
    #rdacademy-boot .baserow .tag,#rdacademy-boot .baserow .pill{justify-self:start}
    #rdacademy-boot .topbar{flex-wrap:wrap}
    #rdacademy-boot .actions{width:100%;margin-left:0}
    #rdacademy-boot .actions .btn{flex:1;justify-content:center}
    #rdacademy-boot .statebadge{order:3}
  }
</style>

<div id="rdacademy-boot"
     data-ajax="<?php echo esc_attr( $ajax_url ); ?>"
     data-nonce="<?php echo esc_attr( $nonce ); ?>"
     data-after="<?php echo esc_url( $after_url ); ?>">
  <div class="panel">

    <div class="topbar">
      <?php /* Copiato da RelDox 3.44.03 (S514, BUG-179.C) — il marchio di prodotto, lo stesso della topbar
           di layout.php (Sole del kit + nome). RDACADEMY_PLUGIN_URL e definita prima del gate needs_db. */ ?>
      <div class="brand"><img class="brand-mark" src="<?php echo esc_url( RDACADEMY_PLUGIN_URL . 'public/img/logo/reldox-symbol-color.svg' ); ?>?v=<?php echo esc_attr( RDACADEMY_VERSION ); ?>" alt="" width="26" height="26" decoding="async"><span class="brand-nome"><?php echo esc_html( RDACADEMY_PRODUCT ); ?></span><span class="brand-sub">Installazione</span></div>
      <span id="state" class="statebadge"><span class="dot"></span>needs_db</span>
      <div class="actions">
        <button id="back" class="btn ghost" type="button" disabled>Indietro</button>
        <button id="next" class="btn primary" type="button">Prova la connessione BASE</button>
      </div>
    </div>

    <div class="steps" id="steps">
      <div class="step on" data-s="0"><span class="n">1</span>Database</div>
      <div class="step" data-s="1"><span class="n">2</span>Scrittura wp-config</div>
    </div>

    <div class="body">

      <!-- STEP 1 · DATABASE (BASE) -->
      <section class="pane on" data-pane="0">
        <h2 class="t">Configurazione database</h2>
        <p class="sub">Per partire serve <b>solo la BASE</b>: il database dedicato del prodotto, che contiene utenti, ruoli, impostazioni e registri. Le sorgenti aggiuntive si attivano piu tardi da <code>Impostazioni → Database</code>.</p>

        <div class="note amber">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 8v5m0 3h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <div><b>Configurazione database mancante.</b> È l'unica pagina raggiungibile finché la BASE non è in <code>wp-config</code>.</div>
        </div>

        <!-- 3.15.62 · decisione owner S32 (RelDox): i database sono predisposti dall'utente, il prodotto non li crea ne li elimina. -->
        <div class="note">
          <div><b>Prerequisito.</b> <?php echo esc_html( RDACADEMY_PRODUCT ); ?> richiede database <b>gia creati e vuoti</b>, dedicati esclusivamente
          all'applicazione. La predisposizione dei database &mdash; creazione, svuotamento, assegnazione dell'utente
          &mdash; si esegue dal <b>pannello di controllo dell'hosting</b> ed &egrave; <b>a cura dell'utente</b>.
          <?php echo esc_html( RDACADEMY_PRODUCT ); ?> non crea n&eacute; elimina database: si limita a inizializzare quelli che gli indichi.</div>
        </div>

        <?php /* `autocomplete` su utente e password del database e dell FTP: senza, il browser li memorizza
           come credenziali DEL SITO e li propone nel login di RelDoxAcademy. */ ?>
        <div class="section-h">Login MySQL <span class="hint">stesso host/utente/password per tutti i DB · cambia solo il nome</span></div>
        <div class="grid">
          <?php /* 3.44.03 (S514, BUG-179.B) — era «sql.xxxxxxx.hosting.aruba.it», un NOME: l hosting da un IP. */ ?>
          <div class="fld"><label>Host MySQL <span class="hint">indirizzo IP del server MySQL</span></label><input id="f-host" value="" placeholder="0.0.0.0" spellcheck="false"></div>
          <div class="fld"><label>Porta</label><input id="f-port" value="3306" spellcheck="false"></div>
          <div class="fld"><label>Charset</label><input id="f-charset" value="utf8mb4" spellcheck="false"></div>
          <div class="fld wide"><label>Utente</label><input id="f-user" value="" placeholder="utente MySQL" spellcheck="false" autocomplete="off"></div>
          <div class="fld"><label>Password</label><input id="f-pass" type="password" value="" autocomplete="new-password"></div>
        </div>

        <div class="section-h">BASE — obbligatoria <span class="hint">→ scritta in wp-config.php · connessione singola all'avvio</span></div>
        <div class="baserow">
          <div class="role"><b>BASE</b><small>verità · <code>rdacademy_*</code></small></div>
          <input id="f-name" value="" placeholder="nome del database BASE" spellcheck="false">
          <span class="tag base">→ wp-config</span>
          <button class="testbtn" type="button" id="btn-testbase">Prova</button>
          <span class="pill" id="basePill"><span class="dot"></span>—</span>
        </div>
        <div id="baseMsg" class="note red" style="display:none"></div>

        <!-- 3.15.62 (S32) · ex selettore «DB già usato?» (§3.5, 3.15.53) RIBALTATO IN AVVISO.
             Decisione owner S32: i DB si predispongono dal pannello dell'hosting; il prodotto verifica e avvisa,
             non offre piu la pulizia. Rimossi i pulsanti e l'endpoint rdacademy_boot_wipe_base. -->
        <div id="rx-dbuse" class="rx-dbuse" style="display:none">
          <div class="rx-dbuse-head">
            <strong>Database non vuoto</strong>
            <span id="rx-dbuse-count">&mdash;</span>
          </div>
          <p class="rx-dbuse-sub">Questo database non &egrave; vuoto &mdash; contiene <b id="rx-dbuse-n">N</b> tabelle.
            <?php echo esc_html( RDACADEMY_PRODUCT ); ?> richiede database vergini. Predisponi il database dal pannello di controllo dell'hosting,
            poi riprova la connessione.</p>
          <details class="rx-dbuse-det"><summary>Mostra tabelle presenti</summary>
            <div id="rx-dbuse-list" class="rx-dbuse-list"></div>
          </details>
        </div>
      </section>

      <!-- STEP 2 · SCRITTURA wp-config -->
      <section class="pane" data-pane="1">
        <h2 class="t">Scrittura di <code>wp-config.php</code> — connessione BASE</h2>
        <p class="sub">Solo la <b>BASE</b> va in <code>wp-config</code>: è la connessione singola che il plugin apre all'avvio. L'iniezione è <b>idempotente</b> — se le costanti esistono già, il wizard non tocca il file.</p>

        <div id="wizMsg" class="note red" role="alert" style="display:none;justify-content:space-between">
          <span class="t"></span>
          <button type="button" class="btn ghost" style="height:28px;padding:0 10px" onclick="this.parentNode.style.display='none'">Chiudi</button>
        </div>
        <div class="ladder">
          <div class="grad sel" id="g1">
            <div class="gn">1</div>
            <div class="gt">
              <b>Scrittura diretta</b> &nbsp;<span class="badge-auto">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>consigliato</span>
              <p>PHP con permesso di scrittura sul file (caso Aruba per-utente). <code>is_writable = sì</code> → nessuna credenziale aggiuntiva.</p>
            </div>
          </div>
          <div class="grad wait" id="g2">
            <div class="gn">2</div>
            <div class="gt">
              <b>FTP / FTPS</b> <span style="font-size:11px;color:var(--mut);font-weight:700">— fallback</span>
              <p>Se la scrittura diretta non è possibile, indica le credenziali FTP: <code>find_folder(WP_CONTENT_DIR)</code> individua <code>wp-content</code>; <b>wp-config = cartella padre</b>.</p>
              <div class="sub-form">
                <div class="grid" style="margin-bottom:0">
                  <div class="fld"><label>Host FTP</label><input id="f-fhost" placeholder="ftp.dominio.it"></div>
                  <div class="fld"><label>Porta</label><input id="f-fport" placeholder="21" value="21"></div>
                  <div class="fld"><label>SSL (FTPS)</label>
                    <select id="f-fssl" style="height:38px;border:1px solid var(--line);border-radius:9px;padding:0 10px;font:inherit"><option value="0">No</option><option value="1">Sì</option></select>
                  </div>
                  <div class="fld"><label>Utente FTP</label><input id="f-fuser" placeholder="" autocomplete="off"></div>
                  <div class="fld wide"><label>Password FTP</label><input id="f-fpass" type="password" placeholder="" autocomplete="new-password"></div>
                </div>
                <button class="btn primary" type="button" id="btn-writeftp" style="margin-top:10px">Scrivi via FTP</button>
              </div>
            </div>
          </div>
          <div class="grad wait" id="g3">
            <div class="gn">3</div>
            <div class="gt">
              <b>Manuale</b> <span style="font-size:11px;color:var(--mut);font-weight:700">— fallback finale</span>
              <p>Incolla il blocco qui sotto in <code>wp-config.php</code> (sopra <code>/* That's all, stop editing! */</code>), salva e ricarica.</p>
            </div>
          </div>
        </div>

        <div class="section-h">Blocco che verrà scritto <span class="hint">solo BASE</span></div>
        <pre class="code" id="block">—</pre>
      </section>

      <!-- SUCCESS -->
      <section class="pane" data-pane="2">
        <div class="done-box">
          <div class="circ">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#16A34A" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <h3>BASE scritta in wp-config</h3>
          <p id="doneMsg">Credenziali BASE scritte in wp-config.php.</p>
          <div class="primo" role="alert" id="rdacademy-primo-accesso">
            <span class="k">Ultimo passo obbligatorio</span>
            <h4>Imposta la password dell’amministratore</h4>
            <p>Nessuno conosce ancora la password per entrare in <?php echo esc_html( RDACADEMY_PRODUCT ); ?>: si sceglie adesso, una volta sola.</p>
            <ol>
              <li>Apri <b>Primo accesso</b> con il pulsante qui sotto.</li>
              <li>Genera il collegamento monouso per <b><?php echo esc_html( RDACADEMY_SERVICE_EMAIL ); ?></b>.</li>
              <li>Aprilo, scegli la password ed entra.</li>
            </ol>
            <a class="btn vai" id="btn-primo-accesso" href="<?php echo esc_url( admin_url( 'admin.php?page=rdacademy-primo-accesso' ) ); ?>">Vai a Primo accesso →</a>
          </div>
          <p class="done-dopo"><button class="btn ghost" type="button" id="btn-done-go">Avvia <?php echo esc_html( RDACADEMY_PRODUCT ); ?> senza impostare la password</button></p>
        </div>
      </section>

      <div class="roadmap">
        <span class="cap">Catena di stato dell'installazione</span>
        <span class="s now" id="rm0">needs_db</span><span class="arr">→</span>
        <span class="s out">moduli &amp; licenza (a parte)</span><span class="arr">→</span>
        <span class="s" id="rm4">ready</span>
      </div>

    </div>
  </div>
</div>

<script>
(function(){
  var root  = document.getElementById('rdacademy-boot');
  var AJAX  = root.getAttribute('data-ajax');
  var NONCE = root.getAttribute('data-nonce');
  var AFTER = root.getAttribute('data-after') || '';
  var cur = 0, baseOk = false;
  var PRODOTTO = <?php echo wp_json_encode( RDACADEMY_PRODUCT ); ?>;
  /* NUOVO — i messaggi restano NELLA PAGINA (REGOLE § 4): in RelDox questi tre
     esiti uscivano con alert(). L avviso resta finche non lo si chiude. */
  function avviso(txt){
    var m=document.getElementById('wizMsg'); if(!m) return;
    m.querySelector('.t').textContent = txt; m.style.display='flex';
  }

  function $(id){ return document.getElementById(id); }
  function val(id){ var e=$(id); return e ? e.value.trim() : ''; }

  function post(action, data, cb){
    var body = 'action='+encodeURIComponent(action)+'&nonce='+encodeURIComponent(NONCE);
    Object.keys(data).forEach(function(k){ body += '&'+encodeURIComponent(k)+'='+encodeURIComponent(data[k]); });
    fetch(AJAX, {method:'POST', credentials:'same-origin',
      headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:body})
      .then(function(r){ return r.json(); })
      .then(function(j){ cb(j); })
      .catch(function(){ cb({success:false, data:'Errore di rete.'}); });
  }

  function baseFields(){
    return { host:val('f-host'), port:val('f-port'), name:val('f-name'),
             user:val('f-user'), pass:($('f-pass')?$('f-pass').value:''), charset:val('f-charset') };
  }

  function setPill(kind, txt){
    var el=$('basePill'); el.className='pill'+(kind?(' '+kind):'');
    el.innerHTML='<span class="dot"></span>'+(txt||(kind==='ok'?'Connesso':kind==='err'?'Errore':'—'));
  }
  function showBaseMsg(txt){ var m=$('baseMsg'); if(!txt){m.style.display='none';return;} m.style.display='block'; m.textContent=txt; }

  function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  function renderBlock(){
    var f=baseFields();
    var host = f.host + (f.port && f.host.indexOf(':')<0 ? ':'+f.port : '');
    var pmask = f.pass ? '••••••••' : '';
    $('block').innerHTML =
      '<span class="c">/* '+esc(PRODOTTO)+' — credenziali database BASE (FASE 0 install) */</span>\n'+
      "<span class=\"k\">define</span>( <span class=\"s\">'RDACADEMY_DB_HOST'</span>,    <span class=\"s\">'"+esc(host)+"'</span> );\n"+
      "<span class=\"k\">define</span>( <span class=\"s\">'RDACADEMY_DB_NAME'</span>,    <span class=\"s\">'"+esc(f.name)+"'</span> ); <span class=\"c\">// BASE</span>\n"+
      "<span class=\"k\">define</span>( <span class=\"s\">'RDACADEMY_DB_USER'</span>,    <span class=\"s\">'"+esc(f.user)+"'</span> );\n"+
      "<span class=\"k\">define</span>( <span class=\"s\">'RDACADEMY_DB_PASS'</span>,    <span class=\"s\">'"+esc(pmask)+"'</span> );\n"+
      "<span class=\"k\">define</span>( <span class=\"s\">'RDACADEMY_DB_CHARSET'</span>, <span class=\"s\">'"+esc(f.charset||'utf8mb4')+"'</span> );";
  }

  function go(step){
    cur=step;
    document.querySelectorAll('#rdacademy-boot .pane').forEach(function(p){p.classList.remove('on');});
    document.querySelector('#rdacademy-boot .pane[data-pane="'+step+'"]').classList.add('on');
    document.querySelectorAll('#rdacademy-boot .step').forEach(function(s){
      var n=+s.dataset.s; s.classList.toggle('on',n===step); s.classList.toggle('done',n<step);
    });
    if(step===1) renderBlock();
    refresh();
  }

  function setState(txt,cls){
    var s=$('state'); s.innerHTML='<span class="dot"></span>'+txt;
    s.className='statebadge'+(cls==='ready'?' ready':'');
  }

  function refresh(){
    var back=$('back'), next=$('next');
    back.disabled=(cur!==1);
    if(cur===0){
      next.style.display='';
      next.textContent = baseOk ? 'Avanti · scrittura wp-config' : 'Prova la connessione BASE';
      next.disabled=false; setState('needs_db','');
    } else if(cur===1){
      next.style.display='';
      next.textContent='Scrivi BASE (diretta)'; next.disabled=false; setState('writing','');
    } else {
      next.style.display='none'; back.disabled=true; setState('ready','ready');
    }
    $('rm4').classList.toggle('now', cur===2);
  }

  // ---- Step 1: test BASE ----
  function testBase(){
    var f=baseFields();
    if(!f.host||!f.name||!f.user){ showBaseMsg('Host, nome database e utente sono obbligatori.'); setPill('err','Dati mancanti'); return; }
    setPill('', 'Verifica…'); showBaseMsg('');
    $('btn-testbase').disabled=true;
    post('rdacademy_boot_testbase', f, function(j){
      $('btn-testbase').disabled=false;
      if(j && j.success){ baseOk=true; setPill('ok','Connesso'); showBaseMsg(''); probeTables(); refresh(); }
      else { baseOk=false; setPill('err','Errore'); hideDbUse(); showBaseMsg((j&&j.data)?(''+ (j.data.message||j.data)):'Connessione fallita.'); refresh(); }
    });
  }

  // ---- 3.15.62 (S32): verifica DB vergine — ex selettore §3.5, ora solo avviso ----
  function hideDbUse(){ var p=$('rx-dbuse'); if(p) p.style.display='none'; }
  function probeTables(){
    hideDbUse();
    post('rdacademy_boot_probe_tables', baseFields(), function(j){
      if(!j || !j.success || !j.data) return;              // errore/core: silenzioso qui, il test e' gia' ok
      if(j.data.empty || !j.data.count) return;            // DB vuoto -> nessun avviso
      var p=$('rx-dbuse'); if(!p) return;
      $('rx-dbuse-count').textContent = 'Contiene '+j.data.count+' tabelle';
      var n=$('rx-dbuse-n'); if(n) n.textContent = j.data.count;
      var list=$('rx-dbuse-list'); list.textContent='';
      (j.data.tables||[]).forEach(function(t){ var d=document.createElement('div'); d.textContent=t; list.appendChild(d); });
      p.style.display='block';
    });
  }

  // ---- Step 2: writers ----
  function toDone(msg){ if(msg) $('doneMsg').textContent=msg; go(2); }
  function writeDirect(){
    var f=baseFields();
    $('next').disabled=true; $('next').textContent='Scrittura…';
    post('rdacademy_boot_write_direct', f, function(j){
      $('next').disabled=false; $('next').textContent='Scrivi BASE (diretta)';
      if(j && j.success){ toDone(j.data && j.data.message); }
      else {
        // Diretta non disponibile → apri il gradino FTP.
        $('g1').classList.remove('sel'); $('g2').classList.add('sel','open'); $('g2').classList.remove('wait');
        var d=(j&&j.data)?(j.data.message||j.data):'Scrittura diretta non riuscita.';
        avviso(d+' — Usa il metodo FTP (qui sotto) o incolla il blocco manualmente.');
      }
    });
  }
  function writeFtp(){
    var f=baseFields();
    f.fhost=val('f-fhost'); f.fport=val('f-fport'); f.fuser=val('f-fuser');
    f.fpass=($('f-fpass')?$('f-fpass').value:''); f.fssl=val('f-fssl');
    if(!f.fhost||!f.fuser||!f.fpass){ avviso('Host, utente e password FTP sono obbligatori.'); return; }
    var b=$('btn-writeftp'); b.disabled=true; b.textContent='Scrittura FTP…';
    post('rdacademy_boot_write_ftp', f, function(j){
      b.disabled=false; b.textContent='Scrivi via FTP';
      if(j && j.success){ toDone(j.data && j.data.message); }
      else { avviso((j&&j.data)?(j.data.message||j.data):'Scrittura FTP non riuscita.'); }
    });
  }

  // ---- nav ----
  $('next').addEventListener('click', function(){
    if(cur===0){ if(!baseOk){ testBase(); return; } go(1); }
    else if(cur===1){ writeDirect(); }
  });
  $('back').addEventListener('click', function(){ if(cur===1) go(0); });
  $('btn-testbase').addEventListener('click', testBase);
  $('btn-writeftp').addEventListener('click', writeFtp);
  var goBtn=$('btn-done-go'); if(goBtn){ goBtn.addEventListener('click', function(){ window.location.href = AFTER || (AJAX.replace('admin-ajax.php','index.php')); }); }
  ['f-host','f-port','f-name','f-user','f-pass','f-charset'].forEach(function(id){
    var e=$(id); if(e) e.addEventListener('input', function(){ baseOk=false; setPill('','—'); hideDbUse(); if(cur===1) renderBlock(); refresh(); });
  });

  refresh();
})();
</script>
