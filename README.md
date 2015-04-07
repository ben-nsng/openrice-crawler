# openrice-crawler
It is a crawler to visit the openrice website and extract the restaurant, user and label information and their relationship.

To use this crawler, please import the schema to your database server first. Then, you need to insert some of the restuarants' url into the table restaurant manually. The script can then be run and new information and relationship will be inserted to the database. After running the script first time, it will be grow and run the script continuously until no new information is found.
