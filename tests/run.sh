#!/bin/bash
# Characterization harness. Runs Atlas under the given PHP binary with the
# built-in server, sends every request in tests/requests.txt, and records the
# status line, headers (minus Date) and body of each response. Image bodies
# are recorded as their size plus a hash of the decoded pixels, and saved
# alongside so diffs can be inspected. Upstream services are served by
# tests/fixtures/server.php through the ATLAS_UPSTREAM seam.
#
#   tests/run.sh <php-binary> <out-dir> [-d ini=value ...]
#
# ATLAS_APP points the harness at another checkout, e.g. a worktree of the old code.
# Compare two runs with: diff -r <out-a>/responses <out-b>/responses
set -u
PHP=${1:?php binary}; OUT=${2:?output directory}; shift 2
HERE=$(cd "$(dirname "$0")" && pwd)
APP=$(cd "${ATLAS_APP:-$HERE/..}" && pwd) || exit 1
[ -f "$APP/public/index.php" ] || { echo "not an Atlas checkout: $APP" >&2; exit 1; }
# The output directory is deleted and recreated, so it has to be a new or
# previous output directory: missing, or containing responses/
if [ -e "$OUT" ] && [ ! -d "$OUT/responses" ]; then
  echo "refusing to replace $OUT: it exists and is not a previous output directory" >&2; exit 1
fi
FIXTURE_PHP=${FIXTURE_PHP:-php8.4}
FIX=http://127.0.0.1:18380
BASE=http://127.0.0.1:18381

rm -rf "$OUT"; mkdir -p "$OUT/responses" "$OUT/images"
OUT=$(cd "$OUT" && pwd)

# Run from a full copy (vendor included, so the autoloader loads the copied
# code) so the test API key never lands in the real data/ directory
rsync -a --exclude .git --exclude tests "$APP/" "$OUT/app/"
echo "test-token" > "$OUT/app/data/apikeys.txt"
cat > "$OUT/router.php" <<'EOF'
<?php
// Serve existing files under public/ directly, everything else through index.php
$file = $_SERVER['DOCUMENT_ROOT'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if($_SERVER['REQUEST_URI'] !== '/' && is_file($file)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
chdir($_SERVER['DOCUMENT_ROOT']);
require 'index.php';
EOF

export FIXTURE_LOG="$OUT/fixture-requests.log"; : > "$FIXTURE_LOG"
pids=()
trap 'kill "${pids[@]}" 2>/dev/null; wait 2>/dev/null' EXIT
$FIXTURE_PHP -S 127.0.0.1:18380 "$HERE/fixtures/server.php" > "$OUT/fixture-server.log" 2>&1 & pids+=($!)
ATLAS_UPSTREAM=$FIX $PHP -d display_errors=0 -d log_errors=1 -d error_reporting=-1 -d "error_log=$OUT/php-errors.log" -d expose_php=0 "$@" \
  -S 127.0.0.1:18381 -t "$OUT/app/public" "$OUT/router.php" > "$OUT/server.log" 2>&1 & pids+=($!)
for port in 18380 18381; do
  for i in $(seq 50); do curl -s -o /dev/null "http://127.0.0.1:$port/" && break; sleep 0.1; done
done

# Values that change on every run: the current time, and the paths of this run
# (the old Slim app prints stack traces on errors). curl -I also writes the
# headers to the body file, so Date lines are dropped everywhere.
mask() {
  sed -E -e "s#$OUT/app#<app>#g; s#$OUT#<out>#g; s#$APP#<checkout>#g" \
    -e 's/"localtime":"[^"]*"/"localtime":"<now>"/g; s/"now":[0-9.]+/"now":"<now>"/g' \
    -e '/^[Dd]ate: /d'
}

n=0
while IFS= read -r line; do
  [[ -z "$line" || "$line" == \#* ]] && continue
  method=GET; body=
  if [[ "$line" =~ ^(GET|HEAD|POST)\ (.*)$ ]]; then method=${BASH_REMATCH[1]}; line=${BASH_REMATCH[2]}; fi
  if [[ "$line" == *" | "* ]]; then body=${line#* | }; line=${line%% | *}; fi
  name=$(printf '%04d-%s-%s' $n "$method" "$(echo "$line" | sed -E 's/[^A-Za-z0-9]+/-/g' | cut -c1-90)")
  args=(-s -g -D "$OUT/h.tmp" -o "$OUT/b.tmp" --max-time 60)
  case $method in
    HEAD) args+=(-I) ;;
    POST) args+=(--data "$body") ;;
  esac
  before=$(wc -l < "$FIXTURE_LOG")
  curl "${args[@]}" "$BASE$line"
  {
    echo "$method $line${body:+ | $body}"
    grep -iv '^date:' "$OUT/h.tmp" | tr -d '\r' | sed -E 's/^HTTP\/1\.[01] /HTTP\/1.x /'
    if grep -qi '^content-type: image/' "$OUT/h.tmp"; then
      ext=$(grep -i '^content-type:' "$OUT/h.tmp" | sed -E 's#.*image/([a-z]+).*#\1#' | tr -d '\r')
      cp "$OUT/b.tmp" "$OUT/images/$name.$ext"
      $FIXTURE_PHP "$HERE/pixels.php" "$OUT/b.tmp"
    else
      cat "$OUT/b.tmp"; echo
    fi
    echo "upstream:"; sed -n "$((before+1)),\$p" "$FIXTURE_LOG" | sort
  } | mask > "$OUT/responses/$name.txt"
  rm -f "$OUT/h.tmp" "$OUT/b.tmp"
  n=$((n+1))
done < "$HERE/requests.txt"

sed -E 's/^\[[^]]*\] //; s#'"$OUT"'/##g' "$OUT/php-errors.log" 2>/dev/null | sort | uniq -c | sort -rn > "$OUT/php-errors-summary.txt"
fatals=$(grep -ciE 'fatal|uncaught' "$OUT/php-errors-summary.txt")
echo "$(basename "$OUT"): $n requests; php error log: $(wc -l < "$OUT/php-errors-summary.txt") distinct, $fatals fatal"
