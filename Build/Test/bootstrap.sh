#!/usr/bin/env bash

if [[ -n ${BASH_SOURCE[0]} ]]; then
  # shellcheck disable=SC2164
  ABSOLUTE_SCRIPT_PATH=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )
else
  # shellcheck disable=SC2164
  ABSOLUTE_SCRIPT_PATH="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
fi
EXTENSION_ROOT_PATH="${ABSOLUTE_SCRIPT_PATH}/../../"

DEFAULT_TYPO3_DATABASE_HOST="localhost"
DEFAULT_TYPO3_DATABASE_NAME="test"
DEFAULT_TYPO3_DATABASE_USERNAME="root"
DEFAULT_TYPO3_DATABASE_PASSWORD="supersecret"
DEFAULT_TIKA_PATH="${EXTENSION_ROOT_PATH}.Build/bin/"

if [[ $* == *--use-defaults* ]]; then
  export TYPO3_DATABASE_HOST="${DEFAULT_TYPO3_DATABASE_HOST}"
  export TYPO3_DATABASE_NAME="${DEFAULT_TYPO3_DATABASE_NAME}"
  export TYPO3_DATABASE_USERNAME="${DEFAULT_TYPO3_DATABASE_USERNAME}"
  export TYPO3_DATABASE_PASSWORD="${DEFAULT_TYPO3_DATABASE_PASSWORD}"
  export TIKA_PATH="${DEFAULT_TIKA_PATH}"
fi

if [[ $* == *--local* ]]; then
  echo -n "Choose a database hostname: [defaults: ${DEFAULT_TYPO3_DATABASE_HOST}] : "
  read -r typo3DbHost
  if [[ -z "${typo3DbHost}" ]]; then typo3DbHost="${DEFAULT_TYPO3_DATABASE_HOST}"; fi
  export TYPO3_DATABASE_HOST=${typo3DbHost}

  echo -n "Choose a database name: [defaults: ${DEFAULT_TYPO3_DATABASE_NAME}] : "
  read -r typo3DbName
  if [[ -z "${typo3DbName}" ]]; then typo3DbName=${DEFAULT_TYPO3_DATABASE_NAME}; fi
  export TYPO3_DATABASE_NAME="${typo3DbName}"

  echo -n "Choose a database user: [defaults: ${DEFAULT_TYPO3_DATABASE_USERNAME}] : "
  read -r typo3DbUser
  if [ -z "${typo3DbUser}" ]; then typo3DbUser="${DEFAULT_TYPO3_DATABASE_USERNAME}"; fi
  export TYPO3_DATABASE_USERNAME=$typo3DbUser

  echo -n "Choose a database password: [defaults: ${DEFAULT_TYPO3_DATABASE_PASSWORD}] : "
  read -r typo3DbPassword
  if [ -z "${typo3DbPassword}" ]; then typo3DbPassword="${DEFAULT_TYPO3_DATABASE_PASSWORD}"; fi
  export TYPO3_DATABASE_PASSWORD="${typo3DbPassword}"

  echo -n "Choose a path for Apache Tika binary/.jar files: [defaults: ${DEFAULT_TIKA_PATH}] : "
    read -r tikaPath
    if [ -z "${tikaPath}" ]; then tikaPath=${DEFAULT_TIKA_PATH}; fi
    export TIKA_PATH="${tikaPath}"
fi

# download Tika if not present
if [[ ! -d "${TIKA_PATH}" ]]; then
	mkdir -p "${TIKA_PATH}"
fi
if [[ ! -f "${TIKA_PATH}/tika-app-$(composer tika:version).jar" ]] && ! composer tika:download -- -D "${TIKA_PATH}" -C -a; then
  echo "Could not download Tika app jar file required for tests."
  exit 1
fi

echo "PWD: $(pwd)"

export TYPO3_PATH_PACKAGES="${EXTENSION_ROOT_PATH}.Build/vendor/"
export TYPO3_PATH_WEB="${EXTENSION_ROOT_PATH}.Build/Web/"
mkdir -p "${TYPO3_PATH_WEB}"/uploads "$TYPO3_PATH_WEB"/typo3temp

echo "Using extension path $EXTENSION_ROOT_PATH"
echo "Using package path $TYPO3_PATH_PACKAGES"
echo "Using web path $TYPO3_PATH_WEB"

# shellcheck disable=SC2034
export COMPOSER_NO_INTERACTION=1
# Install build tools
echo "Install build tools: "
if ! composer global require \
  sclable/xml-lint \
  scrutinizer/ocular
then
  echo "The build tools(sclable/xml-lint, scrutinizer/ocular) could not be installed. Please fix this issue."
  exit 1
fi

SETUP_VARIANT=""
if [[ "${TYPO3_VERSION}" = *"dev"* ]]; then
  SETUP_VARIANT=":t3dev"
fi

if ! composer "tests:setup${SETUP_VARIANT}"; then
  echo "The test environment could not be installed by composer as expected. Please fix this issue."
  exit 1
fi

