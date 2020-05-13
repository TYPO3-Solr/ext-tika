#!/usr/bin/env bash

TIKA_PID=`cat tika_pid`

kill $TIKA_PID
rm -rf .Build composer.lock tika_pid
git checkout composer.json

