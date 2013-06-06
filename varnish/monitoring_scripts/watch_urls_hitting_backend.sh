varnishlog -b | grep --line-buffered TxURL | awk '{ print $4 }'
