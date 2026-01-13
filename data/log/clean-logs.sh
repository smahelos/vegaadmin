#!/bin/bash
#
# Clear all logs in current directory
# https://linuxhint.com/bash_for_loop/
#

VERSION=v1.0

# Switch to current directory (needed if it runs as root via Cron)
# cd "$(dirname "$0")"

n=1
for filename in `ls *.log`
do
    echo "Clearing file No-$n : $filename"
    cat /dev/null > $filename
    ((n++))
done


exit 0
