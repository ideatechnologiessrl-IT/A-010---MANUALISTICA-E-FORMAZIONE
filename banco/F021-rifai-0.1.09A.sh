#!/usr/bin/env bash
# F021-rifai-0.1.09A.sh — RelDoxAcademy · Formazione F021 · 20/11/2026
# ⛔ Documento riservato · non divulgabile — Idea Technologies srl soc. Uninominale.
#
# Ricostruisce il pacchetto di RelDoxAcademy 0.1.09A e della sua diagnostica (REGOLE-FAMIGLIA v4 § 9.2): ZIP completo del plugin,
# nome coi trattini bassi, MD5 ed elenco dei file. Scrive nel file principale il nome breve del pacchetto
# e la data (mai a mano), controlla la terna Version / _VERSION / _STAGE, poi compone.
#
# uso:  bash banco/F021-rifai-0.1.09A.sh <cartella di uscita, fuori dal repository>
set -euo pipefail

REPO="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:?serve la cartella di uscita}"
VER="0.1.09"; LETTERA="A"; STADIO="Alfa"; SESSIONE="F021"; NOME="diagnostica"
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

# 4. F021 — il plugin di diagnostica (SPEC-MANUTENZIONE v2 § 3): stesso numero e stesso pacchetto del prodotto (G10),
#    elenco dei file con MD5 in includes/manifest-file.php (mai a mano), zip a parte.
DIAG="$REPO/reldox-academy-diagnostica"; DPRINC="$DIAG/reldox-academy-diagnostica.php"
DZIP="reldox-academy-diagnostica-${VER//./_}.zip"
sed -i -E "s#^ \* Version:( +)[0-9.]+# * Version:\1${VER}#" "$DPRINC"
sed -i -E "s#^define\( 'RDACADEMY_DIAG_VERSION',( *)'[^']*' \);#define( 'RDACADEMY_DIAG_VERSION',\1'${VER}' );#" "$DPRINC"
sed -i -E "s#^define\( 'RDACADEMY_DIAG_STAGE',( *)'[^']*' \);#define( 'RDACADEMY_DIAG_STAGE',\1'${STADIO}' );#" "$DPRINC"
sed -i -E "s#^define\( 'RDACADEMY_DIAG_PACKAGE',( *)'[^']*' \);#define( 'RDACADEMY_DIAG_PACKAGE',\1'// ${VER}${LETTERA} | ${SESSIONE} | ${NOME}' );#" "$DPRINC"
{
  echo "<?php"
  echo "// Elenco dei file del pacchetto: scritto da banco/F021-rifai-0.1.09A.sh, MAI A MANO. Pacchetto di diagnostica: solo Idea Technologies."
  echo "if ( ! defined( 'ABSPATH' ) ) exit;"
  echo "return array("
  echo "	'pacchetto' => '${VER}',"
  echo "	'etichetta' => '${VER}${LETTERA}',"
  echo "	'creato' => '$(date '+%Y-%m-%d %H:%M')',"
  echo "	'file' => array("
  ( cd "$DIAG" && find . -type f ! -name manifest-file.php ! -name '.DS_Store' | sed 's#^\./##' | sort | while read -r f; do echo "		'$f' => '$(md5sum "$f" | cut -c1-32)',"; done )
  echo "	),"
  echo ");"
} > "$DIAG/includes/manifest-file.php"
for f in $(find "$DIAG" -name '*.php'); do php -l "$f" >/dev/null; done
rm -f "$OUT/$DZIP"
( cd "$REPO" && find reldox-academy-diagnostica -type f ! -name '.DS_Store' | sort | zip -q -X "$OUT/$DZIP" -@ )
( cd "$OUT" && md5sum "$DZIP" > "MD5-${DZIP%.zip}.txt" )
echo "VERDE: $OUT/$DZIP · $(unzip -Z1 "$OUT/$DZIP" | wc -l) file · $(cut -c1-32 "$OUT/MD5-${DZIP%.zip}.txt")"
