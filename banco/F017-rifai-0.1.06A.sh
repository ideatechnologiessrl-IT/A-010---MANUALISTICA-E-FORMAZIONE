#!/usr/bin/env bash
# F017-rifai-0.1.06A.sh — RelDoxAcademy · Formazione F017 · 19/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Ricostruisce il pacchetto di RelDoxAcademy 0.1.06A (REGOLE-FAMIGLIA v4 § 9.2): ZIP completo del plugin,
# nome coi trattini bassi, MD5 ed elenco dei file. Scrive nel file principale il nome breve del pacchetto
# e la data (mai a mano), controlla la terna Version / _VERSION / _STAGE, poi compone.
#
# uso:  bash banco/F017-rifai-0.1.06A.sh <cartella di uscita, fuori dal repository>
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:?serve la cartella di uscita}"
VER="0.1.06"; LETTERA="A"; STADIO="Alfa"; SESSIONE="F017"; NOME="copia-nella-riga-del-plugin"
PRINC="$REPO/reldox-academy/reldox-academy.php"
ZIP="reldox-academy-${VER//./_}.zip"

# 1. nome breve del pacchetto e data, scritti dal compositore
OGGI="$(date +%Y-%m-%d)"
sed -i -E "s#^define\( 'RDACADEMY_PACKAGE',( *)'[^']*' \);#define( 'RDACADEMY_PACKAGE',\1'// ${VER}${LETTERA} | ${SESSIONE} | ${NOME}' );#" "$PRINC"
sed -i -E "s#^define\( 'RDACADEMY_PACKAGE_DATE',( *)'[^']*' \);#define( 'RDACADEMY_PACKAGE_DATE',\1'${OGGI}' );#" "$PRINC"

# 2. la terna
H=$(grep -m1 -E '^ \* Version:' "$PRINC" | awk '{print $NF}')
V=$(grep -m1 -oE "RDACADEMY_VERSION', +'[^']+'" "$PRINC" | grep -oE "'[^']+'$" | tr -d "'")
S=$(grep -m1 -oE "RDACADEMY_STAGE', +'[^']+'" "$PRINC" | grep -oE "'[^']+'$" | tr -d "'")
if [ "$H" != "$VER" ] || [ "$V" != "$VER" ] || [ "$S" != "$STADIO" ]; then
  echo "ROSSO: terna non allineata (Version: $H · _VERSION $V · _STAGE $S, attesi $VER · $VER · $STADIO)"; exit 1
fi
php -l "$PRINC" >/dev/null

# 2 bis. il wizard scrive il database prima dell avvio di WordPress, anche nel wp-config italiano (BUG-1, F013-F1)
php "$REPO/banco/F013-banco-wpconfig.php" "$REPO/reldox-academy" >/dev/null || { echo "ROSSO: banco del wp-config non verde (php banco/F013-banco-wpconfig.php reldox-academy)"; exit 1; }

# 3. lo zip: la cartella del plugin, niente altro
mkdir -p "$OUT"; rm -f "$OUT/$ZIP"
( cd "$REPO" && find reldox-academy -type f ! -name '.DS_Store' | sort | zip -q -X "$OUT/$ZIP" -@ )
( cd "$OUT" && md5sum "$ZIP" > "MD5-${ZIP%.zip}.txt" && unzip -Z1 "$ZIP" > "ELENCO-${ZIP%.zip}.txt" )
echo "VERDE: $OUT/$ZIP · $(unzip -Z1 "$OUT/$ZIP" | wc -l) file · $(cut -c1-32 "$OUT/MD5-${ZIP%.zip}.txt") · pacchetto // ${VER}${LETTERA} | ${SESSIONE} | ${NOME} · data ${OGGI}"
