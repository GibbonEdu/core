- Always use Staging for deployment. after all is ready then swithc to production SSL

Create Docker secrete

kubectl -n $NS create secret docker-registry dockerhub-cred \
  --docker-server=https://index.docker.io/v1/ \
  --docker-username='<your_dockerhub_user>' \
  --docker-password='<your_pat_or_password>' \
  --docker-email='<you@example.com>'


  # Patch deployment to use it
kubectl -n $NS patch deployment gibbon-dev-app --type='json' \
  -p='[{"op":"add","path":"/spec/template/spec/imagePullSecrets","value":[{"name":"dockerhub-cred"}]}]'