pipeline {
  agent {
    kubernetes {
      defaultContainer 'kubectl'
      yaml """
apiVersion: v1
kind: Pod
spec:
  containers:
    - name: kubectl
      image: ntony3419/k8s-agent:1.3
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
              echo "Applying Kubernetes manifests..."
              kubectl apply -n demo-app-deployment -f k8s/gibbon-db-secret.yaml || true
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-uploads-pv-pvc.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-mysql-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-deployment.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-service.yaml
              kubectl apply -n demo-app-deployment -f k8s/gibbon-ingress.yaml
            '''
          }
        }
      }
    }
  }
}
