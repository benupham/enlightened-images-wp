1. React app loads
2. React app requests from custom endpoint page=0 or null and sends timestamp
3. PHP queries all un-annotated images uploaded as of that timestamp, gets total, saves as transient
4. PHP queries all images as of that timestamp.
5. PHP sets page to 1, errors to 0, saves errors as transient.
6. PHP combines required data and sends to REST API
7. Custom endpoint returns:
   1. Total un-annotated images 
   2. Total images
   3. Nextpage=1
8. React displays total images, total un-annotated (same), total errors (0) and progress bar.
9. User clicks bulk annotate
10. React app requests from custom endpoint page=1
11. PHP queries for first page of un-annotated images ordered by most recent after the start time of the bulk task (or from transient)
12. PHP loops through first page of images
    1.  creates Image object class
    2.  gets alt text from Microsoft
    3.  wait 3 seconds because of Microsoft rate limiting
    4.  gets other data from Google
    5.  saves alt text to attachment
    6.  saves other data as custom meta
    7.  adds alt and other data, or error(s) to response array
13. PHP subtracts page 1 amount from total un-annotated and adds to response
14. PHP adds errors to error total and adds to response
15. Custom endpoint returns:
   1. Total remaining un-annotated images (recalculated)
   2. Total errors
   3. First 10 images annotation data (or with error)
   4. Nextpage=2
16. React displays results of first 10 in table
   5. thumbnail
   6. filename linked to attachment page
   7. alt text
   8. other annotation text
17. React requests page 2
18. PHP reaches last page
19. PHP sends last response
20. PHP clears transients
21. Endpoint returns page 2 data. Page 2 is last page:
   9.  Total un-annotated (0)
   10. Total errors
   11. Freshly annotated image data set
   12. Nextpage=false
22. React updates stats, progress bar
23. React displays last images data 
24. React stops bulk process. 