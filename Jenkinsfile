// Jenkinsfile — DEV (image build + rollout)

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
      volumeMounts:
        - name: workspace-volume
          mountPath: /home/jenkins/agent

    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      imagePullPolicy: Always
      # keep alive and ensure /bin/sh exists
      command: ["/busybox/sh","-c"]
      args: ["ln -sf /busybox/sh /bin/sh || true; mkdir -p /home/jenkins/agent/workspace; sleep infinity"]
      tty: true
      env:
        - name: DOCKER_CONFIG
          value: /kaniko/.docker/
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
        - name: workspace-volume
          mountPath: /home/jenkins/agent

  volumes:
    - name: docker-config
      secret:
        secretName: dockerhub-json
        items:
          - key: .dockerconfigjson
            path: config.json
    - name: workspace-volume
      emptyDir: {}
"""
    }
  }

  parameters {
    choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
    string(name: 'NAMESPACE', defaultValue: 'gibbon-dev-deploy', description: 'K8s namespace')
  }

  environment {
    NS = "${params.NAMESPACE}"
    IMAGE_REPO = "docker.io/ntony3419/gibbon"
  }

  stages {

    stage('Checkout') {
      steps {
        git branch: 'gibbon-dev', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    stage('Build & Push Image') {
      steps {
        // Run the build from the kubectl container and exec into the Kaniko container
        container('kubectl') {
          sh '''#!/usr/bin/env bash
set -euo pipefail

: "${IMAGE_REPO:?IMAGE_REPO env is not set}"

# Jenkins puts this pod in the "jenkins" namespace by default
KANIKO_NS="jenkins"
KANIKO_POD="${HOSTNAME}"
KANIKO_CTR="kaniko"

# Compute tag using Jenkins-provided commit hash from the prior Checkout stage
GIT_SHORT="${GIT_COMMIT:-unknown}"
GIT_SHORT="${GIT_SHORT:0:7}"
TAG="git-${GIT_SHORT}-b${BUILD_NUMBER}"
IMG="$IMAGE_REPO:$TAG"

echo "[Build] $IMG"

# Ensure /bin/sh is available inside the kaniko container (paranoid, but safe)
kubectl -n "$KANIKO_NS" exec "$KANIKO_POD" -c "$KANIKO_CTR" -- /busybox/sh -lc 'ln -sf /busybox/sh /bin/sh || true'

# Run Kaniko inside the kaniko container
kubectl -n "$KANIKO_NS" exec "$KANIKO_POD" -c "$KANIKO_CTR" -- /busybox/sh -lc '
  set -eu
  /kaniko/executor \
    --context="$WORKSPACE" \
    --dockerfile="Dockerfile.gibbon" \
    --destination="'"$IMG"'" \
    --destination="'"$IMAGE_REPO:dev-latest"'" \
    --build-arg GIT_COMMIT="'"${GIT_COMMIT:-$GIT_SHORT}"'" \
    --build-arg I18N_COMMIT=refs/heads/main \
    --cache=true \
    --cache-repo="'"$IMAGE_REPO-cache"'"
'

echo "$TAG" > image-tag.txt
"""
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
IMG="$IMAGE_REPO:$TAG"

echo "[0] Ensure namespace exists..."
kubectl create ns "$NS" --dry-run=client -o yaml | kubectl apply -f -

echo "[1] Secret for DB creds (DEV)"
kubectl apply -n "$NS" -f k8s/gibbon-mysql-secret.yaml

echo "[2] MySQL stack..."
kubectl apply -n "$NS" -f k8s/gibbon-mysql-deployment.yaml

echo "[2.0] Wait for MySQL PVC..."
for i in $(seq 1 90); do
  phase=$(kubectl -n "$NS" get pvc gibbon-dev-mysql-pvc -o jsonpath='{.status.phase}' 2>/dev/null || true)
  [ "$phase" = "Bound" ] && break
  sleep 2
done
kubectl -n "$NS" get pvc gibbon-dev-mysql-pvc
[ "$(kubectl -n "$NS" get pvc gibbon-dev-mysql-pvc -o jsonpath='{.status.phase}')" = "Bound" ]

echo "[2.1] Wait for MySQL rollout..."
kubectl -n "$NS" rollout status deploy/gibbon-dev-mysql --timeout=240s

echo "[3] App stack..."
kubectl -n "$NS" apply -f k8s/gibbon-deployment.yaml

# Be generous on first rollout
kubectl -n "$NS" patch deploy gibbon-dev-app -p '{"spec":{"progressDeadlineSeconds":600}}' >/dev/null 2>&1 || true

echo "[3.1] Set freshly built image tag..."
kubectl -n "$NS" set image deploy/gibbon-dev-app gibbon="$IMG"

# Keep init container in lock-step with app image if present
if kubectl -n "$NS" get deploy gibbon-dev-app -o jsonpath='{..initContainers[*].name}' 2>/dev/null | grep -q 'init-seed-i18n'; then
  kubectl -n "$NS" set image deploy/gibbon-dev-app init-seed-i18n="$IMG"
fi

echo "[4] Wait for app rollout..."
kubectl -n "$NS" rollout status deploy/gibbon-dev-app --timeout=600s

echo "[4.1] Smoke: i18n + custom file (newest pod)..."
APP_POD=$(kubectl -n "$NS" get pod -l app=gibbon-dev -o jsonpath='{range .items[*]}{.metadata.creationTimestamp}{"\\t"}{.metadata.name}{"\\n"}{end}' | sort | tail -n1 | cut -f2)
kubectl -n "$NS" exec "$APP_POD" -c gibbon -- sh -lc 'ls -ld /var/www/html/gibbon/i18n || true'
kubectl -n "$NS" exec "$APP_POD" -c gibbon -- sh -lc 'ls -l /var/www/html/gibbon/finance_report.php || echo "finance_report.php missing"'

echo "[5] Ingress"
kubectl -n "$NS" apply -f k8s/gibbon-ingress.yaml

echo "==== DEV Deployed with $IMG ===="
kubectl -n "$NS" get all
kubectl -n "$NS" get pvc
'''
          }
        }
      }
    }
  }
}
