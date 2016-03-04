import redis
import MySQLdb
import sys
import os
import logging
import MySQLdb.cursors
import json
import requests

logger = logging.getLogger('MySQLdb')
hdlr = logging.FileHandler('caching.log')
formatter = logging.Formatter('%(asctime)s %(levelname)s %(message)s')
hdlr.setFormatter(formatter)
logger.addHandler(hdlr)
logger.setLevel(logging.DEBUG)

host = "localhost"
user = "eve"
password= ""
db = "eve_sde"
## Setup for DB connection
try:
    eve_sde = MySQLdb.connect("localhost","username","password","dbname",cursorclass=MySQLdb.cursors.DictCursor)
    cursor = eve_sde.cursor()
    logger.info("DB setup completed")
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)
## Setup for redis
try:
    redis = redis.StrictRedis(host="localhost",password="password")
    logger.info("Redis setup completed")
except BaseException as e:
    logger.exception(e)
    raise SystemExit(1)

    ##helpful functions
def getByAttrVal(objlist,attr,val):
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

request_regions = requests.get("https://public-crest.eveonline.com/regions/")
logger.debug(request_regions)
regions_list = request_regions.json()['items']
logger.debug(regions_list)
## get typeid form sde and pull data from crest then write prices into the redis
cursor.execute("SELECT typeID FROM types_crawl_crest;")
results = cursor.fetchall()
#logger.debug(results)
pipe = redis.pipeline()
exempt = []
for result in results:
    i_type = result['typeID']
    for region in regions_list:
        if region['id'] in exempt:
            continue
        request_region = requests.get("https://public-crest.eveonline.com/regions/{:d}/".format(region['id']))
        logger.debug(request_region)
        reg = request_region.json()['marketSellOrders']
        logger.debug(result)
        r_url = reg['href']
        t_url = "?type=https://public-crest.eveonline.com/types/{:d}/".format(result['typeID'])
        request_url = r_url + t_url
        logger.debug(request_url)
        marketdata_req = requests.get(request_url)
        logger.debug(marketdata_req)
        highest = 0
        marketdata = marketdata_req.json()
        logger.debug(marketdata)
        if marketdata['totalCount'] == 0:
            exempt.append(region['id'])
            continue
        market_items = marketdata['items']
        logger.debug(market_items)
        for item in market_items:
            logger.debug(type(item))
            if item['price'] > highest:
                highest = item['price']
        redis_dict = {region['id_str']:highest}
        logger.debug("redis_dict: {dict}".format(dict = redis_dict))
        redis.hmset(i_type, redis_dict)
        redis.expire(i_type, 600)
        logger.info("Finished caching for {i_type} in {region}".format(i_type = i_type, region = region['id']))
    logger.info("Finished caching for all regions for the i_type: {:d}".format(i_type))
pipe.execute()
logger.info("Finished Caching")
