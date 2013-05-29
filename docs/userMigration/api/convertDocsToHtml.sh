#!/bin/bash
pandoc --smart --toc --standalone --include-in-header=bootstrap.min.css.include --highlight-style=pygments --to=html5 --output="userApi.html" "userApi.markdown"
pandoc --smart --toc --standalone --include-in-header=bootstrap.min.css.include --highlight-style=pygments --to=html5 --output="userAvatar.html" "userAvatar.markdown"
