varnishlog -b | grep --line-buffered BackendOpen | awk '{ print $4 }'
