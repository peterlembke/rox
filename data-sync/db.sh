#!/usr/bin/env bash

mongorestore --uri="mongodb://root:infohub@localhost:27017/aktivbo_analytics_cache_dev" --authenticationDatabase admin --authenticationMechanism SCRAM-SHA-256 --drop --preserveUUID /data/dumpdir/aktivbo_analytics_cache_dev-respondents/aktivbo_analytics_cache_dev/
mongorestore --uri="mongodb://root:infohub@localhost:27017/atlas_api" --authenticationDatabase admin --authenticationMechanism SCRAM-SHA-256 --drop --preserveUUID /data/dumpdir/atlas_api/atlas_api/