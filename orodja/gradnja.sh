#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PACKAGE_MANIFEST="paket/pkg_belauncerunde.xml"
VERSION="$(sed -n 's:.*<version>\([^<][^<]*\)</version>.*:\1:p' "$PACKAGE_MANIFEST" | head -n 1)"

if [[ -z "$VERSION" ]]; then
    echo "Napaka: verzije ni mogoče prebrati iz $PACKAGE_MANIFEST." >&2
    exit 1
fi

echo "Verzija paketa: $VERSION"

while IFS= read -r file; do
    while IFS= read -r found; do
        [[ -z "$found" ]] && continue

        if [[ "$found" != "$VERSION" ]]; then
            echo "Napaka: $file vsebuje verzijo $found, pričakovana je $VERSION." >&2
            exit 1
        fi
    done < <(sed -n 's:.*<version>\([^<][^<]*\)</version>.*:\1:p' "$file")
done < <(find paket -name '*.xml' -print; printf '%s\n' posodobitve.xml)

echo "Preverjanje verzij: OK"

if command -v php >/dev/null 2>&1; then
    while IFS= read -r file; do
        php -l "$file" >/dev/null
    done < <(find paket -name '*.php' -print)
    echo "Preverjanje PHP: OK"
else
    echo "Opozorilo: php ni na voljo, preverjanje PHP sintakse je preskočeno."
fi

if command -v xmllint >/dev/null 2>&1; then
    while IFS= read -r file; do
        xmllint --noout "$file"
    done < <(find paket -name '*.xml' -print; printf '%s\n' posodobitve.xml)
    echo "Preverjanje XML: OK"
else
    echo "Opozorilo: xmllint ni na voljo, preverjanje XML sintakse je preskočeno."
fi

if grep -R -n -E 'JHtml|JFactory|JText|JRoute|JUri|JTable|jQuery|getInstance\(' paket; then
    echo "Napaka: najden je prepovedan Joomla vzorec." >&2
    exit 1
fi

echo "Preverjanje prepovedanih vzorcev: OK"

while IFS= read -r file; do
    if grep -qi 'CREATE TABLE' "$file" && ! grep -qi 'utf8mb4' "$file"; then
        echo "Napaka: CREATE TABLE v $file ne vsebuje utf8mb4." >&2
        exit 1
    fi
done < <(find paket -name '*.sql' -print)

echo "Preverjanje SQL utf8mb4: OK"

if ! command -v zip >/dev/null 2>&1; then
    echo "Napaka: zip ni na voljo." >&2
    exit 1
fi

DIST="$ROOT/dist"
TMP="$DIST/tmp"
PKG_TMP="$TMP/pkg_belauncerunde"
ZIP_PATH="$DIST/pkg_belauncerunde-$VERSION.zip"

rm -rf "$TMP"
mkdir -p "$PKG_TMP/packages"
mkdir -p "$DIST"

(cd paket/com_belauncerunde && zip -qr "$PKG_TMP/packages/com_belauncerunde.zip" .)
cp "$PACKAGE_MANIFEST" "$PKG_TMP/pkg_belauncerunde.xml"
cp -R paket/language "$PKG_TMP/language"

rm -f "$ZIP_PATH"
(cd "$PKG_TMP" && zip -qr "$ZIP_PATH" .)
rm -rf "$TMP"

if command -v sha256sum >/dev/null 2>&1; then
    SHA256="$(sha256sum "$ZIP_PATH" | awk '{print $1}')"
else
    SHA256="$(shasum -a 256 "$ZIP_PATH" | awk '{print $1}')"
fi

echo "ZIP: $ZIP_PATH"
echo "SHA-256: $SHA256"
