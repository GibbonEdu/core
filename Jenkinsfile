pipeline {
  agent any
  environment {
    KUBECONFIG_CRED = credentials('k8s-app-kubeconfig')
  }
  stages {
    stage('Checkout') {
      steps {
        git branch: 'demo-main', url: 'https://github.com/ntony3419/GibbonEdu-core.git'
      }
    }

    stage('Deploy Gibbon Demo') {
      steps {
        withKubeConfig([credentialsId: "k8s-app-kubeconfig"]) {
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
