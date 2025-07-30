
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

  stages {
    stage('Checkout') {
      steps {
        git branch: 'demo-main', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    stage('Deploy Gibbon Demo') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: 'kubeconfig-jenkins']) {
            sh '''
              echo "Deleting old PVC and Deployment..."
              kubectl delete deployment gibbon-app -n demo-app-deployment --ignore-not-found=true
              kubectl delete deployment gibbon-mysql -n demo-app-deployment --ignore-not-found=true
              kubectl delete job gibbon-mysql-grant-job -n demo-app-deployment --ignore-not-found=true

              kubectl delete pvc gibbon-uploads-pvc -n demo-app-deployment --ignore-not-found=true
              kubectl delete pvc gibbon-mysql-pvc -n demo-app-deployment --ignore-not-found=true

              echo "====== Force remove stuck PVs ======"
              sleep 3
              kubectl patch pv gibbon-mysql-pv --type=merge -p '{"metadata":{"finalizers":[]}}' || true
              kubectl patch pv gibbon-uploads-pv --type=merge -p '{"metadata":{"finalizers":[]}}' || true

              kubectl delete pv gibbon-mysql-pv --grace-period=0 --wait=false --ignore-not-found=true
              kubectl delete pv gibbon-uploads-pv --grace-period=0 --wait=false --ignore-not-found=true
              echo "====== Wait for cleanup to complete ======"
              sleep 5

 
              echo "Applying Kubernetes manifests..."

              echo "[5] Apply PVC and PV..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-uploads-pv-pvc.yaml

              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-secret.yaml || true
              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-init-configmap.yaml

              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-service.yaml
              
              echo "[7] Sleep 15s to allow MySQL to come online..."
              sleep 15

              echo "[8] Deploy Gibbon app + service + ingress..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-service.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-ingress.yaml

              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-grant-job.yaml
              echo "---- Forcing rollout restart of gibbon-app ----"
              kubectl rollout restart deployment gibbon-app -n demo-app-deployment
        
            '''
          }
        }
      }
    }
  }
}
