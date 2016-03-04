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
logger.setLevel(logging.DEBUG)

host = ""
user = ""
password= ""
db = ""
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
exclude = [354496,350001]
##Type crawling
types = []
try:
    cursor.execute("SELECT typeID, typeName, marketGroupID FROM invTypes WHERE marketGroupID IS NOT NULL;")
    logger.info("TypeID Query went well")
    results = cursor.fetchall()
    for result in results:
        try:
            if result['marketGroupID'] in exclude:
                continue
            marketIDquery="SELECT * FROM invMarketGroups WHERE marketGroupID = {:d} AND parentGroupID IS NOT NULL;".format(result['marketGroupID'])
            logger.info(marketIDquery)
            cursor.execute(marketIDquery)
            groupcheck_first = cursor.fetchall()
            #logger.info("groupcheck_first")
            #logger.info(groupcheck_first)
            #print(groupcheck_first)
            groupcheck_first_item = groupcheck_first[0]
            logger.debug("groupcheck_first_item")
            logger.debug(groupcheck_first_item)
            marketGroupIDs = groupcheck_first_item['parentGroupID']
            while marketGroupIDs != None:
                logger.info("entered While loop")
                query="SELECT * FROM invMarketGroups WHERE marketGroupID = {:d};".format(marketGroupIDs)
                logger.debug(query)
                cursor.execute(query)
                groupchecks = cursor.fetchall()
                #print(groupchecks)
                groupcheck_item = groupchecks[0]
                marketGroupIDs = groupcheck_item['parentGroupID']
                logger.debug("marketGroupIDs: {0};".format(marketGroupIDs))
                #logger.info("parentGroupID: {0};".format(groupcheck_item['parentGroupID']))
                if groupcheck_item['marketGroupID'] in groupids:
                    types.append({'typeID':result['typeID'],'typeName':result['typeName'],'marketGroupID':result['marketGroupID']})
        except IndexError:
            exclude.append(marketGroupIDs)
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)
## Dont know if i need this as it is here anymore
try:

    def insert_array(insert_item):
        typeid = "{}".format(insert_item["typeID"])
        marketGroupID = "{}".format(insert_item['marketGroupID'])
        insert_command="INSERT INTO types_crawl_crest(typeID,typeName,marketGroupID) VALUES ("+typeid+",\""+insert_item['typeName']+'\",'+marketGroupID+');'
        logger.debug("insert_command")
        logger.debug(insert_command)
        cursor.execute(insert_command)
        return
    for item in types:
        logger.debug("l89: {item}".format(item = item))
        insert_array(item)

    eve_sde.commit()
    logger.info("Insert was successful!")
except BaseException as e:
    logger.error("{!r}".format(e))
    raise SystemExit(1)

eve_sde.close()
logger.info("We did it! Finished setup without errors.")
print("We did it! Finished setup without errors.")
raise SystemExit(0)
