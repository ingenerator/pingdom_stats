# Pingdom Stats

A little script to pull down uptime and response time for a selection of pingdom checks, aggregate them
and build them into a JSON to power the chart on our homepage.

This allows us to publish aggregate client site information without exposing names, status, etc of the
individual systems.

To use it, define the required environment variables (see the script), install composer dependencies and
run `php import_pingdom_uptimes.php`. The script will: 

* fetch the previously generated JSON from S3
* strip any old statistics
* grab any more recent check data from Pingdom
* upload the new statistics back to S3

This is designed to run on an hourly cron.
