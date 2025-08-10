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
            sh '''
              echo "==== 🧹 HARD RESET GIBBON DEPLOYMENT ===="
    
              echo "[1] Deleting deployments, services, jobs, ingresses, PVCs, PVs, secrets, configmaps..."
              kubectl delete deployment gibbon-app gibbon-mysql -n demo-app-deployment --ignore-not-found=true
              kubectl delete svc gibbon-service gibbon-mysql -n demo-app-deployment --ignore-not-found=true
              kubectl delete ingress gibbon-ingress -n demo-app-deployment --ignore-not-found=true
              kubectl delete job gibbon-reset-job gibbon-mysql-fix-grant -n demo-app-deployment --ignore-not-found=true
              kubectl delete pvc gibbon-uploads-pvc gibbon-mysql-pvc -n demo-app-deployment --ignore-not-found=true
              kubectl delete secret gibbon-db-secret gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
              kubectl delete configmap gibbon-db-init -n demo-app-deployment --ignore-not-found=true
              kubectl delete certificate gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
    
              echo "[2] Removing PVs forcibly..."
              kubectl patch pv gibbon-mysql-pv --type=merge -p '{"metadata":{"finalizers":[]}}' || true
              kubectl patch pv gibbon-uploads-pv --type=merge -p '{"metadata":{"finalizers":[]}}' || true
              kubectl delete pv gibbon-mysql-pv --grace-period=0 --wait=false --ignore-not-found=true
              kubectl delete pv gibbon-uploads-pv --grace-period=0 --wait=false --ignore-not-found=true
    
              echo "[3] Wait for full cleanup..."
              sleep 10
    
              echo "==== ✅ GIBBON ENVIRONMENT CLEANED ===="
            '''
          }
        }
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

              kubectl delete certificate gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
              kubectl delete secret gibbon-demo-tls -n demo-app-deployment --ignore-not-found=true
            '''
          }
        }
      }
    }


        
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
              kubectl apply -f k8s/gibbon-deployment.yaml
              echo "[3.1] Wait for gibbon-app rollout..."
              kubectl rollout status deployment gibbon-app -n demo-app-deployment --timeout=180s || true
              echo "[4] Ingress (staging TLS via cert-manager)..."
              kubectl apply -f k8s/gibbon-ingress.yaml
              echo "---- Optionally force a restart after PVC/perm init (usually not needed) ----"
              kubectl rollout restart deployment gibbon-app -n demo-app-deployment || true

#              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-grant-job.yaml
              echo "---- Forcing rollout restart of gibbon-app ----"
              kubectl rollout restart deployment gibbon-app -n demo-app-deployment
        
            '''
          }
        }
      }
    }
  }
}
