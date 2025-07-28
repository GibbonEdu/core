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
      image: ghcr.io/carlosedp/kubectl:latest
      command:
        - /bin/sh
        - -c
      args:
        - sleep infinity
      tty: true
"""
    }
  }

  stages {
    stage('Test kubectl') {
      steps {
        container('kubectl') {
          sh '''
            echo "Testing kubectl..."
            kubectl version --client
            nslookup google.com
            curl -I https://google.com
          '''
        }
      }
    }
  }
}
