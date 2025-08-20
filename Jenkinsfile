// Jenkinsfile — DEV with Kaniko

parameters {
  choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
  string(name: 'NAMESPACE', defaultValue: 'gibbon-dev-deploy', description: 'K8s namespace')
  string(name: 'GIT_BRANCH', defaultValue: 'gibbon-dev', description: 'Git branch to build')
  string(name: 'REGISTRY', defaultValue: 'index.docker.io', description: 'Docker registry server')
  string(name: 'IMAGE_REPO', defaultValue: 'ntony3419/gibbon', description: 'Image repo (e.g., dockerhub_user/repo)')
  string(name: 'I18N_COMMIT', defaultValue: 'refs/heads/main', description: 'Gibbon i18n commit/branch for VI')
}

options {
  // stop Jenkins from doing implicit declarative : checkout scm
  skipDefaultCheckout(true)
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
  volumes:
    - name: docker-config
      secret:
        secretName: regcred-kaniko
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
      args: ["sleep 9999999"]
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
"""
    }
  }

  stages {
    stage('Checkout') {
      steps {
        container('kubectl') {
          git branch: "${params.GIT_BRANCH}", url: 'https://github.com/ntony3419/GibbonEdu-core.git'
          sh 'git rev-parse --short=12 HEAD > .gitshort'
        }
      }
    }

    stage('Ensure Namespace & Staging ClusterIssuer') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh """
              kubectl create ns "${NS}" --dry-run=client -o yaml | kubectl apply -f -
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
            """
          }
        }
      }
    }

    stage('Build & Push (Kaniko)') {
      steps {
        container('kaniko') {
          // REGISTRY auth is provided via /kaniko/.docker/config.json from regcred-kaniko
          sh """
            set -euo pipefail
            COMMIT=\$(cat .gitshort)
            IMAGE="${params.REGISTRY}/${params.IMAGE_REPO}:${params.ENV}-\${COMMIT}"
            echo "Building: \$IMAGE"

            /kaniko/executor \
              --context="${WORKSPACE}" \
              --dockerfile="${WORKSPACE}/Dockerfile.gibbon" \
              --destination="\$IMAGE" \
              --snapshotMode=redo \
              --reproducible \
              --build-arg I18N_COMMIT="${params.I18N_COMMIT}"

            echo -n "\$IMAGE" > image.txt
          """
        }
      }
    }

    stage('Deploy / Update Image') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
              set -euo pipefail
              NS='"'"'${NS}'"'"'
              IMG="$(cat image.txt)"

              echo "[Apply base manifests if needed]"
              kubectl apply -n "$NS" -f k8s/gibbon-mysql-deployment.yaml || true
              kubectl apply -n "$NS" -f k8s/gibbon-deployment.yaml
              kubectl apply -n "$NS" -f k8s/gibbon-ingress.yaml

              echo "[Patch Deployment images: app + init]"
              # Update both the main container and the init-gibbon initContainer to the same image
              kubectl -n "$NS" set image deployment/gibbon-dev-app gibbon="$IMG" init-gibbon="$IMG"

              echo "[Rollout]"
              kubectl rollout status deployment gibbon-dev-app -n "$NS" --timeout=300s

              echo "Deployed image: $IMG"
            '''
          }
        }
      }
    }

    stage('Post-Deploy Smoke') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
              set -euo pipefail
              APP_POD=$(kubectl -n "${NS}" get pod -l app=gibbon-dev -o jsonpath='{.items[0].metadata.name}')
              kubectl -n "${NS}" exec "$APP_POD" -c gibbon -- php -v || true
              kubectl -n "${NS}" get deploy,svc,ing,pvc
            '''
          }
        }
      }
    }
  }
}
