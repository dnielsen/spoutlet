varnishlog | grep --line-buffered Backend_health | awk '{ $1=""; $2=""; $3=""; print }'
