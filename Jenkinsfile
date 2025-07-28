pipeline {
  agent {
    kubernetes {
      label 'kubectl-agent'                // REQUIRED: must match your Pod Template's label
      inheritFrom  'kubectl-agent'             // This must match the "Labels" field in your pod template
      defaultContainer 'kubectl'        // This is the container where kubectl commands will run
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
