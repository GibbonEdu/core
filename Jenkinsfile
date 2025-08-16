// Jenkinsfile — DEV
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
echo "[2.1] Wait for MySQL rollout..."
kubectl rollout status deployment gibbon-dev-mysql -n "$NS" --timeout=180s || true
echo "[2.2] Extra wait to accept connections..."
sleep 15

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
    stage('Persist Gibbon locales (DEV)') {
  steps {
    container('kubectl') {
      withKubeConfig([credentialsId: "${KUBECONFIG_CRED}"]) {
        sh '''
          set -euo pipefail
          NS="gibbon-dev-deploy"
          DEPLOY="gibbon-dev-app"

          # Grab the current app image so the init can copy from the image filesystem
          APP_IMAGE=$(kubectl -n "$NS" get deploy "$DEPLOY" -o jsonpath='{.spec.template.spec.containers[?(@.name=="gibbon")].image}')

          # Strategic-merge apply: add/refresh an initContainer to seed the PVC once,
          # and mount the PVC "uploads" subPath=locale onto resources/locale.
          cat <<YAML | kubectl -n "$NS" apply -f -
          apiVersion: apps/v1
          kind: Deployment
          metadata:
            name: ${DEPLOY}
          spec:
            template:
              spec:
                initContainers:
                - name: init-gibbon-locale
                  image: ${APP_IMAGE}
                  command: ["/bin/sh","-lc"]
                  args:
                    - |
                      set -e
                      mkdir -p /pvc/locale
                      # Seed once: if core en_GB mo is missing, copy all shipped locales
                      if [ ! -f /pvc/locale/en_GB/LC_MESSAGES/gibbon.mo ]; then
                        cp -a /var/www/html/gibbon/resources/locale/. /pvc/locale/
                      fi
                      chown -R www-data:www-data /pvc/locale
                  volumeMounts:
                    # Reuse the existing uploads PVC but mount it at /pvc for seeding
                    - name: uploads
                      mountPath: /pvc

                containers:
                - name: gibbon
                  volumeMounts:
                    # Persist the whole resources/locale from PVC subPath "locale"
                    - name: uploads
                      mountPath: /var/www/html/gibbon/resources/locale
                      subPath: locale
          YAML

          # Rollout wait for visibility
          kubectl -n "$NS" rollout status deploy/"$DEPLOY" --timeout=5m
        '''
      }
    }
  }
}
  }
}
