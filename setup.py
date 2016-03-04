#!/usr/bin/python
## Installer for the auto_cacher.py
## Run once to setup on a system
## You will need an imported SDE db set up
## I hope this isn't hack level of python programming :D

import MySQLdb
import sys
import os
import logging
import MySQLdb.cursors


logger = logging.getLogger('MySQLdb')
hdlr = logging.FileHandler('setup.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.INFO)

host = "localhost"
user = "eve"
password= ""
db = "eve_sde"
try:
    eve_sde = MySQLdb.connect(host,user,password,db,cursorclass=MySQLdb.cursors.DictCursor)
    logger.info("Connect successful!")
except BaseException as e:
    logger.error("Fatal error: {!r}".format(e))
    raise SystemExit(1)

cursor = eve_sde.cursor()
## Creating table to have all types ready to crawling
try:
    sql = """CREATE TABLE IF NOT EXISTS types_crawl_crest (typeID int(11) NOT NULL DEFAULT 0, typeName varchar(100) DEFAULT NULL, marketGroupID int(11) NOT NULL DEFAULT 0, PRIMARY KEY(typeID));"""
    cursor.execute(sql)
    logger.info("Created table without errors.")
except BaseException as e:
    logger.error("Fatal Error: {!r}".format(e))
    raise SystemExit(1)

groupids = [2,4,9,11,24,157,475,477,1320,1849]
##Type crawling
types = []
try:
    cursor.execute("SELECT typeID, typeName, marketGroupID FROM invTypes WHERE marketGroupID IS NOT NULL;")
    logger.info("TypeID Query went well")
    results = cursor.fetchall()
    for result in results:
        marketIDquery="SELECT * FROM invMarketGroups WHERE marketGroupID = {:d};".format(result['marketGroupID'])
        logger.info(marketIDquery)
        cursor.execute(marketIDquery)
        groupcheck_first = cursor.fetchall()
        #logger.info("groupcheck_first")
        #logger.info(groupcheck_first)
        #print(groupcheck_first)
        groupcheck_first_item = groupcheck_first[0]
        #logger.info("groupcheck_first_item")
        #logger.info(groupcheck_first_item)
        marketGroupIDs = groupcheck_first_item['parentGroupID']
        while marketGroupIDs != None:
            logger.info("entered While loop")
            query="SELECT * FROM invMarketGroups WHERE marketGroupID = {:d};".format(marketGroupIDs)
            logger.info(query)
            cursor.execute(query)
            groupchecks = cursor.fetchall()
            #print(groupchecks)
            groupcheck_item = groupchecks[0]
            marketGroupIDs = groupcheck_item['parentGroupID']
            #logger.info("marketGroupIDs: {:d};".format(marketGroupIDs))
            #logger.info("parentGroupID: {:d};".format(groupcheck_item['parentGroupID']))
            if groupcheck_item['marketGroupID'] in groupids:
                types.append({'typeID':result['typeID'],'typeName':result['typeName'],'marketGroupID':result['marketGroupID']})
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)
## Dont know if i need this as it is here anymore
try:
    def insert_array(insert_item):
        insert_command='INSERT INTO types_crawl_crest(typeID,typeName,marketGroupID) VALUES ('+insert_item["typeID"]+','+insert_item['typeName']+','+insert_item['marketGroupID']+');'
        cursor.execute(insert_commands)
        return
    for item in types:
        insert_array(item)
        logger.info(item)
    logger.info("Insert was successful!")
except BaseException as e:
    logger.error("{!r}".format(e))
    raise SystemExit(1)

eve_sde.close()
Logger.info("We did it! Finished setup without errors.")
print("We did it! Finished setup without errors.")
raise SystemExit(0)
