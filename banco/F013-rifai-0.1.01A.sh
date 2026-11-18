#!/usr/bin/env bash
# F013-rifai-0.1.01A.sh — RelDoxAcademy · Formazione F013 · 18/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Ricostruisce il pacchetto di RelDoxAcademy 0.1.01A (REGOLE-FAMIGLIA v4 § 9.2): ZIP completo del plugin,
# nome coi trattini bassi, MD5 ed elenco dei file. Scrive nel file principale il nome breve del pacchetto
# e la data (mai a mano), controlla la terna Version / _VERSION / _STAGE, poi compone.
#
# uso:  bash banco/F013-rifai-0.1.01A.sh <cartella di uscita, fuori dal repository>
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:?serve la cartella di uscita}"
VER="0.1.01"; LETTERA="A"; STADIO="Alfa"; SESSIONE="F013"; NOME="installabile"
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

# 3. lo zip: la cartella del plugin, niente altro
mkdir -p "$OUT"; rm -f "$OUT/$ZIP"
( cd "$REPO" && find reldox-academy -type f ! -name '.DS_Store' | sort | zip -q -X "$OUT/$ZIP" -@ )
( cd "$OUT" && md5sum "$ZIP" > "MD5-${ZIP%.zip}.txt" && unzip -Z1 "$ZIP" > "ELENCO-${ZIP%.zip}.txt" )
echo "VERDE: $OUT/$ZIP · $(unzip -Z1 "$OUT/$ZIP" | wc -l) file · $(cut -c1-32 "$OUT/MD5-${ZIP%.zip}.txt") · pacchetto // ${VER}${LETTERA} | ${SESSIONE} | ${NOME} · data ${OGGI}"
