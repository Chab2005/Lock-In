#!/usr/bin/env bash
# Generate PNG icons from icon.svg using Inkscape or rsvg-convert.
# Run once before loading the extension.
#
#   brew install librsvg       # macOS
#   sudo apt install librsvg2-bin  # Linux

set -e
DIR="$(cd "$(dirname "$0")" && pwd)"
SVG="$DIR/icon.svg"

for SIZE in 16 32 48 128; do
    rsvg-convert -w $SIZE -h $SIZE "$SVG" -o "$DIR/icon-${SIZE}.png"
    echo "Generated icon-${SIZE}.png"
done

echo "Done."
