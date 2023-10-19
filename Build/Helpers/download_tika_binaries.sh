#!/usr/bin/env bash

## BASH COLORS
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

Help()
{
# Usage via composer ---------------------------------------------------------------------------------------------------
  if [[ -n "$CALLED_VIA_COMPOSER" ]]; then
    cat <<-EOF

Usage:
  $(basename "${COMPOSER_BINARY}") tika:download
  $(basename "${COMPOSER_BINARY}") tika:download [--] [<flags>]
  $(basename "${COMPOSER_BINARY}") tika:download [--] [<flags>] [<option> <parameter>]

Options:
 --tika-version  <tika-version>   Specific TIKA version. Default: ${REQUIRED_TIKA_VERSION}
 -D              <directory>      Directory to save the binaries in. Default: working directory

Flags:
 -a, -A, --app-only               Download Tika app only
 -s, -S, --server-only            Download Tika server only
 -c, -C, --check-signature        Signature Verification
                                    Note: imports Apaches TIKA public keys

Examples:
  $(basename "${COMPOSER_BINARY}") tika:download -- -D /tmp/tika-jars
  $(basename "${COMPOSER_BINARY}") tika:download -- -D /tmp/tika-jars
  $(basename "${COMPOSER_BINARY}") tika:download -- -D /tmp/tika-jars -C -a
  $(basename "${COMPOSER_BINARY}") tika:download -- -D /tmp/tika-jars -C -a --tika-version 1.24.1

EOF
    exit
  fi
# END: Usage via composer ----------------------------------------------------------------------------------------------

# Direct usage
cat <<-EOF

Usage:
  $(basename "$0") --version <tika-version>
  $(basename "$0") --version <tika-version> [<flags>] [<option> <parameter>]

Options:
 -v, -V, --version <version>     Specific TIKA version.
 -d, -D, --dir     <directory>   Directory to save the binaries in. Default: working directory

Flags:
 -a, -A, --app-only              Download app only
 -s, -S, --server-only           Download server only
 -c, -C, --check-signature       Signature Verification
                                  Note: imports Apaches TIKA public keys

EOF
}

# Default values
TIKA_PATH="$(pwd -P)"
TIKA_VERSION="${REQUIRED_TIKA_VERSION}"
APP_ONLY=0
SERVER_ONLY=0

LONG_OPTS_LIST=(
  "version:"
  "tika-version:"
  "dir:"
  "app-only"
  "server-only"
  "check-signature"
  "help"
)

#echo "$(printf "%s," "${LONG_OPTS_LIST[@]}")"
#exit

SHORT_OPTS_LIST=":v:V:d:D:aAsScCh"

opts=$(getopt \
  --longoptions "$(printf "%s," "${LONG_OPTS_LIST[@]}")" \
  --name "$(basename "$0")" \
  --options "${SHORT_OPTS_LIST}" \
  -- "$@"
)
eval set --"$opts"
while [[ $# -gt 0 ]]; do
  case "$1" in
    -v)
      TIKA_VERSION=$2; shift 2
      ;;
    -V)
      TIKA_VERSION=$2; shift 2
      ;;
    --version)
      TIKA_VERSION=$2; shift 2
      ;;
    --tika-version)
      TIKA_VERSION=$2; shift 2
      ;;

    -d)
      TIKA_PATH=$2; shift 2
      ;;
    -D)
      TIKA_PATH=$2; shift 2
      ;;
    --dir)
      TIKA_PATH=$2; shift 2
      ;;

    -a)
      APP_ONLY=1; shift 1
      ;;
    -A)
      APP_ONLY=1; shift 1
      ;;
    --app-only)
      APP_ONLY=1; shift 1
      ;;

    -s)
      SERVER_ONLY=1; shift 1
      ;;
    -S)
      SERVER_ONLY=1; shift 1
      ;;
    --server-only)
      SERVER_ONLY=1; shift 1
      ;;

    -c)
      CHECK_SIGNATURE=1; shift 1
      ;;
    -C)
      CHECK_SIGNATURE=1; shift 1
      ;;
    --check-signature)
      CHECK_SIGNATURE=1; shift 1
      ;;

    -h)
      Help ; exit 0 ; shift 1
      ;;
    --help)
      Help ; exit 0 ; shift 1
      ;;

    *)
      break
      ;;
  esac
done

function downloadTika() {
  TIKA_JAR_NAME="tika-$1"
  TIKA_BINARY_FILENAME="${TIKA_JAR_NAME}-${TIKA_VERSION}.jar"
  TIKA_BINARY_PATH_AND_FILENAME="${TIKA_PATH}/${TIKA_BINARY_FILENAME}"

  NEAREST_TIKA_SERVER_URL="https://www.apache.org/dyn/closer.cgi/tika/${TIKA_BINARY_FILENAME}?filename=tika/${TIKA_VERSION}/${TIKA_BINARY_FILENAME}&action=download"
  NEAREST_TIKA_SERVER_URL_OLD="https://www.apache.org/dyn/closer.cgi/tika/${TIKA_BINARY_FILENAME}?filename=tika/${TIKA_BINARY_FILENAME}&action=download"
  ARCHIVE_TIKA_SERVER_URL="https://archive.apache.org/dist/tika/${TIKA_BINARY_FILENAME}"
  DEFAULT_TIKA_SERVER_ASC_URL="https://downloads.apache.org/tika/${TIKA_VERSION}/${TIKA_BINARY_FILENAME}.asc"
  DEFAULT_TIKA_SERVER_ASC_URL_OLD="https://downloads.apache.org/tika/${TIKA_BINARY_FILENAME}.asc"
  ARCHIVE_TIKA_SERVER_ASC_URL="https://archive.apache.org/dist/tika/${TIKA_BINARY_FILENAME}.asc"

  # download jar file
  wget -q --show-progress -t 10 --max-redirect 1 --retry-connrefused "${NEAREST_TIKA_SERVER_URL}" -O "${TIKA_BINARY_PATH_AND_FILENAME}" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME} ]" \
    || wget -q --show-progress "${NEAREST_TIKA_SERVER_URL_OLD}" -O "${TIKA_BINARY_PATH_AND_FILENAME}" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME} ]" \
    || wget -q --show-progress "${ARCHIVE_TIKA_SERVER_URL}" -O "${TIKA_BINARY_PATH_AND_FILENAME}" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME} ]" \
    || >&2 echo "The ${TIKA_BINARY_FILENAME} file could not be downloaded. Please try again later." \
      || return 1

  if [[ "${CHECK_SIGNATURE}" -ne 1 ]]; then
    return 0
  fi

  # download verification file
  wget -q --show-progress -t 10 --max-redirect 1 --retry-connrefused "${DEFAULT_TIKA_SERVER_ASC_URL}" -O "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME}.asc ]" \
  || wget -q --show-progress "${DEFAULT_TIKA_SERVER_ASC_URL_OLD}" -O "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME}.asc ]" \
  || wget -q --show-progress "${ARCHIVE_TIKA_SERVER_ASC_URL}" -O "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      || rm "${TIKA_BINARY_PATH_AND_FILENAME}.asc" \
      && sh -c "[ -f ${TIKA_BINARY_PATH_AND_FILENAME}.asc ]" \
  || >&2 echo "The file integrity could not be verified, because" \
      || >&2 echo "  the ${TIKA_BINARY_FILENAME}.asc file could not be downloaded." \
      || return 3;

  echo -en "Checking signature: "
  if gpg --verify "${TIKA_BINARY_PATH_AND_FILENAME}.asc" "${TIKA_BINARY_PATH_AND_FILENAME}" > /dev/null 2>&1; then
    echo -e "${GREEN}"'✔'"${NC}"
    return 0
  else
    echo -e "${RED}"'✘'
    >&2 echo -e 'The signature verification failed. The files are not saved.'"${NC}"
    test -f "${TIKA_BINARY_PATH_AND_FILENAME}.asc" && rm "${TIKA_BINARY_PATH_AND_FILENAME}.asc"
    test -f "${TIKA_BINARY_PATH_AND_FILENAME}" && rm "${TIKA_BINARY_PATH_AND_FILENAME}"
    return 9
  fi
}

### begin action

if [[ -z "$CALLED_VIA_COMPOSER" ]] && [[ -z "$TIKA_VERSION" ]]; then
  >&2 echo "The $(basename "$0") called not via composer. The --version <tika-version> is required in this context."
  exit 1
fi

if [[ ! -d "${TIKA_PATH}" ]] || [[ ! -w "${TIKA_PATH}" ]]; then
  >&2 echo "The download cannot be started, because \"${TIKA_PATH}\" does not exist or is not writable."
  exit 2
fi

### Check dependencies for signature verifications
if [[ "${CHECK_SIGNATURE}" -eq 1 ]]; then
  if ! gpg --version > /dev/null 2>&1; then
    >&2 echo "Can not proceed, because"
    >&2 echo "  the --check-signature flag is set, but gpg is not installed."
    exit 3
  fi

  echo -en "Import Apache TIKA public keys: "
  KEYRING_URL="https://downloads.apache.org/tika/KEYS"
  KEYS_PATH_AND_FILENAME="${TIKA_PATH}/tika-keys-${TIKA_VERSION}.pub"
  if ! wget -q -t 10 --max-redirect 1 --retry-connrefused "${KEYRING_URL}" -O "${KEYS_PATH_AND_FILENAME}"; then
    >&2 echo "Can not proceed, because"
    >&2 echo "  the --check-signature flag is set, but the keys file could not be downloaded from ${KEYRING_URL}."
    exit 4
  fi
  if ! gpg --import "${KEYS_PATH_AND_FILENAME}" > /dev/null 2>&1; then
    >&2 echo "Can not proceed, because"
    >&2 echo "  the Apache TIKA keys could not be imported from \"${KEYS_PATH_AND_FILENAME}\" ."
  fi
  # cleanup the keys file
  test -f "${KEYS_PATH_AND_FILENAME}" && rm "${KEYS_PATH_AND_FILENAME}"
fi

EXIT_CODE=0
if [[ "${APP_ONLY}" -eq 0 ]] && [[ "${SERVER_ONLY}" -eq 0 ]]; then
  echo "Will download app and server: proceed..."
  downloadTika "app"
  EXIT_CODE=$((EXIT_CODE+$?))
  downloadTika "server"
  EXIT_CODE=$((EXIT_CODE+$?))
fi

if [[ "${APP_ONLY}" -eq 1 ]]; then
  echo "Will download app only: proceed..."
  downloadTika "app"
  EXIT_CODE=$((EXIT_CODE+$?))
fi

if [[ "${SERVER_ONLY}" -eq 1 ]]; then
  echo "Will download server only: proceed..."
  downloadTika "server"
  EXIT_CODE=$((EXIT_CODE+$?))
fi

exit ${EXIT_CODE}
