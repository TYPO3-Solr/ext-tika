#!/usr/bin/env bash

TIKA_PID=`cat tika_pid`

kill $TIKA_PID
rm -Rf \
  .Build \
  composer.lock  \
  tika_pid  \
  tika_log

# Restore composer.json
git checkout composer.json