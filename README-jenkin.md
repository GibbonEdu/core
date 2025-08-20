# Namespace must match where the Jenkins agent pods run (yours is "jenkins")
kubectl -n jenkins delete secret regcred-kaniko --ignore-not-found

kubectl -n jenkins create secret docker-registry regcred-kaniko \
  --docker-server=https://index.docker.io/v1/ \
  --docker-username=YOUR_DOCKERHUB_USERNAME \
  --docker-password=YOUR_DOCKERHUB_PAT_OR_PASSWORD \
  --docker-email=you@example.com
