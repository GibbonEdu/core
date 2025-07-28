pipeline {
  agent {
    kubernetes {
      label 'kubectl-agent'
      defaultContainer 'kubectl'
      yaml """
apiVersion: v1
kind: Pod
spec:
  containers:
  - name: kubectl
    image: ubuntu:22.04
    command:
    - bash
    - -c
    args:
    - cat
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

    stage('Install kubectl') {
      steps {
        container('kubectl') {
          sh '''
            apt-get update && apt-get install -y curl ca-certificates gnupg apt-transport-https
            curl -fsSL https://pkgs.k8s.io/core:/stable:/v1.29/deb/Release.key | gpg --dearmor -o /etc/apt/trusted.gpg.d/kubernetes.gpg
            echo "deb https://pkgs.k8s.io/core:/stable:/v1.29/deb/ /" > /etc/apt/sources.list.d/kubernetes.list
            apt-get update
            apt-get install -y kubectl
            kubectl version --client
          '''
        }
      }
    }

    stage('Deploy Gibbon Demo') {
      steps {
        container('kubectl') {
          withKubeConfig([credentialsId: "kubeconfig-jenkins"]) {
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
