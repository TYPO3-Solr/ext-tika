#!/usr/bin/env bash

TIKA_PID=`cat tika_pid`

kill $TIKA_PID
rm tika_pid
