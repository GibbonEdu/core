//Jenkinsfile
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
      command:
        - /bin/bash
        - -c
      args:
        - sleep infinity
      tty: true
"""
    }
  }

  
    // Optional: uncomment when full environment reset is required
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
              echo "==== ⚙️ Apply Let's Encrypt Staging Issuer ===="

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

              echo "[INFO] Staging ClusterIssuer ready."

              kubectl delete certificate gibbon-dev-tls  -n gibbon-dev  --ignore-not-found=true
              kubectl delete secret gibbon-dev-tls -n gibbon-dev  --ignore-not-found=true
            '''
          }
        }
      }
    }

    stage('Deploy Gibbon Development') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''#!/usr/bin/env bash
set -euo pipefail
NS=gibbon-dev            
              echo "Applying Kubernetes manifests..."

              echo "[1] Secret for DB creds"
              kubectl apply -n "$NS" -f k8s/gibbon-mysql-secret.yaml              
              echo "[2] MySQL stack (Deployment + PV + PVC + Service [+ optional GRANT Job])..."
              kubectl apply -f k8s/gibbon-mysql-deployment.yaml
              echo "[2.1] Wait for MySQL to be rolling out..."
              kubectl rollout status deployment gibbon-mysql -n "$NS" --timeout=180s || true
              echo "[2.2] Give MySQL a few more seconds to accept connections..."
              sleep 15
              
              echo "[3] Gibbon app stack (Deployment + PV + PVC + Service)..."
              # IMPORTANT: in your repo, make sure gibbon-deployment.yaml orders objects as:
              # PV -> PVC -> Service -> Deployment (so fresh clusters bind the PVC before pods schedule)
              kubectl apply -f k8s/gibbon-deployment.yaml
              
              # 5) (Optional but nice) Wait a bit for the uploads PVC to bind in fresh envs
              echo "[info] waiting for gibbon-uploads-pvc to be Bound..."
              for i in $(seq 1 60); do
                phase=$(kubectl get pvc gibbon-dev-uploads-pvc -n "$NS" -o jsonpath='{.status.phase}' 2>/dev/null || true)
                [ "$phase" = "Bound" ] && break
                sleep 2
              done
              kubectl get pvc gibbon-dev-uploads-pvc -n "$NS"

              # 6) Wait for gibbon-app-dev rollout
              kubectl rollout status deployment gibbon-app-dev -n "$NS" --timeout=300s

              # 7) Ingress (fresh env so no patching; just apply)
              kubectl apply -f k8s/gibbon-ingress.yaml
              
              echo "==== Deployed ===="
              kubectl get all -n "$NS"
              kubectl get pv,pvc -n "$NS"
        
            '''
          }
        }
      }
    }
  }
}

