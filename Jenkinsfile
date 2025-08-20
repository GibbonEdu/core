// Jenkinsfile — DEV with Kaniko (fixed declarative placement + init patch)

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
    # Project regcred-kaniko's .dockerconfigjson as config.json for Kaniko
    - name: docker-config
      projected:
        sources:
          - secret:
              name: regcred-kaniko
              items:
                - key: .dockerconfigjson
                  path: config.json
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

  options {
    // prevent implicit "Declarative: Checkout SCM" on controller
    skipDefaultCheckout(true)
    // optional: disableConcurrentBuilds()
    // optional: timestamps()
  }

  parameters {
    choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
    string(name: 'NAMESPACE',  defaultValue: 'gibbon-dev-deploy',  description: 'K8s namespace')
    string(name: 'GIT_BRANCH', defaultValue: 'gibbon-dev',         description: 'Git branch to build')
    string(name: 'REGISTRY',   defaultValue: 'index.docker.io',    description: 'Docker registry')
    string(name: 'IMAGE_REPO', defaultValue: 'ntony3419/gibbon',   description: 'Image repo (e.g. user/repo)')
    string(name: 'I18N_COMMIT',defaultValue: 'refs/heads/main',    description: 'Gibbon i18n commit/branch for VI')
  }

  environment {
    NS = "${params.NAMESPACE}"
  }

  stages {

    stage('Checkout (single)') {
      steps {
        container('kubectl') {
          // Validate branch exists
          sh '''
            set -euo pipefail
            git ls-remote --heads https://github.com/ntony3419/GibbonEdu-core.git "${GIT_BRANCH}" >/dev/null
          '''
          checkout([
            $class: 'GitSCM',
            branches: [[name: "*/${params.GIT_BRANCH}"]],
            userRemoteConfigs: [[url: 'https://github.com/ntony3419/GibbonEdu-core.git']]
          ])
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
          sh '''
            set -euo pipefail
            COMMIT=$(cat .gitshort)
            IMAGE="${REGISTRY}/${IMAGE_REPO}:${ENV}-${COMMIT}"
            echo "Building: ${IMAGE}"

            /kaniko/executor \
              --context="${WORKSPACE}" \
              --dockerfile="${WORKSPACE}/Dockerfile.gibbon" \
              --destination="${IMAGE}" \
              --snapshotMode=redo \
              --reproducible \
              --build-arg I18N_COMMIT="${I18N_COMMIT}"

            echo -n "${IMAGE}" > image.txt
          '''
        }
      }
    }

    stage('Deploy / Update Image') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
              set -euo pipefail
              IMG="$(cat image.txt)"

              # Apply manifests (idempotent)
              kubectl apply -n "${NS}" -f k8s/gibbon-mysql-deployment.yaml || true
              kubectl apply -n "${NS}" -f k8s/gibbon-deployment.yaml
              kubectl apply -n "${NS}" -f k8s/gibbon-ingress.yaml

              # Update main app container
              kubectl -n "${NS}" set image deployment/gibbon-dev-app gibbon="${IMG}"

              # Update init-gibbon (initContainer) via strategic merge patch
              cat <<EOF >/tmp/initpatch.yaml
spec:
  template:
    spec:
      initContainers:
      - name: init-gibbon
        image: ${IMG}
EOF
              kubectl -n "${NS}" patch deployment gibbon-dev-app --type=strategic --patch-file /tmp/initpatch.yaml

              # Rollout
              kubectl rollout status deployment gibbon-dev-app -n "${NS}" --timeout=300s
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
