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
        git branch: 'demo-main', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    
    stage('Hard Reset Gibbon Deployment') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''#!/usr/bin/env bash 
              set -euo pipefail
              NS=demo-app-deployment
              echo "==== 🧹 HARD RESET GIBBON DEPLOYMENT ===="
              # 0) Ensure namespace exists (idempotent)
              kubectl get ns "$NS" >/dev/null 2>&1 || kubectl create ns "$NS"
              echo "[1] Deleting deployments, services, jobs, ingresses, PVCs, PVs, secrets, configmaps..."
              kubectl delete deployment gibbon-app gibbon-mysql -n "$NS" --ignore-not-found=true
              kubectl delete svc gibbon-service gibbon-mysql -n "$NS" --ignore-not-found=true
              kubectl delete ingress gibbon-ingress -n "$NS" --ignore-not-found=true
              kubectl delete job gibbon-reset-job gibbon-mysql-fix-grant gibbon-mysql-grant-job -n "$NS" --ignore-not-found=true
              kubectl delete pvc gibbon-uploads-pvc gibbon-mysql-pvc -n demo-app-deployment --ignore-not-found=true

              echo "[1.2] Delete orphaned ReplicaSets/Pods by label (belt & suspenders)..."
              kubectl delete rs -n "$NS" -l app=gibbon --ignore-not-found=true
              kubectl delete rs -n "$NS" -l app=gibbon-mysql --ignore-not-found=true
              kubectl delete pod -n "$NS" -l app=gibbon --ignore-not-found=true --force --grace-period=0 || true
              kubectl delete pod -n "$NS" -l app=gibbon-mysql --ignore-not-found=true --force --grace-period=0 || true
              

              echo "[1.3] TLS/cert resources (namespaced)..."
              kubectl delete certificate gibbon-demo-tls -n "$NS" --ignore-not-found=true
              # Clean up any previous ACME Challenges/Orders created by cert-manager
              kubectl delete challenges.acme.cert-manager.io -n "$NS" --all --ignore-not-found=true || true
              kubectl delete orders.acme.cert-manager.io -n "$NS" --all --ignore-not-found=true || true

echo "[1.4] Config & Secrets..."
kubectl delete configmap gibbon-db-init -n "$NS" --ignore-not-found=true
kubectl delete secret gibbon-db-secret gibbon-demo-tls -n "$NS" --ignore-not-found=true

echo "[2] Volumes: PVCs and PVs..."
kubectl delete pvc gibbon-uploads-pvc gibbon-mysql-pvc -n "$NS" --ignore-not-found=true

# Remove PV finalizers if stuck, then delete
for PV in gibbon-mysql-pv gibbon-uploads-pv; do
  kubectl patch pv "$PV" --type=merge -p '{"metadata":{"finalizers":[]}}' 2>/dev/null || true
  kubectl delete pv "$PV" --grace-period=0 --wait=false --ignore-not-found=true || true
done

echo "[3] Wait for deletion to finish (deployments/rs/pods/pvc/ingress/svc)..."
kubectl wait --for=delete deployment/gibbon-app -n "$NS" --timeout=60s || true
kubectl wait --for=delete deployment/gibbon-mysql -n "$NS" --timeout=60s || true
# Wait for all pods with our labels to be gone
for L in app=gibbon app=gibbon-mysql; do
  kubectl get pods -n "$NS" -l "$L" --no-headers 2>/dev/null | awk '{print $1}' | \
    xargs -r -I{} kubectl wait --for=delete pod/{} -n "$NS" --timeout=60s || true
done
# PVCs
for PVC in gibbon-uploads-pvc gibbon-mysql-pvc; do
  kubectl wait --for=delete pvc/$PVC -n "$NS" --timeout=60s || true
done
# Services/Ingress
kubectl wait --for=delete svc/gibbon-service -n "$NS" --timeout=30s || true
kubectl wait --for=delete svc/gibbon-mysql -n "$NS" --timeout=30s || true
kubectl wait --for=delete ingress/gibbon-ingress -n "$NS" --timeout=30s || true

echo "==== ✅ GIBBON ENVIRONMENT CLEANED ===="
kubectl get all -n "$NS" || true
kubectl get pv,pvc -A || true


              echo "[3] Wait for full cleanup..."
              sleep 10
    
              echo "==== ✅ GIBBON ENVIRONMENT CLEANED ===="
            '''
          }
        }
      }
    }
/*
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

              kubectl delete certificate gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
              kubectl delete secret gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
            '''
          }
        }
      }
    }

*/
  /*      
    stage('Deploy Gibbon Demo') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''            
              echo "Applying Kubernetes manifests..."

              echo "[1] Apply PVC and PV..."
  #            echo "[6] Apply MySQL reset job..."
  #            kubectl delete job gibbon-reset-job -n demo-app-deployment --ignore-not-found=true
  #            kubectl apply -n demo-app-deployment -f k8s/gibbon-reset-job.yaml
  #            sleep 10
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-secret.yaml              
              echo "[2] MySQL stack (Deployment + PV + PVC + Service [+ optional GRANT Job])..."
              kubectl apply -f k8s/gibbon-mysql-deployment.yaml
              echo "[2.1] Wait for MySQL to be rolling out..."
              kubectl rollout status deployment gibbon-mysql -n demo-app-deployment --timeout=120s || true
              echo "[2.2] Give MySQL a few more seconds to accept connections..."
              sleep 15
              
              echo "[3] Gibbon app stack (Deployment + PV + PVC + Service)..."
              # IMPORTANT: in your repo, make sure gibbon-deployment.yaml orders objects as:
              # PV -> PVC -> Service -> Deployment (so fresh clusters bind the PVC before pods schedule)
              kubectl apply -f k8s/gibbon-deployment.yaml
              
              # 5) (Optional but nice) Wait a bit for the uploads PVC to bind in fresh envs
              echo "[info] waiting for gibbon-uploads-pvc to be Bound..."
              for i in $(seq 1 60); do
                phase=$(kubectl get pvc gibbon-uploads-pvc -n demo-app-deployment -o jsonpath='{.status.phase}' 2>/dev/null || true)
                [ "$phase" = "Bound" ] && break
                sleep 2
              done
              kubectl get pvc gibbon-uploads-pvc -n demo-app-deployment

              # 6) Wait for gibbon-app rollout
              kubectl rollout status deployment gibbon-app -n demo-app-deployment --timeout=300s

              # 7) Ingress (fresh env so no patching; just apply)
              kubectl apply -f k8s/gibbon-ingress.yaml
              
              echo "==== Deployed ===="
              kubectl get all -n demo-app-deployment
              kubectl get pv,pvc -n demo-app-deployment
        
            '''
          }
        }
      }
    }*/
  }
}

