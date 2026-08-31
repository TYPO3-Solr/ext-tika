#!/usr/bin/env bash

#
# Points the EXT:solr version for CI at a branch of the same name in TYPO3-Solr/ext-solr,
# so a change spanning both repositories can be tested before either side is merged.
#
# Only upstream is consulted to prevent security risks:
#   an EXT:solr fork would run in CI with our secrets and outside maintainer control.
#

set -eu

if [ "${GITHUB_ACTIONS:-}" = "true" ]; then
  BRANCH="${GITHUB_HEAD_REF:-${GITHUB_REF_NAME:-}}"
else
  BRANCH="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || true)"
fi

is_matching() {
  if [ -n "$BRANCH" ] && git ls-remote --exit-code --heads \
    https://github.com/TYPO3-Solr/ext-solr "$BRANCH" > /dev/null 2>&1
  then
    echo 'true'
  else
    echo 'false'
  fi
}

# EXT:solr is pinned in require-dev here, not in a CI matrix.
# The alias keeps other packages' constraints on EXT:solr satisfiable; tests:restore-git reverts composer.json afterwards.
pin_matching_branch() {
  # shellcheck disable=SC2016
  CURRENT_EXT_SOLR_REQUIREMENT="$(php -r 'echo json_decode(file_get_contents("composer.json"), true)["require-dev"]["apache-solr-for-typo3/solr"];')"

  # shellcheck disable=SC2016
  php -r '
      $file = "composer.json";
      $manifest = json_decode(file_get_contents($file), true);
      $manifest["require-dev"]["apache-solr-for-typo3/solr"] = $argv[1];
      file_put_contents(
          $file,
          json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
      );
  ' "dev-${BRANCH} as ${CURRENT_EXT_SOLR_REQUIREMENT}"

  echo "EXT:solr for CI pinned to 'dev-${BRANCH} as ${CURRENT_EXT_SOLR_REQUIREMENT}'."
}


# --- exec
if [ "$(is_matching)" = 'true' ]; then
  pin_matching_branch
fi
