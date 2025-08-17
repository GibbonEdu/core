// Jenkinsfile — DEV

// This parameters and enviroment can be used later for demo and production rollout
parameters {
  choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
  string(name: 'NAMESPACE', defaultValue: 'gibbon-dev-deploy', description: 'K8s namespace')
}
environment {
  NS = "${params.NAMESPACE}"
}

pipeline {
  agent {
    kubernetes {
      defaultContainer 'kubectl'
      yaml """
apiVersion: v1
kind: Pod
spec:
  nodeSelector:
    kubernetes.io/hostname: k8s-jenkin
  tolerations:
    - key: "dedicated"
      operator: "Equal"
      value: "jenkin"
      effect: "NoSchedule"
  containers:
    - name: kubectl
      image: ntony3419/k8s-agent:1.3
      imagePullPolicy: Always
      command: ["/bin/bash","-c"]
      args: ["sleep infinity"]
      tty: true
"""
    }
  }

  stages {
    stage('Checkout') {
      steps {
        git branch: 'gibbon-dev', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    stage('Setup Staging Certificate') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
              echo "==== ⚙️ Ensure Let's Encrypt Staging ClusterIssuer ===="
              cat <<EOF | kubectl apply -f -
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-staging
spec:
  acme:
    email: ntony3419@email.com
    server: https://acme-staging-v02.api.letsencrypt.org/directory
    privateKeySecretRef:
      name: letsencrypt-staging
    solvers:
      - http01:
          ingress:
            class: nginx
EOF
            '''
          }
        }
      }
    }

    stage('Deploy Gibbon DEV') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''#!/usr/bin/env bash
set -euo pipefail
NS=gibbon-dev-deploy

echo "[0] Ensure namespace exists..."
kubectl create ns "$NS" --dry-run=client -o yaml | kubectl apply -f -

echo "[1] Secret for DB creds (DEV)"
kubectl apply -n "$NS" -f k8s/gibbon-mysql-secret.yaml

echo "[2] MySQL stack (Deployment + PV + PVC + Service + GRANT Job)..."
kubectl apply -f k8s/gibbon-mysql-deployment.yaml

echo "[2.0] Wait for MySQL PVC to be Bound..."
for i in $(seq 1 90); do
  phase=$(kubectl get pvc gibbon-dev-mysql-pvc -n "$NS" -o jsonpath='{.status.phase}' 2>/dev/null || true)
  [ "$phase" = "Bound" ] && break
  sleep 2
done
kubectl get pvc gibbon-dev-mysql-pvc -n "$NS"
if [ "$(kubectl get pvc gibbon-dev-mysql-pvc -n "$NS" -o jsonpath='{.status.phase}')" != "Bound" ]; then
  echo "[ERR] MySQL PVC did not bind in time"; kubectl -n "$NS" describe pvc gibbon-dev-mysql-pvc; exit 1
fi

echo "[2.1] Wait for MySQL rollout..."
if ! kubectl rollout status deployment gibbon-dev-mysql -n "$NS" --timeout=240s; then
  echo "[ERR] MySQL deployment not ready. Showing events:"
  kubectl -n "$NS" describe deploy gibbon-dev-mysql || true
  kubectl -n "$NS" get pods -l app=gibbon-dev-mysql -o wide || true
  kubectl -n "$NS" describe pods -l app=gibbon-dev-mysql || true
  exit 1
fi


echo "[2.2] Extra wait to accept connections..."
kubectl -n "$NS" exec deploy/gibbon-dev-mysql -- sh -lc '
  for i in $(seq 1 60); do
    mysqladmin -uroot -p"$MYSQL_ROOT_PASSWORD" ping >/dev/null 2>&1 && exit 0
    sleep 2
  done
  echo "mysql not answering" >&2; exit 1
'

echo "[3] Gibbon app stack (Deployment + PV + PVC + Service)..."
kubectl apply -f k8s/gibbon-deployment.yaml

echo "[info] waiting for gibbon-dev-uploads-pvc to be Bound..."
for i in $(seq 1 60); do
  phase=$(kubectl get pvc gibbon-dev-uploads-pvc -n "$NS" -o jsonpath='{.status.phase}' 2>/dev/null || true)
  [ "$phase" = "Bound" ] && break
  sleep 2
done
kubectl get pvc gibbon-dev-uploads-pvc -n "$NS" || true

echo "[4] Wait for gibbon-dev-app rollout..."
kubectl rollout status deployment gibbon-dev-app -n "$NS" --timeout=300s

echo "[4.1] Smoke check: locale is mounted and vi works..."
APP_POD=$(kubectl -n "$NS" get pod -l app=gibbon-dev -o jsonpath='{.items[0].metadata.name}')
kubectl -n "$NS" exec "$APP_POD" -c gibbon -- sh -lc '
  set -e
  ls -l /var/www/html/gibbon/resources/locale/vi_VN/LC_MESSAGES/gibbon.mo
  php -r "putenv(\"LANG=vi_VN.UTF-8\"); setlocale(LC_ALL,\"vi_VN.UTF-8\"); bindtextdomain(\"gibbon\",\"/var/www/html/gibbon/resources/locale\"); textdomain(\"gibbon\"); echo gettext(\"Home\"), PHP_EOL;"
'


echo "[5] Ingress (DEV)"
kubectl apply -f k8s/gibbon-ingress.yaml

echo "==== DEV Deployed ===="
kubectl get all -n "$NS"
kubectl get pv
kubectl get pvc -n "$NS"
'''
          }
        }
      }
    }
  }
}
