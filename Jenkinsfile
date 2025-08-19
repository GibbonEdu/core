// Jenkinsfile — DEV (Option A: image build + rollout)

parameters {
  choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
  string(name: 'NAMESPACE', defaultValue: 'gibbon-dev-deploy', description: 'K8s namespace')
}
environment {
  NS = "${params.NAMESPACE}"
  IMAGE_REPO = "hub.docker.com/repository/docker/ntony3419/gibbon"   // <-- registry path
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
    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      imagePullPolicy: Always
      command: ["/busybox/sh","-c"]
      args: ["sleep infinity"]   # keep container alive
      env:
        - name: DOCKER_CONFIG
          value: /kaniko/.docker/
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
  volumes:
    - name: docker-config
      secret:
        secretName: dockerhub-json
        items:
          - key: .dockerconfigjson
            path: config.json
"""
    }
  }

  stages {

    stage('Checkout') {
      steps {
        git branch: 'gibbon-dev', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    stage('Build & Push Image') {
      steps {
        container('kaniko') {
          sh '''
            set -eu
            GIT_SHA="$(echo "${GIT_COMMIT:-unknown}" | cut -c1-7)"
            TAG="git-${GIT_SHA}-b${BUILD_NUMBER}"

            echo "[Build] ${IMAGE_REPO}:${TAG}"
            /kaniko/executor \
              --context="${WORKSPACE}" \
              --dockerfile="Dockerfile.gibbon" \
              --destination="${IMAGE_REPO}:${TAG}" \
              --destination="${IMAGE_REPO}:dev-latest" \
              --build-arg GIT_COMMIT="${GIT_COMMIT:-unknown}" \
              --build-arg I18N_COMMIT=refs/heads/main \
              --cache=true \
              --cache-repo="${IMAGE_REPO}-cache"

            echo "${TAG}" > image-tag.txt
          '''
        }
      }
    }

    stage('Setup Staging Certificate') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
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
NS="${NS}"

TAG="$(cat image-tag.txt)"
echo "[0] Ensure namespace exists..."
kubectl create ns "$NS" --dry-run=client -o yaml | kubectl apply -f -

echo "[1] Secret for DB creds (DEV)"
kubectl apply -n "$NS" -f k8s/gibbon-mysql-secret.yaml

echo "[2] MySQL stack..."
kubectl apply -f k8s/gibbon-mysql-deployment.yaml

echo "[2.0] Wait for MySQL PVC..."
for i in $(seq 1 90); do
  phase=$(kubectl get pvc gibbon-dev-mysql-pvc -n "$NS" -o jsonpath='{.status.phase}' 2>/dev/null || true)
  [ "$phase" = "Bound" ] && break
  sleep 2
done
kubectl get pvc gibbon-dev-mysql-pvc -n "$NS"
[ "$(kubectl get pvc gibbon-dev-mysql-pvc -n "$NS" -o jsonpath='{.status.phase}')" = "Bound" ]

echo "[2.1] Wait for MySQL rollout..."
kubectl rollout status deployment gibbon-dev-mysql -n "$NS" --timeout=240s

echo "[3] App stack..."
kubectl apply -f k8s/gibbon-deployment.yaml

echo "[3.1] Set freshly built image tag..."
kubectl -n "$NS" set image deploy/gibbon-dev-app gibbon="${IMAGE_REPO}:${TAG}"

echo "[4] Wait for app rollout..."
kubectl rollout status deployment gibbon-dev-app -n "$NS" --timeout=300s

echo "[4.1] Smoke: i18n + custom file..."
APP_POD=$(kubectl -n "$NS" get pod -l app=gibbon-dev -o jsonpath='{.items[0].metadata.name}')
kubectl -n "$NS" exec "$APP_POD" -c gibbon -- sh -lc 'ls -l /var/www/html/gibbon/i18n || true'
kubectl -n "$NS" exec "$APP_POD" -c gibbon -- sh -lc 'ls -l /var/www/html/gibbon/finance_report.php || echo "finance_report.php missing"'

echo "[5] Ingress"
kubectl apply -f k8s/gibbon-ingress.yaml

echo "==== DEV Deployed with ${IMAGE_REPO}:${TAG} ===="
kubectl get all -n "$NS"
kubectl get pvc -n "$NS"
'''
          }
        }
      }
    }
  }
}
