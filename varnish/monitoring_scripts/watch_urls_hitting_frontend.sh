varnishlog | grep --line-buffered RxURL | awk '{ print $4 }'
