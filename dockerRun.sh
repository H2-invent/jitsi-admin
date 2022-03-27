echo We check for initial startup:
FILE=installDocker.sh
if test -f "$FILE"; then
    echo "this is the first run"
    /bin/bash installDocker.sh
    rm installDocker.sh
fi

