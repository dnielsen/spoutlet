#!/bin/bash
pandoc --smart --toc --standalone --include-in-header=bootstrap.min.css.include --highlight-style=pygments --to=html5 --output="AWA - Migration - API Information.html" "AWA - Migration - API Information.markdown"
pandoc --smart --toc --standalone --include-in-header=bootstrap.min.css.include --highlight-style=pygments --to=html5 --output="AWA - Migration - Avatar Information.html" "AWA - Migration - Avatar Information.markdown"
