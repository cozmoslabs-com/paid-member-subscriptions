#!/usr/bin/env bash
set -euo pipefail

# Run Cypress via DDEV for paidmembersubscriptions-testing (sibling DDEV project).
#
# Serial (single DDEV project, one cypress run):
#   ./run-tests.sh
#   ./run-tests.sh --minimal
#   ./run-tests.sh --minimal --spec "cypress/e2e/..."
#   Any other args are forwarded to: ddev cypress-run
#
# Parallel (worker pool + orchestrator in the testing repo; see that README):
#   ./run-tests.sh --parallel
#   ./run-tests.sh --parallel --nodes=4
#   ./run-tests.sh --parallel --nodes=4 --minimal
#   ./run-tests.sh --parallel --spec='cypress/e2e/paidmembersubscriptions/forms/*.cy.js'
#   --parallel runs scripts/start-cypress-ddev-workers.sh, then scripts/cypress-parallel-run.mjs

usage() {
  cat <<'EOF'
Usage: ./run-tests.sh [options] [-- extra-cypress-args]

  Serial (default)
    Runs: ddev cypress-run in paidmembersubscriptions-testing (starts DDEV if web is down).

    --minimal          Pass --env runMode=minimal to Cypress.
    (other args)       Forwarded to ddev cypress-run (e.g. --spec "cypress/e2e/...").

  Parallel
    --parallel         Start primary + all parallel worker DDEV projects, then run the
                       parallel orchestrator (node scripts/cypress-parallel-run.mjs).
    --nodes=N          Use only the first N testing instances (main + workers). Optional;
                       omit to use all instances from cypress-parallel-workers.json.
    --spec=GLOB        Optional spec glob for the orchestrator (same as parallel script).

    --minimal          With --parallel, passed after -- to Cypress (runMode=minimal).
    --                 Further Cypress CLI args for each parallel run (after --).

  -h, --help           Show this help.

Examples:
  ./run-tests.sh --minimal
  ./run-tests.sh --parallel --nodes=4
EOF
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TESTING_DIR="$(cd "$SCRIPT_DIR/../../../.." && pwd)/paidmembersubscriptions-testing"
START_WORKERS_SH="$TESTING_DIR/scripts/start-cypress-ddev-workers.sh"
PARALLEL_RUN_JS="$TESTING_DIR/scripts/cypress-parallel-run.mjs"

parallel=false
nodes=""
minimal=false
spec_glob=""
passthrough=()
extra_after=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --parallel)
      parallel=true
      shift
      ;;
    --nodes=*)
      nodes="${1#--nodes=}"
      shift
      ;;
    --nodes)
      if [[ -z "${2:-}" ]]; then
        echo "run-tests.sh: --nodes requires a value (e.g. --nodes=4)." >&2
        exit 1
      fi
      nodes="$2"
      shift 2
      ;;
    --minimal)
      minimal=true
      shift
      ;;
    --spec=*)
      spec_glob="${1#--spec=}"
      shift
      ;;
    --spec)
      if [[ -z "${2:-}" ]]; then
        echo "run-tests.sh: --spec requires a value." >&2
        exit 1
      fi
      spec_glob="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    --)
      shift
      extra_after=("$@")
      break
      ;;
    *)
      passthrough+=("$1")
      shift
      ;;
  esac
done

if [[ -n "$nodes" ]] && [[ "$parallel" != true ]]; then
  echo "run-tests.sh: --nodes is only valid with --parallel." >&2
  exit 1
fi

if [[ -n "$nodes" ]] && ! [[ "$nodes" =~ ^[1-9][0-9]*$ ]]; then
  echo "run-tests.sh: --nodes must be a positive integer." >&2
  exit 1
fi

if [[ "$parallel" == true ]] && [[ ${#passthrough[@]} -gt 0 ]]; then
  echo "run-tests.sh: in --parallel mode, unknown arguments: ${passthrough[*]}" >&2
  echo "Use --spec / --nodes / --minimal, or put Cypress extras after -- ." >&2
  exit 1
fi

cd "$TESTING_DIR"

sudo xhost +

if [[ "$parallel" == true ]]; then
  if [[ ! -f "$PARALLEL_RUN_JS" ]]; then
    echo "run-tests.sh: missing parallel runner: $PARALLEL_RUN_JS" >&2
    exit 1
  fi
  if [[ ! -f "$START_WORKERS_SH" ]]; then
    echo "run-tests.sh: missing worker start script: $START_WORKERS_SH" >&2
    exit 1
  fi
  echo "Starting primary + parallel Cypress worker DDEV projects..."
  bash "$START_WORKERS_SH"

  cmd=(node "$PARALLEL_RUN_JS")
  if [[ -n "$nodes" ]]; then
    cmd+=(--nodes="$nodes")
  fi
  if [[ -n "$spec_glob" ]]; then
    cmd+=(--spec="$spec_glob")
  fi

  cypress_extra=()
  if [[ "$minimal" == true ]]; then
    cypress_extra+=(--env runMode=minimal)
  fi
  cypress_extra+=("${extra_after[@]}")
  if [[ ${#cypress_extra[@]} -gt 0 ]]; then
    cmd+=(-- "${cypress_extra[@]}")
  fi

  exec "${cmd[@]}"
fi

# --- serial path ---

# Skip ddev start if the web service is already up (see ddev describe: STAT / JSON status).
web_status=""
if command -v jq >/dev/null 2>&1; then
  web_status="$(ddev describe -j 2>/dev/null | jq -r '.raw.services.web.status // ""')"
fi

if [[ "$web_status" == "running" ]]; then
  echo "DDEV web service already running; skipping ddev start."
else
  echo "DDEV web service status: ${web_status:-unknown}; running ddev start..."
  ddev start
fi

cmd=(ddev cypress-run)
if [[ "$minimal" == true ]]; then
  cmd+=(--env runMode=minimal)
fi
if [[ -n "$spec_glob" ]]; then
  cmd+=(--spec "$spec_glob")
fi
cmd+=("${passthrough[@]}")
cmd+=("${extra_after[@]}")

exec "${cmd[@]}"
