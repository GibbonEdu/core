// Jenkinsfile — DEV with Kaniko (CLI checkout + safe.directory)

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
  volumes:
    - name: docker-config
      projected:
        sources:
          - secret:
              name: regcred-kaniko
              items:
                - key: .dockerconfigjson
                  path: config.json
    - name: workspace-volume
      emptyDir: {}
  containers:
    - name: kubectl
      image: ntony3419/k8s-agent:1.3
      imagePullPolicy: Always
      command: ["/bin/bash","-c"]
      args: ["sleep infinity"]
      tty: true
      volumeMounts:
        - name: workspace-volume
          mountPath: /home/jenkins/agent

    - name: scm
      image: alpine/git:latest
      imagePullPolicy: Always
      command: ["/bin/sh","-c"]
      args: ["sleep 9999999"]
      # Optional: match JNLP uid/gid to avoid ownership checks entirely
      securityContext:
        runAsUser: 1000
        runAsGroup: 1000
        fsGroup: 1000
      volumeMounts:
        - name: workspace-volume
          mountPath: /home/jenkins/agent

    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      imagePullPolicy: Always
      command: ["/busybox/sh","-c"]
      args: ["sleep 9999999"]
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
        - name: workspace-volume
          mountPath: /home/jenkins/agent
"""
    }
  }

  options {
    // We do our own CLI checkout; don’t let Jenkins do an implicit checkout.
    skipDefaultCheckout(true)
    // timestamps()
    // disableConcurrentBuilds()
  }

  parameters {
    choice(name: 'ENV', choices: ['dev','demo','prod'], description: 'Target env')
    string(name: 'NAMESPACE',  defaultValue: 'gibbon-dev-deploy',  description: 'K8s namespace')
    s
