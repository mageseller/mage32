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
   
8) Assign Supplier Category Ids to product while import. -> done
9) Use category as attribute while import -> done
10) Use 4th category as attribute option in Xit and 3rd category as attribute option in Dickerdata.
11) While importing price need price margin 
12) Add lowest price if sku is from different supplier and update supplier.

