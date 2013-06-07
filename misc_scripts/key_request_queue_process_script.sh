#!/bin/bash

exec 200<$0
flock -n 200 || exit 1

cd ..

for i in {1..15}
do
    ./app/console pd:keyRequestQueue:process -e prod
done
