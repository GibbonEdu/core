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
              kubectl delete pvc gibbon-uploads-pvc -n demo-app-deployment --ignore-not-found=true
              kubectl delete pvc gibbon-mysql-pvc -n demo-app-deployment --ignore-not-found=true
              kubectl delete pv gibbon-uploads-pv --ignore-not-found

              echo "Deleting old PV (if exists)..."
              kubectl delete pv gibbon-uploads-pv --ignore-not-found
            
              echo "Waiting for cleanup to settle..."
              sleep 5
 
              echo "Applying Kubernetes manifests..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-secret.yaml || true
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-uploads-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-service.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-ingress.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-service.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-init-configmap.yaml
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
