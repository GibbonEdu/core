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
        
    stage('Deploy Gibbon Demo') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''            
              echo "Applying Kubernetes manifests..."

              echo "[1] Apply PVC and PV..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-uploads-pv-pvc.yaml

              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-secret.yaml || true
              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-init-configmap.yaml

              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-deployment.yaml

              echo "[6] Apply MySQL reset job..."
              kubectl delete job gibbon-reset-job -n demo-app-deployment --ignore-not-found=true
              kubectl apply -n demo-app-deployment -f k8s/gibbon-reset-job.yaml
              sleep 10
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-service.yaml
              
              echo "[7] Sleep 15s to allow MySQL to come online..."
              sleep 15

              echo "[8] Deploy Gibbon app + service + ingress..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-service.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-ingress.yaml

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
