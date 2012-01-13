# a small bash script that checks all tables in a given database and looks for MyMaria tables. 
# 

MYSQL="/usr/bin/mysql" 
MYSQLR="/usr/bin/mysqlrepair" 
MYSQLD="/usr/bin/mysqldump" 
MYSQL_HOST="sql002 -P 3330" 
MYSQL_USER="backup" 
MYSQL_PASSWD="restore" 
#MYSQL_SOCKET="/var/run/mysqld/mysqld3.sock" 
MYSQLCONNECT="$MYSQL -u $MYSQL_USER -p$MYSQL_PASSWD -h $MYSQL_HOST -A" 
MYSQLREPAIR="$MYSQLR -v -F -u $MYSQL_USER -p$MYSQL_PASSWD -B" 
MYSQLDUMP="$MYSQLD --skip-add-drop-table --disable-keys --skip-routines --set-charset --skip-triggers --no-create-info --single-transaction --extended-insert --quick --no-create-db -u ${MYSQL_USER} -p${MYSQL_PASSWD} -h $MYSQL_HOST -B" 
MYSQLROUTINES="$MYSQLD --skip-add-drop-table --disable-keys --routines --set-charset --skip-triggers --no-create-info --single-transaction --extended-insert --quick --no-data --no-create-db -u ${MYSQL_USER} -p${MYSQL_PASSWD} -h $MYSQL_HOST -B" 
MYSQLSCHEMA="$MYSQLD --skip-add-drop-table --disable-keys --skip-routines --set-charset --skip-triggers --single-transaction --extended-insert --quick --no-data --no-create-db -u ${MYSQL_USER} -p${MYSQL_PASSWD} -h $MYSQL_HOST -B" 
MYSQLTRIGGERS="$MYSQLD --skip-add-drop-table --disable-keys --skip-routines --set-charset --triggers --no-create-info --single-transaction --extended-insert --quick --no-data --no-create-db -u ${MYSQL_USER} -p${MYSQL_PASSWD} -h $MYSQL_HOST -B" 
 
echo "# Checking DB " 
# Use MySQL 'SHOW DATABASES' 
# echo "$MYSQLCONNECT --batch -N -e 'show databases'" 
DATABASES="`$MYSQLCONNECT --batch -N -e "show databases"`" 
 
# select TABLE_SCHEMA,TABLE_TYPE, TABLE_NAME, ENGINE  from information_schema.TABLES where TABLE_TYPE!='SYSTEM VIEW' AND TABLE_SCHEMA!='mysql' ORDER BY 4;

echo "# Checking Tables " 
# Loop through each instance of MySQL and check all databases in that instance 
for DATABASE in $DATABASES 
do 
   TABLES="`$MYSQLCONNECT --batch -D $DATABASE -N -e "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DATABASE' AND TABLE_TYPE='BASE TABLE' AND ENGINE IN ('MARIA','Aria','MyIsam','InnoDB')"`" 
   #echo "$MYSQLCONNECT --batch -D $DATABASE -N -e 'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$DATABASE' AND TABLE_TYPE='BASE TABLE' AND ENGINE IN ('MARIA','Aria','MyIsam','InnoDB')'" 

   for TABLE in $TABLES  
   do 
      echo "echo $TABLE"
		# echo "Repairing Tables: $MYSQLREPAIR $DATABASE --tables $TABLE" 
      # REPAIR_TABLE=`$MYSQLREPAIR $DATABASE --tables $TABLE` 
		# echo $MYSQLREPAIR $DATABASE --tables $TABLE
		# sleep 5
		# echo "Backup up tables: $MYSQLDUMP $DATABASE --tables $TABLE" 
		DUMP="$MYSQLDUMP $DATABASE --tables $TABLE > data/${DATABASE}_${TABLE}_data.sql"
		ROUT="$MYSQLROUTINES $DATABASE --tables $TABLE > routines/${DATABASE}_${TABLE}_routines.sql"
		SCHM="$MYSQLSCHEMA $DATABASE --tables $TABLE > schema/${DATABASE}_${TABLE}_schema.sql"
		TRGG="$MYSQLTRIGGERS $DATABASE --tables $TABLE > triggers/${DATABASE}_${TABLE}_triggers.sql"

      echo $SCHM
      echo "gzip -r schema &"
      echo "sleep 1"
      echo $ROUT
      echo "gzip -r routines &"
      echo "sleep 1"
      echo $TRGG
      echo "gzip -r triggers &"
      echo "sleep 1"
      echo $DUMP
      echo "gzip -r data &"
      echo "sleep 5"
   done 
   echo "# Done with $DATABASE"
   echo "sleep 60"
done

