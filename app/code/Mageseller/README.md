**Import Products Conditions**
1) Make all products unavailable 1st including Synnex
2) Import all products in live site but don't import description,
   if they are already there (don't overwrite description) -> done
3) New products import + making unavailable deleted or not available products. -> done
4) Brand name will have mapping field, where we can write multiple
   alternate names with comma to assign to same brand. ->done
5) With supplier configuration, please add new field,
   which allow to ad backorder field name with comma exmaple: on-call, back order. -> done
6) Then set product status to Pre-Order or Back Order on Devices.com.au 
   website front-end show status with pre-order and following Notes on product details page. (Example: https://devices.com.au/fuji-xerox-c525a-cyan-toner-cartridge.html)
   -> done
7) Cron job time: Data Download time
   
   **XIT:** 10pm

   **DD:** 12am

   **Leaders:** 1am

   **IM:** 3PM

   **Stock + Price:** all at 5AM every day and DD every 3 hours
   
    00 22 * * *  php -dmemory_limit=3G  bin\magento mageseller_xitimport:xitproductimport
    00 23 * * *  php -dmemory_limit=3G bin\magento mageseller_xitimport:xitproductimageimport
    
    00 00 * * *  php -dmemory_limit=3G  bin\magento mageseller_dickerdataimport:dickerdataproductimport
    
    00 01 * * *  php -dmemory_limit=3G  bin\magento mageseller_leadersystemsimport:leadersystemsproductimport
    00 02 * * *  php -dmemory_limit=3G bin\magento mageseller_leadersystemsimport:leadersystemsproductimageimport

    00 15 * * *  php -dmemory_limit=3G  bin\magento mageseller_ingrammicroimport:ingrammicroproductimport
    00 17 * * *  php -dmemory_limit=3G bin\magento mageseller_ingrammicroimport:ingrammicroproductimageimport

    00 05 * * *  php -dmemory_limit=3G  bin\magento mageseller_xitimport:xitproductimport
    00 05 * * *  php -dmemory_limit=3G  bin\magento mageseller_dickerdataimport:dickerdataproductimport
    00 05 * * *  php -dmemory_limit=3G  bin\magento mageseller_leadersystemsimport:leadersystemsproductimport
    00 05 * * *  php -dmemory_limit=3G  bin\magento mageseller_ingrammicroimport:ingrammicroproductimport
    
8) Assign Supplier Category Ids to product while import. -> done
9) Use category as attribute while import -> done
10) Use 4th category as attribute option in Xit and 3rd category as attribute option in Dickerdata.
11) While importing price need price margin 
12) Add lowest price if sku is from different supplier and update supplier.
13) For importing large products :  

    [mysqld]
    sql_mode=STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION
    innodb_data_file_path=ibdata1:10M:autoextend
    tmp_table_size=4G
    max_heap_table_size=4G

    --------------------------------------------------------------
    innodb_data_file_path = ibdata1:10M:autoextend:max:512M
    max_allowed_packet = 5000M
    tmp_table_size=2048M
    max_heap_table_size=2048M


14) Please see attached Pivot tables for XIT and DD.
    for XIT, please use 3 level and 4th is attribute
    for DD, please use only 2 level and 3rd is attribute

15) also, please make sure we don't have duplicate brand name
    
    once supplier has HP and other one has Hewlett Packard so we need to make sure to use it as ONE brand name


UPDATE `catalog_product_entity_varchar` SET `value`=LCASE(`value`) WHERE `attribute_id` IN (87,88,89) AND `entity_id` in (SELECT `entity_id` FROM `catalog_product_entity_media_gallery_value_to_entity` WHERE `value_id` IN (SELECT `value_id` FROM `catalog_product_entity_media_gallery` WHERE `value_id` > '110364844' AND `value` in (SELECT `value` FROM `catalog_product_entity_media_gallery` WHERE `value_id` < '110364844')));


SELECT * FROM `catalog_product_entity_varchar` WHERE `attribute_id` IN (87,88,89) AND `entity_id` in (SELECT `entity_id` FROM `catalog_product_entity_media_gallery_value_to_entity` WHERE `value_id` IN (SELECT `value_id` FROM `catalog_product_entity_media_gallery` WHERE `value_id` > '110364844' AND `value` in (SELECT `value` FROM `catalog_product_entity_media_gallery` WHERE `value_id` < '110364844'))) LIMIT 50

To Find Duplicate brands :
SELECT COUNT(*) c, V.`value` FROM eav_attribute_option O INNER JOIN eav_attribute_option_value V ON V.option_id =
O.option_id WHERE O.`attribute_id` = 155 AND V.store_id = 0 GROUP BY V.`value` HAVING c > 1;
