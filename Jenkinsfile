// Jenkinsfile — DEV with Kaniko (CLI checkout + safe.directory)

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
      projected:
        sources:
          - secret:
              name: regcred-kaniko
              items:
                - key: .dockerconfigjson
                  path: config.json
    - name: workspace-volume
      emptyDir: {}
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

    - name: scm
      image: alpine/git:latest
      imagePullPolicy: Always
      command: ["/bin/sh","-c"]
      args: ["sleep 9999999"]
      # Optional: match JNLP uid/gid to avoid ownership checks entirely
      securityContext:
        runAsUser: 1000
        runAsGroup: 1000
        fsGroup: 1000
      env:
        - name: HOME
          value: /home/jenkins/agent
      volumeMounts:
        - name: workspace-volume
          mountPath: /home/jenkins/agent
    - name: jnlp
      resources:
        requests: { cpu: "100m", memory: "256Mi" }
        limits:   { memory: "512Mi" }
    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      imagePullPolicy: Always
      command: ["/busybox/sh","-c"]
      args: ["sleep 9999999"]
      env:
        - name: DOCKER_CONFIG
          value: /kaniko/.docker
      resources:
        requests:
          cpu: "250m"
          memory: "512Mi"
        limits:
          cpu: "2"
          memory: "4Gi"
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
        - name: workspace-volume
          mountPath: /home/jenkins/agent
"""
      podRetention never()
      idleMinutes 0
      activeDeadlineSeconds 3600
    }
  }

  options {


    disableConcurrentBuilds(abortPrevious: true)
    buildDiscarder(logRotator(daysToKeepStr: '14', numToKeepStr: '30'))
    timeout(time: 60, unit: 'MINUTES')
    skipDefaultCheckout(true)


  }

  parameters {
    choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
    string(name: 'NAMESPACE',  defaultValue: 'gibbon-dev-deploy',  description: 'K8s namespace')
    string(name: 'GIT_BRANCH', defaultValue: 'gibbon-dev',         description: 'Git branch to build')
    string(name: 'REGISTRY',   defaultValue: 'docker.io',          description: 'Docker registry')
    string(name: 'IMAGE_REPO', defaultValue: 'ntony3419/gibbon',   description: 'Image repo (e.g. user/repo)')
    string(name: 'I18N_COMMIT',defaultValue: 'refs/heads/main',    description: 'Gibbon i18n commit/branch for VI')
    string(name: 'OPENAI_SECRET_NAME', defaultValue: 'gibbon-dev-openai', description: 'K8s Secret name that holds OPENAI_API_KEY')
  }

  environment {
    NS = "${params.NAMESPACE}"
    OPENAI_SECRET_NAME = "${params.OPENAI_SECRET_NAME}"
  }

  stages {

    stage('Checkout (CLI in scm)') {
      steps {
        container('scm') {
          sh '''
            set -eu
            export HOME=/home/jenkins/agent
    
            git --version
            # Whitelist the workspace in case ownership is mixed
            git config --global --add safe.directory "${WORKSPACE}" || true
    
            # Fresh checkout (no Jenkins Git plugin here)
            rm -rf .git || true
            git init
            git remote add origin https://github.com/ntony3419/GibbonEdu-core.git
            git fetch --depth 1 origin "${GIT_BRANCH}"
            git checkout -qf FETCH_HEAD
    
            git rev-parse --short=12 HEAD > .gitshort
          '''
        }
      }
    }

    stage('Ensure Namespace & Staging ClusterIssuer') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh """
              set -eu
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
          sh '''
          set -eu
          COMMIT=$(cat .gitshort)
          TAG="${ENV}-${COMMIT}"
          IMAGE_REPO_FULL="${REGISTRY}/${IMAGE_REPO}"
          IMAGE="${IMAGE_REPO_FULL}:${TAG}"
          CACHE_REPO="${IMAGE_REPO_FULL}-cache"

          echo "Building: ${IMAGE}"

          /kaniko/executor \
            --context="${WORKSPACE}" \
            --dockerfile="${WORKSPACE}/Dockerfile.gibbon" \
            --destination="${IMAGE}" \
            --snapshot-mode=redo \
            --single-snapshot \
            --use-new-run \
            --compression=gzip \
            --compression-level=1 \
            --push-retry=3 \
            --reproducible \
            --cache=true \
            --cache-repo="${CACHE_REPO}" \
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
          
             
            set -eu
            IMG="$(cat image.txt)"
            SEC="${OPENAI_SECRET_NAME}"

          # Apply DB bits and wait for MySQL to be Ready
                              
          kubectl apply -n "${NS}" -f k8s/gibbon-mysql-deployment.yaml || true                            
          kubectl -n "${NS}" rollout status deployment gibbon-dev-mysql --timeout=300s
          kubectl -n "${NS}" wait --for=condition=ready pod -l app=gibbon-dev-mysql --timeout=300s
          
          # App manifests
          kubectl apply -n "${NS}" -f k8s/gibbon-deployment.yaml
          kubectl apply -n "${NS}" -f k8s/gibbon-ingress.yaml

          # Update main app container image
          kubectl -n "${NS}" set image deployment/gibbon-dev-app gibbon="${IMG}"

          # Patch the init container correctly and stamp the pod template with Secret RV

          RV="$(kubectl -n "${NS}" get secret "${SEC}" -o jsonpath='{.metadata.resourceVersion}')"
          SUM="$(kubectl -n "${NS}" get secret "${SEC}" -o jsonpath='{.data.OPENAI_API_KEY}' | sha256sum | awk '{print $1}')"
          
          cat <<EOF >/tmp/patch.yaml
spec:
  template:
    metadata:
      annotations:
        secret.openai.rv: "${RV}"
        secret.openai.sum: "${SUM}"
    spec:
      initContainers:
      - name: init-gibbon-data        
        command: ["/bin/sh","-c"]
        args: ["echo warmup; exit 0"]
      - name: init-seed-i18n
        image: ${IMG}
EOF

          kubectl -n "${NS}" patch deployment gibbon-dev-app --type=strategic --patch-file /tmp/patch.yaml
          kubectl -n "${NS}" rollout status deploy/gibbon-dev-app --timeout=300s
          kubectl -n "${NS}" wait --for=condition=ready pod -l app=gibbon-dev --timeout=300s

          # Verify
          echo "[Verify] Secret tail from API:"
          kubectl -n "${NS}" get secret "${SEC}" -o jsonpath='{.data.OPENAI_API_KEY}' | base64 -d | \
            awk '{print substr($0,1,6)"…"(length>4?substr($0,length-3):$0)}'; echo

          
          for p in $(kubectl -n "${NS}" get pod -l app=gibbon-dev -o jsonpath='{.items[?(@.status.phase=="Running")].metadata.name}'); do
            echo " - $p:"
                kubectl -n "${NS}" exec "$p" -c gibbon -- sh -lc \
          echo "[Verify] Mounted file tail in pod:"
          kubectl -n "${NS}" exec "$APP_POD" -c gibbon -- sh -lc \
            'ls -l /run/secrets/openai || true; head -c 6 /run/secrets/openai/OPENAI_API_KEY; echo -n "…"; tail -c 4 /run/secrets/openai/OPENAI_API_KEY; echo' || true

          echo "[Verify] Apache env (should NOT contain OPENAI_API_KEY):"
          for p in $(kubectl -n "${NS}" get pod -l app=gibbon-dev -o jsonpath='{.items[?(@.status.phase=="Running")].metadata.name}'); do
                echo " - $p:"
                kubectl -n "${NS}" exec "$p" -c gibbon -- sh -lc \
                  'PID=$(pgrep -xo apache2 || pgrep -xo httpd || true); [ -n "$PID" ] && tr "\\0" "\\n" < /proc/$PID/environ | grep "^OPENAI_API_KEY=" || echo "(none)"' || true
              done
          echo "[Verify] PHP getApiKey():"
          for p in $(kubectl -n "${NS}" get pod -l app=gibbon-dev -o jsonpath='{.items[?(@.status.phase=="Running")].metadata.name}'); do
                echo " - $p:"
            kubectl -n "${NS}" exec "$p" -c gibbon -- sh -lc '
                  cat >/var/www/html/gibbon/_whichkey.php <<PHP
                  <?php
                  require __DIR__ . "/openai.php";
                  \$k = getApiKey(false);
                  header("Content-Type: text/plain; charset=UTF-8");
                  echo \$k ? substr(\$k,0,6)."…".substr(\$k,-4)." (len:".strlen(\$k).")\\n" : "MISSING\\n";
                  PHP
                  php -d display_errors=1 /var/www/html/gibbon/_whichkey.php
                  rm -f /var/www/html/gibbon/_whichkey.php
                ' || true
              done
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
              set -eu
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
