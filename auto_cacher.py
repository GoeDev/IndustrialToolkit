import pycrest
import pyredis
import MySQLdb
import sys
import os
import logging

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
## Setup for DB connection
try:
    eve_sde = MySQLdb.connect("localhost","eve","","eve_sde")
    cursor = eve_sde.cursor()
    logger.info("DB setup completed")
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)
## Setup for crest
try:
    crest = pycrest.EVE()
    crest()
    logger.info("CREST setup completed")
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)
## Setup for redis
try:
    redis = Client(host="localhost",password="Fuehrerschein123")
    logger.info("Redis setup completed")
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)

    ##helpful functions
def getByAtrrVal(objlist,attr,val):
    ## Searches list of dicts for a dict with dict[attr] == val
    matches = [getattr(obj, attr) == val for obj in objlist]
    index = matches.index(True)  # find first match, raise ValueError if not found
    return objlist[index]

def getAllItems(page):
    ## Fetch data from all pages
    ret = page().items
    while hasattr(page(), 'next'):
        page = page().next()
        ret.extend(page().items)
    return ret
logger.info("Setup complete")
## Setup extend

regions = crest.regions().items
## get typeid form sde and pull data from crest then write prices into the redis
cursor.execute("SELECT typeIds FROM types_crawl_crest;")
results = cursor.fetchall()
for result in results:
    for region in regions:
        type = "https://public-crest.eveonline.com/types/"+result['typeID']
        response = getAllItems(region['marketSellOrders'](type=type)).items
        for item in response:
            if item['p'] > highest:
                highest = item['price']
        redis_dict = {region['id_str']:highest}
        logger.debug("redis_dict"+redis_dict)
        redis.hmset(type, redis_dict)
        redis.expire(type, 600)
        logger.info("Finished caching for "+type+"in"+region['id'])
    logger.info("Finished caching for all regions for the type:"+type)
