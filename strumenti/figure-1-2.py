"""Figure SVG della lezione 1.2 (RelDoxFood, percorso 1). Formazione F002. Dati inventati.
Uso: python3 -I strumenti/figure-1-2.py <cartella img>"""
import sys, os
OUT = sys.argv[1]
F = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif'
TILES = ['Azienda', 'Festività', 'Branding', 'Email', 'Dati FTP', 'Integrazioni', 'Ristoratori', 'Sistema', 'Database', 'Pericolo']

def num(n, x, y):
    return f'<circle cx="{x}" cy="{y}" r="12" fill="#E8B23A"/><text x="{x}" y="{y+5}" font-size="14" font-weight="700" text-anchor="middle" fill="#0F172A">{n}</text>'

def ev(x, y, w, h):
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="none" stroke="#E8B23A" stroke-width="3"/>'

def base(attiva, modo, corpo, extra, label):
    t = []
    for i, n in enumerate(TILES):
        x = 20 + (i % 5) * 57; y = 76 + (i // 5) * 24
        on = n == attiva; danger = n == 'Pericolo'
        fill = '#E8F6FA' if on else '#fff'; st = '#0C6E86' if on else ('#F5B5B5' if danger else '#CBD3DE')
        col = '#B91C1C' if danger else ('#0C6E86' if on else '#374151')
        t.append(f'<rect x="{x}" y="{y}" width="53" height="20" rx="4" fill="{fill}" stroke="{st}"/><text x="{x+26.5}" y="{y+13.5}" font-size="8.6" text-anchor="middle" fill="{col}" font-weight="{700 if on else 400}">{n}</text>')
    if modo == 'lettura':
        cmd = '<rect x="196" y="46" width="48" height="16" rx="8" fill="#EEF1F5"/><text x="220" y="57.5" font-size="7.5" text-anchor="middle" fill="#5B6472">In lettura</text><rect x="250" y="44" width="52" height="20" rx="5" fill="#0C6E86"/><text x="276" y="57.5" font-size="9" font-weight="700" text-anchor="middle" fill="#fff">Modifica</text>'
    elif modo == 'salvato':
        cmd = '<rect x="160" y="46" width="64" height="16" rx="8" fill="#E8F5EC"/><text x="192" y="57.5" font-size="7.5" font-weight="700" text-anchor="middle" fill="#15803D">Tutto salvato</text><rect x="250" y="44" width="52" height="20" rx="5" fill="#0C6E86"/><text x="276" y="57.5" font-size="9" font-weight="700" text-anchor="middle" fill="#fff">Modifica</text>'
    else:
        cmd = '<rect x="196" y="44" width="48" height="20" rx="5" fill="#fff" stroke="#CBD3DE"/><text x="220" y="57.5" font-size="9" text-anchor="middle" fill="#374151">Annulla</text><rect x="250" y="44" width="52" height="20" rx="5" fill="#0C6E86"/><text x="276" y="57.5" font-size="9" font-weight="700" text-anchor="middle" fill="#fff">Salva</text>'
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 230" role="img" aria-label="{label}">
<title>Impostazioni di RelDoxFood</title>
<style>text{{font-family:{F}}}</style>
<rect x="4" y="4" width="312" height="222" rx="10" fill="#0F2A44"/>
<rect x="12" y="12" width="296" height="20" rx="5" fill="#fff"/><text x="22" y="26" font-size="10" fill="#374151">www.azienda.it/food/impostazioni</text>
<rect x="12" y="36" width="296" height="182" fill="#F4F6F9"/>
<text x="20" y="58" font-size="13" font-weight="700" fill="#111827">Impostazioni</text>
{cmd}
{''.join(t)}
<rect x="20" y="126" width="281" height="86" rx="6" fill="#fff" stroke="#E3E7ED"/>
{corpo}
{extra}
</svg>
'''

def riga(x, y, et, val, w=120):
    return f'<text x="{x}" y="{y}" font-size="8" fill="#5B6472">{et}</text><rect x="{x}" y="{y+3}" width="{w}" height="13" rx="3" fill="#fff" stroke="#CBD3DE"/><text x="{x+4}" y="{y+12.5}" font-size="8.6" fill="#111827">{val}</text>'

def tit(x, y, s):
    return f'<text x="{x}" y="{y}" font-size="9.5" font-weight="700" fill="#111827">{s}</text>'

FIG = {}
FIG['1-2-fig1-pannello.svg'] = base('Azienda', 'lettura',
    tit(30, 142, 'Dati fiscali') + '<text x="30" y="158" font-size="7.6" fill="#5B6472">Ragione sociale</text><text x="110" y="158" font-size="7.6" fill="#111827">non indicato</text>',
    ev(17, 73, 287, 50) + num(1, 304, 70),
    'Figura 1: la pagina Impostazioni in lettura; il numero 1 indica il pannello con dieci pulsanti: Azienda, Festività, Branding, Email, Dati FTP, Integrazioni, Ristoratori, Sistema, Database, Pericolo')
FIG['1-2-fig2-modifica.svg'] = base('Azienda', 'modifica',
    tit(30, 142, 'Dati fiscali') + riga(30, 150, 'Ragione sociale', '', 150),
    ev(193, 41, 112, 26) + num(2, 300, 80) ,
    'Figura 2: dopo aver toccato Modifica compaiono Annulla e Salva, indicati dal numero 2; i campi si possono scrivere')
FIG['1-2-fig3-azienda.svg'] = base('Azienda', 'modifica',
    tit(30, 140, 'Dati fiscali') + riga(30, 148, 'Ragione sociale', 'Azienda Esempio S.p.A.') + riga(30, 172, 'Partita IVA', '00000000000') +
    tit(166, 140, 'Nome utente') + riga(166, 148, 'Regola', 'cognome + matricola', 126) +
    '<text x="166" y="182" font-size="7" fill="#5B6472">Anteprima</text><rect x="166" y="186" width="70" height="14" rx="7" fill="#E8F6FA"/><text x="201" y="196" font-size="7.6" font-weight="700" text-anchor="middle" fill="#0C6E86">rossi1042</text>',
    ev(17, 73, 56, 26) + ev(162, 132, 136, 74) + num(3, 300, 128),
    'Figura 3: la sezione Azienda in modifica, con Dati fiscali (ragione sociale e partita IVA inventate) e Nome utente e foglio di accesso con la regola e l anteprima rossi1042; il numero 3 indica l anteprima')
FIG['1-2-fig4-festivita.svg'] = base('Festività', 'modifica',
    tit(30, 140, 'Festività nazionali') +
    ''.join(f'<text x="30" y="{156+i*13}" font-size="8.6" fill="#111827">{n}</text><rect x="116" y="{147+i*13}" width="30" height="11" rx="3" fill="{"#FDECEC" if c=="Chiuso" else "#E8F5EC"}"/><text x="131" y="{155.5+i*13}" font-size="7.6" font-weight="700" text-anchor="middle" fill="{"#B91C1C" if c=="Chiuso" else "#15803D"}">{c}</text>'
            for i, (n, c) in enumerate([('Capodanno', 'Chiuso'), ('Epifania', 'Chiuso'), ('Lunedì di Pasqua', 'Chiuso'), ('2 giugno', 'Aperto')])) +
    tit(166, 140, 'Santo Patrono') + riga(166, 148, 'Giorno (gg/mm)', '24/06', 60),
    ev(74, 73, 56, 26) + ev(112, 145, 38, 54) + ev(162, 132, 70, 36) + num(4, 244, 150),
    'Figura 4: la sezione Festività in modifica; a sinistra le festività nazionali, ognuna Chiuso o Aperto; a destra il Santo Patrono 24/06; il numero 4 indica le due scelte')
FIG['1-2-fig5-branding.svg'] = base('Branding', 'modifica',
    tit(30, 140, 'Loghi dell’azienda') +
    '<rect x="30" y="148" width="34" height="34" rx="5" fill="#F4F6F9" stroke="#CBD3DE" stroke-dasharray="3 2"/><text x="47" y="192" font-size="7.6" text-anchor="middle" fill="#5B6472">telefono</text>'
    '<rect x="72" y="156" width="70" height="22" rx="5" fill="#F4F6F9" stroke="#CBD3DE" stroke-dasharray="3 2"/><text x="107" y="192" font-size="7.6" text-anchor="middle" fill="#5B6472">computer e tablet</text>' +
    tit(166, 140, 'Foglio per la bacheca') + '<rect x="166" y="150" width="30" height="40" rx="2" fill="#fff" stroke="#CBD3DE"/><rect x="173" y="164" width="16" height="16" fill="#0F2A44"/>'
    '<rect x="204" y="160" width="82" height="20" rx="5" fill="#fff" stroke="#0C6E86"/><text x="245" y="173.5" font-size="8" font-weight="700" text-anchor="middle" fill="#0C6E86">Genera dal sistema</text>',
    ev(131, 73, 56, 26) + ev(26, 144, 120, 54) + num(5, 152, 140),
    'Figura 5: la sezione Branding in modifica; a sinistra i due loghi, quadrato per il telefono e orizzontale per computer e tablet, indicati dal numero 5; a destra il Foglio per la bacheca con il pulsante Genera dal sistema')
FIG['1-2-fig6-email.svg'] = base('Email', 'modifica',
    tit(30, 140, 'Casella di servizio') + '<rect x="128" y="132" width="50" height="12" rx="6" fill="#FDF3E1"/><text x="153" y="140.5" font-size="7.6" font-weight="700" text-anchor="middle" fill="#9A5B00">da provare</text>' +
    riga(30, 150, 'Indirizzo', 'mensa@azienda-esempio.it', 130) + riga(30, 174, 'Server SMTP', 'smtp.azienda-esempio.it', 130) +
    tit(176, 140, 'SMTP · posta in uscita') + riga(176, 150, 'Porta', '465', 50) + riga(234, 150, 'Sicurezza', 'SSL', 50),
    ev(188, 73, 56, 26) + ev(26, 146, 140, 46) + num(6, 170, 196),
    'Figura 6: la sezione Email in modifica, con la Casella di servizio ancora da provare, l indirizzo e il server SMTP inventati, indicati dal numero 6')
FIG['1-2-fig7-salva.svg'] = base('Email', 'salvato',
    tit(30, 140, 'Casella di servizio') + '<rect x="128" y="132" width="50" height="12" rx="6" fill="#FDF3E1"/><text x="153" y="140.5" font-size="7.6" font-weight="700" text-anchor="middle" fill="#9A5B00">da provare</text>' +
    '<text x="30" y="160" font-size="7.6" fill="#5B6472">Indirizzo</text><text x="80" y="160" font-size="7.6" fill="#111827">mensa@azienda-esempio.it</text>',
    ev(157, 43, 70, 22) + num(7, 140, 54),
    'Figura 7: dopo Salva la pagina torna in lettura e la pastiglia dice Tutto salvato, indicata dal numero 7; la casella di servizio è ancora da provare')
FIG['1-2-fig8-prova.svg'] = base('Email', 'modifica',
    tit(30, 140, 'Casella di servizio') + '<rect x="128" y="132" width="44" height="12" rx="6" fill="#E8F5EC"/><text x="150" y="140.5" font-size="7.6" font-weight="700" text-anchor="middle" fill="#15803D">verificata</text>' +
    '<rect x="30" y="150" width="86" height="20" rx="5" fill="#fff" stroke="#0C6E86"/><text x="73" y="163.5" font-size="8" font-weight="700" text-anchor="middle" fill="#0C6E86">Prova la spedizione</text>' +
    ''.join(f'<circle cx="{136}" cy="{154+i*14}" r="5" fill="#15803D"/><path d="m133.5 {154+i*14} 1.8 1.8 3.4-3.4" fill="none" stroke="#fff" stroke-width="1.5"/><text x="146" y="{157+i*14}" font-size="8.4" fill="#111827">{s}</text>'
            for i, s in enumerate(['Il nome del server esiste', 'Il server risponde', 'Utente e password accettati'])),
    ev(26, 146, 94, 28) + num(8, 30, 186),
    'Figura 8: in modifica, il pulsante Prova la spedizione indicato dal numero 8; a destra l esito passo per passo, tutto verde, e la casella diventa verificata')

for nome, svg in FIG.items():
    with open(os.path.join(OUT, nome), 'w', encoding='utf-8') as f:
        f.write(svg)
print(len(FIG), 'figure')
