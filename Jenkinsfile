// Jenkinsfile — DEV (build inside Kaniko container, then rollout)

pipeline {
  agent {
    kubernetes {
      defaultContainer 'kubectl'
      yaml """
apiVersion: v1
kind: Pod
spec:
  securityContext:
    fsGroup: 10000
    fsGroupChangePolicy: OnRootMismatch

  # Ensure workspace perms so checkout succeeds
  initContainers:
    - name: fix-workspace-perms
      image: busybox:1.36
      command: ["/bin/sh","-c"]
      args:
        - >
          set -eux;
          mkdir -p /home/jenkins/agent;
          chown -R 10000:10000 /home/jenkins/agent;
          chmod -R g+rwX /home/jenkins/agent
      volumeMounts:
        - name: workspace-volume
          mountPath: /home/jenkins/agent

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
        - { name: workspace-volume, mountPath: /home/jenkins/agent }

    # Kaniko runs the build directly (no kubectl exec). We ensure /bin/sh exists.
    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      imagePullPolicy: Always
      command: ["/busybox/sh","-c"]
      args: ["ln -sf /busybox/sh /bin/sh || true; sleep infinity"]
      tty: true
      env:
        - { name: DOCKER_CONFIG, value: /kaniko/.docker/ }
      resources:
        requests:
          cpu: "500m"
          memory: "1Gi"
        limits:
          cpu: "2"
          memory: "3Gi"
      volumeMounts:
        - { name: docker-config, mountPath: /kaniko/.docker }
        - { name: workspace-volume, mountPath: /home/jenkins/agent }

    - name: jnlp
      image: jenkins/inbound-agent:3309.v27b_9314fd1a_4-1
      resources:
        requests: { cpu: "100m", memory: "256Mi" }
      volumeMounts:
        - { name: workspace-volume, mountPath: /home/jenkins/agent }

  volumes:
    - name: docker-config
      secret:
        secretName: dockerhub-json
        items:
          - { key: .dockerconfigjson, path: config.json }
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
      options { retry(2) }  // transient hiccups -> auto retry
      steps {
        container('kaniko') {
          // Busybox sh is the shell, keep it POSIX (no bash-isms)
          sh '''#!/bin/sh
set -eu

: "${IMAGE_REPO:?IMAGE_REPO env is not set}"

# Short git SHA from Jenkins env (no `git` call inside this container)
GIT_SHORT="$(echo "${GIT_COMMIT:-unknown}" | cut -c1-7)"
TAG="git-${GIT_SHORT}-b${BUILD_NUMBER}"
IMG="${IMAGE_REPO}:${TAG}"

echo "[Build] ${IMG} (context: ${WORKSPACE})"
/kaniko/executor \
  --context="${WORKSPACE}" \
  --dockerfile="Dockerfile.gibbon" \
  --destination="${IMG}" \
  --destination="${IMAGE_REPO}:dev-latest" \
  --build-arg GIT_COMMIT="${GIT_COMMIT:-${GIT_SHORT}}" \
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
            sh '''#!/bin/sh
set -eu
NS="${NS}"
TAG="$(cat image-tag.txt)"
IMG="${IMAGE_REPO}:${TAG}"

echo "[0] Ensure namespace exists..."
kubectl create ns "${NS}" --dry-run=client -o yaml | kubectl apply -f -

echo "[1] Secret for DB creds (DEV)"
kubectl apply -n "${NS}" -f k8s/gibbon-mysql-secret.yaml

echo "[2] MySQL stack..."
kubectl apply -n "${NS}" -f k8s/gibbon-mysql-deployment.yaml

echo "[2.0] Wait for MySQL PVC..."
for i in $(seq 1 90); do
  phase="$(kubectl -n "${NS}" get pvc gibbon-dev-mysql-pvc -o jsonpath='{.status.phase}' 2>/dev/null || true)"
  [ "${phase}" = "Bound" ] && break
  sleep 2
done
kubectl -n "${NS}" get pvc gibbon-dev-mysql-pvc
[ "$(kubectl -n "${NS}" get pvc gibbon-dev-mysql-pvc -o jsonpath='{.status.phase}')" = "Bound" ]

echo "[2.1] Wait for MySQL rollout..."
kubectl -n "${NS}" rollout status deploy/gibbon-dev-mysql --timeout=240s

echo "[3] App stack..."
kubectl -n "${NS}" apply -f k8s/gibbon-deployment.yaml
kubectl -n "${NS}" patch deploy gibbon-dev-app -p '{"spec":{"progressDeadlineSeconds":600}}' >/dev/null 2>&1 || true

echo "[3.1] Set freshly built image tag..."
kubectl -n "${NS}" set image deploy/gibbon-dev-app gibbon="${IMG}"
if kubectl -n "${NS}" get deploy gibbon-dev-app -o jsonpath='{..initContainers[*].name}' 2>/dev/null | grep -q 'init-seed-i18n'; then
  kubectl -n "${NS}" set image deploy/gibbon-dev-app init-seed-i18n="${IMG}"
fi

echo "[4] Wait for app rollout..."
kubectl -n "${NS}" rollout status deploy/gibbon-dev-app --timeout=600s

echo "[4.1] Smoke: i18n + custom file (newest pod)..."
APP_POD="$(kubectl -n "${NS}" get pod -l app=gibbon-dev -o jsonpath='{range .items[*]}{.metadata.creationTimestamp}{"\\t"}{.metadata.name}{"\\n"}{end}' | sort | tail -n1 | cut -f2)"
kubectl -n "${NS}" exec "${APP_POD}" -c gibbon -- sh -lc 'ls -ld /var/www/html/gibbon/i18n || true'
kubectl -n "${NS}" exec "${APP_POD}" -c gibbon -- sh -lc 'ls -l /var/www/html/gibbon/finance_report.php || echo "finance_report.php missing"'

echo "[5] Ingress"
kubectl -n "${NS}" apply -f k8s/gibbon-ingress.yaml

echo "==== DEV Deployed with ${IMG} ===="
kubectl -n "${NS}" get all
kubectl -n "${NS}" get pvc
'''
          }
        }
      }
    }
  }
}
