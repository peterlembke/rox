# Clear cache

If you run keydb-cli you get

    Flushing Redis Cache .. exit status 127
    OCI runtime exec failed: exec failed: unable to start container process: exec: "redis-cli": executable file not found in $PATH: unknown

To fix this, run:

    rox shell cache root
    cd /usr/local/bin
    ln -s /usr/local/bin/keydb-cli /usr/local/bin/redis-cli