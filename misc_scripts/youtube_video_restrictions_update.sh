#!/bin/bash

exec 200<$0
flock -n 200 || exit 1

cd ..
./app/console pd:videos:updateRestrictions -e prod
