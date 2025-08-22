



# Deployment 
- Always use Staging SSL for deployment. after all is ready then swithc to production SSL
- Ensure deploying node has minimum 8Gb ram to build and push
## Create Docker secret for deployment

kubectl -n $NS create secret docker-registry dockerhub-cred \
  --docker-server=https://index.docker.io/v1/ \
  --docker-username='<your_dockerhub_user>' \
  --docker-password='<your_pat_or_password>' \
  --docker-email='<you@example.com>'


  ### Patch deployment to use it if pods are running
kubectl -n $NS patch deployment gibbon-dev-app --type='json' \
  -p='[{"op":"add","path":"/spec/template/spec/imagePullSecrets","value":[{"name":"dockerhub-cred"}]}]'

## restore database 
- if need to create new database check another section about create new database 
### Steps

- from k8s control plane
- ensure file exist 
ls -lh /root/gibbon_2025_8_14.sql
set -eu
DB_HOST="gibbon-dev-mysql.gibbon-dev-deploy.svc.cluster.local"
DB_NAME="gibbon"
DB_USER="gibbonuser"
DB_PASS="gibbonpass123"
DB_PORT=3306

NS=gibbon-dev-deploy
MYSQL_POD=$(kubectl -n "$NS" get pod -l app=gibbon-dev-mysql \
  -o jsonpath='{.items[0].metadata.name}')
echo "MYSQL_POD=${MYSQL_POD}"
kubectl -n "$NS" exec -it "$MYSQL_POD" -c mysql -- bash -lc '
set -eu
mysql -h 127.0.0.1 -uroot -p"$MYSQL_ROOT_PASSWORD" -e "
  CREATE DATABASE IF NOT EXISTS \`gibbon\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS \"gibbonuser\"@\"%\" IDENTIFIED BY \"gibbonpass123\";
  ALTER USER \"gibbonuser\"@\"%\" IDENTIFIED BY \"gibbonpass123\";
  GRANT ALL PRIVILEGES ON \`gibbon\`.* TO \"gibbonuser\"@\"%\";
  FLUSH PRIVILEGES;
  SELECT user,host FROM mysql.user WHERE user=\"gibbonuser\";
"'


NS=gibbon-dev-deploy
APP_POD=$(kubectl -n "$NS" get pods -l app=gibbon-dev \
  -o jsonpath='{.items[?(@.status.phase=="Running")].metadata.name}' | awk "NR==1{print}")
kubectl -n "$NS" exec -it "$APP_POD" -c gibbon -- bash -lc '
DB_HOST="gibbon-dev-mysql.gibbon-dev-deploy.svc.cluster.local"
DB_NAME="gibbon"
DB_USER="gibbonuser"
DB_PASS="gibbonpass123"
DB_PORT=3306
MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e "SELECT 1;"
'

- restore dump
kubectl -n "$NS" cp /root/gibbon_2025_8_14.sql "$APP_POD":/root/gibbon_2025_8_14.sql -c gibbon


kubectl -n "$NS" exec -it "$APP_POD" -c gibbon -- bash -lc '
DB_HOST="gibbon-dev-mysql.gibbon-dev-deploy.svc.cluster.local"
DB_NAME="gibbon"
DB_USER="gibbonuser"
DB_PASS="gibbonpass123"
DB_PORT=3306
MYSQL_PWD="$DB_PASS" mysql --default-character-set=utf8mb4 \
  -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < /root/gibbon_2025_8_14.sql
MYSQL_PWD="$DB_PASS" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
  -e "SHOW TABLES; SELECT COUNT(*) AS people FROM gibbonPerson;"
'