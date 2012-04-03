#!/bin/bash

##########################################
#
# Work: ingests json files into mongo
#
# Dependencies:
# 1. json files at /var/lc_ingestion/data
#
##########################################

#####################################################
# Set up logging
#####################################################

date_time_stamp=`date +"%Y%m%d_%H%M%S"`
logfile=../../logs/managers/dpla_platform_ingest.manager.sh.${date_time_stamp}.log

echo > $logfile
echo "Begin" >> $logfile
date >> $logfile
echo >> $logfile

#####################################################
# Run mysql2json.php scripts for data sources
#####################################################

data_sources=( npr_org youtube_com biodiversitylibrary_org sfpl_org cdlib_org harvard_edu )
for i in "${data_sources[@]}"
do
	date >> $logfile
	echo "now processing $1" >> $logfile
	cd /var/lc_ingestion/data_sources/$i/scripts/tasks
	/var/lc_ingestion/data_sources/$i/scripts/tasks/mysql2json.php
done
	
#####################################################
# Import json outputs into mongo
#####################################################
	
cd /var/lc_ingestion/data	
for i in "${data_sources[@]}"
do
	date >> $logfile
	echo "now importing $1 into mongo" >> $logfile
	echo "now importing $1 into mongo"
	mongoimport --host localhost --db dpla --collection $i --file $i.json --upsert > /var/lc_ingestion/logs/managers/$i_mongo_import
done
	
echo "Finished" >> $logfile
date >> $logfile
echo >> $logfile
